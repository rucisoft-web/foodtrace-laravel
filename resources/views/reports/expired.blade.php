@extends('layouts.app')
@section('title', $title)
@section('crumb') Laporan <small>/ {{ $title }}</small> @endsection
@section('content')
@component('reports._head', ['title' => $title, 'sub' => 'Periode mengikuti URS Largo: 1, 2, 3, 4, 6 bulan. Default: sudah kedaluwarsa s.d. 4 bulan.'])
  <div class="f"><label>Periode</label><select name="bucket" data-submit><option value="">≤ 4 bulan + kedaluwarsa</option>@foreach(\App\Services\DashboardService::BUCKETS as $b)<option value="{{ $b[0] }}" @selected(request('bucket') === $b[0])>{{ $b[1] }}</option>@endforeach</select></div>
@endcomponent
<div class="grid g4" style="margin-bottom:12px">
  @foreach(array_slice(\App\Services\DashboardService::BUCKETS, 0, 4) as $b)
    @php $g = $rows->where('bucket', $b[0]); @endphp
    <div class="kpi {{ $b[0] === 'expired' ? 'red' : 'amber' }}"><div class="l">{{ $b[1] }}</div><div class="v">{{ \App\Support\Wms::money($g->sum(fn ($x) => $x->qty * $x->item->cost)) }}</div><div class="s">{{ $g->count() }} baris stok</div></div>
  @endforeach
</div>
<div class="card"><div class="card-b flush tbl-wrap"><table class="t">
  <thead><tr><th>Item</th><th>Lot</th><th>Expiry</th><th class="n">Sisa</th><th>Lokasi</th><th>LPN</th><th>Status</th><th class="n">Qty</th><th class="n">Nilai</th></tr></thead>
  <tbody>@forelse($rows as $x)
    <tr class="{{ $x->days < 0 ? 'bad' : ($x->days <= 30 ? 'warn' : '') }}"><td>@zone($x->item->zone) <b>{{ $x->item->code }}</b> {{ $x->item->name }}</td><td>{{ $x->lot->name }}</td><td>@d($x->lot->expiry_date)</td>
      <td class="n">{{ $x->days < 0 ? 'lewat '.abs($x->days) : $x->days }} hr</td><td>{{ $x->location->full_name }}</td><td>{{ $x->lpn?->name }}</td><td>@status($x->status())</td>
      <td class="n">@qty($x->qty) {{ $x->item->uom->code }}</td><td class="n">{{ \App\Support\Wms::money($x->qty * $x->item->cost) }}</td></tr>
  @empty <tr><td colspan="9" class="empty">Tidak ada stok pada periode ini.</td></tr> @endforelse</tbody>
</table></div></div>
@endsection
