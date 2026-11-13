@extends('layouts.hht')
@section('title', 'QC hold')
@section('back', route('hht.home'))
@section('content')
<form method="post">@csrf
  <label>Scan LPN</label><input type="text" name="lpn" class="scan" data-scan autocomplete="off">
  <div class="f" style="margin-top:6px"><label>Alasan</label><select name="reason"><option>Kemasan rusak / bocor</option><option>Suhu tidak sesuai</option><option>Bau / warna tidak normal</option><option>Label tidak terbaca</option><option>Mendekati expiry</option><option>Keluhan customer</option><option>Lainnya</option></select></div>
  <button class="btn red block">Tahan di karantina</button></form>
<p class="muted">LPN dipindah ke lokasi QC zona item. Rilis / tolak dilakukan QA di web.</p>
@endsection
