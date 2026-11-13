@extends('layouts.app')
@section('title', 'Bin / Rak Master')
@section('crumb') Master Data <small>/ Bin / Rak Master</small> @endsection
@php use App\Support\Wms; $e = $edit; @endphp
@section('content')
<div class="page-h"><div><h1>Bin / Rak Master</h1><p>Struktur lokasi: gudang → ruang zona (FZ/CH/DR) → area (bulk, picking, karantina, dst.) → bin. Zona diturunkan dari induk; area menentukan status stok.</p></div>
  <div class="sp"></div><a class="btn ghost" target="_blank" href="{{ route('locations.labels', array_filter(['q' => request('q')])) }}">⎙ Label barcode lokasi</a></div>
<div class="grid g3">
  <div class="span2">
    <form class="filters" method="get">
      <div class="f"><label>Cari</label><input type="search" name="q" value="{{ request('q') }}" placeholder="nama / barcode"></div>
      <div class="f"><label>Zona</label><select name="zone" data-submit><option value="">Semua</option>@foreach(Wms::ZONES as $k => $l)<option value="{{ $k }}" @selected(request('zone') === $k)>{{ $l }}</option>@endforeach</select></div>
      <div class="f"><label>Area</label><select name="area" data-submit><option value="">Semua</option>@foreach(Wms::AREAS as $k => $l)<option value="{{ $k }}" @selected(request('area') === $k)>{{ $l }}</option>@endforeach</select></div>
      <button class="btn">Tampilkan</button>
    </form>
    <div class="card"><div class="card-h"><h3>{{ $rows->count() }} lokasi</h3></div><div class="card-b flush tbl-wrap" style="max-height:640px;overflow:auto"><table class="t">
      <thead><tr><th>Lokasi</th><th>Barcode</th><th>Zona</th><th>Area</th><th>Status stok</th><th>Bin</th><th class="n">Isi</th><th></th></tr></thead>
      <tbody>@foreach($rows as $l)
        @php $depth = substr_count($l->full_name, '/') - 1; @endphp
        <tr class="tree-{{ min(4, max(0, $depth)) }}"><td>{{ $depth > 0 ? '└ ' : '' }}<b>{{ $l->name }}</b><br><small class="muted">{{ $l->full_name }}</small></td><td class="mono">{{ $l->barcode }}</td><td>@zone($l->eff_zone)</td>
          <td><small>{{ $l->areaLabel() }}</small></td><td>@if($l->area)@status($l->status())@endif</td><td>{{ $l->is_bin ? '✓' : '' }}</td>
          <td class="n">@if($l->live)<a href="{{ route('reports.show', ['by-location', 'q' => $l->barcode ?: $l->full_name]) }}">{{ $l->live }}</a>@endif</td>
          <td><a class="btn ghost sm" href="{{ route('locations.index', array_merge(request()->query(), ['edit' => $l->id])) }}">Ubah</a></td></tr>
      @endforeach</tbody></table></div></div>
  </div>
  <div class="card line-red"><div class="card-h"><h3>{{ $e ? 'Ubah '.$e->name : 'Tambah lokasi' }}</h3>@if($e)<div class="sp"></div><a href="{{ route('locations.index', request()->except('edit')) }}">batal</a>@endif</div><div class="card-b">
    <form method="post" action="{{ route('locations.save') }}">@csrf<input type="hidden" name="id" value="{{ $e?->id }}">
      <div class="f"><label>Induk</label><select name="parent_id"><option value="">— (akar gudang)</option>@foreach($parents as $p)<option value="{{ $p->id }}" @selected(old('parent_id', $e?->parent_id) == $p->id)>{{ $p->full_name }}</option>@endforeach</select></div>
      <div class="grid g2" style="gap:8px">
        <div class="f"><label>Nama *</label><input type="text" name="name" value="{{ old('name', $e?->name) }}" required placeholder="mis. A07-1"></div>
        <div class="f"><label>Barcode</label><input type="text" name="barcode" value="{{ old('barcode', $e?->barcode) }}" placeholder="SKI-FZ-A07-1"></div>
        <div class="f"><label>Zona</label><select name="zone"><option value="">ikut induk</option>@foreach(Wms::ZONES as $k => $l)<option value="{{ $k }}" @selected(old('zone', $e?->zone) === $k)>{{ $l }}</option>@endforeach</select></div>
        <div class="f"><label>Area</label><select name="area"><option value="">—</option>@foreach(Wms::AREAS as $k => $l)<option value="{{ $k }}" @selected(old('area', $e?->area) === $k)>{{ $l }}</option>@endforeach</select></div>
        <div class="f"><label>Maks. palet</label><input type="number" name="max_pallets" value="{{ old('max_pallets', $e?->max_pallets) }}" min="0"></div>
        <div class="f"><label>Maks. berat (kg)</label><input type="text" name="max_weight" value="{{ old('max_weight', $e?->max_weight) }}"></div>
      </div>
      <label class="chk"><input type="checkbox" name="is_bin" value="1" @checked(old('is_bin', $e?->is_bin ?? true))> Bin (bisa diisi stok)</label>
      <label class="chk"><input type="checkbox" name="active" value="1" @checked(old('active', $e?->active ?? true))> Aktif</label>
      <button class="btn red">Simpan lokasi</button>
    </form>
    <hr><p class="muted" style="margin:0">Area: <b>rcv</b> dock = Receiving, <b>blk/pck</b> = Available, <b>qc/rtn</b> = Hold, <b>dmg/wst</b> = Damage, <b>out</b> = Picked, <b>prd</b> = ruang potong (tidak dihitung stok).</p>
  </div></div>
</div>
@endsection
