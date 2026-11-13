@extends('layouts.app')
@section('title', 'Butcher / Repack')
@section('crumb') Outbound <small>/ Butcher / Repack</small> @endsection
@section('content')
<div class="page-h"><div><h1>Butcher / Repack</h1><p>Butcher Out: bahan (LPN) keluar ke ruang potong. Butcher In: hasil potong / repack masuk sebagai lot dan LPN baru. Yield = berat hasil ÷ berat bahan.</p></div>
  <div class="sp"></div><a class="btn ghost" href="{{ route('hht.butcher') }}">▣ HHT</a><a class="btn red" href="{{ route('butcher.create', ['kind' => $kind]) }}">+ Butcher {{ $kind === 'in' ? 'In' : 'Out' }} baru</a></div>
<div class="tabs"><a href="{{ route('butcher.index', ['kind' => 'out']) }}" class="{{ $kind === 'out' ? 'on' : '' }}">Butcher Out (bahan)</a><a href="{{ route('butcher.index', ['kind' => 'in']) }}" class="{{ $kind === 'in' ? 'on' : '' }}">Butcher In (hasil)</a></div>
<div class="card"><div class="card-b flush tbl-wrap"><table class="t">
  <thead><tr><th>Nomor</th><th>Tanggal</th><th>Ref.</th>@if($kind === 'in')<th>Bahan (BO)</th>@endif<th>Item</th><th class="n">Total kg</th>@if($kind === 'out')<th class="n">Yield</th>@endif<th>Operator</th><th>Status</th></tr></thead>
  <tbody>@forelse($rows as $b)
    <tr><td><a href="{{ route('butcher.show', $b) }}"><b>{{ $b->number }}</b></a></td><td>@dt($b->date)</td><td>{{ $b->ref }}</td>@if($kind === 'in')<td>{{ $b->source?->number }}</td>@endif
      <td><small>{{ $b->lines->map(fn ($l) => $l->item->code)->unique()->implode(', ') }}</small></td><td class="n">{{ \App\Support\Wms::fmt($b->total_kg, 2) }}</td>
      @if($kind === 'out')<td class="n">{{ $b->state === 'done' ? $b->yieldPct().'%' : '' }}</td>@endif<td>{{ $b->user?->name }}</td>
      <td>{!! \App\Support\Ui::status(['draft' => 'draft', 'done' => 'done', 'cancel' => 'cancel'][$b->state]) !!}</td></tr>
  @empty <tr><td colspan="9" class="empty">Belum ada.</td></tr> @endforelse</tbody>
</table></div></div>
{{ $rows->links() }}
@endsection
