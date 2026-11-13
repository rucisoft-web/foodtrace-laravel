@extends('layouts.app')
@section('title', $doc->number)
@section('crumb') Inventory <small>/ <a href="{{ route('internal.index') }}">Bin Transfer</a> / {{ $doc->number }}</small> @endsection
@section('content')
<div class="page-h"><div><h1>{{ $doc->number }} @status($doc->state)</h1><p>{{ $doc->reason }} · @dt($doc->done_at) · {{ $doc->user?->name }}@if($doc->owner) · owner {{ $doc->owner->name }}@endif</p></div></div>
<div class="card line-navy"><div class="card-b flush"><table class="t">
  <thead><tr><th>Item</th><th>Lot</th><th>LPN asal</th><th>LPN tujuan</th><th>Dari</th><th>Ke</th><th class="n">Qty</th></tr></thead>
  <tbody>@foreach($doc->details as $d)<tr><td><b>{{ $d->item->code }}</b> {{ $d->item->name }}</td><td>{{ $d->lot?->name }}</td><td>{{ $d->lpn?->name }}</td><td>{{ $d->resultLpn?->name ?? '(tanpa LPN)' }}</td>
    <td>{{ $d->from?->full_name }}</td><td>{{ $d->to?->full_name }}</td><td class="n">@qty($d->qty) {{ $d->item->uom->code }}</td></tr>@endforeach</tbody>
</table></div></div>
@endsection
