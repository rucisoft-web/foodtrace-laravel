<?php

namespace App\Http\Controllers;

use App\Models\Pod;
use App\Services\OutboundService;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

/** Proof of Delivery: driver membuka lewat QR di surat jalan (tanpa login), isi penerima, qty diterima dan foto. */
class PodController extends Controller
{
    public function index(Request $r)
    {
        $rows = Pod::with('document.partner', 'document.shipTo', 'document.loading')->withCount('lines')
            ->when($r->state, fn ($q, $s) => $q->where('state', $s))->when($r->boolean('diff'), fn ($q) => $q->where('has_difference', true))
            ->when($r->q, fn ($q, $s) => $q->where('name', 'like', "%{$s}%")->orWhereHas('document', fn ($d) => $d->where('origin', 'like', "%{$s}%")))
            ->latest('id')->paginate(50)->withQueryString();
        $counts = ['open' => Pod::where('state', 'open')->count(), 'done' => Pod::where('state', 'done')->count(), 'diff' => Pod::where('has_difference', true)->count()];

        return view('pod.index', compact('rows', 'counts'));
    }

    public function show(Pod $pod)
    {
        $pod->load('lines.item', 'document.partner', 'document.shipTo', 'document.loading');

        return view('pod.show', compact('pod'));
    }

    public function public(Request $r, string $token)
    {
        $pod = Pod::with('lines.item', 'document.partner', 'document.shipTo', 'document.loading')->where('token', $token)->firstOrFail();
        if ($r->isMethod('post') && $pod->state !== 'done') {
            $r->validate(['received_by' => 'required|max:80', 'photo' => 'nullable|image|max:8192']);
            $path = null;
            if ($r->hasFile('photo')) {
                $name = Str::slug($pod->name).'-'.Str::random(6).'.'.$r->file('photo')->extension();
                $r->file('photo')->move(public_path('uploads/pod'), $name);
                $path = 'uploads/pod/'.$name;
            }
            OutboundService::submitPod($pod, $r->received_by, (array) $r->lines, $path, $r->remark);

            return redirect()->route('pod.public', $token)->with('ok', 'Terima kasih. Bukti penerimaan tersimpan.');
        }

        return view('pod.public', compact('pod'));
    }
}
