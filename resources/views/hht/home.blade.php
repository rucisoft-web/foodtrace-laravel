@extends('layouts.hht')
@section('title', 'FoodTrace HHT')
@section('content')
<div style="display:flex;align-items:center;gap:10px;margin-bottom:12px"><img src="{{ asset('img/logo-saranakulina.png') }}" alt="" style="height:30px"><div style="flex:1"><b>{{ auth()->user()->name }}</b><br><small class="muted">{{ auth()->user()->roleLabel() }}</small></div>
  <a class="btn ghost sm" href="{{ route('dashboard') }}">Web</a></div>
<div class="hht-tiles">
  <a href="{{ route('hht.receipts') }}">@if($c['receipts'])<span class="n">{{ $c['receipts'] }}</span>@endif⇩ Receiving<small>Scan karton / qty per palet</small></a>
  <a href="{{ route('hht.putaway') }}">@if($c['putaway'])<span class="n">{{ $c['putaway'] }}</span>@endif⇢ Put away<small>LPN dari dock ke rak</small></a>
  <a href="{{ route('hht.jobs') }}">@if($c['jobs'])<span class="n">{{ $c['jobs'] }}</span>@endif☰ Picking<small>Ambil sesuai FEFO</small></a>
  <a href="{{ route('hht.check') }}">@if($c['check'])<span class="n">{{ $c['check'] }}</span>@endif✓ Checker<small>Cek barang di staging</small></a>
  <a href="{{ route('hht.loadings') }}">@if($c['load'])<span class="n">{{ $c['load'] }}</span>@endif⛟ Loading<small>Scan LPN naik kendaraan</small></a>
  <a href="{{ route('hht.putaway') }}">⇄ Bin transfer<small>Pindah LPN antar rak</small></a>
  <a href="{{ route('hht.ic') }}">☑ Inventory check<small>Cek fisik LPN / lokasi</small></a>
  <a href="{{ route('hht.count') }}"># Cycle count<small>Hitung buta lokasi</small></a>
  <a href="{{ route('hht.hold') }}">⚑ QC hold<small>Tahan LPN bermasalah</small></a>
  <a href="{{ route('hht.butcher') }}">✂ Butcher out<small>Bahan ke ruang potong</small></a>
  <a href="{{ route('hht.lookup') }}">⌕ Cek barcode<small>Info LPN / lokasi / item</small></a>
  <a href="#" onclick="document.getElementById('lo').submit();return false">⏻ Keluar<small>{{ auth()->user()->username }}</small></a>
</div>
<form id="lo" method="post" action="{{ route('logout') }}">@csrf</form>
@endsection
