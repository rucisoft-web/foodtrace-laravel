@extends('layouts.app')
@section('title', 'Loading Job')
@section('crumb') Outbound <small>/ Loading Job</small> @endsection
@section('content')
<div class="page-h"><div><h1>Loading Job</h1><p>Kelompokkan dokumen yang sudah dicek ke satu kendaraan. Setiap LPN discan saat dimuat; tutup loading = Goods Issue, POD dan surat jalan.</p></div>
  <div class="sp"></div><a class="btn ghost" href="{{ route('hht.loadings') }}">▣ Muat dengan HHT</a></div>
<div class="grid g3">
<div class="card line-red span2">
  <div class="card-h"><h3>Siap dimuat ({{ $ready->count() }})</h3></div>
  <form method="post" action="{{ route('loading.store') }}">@csrf
  <div class="card-b flush tbl-wrap" style="max-height:340px;overflow:auto"><table class="t">
    <thead><tr><th><input type="checkbox" data-check-all=".ld"></th><th>Dokumen</th><th>Customer / outlet</th><th>Rute</th><th>Kirim</th></tr></thead>
    <tbody>@forelse($ready as $d)<tr><td><input type="checkbox" class="ld" name="ids[]" value="{{ $d->id }}"></td><td><a href="{{ route('outbound.show', $d) }}">{{ $d->number }}</a></td><td>{{ $d->partner?->name }}<br><small>{{ $d->shipTo?->name }}</small></td><td>{{ $d->shipping_group }}</td><td>@d($d->scheduled_at)</td></tr>
    @empty<tr><td colspan="5" class="empty">Belum ada dokumen berstatus Sudah dicek.</td></tr>@endforelse</tbody></table></div>
  <div class="card-b inline"><div class="f"><label>No. polisi</label><input type="text" name="vehicle_plate" required placeholder="B 1234 XYZ"></div><div class="f"><label>Pengemudi</label><input type="text" name="driver" required></div>
    <div class="f"><label>Tanggal</label><input type="date" name="date" value="{{ now()->toDateString() }}"></div><button class="btn red">⛟ Buat loading job</button></div>
  </form>
</div>
<div class="card line-navy"><div class="card-h"><h3>Loading job</h3><div class="sp"></div>
  <div class="tabs" style="margin:0;border:0">@foreach(['open' => 'Open', 'close' => 'Close', 'all' => 'Semua'] as $k => $l)<a href="{{ route('loading.index', ['state' => $k]) }}" class="{{ $state === $k ? 'on' : '' }}">{{ $l }}</a>@endforeach</div></div>
  <div class="card-b flush"><table class="t">@forelse($jobs as $j)
    <tr><td><a href="{{ route('loading.show', $j) }}"><b>{{ $j->number }}</b></a><br><small>@d($j->date) · {{ $j->vehicle_plate }} · {{ $j->driver }}</small></td><td class="n">{{ $j->documents_count }} dok</td><td>@status($j->state)</td></tr>
  @empty<tr><td class="empty">Tidak ada.</td></tr>@endforelse</table></div>
</div>
</div>
{{ $jobs->links() }}
@endsection
