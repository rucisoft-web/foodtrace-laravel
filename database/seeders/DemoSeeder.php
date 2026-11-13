<?php

namespace Database\Seeders;

use App\Models\Announcement;
use App\Models\BarcodeTemplate;
use App\Models\Brand;
use App\Models\Butcher;
use App\Models\Carton;
use App\Models\Document;
use App\Models\Item;
use App\Models\Loading;
use App\Models\Location;
use App\Models\Lot;
use App\Models\Lpn;
use App\Models\Partner;
use App\Models\PickingJob;
use App\Models\SapPosting;
use App\Models\Stock;
use App\Models\StockMove;
use App\Models\Uom;
use App\Models\User;
use App\Models\Warehouse;
use App\Services\ButcherService;
use App\Services\InboundService;
use App\Services\InventoryService;
use App\Services\OutboundService;
use App\Services\SapService;
use App\Services\StockService;
use App\Services\SyncService;
use App\Support\Seq;
use Illuminate\Database\Seeder;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;

/**
 * Data demo PT Sarana Kulina Intisejahtera dari tarikan riil SAP B1 (DB SARKUL, 13 Nov 2026), sama dengan
 * foodtrace_demo versi Odoo:
 *   data/sarkul/master_item.csv  842 item aktif: group, sub group, brand, satuan SAP, OnHand, ItemCost
 *   data/sarkul/po.csv           69 PO (239 baris) 1 Sep - 13 Nov 2026
 *   data/sarkul/so.csv           SO Closed 7 hari terakhir + semua SO Open
 *
 * Setiap PO dan SO masuk lewat jalur produksi (dokumen sinkron POR/SOR). PO Closed diterima (lot, LPN, dock,
 * container), SO Closed 7 hari terakhir dikirim FEFO, PO dan SO Open menjadi pekerjaan terbuka. Stok awal dihitung
 * mundur supaya stok akhir = OnHand SAP. Tanggal digeser: 13 Nov 2026 = hari demo dipasang.
 * Lot, kedaluwarsa, bin, palet, kendaraan dan pengemudi tidak ada di SAP dan dibangkitkan.
 */
class DemoSeeder extends Seeder
{
    private const DATA_DAY = '2026-11-13';
    private const HISTORY_DAYS = 7;
    private const SHELF_LIFE = ['frozen' => 540, 'chilled' => 120, 'dry' => 365];
    private const SHIPPING_GROUP = ['GR' => 'CABANG', 'EC' => 'E-COMMERCE', 'SM' => 'RITEL MODERN', 'HT' => 'HOTEL', 'RS' => 'RESTO', 'CT' => 'KATERING'];
    private const MIN_LIFE = ['SM' => 50, 'HT' => 60];
    private const PLATES = ['B 9123 KXT', 'B 9561 TRS', 'B 9802 UAK', 'B 9017 NDP', 'B 9442 SKI'];
    private const DRIVERS = ['Agus', 'Bambang', 'Dedi', 'Hendra', 'Rizal', 'Slamet'];

    private array $master; private array $po; private array $so; private array $rows;
    private array $items = []; private array $usedBins = []; private array $pck = []; private array $cursor = [];
    private int $shift; private Carbon $now; private Carbon $windowStart;
    private Warehouse $wh; private $docks; private ?Location $staging;

    private function rnd(int $a, int $b): int { return mt_rand($a, $b); }
    private function frand(): float { return mt_rand() / mt_getrandmax(); }
    private function pick(array $a) { return $a[array_rand($a)]; }

    private function csv(string $name): array
    {
        $f = fopen(__DIR__.'/data/sarkul/'.$name, 'r');
        $head = fgetcsv($f, escape: '\\');
        $rows = [];
        while (($r = fgetcsv($f, escape: '\\')) !== false) {
            $rows[] = array_combine($head, $r);
        }

        return $rows;
    }

    private function day(string $text): Carbon
    {
        return Carbon::parse($text)->addDays($this->shift)->startOfDay();
    }

