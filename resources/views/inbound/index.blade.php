@extends('layouts.app')
@section('title', 'Inbound Document')
@section('crumb') Inbound <small>/ Inbound Document</small> @endsection
@section('content')
<div class="page-h">
  <div><h1>Inbound Document</h1><p>Purchase Order dari SAP B1 (POR), titipan, retur dan warehouse transfer yang menunggu diterima.</p></div>
  <div class="sp"></div>
  <a class="btn ghost" href="{{ route('sap.sync') }}">⇆ Dokumen masuk SAP</a>
</div>
<div class="tabs">
  <a href="{{ route('inbound.index') }}" class="{{ $state === 'open' ? 'on' : '' }}">Menunggu <span class="badge b-navy">{{ (int) $counts->o }}</span></a>
  <a href="{{ route('inbound.index', ['state' => 'done']) }}" class="{{ $state === 'done' ? 'on' : '' }}">Selesai <span class="badge b-grey">{{ (int) $counts->d }}</span></a>
  <a href="{{ route('inbound.index', ['state' => 'cancel']) }}" class="{{ $state === 'cancel' ? 'on' : '' }}">Batal</a>
  <a href="{{ route('inbound.index', ['state' => 'all']) }}" class="{{ $state === 'all' ? 'on' : '' }}">Semua</a>
</div>
<form class="filters" method="get">
  <input type="hidden" name="state" value="{{ $state }}">
  <div class="f"><label>Cari</label><input type="search" name="q" value="{{ request('q') }}" placeholder="No. PO / RCV / kontainer / pemasok" data-autofocus></div>
  <div class="f"><label>Jenis</label><select name="type" data-submit><option value="">Semua</option>@foreach(\App\Support\Wms::INBOUND_TYPES as $k => $l)<option value="{{ $k }}" @selected(request('type') === $k)>{{ $l }}</option>@endforeach</select></div>
  <button class="btn">Tampilkan</button>
</form>
<div class="card"><div class="card-b flush tbl-wrap"><table class="t">
  <thead><tr><th>Nomor</th><th>Ref. SAP</th><th>Jenis</th><th>Pemasok</th><th>Owner</th><th>Jadwal</th><th>Kontainer</th><th>Dock</th><th class="n">Baris</th><th>Status</th></tr></thead>
  <tbody>@forelse($docs as $d)
    <tr><td><a href="{{ route('inbound.show', $d) }}"><b>{{ $d->number }}</b></a></td><td>{{ $d->origin }}</td><td>{{ $d->typeLabel() }}</td>
      <td>{{ $d->partner?->name }}</td><td><small>{{ $d->owner?->name ?? 'Milik sendiri' }}</small></td><td>@d($d->scheduled_at)</td>
      <td>{{ $d->container_no }}</td><td>{{ $d->dock?->name }}</td><td class="n">{{ $d->lines_count }}</td><td>@status($d->state)</td></tr>
  @empty <tr><td colspan="10" class="empty">Tidak ada dokumen.</td></tr> @endforelse</tbody>
</table></div></div>
{{ $docs->links() }}
@endsection
