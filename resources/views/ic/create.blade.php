@extends('layouts.app')
@section('title', 'Inventory check baru')
@section('crumb') Inventory <small>/ <a href="{{ route('ic.index') }}">Inventory Check</a> / Baru</small> @endsection
@section('content')
<div class="page-h"><div><h1>Inventory check baru</h1><p>Pilih objek yang dicek. Sistem memuat stok saat ini sebagai qty sistem.</p></div></div>
<div class="card line-navy" style="max-width:620px"><div class="card-b">
  <form method="post" action="{{ route('ic.store') }}">@csrf
    <div class="f"><label>Cek per</label>
      <label class="chk"><input type="radio" name="mode" value="lpn" @checked(request('lpn') || old('mode', 'lpn') === 'lpn')> LPN (satu palet)</label>
      <label class="chk"><input type="radio" name="mode" value="location" @checked(!request('lpn') && old('mode') === 'location')> Lokasi (bin / rak / zona, termasuk turunannya)</label></div>
    <div class="f"><label>Scan / ketik LPN atau lokasi</label><input type="text" name="code" value="{{ old('code', request('lpn')) }}" placeholder="mis. SKI0000123 atau SKI-FZ-A01-1" required data-autofocus></div>
    <button class="btn red">Buat & muat stok</button>
  </form>
</div></div>
@endsection
