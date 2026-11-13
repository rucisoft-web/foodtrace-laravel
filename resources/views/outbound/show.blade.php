@extends('layouts.app')
@section('title', $doc->number)
@section('crumb') Outbound <small>/ <a href="{{ route('outbound.index') }}">Outbound Document</a> / {{ $doc->number }}</small> @endsection
@php use App\Support\Wms; $open = $doc->isOpen(); $order = ['open', 'picking', 'staged', 'checked', 'loading', 'shipped', 'delivered']; $at = array_search($doc->stage, $order); @endphp
@section('content')
<div class="page-h">
  <div><h1>{{ $doc->number }} @status($doc->state)</h1><p>{{ $doc->typeLabel() }} · Ref. SAP <b>{{ $doc->origin ?: '-' }}</b> · {{ $doc->partner?->name }}@if($doc->shipTo) → <b>{{ $doc->shipTo->name }}</b>@endif</p></div>
  <div class="sp"></div>
  @if($open)
    <form method="post" action="{{ route('outbound.reserve', $doc) }}">@csrf<button class="btn ghost">↻ Reservasi FEFO</button></form>
    @if($doc->details->isNotEmpty())<form method="post" action="{{ route('outbound.unreserve', $doc) }}" data-confirm="Batalkan semua reservasi?">@csrf<button class="btn ghost">Batalkan reservasi</button></form>@endif
    @if(auth()->user()->isManager() && $doc->details->isNotEmpty())<form method="post" action="{{ route('outbound.issue', $doc) }}" data-confirm="Goods Issue langsung tanpa loading job? Stok keluar dan Delivery dikirim ke antrean SAP.">@csrf<button class="btn red">Goods Issue langsung</button></form>@endif
  @endif
  <a class="btn ghost" href="{{ route('outbound.sj', $doc) }}" target="_blank">⎙ Surat jalan</a>
</div>
<div class="steps">@foreach($order as $i => $k)<span class="{{ $i < $at ? 'done' : ($i === $at ? 'on' : '') }}">{{ Wms::OUT_STAGES[$k] }}</span>@endforeach</div>
<div class="grid g3">
  <div class="card line-navy"><div class="card-h"><h3>Header</h3></div><div class="card-b"><dl class="kv">
    <dt>Customer</dt><dd>{{ $doc->partner?->label() }}</dd><dt>ShipTo</dt><dd>{{ $doc->shipTo?->name ?? '-' }}<br><small class="muted">{{ $doc->shipTo?->address }}</small></dd>
    <dt>Rute</dt><dd>{{ $doc->shipping_group ?: '-' }}</dd><dt>Tanggal kirim</dt><dd>@d($doc->scheduled_at)</dd><dt>Prioritas</dt><dd>{{ $doc->priority ?: '-' }}</dd>
    <dt>Owner</dt><dd>{{ $doc->owner?->name ?? 'Milik sendiri' }}</dd><dt>Min. umur simpan</dt><dd>{{ $doc->partner?->min_shelf_life_pct ? $doc->partner->min_shelf_life_pct.'%' : '-' }}</dd>
    <dt>Picking job</dt><dd>@if($doc->pickingJob)<a href="{{ route('jobs.show', $doc->pickingJob) }}">{{ $doc->pickingJob->number }}</a>@else - @endif</dd>
    <dt>Staging</dt><dd>{{ $doc->staging?->name ?? '-' }}</dd>
    <dt>Loading</dt><dd>@if($doc->loading)<a href="{{ route('loading.show', $doc->loading) }}">{{ $doc->loading->number }}</a> · {{ $doc->loading->vehicle_plate }}@else - @endif</dd>
    <dt>POD</dt><dd>@if($doc->pod)<a href="{{ route('pod.show', $doc->pod) }}">{{ $doc->pod->state === 'done' ? 'Diterima '.$doc->pod->received_by : 'Menunggu' }}</a>@else - @endif</dd>
    @foreach($doc->sapPostings as $p)<dt>SAP {{ \App\Models\SapPosting::TYPES[$p->doc_type] ?? '' }}</dt><dd>@status($p->state) {{ $p->sap_doc_num }}</dd>@endforeach
  </dl></div></div>
  <div class="card line-red span2"><div class="card-h"><h3>Baris SO & reservasi FEFO</h3></div><div class="card-b flush tbl-wrap"><table class="t">
    <thead><tr><th>Item</th><th class="n">Diminta</th><th class="n">Reservasi</th><th>Lot · expiry · lokasi · LPN</th><th>Status</th></tr></thead>
    <tbody>@foreach($doc->lines as $l)
      @php $res = $l->details->sum('qty'); @endphp
      <tr class="{{ $res + 0.0005 < $l->qty_demand && $open ? 'bad' : '' }}"><td>@zone($l->item->zone) <b>{{ $l->item->code }}</b><br><small>{{ $l->item->name }}</small></td>
        <td class="n">@qty($l->qty_demand) {{ $l->uom->code }}</td><td class="n"><b>@qty($res)</b></td>
        <td>@foreach($l->details as $d)<div><small>{{ $d->lot?->name }} · exp @d($d->lot?->expiry_date) · {{ $d->from?->name }} · {{ $d->lpn?->name }} · <b>@qty($d->qty)</b></small>
          {!! $d->picked ? '<span class="badge b-green">diambil</span>' : '' !!}{!! $d->checked ? '<span class="badge b-navy">dicek</span>' : '' !!}{!! $d->loaded ? '<span class="badge b-brown">dimuat</span>' : '' !!}</div>@endforeach</td>
        <td>@if($res + 0.0005 < $l->qty_demand && $open)<span class="badge b-red">short @qty($l->qty_demand - $res)</span>@endif</td></tr>
    @endforeach</tbody></table></div></div>
</div>
@endsection
