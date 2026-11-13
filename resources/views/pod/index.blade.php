@extends('layouts.app')
@section('title', 'POD')
@section('crumb') Outbound <small>/ POD</small> @endsection
@section('content')
<div class="page-h"><div><h1>Proof of Delivery (POD)</h1><p>Diisi driver / penerima lewat QR di surat jalan: nama penerima, qty diterima per item, catatan dan foto.</p></div></div>
<div class="tabs">
  <a href="{{ route('pod.index') }}" class="{{ !request('state') && !request('diff') ? 'on' : '' }}">Semua</a>
  <a href="{{ route('pod.index', ['state' => 'open']) }}" class="{{ request('state') === 'open' ? 'on' : '' }}">Menunggu <span class="badge b-navy">{{ $counts['open'] }}</span></a>
  <a href="{{ route('pod.index', ['state' => 'done']) }}" class="{{ request('state') === 'done' ? 'on' : '' }}">Diterima <span class="badge b-green">{{ $counts['done'] }}</span></a>
  <a href="{{ route('pod.index', ['diff' => 1]) }}" class="{{ request('diff') ? 'on' : '' }}">Ada selisih <span class="badge b-red">{{ $counts['diff'] }}</span></a>
</div>
<div class="card"><div class="card-b flush tbl-wrap"><table class="t">
  <thead><tr><th>Dokumen</th><th>Customer / outlet</th><th>Loading</th><th class="n">Item</th><th>Penerima</th><th>Diterima</th><th>Status</th></tr></thead>
  <tbody>@forelse($rows as $p)
    <tr class="{{ $p->has_difference ? 'warn' : '' }}"><td><a href="{{ route('pod.show', $p) }}"><b>{{ $p->name }}</b></a><br><small>{{ $p->document->origin }}</small></td>
      <td>{{ $p->document->partner?->name }}<br><small>{{ $p->document->shipTo?->name }}</small></td><td>{{ $p->document->loading?->number }}<br><small>{{ $p->document->loading?->vehicle_plate }}</small></td>
      <td class="n">{{ $p->lines_count }}</td><td>{{ $p->received_by }}</td><td>@dt($p->received_at)</td>
      <td>{!! $p->state === 'done' ? ($p->has_difference ? '<span class="badge b-red">Selisih</span>' : '<span class="badge b-green">Diterima</span>') : '<span class="badge b-navy">Menunggu</span>' !!}</td></tr>
  @empty <tr><td colspan="7" class="empty">Belum ada POD.</td></tr> @endforelse</tbody>
</table></div></div>
{{ $rows->links() }}
@endsection
