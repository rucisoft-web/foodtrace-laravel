@extends('layouts.app')
@section('title', 'Put Away')
@section('crumb') Inbound <small>/ Put Away</small> @endsection
@section('content')
<div class="page-h">
  <div><h1>Put Away</h1><p>LPN yang sudah diterima (status Receiving di dock) dipindah ke rak sesuai zona. Rak zona lain ditolak sistem.</p></div>
  <div class="sp"></div><a class="btn ghost" href="{{ route('hht.putaway') }}">▣ Put away dengan HHT</a>
</div>
<div class="grid g4" style="margin-bottom:12px">
  <div class="kpi red"><div class="l">Menunggu put away</div><div class="v">{{ $stocks->count() }}</div><div class="s">baris stok di dock</div></div>
  @foreach(\App\Support\Wms::ZONES as $z => $l)<div class="kpi" style="--c:var(--{{ $z === 'frozen' ? 'frozen' : ($z === 'chilled' ? 'chilled' : 'dry') }})"><div class="l">Bin kosong {{ $l }}</div><div class="v">{{ $freeCount[$z] ?? 0 }}</div><div class="s">@zone($z) palet utuh + picking</div></div>@endforeach
</div>
<form class="filters" method="get"><div class="f"><label>Zona</label><select name="zone" data-submit><option value="">Semua</option>@foreach(\App\Support\Wms::ZONES as $k => $l)<option value="{{ $k }}" @selected(request('zone') === $k)>{{ $l }}</option>@endforeach</select></div></form>
<div class="card"><div class="card-b flush tbl-wrap"><table class="t">
  <thead><tr><th>Zona</th><th>LPN</th><th>Item</th><th>Lot · expiry</th><th>Di</th><th class="n">Qty</th><th>Pindahkan ke (scan bin)</th></tr></thead>
  <tbody>@forelse($stocks as $s)
    <tr><td>@zone($s->item->zone)</td><td>@if($s->lpn)<a href="{{ route('lpn.show', $s->lpn) }}"><b>{{ $s->lpn->name }}</b></a>@else<small>tanpa LPN</small>@endif</td>
      <td><b>{{ $s->item->code }}</b><br><small>{{ $s->item->name }}</small></td><td>{{ $s->lot?->name }}<br><small>@d($s->lot?->expiry_date)</small></td>
      <td>{{ $s->location->name }} @status($s->status())</td><td class="n">@qty($s->qty) {{ $s->item->uom->code }}</td>
      <td><form method="post" action="{{ route('putaway.move') }}" class="inline">@csrf<input type="hidden" name="stock_id" value="{{ $s->id }}">
        <div class="f"><input type="text" name="dest" value="{{ $s->suggest?->barcode }}" placeholder="barcode bin" style="width:150px" required></div>
        <button class="btn sm">⇢ Put away</button>@if($s->suggest)<small class="muted">saran: {{ $s->suggest->full_name }}</small>@else<small class="muted" style="color:var(--maroon)">rak zona penuh</small>@endif</form></td></tr>
  @empty <tr><td colspan="7" class="empty">Tidak ada barang di dock. Semua sudah di rak.</td></tr> @endforelse</tbody>
</table></div></div>
@endsection
