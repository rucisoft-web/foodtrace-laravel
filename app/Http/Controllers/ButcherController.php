<?php

namespace App\Http\Controllers;

use App\Models\Butcher;
use App\Models\Location;
use App\Services\ButcherService;
use App\Services\Lookup;
use App\Services\StockService;
use App\Support\WmsException;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

/** Butcher / repack: Butcher Out (bahan keluar ke ruang potong) dan Butcher In (hasil masuk sebagai lot + LPN baru). */
class ButcherController extends Controller
{
    public function index(Request $r)
    {
        $kind = $r->get('kind', 'out');
        $rows = Butcher::with('user', 'source', 'lines.item')->withCount('lines')->where('kind', $kind)->latest('id')->paginate(50)->withQueryString();

        return view('butcher.index', compact('rows', 'kind'));
    }

    public function create(Request $r)
    {
        $kind = $r->get('kind', 'out');
        $sources = Butcher::where('kind', 'out')->where('state', 'done')->latest('id')->limit(50)->get();
        $bins = Location::where('is_bin', true)->where('active', true)->whereIn('area', ['blk', 'pck'])->orderBy('full_name')->get(['id', 'full_name', 'barcode', 'eff_zone']);

        return view('butcher.create', compact('kind', 'sources', 'bins'));
    }

    public function store(Request $r)
    {
        $kind = $r->kind === 'in' ? 'in' : 'out';
        $rows = collect((array) $r->lines)->filter(fn ($l) => ! empty($l['code']) && (float) ($l['qty'] ?? 0) > 0);
        if ($rows->isEmpty()) {
            throw new WmsException('Isi minimal satu baris (LPN / item dan qty).');
        }
        $doc = DB::transaction(function () use ($r, $kind, $rows) {
            $doc = Butcher::create(['number' => ButcherService::newNumber($kind), 'kind' => $kind, 'ref' => $r->ref, 'date' => StockService::now(),
                'user_id' => auth()->id(), 'source_id' => $kind === 'in' ? $r->source_id : null]);
            foreach ($rows as $l) {
                $qty = (float) str_replace(',', '.', $l['qty']);
                if ($kind === 'out') {
                    $lpn = Lookup::lpn($l['code']);
                    $stock = $lpn->liveStocks()->first() ?? throw new WmsException("LPN {$lpn->name} kosong.");
                    $doc->lines()->create(['process_type' => $l['type'] ?? 'rebag', 'lpn_id' => $lpn->id, 'item_id' => $stock->item_id, 'lot_id' => $stock->lot_id,
                        'location_id' => $stock->location_id, 'qty' => $qty]);
                } else {
                    $item = Lookup::item($l['code']) ?? throw new WmsException("Item {$l['code']} tidak dikenal.");
                    $loc = Lookup::location($l['location'] ?? '');
                    $doc->lines()->create(['process_type' => $l['type'] ?? 'rebag', 'item_id' => $item->id, 'location_id' => $loc->id, 'qty' => $qty,
                        'lot_name' => $l['lot'] ?? null, 'expiry_date' => ($l['expiry'] ?? null) ?: null, 'grade' => $l['grade'] ?? null,
                        'cutting_type' => $l['cutting'] ?? null, 'brand' => $item->brand?->label()]);
                }
            }

            return $doc;
        });
        if ($r->boolean('done')) {
            ButcherService::done($doc);
        }

        return redirect()->route('butcher.show', $doc)->with('ok', "{$doc->number} ".($doc->fresh()->state === 'done' ? 'selesai.' : 'disimpan sebagai draft.'));
    }

    public function show(Butcher $butcher)
    {
        $butcher->load('lines.item.uom', 'lines.lot', 'lines.lpn', 'lines.location', 'user', 'source', 'results');

        return view('butcher.show', ['b' => $butcher]);
    }

    public function done(Butcher $butcher)
    {
        ButcherService::done($butcher);

        return back()->with('ok', "{$butcher->number} selesai.");
    }

    public function cancel(Butcher $butcher)
    {
        if ($butcher->state !== 'draft') {
            throw new WmsException('Hanya draft yang bisa dibatalkan.');
        }
        $butcher->update(['state' => 'cancel']);

        return back()->with('ok', "{$butcher->number} dibatalkan.");
    }
}
