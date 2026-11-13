@extends('layouts.app')
@section('title', $job->number)
@section('crumb') Outbound <small>/ <a href="{{ route('loading.index') }}">Loading Job</a> / {{ $job->number }}</small> @endsection
@section('content')
<div class="page-h"><div><h1>{{ $job->number }} @status($job->state)</h1><p>@d($job->date) · {{ $job->vehicle_plate }} · {{ $job->driver }} · {{ $p['loaded'] }}/{{ $p['lines'] }} baris dimuat · {{ \App\Support\Wms::fmt($p['weight'], 2) }} kg</p></div>
  <div class="sp"></div>
  <a class="btn ghost" href="{{ route('loading.manifest', $job) }}" target="_blank">⎙ Manifest</a>
  <a class="btn ghost" href="{{ route('loading.sj', $job) }}" target="_blank">⎙ Surat jalan</a>
  @if($job->state === 'open')<form method="post" action="{{ route('loading.cancel', $job) }}" data-confirm="Batalkan loading job?">@csrf<button class="btn ghost">Batalkan</button></form>@endif
</div>
@if($job->state === 'open')
<div class="grid g2">
  <div class="scanbox"><form method="post" action="{{ route('loading.scan', $job) }}" class="inline">@csrf<div class="f" style="flex:1"><label>Scan LPN yang dimuat</label><input type="text" name="code" required data-autofocus autocomplete="off"></div><button class="btn">Muat</button></form></div>
  <div class="scanbox" style="background:var(--maroon-soft);border-color:var(--maroon)"><div class="actions-row">
    <form method="post" action="{{ route('loading.close', $job) }}" data-confirm="Tutup loading? Semua dokumen Goods Issue dan POD dibuat.">@csrf<button class="btn red" @disabled($p['loaded'] < $p['lines'])>✓ Tutup loading (GI)</button></form>
    @if(auth()->user()->isManager() && $p['loaded'] < $p['lines'])<form method="post" action="{{ route('loading.close', $job) }}" data-confirm="Tutup paksa walau {{ $p['lines'] - $p['loaded'] }} baris belum discan?">@csrf<input type="hidden" name="force" value="1"><button class="btn ghost">Tutup paksa</button></form>@endif
    <div class="progress" style="flex:1"><i style="width:{{ $p['lines'] ? $p['loaded'] / $p['lines'] * 100 : 0 }}%"></i></div></div></div>
</div>
@endif
<div class="grid g3">
  <div class="card line-navy"><div class="card-h"><h3>Dokumen & POD</h3></div><div class="card-b flush"><table class="t">
    @foreach($docs as $d)<tr><td><a href="{{ route('outbound.show', $d) }}">{{ $d->number }}</a><br><small>{{ $d->shipTo?->name ?? $d->partner?->name }}</small></td><td>@status($d->stage)</td>
      <td>@if($d->pod)<a href="{{ route('pod.show', $d->pod) }}">POD</a>@endif</td></tr>@endforeach
  </table></div></div>
  <div class="card line-red span2"><div class="card-h"><h3>Muatan</h3></div><div class="card-b flush tbl-wrap"><table class="t">
    <thead><tr><th>Dokumen</th><th>Item</th><th>Lot</th><th>LPN</th><th class="n">Qty</th><th>Dimuat</th></tr></thead>
    <tbody>@foreach($details as $d)<tr class="{{ $d->loaded ? 'ok' : '' }}"><td><small>{{ $d->document->number }}</small></td><td><b>{{ $d->item->code }}</b> <small>{{ $d->item->name }}</small></td><td>{{ $d->lot?->name }}</td><td>{{ $d->anyLpn()?->name }}</td><td class="n">@qty($d->qty) {{ $d->item->uom->code }}</td><td>{!! $d->loaded ? '<span class="badge b-green">✓</span>' : '' !!}</td></tr>@endforeach</tbody>
  </table></div></div>
</div>
@endsection
