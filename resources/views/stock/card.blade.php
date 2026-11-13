@extends('layouts.app')
@section('title', 'Stock Card')
@section('crumb') Inventory <small>/ Stock Card</small> @endsection
@section('content')
<div class="page-h"><div><h1>Stock Card (kartu stok)</h1><p>Semua mutasi satu item: masuk, keluar, pindah, penyesuaian dan butcher, dengan saldo berjalan.</p></div></div>
<form class="filters" method="get">
  <div class="f"><label>Item code / barcode</label><input type="search" name="item" value="{{ request('item') }}" placeholder="mis. HFR02FRC0001" required data-autofocus></div>
  <div class="f"><label>Lot (opsional)</label><input type="text" name="lot" value="{{ request('lot') }}"></div>
  <div class="f"><label>Dari</label><input type="date" name="from" value="{{ $from }}"></div><div class="f"><label>Sampai</label><input type="date" name="to" value="{{ $to }}"></div>
  <button class="btn">Tampilkan</button>
</form>
@if(request('item') && !$item)<div class="alert bad">Item {{ request('item') }} tidak ditemukan.</div>@endif
@if($item)
<div class="grid g4" style="margin-bottom:12px">
  <div class="kpi"><div class="l">Item</div><div class="v" style="font-size:1.05rem">{{ $item->code }}</div><div class="s">{{ $item->name }}</div></div>
  <div class="kpi"><div class="l">Saldo awal {{ \App\Support\Ui::date($from) }}</div><div class="v">@qty($opening)</div><div class="s">{{ $item->uom->code }}</div></div>
  <div class="kpi green"><div class="l">Masuk / keluar periode</div><div class="v">@qty($rows->sum('q_in')) / @qty($rows->sum('q_out'))</div><div class="s">{{ $rows->count() }} mutasi</div></div>
  <div class="kpi brown"><div class="l">Stok fisik sekarang</div><div class="v">@qty($item->onHand())</div><div class="s">{{ $item->uom->code }}@if(request('lot')) · semua lot @endif</div></div>
</div>
<div class="card"><div class="card-b flush tbl-wrap"><table class="t">
  <thead><tr><th>Tanggal</th><th>Referensi</th><th>Jenis</th><th>Lot</th><th>Dari</th><th>Ke</th><th>LPN</th><th class="n">Masuk</th><th class="n">Keluar</th><th class="n">Saldo</th><th>Operator</th></tr></thead>
  <tbody>
    <tr class="grp"><td colspan="9">Saldo awal</td><td class="n">@qty($opening)</td><td></td></tr>
    @forelse($rows as $m)
    <tr><td>@dt($m->date)</td><td>@if($m->document)<a href="{{ $m->document->show() }}">{{ $m->reference }}</a>@else{{ $m->reference }}@endif</td>
      <td>{{ ['in' => 'Masuk', 'out' => 'Keluar', 'internal' => 'Pindah', 'adjust' => 'Penyesuaian', 'butcher' => 'Butcher'][$m->kind] ?? $m->kind }}</td>
      <td>{{ $m->lot?->name }}</td><td><small>{{ $m->fromName() }}</small></td><td><small>{{ $m->toName() }}</small></td><td><small>{{ ($m->resultLpn ?? $m->lpn)?->name }}</small></td>
      <td class="n">{{ $m->q_in ? \App\Support\Wms::fmt($m->q_in) : '' }}</td><td class="n">{{ $m->q_out ? \App\Support\Wms::fmt($m->q_out) : '' }}</td><td class="n"><b>@qty($m->balance)</b></td><td><small>{{ $m->user?->name }}</small></td></tr>
    @empty <tr><td colspan="11" class="empty">Tidak ada mutasi pada periode ini.</td></tr> @endforelse
  </tbody>
</table></div></div>
@endif
@endsection