    public function run(): void
    {
        @set_time_limit(0);
        @ini_set('memory_limit', '1024M');
        DB::connection()->disableQueryLog();
        mt_srand(95);
        $this->now = now()->startOfSecond();
        $this->shift = (int) Carbon::parse(self::DATA_DAY)->startOfDay()->diffInDays(now()->startOfDay());
        $this->master = $this->csv('master_item.csv');
        $this->po = $this->csv('po.csv');
        $this->so = $this->csv('so.csv');
        $this->rows = collect($this->master)->keyBy('item_code')->all();
        $this->windowStart = Carbon::parse(self::DATA_DAY)->subDays(self::HISTORY_DAYS - 1);
        $this->wh = Warehouse::orderBy('id')->firstOrFail();
        $this->docks = Location::where('area', 'rcv')->orderBy('name')->get();
        $this->staging = Location::where('area', 'out')->first();

        $this->users();
        $this->locations();
        $this->partners();
        $this->items();
        $this->openingStock();
        $this->command?->info('Demo: memutar ulang riwayat PO/SO...');
        foreach ($this->events() as $i => [$d, $kind, $num, $lines]) {
            $kind === 0 ? $this->receive($lines) : $this->ship($lines);
        }
        StockService::$forceDate = null;
        StockService::$skipChecks = false;
        $this->reconcileOnhand();
        $this->afterHistory();
        $this->openWork();
        $this->sapQueue();
        $this->inventoryAndButcher();
        $this->announcements();
        StockService::$forceDate = null;
        StockService::$skipChecks = false;
    }

    // ------------------------------------------------------------------ master

    private function users(): void
    {
        foreach ([
            ['hendra.kepala', 'Hendra Wijaya', 'manager'], ['sari.inbound', 'Sari Lestari', 'inbound'],
            ['dewi.outbound', 'Dewi Anggraini', 'outbound'], ['agus.operator', 'Agus Saputra', 'operator'],
            ['budi.operator', 'Budi Santoso', 'operator'], ['rina.qa', 'Rina Marlina', 'manager'],
        ] as [$u, $n, $r]) {
            User::firstOrCreate(['username' => $u], ['name' => $n, 'role' => $r, 'email' => $u.'@saranakulina.local', 'password' => 'foodtrace']);
        }
        auth()->setUser(User::where('username', 'admin')->first());
    }

    private function locations(): void
    {
        foreach (DatabaseSeeder::ZONE_CODE as $zone => $code) {
            $bins = Location::where('area', 'blk')->where('eff_zone', $zone)->where('is_bin', true)->pluck('id')->all();
            mt_srand(95 + strlen($zone));
            shuffle($bins);
            // ±85% posisi terisi, sisanya kosong untuk saran put away.
            $this->usedBins[$zone] = array_slice($bins, 0, (int) (count($bins) * 0.85));
            $this->pck[$zone] = Location::where('area', 'pck')->where('eff_zone', $zone)->value('id');
            $this->cursor[$zone] = 0;
        }
        mt_srand(95);
    }

    private function nextBin(string $zone): Location
    {
        $bins = $this->usedBins[$zone];
        $id = $bins[$this->cursor[$zone]++ % count($bins)];

        return Location::find($id);
    }

    private function partners(): void
    {
        $customers = []; $suppliers = [];
        foreach ($this->so as $r) { $customers[$r['card_code']] ??= $r['card_name']; }
        foreach ($this->po as $r) { $suppliers[$r['card_code']] ??= $r['card_name']; }
        ksort($customers); ksort($suppliers);
        foreach ($customers as $code => $name) {
            Partner::updateOrCreate(['code' => $code], ['name' => $name, 'is_destination' => true,
                'shipping_group' => self::SHIPPING_GROUP[substr($code, 0, 2)] ?? 'UMUM', 'city' => 'Jakarta']);
        }
        foreach ($suppliers as $code => $name) {
            // Pemasok impor (VI*) mengirim keju/daging berat aktual: toleransi lebih terima 2%.
            Partner::updateOrCreate(['code' => $code], ['name' => $name, 'is_source' => true, 'over_receive_pct' => str_starts_with($code, 'VI') ? 2 : 0]);
        }
    }

