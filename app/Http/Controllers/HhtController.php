<?php

namespace App\Http\Controllers;

use App\Models\Butcher;
use App\Models\Document;
use App\Models\DocumentDetail;
use App\Models\InventoryCheck;
use App\Models\Item;
use App\Models\Loading;
use App\Models\Location;
use App\Models\Lpn;
use App\Models\PickingJob;
use App\Models\Stock;
use App\Services\ButcherService;
use App\Services\InboundService;
use App\Services\InventoryService;
use App\Services\Lookup;
use App\Services\OutboundService;
use App\Services\StockService;
use App\Support\NavCounts;
use App\Support\WmsException;
use Illuminate\Http\Request;

/** Layar handheld (HHT) untuk operator: tombol besar, satu langkah per layar, scan barcode lalu Enter. */
class HhtController extends Controller
{
    public function home()
    {
        $c = NavCounts::get();
        $c['jobs'] = PickingJob::where('state', 'in_progress')->count();
        $c['check'] = Document::where('type', 'outbound')->where('stage', 'staged')->whereIn('state', ['open', 'ready'])->count();
        $c['load'] = Loading::where('state', 'open')->count();

        return view('hht.home', compact('c'));
    }

    // ---------- Receiving ----------
    public function receipts(Request $r)
    {
        $docs = Document::with('partner')->withCount('lines')->where('type', 'inbound')->whereIn('state', ['open', 'ready'])
            ->when($r->q, fn ($q, $s) => $q->where(fn ($w) => $w->where('number', 'like', "%{$s}%")->orWhere('origin', 'like', "%{$s}%")->orWhere('container_no', 'like', "%{$s}%")))
            ->orderBy('scheduled_at')->limit(60)->get();

        return view('hht.receipts', compact('docs'));
    }

    public function receipt(Request $r, Document $document)
    {
        abort_unless($document->type === 'inbound', 404);
        $document->load('lines.item.uom', 'lines.uom', 'lines.details', 'dock');
        $line = $r->line ? $document->lines->firstWhere('id', (int) $r->line) : null;
        $open = DocumentDetail::with('resultLpn', 'item', 'lot')->where('document_id', $document->id)->whereHas('resultLpn', fn ($q) => $q->where('closed', false))->get();

        return view('hht.receipt', ['doc' => $document, 'line' => $line, 'open' => $open, 'docks' => InboundService::docks($document)]);
    }

    public function receiptPost(Request $r, Document $document)
    {
        $back = fn ($extra = []) => redirect()->route('hht.receipt', array_merge(['document' => $document->id], $extra));
        switch ($r->action) {
            case 'item':
                $code = trim((string) $r->code);
                $line = $document->lines()->with('item')->get()->first(fn ($l) => in_array($code, array_filter([$l->item->code, $l->item->barcode]), true));
                if (! $line) {
                    $item = Lookup::item($code);
                    $line = $item ? $document->lines()->where('item_id', $item->id)->first() : null;
                }
                if (! $line) {
                    throw new WmsException("Item {$code} tidak ada di dokumen ini.");
                }

                return $back(['line' => $line->id]);
            case 'scan':
                $line = $document->lines()->with('item.uom', 'uom')->findOrFail($r->integer('line'));
                $msg = InboundService::scan($document, $line, $r->barcode, (float) str_replace(',', '.', (string) $r->weight), $r->lot, $r->expiry ?: null, (float) str_replace(',', '.', (string) $r->qty));

                return $back(['line' => $line->id])->with('ok', $msg);
            case 'pallet':
                $lpn = Lpn::findOrFail($r->integer('lpn'));
                InboundService::closePallet($document, $lpn, $r->only('pallet_no'));

                return $back(['line' => $r->line])->with('ok', "Palet {$lpn->name} disimpan. Tempel label.");
            case 'dock':
                $dock = Lookup::location($r->dock);
                if ($dock->area !== 'rcv') {
                    throw new WmsException("{$dock->name} bukan loading dock.");
                }
                $document->update(['dock_id' => $dock->id]);

                return $back()->with('ok', "Dock {$dock->name}.");
            case 'validate':
                return redirect()->route('hht.receipts')->with('ok', InboundService::validate($document));
        }

        return $back();
    }

