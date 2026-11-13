@extends('layouts.app')
@section('title', 'Item Master')
@section('crumb') Master Data <small>/ Item Master</small> @endsection
@section('content')
<div class="page-h"><div><h1>Item Master</h1><p>Item dari SAP B1 (OITM): satuan persediaan SAP tanpa konversi, brand SAP, zona simpan dan aturan food safety per item.</p></div>
  <div class="sp"></div><a class="btn ghost" href="{{ request()->fullUrlWithQuery(['export' => 'csv']) }}">⇩ Ekspor CSV</a><a class="btn red" href="{{ route('items.edit', 'new') }}">+ Item baru</a></div>
<form class="filters" method="get">
  <div class="f"><label>Cari</label><input type="search" name="q" value="{{ request('q') }}" placeholder="ItemCode / nama / barcode" data-autofocus></div>
  <div class="f"><label>Zona</label><select name="zone" data-submit><option value="">Semua</option>@foreach(\App\Support\Wms::ZONES as $k => $l)<option value="{{ $k }}" @selected(request('zone') === $k)>{{ $l }}</option>@endforeach</select></div>
  <div class="f"><label>Group</label><select name="group" data-submit><option value="">Semua</option>@foreach($groups as $g)<option @selected(request('group') === $g)>{{ $g }}</option>@endforeach</select></div>
  <div class="f"><label>Brand</label><select name="brand" data-submit><option value="">Semua</option>@foreach($brands as $b)<option value="{{ $b->id }}" @selected(request('brand') == $b->id)>{{ $b->label() }}</option>@endforeach</select></div>
  <div class="f"><label>Tanda</label><select name="flag" data-submit><option value="">—</option><option value="cw" @selected(request('flag') === 'cw')>Berat aktual</option><option value="qc" @selected(request('flag') === 'qc')>Auto karantina</option><option value="inactive" @selected(request('flag') === 'inactive')>Nonaktif</option></select></div>
  <button class="btn">Tampilkan</button>
</form>
<div class="card"><div class="card-h"><h3>{{ number_format($rows->total()) }} item</h3></div><div class="card-b flush tbl-wrap"><table class="t">
  <thead><tr><th>ItemCode</th><th>Nama</th><th>Zona</th><th>Group</th><th>Brand</th><th>Satuan</th><th class="n">Umur simpan</th><th class="n">Min. terima</th><th class="n">Min stok</th><th>Aturan</th><th class="n">Harga pokok</th></tr></thead>
  <tbody>@forelse($rows as $i)
    <tr><td><a href="{{ route('items.edit', $i) }}"><b>{{ $i->code }}</b></a></td><td>{{ $i->name }}</td><td>@zone($i->zone)</td><td><small>{{ $i->item_group }}</small></td><td><small>{{ $i->brand?->label() }}</small></td><td>{{ $i->uom->code }}</td>
      <td class="n">{{ $i->shelf_life_days ?: '' }}</td><td class="n">{{ $i->min_receive_life_pct ? $i->min_receive_life_pct.'%' : '' }}</td><td class="n">{{ $i->min_stock > 0 ? \App\Support\Wms::fmt($i->min_stock) : '' }}</td>
      <td>@if($i->catch_weight)<span class="badge b-brown">berat aktual</span>@endif @if($i->auto_quarantine)<span class="badge b-amber">karantina</span>@endif @if($i->picking_strategy === 'fifo')<span class="badge b-grey">FIFO</span>@endif</td>
      <td class="n">{{ \App\Support\Wms::money($i->cost) }}</td></tr>
  @empty <tr><td colspan="11" class="empty">Tidak ada item.</td></tr> @endforelse</tbody>
</table></div></div>
{{ $rows->links() }}
@endsection
