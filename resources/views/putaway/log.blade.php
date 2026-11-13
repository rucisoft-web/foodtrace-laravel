@extends('layouts.app')
@section('title', 'Bin Transfer')
@section('crumb') Inventory <small>/ Bin Transfer</small> @endsection
@section('content')
<div class="page-h"><div><h1>Bin Transfer & pemindahan internal</h1><p>Semua pemindahan antar-lokasi dalam gudang: put away, bin transfer, QC hold / release. Tidak diposting ke SAP (satu gudang SAP).</p></div>
  <div class="sp"></div><a class="btn ghost" href="{{ route('hht.putaway') }}">▣ Pindah LPN dengan HHT</a></div>
<div class="card line-navy"><div class="card-h"><h3>Pindahkan stok</h3></div><div class="card-b">
  <form method="get" action="{{ route('stock.index') }}" class="inline"><div class="f"><label>Cari stok yang akan dipindah</label><input type="search" name="q" placeholder="LPN / item / lokasi"></div><button class="btn ghost">Cari di Stock Status</button>
  <small class="muted">Untuk memindah satu LPN utuh, gunakan HHT (scan LPN lalu scan bin tujuan) atau layar Put Away.</small></form>
</div></div>
<form class="filters" method="get">
  <div class="f"><label>Cari</label><input type="search" name="q" value="{{ request('q') }}" placeholder="INT / item / LPN"></div>
  <div class="f"><label>Alasan</label><select name="reason" data-submit><option value="">Semua</option>@foreach($reasons as $x)<option @selected(request('reason') === $x)>{{ $x }}</option>@endforeach</select></div>
  <button class="btn">Tampilkan</button>
</form>
<div class="card"><div class="card-b flush tbl-wrap"><table class="t">
  <thead><tr><th>Nomor</th><th>Tanggal</th><th>Alasan</th><th>Item</th><th>LPN</th><th>Dari</th><th>Ke</th><th class="n">Qty</th><th>Operator</th></tr></thead>
  <tbody>@forelse($rows as $d)
    @php $x = $d->details->first(); @endphp
    <tr><td><a href="{{ route('internal.show', $d) }}">{{ $d->number }}</a></td><td>@dt($d->done_at)</td><td>{{ $d->reason }}</td><td>{{ $x?->item->code }}</td><td>{{ $x?->lpn?->name }}</td>
      <td><small>{{ $x?->from?->full_name }}</small></td><td><small>{{ $x?->to?->full_name }}</small></td><td class="n">@qty($d->details->sum('qty'))</td><td><small>{{ $d->user?->name }}</small></td></tr>
  @empty <tr><td colspan="9" class="empty">Belum ada pemindahan.</td></tr> @endforelse</tbody>
</table></div></div>
{{ $rows->links() }}
@endsection
