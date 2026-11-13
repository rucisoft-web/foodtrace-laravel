@extends('layouts.app')
@section('title', 'Stock Status')
@section('crumb') Inventory <small>/ Stock Status</small> @endsection
@php use App\Support\Wms; @endphp
@section('content')
<div class="page-h">
  <div><h1>Stock Status</h1><p>Stok per item, lot, LPN dan lokasi. Status mengikuti area lokasi: Receiving, Available, Picked, Hold, Damage.</p></div>
  <div class="sp"></div>
  <a class="btn ghost" href="{{ request()->fullUrlWithQuery(['export' => 'csv']) }}">⇩ Ekspor CSV</a>
</div>
<div class="grid g6" style="margin-bottom:12px">
  @foreach(Wms::STOCK_STATUS as $k => $l)
    <a class="kpi {{ ['receiving' => '', 'available' => 'green', 'picked' => 'brown', 'hold' => 'amber', 'damage' => 'red'][$k] }}" href="{{ request()->fullUrlWithQuery(['status' => request('status') === $k ? null : $k, 'page' => null]) }}" style="{{ request('status') === $k ? 'outline:2px solid var(--maroon)' : '' }}">
      <div class="l">{{ $l }}</div><div class="v">{{ number_format($sum[$k]['n'] ?? 0) }}</div><div class="s">{{ Wms::moneyShort($sum[$k]['v'] ?? 0) }}</div></a>
  @endforeach
  <div class="kpi"><div class="l">Total nilai</div><div class="v">{{ Wms::moneyShort($sum->sum('v')) }}</div><div class="s">{{ number_format($sum->sum('n')) }} baris stok</div></div>
</div>
<form class="filters" method="get">
  <div class="f"><label>Cari</label><input type="search" name="q" value="{{ request('q') }}" placeholder="item / lot / LPN / lokasi" data-autofocus></div>
  <div class="f"><label>Zona</label><select name="zone" data-submit><option value="">Semua</option>@foreach(Wms::ZONES as $k => $l)<option value="{{ $k }}" @selected(request('zone') === $k)>{{ $l }}</option>@endforeach</select></div>
  <div class="f"><label>Status</label><select name="status" data-submit><option value="">Semua</option>@foreach(Wms::STOCK_STATUS as $k => $l)<option value="{{ $k }}" @selected(request('status') === $k)>{{ $l }}</option>@endforeach</select></div>
  <div class="f"><label>Kepemilikan</label><select name="owner" data-submit><option value="">Semua</option><option value="own" @selected(request('owner') === 'own')>Milik sendiri</option><option value="titipan" @selected(request('owner') === 'titipan')>Titipan</option></select></div>
  <button class="btn">Tampilkan</button>
</form>
<div class="card"><div class="card-b flush tbl-wrap"><table class="t">
  <thead><tr><th>Lokasi</th><th>Item</th><th>Lot</th><th>Expiry</th><th>LPN</th><th>Owner</th><th>Status</th><th class="n">Qty</th><th class="n">Reservasi</th><th class="n">Nilai</th></tr></thead>
  <tbody>@forelse($rows as $s)
    @php $days = $s->lot?->daysLeft(); @endphp
    <tr class="{{ $days !== null && $days < 0 ? 'bad' : ($days !== null && $days <= 30 ? 'warn' : '') }}">
      <td><a href="{{ route('reports.show', ['by-location', 'q' => $s->location->barcode ?: $s->location->full_name]) }}">{{ $s->location->full_name }}</a></td>
      <td>@zone($s->item->zone) <b>{{ $s->item->code }}</b><br><small>{{ $s->item->name }}</small></td><td>{{ $s->lot?->name }}</td>
      <td>@d($s->lot?->expiry_date)@if($days !== null)<br><small>{{ $days < 0 ? 'lewat '.abs($days).' hr' : $days.' hr' }}</small>@endif</td>
      <td>@if($s->lpn)<a href="{{ route('lpn.show', $s->lpn) }}">{{ $s->lpn->name }}</a>@endif</td><td><small>{{ $s->owner?->name }}</small></td>
      <td>@status($s->status())</td><td class="n"><b>@qty($s->qty)</b> {{ $s->item->uom->code }}</td><td class="n">{{ $s->reserved_qty > 0 ? Wms::fmt($s->reserved_qty) : '' }}</td>
      <td class="n">{{ Wms::money($s->qty * $s->item->cost) }}</td></tr>
  @empty <tr><td colspan="10" class="empty">Tidak ada stok.</td></tr> @endforelse</tbody>
</table></div></div>
{{ $rows->links() }}
@endsection