    // ---------- Put away / bin transfer ----------
    public function putaway(Request $r)
    {
        $lpn = $r->lpn ? Lookup::lpn($r->lpn) : null;
        if ($r->isMethod('post') && $lpn && $r->dest) {
            $dest = Lookup::location($r->dest);
            if (! $dest->is_bin) {
                throw new WmsException("{$dest->full_name} bukan bin.");
            }
            $from = $lpn->currentLocation();
            StockService::moveLpn($lpn, $dest, in_array($from?->area, ['rcv', 'in']) ? 'Put away HHT' : 'Bin transfer HHT');

            return redirect()->route('hht.putaway')->with('ok', "{$lpn->name} → {$dest->full_name}");
        }
        $stock = $lpn?->liveStocks()->with('item.uom', 'lot', 'location')->first();
        if ($lpn && ! $stock) {
            throw new WmsException("LPN {$lpn->name} kosong.");
        }
        $suggest = $stock ? (PutawayController::freeBins()[$stock->item->zone] ?? collect())->first() : null;
        $waiting = Stock::where('qty', '>', 0)->whereHas('location', fn ($l) => $l->whereIn('area', ['rcv', 'in']))->whereNotNull('lpn_id')->with('lpn', 'item')->limit(20)->get();

        return view('hht.putaway', compact('lpn', 'stock', 'suggest', 'waiting'));
    }

    // ---------- Picking ----------
    public function jobs()
    {
        return view('hht.jobs', ['jobs' => PickingJob::withCount('documents')->where('state', 'in_progress')->latest('id')->get()]);
    }

    private function jobDetails(PickingJob $job)
    {
        return DocumentDetail::with('item.uom', 'lot', 'from', 'lpn', 'document')->whereIn('document_id', $job->documents()->pluck('id'))->get()
            ->sortBy(fn ($d) => [$d->picked ? 1 : 0, $d->from?->full_name, $d->item->code])->values();
    }

    public function pick(PickingJob $job)
    {
        $details = $this->jobDetails($job);
        $next = $details->firstWhere('picked', false);
        $staged = $job->documents()->whereIn('state', ['open', 'ready'])->whereIn('stage', ['open', 'picking'])->doesntExist();

        return view('hht.pick', ['job' => $job, 'details' => $details, 'next' => $next, 'staged' => $staged,
            'staging' => OutboundService::stagingLocation($job->documents()->value('warehouse_id') ?? 1)]);
    }

    public function pickPost(Request $r, PickingJob $job)
    {
        if ($r->action === 'stage') {
            $docs = $job->documents()->whereIn('state', ['open', 'ready'])->whereIn('stage', ['open', 'picking'])->get();

            return redirect()->route('hht.jobs')->with('ok', OutboundService::stage($docs, (string) $r->location));
        }
        $d = DocumentDetail::findOrFail($r->integer('detail'));
        abort_unless($d->document->picking_job_id === $job->id, 404);

        return back()->with('ok', OutboundService::pick($d, (string) $r->location, (string) $r->code, $r->qty));
    }

    // ---------- Checker ----------
    public function check(Request $r)
    {
        $doc = $r->doc ? Document::with('details.item.uom', 'details.lot', 'details.lpn', 'details.resultLpn', 'partner', 'shipTo')->find($r->doc) : null;
        if ($r->isMethod('post')) {
            if (! $doc) {
                $code = trim((string) $r->document);
                $doc = Document::where('type', 'outbound')->where('stage', 'staged')->where(fn ($q) => $q->where('number', $code)->orWhere('origin', $code))->first()
                    ?? throw new WmsException("Dokumen {$code} tidak ada di staging.");

                return redirect()->route('hht.check', ['doc' => $doc->id]);
            }
            $msg = OutboundService::check($doc, (string) $r->code);

            return redirect()->route('hht.check', $doc->fresh()->stage === 'checked' ? [] : ['doc' => $doc->id])->with('ok', $msg);
        }
        $staged = $doc ? collect() : Document::with('shipTo', 'partner')->where('type', 'outbound')->where('stage', 'staged')->whereIn('state', ['open', 'ready'])->limit(30)->get();

        return view('hht.check', compact('doc', 'staged'));
    }

    // ---------- Loading ----------
    public function loadings()
    {
        return view('hht.loadings', ['jobs' => Loading::withCount('documents')->where('state', 'open')->latest('id')->get()]);
    }

    public function loading(Request $r, Loading $loading)
    {
        if ($r->isMethod('post')) {
            if ($r->action === 'close') {
                return redirect()->route('hht.loadings')->with('ok', OutboundService::closeLoading($loading));
            }

            return back()->with('ok', OutboundService::loadScan($loading, (string) $r->code));
        }
        $details = $loading->details()->with('item.uom', 'lpn', 'resultLpn', 'document')->get()->sortBy('loaded');

        return view('hht.loading', ['job' => $loading, 'details' => $details, 'p' => $loading->progress()]);
    }

