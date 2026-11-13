@extends('layouts.app')
@section('title', 'Brand SAP')
@section('crumb') Master Data <small>/ Brand SAP</small> @endsection
@section('content')
<div class="page-h"><div><h1>Brand SAP</h1><p>Daftar brand dari field U_Brand SAP B1, mis. "LAI - LAITA". Dicetak di label dan dipakai di laporan.</p></div></div>
<div class="grid g3">
  <div class="card line-navy span2"><div class="card-h"><h3>{{ $rows->count() }} brand</h3><div class="sp"></div><form method="get"><input type="search" name="q" value="{{ request('q') }}" placeholder="cari"></form></div>
    <div class="card-b flush tbl-wrap"><table class="t"><thead><tr><th>Kode</th><th>Nama</th><th class="n">Item</th><th>Aktif</th><th></th></tr></thead><tbody>
    @foreach($rows as $b)<tr><td><b>{{ $b->code }}</b></td><td>{{ $b->name }}</td><td class="n"><a href="{{ route('items.index', ['brand' => $b->id]) }}">{{ $b->items_count }}</a></td><td>{{ $b->active ? '✓' : '' }}</td>
      <td><a class="btn ghost sm" href="#f" onclick="let f=document.getElementById('bf');f.id.value='{{ $b->id }}';f.code.value=@js($b->code);f.name.value=@js($b->name);f.active.checked={{ $b->active ? 'true' : 'false' }};f.code.focus();return false">Ubah</a></td></tr>@endforeach
    </tbody></table></div></div>
  <div class="card line-red"><div class="card-h"><h3>Tambah / ubah brand</h3></div><div class="card-b">
    <form method="post" action="{{ route('brands.save') }}" id="bf">@csrf<input type="hidden" name="id">
      <div class="f"><label>Kode</label><input type="text" name="code" required></div><div class="f"><label>Nama</label><input type="text" name="name" required></div>
      <label class="chk"><input type="checkbox" name="active" value="1" checked> Aktif</label><button class="btn red">Simpan</button></form></div></div>
</div>
@endsection
