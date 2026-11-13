<?php

namespace App\Http\Controllers;

use App\Models\Document;
use App\Services\OutboundService;
use App\Support\Wms;
use App\Support\WmsException;
use Illuminate\Http\Request;

/** Outbound Document (SO dari SAP, transfer, retur ke vendor, pemusnahan): reservasi FEFO, picking job, Goods Issue. */
class OutboundController extends Controller
{
    public function index(Request $r)
    {
        $stage = $r->get('stage', 'todo');
        $q = Document::with('partner', 'shipTo', 'pickingJob', 'loading')->withCount('lines')->where('type', 'outbound')
            ->when($stage === 'todo', fn ($q) => $q->whereIn('state', ['open', 'ready']))
            ->when(in_array($stage, array_keys(Wms::OUT_STAGES)), fn ($q) => $q->where('stage', $stage)->when(in_array($stage, ['open', 'picking', 'staged', 'checked', 'loading']), fn ($w) => $w->whereIn('state', ['open', 'ready'])))
            ->when($stage === 'cancel', fn ($q) => $q->where('state', 'cancel'))
            ->when($r->group, fn ($q, $g) => $q->where('shipping_group', $g))
            ->when($r->date, fn ($q, $d) => $q->whereDate('scheduled_at', '<=', $d))
            ->when($r->type, fn ($q, $t) => $q->where('outbound_type', $t))
            ->when($r->boolean('nojob'), fn ($q) => $q->whereNull('picking_job_id'))
            ->when($r->q, fn ($q, $s) => $q->where(fn ($w) => $w->where('number', 'like', "%{$s}%")->orWhere('origin', 'like', "%{$s}%")
                ->orWhereHas('partner', fn ($p) => $p->where('name', 'like', "%{$s}%"))->orWhereHas('shipTo', fn ($p) => $p->where('name', 'like', "%{$s}%"))));
        $docs = $q->orderBy('scheduled_at')->orderBy('shipping_group')->orderBy('id')->paginate(100)->withQueryString();
        $counts = Document::where('type', 'outbound')->whereIn('state', ['open', 'ready'])->selectRaw('stage, COUNT(*) n')->groupBy('stage')->pluck('n', 'stage');
        $groups = Document::where('type', 'outbound')->whereIn('state', ['open', 'ready'])->whereNotNull('shipping_group')->distinct()->orderBy('shipping_group')->pluck('shipping_group');

        return view('outbound.index', compact('docs', 'stage', 'counts', 'groups'));
    }

    public function createJob(Request $r)
    {
        $docs = Document::whereIn('id', (array) $r->ids)->get();
        if ($docs->whereNotNull('picking_job_id')->isNotEmpty() && ! $r->boolean('force')) {
            throw new WmsException('Sebagian dokumen sudah punya picking job: '.$docs->whereNotNull('picking_job_id')->pluck('number')->implode(', '));
        }
        $job = OutboundService::createPickingJob($docs, array_filter($r->only('driver', 'transporter')) + ['use_tote' => $r->boolean('use_tote')]);
        $short = Document::where('picking_job_id', $job->id)->where('state', 'open')->count();

        return redirect()->route('jobs.show', $job)->with($short ? 'warn' : 'ok', "{$job->number} dibuat untuk {$docs->count()} dokumen; reservasi FEFO selesai".($short ? ", {$short} dokumen stoknya kurang (short)." : '.'));
    }

    public function show(Document $document)
    {
        abort_unless($document->type === 'outbound', 404);
        $document->load('lines.item.uom', 'lines.uom', 'lines.details.lot', 'lines.details.from', 'lines.details.lpn', 'partner', 'shipTo', 'owner', 'pickingJob', 'loading', 'staging', 'pod', 'sapPostings', 'syncDocument');

        return view('outbound.show', ['doc' => $document]);
    }

    public function reserve(Document $document)
    {
        $short = OutboundService::reserve($document);

        return back()->with($short ? 'warn' : 'ok', $short ? "{$short} baris stoknya belum cukup (short). Sisanya sudah direservasi FEFO." : 'Semua baris direservasi (FEFO).');
    }

    public function unreserve(Document $document)
    {
        if ($document->details()->where('picked', true)->exists()) {
            throw new WmsException('Sudah ada barang yang diambil; reservasi tidak bisa dibatalkan.');
        }
        OutboundService::unreserve($document);

        return back()->with('ok', 'Reservasi dibatalkan.');
    }

    /** Goods Issue langsung (tanpa loading job), mis. pemusnahan, butcher, ambil sendiri. */
    public function issue(Document $document)
    {
        OutboundService::goodsIssue($document);
        if (! $document->pod && $document->outbound_type === 'so') {
            OutboundService::createPod($document);
        }

        return back()->with('ok', "{$document->number} Goods Issue. Delivery masuk antrean SAP.");
    }

    public function suratJalan(Document $document)
    {
        $document->load('details.item.uom', 'details.lot', 'partner', 'shipTo', 'loading', 'pod');

        return view('print.surat-jalan', ['docs' => collect([$document])]);
    }
}
