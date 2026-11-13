@extends('layouts.app')
@section('title', 'LPN List')
@section('crumb') Inventory <small>/ LPN List</small> @endsection
@section('content')
<div class="page-h">
  <div><h1>LPN List</h1><p>License Plate Number per palet (1 SKU + 1 lot). Cetak ulang label untuk LPN yang ditandai setelah Inventory Check.</p></div>
  <div class="sp"></div>
  @if($reprint)<a class="btn red" href="{{ route('lpn.labels', ['reprint' => 1]) }}" target="_blank">⎙ Cetak ulang {{ $reprint }} label</a>@endif
</div>
<form class="filters" method="get">
  <div class="f"><label>Cari</label><input type="search" name="q" value="{{ request('q') }}" placeholder="LPN / item / lot" data-autofocus></div>
  <div class="f"><label>Zona</label><select name="zone" data-submit><option value="">Semua</option>@foreach(\App\Support\Wms::ZONES as $k => $l)<option value="{{ $k }}" @selected(request('zone') === $k)>{{ $l }}</option>@endforeach</select></div>
  <label class="chk"><input type="checkbox" name="reprint" value="1" @checked(request('reprint')) data-submit> Perlu cetak ulang</label>
  <label class="chk"><input type="checkbox" name="empty" value="1" @checked(request('empty')) data-submit> Termasuk LPN kosong</label>
  <button class="btn">Tampilkan</button>
</form>
<form method="get" action="{{ route('lpn.labels') }}" target="_blank" id="lblform">
<div class="card"><div class="card-h"><h3>{{ number_format($rows->total()) }} LPN</h3><div class="sp"></div><button class="btn ghost sm" onclick="this.form.ids.value=[...document.querySelectorAll('.pick:checked')].map(x=>x.value).join(',')">⎙ Cetak label terpilih</button><input type="hidden" name="ids"></div>
<div class="card-b flush tbl-wrap"><table class="t">
  <thead><tr><th><input type="checkbox" data-check-all=".pick"></th><th>LPN</th><th>Item</th><th>Lot</th><th>Expiry</th><th>Lokasi</th><th>Status</th><th class="n">Qty</th><th class="n">Karton</th><th>No. palet</th><th></th></tr></thead>
  <tbody>@forelse($rows as $p)
    @php $s = $locs[$p->id] ?? null; @endphp
    <tr><td><input type="checkbox" class="pick" value="{{ $p->id }}"></td><td><a href="{{ route('lpn.show', $p) }}"><b>{{ $p->name }}</b></a></td>
      <td>@zone($p->item?->zone) {{ $p->item?->code }}<br><small>{{ $p->item?->name }}</small></td><td>{{ $p->lot?->name }}</td><td>@d($p->lot?->expiry_date)</td>
      <td><small>{{ $s?->location->full_name }}</small></td><td>@if($s)@status($s->status())@else<span class="badge b-grey">Kosong</span>@endif</td>
      <td class="n">@qty($p->qty ?? 0) {{ $p->item?->uom?->code }}</td><td class="n">{{ $p->cartons_count ?: '' }}</td><td>{{ $p->pallet_no }}</td>
      <td>@if($p->need_reprint)<span class="badge b-red">cetak ulang</span>@endif</td></tr>
  @empty <tr><td colspan="11" class="empty">Tidak ada LPN.</td></tr> @endforelse</tbody>
</table></div></div>
</form>
{{ $rows->links() }}
@endsection
