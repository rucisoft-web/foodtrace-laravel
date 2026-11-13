@extends('layouts.app')
@section('title', 'QC Hold / Release')
@section('crumb') Inbound <small>/ QC Hold / Release</small> @endsection
@section('content')
<div class="page-h"><div><h1>QC Hold / Release</h1><p>Barang karantina (auto karantina saat receiving atau ditahan QA), retur dan damage. Hanya stok Available yang bisa dipicking.</p></div>
  <div class="sp"></div><a class="btn ghost" href="{{ route('hht.hold') }}">▣ Hold dengan HHT</a></div>
<div class="grid g3">
  <div class="card line-red"><div class="card-h"><h3>Tahan LPN (QC Hold)</h3></div><div class="card-b">
    <form method="post" action="{{ route('qc.hold') }}">@csrf
      <div class="f"><label>LPN</label><input type="text" name="lpn" placeholder="scan LPN" required data-autofocus></div>
      <div class="f"><label>Alasan</label><input type="text" name="reason" placeholder="mis. kemasan bocor, suhu tidak sesuai, keluhan customer" required></div>
      <div class="f"><label>Lokasi tujuan (opsional)</label><input type="text" name="dest" placeholder="kosong = karantina zona item"><div class="hint">Bisa diisi lokasi RTN / DMG untuk retur atau rusak.</div></div>
      <button class="btn red">⚑ Tahan</button>
    </form>
  </div></div>
  <div class="card line-navy span2">
    <div class="card-h"><h3>Stok ditahan ({{ $rows->count() }})</h3><div class="sp"></div>
      <div class="tabs" style="margin:0;border:0">@foreach(['' => 'Semua', 'qc' => 'Karantina', 'rtn' => 'Retur', 'dmg' => 'Damage', 'wst' => 'Waste'] as $k => $l)<a href="{{ route('qc.index', array_filter(['area' => $k])) }}" class="{{ request('area', '') === $k ? 'on' : '' }}">{{ $l }}</a>@endforeach</div></div>
    <div class="card-b flush tbl-wrap"><table class="t">
      <thead><tr><th>Lokasi</th><th>LPN</th><th>Item</th><th>Lot · expiry</th><th class="n">Qty</th><th>Sejak</th><th></th></tr></thead>
      <tbody>@forelse($rows as $s)
        @php $at = $since[$s->lpn_id] ?? null; $days = $at ? (int) \Illuminate\Support\Carbon::parse($at)->diffInDays(now()) : null; @endphp
        <tr><td>{{ $s->location->name }} @status($s->status())</td><td>@if($s->lpn)<a href="{{ route('lpn.show', $s->lpn) }}">{{ $s->lpn->name }}</a>@endif</td>
          <td>@zone($s->item->zone) <b>{{ $s->item->code }}</b><br><small>{{ $s->item->name }}</small>@if($s->item->auto_release)<br><span class="badge b-green">auto release {{ $s->item->quarantine_days }} hr</span>@endif</td>
          <td>{{ $s->lot?->name }}<br><small>@d($s->lot?->expiry_date)</small></td><td class="n">@qty($s->qty) {{ $s->item->uom->code }}</td>
          <td>@if($at)@d($at)<br><small>{{ $days }} hari</small>@endif</td>
          <td>@if(auth()->user()->isManager())<a class="btn sm" href="{{ route('qc.release', $s) }}">Putuskan</a>@endif</td></tr>
      @empty <tr><td colspan="7" class="empty">Tidak ada stok yang ditahan.</td></tr> @endforelse</tbody>
    </table></div>
  </div>
</div>
@endsection
