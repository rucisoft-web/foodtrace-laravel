@extends('layouts.app')
@section('title', 'Dashboard')
@section('crumb', 'Dashboard gudang')
@push('head')<script src="{{ asset('vendor/chart.umd.js') }}"></script>@endpush
@php use App\Support\Wms; $k = $d['kpi']; @endphp
@section('content')
<div class="page-h">
  <div><h1>Dashboard gudang</h1><p>{{ \App\Models\Warehouse::orderBy('id')->value('name') }} · data per {{ now()->translatedFormat('d M Y H:i') }}</p></div>
  <div class="sp"></div>
  <div class="tabs" style="margin:0;border:0">
    <a href="{{ route('dashboard') }}" class="{{ $zone ? '' : 'on' }}">Semua zona</a>
    @foreach(Wms::ZONES as $z => $l)<a href="{{ route('dashboard', ['zone' => $z]) }}" class="{{ $zone === $z ? 'on' : '' }}">{{ $l }}</a>@endforeach
  </div>
</div>

<div class="grid g6" style="margin-bottom:14px">
  <div class="kpi"><div class="l">Nilai stok</div><div class="v">{{ Wms::moneyShort($k['value']) }}</div><div class="s">{{ number_format($k['items']) }} item · {{ number_format($k['lots']) }} lot · {{ number_format($k['lpns']) }} LPN</div></div>
  <div class="kpi red"><div class="l">Kedaluwarsa</div><div class="v"><a href="{{ route('reports.show', 'expired') }}">{{ Wms::moneyShort($k['expired']) }}</a></div><div class="s">masih di gudang, diblokir</div></div>
  <div class="kpi amber"><div class="l">Expired ≤ 30 hari</div><div class="v">{{ Wms::moneyShort($k['near']) }}</div><div class="s">≤ 90 hari: {{ Wms::money($k['m3']) }}</div></div>
  <div class="kpi brown"><div class="l">Okupansi rak</div><div class="v">{{ $k['occ_pct'] }}%</div><div class="s">posisi palet utuh terisi</div></div>
  <div class="kpi green"><div class="l">Pekerjaan terbuka</div><div class="v"><a href="{{ route('inbound.index') }}">{{ $k['open_in'] }}</a> / <a href="{{ route('outbound.index') }}">{{ $k['today_out'] }}</a></div><div class="s">PO belum diterima / SO jatuh tempo · {{ $k['short_out'] }} short</div></div>
  <div class="kpi {{ $k['sap_failed'] ? 'red' : '' }}"><div class="l">SAP B1</div><div class="v"><a href="{{ route('sap.queue') }}">{{ $k['sap_failed'] }} gagal</a></div><div class="s">{{ $k['sap_queued'] }} antre · {{ number_format($k['sap_posted']) }} terkirim</div></div>
</div>

<div class="grid g3">
  <div class="card line-red span2">
    <div class="card-h"><h3>Perlu keputusan hari ini</h3><div class="sp"></div><span class="badge b-red">{{ count($d['decisions']) }}</span></div>
    <div class="card-b flush">
      <table class="t">@forelse($d['decisions'] as $x)
        <tr><td style="width:10px"><span class="badge b-{{ $x['level'] }}">●</span></td><td>{{ $x['text'] }}</td><td class="n"><a class="btn ghost sm" href="{{ $x['url'] }}">Tindak lanjut →</a></td></tr>
      @empty <tr><td class="empty">Tidak ada yang perlu diputuskan. Gudang dalam kondisi baik.</td></tr> @endforelse</table>
    </div>
  </div>
  <div class="card line-navy"><div class="card-h"><h3>Nilai stok per zona</h3></div><div class="card-b"><div class="chart-box"><canvas id="cZone"></canvas></div></div></div>
</div>

<div class="grid g3">
  <div class="card line-navy span2"><div class="card-h"><h3>Arus barang 30 hari (nilai masuk vs keluar)</h3></div><div class="card-b"><div class="chart-box"><canvas id="cFlow"></canvas></div></div></div>
  <div class="card line-brown"><div class="card-h"><h3>Status stok (nilai)</h3></div><div class="card-b"><div class="chart-box"><canvas id="cStatus"></canvas></div></div></div>
</div>

<div class="grid g3">
  <div class="card line-red span2"><div class="card-h"><h3>Profil kedaluwarsa per zona</h3><div class="sp"></div><small>nilai stok per sisa umur</small></div><div class="card-b"><div class="chart-box"><canvas id="cExpiry"></canvas></div></div></div>
  <div class="card line-green"><div class="card-h"><h3>Okupansi rak palet per zona</h3></div><div class="card-b"><div class="chart-box"><canvas id="cOcc"></canvas></div></div></div>
</div>

