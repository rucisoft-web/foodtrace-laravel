<?php

namespace App\Services;

use App\Models\Document;
use App\Models\InventoryCheck;
use App\Models\Location;
use App\Models\Lpn;
use App\Models\Pod;
use App\Models\SapPosting;
use App\Models\SyncDocument;
use App\Models\Zone;
use App\Support\Wms;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;

/** Angka dashboard gudang: stok per zona, kedaluwarsa, arus barang, okupansi, outbound, SAP, dan daftar keputusan. */
class DashboardService
{
    public const BUCKETS = [
        ['expired', 'Kedaluwarsa', null, 0], ['m1', '≤ 1 bln', 0, 30], ['m2', '1-2 bln', 30, 60], ['m3', '2-3 bln', 60, 90],
        ['m4', '3-4 bln', 90, 120], ['m6', '4-6 bln', 120, 180], ['long', '> 6 bln', 180, null],
    ];

    public static function bucket(?float $days): string
    {
        if ($days === null) {
            return 'long';
        }
        foreach (self::BUCKETS as [$k, , $lo, $hi]) {
            if ($lo === null && $days < 0) {
                return $k;
            }
            if ($lo !== null && $days >= $lo && ($hi === null || $days < $hi)) {
                return $k;
            }
        }

        return 'long';
    }

