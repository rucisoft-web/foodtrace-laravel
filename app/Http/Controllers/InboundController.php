<?php

namespace App\Http\Controllers;

use App\Models\Document;
use App\Models\DocumentDetail;
use App\Models\Lpn;
use App\Services\InboundService;
use App\Support\Wms;
use App\Support\WmsException;
use Illuminate\Http\Request;

/** Inbound Document (PO dari SAP, titipan, retur, transfer) dan layar receiving. */
class InboundController extends Controller
{
    public function index(Request $r)
    {
        $state = $r->get('state', 'open');
        $q = Document::with('partner', 'dock', 'owner')->withCount('lines')->where('type', 'inbound')
            ->when($state === 'open', fn ($q) => $q->whereIn('state', ['open', 'ready']))
            ->when(in_array($state, ['done', 'cancel']), fn ($q) => $q->where('state', $state))
            ->when($r->type, fn ($q, $t) => $q->where('inbound_type', $t))
            ->when($r->q, fn ($q, $s) => $q->where(fn ($w) => $w->where('number', 'like', "%{$s}%")->orWhere('origin', 'like', "%{$s}%")
                ->orWhere('container_no', 'like', "%{$s}%")->orWhereHas('partner', fn ($p) => $p->where('name', 'like', "%{$s}%"))));
        $counts = Document::where('type', 'inbound')->selectRaw("SUM(state IN ('open','ready')) o, SUM(state='done') d, SUM(state='cancel') c")->first();
        $docs = $q->orderBy($state === 'open' ? 'scheduled_at' : 'done_at', $state === 'open' ? 'asc' : 'desc')->orderByDesc('id')->paginate(50)->withQueryString();

        return view('inbound.index', compact('docs', 'state', 'counts'));
    }

    public function show(Document $document)
    {
        abort_unless($document->type === 'inbound', 404);
        $document->load('lines.item.uom', 'lines.item.brand', 'lines.uom', 'lines.details.lot', 'lines.details.resultLpn', 'lines.details.to', 'partner', 'owner', 'dock', 'syncDocument', 'sapPostings');
        $docks = InboundService::docks($document);
        $lpns = Lpn::whereIn('id', $document->details()->pluck('result_lpn_id'))->with('item', 'lot')->withCount('cartons')->orderBy('id')->get();
        $qty = DocumentDetail::where('document_id', $document->id)->selectRaw('result_lpn_id, SUM(qty) q')->groupBy('result_lpn_id')->pluck('q', 'result_lpn_id');

        return view('inbound.show', ['doc' => $document, 'docks' => $docks, 'lpns' => $lpns, 'lpnQty' => $qty]);
    }

    public function header(Request $r, Document $document)
    {
        $data = $r->validate(['dock_id' => 'nullable|exists:locations,id', 'container_no' => 'nullable|max:40', 'vendor_ref' => 'nullable|max:120',
            'vehicle_plate' => 'nullable|max:20', 'driver' => 'nullable|max:60', 'remark' => 'nullable|max:2000']);
        $document->update($data);

        return back()->with('ok', 'Header penerimaan disimpan.');
    }

    public function scan(Request $r, Document $document)
    {
        $line = $document->lines()->with('item.uom', 'uom')->findOrFail($r->integer('line_id'));
        $msg = InboundService::scan($document, $line, $r->barcode, (float) str_replace(',', '.', (string) $r->weight), $r->lot, $r->expiry ?: null, (float) str_replace(',', '.', (string) $r->qty));

        return back()->with('ok', $msg)->with('line_id', $line->id);
    }

    public function closePallet(Request $r, Document $document, Lpn $lpn)
    {
        InboundService::closePallet($document, $lpn, $r->only('pallet_no', 'remark', 'mfg_date', 'packaging', 'grade', 'est'));

        return back()->with('ok', "Palet {$lpn->name} disimpan. Scan berikutnya membuka LPN baru.");
    }

    public function removeDetail(Document $document, DocumentDetail $detail)
    {
        InboundService::removeDetail($document, $detail);

        return back()->with('ok', 'Baris penerimaan dihapus.');
    }

    public function validateDoc(Request $r, Document $document)
    {
        $msg = InboundService::validate($document, $r->only('dock_id'));

        return redirect()->route('inbound.show', $document)->with('ok', $msg);
    }

    public function cancel(Document $document)
    {
        $this->requireManager();
        if (! $document->isOpen()) {
            throw new WmsException('Dokumen sudah selesai.');
        }
        foreach ($document->details as $d) {
            InboundService::removeDetail($document, $d);
        }
        $document->update(['state' => 'cancel']);

        return back()->with('ok', "{$document->number} dibatalkan.");
    }
}