<div class="grid g3">
  <div class="card line-navy"><div class="card-h"><h3>Pipeline outbound</h3><div class="sp"></div><small>terbuka + 7 hari</small></div><div class="card-b"><div class="chart-box"><canvas id="cStage"></canvas></div></div></div>
  <div class="card line-brown"><div class="card-h"><h3>Pengiriman per rute (30 hari)</h3></div><div class="card-b"><div class="chart-box"><canvas id="cGroup"></canvas></div></div></div>
  <div class="card line-red"><div class="card-h"><h3>Posting SAP B1</h3></div><div class="card-b"><div class="chart-box"><canvas id="cSap"></canvas></div></div></div>
</div>

<div class="grid g3">
  <div class="card line-navy"><div class="card-h"><h3>10 item nilai stok terbesar</h3></div><div class="card-b"><div class="chart-box tall"><canvas id="cTop"></canvas></div></div></div>
  <div class="card line-green"><div class="card-h"><h3>Nilai per item group</h3></div><div class="card-b"><div class="chart-box tall"><canvas id="cGroupVal"></canvas></div></div></div>
  <div class="card line-brown"><div class="card-h"><h3>10 pelanggan teratas (30 hari)</h3></div><div class="card-b"><div class="chart-box tall"><canvas id="cCust"></canvas></div></div></div>
</div>

<div class="grid g3">
  <div class="card line-navy"><div class="card-h"><h3>Brand SAP terbesar (nilai stok)</h3></div><div class="card-b"><div class="chart-box"><canvas id="cBrand"></canvas></div></div></div>
  <div class="card line-green"><div class="card-h"><h3>Pemasok (nilai terima 90 hari)</h3></div><div class="card-b"><div class="chart-box"><canvas id="cSupp"></canvas></div></div></div>
  <div class="card line-red"><div class="card-h"><h3>Dokumen per hari (30 hari)</h3></div><div class="card-b"><div class="chart-box"><canvas id="cDocs"></canvas></div></div></div>
</div>

<div class="grid g2">
  <div class="card line-red">
    <div class="card-h"><h3>Kedaluwarsa ≤ 30 hari & sudah lewat</h3><div class="sp"></div><a href="{{ route('reports.show', 'expired') }}">laporan lengkap →</a></div>
    <div class="card-b flush tbl-wrap"><table class="t">
      <thead><tr><th>Item</th><th>Lot</th><th>Lokasi</th><th class="n">Sisa</th><th class="n">Qty</th><th class="n">Nilai</th></tr></thead>
      <tbody>@forelse($d['near'] as $n)
        <tr class="{{ $n['days'] < 0 ? 'bad' : ($n['days'] <= 14 ? 'warn' : '') }}"><td>@zone($n['zone']) <b>{{ $n['code'] }}</b><br><small>{{ $n['name'] }}</small></td>
          <td>{{ $n['lot'] }}</td><td><small>{{ $n['loc'] }}</small></td><td class="n">{{ $n['days'] < 0 ? 'lewat '.abs($n['days']).' hr' : $n['days'].' hr' }}</td>
          <td class="n">{{ Wms::fmt($n['qty']) }} {{ $n['uom'] }}</td><td class="n">{{ Wms::money($n['value']) }}</td></tr>
      @empty <tr><td colspan="6" class="empty">Tidak ada.</td></tr> @endforelse</tbody></table></div>
  </div>
  <div class="card line-brown">
    <div class="card-h"><h3>Item di bawah stok minimum</h3><div class="sp"></div><a href="{{ route('reports.show', 'item-low') }}">semua ({{ $k['low'] }}) →</a></div>
    <div class="card-b flush tbl-wrap"><table class="t">
      <thead><tr><th>Item</th><th class="n">Stok</th><th class="n">Minimum</th><th>Cukup</th></tr></thead>
      <tbody>@forelse($d['low'] as $l)
        <tr><td>@zone($l->zone) <b>{{ $l->code }}</b><br><small>{{ $l->name }}</small></td><td class="n">{{ Wms::fmt($l->qty) }} {{ $l->uom }}</td><td class="n">{{ Wms::fmt($l->min_stock) }}</td>
          <td style="width:120px"><div class="progress"><i style="width:{{ min(100, $l->min_stock ? $l->qty / $l->min_stock * 100 : 0) }}%"></i></div></td></tr>
      @empty <tr><td colspan="4" class="empty">Semua item di atas minimum.</td></tr> @endforelse</tbody></table></div>
  </div>
</div>
@endsection
@push('scripts')
<script>
const D = @json($d);
const C = FT.colors, rp = FT.rp, sh = FT.short;
const money = { ticks: { callback: (v) => sh(v) } };
const tip = { callbacks: { label: (c) => (c.dataset.label ? c.dataset.label + ': ' : '') + rp(c.raw) } };
new Chart(cZone, { type: 'doughnut', data: { labels: D.zones.map(z => z.label), datasets: [{ data: D.zones.map(z => z.value), backgroundColor: D.zones.map(z => z.color), borderWidth: 2 }] },
  options: { cutout: '62%', plugins: { tooltip: { callbacks: { label: (c) => c.label + ': ' + rp(c.raw) } }, legend: { position: 'bottom' } } } });
