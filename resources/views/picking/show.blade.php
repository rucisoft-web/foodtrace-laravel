@extends('layouts.app')
@section('title', $job->number)
@section('crumb') Outbound <small>/ <a href="{{ route('jobs.index') }}">Picking Job</a> / {{ $job->number }}</small> @endsection
@php $todo = $details->where('picked', false)->count(); @endphp
@section('content')
<div class="page-h"><div><h1>{{ $job->number }} @status($job->state)</h1><p>{{ $job->description }} · {{ $docs->count() }} dokumen · {{ $details->count() - $todo }}/{{ $details->count() }} baris diambil · siap {{ $job->readyPct() }}%</p></div>
  <div class="sp"></div><a class="btn ghost" href="{{ route('hht.pick', $job) }}">▣ Buka di HHT</a></div>
<div class="grid g3">
  <div class="card line-navy"><div class="card-h"><h3>Dokumen</h3></div><div class="card-b flush"><table class="t">
    @foreach($docs as $d)<tr><td><a href="{{ route('outbound.show', $d) }}">{{ $d->number }}</a><br><small>{{ $d->shipTo?->name ?? $d->partner?->name }}</small></td><td>@status($d->stage)</td>
      <td>{!! $d->state === 'open' ? '<span class="badge b-red">short</span>' : '' !!}</td></tr>@endforeach
  </table></div></div>
  <div class="card line-red span2"><div class="card-h"><h3>Finish trip → outbound staging</h3></div><div class="card-b">
    @if($todo)<p class="muted">Masih ada <b>{{ $todo }}</b> baris yang belum diambil. Setelah semua diambil, scan lokasi staging.</p>@endif
    <form method="post" action="{{ route('jobs.stage', $job) }}" class="inline">@csrf
      <div class="f"><label>Lokasi staging</label><input type="text" name="location" value="{{ $staging?->barcode }}" required></div>
      <button class="btn red" @disabled($todo)>Letakkan di staging</button></form>
  </div></div>
</div>
<div class="card"><div class="card-h"><h3>Daftar ambil (urut lokasi, FEFO)</h3></div><div class="card-b flush tbl-wrap"><table class="t">
  <thead><tr><th>Lokasi</th><th>Item</th><th>Lot · expiry</th><th>LPN</th><th class="n">Qty</th><th>Dokumen</th><th>Ambil (scan lokasi + LPN/item)</th></tr></thead>
  <tbody>@foreach($details as $d)
    <tr class="{{ $d->picked ? 'ok' : '' }}"><td><b>{{ $d->from?->name }}</b><br><small>{{ $d->from?->barcode }}</small></td><td>@zone($d->item->zone) <b>{{ $d->item->code }}</b><br><small>{{ $d->item->name }}</small></td>
      <td>{{ $d->lot?->name }}<br><small>@d($d->lot?->expiry_date)</small></td><td>{{ $d->lpn?->name }}</td><td class="n">@qty($d->qty) {{ $d->item->uom->code }}</td><td><small>{{ $d->document->number }}</small></td>
      <td>@if($d->picked)<span class="badge b-green">✓ diambil</span>@else
        <form method="post" action="{{ route('jobs.pick', [$job, $d]) }}" class="inline">@csrf
          <div class="f"><input type="text" name="location" placeholder="lokasi" style="width:110px" required></div><div class="f"><input type="text" name="code" placeholder="LPN / lot / item" style="width:120px" required></div>
          <div class="f"><input type="text" name="qty" placeholder="qty" inputmode="decimal" style="width:60px" title="kosong = semua"></div><button class="btn sm">Ambil</button></form>@endif</td></tr>
  @endforeach</tbody>
</table></div></div>
@endsection
