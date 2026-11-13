<?php

namespace App\Http\Controllers;

use App\Models\SapPosting;
use App\Models\Setting;
use App\Models\SyncDocument;
use App\Services\SapService;
use App\Services\SyncService;
use Illuminate\Http\Request;

/** Integrasi SAP B1: dokumen masuk (POR/SOR) dan antrean posting (GRPO, Delivery). */
class SapController extends Controller
{
    public function sync(Request $r)
    {
        $rows = SyncDocument::with('document')->when($r->state, fn ($q, $s) => $q->where('state', $s))->when($r->direction, fn ($q, $d) => $q->where('direction', $d))
            ->when($r->q, fn ($q, $s) => $q->where(fn ($w) => $w->where('number', 'like', "%{$s}%")->orWhere('partner_name', 'like', "%{$s}%")->orWhere('partner_code', 'like', "%{$s}%")))
            ->latest('id')->paginate(50)->withQueryString();
        $counts = SyncDocument::selectRaw('state, COUNT(*) n')->groupBy('state')->pluck('n', 'state');

        return view('sap.sync', ['rows' => $rows, 'counts' => $counts, 'token' => auth()->user()->isAdmin() ? Setting::get('sync_token') : null]);
    }

    public function import(Request $r)
    {
        $r->validate(['json' => 'nullable|string', 'file' => 'nullable|file|max:20480']);
        $text = $r->hasFile('file') ? file_get_contents($r->file('file')->getRealPath()) : (string) $r->json;
        if (trim($text) === '') {
            return back()->withErrors('Tempel isi JSON atau pilih file sinkron SAP.');
        }
        try {
            $docs = SyncService::importJson($text);
        } catch (\Throwable $e) {
            return back()->withErrors('File tidak bisa dibaca: '.$e->getMessage())->withInput();
        }
        $err = $docs->where('state', 'error')->count();

        return back()->with($err ? 'warn' : 'ok', sprintf('%d dokumen diterima: %d jadi dokumen gudang, %d gagal.', $docs->count(), $docs->where('state', 'done')->count(), $err));
    }

    public function syncShow(SyncDocument $sync)
    {
        return view('sap.sync-show', ['s' => $sync->load('document')]);
    }

    public function reprocess(SyncDocument $sync)
    {
        if ($sync->state === 'done') {
            return back()->with('ok', 'Dokumen sudah diproses.');
        }
        SyncService::process($sync);
        $sync->refresh();

        return back()->with($sync->state === 'done' ? 'ok' : 'warn', $sync->state === 'done' ? "{$sync->number} menjadi {$sync->document->number}." : "Masih gagal: {$sync->error}");
    }

    public function queue(Request $r)
    {
        $rows = SapPosting::with('document.partner')->when($r->state, fn ($q, $s) => $q->where('state', $s))->when($r->type, fn ($q, $t) => $q->where('doc_type', $t))
            ->when($r->q, fn ($q, $s) => $q->where('ref', 'like', "%{$s}%"))
            ->orderByRaw("FIELD(state, 'failed', 'queued', 'posted')")->latest('id')->paginate(50)->withQueryString();
        $counts = SapPosting::selectRaw('state, COUNT(*) n')->groupBy('state')->pluck('n', 'state');

        return view('sap.queue', ['rows' => $rows, 'counts' => $counts, 'mode' => Setting::get('sap_mode', 'simulation')]);
    }

    public function post(Request $r)
    {
        $q = SapPosting::query()->where('state', '!=', 'posted');
        $q = $r->ids ? $q->whereIn('id', (array) $r->ids) : $q->where('state', 'queued');
        $items = $q->limit(500)->get();
        $n = SapService::post($items);
        $failed = $items->count() - $n;

        return back()->with($failed ? 'warn' : 'ok', "{$n} dokumen terkirim ke SAP B1".($failed ? ", {$failed} gagal (lihat kolom error)." : '.'));
    }

    /** API untuk middleware SAP: POST /api/v1/sync dengan header X-WMS-Token, body = isi file sinkron (JSON). */
    public function api(Request $r)
    {
        $token = (string) Setting::get('sync_token');
        if ($token === '' || ! hash_equals($token, (string) $r->header('X-WMS-Token'))) {
            return response()->json(['ok' => false, 'error' => 'token tidak valid'], 401);
        }
        try {
            $docs = SyncService::importJson($r->getContent());
        } catch (\Throwable $e) {
            return response()->json(['ok' => false, 'error' => $e->getMessage()], 422);
        }

        return response()->json(['ok' => true, 'documents' => $docs->map(fn ($d) => ['number' => $d->number, 'state' => $d->state, 'error' => $d->error,
            'wms_number' => $d->document?->number])->values()]);
    }
}
