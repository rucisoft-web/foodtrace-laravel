@extends('layouts.app')
@section('title', 'Zona & gudang')
@section('crumb') Master Data <small>/ Zona & gudang</small> @endsection
@section('content')
<div class="page-h"><div><h1>Zona & gudang</h1><p>Standar zona simpan (Frozen, Chilled, Dry) dan data gudang. Kode gudang SAP dipakai untuk mencocokkan file sinkron.</p></div></div>
<div class="grid g2">
  <div class="card line-navy"><div class="card-h"><h3>Standar zona</h3></div><div class="card-b">
    <form method="post" action="{{ route('zones.save') }}">@csrf
      <table class="t"><thead><tr><th>Zona</th><th>Nama</th><th>Kode</th><th>Umur simpan standar (hari)</th><th>Warna</th><th class="n">Bin</th></tr></thead><tbody>
      @foreach($zones as $z)<tr><td>@zone($z->zone)</td><td><input type="text" name="zones[{{ $z->id }}][name]" value="{{ $z->name }}" style="width:110px"></td><td><input type="text" name="zones[{{ $z->id }}][code]" value="{{ $z->code }}" style="width:50px"></td>
        <td><input type="number" name="zones[{{ $z->id }}][shelf_life_days]" value="{{ $z->shelf_life_days }}" style="width:90px"></td><td><input type="color" name="zones[{{ $z->id }}][color]" value="{{ $z->color }}"></td>
        <td class="n"><small>@foreach($stats[$z->zone] ?? [] as $s){{ \App\Support\Wms::AREAS[$s->area] ?? $s->area }}: {{ $s->n }}<br>@endforeach</small></td></tr>@endforeach
      </tbody></table>
      <p class="muted">Umur simpan standar dipakai bila item tidak punya umur simpan sendiri. Frozen ≤ -18 °C, chilled 0–4 °C, dry suhu ruang terkendali.</p>
      <button class="btn red">Simpan zona</button>
    </form>
  </div></div>
  <div class="card line-red"><div class="card-h"><h3>Gudang & perusahaan</h3></div><div class="card-b">
    <form method="post" action="{{ route('warehouse.save') }}">@csrf
      <div class="grid g2" style="gap:8px">
        <div class="f"><label>Kode gudang</label><input type="text" value="{{ $wh?->code }}" disabled><div class="hint">Prefiks nomor LPN.</div></div>
        <div class="f"><label>Kode gudang SAP</label><input type="text" name="sap_code" value="{{ $wh?->sap_code }}"></div>
        <div class="f span2"><label>Nama gudang</label><input type="text" name="name" value="{{ $wh?->name }}" required></div>
        <div class="f span2"><label>Alamat</label><input type="text" name="address" value="{{ $wh?->address }}"></div>
        <div class="f"><label>Nama perusahaan</label><input type="text" name="company_name" value="{{ \App\Models\Setting::get('company_name') }}"></div>
        <div class="f"><label>Kota</label><input type="text" name="company_city" value="{{ \App\Models\Setting::get('company_city') }}"></div>
        <div class="f"><label>Database SAP (CompanyDB)</label><input type="text" name="sap_company_db" value="{{ \App\Models\Setting::get('sap_company_db') }}"></div>
        <div class="f"><label>Mode posting SAP</label><select name="sap_mode"><option value="simulation">Simulasi</option><option value="service_layer" @selected(\App\Models\Setting::get('sap_mode') === 'service_layer')>Service Layer</option></select></div>
      </div>
      <button class="btn red">Simpan</button>
    </form>
  </div></div>
</div>
@endsection
