@extends('layouts.hht')
@section('title', $job->number)
@section('back', route('hht.jobs'))
@section('content')
<p class="muted" style="margin-top:0">{{ $details->where('picked', true)->count() }}/{{ $details->count() }} baris diambil</p>
@if($next)
  <div class="row next"><small>AMBIL DARI</small><br><b style="font-size:1.3rem">{{ $next->from?->name }}</b> <small>{{ $next->from?->barcode }}</small><hr style="margin:6px 0">
    @zone($next->item->zone) <b>{{ $next->item->code }}</b> {{ $next->item->name }}<br>lot <b>{{ $next->lot?->name }}</b> · exp {{ \App\Support\Ui::date($next->lot?->expiry_date) }}<br>LPN <b>{{ $next->lpn?->name ?? '-' }}</b> · qty <b style="font-size:1.2rem">{{ \App\Support\Wms::fmt($next->qty) }}</b> {{ $next->item->uom->code }}<br><small>{{ $next->document->number }}</small></div>
  <form method="post">@csrf<input type="hidden" name="detail" value="{{ $next->id }}">
    <label>Scan lokasi</label><input type="text" name="location" class="scan" data-scan autocomplete="off">
    <label style="margin-top:6px">Scan LPN / lot / item</label><input type="text" name="code" class="scan" autocomplete="off">
    <div class="f" style="margin-top:6px"><label>Qty diambil (kosong = semua)</label><input type="text" name="qty" inputmode="decimal"></div>
    <button class="btn red block">Ambil</button></form>
@elseif(!$staged)
  <div class="msg ok">Semua barang sudah diambil. Bawa ke outbound staging.</div>
  <form method="post">@csrf<input type="hidden" name="action" value="stage"><label>Scan lokasi staging</label><input type="text" name="location" class="scan" data-scan placeholder="{{ $staging?->barcode }}"><button class="btn red block" style="margin-top:8px">Finish trip</button></form>
@else
  <div class="msg ok">Job sudah di staging, menunggu checker.</div>
@endif
<div style="margin-top:12px">@foreach($details->where('picked', true) as $d)<div class="row done"><small>✓ {{ $d->from?->name }} · {{ $d->item->code }} · {{ \App\Support\Wms::fmt($d->qty) }}</small></div>@endforeach</div>
@endsection
