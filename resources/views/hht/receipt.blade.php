@extends('layouts.hht')
@section('title', $doc->number)
@section('back', route('hht.receipts'))
@php use App\Support\Wms; @endphp
@section('content')
<div class="row"><b>{{ $doc->origin }}</b> · {{ $doc->partner?->name }}<br><small>Dock: <b>{{ $doc->dock?->name ?? 'belum diisi' }}</b>{{ $doc->container_no ? ' · '.$doc->container_no : '' }}</small></div>
@if(!$doc->dock_id)
  <form method="post">@csrf<input type="hidden" name="action" value="dock"><label>1. Scan loading dock</label><input type="text" name="dock" class="scan" placeholder="DOCK_01" data-scan></form>
@elseif(!$line)
  <form method="post">@csrf<input type="hidden" name="action" value="item"><label>Scan item (barcode / ItemCode)</label><input type="text" name="code" class="scan" data-scan></form>
  <div style="margin-top:10px">@foreach($doc->lines as $l)@php $q = $l->qtyDone(); @endphp
    <a class="row {{ $q >= $l->qty_demand && $q > 0 ? 'done' : '' }}" href="{{ route('hht.receipt', [$doc, 'line' => $l->id]) }}"><b>{{ $l->item->code }}</b> {{ $l->item->name }}<br><small>{{ Wms::fmt($q) }} / {{ Wms::fmt($l->qty_demand) }} {{ $l->uom->code }}{{ $l->item->catch_weight ? ' · scan per karton' : '' }}</small></a>
  @endforeach</div>
@else
  <div class="row next"><b>{{ $line->item->code }}</b> {{ $line->item->name }}<br><small>Diterima {{ Wms::fmt($line->qtyDone()) }} / {{ Wms::fmt($line->qty_demand) }} {{ $line->uom->code }}</small> · <a href="{{ route('hht.receipt', $doc) }}">ganti item</a></div>
  <form method="post">@csrf<input type="hidden" name="action" value="scan"><input type="hidden" name="line" value="{{ $line->id }}">
    @if($line->item->catch_weight)
      <label>Scan barcode karton</label><input type="text" name="barcode" class="scan" data-scan autocomplete="off">
      <div class="grid g2" style="gap:8px;margin-top:6px"><div class="f"><label>Berat manual (kg)</label><input type="text" name="weight" inputmode="decimal"></div><div class="f"><label>Lot</label><input type="text" name="lot" value="{{ old('lot') }}"></div></div>
    @else
      <div class="grid g2" style="gap:8px"><div class="f"><label>Qty ({{ $line->uom->code }})</label><input type="text" name="qty" inputmode="decimal" class="scan" data-scan></div><div class="f"><label>Lot</label><input type="text" name="lot" value="{{ old('lot') }}"></div></div>
    @endif
    <div class="f"><label>Expiry</label><input type="date" name="expiry" value="{{ old('expiry') }}"></div>
    <button class="btn red block">Terima</button>
  </form>
@endif
@foreach($open as $o)
  <form method="post" class="row" style="margin-top:10px">@csrf<input type="hidden" name="action" value="pallet"><input type="hidden" name="lpn" value="{{ $o->result_lpn_id }}"><input type="hidden" name="line" value="{{ $line?->id }}">
    Palet terbuka <b>{{ $o->resultLpn->name }}</b> · {{ $o->item->code }} · lot {{ $o->lot?->name }} · <b>{{ Wms::fmt($o->qty) }}</b>
    <div class="grid g2" style="gap:8px;margin-top:6px"><input type="text" name="pallet_no" placeholder="No. palet"><button class="btn ghost">Simpan palet</button></div></form>
@endforeach
@if($doc->dock_id && $doc->details()->exists())
  <form method="post" style="margin-top:14px" data-confirm="Validasi penerimaan {{ $doc->number }}?">@csrf<input type="hidden" name="action" value="validate"><button class="btn green block">✓ Validasi penerimaan</button></form>
  <a class="btn ghost block" style="margin-top:8px" href="{{ route('lpn.labels', ['document' => $doc->id]) }}" target="_blank">⎙ Cetak label LPN</a>
@endif
@endsection
