<?php

namespace App\Http\Controllers;

use App\Models\Document;
use App\Models\InventoryCheckLine;
use App\Models\Location;
use App\Models\Stock;
use App\Models\StockMove;
use App\Services\DashboardService;
use App\Support\Wms;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

/** Laporan sesuai URS Largo. Semua laporan bisa diekspor ke CSV (Excel). */
class ReportController extends Controller
{
    public const MENU = [
        'by-location' => 'Stok per Lokasi', 'by-item' => 'Lokasi per Item', 'expired' => 'Expired Report', 'item-low' => 'Item Low',
        'material-in' => 'Material In', 'material-out' => 'Material Out', 'transfer-log' => 'Transfer Log', 'adjustment' => 'Cycle Count / Adjustment',
        'ic-discrepancy' => 'Inventory Check Discrepancy', 'warehouse-transfer' => 'Warehouse Transfer',
    ];

    public function show(Request $r, string $report)
    {
        abort_unless(isset(self::MENU[$report]), 404);
        $method = lcfirst(str_replace('-', '', ucwords($report, '-')));

        return $this->$method($r) ->with(['report' => $report, 'title' => self::MENU[$report]]);
    }

    private function stockQuery(Request $r)
    {
        return Stock::with('item.uom', 'lot', 'lpn', 'location', 'owner')->where('qty', '!=', 0)
            ->whereHas('location', fn ($q) => $q->where(fn ($w) => $w->where('area', '!=', 'prd')->orWhereNull('area')))
            ->when($r->zone, fn ($q, $z) => $q->whereHas('item', fn ($i) => $i->where('zone', $z)));
    }

    /** Stok per lokasi: isi satu bin/rak/zona. Cari dengan barcode atau nama lokasi (mis. SKI-FZ-A01 atau FZ/BLK). */
    private function byLocation(Request $r)
    {
        $q = $this->stockQuery($r);
        if ($s = trim((string) $r->q)) {
            $ids = Location::where('barcode', 'like', "%{$s}%")->orWhere('full_name', 'like', "%{$s}%")->pluck('id');
            $q->whereIn('location_id', $ids);
        }
        $rows = $q->get()->sortBy(fn ($x) => [$x->location->full_name, $x->item->code])->values();
        if ($this->wantsCsv($r)) {
            return $this->csv('stok-per-lokasi', ['Lokasi', 'Barcode', 'Area', 'Item', 'Nama', 'Lot', 'Expiry', 'LPN', 'Qty', 'Satuan', 'Status', 'Nilai'],
                $rows->map(fn ($x) => [$x->location->full_name, $x->location->barcode, $x->location->areaLabel(), $x->item->code, $x->item->name, $x->lot?->name,
                    optional($x->lot?->expiry_date)->format('Y-m-d'), $x->lpn?->name, $x->qty, $x->item->uom->code, Wms::STOCK_STATUS[$x->status()], round($x->qty * $x->item->cost)]));
        }

        return view('reports.grouped', ['rows' => $rows->take(3000), 'total' => $rows->count(), 'groupBy' => 'location', 'value' => $rows->sum(fn ($x) => $x->qty * $x->item->cost)]);
    }

    /** Lokasi per item: di mana saja satu item code berada. */
    private function byItem(Request $r)
    {
        $q = $this->stockQuery($r);
        if ($s = trim((string) $r->q)) {
            $q->whereHas('item', fn ($i) => $i->where('code', 'like', "%{$s}%")->orWhere('name', 'like', "%{$s}%"));
        }
        $rows = $q->get()->sortBy(fn ($x) => [$x->item->code, optional($x->lot?->expiry_date)->timestamp ?? PHP_INT_MAX])->values();
        if ($this->wantsCsv($r)) {
            return $this->csv('lokasi-per-item', ['Item', 'Nama', 'Lokasi', 'Barcode', 'Lot', 'Expiry', 'LPN', 'Qty', 'Satuan', 'Status'],
                $rows->map(fn ($x) => [$x->item->code, $x->item->name, $x->location->full_name, $x->location->barcode, $x->lot?->name,
                    optional($x->lot?->expiry_date)->format('Y-m-d'), $x->lpn?->name, $x->qty, $x->item->uom->code, Wms::STOCK_STATUS[$x->status()]]));
        }

        return view('reports.grouped', ['rows' => $rows->take(3000), 'total' => $rows->count(), 'groupBy' => 'item', 'value' => $rows->sum(fn ($x) => $x->qty * $x->item->cost)]);
    }

    private function expired(Request $r)
    {
        $rows = $this->stockQuery($r)->whereHas('lot', fn ($l) => $l->whereNotNull('expiry_date'))->get()
            ->map(function ($x) { $x->days = $x->lot->daysLeft(); $x->bucket = DashboardService::bucket($x->days); return $x; })
            ->when($r->bucket, fn ($c, $b) => $c->where('bucket', $b), fn ($c) => $c->filter(fn ($x) => $x->days <= 120))
            ->sortBy('days')->values();
        if ($this->wantsCsv($r)) {
            return $this->csv('expired', ['Item', 'Nama', 'Zona', 'Lot', 'Expiry', 'Sisa hari', 'Lokasi', 'LPN', 'Qty', 'Satuan', 'Nilai', 'Status'],
                $rows->map(fn ($x) => [$x->item->code, $x->item->name, $x->item->zone, $x->lot->name, $x->lot->expiry_date->format('Y-m-d'), $x->days,
                    $x->location->full_name, $x->lpn?->name, $x->qty, $x->item->uom->code, round($x->qty * $x->item->cost), Wms::STOCK_STATUS[$x->status()]]));
        }

        return view('reports.expired', ['rows' => $rows]);
    }

