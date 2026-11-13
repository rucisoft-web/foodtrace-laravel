@extends('layouts.app')
@section('title', 'Outbound Document')
@section('crumb') Outbound <small>/ Outbound Document</small> @endsection
@php use App\Support\Wms; @endphp
@section('content')
<div class="page-h"><div><h1>Outbound Document</h1><p>Sales Order SAP (SOR) dan pengeluaran lain. Pilih beberapa dokumen satu rute (shipping group) lalu buat Picking Job: stok direservasi FEFO.</p></div></div>
<div class="tabs">
  <a href="{{ route('outbound.index') }}" class="{{ $stage === 'todo' ? 'on' : '' }}">Belum selesai <span class="badge b-navy">{{ $counts->sum() }}</span></a>
  @foreach(['open', 'picking', 'staged', 'checked', 'loading'] as $k)<a href="{{ route('outbound.index', ['stage' => $k]) }}" class="{{ $stage === $k ? 'on' : '' }}">{{ Wms::OUT_STAGES[$k] }} <span class="badge b-grey">{{ $counts[$k] ?? 0 }}</span></a>@endforeach
  <a href="{{ route('outbound.index', ['stage' => 'shipped']) }}" class="{{ $stage === 'shipped' ? 'on' : '' }}">Terkirim</a>
  <a href="{{ route('outbound.index', ['stage' => 'delivered']) }}" class="{{ $stage === 'delivered' ? 'on' : '' }}">POD</a>
</div>
<form class="filters" method="get"><input type="hidden" name="stage" value="{{ $stage }}">
  <div class="f"><label>Cari</label><input type="search" name="q" value="{{ request('q') }}" placeholder="DO / SO / customer / outlet" data-autofocus></div>
  <div class="f"><label>Rute (shipping group)</label><select name="group" data-submit><option value="">Semua</option>@foreach($groups as $g)<option @selected(request('group') === $g)>{{ $g }}</option>@endforeach</select></div>
  <div class="f"><label>Kirim s.d.</label><input type="date" name="date" value="{{ request('date') }}"></div>
  <div class="f"><label>Jenis</label><select name="type" data-submit><option value="">Semua</option>@foreach(Wms::OUTBOUND_TYPES as $k => $l)<option value="{{ $k }}" @selected(request('type') === $k)>{{ $l }}</option>@endforeach</select></div>
  <label class="chk"><input type="checkbox" name="nojob" value="1" @checked(request('nojob')) data-submit> Belum ada picking job</label>
  <button class="btn">Tampilkan</button>
</form>
<form method="post" action="{{ route('outbound.job') }}">@csrf
<div class="card">
  <div class="card-h"><h3>{{ number_format($docs->total()) }} dokumen</h3><div class="sp"></div>
    <input type="text" name="driver" placeholder="Pengemudi (opsional)" style="width:150px"><input type="text" name="transporter" placeholder="Ekspedisi" style="width:120px">
    <label class="chk"><input type="checkbox" name="use_tote" value="1"> pakai tote</label>
    <button class="btn red sm">☰ Buat Picking Job dari terpilih</button></div>
  <div class="card-b flush tbl-wrap"><table class="t">
  <thead><tr><th><input type="checkbox" data-check-all=".pick"></th><th>Nomor</th><th>Ref. SO</th><th>Customer / outlet (ShipTo)</th><th>Rute</th><th>Kirim</th><th class="n">Baris</th><th>Reservasi</th><th>Tahap</th><th>Picking / loading</th></tr></thead>
  <tbody>@forelse($docs as $d)
    <tr><td>@if($d->isOpen())<input type="checkbox" class="pick" name="ids[]" value="{{ $d->id }}">@endif</td>
      <td><a href="{{ route('outbound.show', $d) }}"><b>{{ $d->number }}</b></a><br><small>{{ $d->typeLabel() }}</small></td><td>{{ $d->origin }}</td>
      <td>{{ $d->partner?->name }}@if($d->shipTo)<br><small>→ {{ $d->shipTo->name }}</small>@endif</td><td>{{ $d->shipping_group }}</td>
      <td>@d($d->scheduled_at){!! $d->priority && strtolower($d->priority) !== 'normal' ? '<br><span class="badge b-red">'.e($d->priority).'</span>' : '' !!}</td><td class="n">{{ $d->lines_count }}</td>
      <td>{!! $d->state === 'ready' ? '<span class="badge b-green">Lengkap</span>' : ($d->state === 'open' ? ($d->picking_job_id ? '<span class="badge b-red">Short</span>' : '<span class="badge b-grey">Belum</span>') : \App\Support\Ui::status($d->state)) !!}</td>
      <td>@status($d->stage)</td><td>@if($d->pickingJob)<a href="{{ route('jobs.show', $d->pickingJob) }}">{{ $d->pickingJob->number }}</a>@endif @if($d->loading)<br><a href="{{ route('loading.show', $d->loading) }}">{{ $d->loading->number }}</a>@endif</td></tr>
  @empty <tr><td colspan="10" class="empty">Tidak ada dokumen.</td></tr> @endforelse</tbody>
  </table></div>
</div>
</form>
{{ $docs->links() }}
@endsection
