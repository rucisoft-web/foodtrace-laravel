@extends('layouts.app')
@section('title', $title)
@section('crumb') Laporan <small>/ {{ $title }}</small> @endsection
@section('content')
@include('reports._head', ['sub' => 'Item dengan stok di bawah stok minimum (diatur di Item Master). '.count($rows).' item.'])
<div class="card"><div class="card-b flush tbl-wrap"><table class="t">
  <thead><tr><th>Item</th><th>Group</th><th class="n">Stok</th><th class="n">Minimum</th><th class="n">Kurang</th><th>Cukup</th></tr></thead>
  <tbody>@forelse($rows as $x)
    <tr><td>@zone($x->zone) <a href="{{ route('items.edit', $x->id) }}"><b>{{ $x->code }}</b></a> {{ $x->name }}</td><td>{{ $x->item_group }}</td><td class="n">@qty($x->qty) {{ $x->uom }}</td><td class="n">@qty($x->min_stock)</td>
      <td class="n">@qty($x->min_stock - $x->qty)</td><td style="width:140px"><div class="progress"><i style="width:{{ min(100, $x->qty / $x->min_stock * 100) }}%"></i></div></td></tr>
  @empty <tr><td colspan="6" class="empty">Semua item di atas minimum.</td></tr> @endforelse</tbody>
</table></div></div>
@endsection
