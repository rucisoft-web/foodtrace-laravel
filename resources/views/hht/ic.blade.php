@extends('layouts.hht')
@section('title', 'Inventory check')
@section('back', $check ? route('hht.ic') : route('hht.home'))
@section('content')
@if(!$check)
  <form method="post">@csrf<label>Scan LPN atau lokasi</label><input type="text" name="code" class="scan" data-scan autocomplete="off"></form>
  <p class="muted">Sistem memuat stok saat ini; isi qty fisik dan expiry yang terbaca, lalu kirim untuk approval kepala gudang.</p>
@else
  <div class="row"><b>{{ $check->number }}</b> · {{ $check->title() }}</div>
  <form method="post">@csrf<input type="hidden" name="action" value="submit"><input type="hidden" name="check" value="{{ $check->id }}">
  @forelse($check->lines as $l)
    <div class="row"><b>{{ $l->item->code }}</b> {{ $l->item->name }}<br><small>{{ $l->location->name }} · lot {{ $l->lot?->name }} · {{ $l->lpn?->name }} · sistem {{ \App\Support\Wms::fmt($l->qty_before) }} {{ $l->item->uom->code }}</small>
      <div class="grid g2" style="gap:8px;margin-top:6px"><div class="f"><label>Qty fisik</label><input type="text" inputmode="decimal" name="rows[{{ $l->id }}][qty]" value="{{ \App\Support\Wms::fmt($l->qty_after) }}"></div>
        <div class="f"><label>Expiry fisik</label><input type="date" name="rows[{{ $l->id }}][expiry]" value="{{ optional($l->expiry_after)->format('Y-m-d') }}"></div></div></div>
  @empty <p class="muted">Tidak ada stok.</p> @endforelse
  <button class="btn red block">Kirim untuk approval</button></form>
@endif
@endsection