    private function items(): void
    {
        $uoms = Uom::pluck('id', 'code');
        $brands = [];
        foreach ($this->master as $r) {
            if ($r['brand'] && ! isset($brands[$r['brand']])) {
                [$code, $name] = array_map('trim', explode(' - ', $r['brand'], 2) + [1 => $r['brand']]);
                $brands[$r['brand']] = Brand::firstOrCreate(['code' => $code], ['name' => $name])->id;
            }
        }
        $now = now();
        $vals = [];
        foreach ($this->master as $r) {
            $catch = $r['catch_weight'] === '1';
            $vals[] = ['code' => $r['item_code'], 'name' => $r['item_name'], 'item_group' => $r['item_group'] ?: 'LAIN-LAIN',
                'sub_group' => $r['sub_group'] ?: null, 'brand_id' => $brands[$r['brand']] ?? null, 'uom_id' => $uoms[$r['uom']] ?? $uoms['PCE'],
                'zone' => $r['zone'], 'catch_weight' => $catch, 'country' => $r['country'] ?: null,
                'cost' => (float) $r['item_cost'] ?: (float) $r['last_price'], 'last_price' => (float) $r['last_price'],
                'shelf_life_days' => self::SHELF_LIFE[$r['zone']], 'units_per_pallet' => $catch ? 0 : 60, 'weight_kg' => $catch ? 1 : 0,
                'picking_strategy' => 'fefo', 'auto_quarantine' => $r['sub_group'] === 'CAVIAR', 'auto_release' => $r['sub_group'] === 'CAVIAR',
                'quarantine_days' => $r['sub_group'] === 'CAVIAR' ? 2 : 7, 'created_at' => $now, 'updated_at' => $now];
        }
        foreach (array_chunk($vals, 200) as $chunk) {
            Item::insert($chunk);
        }
        $this->items = Item::with('uom', 'brand')->get()->keyBy('code')->all();
        $catch = collect($this->items)->filter->catch_weight;
        BarcodeTemplate::where('kind', 'gs1')->first()?->items()->sync($catch->pluck('id'));
        BarcodeTemplate::where('kind', 'regex')->first()?->items()->sync($catch->filter(fn ($i) => str_contains((string) $i->country, 'US'))->pluck('id'));
    }

    // ------------------------------------------------------------------ stok awal

    private function closedPo(): array
    {
        return array_values(array_filter($this->po, fn ($r) => $r['status'] === 'Closed' && isset($this->items[$r['item_code']])));
    }

    private function closedSo(): array
    {
        return array_values(array_filter($this->so, function ($r) {
            $d = Carbon::parse($r['date']);

            return $r['status'] === 'Closed' && isset($this->items[$r['item_code']]) && $d->gte($this->windowStart) && $d->lte(Carbon::parse(self::DATA_DAY));
        }));
    }

    private function lotExpiry(string $zone, bool $allowExpired = true): Carbon
    {
        $r = $this->frand();
        $days = match (true) {
            $r < 0.03 && $allowExpired => -$this->rnd(3, 60),
            $r < 0.10 => $this->rnd(15, 30),
            $r < 0.18 => $this->rnd(31, 60),
            $r < 0.25 => $this->rnd(61, 90),
            $r < 0.32 => $this->rnd(91, 120),
            default => $this->rnd(121, max(150, self::SHELF_LIFE[$zone])),
        };

        return $this->now->copy()->addDays($days)->addHours($this->rnd(0, 23));
    }

    /** Stok awal per item supaya stok akhir = OnHand SAP dan saldo tidak pernah minus di tengah jalan. */
    private function openingQty(): array
    {
        $events = [];
        foreach ($this->closedPo() as $r) { $events[$r['item_code']][] = [$r['date'], 0, (float) $r['qty']]; }
        foreach ($this->closedSo() as $r) { $events[$r['item_code']][] = [$r['date'], 1, -(float) $r['qty']]; }
        $opening = [];
        foreach ($this->rows as $code => $row) {
            $balance = 0.0; $low = 0.0;
            $ev = $events[$code] ?? [];
            sort($ev);
            foreach ($ev as [, , $q]) { $balance += $q; $low = min($low, $balance); }
            $opening[$code] = round(max((float) $row['onhand'] - $balance, -$low, 0.0), 3);
        }

        return $opening;
    }

