<?php

namespace App\Http\Controllers;

use App\Models\InventoryCheck;
use App\Services\InventoryService;
use App\Services\Lookup;
use App\Support\WmsException;
use Illuminate\Http\Request;

/** Inventory Check (format Largo): cek fisik per LPN / lokasi, kirim, disetujui Kepala gudang / QA. */
class InventoryCheckController extends Controller
{
    public function index(Request $r)
    {
        $rows = InventoryCheck::with('location', 'lpn', 'user', 'approver', 'lines')->when($r->state, fn ($q, $s) => $q->where('state', $s))
            ->latest('id')->paginate(50)->withQueryString();
        $counts = InventoryCheck::selectRaw('state, COUNT(*) n')->groupBy('state')->pluck('n', 'state');

        return view('ic.index', compact('rows', 'counts'));
    }

    public function create(Request $r)
    {
        return view('ic.create');
    }

    public function store(Request $r)
    {
        $lpn = $r->mode === 'lpn' ? Lookup::lpn($r->code) : null;
        $loc = $r->mode !== 'lpn' ? Lookup::location($r->code) : null;
        $check = InventoryService::create($lpn, $loc);

        return redirect()->route('ic.show', $check)->with('ok', "{$check->number} dibuat, {$check->lines()->count()} baris stok dimuat.");
    }

    public function show(InventoryCheck $inventory_check)
    {
        $inventory_check->load('lines.item.uom', 'lines.lot', 'lines.lpn', 'lines.location', 'location', 'lpn', 'user', 'approver');

        return view('ic.show', ['c' => $inventory_check]);
    }

    public function submit(Request $r, InventoryCheck $check)
    {
        if ($check->state !== 'draft') {
            throw new WmsException('Dokumen sudah dikirim.');
        }
        $check->update(['remark' => $r->remark]);
        InventoryService::submit($check, (array) $r->rows);

        return back()->with('ok', "{$check->number} dikirim, menunggu approval Kepala gudang / QA.");
    }

    public function approve(InventoryCheck $check)
    {
        $this->requireManager();
        InventoryService::approve($check);

        return back()->with('ok', "{$check->number} disetujui. Selisih qty disesuaikan, expiry diperbarui, LPN berubah ditandai cetak ulang.");
    }

    public function reject(Request $r, InventoryCheck $check)
    {
        $this->requireManager();
        $check->update(['state' => 'rejected', 'approved_by' => auth()->id(), 'remark' => trim($check->remark."\nDitolak: ".$r->reason)]);

        return back()->with('ok', "{$check->number} ditolak.");
    }

    public function reset(InventoryCheck $check)
    {
        if (! in_array($check->state, ['draft', 'rejected'])) {
            throw new WmsException('Hanya draft / rejected yang bisa dimuat ulang.');
        }
        $check->update(['state' => 'draft']);
        InventoryService::load($check);

        return back()->with('ok', 'Baris dimuat ulang dari stok sistem saat ini.');
    }
}
