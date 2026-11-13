@extends('layouts.app')
@section('title', 'Picking Job')
@section('crumb') Outbound <small>/ Picking Job</small> @endsection
@section('content')
<div class="page-h"><div><h1>Picking Job</h1><p>Satu job berisi beberapa outbound document (biasanya satu rute). Picker mengambil sesuai saran lokasi FEFO lalu meletakkan di outbound staging.</p></div>
  <div class="sp"></div><a class="btn ghost" href="{{ route('hht.jobs') }}">▣ Picking dengan HHT</a><a class="btn red" href="{{ route('outbound.index', ['nojob' => 1]) }}">+ Buat dari Outbound</a></div>
<div class="tabs">@foreach(['in_progress' => 'Berjalan', 'done' => 'Selesai', 'cancel' => 'Batal', 'all' => 'Semua'] as $k => $l)<a href="{{ route('jobs.index', ['state' => $k]) }}" class="{{ $state === $k ? 'on' : '' }}">{{ $l }}</a>@endforeach</div>
<div class="card"><div class="card-b flush tbl-wrap"><table class="t">
  <thead><tr><th>Nomor</th><th>Deskripsi / rute</th><th class="n">Dokumen</th><th>Siap</th><th>Pengemudi</th><th>Ekspedisi</th><th>Dibuat</th><th>Status</th></tr></thead>
  <tbody>@forelse($jobs as $j)
    @php $pct = $j->readyPct(); @endphp
    <tr><td><a href="{{ route('jobs.show', $j) }}"><b>{{ $j->number }}</b></a></td><td>{{ $j->description }}{{ $j->use_tote ? ' · tote' : '' }}</td><td class="n">{{ $j->documents_count }}</td>
      <td><div class="progress"><i style="width:{{ $pct }}%"></i></div><small>{{ $pct }}%</small></td><td>{{ $j->driver }}</td><td>{{ $j->transporter }}</td><td>@dt($j->created_at)<br><small>{{ $j->user?->name }}</small></td>
      <td>@status($j->state)</td></tr>
  @empty <tr><td colspan="8" class="empty">Tidak ada picking job.</td></tr> @endforelse</tbody>
</table></div></div>
{{ $jobs->links() }}
@endsection