    private function itemLow(Request $r)
    {
        $rows = DB::table('items as i')->leftJoin('stocks as s', 's.item_id', '=', 'i.id')->leftJoin('uoms as u', 'u.id', '=', 'i.uom_id')
            ->where('i.min_stock', '>', 0)->when($r->zone, fn ($q, $z) => $q->where('i.zone', $z))
            ->groupBy('i.id', 'i.code', 'i.name', 'i.zone', 'i.min_stock', 'u.code', 'i.item_group')
            ->selectRaw('i.id, i.code, i.name, i.zone, i.item_group, i.min_stock, u.code uom, COALESCE(SUM(s.qty),0) qty')
            ->havingRaw('COALESCE(SUM(s.qty),0) < i.min_stock')->orderBy('i.code')->get();
        if ($this->wantsCsv($r)) {
            return $this->csv('item-low', ['Item', 'Nama', 'Zona', 'Group', 'Stok', 'Minimum', 'Kurang', 'Satuan'],
                $rows->map(fn ($x) => [$x->code, $x->name, $x->zone, $x->item_group, $x->qty, $x->min_stock, round($x->min_stock - $x->qty, 3), $x->uom]));
        }

        return view('reports.item-low', ['rows' => $rows]);
    }

    private function moves(Request $r, array $kinds, string $file)
    {
        $from = $r->from ?: now()->subDays(30)->toDateString();
        $to = $r->to ?: now()->toDateString();
        $q = StockMove::with('item', 'lot', 'from', 'to', 'lpn', 'resultLpn', 'partner', 'user')->whereIn('kind', $kinds)
            ->whereBetween('date', [$from.' 00:00:00', $to.' 23:59:59'])
            ->when($r->q, fn ($q, $s) => $q->where(fn ($w) => $w->where('reference', 'like', "%{$s}%")->orWhereHas('item', fn ($i) => $i->where('code', 'like', "%{$s}%")->orWhere('name', 'like', "%{$s}%"))))
            ->when($r->zone, fn ($q, $z) => $q->whereHas('item', fn ($i) => $i->where('zone', $z)))
            ->orderByDesc('date')->orderByDesc('id');
        if ($this->wantsCsv($r)) {
            return $this->csv($file, ['Tanggal', 'Referensi', 'Item', 'Nama', 'Lot', 'Dari', 'Ke', 'LPN asal', 'LPN tujuan', 'Qty', 'Satuan', 'Nilai', 'Partner', 'Operator'],
                $q->limit(20000)->get()->map(fn ($m) => [$m->date->format('Y-m-d H:i'), $m->reference, $m->item->code, $m->item->name, $m->lot?->name, $m->fromName(), $m->toName(),
                    $m->lpn?->name, $m->resultLpn?->name, $m->qty, $m->uom, $m->value, $m->partner?->name, $m->user?->name]));
        }
        $sum = (clone $q)->reorder()->selectRaw('SUM(qty) q, SUM(value) v, COUNT(*) n')->first();

        return view('reports.moves', ['rows' => $q->paginate(100)->withQueryString(), 'from' => $from, 'to' => $to, 'sum' => $sum]);
    }

    private function materialIn(Request $r) { return $this->moves($r, ['in'], 'material-in'); }
    private function materialOut(Request $r) { return $this->moves($r, ['out'], 'material-out'); }
    private function transferLog(Request $r) { return $this->moves($r, ['internal', 'butcher'], 'transfer-log'); }
    private function adjustment(Request $r) { return $this->moves($r, ['adjust'], 'adjustment'); }

    private function icDiscrepancy(Request $r)
    {
        $rows = InventoryCheckLine::with('check.user', 'check.approver', 'item.uom', 'lot', 'lpn', 'location')->get()->filter->isDifference()->sortByDesc('id')->values();
        if ($this->wantsCsv($r)) {
            return $this->csv('ic-discrepancy', ['Dokumen', 'Status', 'Tanggal', 'Item', 'Lot', 'LPN', 'Lokasi', 'Qty sistem', 'Qty fisik', 'Selisih', 'Expiry sistem', 'Expiry fisik', 'Checker', 'Approver'],
                $rows->map(fn ($l) => [$l->check->number, $l->check->state, $l->check->date->format('Y-m-d'), $l->item->code, $l->lot?->name, $l->lpn?->name, $l->location->full_name,
                    $l->qty_before, $l->qty_after, $l->difference(), optional($l->expiry_before)->format('Y-m-d'), optional($l->expiry_after)->format('Y-m-d'), $l->check->user?->name, $l->check->approver?->name]));
        }

        return view('reports.ic', ['rows' => $rows]);
    }

    private function warehouseTransfer(Request $r)
    {
        $rows = Document::with('partner', 'lines')->where(fn ($q) => $q->where('inbound_type', 'transfer')->orWhere('outbound_type', 'transfer'))->latest('id')->paginate(50);

        return view('reports.wh-transfer', ['rows' => $rows]);
    }
}
