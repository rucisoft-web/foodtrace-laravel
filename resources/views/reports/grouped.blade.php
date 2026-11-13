@extends('layouts.app')
@section('title', $title)
@section('crumb') Laporan <small>/ {{ $title }}</small> @endsection
@section('content')
@include('reports._head', ['search' => $groupBy === 'location' ? 'Lokasi (barcode / nama)' : 'Item code / nama', 'placeholder' => $groupBy === 'location' ? 'mis. SKI-FZ-A01 atau FZ/BLK' : 'mis. HFR02FRC0001 atau BUTTER',
  'sub' => number_format($total).' baris stok · nilai '.\App\Support\Wms::money($value)])
@if($total > count($rows))<div class="alert warn">Menampilkan 3.000 baris pertama dari {{ number_format($total) }}. Persempit pencarian atau ekspor CSV.</div>@endif
<div class="card"><div class="card-b flush tbl-wrap">
<table class="t">
  <thead><tr>@if($groupBy === 'location')<th>Item</th>@else<th>Lokasi</th>@endif<th>Lot</th><th>Expiry</th><th>LPN</th><th>Status</th><th class="n">Qty</th><th class="n">Nilai</th></tr></thead>
  <tbody>
  @foreach($rows->groupBy($groupBy === 'location' ? 'location_id' : 'item_id') as $g)
    @php $f = $g->first(); @endphp
    <tr class="grp"><td colspan="5">
      @if($groupBy === 'location') ⌖ {{ $f->location->full_name }} <small class="muted">{{ $f->location->barcode }} · {{ $f->location->areaLabel() }}</small> @zone($f->location->eff_zone)
      @else @zone($f->item->zone) {{ $f->item->code }} · {{ $f->item->name }} <small class="muted">{{ $f->item->brand?->label() }}</small> @endif
      </td><td class="n">@qty($g->sum('qty')) {{ $f->item->uom->code }}</td><td class="n">{{ \App\Support\Wms::money($g->sum(fn ($x) => $x->qty * $x->item->cost)) }}</td></tr>
    @foreach($g as $x)
      <tr><td>@if($groupBy === 'location')@zone($x->item->zone) <b>{{ $x->item->code }}</b> {{ $x->item->name }}@else<a href="{{ route('reports.show', ['by-location', 'q' => $x->location->barcode]) }}">{{ $x->location->full_name }}</a>@endif</td>
        <td>{{ $x->lot?->name }}</td><td>@d($x->lot?->expiry_date)</td><td>@if($x->lpn)<a href="{{ route('lpn.show', $x->lpn) }}">{{ $x->lpn->name }}</a>@endif</td>
        <td>@status($x->status())</td><td class="n">@qty($x->qty) {{ $x->item->uom->code }}</td><td class="n">{{ \App\Support\Wms::money($x->qty * $x->item->cost) }}</td></tr>
    @endforeach
  @endforeach
  @if(!count($rows))<tr><td colspan="7" class="empty">Tidak ada stok.</td></tr>@endif
  </tbody>
</table></div></div>
@endsection
