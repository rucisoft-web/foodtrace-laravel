@extends('layouts.print')
@section('title', 'Manifest muat '.$job->number)
@section('content')
<div class="sheet">
  <div class="doc-h"><img class="logo" src="{{ asset('img/logo-saranakulina.png') }}" alt=""><div class="sp"><h2>MANIFEST MUAT</h2><div><b>{{ $job->number }}</b> · {{ \App\Models\Setting::get('company_name') }}</div></div></div>
  <div class="meta"><div><b>Tanggal:</b> @d($job->date)</div><div><b>Kendaraan:</b> {{ $job->vehicle_plate }}</div><div><b>Pengemudi:</b> {{ $job->driver }}</div></div>
  <table class="p"><thead><tr><th>Outbound</th><th>Customer / outlet</th><th>Item</th><th>Lot</th><th>Expiry</th><th>LPN</th><th class="n">Qty</th><th>Muat</th></tr></thead>
    <tbody>@foreach($docs as $doc)@foreach($doc->details as $d)<tr><td>{{ $doc->number }}</td><td>{{ $doc->shipTo?->name ?? $doc->partner?->name }}</td><td>{{ $d->item->code }} {{ $d->item->name }}</td><td>{{ $d->lot?->name }}</td>
      <td>{{ optional($d->lot?->expiry_date)->format('d-m-Y') }}</td><td>{{ $d->anyLpn()?->name }}</td><td class="n">{{ \App\Support\Wms::fmt($d->qty) }} {{ $d->item->uom->code }}</td><td>{{ $d->loaded ? '✓' : '' }}</td></tr>@endforeach @endforeach</tbody></table>
  <p><b>Total berat:</b> {{ \App\Support\Wms::fmt($p['weight'], 2) }} kg · <b>Baris dimuat:</b> {{ $p['loaded'] }}/{{ $p['lines'] }}</p>
  <div class="sign"><div>Checker</div><div>Pengemudi</div><div>Kepala gudang</div></div>
</div>
@endsection
