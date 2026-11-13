<?php

namespace App\Http\Controllers;

use App\Models\Document;
use App\Models\DocumentDetail;
use App\Models\Loading;
use App\Services\OutboundService;
use App\Support\Seq;
use App\Support\WmsException;
use Illuminate\Http\Request;

/** Loading Job: dokumen yang sudah dicek dimuat ke kendaraan; tutup loading = Goods Issue + POD + surat jalan. */
class LoadingController extends Controller
{
    public function index(Request $r)
    {
        $state = $r->get('state', 'open');
        $jobs = Loading::withCount('documents')->when($state !== 'all', fn ($q) => $q->where('state', $state))->latest('id')->paginate(50)->withQueryString();
        $ready = Document::with('partner', 'shipTo')->where('type', 'outbound')->whereIn('state', ['open', 'ready'])->where('stage', 'checked')->whereNull('loading_id')
            ->orderBy('shipping_group')->get();

        return view('loading.index', compact('jobs', 'ready', 'state'));
    }

    public function store(Request $r)
    {
        $r->validate(['ids' => 'required|array', 'vehicle_plate' => 'required|max:20', 'driver' => 'required|max:60']);
        $docs = Document::whereIn('id', $r->ids)->where('stage', 'checked')->whereNull('loading_id')->get();
        if ($docs->isEmpty()) {
            throw new WmsException('Pilih dokumen yang sudah dicek checker.');
        }
        $job = Loading::create(['number' => Seq::next('LD'), 'date' => $r->date ?: now()->toDateString(), 'driver' => $r->driver, 'vehicle_plate' => strtoupper($r->vehicle_plate)]);
        Document::whereIn('id', $docs->pluck('id'))->update(['loading_id' => $job->id, 'vehicle_plate' => $job->vehicle_plate, 'driver' => $job->driver]);

        return redirect()->route('loading.show', $job)->with('ok', "{$job->number} dibuat dengan {$docs->count()} dokumen.");
    }

    public function show(Loading $loading)
    {
        $docs = $loading->documents()->with('partner', 'shipTo', 'pod')->get();
        $details = DocumentDetail::with('item.uom', 'lot', 'lpn', 'resultLpn', 'document')->whereIn('document_id', $docs->pluck('id'))->get()->sortBy(fn ($d) => [$d->loaded, $d->document_id]);

        return view('loading.show', ['job' => $loading, 'docs' => $docs, 'details' => $details, 'p' => $loading->progress()]);
    }

    public function scan(Request $r, Loading $loading)
    {
        if ($loading->state !== 'open') {
            throw new WmsException('Loading sudah ditutup.');
        }

        return back()->with('ok', OutboundService::loadScan($loading, (string) $r->code));
    }

    public function close(Request $r, Loading $loading)
    {
        $force = $r->boolean('force');
        if ($force) {
            $this->requireManager();
        }

        return back()->with('ok', OutboundService::closeLoading($loading, $force));
    }

    public function cancel(Loading $loading)
    {
        if ($loading->state !== 'open') {
            throw new WmsException('Hanya loading terbuka yang bisa dibatalkan.');
        }
        OutboundService::cancelLoading($loading);

        return redirect()->route('loading.index')->with('ok', "{$loading->number} dibatalkan; dokumen kembali ke tahap Sudah dicek.");
    }

    public function manifest(Loading $loading)
    {
        $docs = $loading->documents()->with('partner', 'shipTo', 'details.item.uom', 'details.lot', 'details.lpn', 'details.resultLpn')->get();

        return view('print.manifest', ['job' => $loading, 'docs' => $docs, 'p' => $loading->progress()]);
    }

    public function suratJalan(Loading $loading)
    {
        $docs = $loading->documents()->with('details.item.uom', 'details.lot', 'partner', 'shipTo', 'loading', 'pod')->get();

        return view('print.surat-jalan', ['docs' => $docs]);
    }
}
