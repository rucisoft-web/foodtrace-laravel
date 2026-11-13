<?php

namespace App\Http\Controllers;

use App\Models\Carton;
use App\Models\Document;
use App\Models\DocumentDetail;
use App\Models\Item;
use App\Models\Location;
use App\Models\Lpn;
use App\Models\Stock;
use App\Models\StockMove;
use App\Services\InventoryService;
use App\Services\Lookup;
use App\Support\Wms;
use Illuminate\Http\Request;

/** Stock Status, LPN list + label, tally karton, kartu stok, cycle count. */
class StockController extends Controller
{
    public function index(Request $r)
    {
        $q = Stock::with('item.uom', 'item.brand', 'lot', 'lpn', 'location', 'owner')->where('qty', '>', 0)
            ->whereHas('location', fn ($l) => $l->where(fn ($w) => $w->where('area', '!=', 'prd')->orWhereNull('area')))
            ->when($r->zone, fn ($q, $z) => $q->whereHas('item', fn ($i) => $i->where('zone', $z)))
            ->when($r->status, function ($q, $s) {
                $areas = array_keys(array_filter(Wms::AREA_STATUS, fn ($v) => $v === $s));
                $s === 'available'
                    ? $q->whereHas('location', fn ($l) => $l->whereNotIn('area', array_keys(Wms::AREA_STATUS))->orWhereNull('area'))
                    : $q->whereHas('location', fn ($l) => $l->whereIn('area', $areas));
            })
            ->when($r->q, fn ($q, $s) => $q->where(fn ($w) => $w->whereHas('item', fn ($i) => $i->where('code', 'like', "%{$s}%")->orWhere('name', 'like', "%{$s}%"))
                ->orWhereHas('lot', fn ($l) => $l->where('name', 'like', "%{$s}%"))->orWhereHas('lpn', fn ($l) => $l->where('name', 'like', "%{$s}%"))
                ->orWhereHas('location', fn ($l) => $l->where('full_name', 'like', "%{$s}%")->orWhere('barcode', 'like', "%{$s}%"))))
            ->when($r->owner === 'own', fn ($q) => $q->whereNull('owner_id'))->when($r->owner === 'titipan', fn ($q) => $q->whereNotNull('owner_id'));
        if ($this->wantsCsv($r)) {
            return $this->csv('stock-status', ['Item', 'Nama', 'Zona', 'Lot', 'Expiry', 'LPN', 'Lokasi', 'Status', 'Owner', 'Qty', 'Reservasi', 'Satuan', 'Nilai'],
                $q->get()->map(fn ($s) => [$s->item->code, $s->item->name, $s->item->zone, $s->lot?->name, optional($s->lot?->expiry_date)->format('Y-m-d'), $s->lpn?->name,
                    $s->location->full_name, Wms::STOCK_STATUS[$s->status()], $s->owner?->name, $s->qty, $s->reserved_qty, $s->item->uom->code, round($s->qty * $s->item->cost)]));
        }
        $sum = Stock::join('locations as l', 'l.id', '=', 'stocks.location_id')->join('items as i', 'i.id', '=', 'stocks.item_id')->where('stocks.qty', '>', 0)
            ->where(fn ($w) => $w->where('l.area', '!=', 'prd')->orWhereNull('l.area'))->when($r->zone, fn ($q, $z) => $q->where('i.zone', $z))
            ->selectRaw('l.area, COUNT(*) n, SUM(stocks.qty * i.cost) v')->groupBy('l.area')->get()
            ->groupBy(fn ($x) => Wms::AREA_STATUS[$x->area] ?? 'available')->map(fn ($g) => ['n' => $g->sum('n'), 'v' => $g->sum('v')]);
        $rows = $q->orderBy(Location::select('full_name')->whereColumn('locations.id', 'stocks.location_id'))->paginate(100)->withQueryString();

        return view('stock.index', compact('rows', 'sum'));
    }

