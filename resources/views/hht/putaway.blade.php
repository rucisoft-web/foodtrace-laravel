@extends('layouts.hht')
@section('title', 'Put away / bin transfer')
@section('back', route('hht.home'))
@section('content')
@if(!$lpn)
  <form method="get"><label>1. Scan LPN</label><input type="text" name="lpn" class="scan" data-scan autocomplete="off"></form>
  @if($waiting->isNotEmpty())<p class="muted" style="margin-top:12px">Menunggu put away di dock:</p>@foreach($waiting as $w)<a class="row" href="{{ route('hht.putaway', ['lpn' => $w->lpn->name]) }}"><b>{{ $w->lpn->name }}</b> · {{ $w->item->code }}<br><small>{{ $w->item->name }}</small></a>@endforeach @endif
@else
  <div class="row next"><b>{{ $lpn->name }}</b> · @zone($stock->item->zone) {{ $stock->item->code }}<br>{{ $stock->item->name }}<br><small>lot {{ $stock->lot?->name }} · {{ \App\Support\Wms::fmt($stock->qty) }} {{ $stock->item->uom->code }} · di <b>{{ $stock->location->full_name }}</b></small></div>
  @if($suggest)<div class="msg ok">Saran: <b>{{ $suggest->full_name }}</b> ({{ $suggest->barcode }})</div>@endif
  <form method="post">@csrf<input type="hidden" name="lpn" value="{{ $lpn->name }}"><label>2. Scan bin tujuan</label><input type="text" name="dest" class="scan" data-scan autocomplete="off"><button class="btn red block" style="margin-top:8px">Pindahkan</button></form>
  <a class="btn ghost block" style="margin-top:8px" href="{{ route('hht.putaway') }}">Batal</a>
@endif
@endsection
