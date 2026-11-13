@extends('layouts.hht')
@section('title', 'Butcher out')
@section('back', route('hht.home'))
@section('content')
<form method="post">@csrf
  <label>Scan LPN bahan</label><input type="text" name="lpn" class="scan" data-scan autocomplete="off">
  <div class="grid g2" style="gap:8px;margin-top:6px"><div class="f"><label>Qty (kosong = semua)</label><input type="text" name="qty" inputmode="decimal"></div>
    <div class="f"><label>Proses</label><select name="type">@foreach(\App\Models\Butcher::TYPES as $k => $l)<option value="{{ $k }}">{{ $l }}</option>@endforeach</select></div></div>
  <div class="f"><label>Ref. (SO / WO)</label><input type="text" name="ref"></div>
  <button class="btn red block">Keluarkan ke ruang potong</button></form>
<p class="muted">Hasil potong dicatat di web: Butcher / Repack → Butcher In.</p>
@foreach($recent as $b)<div class="row done"><small>{{ $b->number }} · {{ $b->lines->first()?->item->code }} · {{ \App\Support\Wms::fmt($b->total_kg, 2) }} kg</small></div>@endforeach
@endsection
