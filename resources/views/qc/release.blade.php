@extends('layouts.app')
@section('title', 'Keputusan QC')
@section('crumb') Inbound <small>/ <a href="{{ route('qc.index') }}">QC Hold / Release</a> / Keputusan</small> @endsection
@section('content')
<div class="page-h"><div><h1>Keputusan QC</h1><p>{{ $s->item->label() }} · lot {{ $s->lot?->name }} · LPN {{ $s->lpn?->name ?? '-' }} · di {{ $s->location->full_name }}</p></div></div>
<div class="grid g2">
  <div class="card line-green"><div class="card-h"><h3>Rilis ke rak (Available)</h3></div><div class="card-b">
    <form method="post">@csrf<input type="hidden" name="decision" value="release">
      <div class="f"><label>Bin tujuan</label><input type="text" name="dest" value="{{ $suggest?->barcode }}" required><div class="hint">{{ $suggest ? 'Saran bin kosong zona: '.$suggest->full_name : 'Tidak ada bin kosong di zona ini.' }}</div></div>
      <div class="f"><label>Qty (kosong = semua {{ \App\Support\Wms::fmt($s->qty) }} {{ $s->item->uom->code }})</label><input type="text" name="qty" inputmode="decimal"></div>
      <div class="f"><label>Catatan hasil QC</label><input type="text" name="note" placeholder="mis. hasil uji lab OK"></div>
      <button class="btn green">✓ Rilis</button>
    </form>
  </div></div>
  <div class="card line-red"><div class="card-h"><h3>Tolak (damage / waste / retur)</h3></div><div class="card-b">
    <form method="post">@csrf<input type="hidden" name="decision" value="reject">
      <div class="f"><label>Lokasi</label><select name="dest" required>@foreach($reject as $l)<option value="{{ $l->barcode ?: $l->full_name }}">{{ $l->full_name }} ({{ $l->areaLabel() }})</option>@endforeach</select></div>
      <div class="f"><label>Qty (kosong = semua)</label><input type="text" name="qty" inputmode="decimal"></div>
      <div class="f"><label>Alasan penolakan</label><input type="text" name="note" required></div>
      <button class="btn red">✕ Tolak</button>
    </form>
  </div></div>
</div>
@endsection