    private function openingStock(): void
    {
        $shipped = array_flip(array_column($this->closedSo(), 'item_code'));
        $opening = $this->openingQty();
        $inDate = Carbon::parse('2026-08-25')->addDays($this->shift);
        $cartons = [];
        foreach ($this->items as $code => $item) {
            $qty = $opening[$code];
            $zone = $item->zone;
            if ($qty <= 0) {
                continue;
            }
            $n = $qty < 20 ? 1 : $this->pick([1, 2, 2, 3]);
            $parts = array_fill(0, $n, round($qty / $n, 3));
            if (! $item->uom->is_weight) {
                $parts = array_fill(0, $n, floor($qty / $n));
            }
            $parts[$n - 1] = round($qty - array_sum(array_slice($parts, 0, $n - 1)), 3);
            foreach ($parts as $i => $part) {
                if ($part <= 0) {
                    continue;
                }
                $exp = $this->lotExpiry($zone, ! isset($shipped[$code]));
                $lot = Lot::firstOrCreate(['item_id' => $item->id, 'name' => substr($code, -6).'-'.$exp->format('ym').($i + 1)], [
                    'expiry_date' => $exp, 'mfg_date' => $exp->copy()->subDays(self::SHELF_LIFE[$zone]), 'brand' => $item->brand?->label()]);
                $lpn = Lpn::create(['name' => Seq::lpn(), 'item_id' => $item->id, 'lot_id' => $lot->id, 'closed' => true,
                    'pallet_no' => sprintf('P%05d', Lpn::count()), 'pack_date' => $inDate]);
                $loc = ($i === 0 || $this->frand() < 0.7) ? $this->nextBin($zone) : Location::find($this->pck[$zone]);
                StockService::$forceDate = $inDate;
                StockService::add($item, $loc, $part, $lot, $lpn, null, $inDate);
                StockService::ledger(['reference' => 'Saldo awal go-live', 'kind' => 'adjust', 'item_id' => $item->id, 'lot_id' => $lot->id,
                    'from_label' => 'Saldo awal', 'to_location_id' => $loc->id, 'result_lpn_id' => $lpn->id, 'qty' => $part]);
                if ($item->catch_weight && $zone !== 'dry') {
                    $cartons = array_merge($cartons, $this->cartons($item, $lot, $lpn, $loc, $part, $exp));
                }
            }
        }
        foreach (array_chunk($cartons, 300) as $chunk) {
            Carton::insert($chunk);
        }
        StockService::$forceDate = null;
    }

    private function cartons(Item $item, Lot $lot, Lpn $lpn, Location $loc, float $qty, Carbon $exp): array
    {
        $out = []; $remaining = $qty; $now = now();
        $count = min(25, max(1, (int) floor($qty / 8)));
        for ($c = 0; $c < $count; $c++) {
            $w = round(min($remaining, 4.5 + $this->frand() * 8), 3);
            if ($w <= 0) {
                break;
            }
            $remaining -= $w;
            $gtin = sprintf('%013d', (array_sum(array_map('ord', str_split($item->code))) * 7919) % 10000000000000);
            $out[] = ['name' => sprintf('%s-%03d', $lot->name, $c + 1), 'item_id' => $item->id, 'lot_id' => $lot->id, 'lpn_id' => $lpn->id,
                'location_id' => $loc->id, 'weight_kg' => $w, 'weight_lb' => round($w * 2.20462, 2), 'state' => 'available',
                'raw_barcode' => sprintf('(01)0%s(3102)%06d(10)%s(17)%s(21)%05d', $gtin, (int) ($w * 100), $lot->name, $exp->format('ymd'), $c + 1),
                'created_at' => $now, 'updated_at' => $now];
        }

        return $out;
    }

    // ------------------------------------------------------------------ payload SAP

    private function sapUom(array $r): string
    {
        return $r['uom'] ?: ($this->rows[$r['item_code']]['uom'] ?? 'PCE');
    }

    private function porPayload(array $lines, string $container = ''): array
    {
        $h = $lines[0];

        return [
            'additional_information' => [['doc_entry' => $h['doc_num'], 'vendor_ref_no' => $h['ref'], 'vendor_do_no' => $h['ref'],
                'container' => $container, 'remarks' => '', 'web_trans_no' => 'POR-'.$h['doc_num']]],
            'items' => array_map(fn ($r, $i) => ['po_line_no' => (string) $i, 'item_code' => $r['item_code'], 'item_name' => $r['item_name'],
                'uom_id' => $this->sapUom($r), 'qty' => (float) $r['qty'], 'remark' => '', 'is_free' => 'NO',
                'brand_desc' => $this->rows[$r['item_code']]['brand'] ?? null], $lines, array_keys($lines)),
            'number' => '95-SKI-POR-'.$h['doc_num'], 'inbound_type' => 'Purchase Order', 'source' => $h['card_code'], 'warehouse' => '95-SKI',
            'owner' => '95-SKI', 'user' => 'indogunasap', 'date' => $this->day($h['date'])->format('m/d/Y'), 'status' => $h['status'], 'obj_type' => '22',
        ];
    }

