@extends('layouts.app')
@section('title', 'LPN '.$lpn->name)
@section('crumb') Inventory <small>/ <a href="{{ route('lpn.index') }}">LPN List</a> / {{ $lpn->name }}</small> @endsection
@section('content')
<div class="page-h">
  <div><h1>LPN {{ $lpn->name }} @if($lpn->need_reprint)<span class="badge b-red">perlu cetak ulang</span>@endif</h1><p>{{ $lpn->item?->label() }} · lot {{ $lpn->lot?->name }}</p></div>
  <div class="sp"></div>
  <a class="btn ghost" href="{{ route('lpn.labels', ['ids' => $lpn->id]) }}" target="_blank">⎙ Cetak label</a>
  <a class="btn ghost" href="{{ route('ic.create', ['lpn' => $lpn->name]) }}">☑ Inventory check</a>
  <a class="btn ghost" href="{{ route('stock.card', ['item' => $lpn->item?->code, 'lot' => $lpn->lot?->name]) }}">▧ Kartu stok</a>
</div>
<div class="grid g3">
  <div class="card line-navy"><div class="card-h"><h3>Info palet</h3></div><div class="card-b">
    <div style="display:flex;gap:14px"><div class="qr-inline" style="width:110px;flex:none">{!! \App\Support\Qr::svg($lpn->name, 110) !!}</div>
    <dl class="kv"><dt>Item</dt><dd>{{ $lpn->item?->code }}</dd><dt>Zona</dt><dd>@zone($lpn->item?->zone)</dd><dt>Lot</dt><dd>{{ $lpn->lot?->name }}</dd><dt>Expiry</dt><dd>@d($lpn->lot?->expiry_date)</dd>
      <dt>Pack</dt><dd>@d($lpn->pack_date)</dd><dt>No. palet</dt><dd>{{ $lpn->pallet_no ?: '-' }}</dd><dt>Packaging</dt><dd>{{ $lpn->packaging ?: '-' }}</dd><dt>Grade / EST</dt><dd>{{ $lpn->lot?->grade ?: '-' }} / {{ $lpn->lot?->est ?: '-' }}</dd></dl></div>
  </div></div>
  <div class="card line-red span2"><div class="card-h"><h3>Stok saat ini</h3></div><div class="card-b flush"><table class="t">
    <thead><tr><th>Lokasi</th><th>Status</th><th>Owner</th><th class="n">Qty</th><th class="n">Reservasi</th></tr></thead>
    <tbody>@forelse($stocks as $s)<tr><td>{{ $s->location->full_name }}</td><td>@status($s->status())</td><td>{{ $s->owner?->name ?? 'Milik sendiri' }}</td><td class="n"><b>@qty($s->qty)</b> {{ $lpn->item?->uom?->code }}</td><td class="n">@qty($s->reserved_qty)</td></tr>
    @empty<tr><td colspan="5" class="empty">LPN kosong (sudah keluar / dipakai).</td></tr>@endforelse</tbody></table></div></div>
</div>
<div class="card line-brown"><div class="card-h"><h3>Riwayat pergerakan</h3></div><div class="card-b flush tbl-wrap"><table class="t">
  <thead><tr><th>Tanggal</th><th>Jenis</th><th>Referensi</th><th>Dari</th><th>Ke</th><th class="n">Qty</th><th>Operator</th></tr></thead>
  <tbody>@forelse($moves as $m)<tr><td>@dt($m->date)</td><td>{{ ['in' => 'Masuk', 'out' => 'Keluar', 'internal' => 'Pindah', 'adjust' => 'Penyesuaian', 'butcher' => 'Butcher'][$m->kind] ?? $m->kind }}</td>
    <td>{{ $m->reference }}</td><td><small>{{ $m->fromName() }}</small></td><td><small>{{ $m->toName() }}</small></td><td class="n">@qty($m->qty) {{ $m->uom }}</td><td><small>{{ $m->user?->name }}</small></td></tr>
  @empty<tr><td colspan="7" class="empty">Belum ada pergerakan.</td></tr>@endforelse</tbody></table></div></div>
@if($cartons->isNotEmpty())
<div class="card line-green"><div class="card-h"><h3>Karton ({{ $cartons->count() }}) · {{ \App\Support\Wms::fmt($cartons->sum('weight_kg'), 2) }} kg</h3></div><div class="card-b flush tbl-wrap"><table class="t">
  <thead><tr><th>Karton</th><th class="n">Kg</th><th>Status</th><th>Barcode</th></tr></thead>
  <tbody>@foreach($cartons as $c)<tr><td>{{ $c->name }}</td><td class="n">{{ \App\Support\Wms::fmt($c->weight_kg, 2) }}</td><td>{{ \App\Models\Carton::STATES[$c->state] ?? $c->state }}</td><td><small class="mono">{{ $c->raw_barcode }}</small></td></tr>@endforeach</tbody></table></div></div>
@endif
@endsection
