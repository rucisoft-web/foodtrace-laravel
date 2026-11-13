@extends('layouts.app')
@section('title', 'Cek '.$doc->number)
@section('crumb') Outbound <small>/ <a href="{{ route('checker.index') }}">Checker</a> / {{ $doc->number }}</small> @endsection
@section('content')
<div class="page-h"><div><h1>Cek {{ $doc->number }} @status($doc->stage)</h1><p>{{ $doc->partner?->name }}{{ $doc->shipTo ? ' → '.$doc->shipTo->name : '' }} · staging {{ $doc->staging?->name }}</p></div></div>
@if($doc->stage === 'staged')
<div class="scanbox"><form method="post" class="inline">@csrf<div class="f" style="flex:1"><label>Scan LPN / lot / item</label><input type="text" name="code" required data-autofocus autocomplete="off"></div><button class="btn red">Cek</button></form></div>
@endif
<div class="card"><div class="card-b flush"><table class="t">
  <thead><tr><th>Item</th><th>Lot</th><th>LPN</th><th class="n">Qty</th><th>Dicek</th></tr></thead>
  <tbody>@foreach($doc->details as $d)<tr class="{{ $d->checked ? 'ok' : '' }}"><td><b>{{ $d->item->code }}</b> {{ $d->item->name }}</td><td>{{ $d->lot?->name }}</td><td>{{ $d->anyLpn()?->name }}</td><td class="n">@qty($d->qty) {{ $d->item->uom->code }}</td><td>{!! $d->checked ? '<span class="badge b-green">✓</span>' : '' !!}</td></tr>@endforeach</tbody>
</table></div></div>
@endsection
