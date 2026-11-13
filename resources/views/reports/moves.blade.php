@extends('layouts.app')
@section('title', $title)
@section('crumb') Laporan <small>/ {{ $title }}</small> @endsection
@section('content')
@include('reports._head', ['search' => 'Referensi / item', 'dates' => true, 'sub' => number_format($sum->n).' baris · qty '.\App\Support\Wms::fmt($sum->q).' · nilai '.\App\Support\Wms::money($sum->v)])
<div class="card"><div class="card-b flush tbl-wrap"><table class="t">
  <thead><tr><th>Tanggal</th><th>Referensi</th><th>Item</th><th>Lot</th><th>Dari</th><th>Ke</th><th>LPN</th><th class="n">Qty</th><th>Partner</th><th>Operator</th></tr></thead>
  <tbody>@forelse($rows as $m)
    <tr><td>@dt($m->date)</td><td>@if($m->document_id)<a href="{{ $m->document->show() }}">{{ $m->reference }}</a>@else{{ $m->reference }}@endif</td>
      <td><b>{{ $m->item->code }}</b><br><small>{{ $m->item->name }}</small></td><td>{{ $m->lot?->name }}</td><td><small>{{ $m->fromName() }}</small></td><td><small>{{ $m->toName() }}</small></td>
      <td><small>{{ $m->lpn?->name }}{{ $m->resultLpn && $m->resultLpn->id !== $m->lpn_id ? ' → '.$m->resultLpn->name : '' }}</small></td>
      <td class="n">@qty($m->qty) {{ $m->uom }}</td><td><small>{{ $m->partner?->name }}</small></td><td><small>{{ $m->user?->name }}</small></td></tr>
  @empty <tr><td colspan="10" class="empty">Tidak ada pergerakan pada periode ini.</td></tr> @endforelse</tbody>
</table></div></div>
{{ $rows->links() }}
@endsection
