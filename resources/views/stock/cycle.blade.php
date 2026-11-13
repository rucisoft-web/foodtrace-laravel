@extends('layouts.app')
@section('title', 'Cycle Count')
@section('crumb') Inventory <small>/ Cycle Count</small> @endsection
@section('content')
<div class="page-h"><div><h1>Cycle Count</h1><p>Hitung buta per lokasi: qty sistem tidak ditampilkan ke penghitung. Selisih disetujui kepala gudang dan dicatat sebagai penyesuaian.</p></div>
  <div class="sp"></div><a class="btn ghost" href="{{ route('hht.count') }}">▣ Hitung dengan HHT</a></div>
<div class="grid g2">
<div class="card line-navy">
  <div class="card-h"><h3>1. Hitung lokasi</h3></div>
  <div class="card-b">
    <form method="get" class="inline" style="margin-bottom:10px"><div class="f"><label>Lokasi (scan / ketik)</label><input type="text" name="location" value="{{ request('location') }}" placeholder="mis. SKI-FZ-A01-1 atau FZ/BLK" required data-autofocus></div><button class="btn">Muat</button></form>
    @if($loc)
      <form method="post" action="{{ route('cycle.save') }}">@csrf
      <table class="t"><thead><tr><th>Lokasi</th><th>Item / lot / LPN</th><th class="n">Hasil hitung</th></tr></thead><tbody>
      @forelse($stocks as $s)
        <tr><td><small>{{ $s->location->name }}</small></td><td><b>{{ $s->item->code }}</b> {{ $s->item->name }}<br><small>{{ $s->lot?->name }} · {{ $s->lpn?->name }}</small></td>
          <td class="n"><input type="text" inputmode="decimal" name="counts[{{ $s->id }}]" value="{{ $s->counted_qty !== null ? \App\Support\Wms::fmt($s->counted_qty) : '' }}" style="width:90px;text-align:right"> {{ $s->item->uom->code }}</td></tr>
      @empty <tr><td colspan="3" class="empty">Tidak ada stok di {{ $loc->full_name }}.</td></tr> @endforelse
      </tbody></table>
      @if($stocks->isNotEmpty())<p><button class="btn red">Simpan hasil hitung</button></p>@endif
      </form>
    @endif
  </div>
</div>
<div class="card line-red">
  <div class="card-h"><h3>2. Persetujuan selisih</h3><div class="sp"></div><span class="badge b-amber">{{ $pending->count() }}</span></div>
  <div class="card-b flush">
    <form method="post" action="{{ route('cycle.apply') }}">@csrf
    <table class="t"><thead><tr><th><input type="checkbox" data-check-all=".cc" checked></th><th>Lokasi · item</th><th class="n">Sistem</th><th class="n">Hitung</th><th class="n">Selisih</th><th>Oleh</th></tr></thead><tbody>
    @forelse($pending as $s)
      @php $diff = round($s->counted_qty - $s->qty, 3); @endphp
      <tr class="{{ abs($diff) > 0.0005 ? 'warn' : 'ok' }}"><td><input type="checkbox" class="cc" name="ids[]" value="{{ $s->id }}" checked></td>
        <td><small>{{ $s->location->full_name }}</small><br><b>{{ $s->item->code }}</b> <small>{{ $s->lot?->name }} {{ $s->lpn?->name }}</small></td>
        <td class="n">@qty($s->qty)</td><td class="n">@qty($s->counted_qty)</td><td class="n"><b>{{ $diff > 0 ? '+' : '' }}@qty($diff)</b></td><td><small>{{ $counters[$s->counted_by] ?? '' }}</small></td></tr>
    @empty <tr><td colspan="6" class="empty">Tidak ada hitungan yang menunggu persetujuan.</td></tr> @endforelse
    </tbody></table>
    @if($pending->isNotEmpty() && auth()->user()->isManager())
      <div class="card-b actions-row"><button class="btn green" name="action" value="apply" data-confirm="Terapkan hitungan terpilih sebagai stok?">✓ Setujui & sesuaikan stok</button><button class="btn ghost" name="action" value="reject">Tolak (hitung ulang)</button></div>
    @elseif($pending->isNotEmpty())<div class="card-b"><small class="muted">Menunggu persetujuan Kepala gudang / QA.</small></div>@endif
    </form>
  </div>
</div>
</div>
@endsection