    public function cartons(Request $r)
    {
        $rows = Carton::with('item', 'lot', 'lpn', 'location', 'inDocument', 'outDocument')
            ->when($r->state, fn ($q, $s) => $q->where('state', $s))
            ->when($r->q, fn ($q, $s) => $q->where(fn ($w) => $w->where('name', 'like', "%{$s}%")->orWhere('raw_barcode', 'like', "%{$s}%")
                ->orWhereHas('lpn', fn ($l) => $l->where('name', 'like', "%{$s}%"))->orWhereHas('item', fn ($i) => $i->where('code', 'like', "%{$s}%"))
                ->orWhereHas('inDocument', fn ($d) => $d->where('number', 'like', "%{$s}%")->orWhere('origin', 'like', "%{$s}%"))))
            ->latest('id');
        if ($this->wantsCsv($r)) {
            return $this->csv('tally-karton', ['Karton', 'Barcode', 'Item', 'Lot', 'LPN', 'Kg', 'Lb', 'Lokasi', 'Status', 'Inbound', 'Outbound', 'Manual'],
                $rows->limit(50000)->get()->map(fn ($c) => [$c->name, $c->raw_barcode, $c->item->code, $c->lot?->name, $c->lpn?->name, $c->weight_kg, $c->weight_lb,
                    $c->location?->full_name, Carton::STATES[$c->state] ?? $c->state, $c->inDocument?->number, $c->outDocument?->number, $c->manual_weight ? 'ya' : '']));
        }
        $stats = Carton::selectRaw('state, COUNT(*) n, SUM(weight_kg) kg')->groupBy('state')->get()->keyBy('state');

        return view('stock.cartons', ['rows' => $rows->paginate(100)->withQueryString(), 'stats' => $stats]);
    }

    public function lpns(Request $r)
    {
        $rows = Lpn::with('item.uom', 'lot')->withSum(['stocks as qty' => fn ($q) => $q->where('qty', '>', 0)], 'qty')->withCount('cartons')
            ->when(! $r->boolean('empty'), fn ($q) => $q->whereHas('stocks', fn ($s) => $s->where('qty', '>', 0)))
            ->when($r->boolean('reprint'), fn ($q) => $q->where('need_reprint', true))
            ->when($r->zone, fn ($q, $z) => $q->whereHas('item', fn ($i) => $i->where('zone', $z)))
            ->when($r->q, fn ($q, $s) => $q->where(fn ($w) => $w->where('name', 'like', "%{$s}%")->orWhereHas('item', fn ($i) => $i->where('code', 'like', "%{$s}%")->orWhere('name', 'like', "%{$s}%"))
                ->orWhereHas('lot', fn ($l) => $l->where('name', 'like', "%{$s}%"))))
            ->orderByDesc('id')->paginate(100)->withQueryString();
        $locs = Stock::with('location')->whereIn('lpn_id', $rows->pluck('id'))->where('qty', '>', 0)->get()->keyBy('lpn_id');

        return view('stock.lpns', ['rows' => $rows, 'locs' => $locs, 'reprint' => Lpn::where('need_reprint', true)->count()]);
    }

    public function lpn(Lpn $lpn)
    {
        $lpn->load('item.uom', 'item.brand', 'lot');
        $stocks = $lpn->stocks()->with('location', 'owner')->where('qty', '>', 0)->get();
        $moves = StockMove::with('from', 'to', 'user')->where(fn ($q) => $q->where('lpn_id', $lpn->id)->orWhere('result_lpn_id', $lpn->id))->orderByDesc('date')->orderByDesc('id')->limit(200)->get();
        $cartons = $lpn->cartons()->orderBy('id')->get();

        return view('stock.lpn', compact('lpn', 'stocks', 'moves', 'cartons'));
    }

    /** Label LPN format Largo (150 x 100 mm): info palet + QR LPN, kanan 24 stiker QR kecil untuk karton. */
    public function labels(Request $r)
    {
        $ids = collect(explode(',', (string) $r->ids))->filter()->map(fn ($x) => (int) $x);
        $doc = null;
        if ($r->document) {
            $doc = Document::findOrFail($r->document);
            $ids = $ids->merge(DocumentDetail::where('document_id', $doc->id)->whereNotNull('result_lpn_id')->pluck('result_lpn_id'));
        }
        if ($r->boolean('reprint')) {
            $ids = $ids->merge(Lpn::where('need_reprint', true)->pluck('id'));
        }
        $lpns = Lpn::with('item.uom', 'lot')->withCount('cartons')->whereIn('id', $ids->unique())->orderBy('id')->get();
        abort_if($lpns->isEmpty(), 404, 'Tidak ada LPN untuk dicetak.');
        $labels = $lpns->map(function (Lpn $p) {
            $in = DocumentDetail::with('document')->where('result_lpn_id', $p->id)->whereHas('document', fn ($d) => $d->where('type', 'inbound'))->first()?->document;
            $stock = $p->stocks()->where('qty', '>', 0)->first();
            $qty = $p->qty() ?: (float) DocumentDetail::where('result_lpn_id', $p->id)->sum('qty');

            return ['lpn' => $p, 'code' => $p->item?->code, 'name' => $p->item?->name, 'qty' => Wms::fmt($qty), 'uom' => $p->item?->uom?->code,
                'cartons' => $p->cartons_count, 'lot' => $p->lot?->name, 'est' => $p->lot?->est, 'pallet' => $p->pallet_no, 'container' => $in?->container_no,
                'packaging' => $p->packaging, 'grade' => $p->lot?->grade, 'zone' => strtoupper((string) $p->item?->zone),
                'exp' => optional($p->lot?->expiry_date)->format('d-m-Y'), 'rcv' => optional($in?->done_at ?? $stock?->in_date)->format('d-m-Y'),
                'pack' => optional($p->pack_date)->format('d-m-Y')];
        });
        Lpn::whereIn('id', $lpns->pluck('id'))->where('need_reprint', true)->update(['need_reprint' => false]);

        return view('print.lpn-labels', ['labels' => $labels, 'doc' => $doc]);
    }