    // ---------- Inventory check ----------
    public function ic(Request $r)
    {
        $check = $r->check ? InventoryCheck::with('lines.item.uom', 'lines.lot', 'lines.lpn', 'lines.location')->find($r->check) : null;
        if ($r->isMethod('post')) {
            if ($r->action === 'submit' && $check) {
                InventoryService::submit($check, (array) $r->rows);

                return redirect()->route('hht.ic')->with('ok', "{$check->number} dikirim untuk approval.");
            }
            $code = trim((string) $r->code);
            $lpn = Lpn::where('name', preg_replace('/^LPN-/i', '', $code))->first();
            $check = InventoryService::create($lpn, $lpn ? null : Lookup::location($code));

            return redirect()->route('hht.ic', ['check' => $check->id]);
        }

        return view('hht.ic', compact('check'));
    }

    // ---------- Butcher out ----------
    public function butcher(Request $r)
    {
        if ($r->isMethod('post')) {
            $lpn = Lookup::lpn($r->lpn);
            $stock = $lpn->liveStocks()->first() ?? throw new WmsException("LPN {$lpn->name} kosong.");
            $qty = $r->filled('qty') ? (float) str_replace(',', '.', $r->qty) : $stock->qty;
            $b = Butcher::create(['number' => ButcherService::newNumber('out'), 'kind' => 'out', 'ref' => $r->ref, 'date' => StockService::now(), 'user_id' => auth()->id()]);
            $b->lines()->create(['process_type' => $r->type ?: 'rebag', 'lpn_id' => $lpn->id, 'item_id' => $stock->item_id, 'lot_id' => $stock->lot_id, 'location_id' => $stock->location_id, 'qty' => $qty]);
            ButcherService::done($b);

            return back()->with('ok', "{$b->number}: {$lpn->name} ".\App\Support\Wms::fmt($qty).' ke ruang potong.');
        }

        return view('hht.butcher', ['recent' => Butcher::with('lines.item')->where('kind', 'out')->where('user_id', auth()->id())->latest('id')->limit(8)->get()]);
    }

    // ---------- QC hold ----------
    public function hold(Request $r)
    {
        if ($r->isMethod('post')) {
            $r->validate(['lpn' => 'required', 'reason' => 'required|max:120']);
            $lpn = Lookup::lpn($r->lpn);
            $stock = $lpn->liveStocks()->with('location', 'item')->first() ?? throw new WmsException("LPN {$lpn->name} kosong.");
            $dest = $stock->location->qcLocation($stock->item->zone) ?? throw new WmsException('Lokasi karantina tidak ditemukan.');
            StockService::moveLpn($lpn, $dest, 'QC Hold: '.$r->reason);

            return back()->with('ok', "{$lpn->name} ditahan di {$dest->name}.");
        }

        return view('hht.hold');
    }

    // ---------- Cycle count ----------
    public function count(Request $r)
    {
        if ($r->isMethod('post')) {
            $n = 0;
            foreach ((array) $r->counts as $id => $qty) {
                if ($qty !== null && $qty !== '') {
                    Stock::whereKey($id)->update(['counted_qty' => (float) str_replace(',', '.', $qty), 'counted_by' => auth()->id()]);
                    $n++;
                }
            }

            return redirect()->route('hht.count')->with('ok', "{$n} hitungan disimpan.");
        }
        $loc = $r->location ? Lookup::location($r->location) : null;
        $stocks = $loc ? Stock::with('item.uom', 'lot', 'lpn', 'location')->whereIn('location_id', $loc->descendantIds())->where('qty', '>', 0)->get()->sortBy(fn ($s) => $s->location->full_name) : collect();

        return view('hht.count', compact('loc', 'stocks'));
    }

    // ---------- Lookup ----------
    public function lookup(Request $r)
    {
        $code = trim((string) $r->code);
        $res = null;
        if ($code !== '') {
            $lpn = Lpn::with('item.uom', 'lot')->where('name', preg_replace('/^LPN-/i', '', $code))->first();
            $loc = $lpn ? null : Location::where('barcode', $code)->orWhere('full_name', $code)->first();
            $item = $lpn || $loc ? null : Lookup::item($code);
            $stocks = Stock::with('item.uom', 'lot', 'lpn', 'location')->where('qty', '>', 0)
                ->when($lpn, fn ($q) => $q->where('lpn_id', $lpn->id))->when($loc, fn ($q) => $q->whereIn('location_id', $loc->descendantIds()))
                ->when($item, fn ($q) => $q->where('item_id', $item->id))->when(! $lpn && ! $loc && ! $item, fn ($q) => $q->whereRaw('1=0'))
                ->limit(100)->get();
            $res = compact('lpn', 'loc', 'item', 'stocks');
            if (! $lpn && ! $loc && ! $item) {
                return back()->withErrors("{$code} tidak dikenal (bukan LPN, lokasi, atau item).");
            }
        }

        return view('hht.lookup', compact('res', 'code'));
    }
}
