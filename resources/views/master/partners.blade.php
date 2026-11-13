@extends('layouts.app')
@php $src = $kind === 'source'; @endphp
@section('title', $src ? 'Source Master' : 'Destination Master')
@section('crumb') Master Data <small>/ {{ $src ? 'Source Master' : 'Destination Master' }}</small> @endsection
@section('content')
<div class="page-h"><div><h1>{{ $src ? 'Source Master (pemasok)' : 'Destination Master (customer & ShipTo)' }}</h1>
  <p>{{ $src ? 'Pemasok dan pemilik titipan: toleransi over receiving per pemasok, kode owner untuk stok titipan.' : 'Customer SAP dengan banyak alamat kirim (ShipTo), rute pengiriman (shipping group) dan batas minimum sisa umur simpan saat dikirim.' }}</p></div>
  <div class="sp"></div><a class="btn red" href="{{ route('partners.edit', ['partner' => 'new', 'kind' => $kind]) }}">+ {{ $src ? 'Pemasok' : 'Customer' }} baru</a></div>
<form class="filters" method="get">
  <div class="f"><label>Cari</label><input type="search" name="q" value="{{ request('q') }}" placeholder="kode / nama / kota" data-autofocus></div>
  @if(!$src)<div class="f"><label>Rute</label><select name="group" data-submit><option value="">Semua</option>@foreach($groups as $g)<option @selected(request('group') === $g)>{{ $g }}</option>@endforeach</select></div>@endif
  <button class="btn">Tampilkan</button>
</form>
<div class="card"><div class="card-b flush tbl-wrap"><table class="t">
  <thead><tr><th>Kode SAP</th><th>Nama</th><th>Kota</th>@if($src)<th>Kode owner</th><th class="n">Toleransi over receive</th>@else<th>Rute</th><th class="n">Min. umur simpan</th><th class="n">ShipTo</th>@endif<th>Juga</th></tr></thead>
  <tbody>@forelse($rows as $p)
    <tr><td><a href="{{ route('partners.edit', $p) }}"><b>{{ $p->code }}</b></a></td><td>{{ $p->name }}</td><td>{{ $p->city }}</td>
      @if($src)<td>{{ $p->owner_code }}</td><td class="n">{{ $p->over_receive_pct ? \App\Support\Wms::fmt($p->over_receive_pct, 2).'%' : '' }}</td>
      @else<td>{{ $p->shipping_group }}</td><td class="n">{{ $p->min_shelf_life_pct ? $p->min_shelf_life_pct.'%' : '' }}</td><td class="n">{{ $p->ship_tos_count ?: '' }}</td>@endif
      <td>@if($src && $p->is_destination)<span class="badge b-grey">customer</span>@elseif(!$src && $p->is_source)<span class="badge b-grey">pemasok</span>@endif</td></tr>
  @empty <tr><td colspan="7" class="empty">Tidak ada data.</td></tr> @endforelse</tbody>
</table></div></div>
{{ $rows->links() }}
@endsection
