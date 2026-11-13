@extends('layouts.app')
@section('title', $title)
@section('crumb') Laporan <small>/ {{ $title }}</small> @endsection
@section('content')
@include('reports._head', ['sub' => 'Baris inventory check dengan selisih qty atau expiry.'])
<div class="card"><div class="card-b flush tbl-wrap"><table class="t">
  <thead><tr><th>Dokumen</th><th>Status</th><th>Item</th><th>Lot / LPN</th><th>Lokasi</th><th class="n">Sistem</th><th class="n">Fisik</th><th class="n">Selisih</th><th>Expiry</th><th>Checker / approver</th></tr></thead>
  <tbody>@forelse($rows as $l)
    <tr><td><a href="{{ route('ic.show', $l->check) }}">{{ $l->check->number }}</a><br><small>@d($l->check->date)</small></td><td>@status($l->check->state)</td><td><b>{{ $l->item->code }}</b><br><small>{{ $l->item->name }}</small></td>
      <td>{{ $l->lot?->name }}<br><small>{{ $l->lpn?->name }}</small></td><td><small>{{ $l->location->full_name }}</small></td><td class="n">@qty($l->qty_before)</td><td class="n">@qty($l->qty_after)</td>
      <td class="n"><b class="{{ $l->difference() < 0 ? 'text-red' : '' }}">@qty($l->difference())</b></td><td><small>@d($l->expiry_before){{ $l->expiryChanged() ? ' → ' : '' }}@if($l->expiryChanged())@d($l->expiry_after)@endif</small></td>
      <td><small>{{ $l->check->user?->name }} / {{ $l->check->approver?->name ?? '-' }}</small></td></tr>
  @empty <tr><td colspan="10" class="empty">Tidak ada selisih.</td></tr> @endforelse</tbody>
</table></div></div>
@endsection
