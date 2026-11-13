@extends('layouts.print')
@section('title', 'Surat jalan'.($docs->count() === 1 ? ' '.$docs->first()->number : ' ('.$docs->count().')'))
@section('content')
@foreach($docs as $p)
<div class="sheet">
  <div class="doc-h">
    <img class="logo" src="{{ asset('img/logo-saranakulina.png') }}" alt="">
    <div class="sp"><h2>SURAT JALAN</h2><div><b>{{ $p->delivery_note ?: $p->number }}</b> · {{ \App\Models\Setting::get('company_name') }}</div></div>
    @if($p->pod)<div style="text-align:center;width:32mm"><div style="width:30mm;margin:auto">{!! \App\Support\Qr::svg($p->pod->url(), 160) !!}</div><div style="font-size:9px">Scan untuk POD<br>(qty diterima, foto)</div></div>@endif
  </div>
  <div class="meta">
    <div><b>Kepada:</b><br>{{ $p->partner?->name }}@if($p->shipTo)<br>{{ $p->shipTo->name }}@endif<br><span class="muted">{{ $p->shipTo?->address ?? $p->partner?->address }} {{ $p->shipTo?->city ?? $p->partner?->city }}</span></div>
    <div><b>Ref. SO:</b> {{ $p->origin }}<br><b>Rute:</b> {{ $p->shipping_group }}<br><b>Tanggal:</b> @d($p->done_at ?? $p->scheduled_at)</div>
    <div><b>Kendaraan:</b> {{ $p->loading?->vehicle_plate ?? $p->vehicle_plate }}<br><b>Pengemudi:</b> {{ $p->loading?->driver ?? $p->driver }}<br><b>Loading:</b> {{ $p->loading?->number }}</div>
  </div>
  <table class="p"><thead><tr><th>No</th><th>Item</th><th>Lot</th><th>Expiry</th><th class="n">Qty</th><th>Satuan</th></tr></thead>
    <tbody>@foreach($p->details as $i => $d)<tr><td>{{ $i + 1 }}</td><td>{{ $d->item->code }} {{ $d->item->name }}</td><td>{{ $d->lot?->name }}</td><td>{{ optional($d->lot?->expiry_date)->format('d-m-Y') }}</td><td class="n">{{ \App\Support\Wms::fmt($d->qty) }}</td><td>{{ $d->item->uom->code }}</td></tr>@endforeach</tbody>
  </table>
  <p class="muted" style="margin-top:10px">Barang diterima dalam keadaan baik dan jumlah sesuai, kecuali dicatat di POD.</p>
  <div class="sign"><div>Gudang</div><div>Pengemudi</div><div>Penerima</div></div>
</div>
@endforeach
@endsection
