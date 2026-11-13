@extends('layouts.app')
@section('title', $b->number)
@section('crumb') Outbound <small>/ <a href="{{ route('butcher.index', ['kind' => $b->kind]) }}">Butcher / Repack</a> / {{ $b->number }}</small> @endsection
@section('content')
<div class="page-h"><div><h1>{{ $b->number }} @status($b->state)</h1><p>Butcher {{ $b->kind === 'in' ? 'In (hasil)' : 'Out (bahan)' }} · @dt($b->date) · {{ $b->user?->name }}@if($b->ref) · ref {{ $b->ref }}@endif @if($b->source) · bahan <a href="{{ route('butcher.show', $b->source) }}">{{ $b->source->number }}</a>@endif</p></div>
  <div class="sp"></div>
  @if($b->state === 'draft')<form method="post" action="{{ route('butcher.cancel', $b) }}">@csrf<button class="btn ghost">Batalkan</button></form><form method="post" action="{{ route('butcher.done', $b) }}">@csrf<button class="btn red">✓ Selesaikan</button></form>@endif
  @if($b->kind === 'out' && $b->state === 'done')<a class="btn ghost" href="{{ route('butcher.create', ['kind' => 'in', 'source' => $b->id]) }}">+ Catat hasil (Butcher In)</a>@endif
  @if($b->kind === 'in' && $b->state === 'done')<a class="btn ghost" target="_blank" href="{{ route('lpn.labels', ['ids' => $b->lines->pluck('lpn_id')->filter()->implode(',')]) }}">⎙ Label LPN hasil</a>@endif
</div>
<div class="grid g4" style="margin-bottom:12px">
  <div class="kpi"><div class="l">Total berat</div><div class="v">{{ \App\Support\Wms::fmt($b->total_kg, 2) }} kg</div><div class="s">{{ $b->lines->count() }} baris</div></div>
  @if($b->state === 'done')<div class="kpi green"><div class="l">Yield</div><div class="v">{{ $b->yieldPct() }}%</div><div class="s">hasil ÷ bahan</div></div>@endif
</div>
<div class="card line-navy"><div class="card-b flush tbl-wrap"><table class="t">
  <thead><tr><th>Proses</th><th>Item</th><th>Lot</th><th>LPN</th><th>Lokasi</th><th class="n">Qty</th><th class="n">Kg</th><th>Grade / cutting</th><th>Expiry</th></tr></thead>
  <tbody>@foreach($b->lines as $l)<tr><td>{{ \App\Models\Butcher::TYPES[$l->process_type] ?? $l->process_type }}</td><td><b>{{ $l->item->code }}</b> {{ $l->item->name }}</td><td>{{ $l->lot?->name ?? $l->lot_name }}</td>
    <td>@if($l->lpn)<a href="{{ route('lpn.show', $l->lpn) }}">{{ $l->lpn->name }}</a>@endif</td><td><small>{{ $l->location?->full_name }}</small></td><td class="n">@qty($l->qty) {{ $l->item->uom->code }}</td><td class="n">{{ $l->qty_kg ? \App\Support\Wms::fmt($l->qty_kg, 2) : '' }}</td>
    <td>{{ $l->grade }} {{ $l->cutting_type }}</td><td>@d($l->expiry_date ?? $l->lot?->expiry_date)</td></tr>@endforeach</tbody>
</table></div></div>
@if($b->kind === 'out' && $b->results->isNotEmpty())
<div class="card line-green"><div class="card-h"><h3>Hasil (Butcher In)</h3></div><div class="card-b flush"><table class="t">@foreach($b->results as $x)<tr><td><a href="{{ route('butcher.show', $x) }}">{{ $x->number }}</a></td><td>@dt($x->date)</td><td class="n">{{ \App\Support\Wms::fmt($x->total_kg, 2) }} kg</td><td>@status($x->state)</td></tr>@endforeach</table></div></div>
@endif
@endsection
