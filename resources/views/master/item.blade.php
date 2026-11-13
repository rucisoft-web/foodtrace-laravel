@extends('layouts.app')
@section('title', $item->exists ? $item->code : 'Item baru')
@section('crumb') Master Data <small>/ <a href="{{ route('items.index') }}">Item Master</a> / {{ $item->code ?: 'Baru' }}</small> @endsection
@section('content')
<div class="page-h"><div><h1>{{ $item->exists ? $item->code.' · '.$item->name : 'Item baru' }}</h1>@if($item->exists)<p>Stok fisik: <b>@qty($onHand) {{ $item->uom?->code }}</b> · <a href="{{ route('reports.show', ['by-item', 'q' => $item->code]) }}">Lokasi per item</a> · <a href="{{ route('stock.card', ['item' => $item->code]) }}">Kartu stok</a></p>@endif</div></div>
<form method="post" action="{{ route('items.save', $item->exists ? $item : null) }}">@csrf
<div class="grid g3">
  <div class="card line-navy span2"><div class="card-h"><h3>Data SAP</h3></div><div class="card-b grid g3">
    <div class="f"><label>ItemCode SAP *</label><input type="text" name="code" value="{{ old('code', $item->code) }}" required></div>
    <div class="f span2"><label>Nama *</label><input type="text" name="name" value="{{ old('name', $item->name) }}" required></div>
    <div class="f"><label>Barcode</label><input type="text" name="barcode" value="{{ old('barcode', $item->barcode) }}"></div>
    <div class="f"><label>Item group</label><input type="text" name="item_group" value="{{ old('item_group', $item->item_group) }}"></div>
    <div class="f"><label>Sub group</label><input type="text" name="sub_group" value="{{ old('sub_group', $item->sub_group) }}"></div>
    <div class="f"><label>Brand SAP</label><select name="brand_id"><option value="">—</option>@foreach($brands as $b)<option value="{{ $b->id }}" @selected(old('brand_id', $item->brand_id) == $b->id)>{{ $b->label() }}</option>@endforeach</select></div>
    <div class="f"><label>Satuan persediaan SAP *</label><select name="uom_id" required>@foreach($uoms as $u)<option value="{{ $u->id }}" @selected(old('uom_id', $item->uom_id) == $u->id)>{{ $u->code }} · {{ $u->name }}</option>@endforeach</select><div class="hint">Tanpa konversi antarsatuan, sama dengan SAP.</div></div>
    <div class="f"><label>Negara asal</label><input type="text" name="country" value="{{ old('country', $item->country) }}"></div>
    <div class="f"><label>Harga pokok (Rp)</label><input type="text" name="cost" value="{{ old('cost', $item->cost) }}" inputmode="decimal"></div>
    <div class="f"><label>Berat per satuan (kg)</label><input type="text" name="weight_kg" value="{{ old('weight_kg', $item->weight_kg) }}" inputmode="decimal"><div class="hint">Untuk total berat muatan.</div></div>
    <label class="chk"><input type="checkbox" name="active" value="1" @checked(old('active', $item->active ?? true))> Aktif</label>
  </div></div>
  <div class="card line-red"><div class="card-h"><h3>Gudang & food safety</h3></div><div class="card-b">
    <div class="f"><label>Zona simpan *</label><select name="zone" required>@foreach(\App\Support\Wms::ZONES as $k => $l)<option value="{{ $k }}" @selected(old('zone', $item->zone) === $k)>{{ $l }}</option>@endforeach</select></div>
    <div class="grid g2" style="gap:8px">
      <div class="f"><label>Umur simpan (hari)</label><input type="number" name="shelf_life_days" value="{{ old('shelf_life_days', $item->shelf_life_days) }}" min="0"><div class="hint">0 = standar zona</div></div>
      <div class="f"><label>Min. sisa umur saat terima (%)</label><input type="number" name="min_receive_life_pct" value="{{ old('min_receive_life_pct', $item->min_receive_life_pct) }}" min="0" max="100"></div>
      <div class="f"><label>Strategi picking</label><select name="picking_strategy"><option value="fefo">FEFO</option><option value="fifo" @selected(old('picking_strategy', $item->picking_strategy) === 'fifo')>FIFO</option></select></div>
      <div class="f"><label>Min stok</label><input type="text" name="min_stock" value="{{ old('min_stock', $item->min_stock) }}" inputmode="decimal"></div>
      <div class="f"><label>Unit per palet</label><input type="text" name="units_per_pallet" value="{{ old('units_per_pallet', $item->units_per_pallet) }}" inputmode="decimal"></div>
      <div class="f"><label>Hari karantina</label><input type="number" name="quarantine_days" value="{{ old('quarantine_days', $item->quarantine_days ?? 7) }}" min="0"></div>
    </div>
    <label class="chk"><input type="checkbox" name="catch_weight" value="1" @checked(old('catch_weight', $item->catch_weight))> Berat aktual (catch weight): scan per karton</label>
    <label class="chk"><input type="checkbox" name="auto_quarantine" value="1" @checked(old('auto_quarantine', $item->auto_quarantine))> Auto karantina saat diterima</label>
    <label class="chk"><input type="checkbox" name="auto_release" value="1" @checked(old('auto_release', $item->auto_release))> Auto release setelah hari karantina</label>
    <label class="chk"><input type="checkbox" name="multi_qty" value="1" @checked(old('multi_qty', $item->multi_qty))> Boleh multi qty per scan</label>
    <div class="f" style="margin-top:8px"><label>Template barcode karton</label>@foreach($templates as $t)<label class="chk"><input type="checkbox" name="templates[]" value="{{ $t->id }}" @checked($item->barcodeTemplates->contains($t))> {{ $t->name }}</label>@endforeach</div>
  </div></div>
</div>
<button class="btn red lg">Simpan item</button>
</form>
@endsection
