@extends('layouts.print')
@section('title', 'Label lokasi ('.$locs->count().')')
@section('style')
  .grid-l { display: grid; grid-template-columns: repeat(3, 1fr); gap: 4mm; width: 210mm; margin: 12px auto; background: #fff; padding: 8mm; }
  .ll { border: .5mm solid #000; padding: 3mm; display: flex; gap: 3mm; align-items: center; page-break-inside: avoid; }
  .ll .q { width: 24mm; flex: none; } .ll .q svg { width: 100%; height: auto; display: block; }
  .ll b { font-size: 15pt; display: block; } .ll small { font-size: 7pt; }
  .zz { display: inline-block; padding: 0 2mm; color: #fff; font-weight: bold; font-size: 8pt; margin-top: 1mm; }
  @media print { .grid-l { margin: 0; padding: 0; } }
@endsection
@section('content')
<div class="grid-l">
@foreach($locs as $l)
  <div class="ll"><div class="q">{!! \App\Support\Qr::svg($l->barcode, 120) !!}</div>
    <div><b>{{ $l->name }}</b><div class="mono">{{ $l->barcode }}</div><small>{{ $l->full_name }}</small><br>
      @if($l->eff_zone)<span class="zz" style="background:{{ ['frozen' => '#1b2a4e', 'chilled' => '#0f766e', 'dry' => '#9a3412'][$l->eff_zone] ?? '#555' }}">{{ strtoupper($l->eff_zone) }}</span>@endif</div></div>
@endforeach
</div>
@endsection
