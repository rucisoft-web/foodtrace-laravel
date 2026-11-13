@extends('layouts.app')
@section('title', 'Ganti kata sandi')
@section('crumb', 'Ganti kata sandi')
@section('content')
<div class="page-h"><div><h1>Ganti kata sandi</h1><p>{{ auth()->user()->name }} · {{ auth()->user()->username }}</p></div></div>
<div class="card line-navy" style="max-width:460px"><div class="card-b">
  <form method="post">@csrf
    <div class="f"><label>Kata sandi lama</label><input type="password" name="current" required autocomplete="current-password"></div>
    <div class="f"><label>Kata sandi baru (min. 8)</label><input type="password" name="password" required autocomplete="new-password"></div>
    <div class="f"><label>Ulangi kata sandi baru</label><input type="password" name="password_confirmation" required></div>
    <button class="btn red">Simpan</button>
  </form>
</div></div>
@endsection