    private function sorPayload(array $lines): array
    {
        $h = $lines[0];

        return [
            'additional_information' => [['doc_entry' => $h['doc_num'], 'vendor_ref_no' => $h['ref'], 'remarks' => '', 'web_trans_no' => 'SOR-'.$h['doc_num']]],
            'items' => array_map(fn ($r, $i) => ['so_line_no' => (string) $i, 'item_code' => $r['item_code'], 'item_name' => $r['item_name'],
                'uom_id' => $this->sapUom($r), 'qty' => (float) $r['qty'], 'remark' => '', 'brand_desc' => $this->rows[$r['item_code']]['brand'] ?? null,
                'grade' => '-'], $lines, array_keys($lines)),
            'number' => '95-SKI-SOR-'.$h['doc_num'], 'outbound_type' => 'Sales Order', 'warehouse' => '95-SKI', 'owner' => '95-SKI',
            'user' => 'indogunasap', 'destination_code' => $h['card_code'], 'destination_outlet' => $h['ship_to'] ?: $h['card_name'],
            'po_date' => $this->day($h['date'])->format('m/d/Y'), 'delivery_date' => $this->day($h['due_date'])->format('m/d/Y'),
            'shipping_group' => self::SHIPPING_GROUP[substr($h['card_code'], 0, 2)] ?? 'UMUM', 'status' => $h['status'], 'priority' => 'Normal', 'obj_type' => '17',
        ];
    }

    private function docs(array $rows): array
    {
        $out = [];
        foreach ($rows as $r) { $out[$r['doc_num']][] = $r; }

        return $out;
    }

    // ------------------------------------------------------------------ riwayat

    private function events(): array
    {
        $ev = [];
        foreach ($this->docs($this->closedPo()) as $num => $lines) { $ev[] = [$lines[0]['date'], 0, (string) $num, $lines]; }
        foreach ($this->docs($this->closedSo()) as $num => $lines) { $ev[] = [$lines[0]['date'], 1, (string) $num, $lines]; }
        usort($ev, fn ($a, $b) => [$a[0], $a[1], $a[2]] <=> [$b[0], $b[1], $b[2]]);

        return $ev;
    }

    private function receive(array $lines): void
    {
        $h = $lines[0];
        $when = $this->day($h['date'])->addHours($this->rnd(1, 4))->addMinutes($this->rnd(0, 59));
        StockService::$forceDate = $when;
        StockService::$skipChecks = true;
        $zones = array_unique(array_map(fn ($r) => $this->items[$r['item_code']]->zone, $lines));
        $container = str_starts_with($h['card_code'], 'VI') && array_diff($zones, ['dry'])
            ? $this->pick(['MSKU', 'TGHU', 'CMAU', 'MEDU']).$this->rnd(1000000, 9999999) : '';
        $sd = SyncService::importJson([$this->porPayload($lines, $container)])->first();
        $doc = $sd->document;
        if (! $doc) {
            return;
        }
        $doc->update(['dock_id' => $this->docks->random()->id, 'vehicle_plate' => $this->pick(self::PLATES), 'driver' => $this->pick(self::DRIVERS),
            'delivery_note' => $h['ref'] ?: 'SJ-'.$h['doc_num']]);
        foreach ($doc->lines()->with('item')->get() as $line) {
            $item = $line->item;
            $exp = $when->copy()->addDays((int) (self::SHELF_LIFE[$item->zone] * (0.75 + $this->frand() * 0.2)));
            $lot = Lot::firstOrCreate(['item_id' => $item->id, 'name' => substr($item->code, -6).'-R'.$when->format('md').sprintf('%02d', $line->id % 100)],
                ['expiry_date' => $exp, 'brand' => $item->brand?->label()]);
            $lpn = Lpn::create(['name' => Seq::lpn(), 'item_id' => $item->id, 'lot_id' => $lot->id, 'closed' => true, 'pack_date' => $when]);
            $doc->details()->create(['document_line_id' => $line->id, 'item_id' => $item->id, 'lot_id' => $lot->id,
                'to_location_id' => $this->nextBin($item->zone)->id, 'result_lpn_id' => $lpn->id, 'qty' => $line->qty_demand, 'picked' => true]);
        }
        InboundService::validate($doc->fresh());
    }

