@extends('layouts.app')
@section('title', 'Persiapan Implementasi')
@section('crumb') Bantuan <small>/ Persiapan Implementasi</small> @endsection
@section('content')
<div class="page-h"><div><h1>Persiapan Implementasi FoodTrace</h1><p style="max-width:900px">{{ $ringkas }}</p></div>
  <div class="sp"></div><button class="btn ghost" onclick="window.print()">⎙ Cetak</button></div>

<div class="card line-navy"><div class="card-h"><h3>Peta tahapan</h3></div><div class="card-b">
  <div class="steps" style="margin:0">@foreach($fase as $f)<span class="{{ $loop->first ? 'on' : 'done' }}"><a href="#fase-{{ $f['kode'] }}" style="color:inherit">{{ $f['kode'] }}. {{ $f['judul'] }}</a></span>@endforeach</div>
</div></div>

@foreach($fase as $f)
<div class="card {{ ['line-navy', 'line-red', 'line-brown', 'line-green'][$loop->index % 4] }}" id="fase-{{ $f['kode'] }}">
  <div class="card-h"><h3>Tahap {{ $f['kode'] }} · {{ $f['judul'] }}</h3><div class="sp"></div><span class="badge b-navy">{{ $f['durasi'] }}</span></div>
  <div class="card-b grid g3" style="gap:18px">
    <div class="span2"><p style="margin-top:0"><b>Tujuan:</b> {{ $f['tujuan'] }}</p>
      <ol style="margin:0;padding-left:20px">@foreach($f['langkah'] as $l)<li style="margin:4px 0">{{ $l }}</li>@endforeach</ol></div>
    <div><div style="font-size:.78rem;font-weight:700;text-transform:uppercase;letter-spacing:.06em;color:var(--brown);margin-bottom:6px">Hasil kerja</div>
      @foreach($f['hasil'] as $h)<label class="chk"><input type="checkbox" data-impl="{{ $f['kode'] }}-{{ $loop->index }}"> {{ $h }}</label>@endforeach</div>
  </div>
</div>
@endforeach

<h2 style="margin-top:20px">Peran dalam tim proyek</h2>
<div class="grid g3">
@foreach($peran as $p)
  <div class="card {{ ['line-navy', 'line-red', 'line-brown', 'line-green'][$loop->index % 4] }}"><div class="card-h"><h3>{{ $p['nama'] }}</h3></div><div class="card-b">
    <p class="muted" style="margin-top:0">Biasanya: <b>{{ $p['siapa'] }}</b></p>
    <ul style="margin:0;padding-left:18px">@foreach($p['tugas'] as $t)<li style="margin:3px 0">{{ $t }}</li>@endforeach</ul>
  </div></div>
@endforeach
</div>

<div class="grid g2">
  <div class="card line-navy"><div class="card-h"><h3>Matriks RACI</h3><div class="sp"></div><small class="muted">R pelaksana · A penanggung jawab · C dimintai masukan · I diberi informasi</small></div>
    <div class="card-b flush tbl-wrap"><table class="t"><thead><tr><th>Kegiatan</th>@foreach($raci['kolom'] as $c)<th style="text-align:center">{{ $c }}</th>@endforeach</tr></thead><tbody>
    @foreach($raci['baris'] as $k => $vals)<tr><td><b>{{ $k }}</b></td>@foreach($vals as $v)<td style="text-align:center"><span class="badge {{ ['R' => 'b-navy', 'A' => 'b-red', 'C' => 'b-amber', 'I' => 'b-grey'][$v] }}">{{ $v }}</span></td>@endforeach</tr>@endforeach
    </tbody></table></div></div>
  <div class="card line-red"><div class="card-h"><h3>Risiko utama & mitigasi</h3></div><div class="card-b flush"><table class="t">
    @foreach($risiko as $x)<tr><td style="width:40%"><b>{{ $x['risiko'] }}</b></td><td>{{ $x['mitigasi'] }}</td></tr>@endforeach</table></div></div>
</div>
@push('scripts')
<script>
  // Centang hasil kerja disimpan di browser ini saja (pengingat pribadi).
  document.querySelectorAll('[data-impl]').forEach(function (c) {
    var k = 'ft-impl-' + c.dataset.impl;
    try { c.checked = localStorage.getItem(k) === '1'; } catch (e) {}
    c.addEventListener('change', function () { try { localStorage.setItem(k, c.checked ? '1' : '0'); } catch (e) {} });
  });
</script>
@endpush
@endsection
