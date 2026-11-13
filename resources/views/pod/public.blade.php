@extends('layouts.hht')
@section('title', 'POD '.$pod->name)
@section('content')
<div style="text-align:center;margin-bottom:10px"><img src="{{ asset('img/logo-saranakulina.png') }}" alt="" style="height:36px"></div>
<div class="row"><b>{{ $pod->document->partner?->name }}</b>@if($pod->document->shipTo)<br>{{ $pod->document->shipTo->name }}@endif<br><small>{{ $pod->document->shipTo?->address }}</small>
  <br><small>Ref. SO {{ $pod->document->origin }} · {{ $pod->document->loading?->vehicle_plate }} · {{ $pod->document->loading?->driver }}</small></div>
@if($pod->state === 'done')
  <div class="msg ok">Sudah diterima oleh {{ $pod->received_by }} pada {{ \App\Support\Ui::date($pod->received_at, true) }}.</div>
  @foreach($pod->lines as $l)<div class="row {{ abs($l->qty_received - $l->qty_sent) > 0.0005 ? '' : 'done' }}"><b>{{ $l->item->name }}</b><br>Dikirim {{ \App\Support\Wms::fmt($l->qty_sent) }} · diterima <b>{{ \App\Support\Wms::fmt($l->qty_received) }}</b> {{ $l->uom }}{{ $l->remark ? ' · '.$l->remark : '' }}</div>@endforeach
  @if($pod->photo_path)<img src="{{ asset($pod->photo_path) }}" alt="" style="width:100%;border-radius:6px">@endif
@else
<form method="post" enctype="multipart/form-data">@csrf
  <p class="muted">Periksa barang, ubah qty bila ada yang kurang / rusak, lalu kirim.</p>
  @foreach($pod->lines as $l)
    <div class="row"><b>{{ $l->item->code }}</b> {{ $l->item->name }}<br><small>Dikirim {{ \App\Support\Wms::fmt($l->qty_sent) }} {{ $l->uom }}</small>
      <div class="grid g2" style="gap:8px;margin-top:6px"><div class="f"><label>Qty diterima</label><input type="text" inputmode="decimal" name="lines[{{ $l->id }}][qty]" value="{{ \App\Support\Wms::fmt($l->qty_sent) }}"></div>
        <div class="f"><label>Catatan</label><input type="text" name="lines[{{ $l->id }}][remark]" placeholder="rusak / kurang"></div></div></div>
  @endforeach
  <div class="f"><label>Nama penerima *</label><input type="text" name="received_by" required value="{{ old('received_by') }}"></div>
  <div class="f"><label>Catatan umum</label><input type="text" name="remark" value="{{ old('remark') }}"></div>
  <div class="f"><label>Foto barang / surat jalan bertanda tangan</label><input type="file" name="photo" accept="image/*" capture="environment"></div>
  <button class="btn red block">Kirim bukti penerimaan</button>
</form>
@endif
@endsection
