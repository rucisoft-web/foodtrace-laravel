<?php

namespace App\Http\Controllers;

use App\Models\Document;
use App\Models\Location;
use App\Models\Stock;
use App\Services\Lookup;
use App\Services\StockService;
use App\Support\WmsException;
use Illuminate\Http\Request;

/** Put away per zona (Frozen/Chilled/Dry) dari dock ke rak, dan log pemindahan internal (bin transfer). */
class PutawayController extends Controller
{
    /** Bin kosong per zona untuk saran put away (area palet utuh dulu, lalu picking). */
    public static function freeBins(): array
    {
        $used = Stock::where('qty', '>', 0)->distinct()->pluck('location_id')->flip();

        return Location::where('is_bin', true)->where('active', true)->whereIn('area', ['blk', 'pck'])->orderByRaw("area = 'pck'")->orderBy('full_name')->get()
            ->reject(fn ($l) => isset($used[$l->id]))->groupBy('eff_zone')->map(fn ($g) => $g->values())->all();
    }

    public function index(Request $r)
    {
        $stocks = Stock::with('item.uom', 'lot', 'lpn', 'location')->where('qty', '>', 0)
            ->whereHas('location', fn ($l) => $l->whereIn('area', ['rcv', 'in']))
            ->when($r->zone, fn ($q, $z) => $q->whereHas('item', fn ($i) => $i->where('zone', $z)))
            ->get()->sortBy(fn ($s) => [$s->item->zone, $s->location->name, $s->lpn?->name]);
        $free = self::freeBins();
        $ptr = [];
        foreach ($stocks as $s) {
            $z = $s->item->zone;
            $i = $ptr[$z] = ($ptr[$z] ?? -1) + 1;
            $s->suggest = $free[$z][$i] ?? null;
        }
        $freeCount = collect($free)->map->count();

        return view('putaway.index', compact('stocks', 'freeCount'));
    }

    public function move(Request $r)
    {
        $stock = Stock::with('lpn', 'item', 'location')->findOrFail($r->integer('stock_id'));
        $dest = Lookup::location($r->dest);
        if (! $dest->is_bin) {
            throw new WmsException("{$dest->full_name} bukan bin yang bisa diisi stok.");
        }
        $reason = $r->reason ?: 'Put away';
        if ($stock->lpn && ! $r->filled('qty')) {
            StockService::moveLpn($stock->lpn, $dest, $reason);
        } else {
            StockService::transfer($stock, $dest, $r->filled('qty') ? (float) str_replace(',', '.', $r->qty) : $stock->qty, $reason);
        }

        return back()->with('ok', sprintf('%s %s dipindah ke %s.', $stock->lpn?->name ?? $stock->item->code, $stock->item->name, $dest->full_name));
    }

    public function log(Request $r)
    {
        $rows = Document::with('details.item', 'details.from', 'details.to', 'details.lpn', 'user')->where('type', 'internal')
            ->when($r->reason, fn ($q, $s) => $q->where('reason', 'like', "%{$s}%"))
            ->when($r->q, fn ($q, $s) => $q->where(fn ($w) => $w->where('number', 'like', "%{$s}%")->orWhereHas('details.item', fn ($i) => $i->where('code', 'like', "%{$s}%"))
                ->orWhereHas('details.lpn', fn ($l) => $l->where('name', 'like', "%{$s}%"))))
            ->latest('id')->paginate(50)->withQueryString();
        $reasons = Document::where('type', 'internal')->distinct()->orderBy('reason')->pluck('reason')->filter();

        return view('putaway.log', compact('rows', 'reasons'));
    }

    public function show(Document $document)
    {
        abort_unless($document->type === 'internal', 404);
        $document->load('details.item.uom', 'details.lot', 'details.from', 'details.to', 'details.lpn', 'details.resultLpn', 'user', 'owner');

        return view('putaway.show', ['doc' => $document]);
    }
}
