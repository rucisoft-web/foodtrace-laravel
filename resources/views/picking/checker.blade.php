@extends('layouts.app')
@section('title', 'Checker')
@section('crumb') Outbound <small>/ Checker</small> @endsection
@section('content')
<div class="page-h"><div><h1>Checker</h1><p>Barang di outbound staging dicek ulang per dokumen dengan scan LPN / lot / item sebelum dimuat. Barang yang salah ditolak.</p></div>
  <div class="sp"></div><a class="btn ghost" href="{{ route('hht.check') }}">▣ Cek dengan HHT</a></div>
<div class="card"><div class="card-b flush tbl-wrap"><table class="t">
  <thead><tr><th>Dokumen</th><th>Customer / outlet</th><th>Rute</th><th>Staging</th><th>Picking job</th><th>Dicek</th><th>Tahap</th></tr></thead>
  <tbody>@forelse($docs as $d)
    <tr><td><a href="{{ route('checker.show', $d) }}"><b>{{ $d->number }}</b></a></td><td>{{ $d->partner?->name }}@if($d->shipTo)<br><small>→ {{ $d->shipTo->name }}</small>@endif</td><td>{{ $d->shipping_group }}</td>
      <td>{{ $d->staging?->name }}</td><td>{{ $d->pickingJob?->number }}</td>
      <td><div class="progress"><i style="width:{{ $d->details_count ? $d->checked_count / $d->details_count * 100 : 0 }}%"></i></div><small>{{ $d->checked_count }}/{{ $d->details_count }}</small></td><td>@status($d->stage)</td></tr>
  @empty <tr><td colspan="7" class="empty">Tidak ada barang di staging.</td></tr> @endforelse</tbody>
</table></div></div>
@endsection
