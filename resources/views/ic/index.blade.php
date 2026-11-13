@extends('layouts.app')
@section('title', 'Inventory Check')
@section('crumb') Inventory <small>/ Inventory Check</small> @endsection
@section('content')
<div class="page-h"><div><h1>Inventory Check</h1><p>Cek fisik per LPN atau lokasi: qty dan expiry. Selisih baru berlaku setelah disetujui Kepala gudang / QA.</p></div>
  <div class="sp"></div><a class="btn ghost" href="{{ route('hht.ic') }}">▣ Cek dengan HHT</a><a class="btn red" href="{{ route('ic.create') }}">+ Inventory check baru</a></div>
<div class="tabs">
  <a href="{{ route('ic.index') }}" class="{{ request('state') ? '' : 'on' }}">Semua</a>
  @foreach(\App\Models\InventoryCheck::STATES as $k => $l)<a href="{{ route('ic.index', ['state' => $k]) }}" class="{{ request('state') === $k ? 'on' : '' }}">{{ $l }} <span class="badge {{ $k === 'submitted' ? 'b-amber' : 'b-grey' }}">{{ $counts[$k] ?? 0 }}</span></a>@endforeach
</div>
<div class="card"><div class="card-b flush tbl-wrap"><table class="t">
  <thead><tr><th>Nomor</th><th>Tanggal</th><th>Objek</th><th class="n">Baris</th><th class="n">Selisih</th><th>Checker</th><th>Approver</th><th>Status</th></tr></thead>
  <tbody>@forelse($rows as $c)
    <tr><td><a href="{{ route('ic.show', $c) }}"><b>{{ $c->number }}</b></a></td><td>@dt($c->date)</td><td>{{ $c->title() }}</td><td class="n">{{ $c->lines->count() }}</td>
      <td class="n">{!! $c->differenceCount() ? '<span class="badge b-red">'.$c->differenceCount().'</span>' : '0' !!}</td><td>{{ $c->user?->name }}</td><td>{{ $c->approver?->name }}</td><td>@status($c->state)</td></tr>
  @empty <tr><td colspan="8" class="empty">Belum ada inventory check.</td></tr> @endforelse</tbody>
</table></div></div>
{{ $rows->links() }}
@endsection