    /** Kartu stok per item (opsional per lot / lokasi) dengan saldo berjalan. */
    public function card(Request $r)
    {
        $item = $r->item ? Lookup::item($r->item) ?? Item::where('name', 'like', "%{$r->item}%")->first() : null;
        $rows = collect(); $opening = 0; $from = $r->from ?: now()->subDays(90)->toDateString(); $to = $r->to ?: now()->toDateString();
        if ($item) {
            $base = StockMove::where('item_id', $item->id)->when($r->lot, fn ($q, $l) => $q->whereHas('lot', fn ($x) => $x->where('name', $l)));
            $sign = "CASE WHEN kind='in' OR (kind='adjust' AND to_location_id IS NOT NULL) OR (kind='butcher' AND to_location_id IS NOT NULL) THEN qty WHEN kind='out' OR (kind='adjust' AND from_location_id IS NOT NULL) OR (kind='butcher' AND from_location_id IS NOT NULL) THEN -qty ELSE 0 END";
            $opening = (float) (clone $base)->where('date', '<', $from.' 00:00:00')->selectRaw("SUM({$sign}) s")->value('s');
            $bal = $opening;
            $rows = (clone $base)->with('lot', 'from', 'to', 'lpn', 'resultLpn', 'partner', 'user', 'document')->whereBetween('date', [$from.' 00:00:00', $to.' 23:59:59'])
                ->orderBy('date')->orderBy('id')->limit(3000)->get()->map(function ($m) use (&$bal) {
                    $in = in_array($m->kind, ['in']) || (in_array($m->kind, ['adjust', 'butcher']) && $m->to_location_id) ? $m->qty : 0;
                    $out = $m->kind === 'out' || (in_array($m->kind, ['adjust', 'butcher']) && $m->from_location_id) ? $m->qty : 0;
                    $bal = round($bal + $in - $out, 3);
                    $m->q_in = $in; $m->q_out = $out; $m->balance = $bal;

                    return $m;
                });
        }

        return view('stock.card', compact('item', 'rows', 'opening', 'from', 'to'));
    }

    /** Cycle count (hitung buta): operator isi hasil hitung tanpa melihat qty sistem; kepala gudang menyetujui selisih. */
    public function cycle(Request $r)
    {
        $loc = null; $stocks = collect();
        if ($r->location) {
            $loc = Lookup::location($r->location);
            $stocks = Stock::with('item.uom', 'lot', 'lpn', 'location')->whereIn('location_id', $loc->descendantIds())->where('qty', '>', 0)
                ->get()->sortBy(fn ($s) => [$s->location->full_name, $s->item->code]);
        }
        $pending = Stock::with('item.uom', 'lot', 'lpn', 'location')->whereNotNull('counted_qty')->get()->sortBy(fn ($s) => $s->location->full_name);
        $counters = \App\Models\User::whereIn('id', $pending->pluck('counted_by')->filter())->pluck('name', 'id');

        return view('stock.cycle', compact('loc', 'stocks', 'pending', 'counters'));
    }

    public function cycleSave(Request $r)
    {
        $n = 0;
        foreach ((array) $r->counts as $id => $qty) {
            if ($qty === null || $qty === '') {
                continue;
            }
            Stock::whereKey($id)->update(['counted_qty' => (float) str_replace(',', '.', $qty), 'counted_by' => auth()->id()]);
            $n++;
        }

        return back()->with('ok', "{$n} hasil hitung disimpan, menunggu persetujuan kepala gudang.");
    }

    public function cycleApply(Request $r)
    {
        $this->requireManager();
        $stocks = Stock::with('item')->whereIn('id', (array) $r->ids)->whereNotNull('counted_qty')->get();
        if ($r->action === 'reject') {
            Stock::whereIn('id', $stocks->pluck('id'))->update(['counted_qty' => null, 'counted_by' => null]);

            return back()->with('ok', $stocks->count().' hitungan ditolak, silakan hitung ulang.');
        }
        $n = InventoryService::applyCounts($stocks, 'CC/'.now()->format('ymd-Hi'));

        return back()->with('ok', "{$n} hitungan disetujui; selisih dicatat sebagai penyesuaian di kartu stok.");
    }
}