new Chart(cFlow, { data: { labels: D.flow.days, datasets: [
  { type: 'bar', label: 'Masuk', data: D.flow.in, backgroundColor: '#1b2a4e', borderRadius: 2 },
  { type: 'bar', label: 'Keluar', data: D.flow.out, backgroundColor: '#7f1d2d', borderRadius: 2 },
  { type: 'line', label: 'Selisih kumulatif', data: D.flow.in.reduce((a, v, i) => (a.push((a[i - 1] || 0) + v - D.flow.out[i]), a), []), borderColor: '#7aa83a', backgroundColor: '#7aa83a', tension: .3, pointRadius: 0, yAxisID: 'y' },
] }, options: { scales: { y: money, x: { ticks: { maxRotation: 0, autoSkip: true, maxTicksLimit: 15 } } }, plugins: { tooltip: tip } } });
new Chart(cStatus, { type: 'bar', data: { labels: D.status.map(s => s.label), datasets: [{ data: D.status.map(s => s.value), backgroundColor: ['#24386a', '#7aa83a', '#47302a', '#b7791f', '#7f1d2d'] }] },
  options: { indexAxis: 'y', plugins: { legend: { display: false }, tooltip: tip }, scales: { x: money } } });
new Chart(cExpiry, { type: 'bar', data: { labels: D.expiry.labels, datasets: D.zoneKeys.map((z, i) => ({ label: D.zones[i].label, data: D.expiry.by_zone[z], backgroundColor: D.zones[i].color, stack: 's' })) },
  options: { scales: { x: { stacked: true }, y: Object.assign({ stacked: true }, money) }, plugins: { tooltip: tip } } });
new Chart(cOcc, { type: 'bar', data: { labels: D.zones.map(z => z.label), datasets: [
  { label: 'Terisi', data: D.zones.map(z => z.occ), backgroundColor: D.zones.map(z => z.color), stack: 'o' },
  { label: 'Kosong', data: D.zones.map(z => Math.max(0, z.cap - z.occ)), backgroundColor: '#e5e7eb', stack: 'o' }] },
  options: { scales: { x: { stacked: true }, y: { stacked: true } }, plugins: { tooltip: { callbacks: { footer: (it) => { const z = D.zones[it[0].dataIndex]; return z.cap ? Math.round(z.occ / z.cap * 100) + '% terisi' : ''; } } } } } });
new Chart(cStage, { type: 'bar', data: { labels: D.stages.map(s => s.label), datasets: [{ label: 'Dokumen', data: D.stages.map(s => s.value), backgroundColor: ['#9aa6c4', '#24386a', '#1b2a4e', '#0f766e', '#b7791f', '#7f1d2d', '#7aa83a'] }] },
  options: { indexAxis: 'y', plugins: { legend: { display: false } } } });
new Chart(cGroup, { type: 'pie', data: { labels: Object.keys(D.shipGroups), datasets: [{ data: Object.values(D.shipGroups), backgroundColor: C }] }, options: { plugins: { legend: { position: 'right' } } } });
new Chart(cSap, { type: 'doughnut', data: { labels: Object.keys(D.sap), datasets: [{ data: Object.values(D.sap), backgroundColor: ['#7aa83a', '#24386a', '#7f1d2d'] }] }, options: { cutout: '60%', plugins: { legend: { position: 'bottom' } } } });
const hbar = (el, labels, data, color, label) => new Chart(el, { type: 'bar', data: { labels, datasets: [{ label, data, backgroundColor: color, borderRadius: 2 }] },
  options: { indexAxis: 'y', plugins: { legend: { display: false }, tooltip: tip }, scales: { x: money, y: { ticks: { callback: function (v) { const s = this.getLabelForValue(v); return s.length > 26 ? s.slice(0, 25) + '…' : s; } } } } } });
hbar(cTop, D.top.map(t => t.code + ' ' + t.name), D.top.map(t => Math.round(t.value)), D.top.map(t => FT.zone[t.zone]), 'Nilai');
hbar(cGroupVal, D.groups.map(g => g.name), D.groups.map(g => g.value), '#7aa83a', 'Nilai');
hbar(cCust, D.customers.map(c => c.name), D.customers.map(c => Math.round(c.v)), '#47302a', 'Nilai kirim');
hbar(cBrand, D.brands.map(b => b.name), D.brands.map(b => b.value), '#24386a', 'Nilai');
hbar(cSupp, D.suppliers.map(s => s.name), D.suppliers.map(s => Math.round(s.v)), '#557a22', 'Nilai terima');
new Chart(cDocs, { type: 'line', data: { labels: D.flow.days, datasets: [
  { label: 'Penerimaan', data: D.flow.in_docs, borderColor: '#1b2a4e', backgroundColor: 'rgba(27,42,78,.12)', fill: true, tension: .3, pointRadius: 1 },
  { label: 'Pengiriman', data: D.flow.out_docs, borderColor: '#7f1d2d', backgroundColor: 'rgba(127,29,45,.10)', fill: true, tension: .3, pointRadius: 1 }] },
  options: { scales: { x: { ticks: { maxTicksLimit: 10, maxRotation: 0 } } } } });
</script>
@endpush
