@extends('layouts.app')
@section('title', 'Satuan SAP')
@section('crumb') Master Data <small>/ Satuan SAP</small> @endsection
@section('content')
<div class="page-h"><div><h1>Satuan SAP</h1><p>Satuan persediaan SAP (InvntryUom). FoodTrace memakai satuan yang sama dengan SAP tanpa faktor konversi, sehingga qty di WMS = qty di SAP.</p></div></div>
<div class="grid g3">
  <div class="card line-navy span2"><div class="card-b flush"><table class="t"><thead><tr><th>Kode</th><th>Nama</th><th>Satuan berat</th><th class="n">Dipakai item</th><th></th></tr></thead><tbody>
    @foreach($rows as $u)<tr><td><b>{{ $u->code }}</b></td><td>{{ $u->name }}</td><td>{{ $u->is_weight ? '✓ (berat aktual dari timbangan / barcode)' : '' }}</td><td class="n">{{ $used[$u->id] ?? 0 }}</td>
      <td><a class="btn ghost sm" href="#" onclick="let f=document.getElementById('uf');f.id.value='{{ $u->id }}';f.code.value=@js($u->code);f.name.value=@js($u->name);f.is_weight.checked={{ $u->is_weight ? 'true' : 'false' }};return false">Ubah</a></td></tr>@endforeach
  </tbody></table></div></div>
  <div class="card line-red"><div class="card-h"><h3>Tambah / ubah satuan</h3></div><div class="card-b">
    <form method="post" action="{{ route('uoms.save') }}" id="uf">@csrf<input type="hidden" name="id">
      <div class="f"><label>Kode SAP</label><input type="text" name="code" required></div><div class="f"><label>Nama</label><input type="text" name="name" required></div>
      <label class="chk"><input type="checkbox" name="is_weight" value="1"> Satuan berat (KG / GRAM)</label><button class="btn red">Simpan</button></form></div></div>
</div>
@endsection
