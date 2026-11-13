@extends('layouts.app')
@section('title', 'POD '.$pod->name)
@section('crumb') Outbound <small>/ <a href="{{ route('pod.index') }}">POD</a> / {{ $pod->name }}</small> @endsection
@section('content')
<div class="page-h"><div><h1>POD {{ $pod->name }}</h1><p>{{ $pod->document->partner?->name }}{{ $pod->document->shipTo ? ' → '.$pod->document->shipTo->name : '' }} · Ref. SO {{ $pod->document->origin }}</p></div>
  <div class="sp"></div><a class="btn ghost" href="{{ route('outbound.sj', $pod->document) }}" target="_blank">⎙ Surat jalan</a><a class="btn ghost" href="{{ $pod->url() }}" target="_blank">↗ Halaman driver</a></div>
<div class="grid g3">
  <div class="card line-navy"><div class="card-h"><h3>Penerimaan</h3></div><div class="card-b">
    <dl class="kv"><dt>Status</dt><dd>{{ $pod->state === 'done' ? 'Diterima' : 'Menunggu' }}</dd><dt>Penerima</dt><dd>{{ $pod->received_by ?: '-' }}</dd><dt>Waktu</dt><dd>@dt($pod->received_at)</dd>
      <dt>Kendaraan</dt><dd>{{ $pod->document->loading?->vehicle_plate }} · {{ $pod->document->loading?->driver }}</dd><dt>Catatan</dt><dd>{{ $pod->remark ?: '-' }}</dd></dl>
    @if($pod->photo_path)<p><a href="{{ asset($pod->photo_path) }}" target="_blank"><img src="{{ asset($pod->photo_path) }}" alt="foto" style="max-width:100%;border-radius:6px;border:1px solid var(--line)"></a></p>@endif
    @if($pod->state !== 'done')<div style="width:150px" class="qr-inline">{!! \App\Support\Qr::svg($pod->url(), 150) !!}</div><small class="muted">QR ini tercetak di surat jalan.</small>@endif
  </div></div>
  <div class="card line-red span2"><div class="card-h"><h3>Qty dikirim vs diterima</h3></div><div class="card-b flush"><table class="t">
    <thead><tr><th>Item</th><th class="n">Dikirim</th><th class="n">Diterima</th><th class="n">Selisih</th><th>Catatan</th></tr></thead>
    <tbody>@foreach($pod->lines as $l)@php $df = round($l->qty_received - $l->qty_sent, 3); @endphp
      <tr class="{{ $pod->state === 'done' && abs($df) > 0.0005 ? 'bad' : '' }}"><td><b>{{ $l->item->code }}</b> {{ $l->item->name }}</td><td class="n">@qty($l->qty_sent) {{ $l->uom }}</td><td class="n">{{ $pod->state === 'done' ? \App\Support\Wms::fmt($l->qty_received) : '-' }}</td>
        <td class="n">{{ $pod->state === 'done' && $df ? \App\Support\Wms::fmt($df) : '' }}</td><td><small>{{ $l->remark }}</small></td></tr>@endforeach</tbody>
  </table></div></div>
</div>
@endsection