    private function ship(array $lines): void
    {
        $h = $lines[0];
        $when = $this->day($h['date'])->addHours($this->rnd(2, 9))->addMinutes($this->rnd(0, 59));
        StockService::$forceDate = $when;
        StockService::$skipChecks = true;
        $sd = SyncService::importJson([$this->sorPayload($lines)])->first();
        $doc = $sd->document;
        if (! $doc) {
            return;
        }
        OutboundService::reserve($doc);
        if ($doc->details()->doesntExist()) {
            return;
        }
        $doc->details()->update(['picked' => true, 'checked' => true]);
        OutboundService::goodsIssue($doc->fresh());
    }

    /**
     * Samakan stok dengan OnHand SAP 13 Nov. Sebagian PO Sep-Okt sudah habis oleh SO sebelum jendela 7 hari yang
     * tidak diputar ulang; selisih dikeluarkan lewat penyesuaian bertanggal awal jendela (rekonsiliasi go-live).
     */
    private function reconcileOnhand(): void
    {
        StockService::$forceDate = $this->windowStart->copy()->addDays($this->shift)->subHours(2);
        $n = 0;
        foreach ($this->items as $code => $item) {
            $stocks = Stock::where('item_id', $item->id)->where('qty', '>', 0)->orderBy('in_date')->orderBy('id')->get();
            $excess = round($stocks->sum('qty') - (float) $this->rows[$code]['onhand'], 3);
            foreach ($stocks as $s) {
                if ($excess <= 0.0005) {
                    break;
                }
                $take = min($excess, $s->available());
                if ($take <= 0) {
                    continue;
                }
                StockService::adjust($s, round($s->qty - $take, 3), 'Rekonsiliasi OnHand SAP 13 Nov 2026');
                $excess = round($excess - $take, 3);
                $n++;
            }
        }
        StockService::$forceDate = null;
        $this->command?->info("Demo: {$n} stok disesuaikan ke OnHand SAP");
    }

    private function afterHistory(): void
    {
        // Batas umur simpan dipasang setelah riwayat dibangun (riwayat = fakta yang sudah terjadi).
        foreach (['frozen' => 50, 'chilled' => 60, 'dry' => 40] as $zone => $pct) {
            Item::where('zone', $zone)->update(['min_receive_life_pct' => $pct]);
        }
        foreach (self::MIN_LIFE as $prefix => $pct) {
            Partner::where('code', 'like', $prefix.'%')->where('is_destination', true)->update(['min_shelf_life_pct' => $pct]);
        }
        // Stok minimum = rata-rata SO harian 7 hari terakhir x 5 hari kerja.
        $sold = [];
        foreach ($this->closedSo() as $r) { $sold[$r['item_code']] = ($sold[$r['item_code']] ?? 0) + (float) $r['qty']; }
        foreach ($sold as $code => $q) {
            Item::where('code', $code)->update(['min_stock' => round($q / self::HISTORY_DAYS * 5, 2)]);
        }
        // Rekap POD untuk 6 pengiriman terakhir lewat satu loading job yang sudah ditutup.
        $last = Document::where('type', 'outbound')->where('state', 'done')->orderByDesc('done_at')->limit(6)->get()->sortBy('done_at')->values();
        $job = Loading::create(['number' => Seq::next('LD'), 'vehicle_plate' => self::PLATES[0], 'driver' => self::DRIVERS[0],
            'date' => $this->now->copy()->subDay()->toDateString(), 'state' => 'close']);
        foreach ($last as $i => $doc) {
            $doc->update(['loading_id' => $job->id]);
            $doc->details()->update(['loaded' => true, 'checked' => true]);
            $pod = OutboundService::createPod($doc);
            if ($i < 4) {
                $lines = [];
                foreach ($pod->lines as $l) {
                    $lines[$l->id] = ['qty' => $l->qty_sent - ($i === 1 && $l->qty_sent > 1 ? 1 : 0), 'remark' => $i === 1 ? '1 karton penyok' : ''];
                }
                StockService::$forceDate = Carbon::parse($doc->done_at)->addHours(3);
                OutboundService::submitPod($pod, $this->pick(['Pak Hendra', 'Ibu Sari', 'Chef Anto']), $lines);
                StockService::$forceDate = null;
            } else {
                $doc->update(['stage' => 'shipped']);
            }
        }
    }

