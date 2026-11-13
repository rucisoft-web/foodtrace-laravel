@extends('layouts.app')
@section('title', 'Pengumuman')
@section('crumb') Master Data <small>/ Pengumuman</small> @endsection
@php $e = $edit; @endphp
@section('content')
<div class="page-h"><div><h1>Pengumuman</h1><p>Tampil di halaman depan (landing page) sesuai peran yang dituju. Kosongkan peran untuk semua. Hanya Administrator yang bisa mengisi.</p></div>
  <div class="sp"></div><a class="btn ghost" href="{{ route('home') }}#pengumuman" target="_blank">↗ Lihat di halaman depan</a></div>
<div class="grid g3">
  <div class="span2">@forelse($rows as $a)
    @php $live = (!$a->starts_at || $a->starts_at->lte(today())) && (!$a->ends_at || $a->ends_at->gte(today())); @endphp
    <div class="card {{ $a->level === 'darurat' ? 'line-red' : ($a->level === 'penting' ? 'line-brown' : 'line-navy') }}"><div class="card-h">
      <h3>{{ $a->pinned ? '📌 ' : '' }}{{ $a->title }}</h3><span class="badge {{ ['info' => 'b-navy', 'penting' => 'b-amber', 'darurat' => 'b-red'][$a->level] }}">{{ \App\Models\Announcement::LEVELS[$a->level] }}</span>
      {!! $live ? '<span class="badge b-green">tayang</span>' : '<span class="badge b-grey">tidak tayang</span>' !!}<div class="sp"></div>
      <a class="btn ghost sm" href="{{ route('ann.index', ['edit' => $a->id]) }}">Ubah</a>
      <form method="post" action="{{ route('ann.delete', $a) }}" data-confirm="Hapus pengumuman ini?">@csrf @method('delete')<button class="btn ghost sm">✕</button></form></div>
      <div class="card-b"><div style="white-space:pre-line">{{ $a->body }}</div>
        <p class="muted" style="margin:8px 0 0">Untuk: {{ implode(', ', $a->roleLabels()) }} · {{ $a->starts_at ? 'mulai '.$a->starts_at->format('d/m/Y') : 'sejak dibuat' }}{{ $a->ends_at ? ' s.d. '.$a->ends_at->format('d/m/Y') : '' }} · oleh {{ $a->user?->name }}</p></div></div>
  @empty <div class="card"><div class="card-b empty">Belum ada pengumuman.</div></div> @endforelse</div>
  <div class="card line-red"><div class="card-h"><h3>{{ $e ? 'Ubah pengumuman' : 'Pengumuman baru' }}</h3>@if($e)<div class="sp"></div><a href="{{ route('ann.index') }}">batal</a>@endif</div><div class="card-b">
    <form method="post" action="{{ route('ann.save', $e) }}">@csrf
      <div class="f"><label>Judul *</label><input type="text" name="title" value="{{ old('title', $e?->title) }}" required></div>
      <div class="f"><label>Isi *</label><textarea name="body" rows="6" required>{{ old('body', $e?->body) }}</textarea></div>
      <div class="f"><label>Tingkat</label><select name="level">@foreach(\App\Models\Announcement::LEVELS as $k => $l)<option value="{{ $k }}" @selected(old('level', $e?->level) === $k)>{{ $l }}</option>@endforeach</select></div>
      <div class="f"><label>Untuk peran (kosong = semua)</label>@foreach(\App\Models\User::ROLES as $k => $l)<label class="chk"><input type="checkbox" name="roles[]" value="{{ $k }}" @checked(in_array($k, old('roles', $e?->roles ?? [])))> {{ $l }}</label>@endforeach</div>
      <div class="grid g2" style="gap:8px"><div class="f"><label>Tayang mulai</label><input type="date" name="starts_at" value="{{ old('starts_at', optional($e?->starts_at)->format('Y-m-d')) }}"></div><div class="f"><label>Sampai</label><input type="date" name="ends_at" value="{{ old('ends_at', optional($e?->ends_at)->format('Y-m-d')) }}"></div></div>
      <label class="chk"><input type="checkbox" name="pinned" value="1" @checked(old('pinned', $e?->pinned))> Sematkan di atas</label>
      <button class="btn red">Simpan & tayangkan</button>
    </form>
  </div></div>
</div>
@endsection
