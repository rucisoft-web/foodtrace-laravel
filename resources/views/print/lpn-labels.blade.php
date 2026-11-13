@extends('layouts.print')
@section('title', 'Label LPN'.($doc ? ' · '.$doc->number : '').' ('.count($labels).')')
@section('style')
  .lbl { width: 150mm; height: 100mm; background: #fff; margin: 12px auto; padding: 3mm; display: flex; gap: 0; box-shadow: 0 2px 10px rgba(0,0,0,.15); overflow: hidden; }
  .lbl .l { width: 52%; padding-right: 2mm; border-right: .6mm solid #000; }
  .lbl .r { flex: 1; padding-left: 1.5mm; display: grid; grid-template-columns: repeat(4, 1fr); grid-auto-rows: 15.2mm; gap: 0; align-content: start; }
  .lbl .r div { border: .2mm dashed #999; text-align: center; padding: .5mm; } .lbl .r i { width: 11mm; height: 11mm; display: block; margin: 0 auto; background-size: contain; background-repeat: no-repeat; -webkit-print-color-adjust: exact; print-color-adjust: exact; } .lbl .r span { font-size: 5pt; }
  .pr { font-size: 6.5pt; white-space: nowrap; } .code { font-size: 13pt; font-weight: bold; margin-top: 1mm; } .nm { font-size: 10pt; font-weight: bold; border-bottom: .6mm solid #000; padding-bottom: 1mm; line-height: 1.15; max-height: 9mm; overflow: hidden; }
  .qrrow { display: flex; margin-top: 1mm; } .qrbox { width: 40%; border: .5mm solid #000; text-align: center; padding: 1mm; } .qrbox img { width: 25mm; height: 25mm; } .qrbox div { font-size: 8pt; }
  .dates { padding-left: 2mm; font-size: 11pt; font-weight: bold; line-height: 1.6; align-self: center; }
  table.inf { width: 100%; font-size: 9pt; margin-top: 1.5mm; line-height: 1.35; border-collapse: collapse; } table.inf td { padding: 0; } table.inf td:first-child { width: 42%; font-weight: bold; }
  .zn { float: right; border: .4mm solid #000; padding: 0 1.5mm; font-weight: bold; }
  @media print { @page { size: 150mm 100mm; margin: 0; } .lbl { margin: 0; box-shadow: none; page-break-after: always; } }
@endsection
@section('content')
@foreach($labels as $d)
  @php $qr = \App\Support\Qr::uri($d['lpn']->name, 300); @endphp
  <style>.q{{ $d['lpn']->id }} { background-image: url('{{ $qr }}'); }</style>
  <div class="lbl">
    <div class="l">
      <div class="pr"><b>Printed By :</b> {{ auth()->user()->name }} &nbsp; <b>Printed On :</b> {{ now()->format('d-m-Y H:i') }}</div>
      <div class="code">{{ $d['code'] }}</div>
      <div class="nm">{{ $d['name'] }}</div>
      <div class="qrrow"><div class="qrbox"><img src="{{ $qr }}" alt=""><div>LPN-{{ $d['lpn']->name }}</div></div>
        <div class="dates"><div>Exp : {{ $d['exp'] }}</div><div>Rcv : {{ $d['rcv'] }}</div><div>Pack : {{ $d['pack'] }}</div></div></div>
      <table class="inf">
        <tr><td>Qty Tally/Karton</td><td>: <b>{{ $d['qty'] }}</b> {{ $d['uom'] }} / <b>{{ $d['cartons'] }}</b></td></tr>
        <tr><td>Lot</td><td>: {{ $d['lot'] }}</td></tr><tr><td>EST</td><td>: {{ $d['est'] }}</td></tr>
        <tr><td>No. Pallet</td><td>: {{ $d['pallet'] }}</td></tr><tr><td>No Container</td><td>: {{ $d['container'] }}</td></tr>
        <tr><td>Packaging</td><td>: {{ $d['packaging'] }}</td></tr>
        <tr><td>Grade</td><td>: {{ $d['grade'] }} @if($d['zone'])<span class="zn">{{ $d['zone'] }}</span>@endif</td></tr>
      </table>
    </div>
    <div class="r">@for($i = 0; $i < 24; $i++)<div><i class="q{{ $d['lpn']->id }}"></i><span>{{ $d['lpn']->name }}</span></div>@endfor</div>
  </div>
@endforeach
@endsection