    private function openWork(): void
    {
        StockService::$skipChecks = false;
        // PO Open: truk belum datang, tampil di Inbound Document "Belum diterima".
        foreach ($this->docs(array_filter($this->po, fn ($r) => $r['status'] === 'Open')) as $lines) {
            SyncService::importJson([$this->porPayload($lines)]);
        }
        // SO Open: dokumen dengan ItemCode yang belum ada di master gagal (kasus nyata data SARKUL).
        $out = collect();
        foreach ($this->docs(array_filter($this->so, fn ($r) => $r['status'] === 'Open')) as $lines) {
            $sd = SyncService::importJson([$this->sorPayload($lines)])->first();
            if ($sd->document) {
                $out->push($sd->document);
            }
        }
        foreach ($out as $doc) {
            OutboundService::reserve($doc);
        }
        $today = $this->now->toDateString();
        $todays = Document::whereIn('id', $out->pluck('id'))->where('state', 'ready')->whereDate('scheduled_at', $today)->get();
        $groups = $todays->groupBy('shipping_group')->sortByDesc(fn ($g) => $g->count())->values();
        // Picking Job: kelompok kirim terbanyak hari ini.
        $jobDocs = ($groups[0] ?? collect())->take(4);
        if ($jobDocs->isNotEmpty()) {
            $job = OutboundService::createPickingJob($jobDocs, ['driver' => self::DRIVERS[3], 'transporter' => self::PLATES[1]]);
            Document::whereIn('id', $jobDocs->pluck('id'))->update(['stage' => 'picking']);
            $jobDocs->first()->details()->update(['picked' => true]);
        }
        // Sudah dicek di staging dan mulai dimuat: kelompok kirim kedua.
        $ready = ($groups[1] ?? collect())->take(3);
        if ($ready->isNotEmpty()) {
            $loading = Loading::create(['number' => Seq::next('LD'), 'vehicle_plate' => self::PLATES[2], 'driver' => self::DRIVERS[1], 'date' => $today]);
            foreach ($ready as $doc) {
                $doc->details()->update(['picked' => true, 'checked' => true]);
                $doc->update(['stage' => 'checked', 'staging_location_id' => $this->staging?->id, 'loading_id' => $loading->id]);
            }
            $first = $ready->first();
            $first->details()->orderBy('id')->limit(1)->update(['loaded' => true]);
            $first->update(['stage' => 'loading']);
        }
    }

    private function sapQueue(): void
    {
        $all = SapPosting::orderBy('id')->get();
        $posted = $all->slice(0, max(0, $all->count() - 7));
        foreach ($posted as $p) {
            $when = Carbon::parse($p->document?->done_at ?? $this->now);
            StockService::$forceDate = $when->copy()->addMinutes($this->rnd(2, 15));
            SapService::post([$p]);
        }
        StockService::$forceDate = null;
        $errors = ['Service Layer: (-5002) Quantity falls into negative inventory [OIGE.WhsCode][line: 1]',
            'Service Layer: (-10) Batch number already exists for this item', 'Koneksi ke Service Layer timeout (30 dtk)'];
        foreach ($all->slice(-7, 3)->values() as $i => $p) {
            $p->update(['state' => 'failed', 'error' => $errors[$i], 'attempts' => 3]);
        }
    }

