@extends('layouts.hht')
@section('title', 'Cek barcode')
@section('back', route('hht.home'))
@section('content')
<form method="get"><label>Scan LPN / lokasi / item</label><input type="text" name="code" class="scan" value="{{ $code }}" data-scan autocomplete="off"></form>
@if($res)
  @if($res['lpn'])<div class="row next"><b>LPN {{ $res['lpn']->name }}</b><br>{{ $res['lpn']->item?->label() }}<br><small>lot {{ $res['lpn']->lot?->name }} · exp {{ \App\Support\Ui::date($res['lpn']->lot?->expiry_date) }} · palet {{ $res['lpn']->pallet_no ?: '-' }}</small></div>@endif
  @if($res['loc'])<div class="row next"><b>{{ $res['loc']->full_name }}</b><br><small>{{ $res['loc']->areaLabel() }} · zona {{ $res['loc']->eff_zone }}</small></div>@endif
  @if($res['item'])<div class="row next"><b>{{ $res['item']->code }}</b> {{ $res['item']->name }}<br><small>stok {{ \App\Support\Wms::fmt($res['item']->onHand()) }} {{ $res['item']->uom->code }}</small></div>@endif
  @foreach($res['stocks'] as $s)<div class="row">{!! \App\Support\Ui::status($s->status()) !!} <b>{{ $s->location->name }}</b> · {{ \App\Support\Wms::fmt($s->qty) }} {{ $s->item->uom->code }}<br><small>{{ $s->item->code }} · lot {{ $s->lot?->name }} · {{ $s->lpn?->name }} · exp {{ \App\Support\Ui::date($s->lot?->expiry_date) }}</small></div>@endforeach
@endif
@endsection
