@extends('layouts.app')
@section('title', 'Pengguna')
@section('crumb') Master Data <small>/ Pengguna</small> @endsection
@php $e = $edit; @endphp
@section('content')
<div class="page-h"><div><h1>Pengguna & peran</h1><p>Masuk dengan username atau email. Peran menentukan menu yang boleh dipakai dan pengumuman yang tampil di halaman depan.</p></div></div>
<div class="grid g3">
  <div class="card line-navy span2"><div class="card-b flush tbl-wrap"><table class="t"><thead><tr><th>Nama</th><th>Username</th><th>Email</th><th>Peran</th><th>Aktif</th><th>Dibuat</th><th></th></tr></thead><tbody>
    @foreach($rows as $u)<tr class="{{ $u->active ? '' : 'warn' }}"><td><span class="avatar" style="width:24px;height:24px;font-size:.7rem">{{ $u->initials() }}</span> <b>{{ $u->name }}</b></td><td class="mono">{{ $u->username }}</td><td>{{ $u->email }}</td>
      <td><span class="badge {{ $u->role === 'admin' ? 'b-red' : ($u->role === 'manager' ? 'b-navy' : 'b-grey') }}">{{ $u->roleLabel() }}</span></td><td>{{ $u->active ? '✓' : 'nonaktif' }}</td><td>@d($u->created_at)</td>
      <td><a class="btn ghost sm" href="{{ route('users.index', ['edit' => $u->id]) }}">Ubah</a></td></tr>@endforeach
  </tbody></table></div></div>
  <div class="card line-red"><div class="card-h"><h3>{{ $e ? 'Ubah '.$e->name : 'Pengguna baru' }}</h3>@if($e)<div class="sp"></div><a href="{{ route('users.index') }}">batal</a>@endif</div><div class="card-b">
    <form method="post" action="{{ route('users.save', $e) }}">@csrf
      <div class="f"><label>Nama lengkap *</label><input type="text" name="name" value="{{ old('name', $e?->name) }}" required></div>
      <div class="f"><label>Username *</label><input type="text" name="username" value="{{ old('username', $e?->username) }}" required></div>
      <div class="f"><label>Email</label><input type="email" name="email" value="{{ old('email', $e?->email) }}"></div>
      <div class="f"><label>Peran *</label><select name="role">@foreach(\App\Models\User::ROLES as $k => $l)<option value="{{ $k }}" @selected(old('role', $e?->role ?? 'operator') === $k)>{{ $l }}</option>@endforeach</select></div>
      <div class="f"><label>Kata sandi {{ $e ? '(kosongkan bila tidak diganti)' : '*' }}</label><input type="password" name="password" autocomplete="new-password" @required(!$e)></div>
      <label class="chk"><input type="checkbox" name="active" value="1" @checked(old('active', $e?->active ?? true))> Aktif (bisa masuk)</label>
      <button class="btn red">Simpan</button>
    </form>
    <hr><dl class="kv" style="font-size:.85rem">@foreach(['admin' => 'Semua menu, pengguna, pengumuman.', 'manager' => 'Approval inventory check, cycle count, QC release, tutup paksa loading, Goods Issue langsung.', 'inbound' => 'Inbound, receiving, put away, label.', 'outbound' => 'Outbound, picking job, checker, loading, POD.', 'operator' => 'Layar handheld (HHT).'] as $k => $d)<dt>{{ \App\Models\User::ROLES[$k] }}</dt><dd style="font-weight:400">{{ $d }}</dd>@endforeach</dl>
  </div></div>
</div>
@endsection
