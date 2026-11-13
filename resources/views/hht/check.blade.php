@extends('layouts.hht')
@section('title', 'Checker')
@section('back', $doc ? route('hht.check') : route('hht.home'))
@section('content')
@if(!$doc)
  <form method="post">@csrf<label>Scan nomor DO / SO</label><input type="text" name="document" class="scan" data-scan autocomplete="off"></form>
  <div style="margin-top:10px">@foreach($staged as $s)<a class="row" href="{{ route('hht.check', ['doc' => $s->id]) }}"><b>{{ $s->number }}</b> · {{ $s->origin }}<br><small>{{ $s->shipTo?->name ?? $s->partner?->name }}</small></a>@endforeach</div>
@else
  <div class="row"><b>{{ $doc->number }}</b> · {{ $doc->shipTo?->name ?? $doc->partner?->name }}<br><small>{{ $doc->details->where('checked', true)->count() }}/{{ $doc->details->count() }} dicek</small></div>
  <form method="post">@csrf<input type="hidden" name="doc" value="{{ $doc->id }}"><label>Scan LPN / lot / item</label><input type="text" name="code" class="scan" data-scan autocomplete="off"></form>
  <div style="margin-top:10px">@foreach($doc->details as $d)<div class="row {{ $d->checked ? 'done' : '' }}">{{ $d->checked ? '✓' : '○' }} <b>{{ $d->item->code }}</b> {{ $d->item->name }}<br><small>{{ $d->lot?->name }} · {{ $d->anyLpn()?->name }} · {{ \App\Support\Wms::fmt($d->qty) }} {{ $d->item->uom->code }}</small></div>@endforeach</div>
@endif
@endsection
