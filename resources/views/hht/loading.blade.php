@extends('layouts.hht')
@section('title', $job->number)
@section('back', route('hht.loadings'))
@section('content')
<div class="row"><b>{{ $job->vehicle_plate }}</b> · {{ $job->driver }}<br><small>{{ $p['loaded'] }}/{{ $p['lines'] }} dimuat · {{ \App\Support\Wms::fmt($p['weight'], 2) }} kg</small><div class="progress" style="margin-top:4px"><i style="width:{{ $p['lines'] ? $p['loaded'] / $p['lines'] * 100 : 0 }}%"></i></div></div>
<form method="post">@csrf<label>Scan LPN yang naik</label><input type="text" name="code" class="scan" data-scan autocomplete="off"></form>
@if($p['lines'] && $p['loaded'] >= $p['lines'])<form method="post" style="margin-top:10px" data-confirm="Tutup loading dan Goods Issue?">@csrf<input type="hidden" name="action" value="close"><button class="btn green block">✓ Tutup loading</button></form>@endif
<div style="margin-top:10px">@foreach($details as $d)<div class="row {{ $d->loaded ? 'done' : '' }}">{{ $d->loaded ? '✓' : '○' }} <b>{{ $d->anyLpn()?->name ?? $d->item->code }}</b> · {{ $d->item->name }}<br><small>{{ $d->document->number }} · {{ \App\Support\Wms::fmt($d->qty) }} {{ $d->item->uom->code }}</small></div>@endforeach</div>
@endsection