    public static function summary(?string $zoneFilter = null): array
    {
        $now = now();
        $rows = DB::table('stocks as s')
            ->join('items as i', 'i.id', '=', 's.item_id')
            ->join('locations as l', 'l.id', '=', 's.location_id')
            ->leftJoin('lots as t', 't.id', '=', 's.lot_id')
            ->leftJoin('brands as b', 'b.id', '=', 'i.brand_id')
            ->leftJoin('uoms as u', 'u.id', '=', 'i.uom_id')
            ->where('s.qty', '>', 0)->where(fn ($q) => $q->where('l.area', '!=', 'prd')->orWhereNull('l.area'))
            ->when($zoneFilter, fn ($q) => $q->where('i.zone', $zoneFilter))
            ->select('s.qty', 's.lot_id', 's.lpn_id', 's.location_id', 'i.id as item_id', 'i.code', 'i.name', 'i.zone', 'i.cost', 'i.item_group',
                'l.area', 'l.full_name', 'l.name as loc', 't.name as lot', 't.expiry_date', 'b.code as brand', 'u.code as uom')
            ->get();

        $kpi = ['value' => 0, 'expired' => 0, 'near' => 0, 'm3' => 0, 'hold' => 0, 'receiving' => 0];
        $byZone = []; $byStatus = []; $byGroup = []; $byItem = []; $byBrand = []; $bucketZone = []; $near = [];
        $items = []; $lots = [];
        foreach ($rows as $r) {
            $v = $r->qty * $r->cost;
            $kpi['value'] += $v;
            $status = Wms::AREA_STATUS[$r->area] ?? 'available';
            $byStatus[$status] = ($byStatus[$status] ?? 0) + $v;
            $byZone[$r->zone]['value'] = ($byZone[$r->zone]['value'] ?? 0) + $v;
            $byZone[$r->zone]['items'][$r->item_id] = 1;
            $byGroup[$r->item_group ?: 'LAIN-LAIN'] = ($byGroup[$r->item_group ?: 'LAIN-LAIN'] ?? 0) + $v;
            $byBrand[$r->brand ?: '(tanpa brand)'] = ($byBrand[$r->brand ?: '(tanpa brand)'] ?? 0) + $v;
            $byItem[$r->item_id] ??= ['code' => $r->code, 'name' => $r->name, 'zone' => $r->zone, 'qty' => 0, 'uom' => $r->uom, 'value' => 0];
            $byItem[$r->item_id]['qty'] += $r->qty;
            $byItem[$r->item_id]['value'] += $v;
            $items[$r->item_id] = 1;
            if ($r->lot_id) {
                $lots[$r->lot_id] = 1;
            }
            if ($status === 'hold') {
                $kpi['hold'] += $v;
            }
            if ($status === 'receiving') {
                $kpi['receiving'] += $v;
            }
            $days = $r->expiry_date ? $now->diffInSeconds(Carbon::parse($r->expiry_date), false) / 86400 : null;
            $b = self::bucket($days);
            $bucketZone[$b][$r->zone] = ($bucketZone[$b][$r->zone] ?? 0) + $v;
            if ($days !== null) {
                if ($days < 0) {
                    $kpi['expired'] += $v;
                } elseif ($days <= 30) {
                    $kpi['near'] += $v;
                }
                if ($days >= 0 && $days <= 90) {
                    $kpi['m3'] += $v;
                }
                if ($days <= 30) {
                    $near[] = ['code' => $r->code, 'name' => $r->name, 'lot' => $r->lot, 'zone' => $r->zone, 'loc' => $r->full_name,
                        'days' => (int) floor($days), 'qty' => $r->qty, 'uom' => $r->uom, 'value' => $v, 'status' => $status];
                }
            }
        }
        usort($near, fn ($a, $b) => $a['days'] <=> $b['days']);
        arsort($byGroup);
        arsort($byBrand);
        uasort($byItem, fn ($a, $b) => $b['value'] <=> $a['value']);

        // Okupansi rak palet utuh per zona.
        $cap = Location::where('area', 'blk')->where('is_bin', true)->where('active', true)->selectRaw('eff_zone, count(*) c')->groupBy('eff_zone')->pluck('c', 'eff_zone');
        $occ = DB::table('stocks as s')->join('locations as l', 'l.id', '=', 's.location_id')->where('s.qty', '>', 0)->where('l.area', 'blk')->where('l.is_bin', true)
            ->selectRaw('l.eff_zone, count(distinct l.id) c')->groupBy('l.eff_zone')->pluck('c', 'eff_zone');

        // Arus barang 30 hari (nilai dan jumlah dokumen per hari).
        $start = $now->copy()->subDays(29)->startOfDay();
        $flowRows = DB::table('stock_moves')->where('date', '>=', $start)->whereIn('kind', ['in', 'out'])
            ->selectRaw('DATE(date) d, kind, SUM(value) v, COUNT(DISTINCT document_id) n')->groupBy('d', 'kind')->get();
        $flow = [];
        for ($i = 0; $i < 30; $i++) {
            $flow[$start->copy()->addDays($i)->toDateString()] = ['in' => 0, 'out' => 0, 'in_docs' => 0, 'out_docs' => 0];
        }
        foreach ($flowRows as $f) {
            if (isset($flow[$f->d])) {
                $flow[$f->d][$f->kind] = round($f->v);
                $flow[$f->d][$f->kind.'_docs'] = (int) $f->n;
            }
        }

        // Pipeline outbound dan pelanggan teratas 30 hari.
        $stages = Document::where('type', 'outbound')->where(fn ($q) => $q->whereIn('state', ['open', 'ready'])->orWhere('done_at', '>=', $now->copy()->subDays(7)))
            ->selectRaw('stage, count(*) c')->groupBy('stage')->pluck('c', 'stage');
        $customers = DB::table('stock_moves as m')->join('partners as p', 'p.id', '=', 'm.partner_id')->where('m.kind', 'out')->where('m.date', '>=', $start)
            ->selectRaw('p.name, SUM(m.value) v, COUNT(DISTINCT m.document_id) n')->groupBy('p.name')->orderByDesc('v')->limit(10)->get();
        $groups = DB::table('documents')->where('type', 'outbound')->where('done_at', '>=', $start)
            ->selectRaw("COALESCE(shipping_group,'UMUM') g, count(*) c")->groupBy('g')->orderByDesc('c')->pluck('c', 'g');
        $suppliers = DB::table('stock_moves as m')->join('partners as p', 'p.id', '=', 'm.partner_id')->where('m.kind', 'in')->where('m.date', '>=', $now->copy()->subDays(90))
            ->selectRaw('p.name, SUM(m.value) v')->groupBy('p.name')->orderByDesc('v')->limit(8)->get();

        $sap = SapPosting::selectRaw('state, count(*) c')->groupBy('state')->pluck('c', 'state');
        $low = DB::table('items as i')->leftJoin('stocks as s', 's.item_id', '=', 'i.id')->leftJoin('uoms as u', 'u.id', '=', 'i.uom_id')
            ->where('i.min_stock', '>', 0)->groupBy('i.id', 'i.code', 'i.name', 'i.min_stock', 'u.code', 'i.zone')
            ->selectRaw('i.id, i.code, i.name, i.zone, i.min_stock, u.code uom, COALESCE(SUM(s.qty),0) qty')
            ->havingRaw('COALESCE(SUM(s.qty),0) < i.min_stock')->orderByRaw('COALESCE(SUM(s.qty),0) / i.min_stock')->get();

        $openIn = Document::where('type', 'inbound')->whereIn('state', ['open', 'ready'])->count();
        $openOut = Document::where('type', 'outbound')->whereIn('state', ['open', 'ready']);
        $todayOut = (clone $openOut)->whereDate('scheduled_at', '<=', $now->toDateString())->count();
        $shortOut = (clone $openOut)->where('state', 'open')->count();
        $zones = Zone::map();

        // Daftar keputusan: hal yang perlu ditindaklanjuti hari ini.
        $decisions = [];
        $add = function ($level, $text, $url) use (&$decisions) { $decisions[] = compact('level', 'text', 'url'); };
        if ($kpi['expired'] > 0) { $add('red', 'Stok kedaluwarsa senilai '.Wms::money($kpi['expired']).' masih di rak: putuskan pemusnahan/retur dan pindahkan ke DMG.', route('reports.show', 'expired')); }
        if ($kpi['near'] > 0) { $add('amber', count(array_filter($near, fn ($n) => $n['days'] >= 0)).' baris stok kedaluwarsa ≤ 30 hari ('.Wms::money($kpi['near']).'): dorong penjualan/promo atau negosiasi retur.', route('reports.show', 'expired')); }
        if ($n = $sap['failed'] ?? 0) { $add('red', "{$n} posting SAP gagal: stok SAP dan WMS bisa berbeda sampai dikirim ulang.", route('sap.queue', ['state' => 'failed'])); }
        if ($n = SyncDocument::where('state', 'error')->count()) { $add('red', "{$n} dokumen SAP gagal masuk (ItemCode belum ada di master): lengkapi Item Master lalu proses ulang.", route('sap.sync', ['state' => 'error'])); }
        if ($shortOut) { $add('amber', "{$shortOut} outbound document belum tercukupi stoknya (short): cek PO masuk atau konfirmasi ke sales.", route('outbound.index', ['state' => 'open'])); }
        if ($low->count()) { $add('amber', $low->count().' item di bawah stok minimum: usulkan PO ke purchasing.', route('reports.show', 'item-low')); }
        if ($n = InventoryCheck::where('state', 'submitted')->count()) { $add('navy', "{$n} inventory check menunggu approval kepala gudang.", route('ic.index', ['state' => 'submitted'])); }
        if ($kpi['hold'] > 0) { $add('navy', 'Stok Hold/karantina senilai '.Wms::money($kpi['hold']).' menunggu keputusan QA (GOOD/DAMAGE).', route('qc.index')); }
        if ($n = Lpn::where('need_reprint', true)->count()) { $add('navy', "{$n} label LPN perlu dicetak ulang setelah inventory check.", route('lpn.index', ['reprint' => 1])); }
        if ($n = Pod::where('has_difference', true)->where('created_at', '>=', $now->copy()->subDays(14))->count()) { $add('amber', "{$n} POD dengan selisih qty diterima pelanggan: tindak lanjuti klaim.", route('pod.index', ['diff' => 1])); }
        foreach ($zones as $z => $zone) {
            $c = $cap[$z] ?? 0;
            if ($c && ($occ[$z] ?? 0) / $c >= 0.9) {
                $add('amber', "Rak {$zone->name} terisi ".round(($occ[$z] ?? 0) / $c * 100).'%: atur ulang slotting atau tunda PO besar.', route('reports.show', 'by-location'));
            }
        }

        return [
            'kpi' => $kpi + ['items' => count($items), 'lots' => count($lots), 'cartons' => DB::table('cartons')->whereIn('state', ['available', 'quarantine'])->count(),
                'open_in' => $openIn, 'today_out' => $todayOut, 'short_out' => $shortOut, 'low' => $low->count(),
                'sap_failed' => $sap['failed'] ?? 0, 'sap_queued' => $sap['queued'] ?? 0, 'sap_posted' => $sap['posted'] ?? 0,
                'occ_pct' => $cap->sum() ? round($occ->sum() / $cap->sum() * 100) : 0, 'lpns' => count(array_unique(array_filter($rows->pluck('lpn_id')->all())))],
            'zones' => $zones->map(fn ($z) => ['label' => $z->name, 'color' => $z->color, 'value' => round($byZone[$z->zone]['value'] ?? 0),
                'items' => count($byZone[$z->zone]['items'] ?? []), 'cap' => $cap[$z->zone] ?? 0, 'occ' => $occ[$z->zone] ?? 0])->values(),
            'zoneKeys' => $zones->keys()->values(),
            'status' => collect(Wms::STOCK_STATUS)->map(fn ($l, $k) => ['label' => $l, 'value' => round($byStatus[$k] ?? 0)])->values(),
            'expiry' => ['labels' => array_column(self::BUCKETS, 1),
                'by_zone' => $zones->keys()->mapWithKeys(fn ($z) => [$z => array_map(fn ($b) => round($bucketZone[$b[0]][$z] ?? 0), self::BUCKETS)])],
            'groups' => collect($byGroup)->take(10)->map(fn ($v, $k) => ['name' => $k, 'value' => round($v)])->values(),
            'brands' => collect($byBrand)->take(10)->map(fn ($v, $k) => ['name' => $k, 'value' => round($v)])->values(),
            'top' => collect($byItem)->take(10)->values(),
            'flow' => ['days' => array_map(fn ($d) => Carbon::parse($d)->format('d/m'), array_keys($flow)), 'in' => array_column($flow, 'in'), 'out' => array_column($flow, 'out'),
                'in_docs' => array_column($flow, 'in_docs'), 'out_docs' => array_column($flow, 'out_docs')],
            'stages' => collect(Wms::OUT_STAGES)->map(fn ($l, $k) => ['label' => $l, 'value' => $stages[$k] ?? 0])->values(),
            'customers' => $customers, 'shipGroups' => $groups, 'suppliers' => $suppliers,
            'sap' => ['Terkirim' => $sap['posted'] ?? 0, 'Antre' => $sap['queued'] ?? 0, 'Gagal' => $sap['failed'] ?? 0],
            'near' => array_slice($near, 0, 12), 'low' => $low->take(10), 'decisions' => $decisions,
        ];
    }
}
