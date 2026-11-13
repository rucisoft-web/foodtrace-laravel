<?php

namespace App\Http\Controllers;

use App\Models\Location;
use App\Models\Stock;
use App\Services\Lookup;
use App\Services\StockService;
use App\Support\WmsException;
use Illuminate\Http\Request;

/** QC Hold / Release: stok ditahan di lokasi karantina zona, dirilis ke rak atau ditolak ke damage/waste. */
class QcController extends Controller
{
    public function index(Request $r)
    {
        $rows = Stock::with('item.uom', 'lot', 'lpn', 'location', 'owner')->where('qty', '>', 0)
            ->whereHas('location', fn ($l) => $l->whereIn('area', $r->get('area') ? [$r->area] : ['qc', 'rtn', 'dmg', 'wst']))
            ->get()->sortBy(fn ($s) => [$s->location->area, $s->location->full_name]);
        $since = \App\Models\StockMove::whereIn('result_lpn_id', $rows->pluck('lpn_id')->filter())->whereIn('to_location_id', $rows->pluck('location_id'))
            ->selectRaw('result_lpn_id, MAX(date) d')->groupBy('result_lpn_id')->pluck('d', 'result_lpn_id');

        return view('qc.index', compact('rows', 'since'));
    }

    public function hold(Request $r)
    {
        $r->validate(['lpn' => 'required', 'reason' => 'required|max:120']);
        $lpn = Lookup::lpn($r->lpn);
        $stock = $lpn->liveStocks()->with('location', 'item')->first() ?? throw new WmsException("LPN {$lpn->name} kosong.");
        $dest = $r->dest ? Lookup::location($r->dest) : $stock->location->qcLocation($stock->item->zone);
        if (! $dest || ! in_array($dest->area, ['qc', 'rtn', 'dmg', 'wst'])) {
            throw new WmsException('Lokasi karantina zona '.$stock->item->zone.' tidak ditemukan.');
        }
        StockService::moveLpn($lpn, $dest, 'QC Hold: '.$r->reason);

        return back()->with('ok', "LPN {$lpn->name} ditahan di {$dest->full_name}.");
    }

    public function releaseForm(Stock $stock)
    {
        $stock->load('item.uom', 'lot', 'lpn', 'location');
        $free = PutawayController::freeBins()[$stock->item->zone] ?? collect();
        $reject = Location::where('warehouse_id', $stock->location->warehouse_id)->whereIn('area', ['dmg', 'wst', 'rtn'])->orderBy('full_name')->get();

        return view('qc.release', ['s' => $stock, 'suggest' => $free->first(), 'reject' => $reject]);
    }

    public function release(Request $r, Stock $stock)
    {
        $r->validate(['dest' => 'required', 'decision' => 'required|in:release,reject', 'note' => 'nullable|max:120']);
        $dest = Lookup::location($r->dest);
        if ($r->decision === 'release' && ! in_array($dest->area, ['blk', 'pck', null], true)) {
            throw new WmsException('Rilis harus ke rak simpan (palet utuh / picking).');
        }
        $qty = $r->filled('qty') ? (float) str_replace(',', '.', $r->qty) : $stock->qty;
        StockService::transfer($stock, $dest, $qty, ($r->decision === 'release' ? 'QC Release' : 'QC Reject').($r->note ? ': '.$r->note : ''));

        return redirect()->route('qc.index')->with('ok', ($r->decision === 'release' ? 'Dirilis' : 'Ditolak').' ke '.$dest->full_name.'.');
    }
}
