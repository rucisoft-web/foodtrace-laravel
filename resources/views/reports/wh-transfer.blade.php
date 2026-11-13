@extends('layouts.app')
@section('title', $title)
@section('crumb') Laporan <small>/ {{ $title }}</small> @endsection
@section('content')
<div class="page-h"><div><h1>{{ $title }}</h1><p>Dokumen inbound/outbound berjenis Warehouse Transfer (antar gudang SAP).</p></div></div>
<div class="card"><div class="card-b flush"><table class="t">
  <thead><tr><th>Nomor</th><th>Arah</th><th>Ref. SAP</th><th>Partner</th><th>Tanggal</th><th>Status</th><th class="n">Baris</th></tr></thead>
  <tbody>@forelse($rows as $d)
    <tr><td><a href="{{ $d->show() }}">{{ $d->number }}</a></td><td>{{ ucfirst($d->type) }}</td><td>{{ $d->origin }}</td><td>{{ $d->partner?->name }}</td><td>@d($d->scheduled_at)</td><td>@status($d->state)</td><td class="n">{{ $d->lines->count() }}</td></tr>
  @empty <tr><td colspan="7" class="empty">Belum ada warehouse transfer. Transfer antar gudang dari SAP akan tampil di sini.</td></tr> @endforelse</tbody>
</table></div></div>
{{ $rows->links() }}
@endsection
