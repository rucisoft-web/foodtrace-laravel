<?php

namespace App\Http\Controllers;

use App\Models\Document;
use App\Models\DocumentDetail;
use App\Models\PickingJob;
use App\Services\OutboundService;
use App\Support\WmsException;
use Illuminate\Http\Request;

/** Picking Job (assisted picking FEFO, finish trip ke staging) dan Checker. */
class PickingController extends Controller
{
    public function index(Request $r)
    {
        $state = $r->get('state', 'in_progress');
        $jobs = PickingJob::with('user')->withCount('documents')->when($state !== 'all', fn ($q) => $q->where('state', $state))->latest('id')->paginate(50)->withQueryString();

        return view('picking.index', compact('jobs', 'state'));
    }

    public function show(PickingJob $job)
    {
        $docs = $job->documents()->with('partner', 'shipTo', 'staging')->get();
        $details = DocumentDetail::with('item.uom', 'lot', 'from', 'lpn', 'document')->whereIn('document_id', $docs->pluck('id'))->get()
            ->sortBy(fn ($d) => [$d->picked ? 1 : 0, $d->from?->full_name, $d->item->code])->values();
        $staging = OutboundService::stagingLocation($docs->first()?->warehouse_id ?? 1);

        return view('picking.show', compact('job', 'docs', 'details', 'staging'));
    }

    public function pick(Request $r, PickingJob $job, DocumentDetail $detail)
    {
        abort_unless($detail->document->picking_job_id === $job->id, 404);
        $msg = OutboundService::pick($detail, (string) $r->location, (string) $r->code, $r->qty);

        return back()->with('ok', $msg);
    }

    public function stage(Request $r, PickingJob $job)
    {
        $docs = $job->documents()->whereIn('state', ['open', 'ready'])->whereIn('stage', ['open', 'picking'])->get();
        if ($docs->isEmpty()) {
            throw new WmsException('Tidak ada dokumen yang perlu diletakkan di staging.');
        }

        return back()->with('ok', OutboundService::stage($docs, (string) $r->location));
    }

    public function checker()
    {
        $docs = Document::with('partner', 'shipTo', 'staging', 'pickingJob')->withCount(['details', 'details as checked_count' => fn ($q) => $q->where('checked', true)])
            ->where('type', 'outbound')->whereIn('state', ['open', 'ready'])->whereIn('stage', ['staged', 'checked'])->orderBy('stage', 'desc')->orderBy('shipping_group')->get();

        return view('picking.checker', compact('docs'));
    }

    public function check(Document $document)
    {
        $document->load('details.item.uom', 'details.lot', 'details.lpn', 'details.resultLpn', 'partner', 'shipTo', 'staging');

        return view('picking.check', ['doc' => $document]);
    }

    public function checkScan(Request $r, Document $document)
    {
        if ($document->stage !== 'staged') {
            throw new WmsException("{$document->number} tidak dalam tahap staging (tahap: {$document->stageLabel()}).");
        }
        $msg = OutboundService::check($document, (string) $r->code);

        return back()->with('ok', $msg);
    }
}