    private function inventoryAndButcher(): void
    {
        $stocks = Stock::with('location')->whereHas('location', fn ($q) => $q->where('area', 'blk'))->whereNotNull('lpn_id')
            ->where('qty', '>', 5)->where('reserved_qty', 0)->orderBy('id')->limit(40)->get();
        if ($stocks->count() < 3) {
            return;
        }
        // Satu inventory check sudah disetujui, satu menunggu approval kepala gudang.
        $approved = InventoryService::create($stocks[0]->lpn, null);
        $l = $approved->lines()->first();
        $l->update(['qty_after' => $l->qty_before - 1]);
        InventoryService::submit($approved, [$l->id => ['qty' => $l->qty_before - 1]]);
        auth()->setUser(User::where('username', 'hendra.kepala')->first());
        InventoryService::approve($approved->fresh());
        auth()->setUser(User::where('username', 'agus.operator')->first());
        $pending = InventoryService::create(null, $stocks[1]->location);
        if ($l = $pending->lines()->first()) {
            InventoryService::submit($pending, [$l->id => ['qty' => $l->qty_before + 2,
                'expiry' => Carbon::parse($l->expiry_before ?? $this->now)->addDays(30)->toDateTimeString()]]);
        }
        // Butcher: daging berat aktual dipotong jadi porsi resto, yield ±88%.
        $meat = Stock::with('item', 'lot', 'lpn', 'location')->whereHas('item', fn ($q) => $q->where('item_group', 'A-MEAT')->where('catch_weight', true))
            ->whereHas('location', fn ($q) => $q->where('area', 'blk'))->where('qty', '>', 10)->where('reserved_qty', 0)->whereNotNull('lpn_id')
            ->whereHas('lot', fn ($q) => $q->where('expiry_date', '>', now()))->first();
        if ($meat) {
            auth()->setUser(User::where('username', 'budi.operator')->first());
            $qty = round(min($meat->qty, 10.0), 3);
            $out = Butcher::create(['number' => ButcherService::newNumber('out'), 'kind' => 'out', 'ref' => 'SPK-BTC-0001', 'date' => now(), 'user_id' => auth()->id()]);
            $out->lines()->create(['lpn_id' => $meat->lpn_id, 'item_id' => $meat->item_id, 'lot_id' => $meat->lot_id, 'location_id' => $meat->location_id, 'qty' => $qty]);
            ButcherService::done($out);
            $in = Butcher::create(['number' => ButcherService::newNumber('in'), 'kind' => 'in', 'ref' => 'SPK-BTC-0001', 'source_id' => $out->id, 'date' => now(), 'user_id' => auth()->id()]);
            $in->lines()->create(['process_type' => 'rebag', 'item_id' => $meat->item_id, 'qty' => round($qty * 0.8, 3), 'location_id' => $this->nextBin($meat->item->zone)->id,
                'grade' => 'A', 'brand' => 'SARANAKULINA', 'cutting_type' => 'Steak 200 gr']);
            $in->lines()->create(['process_type' => 'trimming', 'item_id' => $meat->item_id, 'qty' => round($qty * 0.08, 3), 'location_id' => $this->pck[$meat->item->zone],
                'grade' => 'B', 'cutting_type' => 'Trimming']);
            ButcherService::done($in);
        }
        auth()->setUser(User::where('username', 'admin')->first());
    }

    private function announcements(): void
    {
        $admin = User::where('username', 'admin')->value('id');
        foreach ([
            ['Go-live FoodTrace gudang GENERAL WAREHOUSE', 'Mulai Senin semua penerimaan dan pengiriman wajib lewat FoodTrace. Dokumen PO/SO masuk otomatis dari SAP; jangan membuat dokumen gudang manual.', null, 'penting', true],
            ['Dock wajib diisi saat receiving', 'Isi Loading dock (DOCK_01–DOCK_03) sebelum validasi penerimaan. Tanpa dock, penerimaan ditolak sistem.', ['inbound', 'operator'], 'info', false],
            ['Cut-off picking jam 14.00', 'Picking Job untuk pengiriman besok dibuat paling lambat 14.00. Prioritas rute RITEL MODERN dan HOTEL.', ['outbound', 'operator'], 'info', false],
            ['Cek lot mendekati kedaluwarsa', 'Ada lot frozen dan chilled yang kedaluwarsa dalam 30 hari. Kepala gudang/QA mohon tinjau di Dashboard dan Expired Report, lalu putuskan promo, retur, atau QC Hold.', ['manager'], 'penting', false],
            ['Posting SAP gagal perlu ditindaklanjuti', 'Tiga posting ke SAP gagal (stok negatif, batch ganda, timeout). Admin mohon cek menu Integrasi SAP B1 → Antrean posting lalu kirim ulang.', ['admin', 'manager'], 'darurat', false],
        ] as [$t, $b, $roles, $lvl, $pin]) {
            Announcement::create(['title' => $t, 'body' => $b, 'roles' => $roles, 'level' => $lvl, 'pinned' => $pin, 'user_id' => $admin,
                'starts_at' => now()->subDays(2)->toDateString()]);
        }
    }
}
