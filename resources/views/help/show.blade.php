@extends('layouts.app')
@section('title', 'Panduan: '.$t['judul'])
@section('crumb') Bantuan <small>/ <a href="{{ route('help.index') }}">Panduan</a> / {{ $t['judul'] }}</small> @endsection
@php
  $flat = collect(config('bantuan.kelompok'))->flatten()->values(); $i = $flat->search($key);
  $prev = $i !== false && $i > 0 ? $flat[$i - 1] : null; $next = $i !== false && $i < $flat->count() - 1 ? $flat[$i + 1] : null;
  $group = collect(config('bantuan.kelompok'))->search(fn ($keys) => in_array($key, $keys));
@endphp
@section('content')
<div class="grid g3">
  <div class="card line-red span2"><div class="card-b" style="padding:18px 22px">
    <div class="eyebrow" style="color:var(--brown);font-weight:700;font-size:.75rem;letter-spacing:.08em;text-transform:uppercase">{{ $group }}</div>
    @include('help._topic', ['t' => $t, 'k' => $key])
    <hr><div class="actions-row">@if($prev)<a class="btn ghost" href="{{ route('help.show', $prev) }}">← {{ config('bantuan.topik.'.$prev.'.judul') }}</a>@endif<div class="sp" style="flex:1"></div>@if($next)<a class="btn" href="{{ route('help.show', $next) }}">{{ config('bantuan.topik.'.$next.'.judul') }} →</a>@endif</div>
  </div></div>
  <div class="card line-navy"><div class="card-h"><h3>{{ $group ?: 'Topik' }}</h3></div><div class="card-b flush"><table class="t">
    @foreach(config('bantuan.kelompok.'.$group, []) as $k)<tr class="{{ $k === $key ? 'grp' : '' }}"><td><a href="{{ route('help.show', $k) }}">{{ config('bantuan.topik.'.$k.'.judul') }}</a></td></tr>@endforeach
    <tr><td><a href="{{ route('help.index') }}">← Semua topik</a></td></tr>
  </table></div></div>
</div>
@endsection
