@extends('layouts.app')
@section('title', 'Panduan')
@section('crumb') Bantuan <small>/ Panduan</small> @endsection
@php $topik = config('bantuan.topik'); @endphp
@section('content')
<div class="page-h"><div><h1>Panduan FoodTrace</h1><p>Satu topik untuk setiap layar: apa yang dibahas, kapan dilaksanakan, langkah input data dan aturan sistem, lengkap dengan tangkapan layar. Di setiap layar, tekan <b>？ Bantuan</b> di kanan atas (atau tombol <kbd>?</kbd>).</p></div>
  <div class="sp"></div><a class="btn ghost" href="{{ route('implementation') }}">◈ Persiapan Implementasi</a></div>
<div class="card line-navy"><div class="card-h"><h3>Istilah penting</h3></div><div class="card-b"><dl class="kv">@foreach($concepts as $k => $v)<dt>{{ $k }}</dt><dd style="font-weight:400">{{ $v }}</dd>@endforeach</dl></div></div>
<div class="grid g3">
@foreach($groups as $g => $keys)
  <div class="card {{ ['line-navy', 'line-red', 'line-brown', 'line-green'][$loop->index % 4] }}"><div class="card-h"><h3>{{ $g }}</h3><div class="sp"></div><span class="badge b-grey">{{ count($keys) }}</span></div>
    <div class="card-b flush"><table class="t">@foreach($keys as $k)@if(isset($topik[$k]))
      <tr><td style="width:46px">@if(file_exists(public_path('img/bantuan/'.$k.'.jpg')))<img src="{{ asset('img/bantuan/'.$k.'.jpg') }}" alt="" loading="lazy" style="width:42px;height:28px;object-fit:cover;object-position:top left;border:1px solid var(--line);border-radius:3px">@endif</td>
        <td><a href="{{ route('help.show', $k) }}"><b>{{ $topik[$k]['judul'] }}</b></a><br><small class="muted">{{ \Illuminate\Support\Str::limit($topik[$k]['apa'], 90) }}</small></td></tr>
    @endif @endforeach</table></div></div>
@endforeach
</div>
@endsection
