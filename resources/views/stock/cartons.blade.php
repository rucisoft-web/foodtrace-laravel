@extends('layouts.app')
@section('title', 'Tally karton')
@section('crumb') Inbound <small>/ Tally karton</small> @endsection
@section('content')
<div class="page-h">
  <div><h1>Tally karton</h1><p>Setiap karton berat aktual (catch weight) yang discan saat receiving: nomor karton, berat, lot, LPN dan riwayat keluarnya.</p></div>
  <div class="sp"></div><a class="btn ghost" href="{{ request()->fullUrlWithQuery(['export' => 'csv']) }}">⇩ Ekspor CSV</a>
</div>
<div class="grid g4" style="margin-bottom:12px">
  @foreach(\App\Models\Carton::STATES as $k => $l)
    <div class="kpi {{ ['available' => 'green', 'quarantine' => 'amber', 'shipped' => '', 'waste' => 'red'][$k] }}"><div class="l">{{ $l }}</div><div class="v">{{ number_format($stats[$k]->n ?? 0) }}</div><div class="s">{{ \App\Support\Wms::fmt($stats[$k]->kg ?? 0, 2) }} kg</div></div>
  @endforeach
</div>
<form class="filters" method="get">
  <div class="f"><label>Cari</label><input type="search" name="q" value="{{ request('q') }}" placeholder="karton / barcode / LPN / item / PO" data-autofocus></div>
  <div class="f"><label>Status</label><select name="state" data-submit><option value="">Semua</option>@foreach(\App\Models\Carton::STATES as $k => $l)<option value="{{ $k }}" @selected(request('state') === $k)>{{ $l }}</option>@endforeach</select></div>
  <button class="btn">Tampilkan</button>
</form>
<div class="card"><div class="card-b flush tbl-wrap"><table class="t">
  <thead><tr><th>Karton</th><th>Item</th><th>Lot</th><th>LPN</th><th class="n">Kg</th><th class="n">Lb</th><th>Lokasi</th><th>Status</th><th>Inbound</th><th>Outbound</th></tr></thead>
  <tbody>@forelse($rows as $c)
    <tr><td><b>{{ $c->name }}</b>@if($c->manual_weight) <span class="badge b-amber" title="berat diinput manual">manual</span>@endif<br><small class="mono">{{ \Illuminate\Support\Str::limit($c->raw_barcode, 40) }}</small></td>
      <td>{{ $c->item->code }}<br><small>{{ $c->item->name }}</small></td><td>{{ $c->lot?->name }}</td><td>@if($c->lpn)<a href="{{ route('lpn.show', $c->lpn) }}">{{ $c->lpn->name }}</a>@endif</td>
      <td class="n">{{ \App\Support\Wms::fmt($c->weight_kg, 2) }}</td><td class="n">{{ $c->weight_lb ? \App\Support\Wms::fmt($c->weight_lb, 2) : '' }}</td>
      <td><small>{{ $c->location?->full_name }}</small></td><td><span class="badge {{ ['available' => 'b-green', 'quarantine' => 'b-amber', 'shipped' => 'b-grey', 'waste' => 'b-red'][$c->state] ?? 'b-grey' }}">{{ \App\Models\Carton::STATES[$c->state] ?? $c->state }}</span></td>
      <td>@if($c->inDocument)<a href="{{ route('inbound.show', $c->inDocument) }}">{{ $c->inDocument->number }}</a>@endif</td><td>@if($c->outDocument)<a href="{{ route('outbound.show', $c->outDocument) }}">{{ $c->outDocument->number }}</a>@endif</td></tr>
  @empty <tr><td colspan="10" class="empty">Belum ada karton.</td></tr> @endforelse</tbody>
</table></div></div>
{{ $rows->links() }}
@endsection
