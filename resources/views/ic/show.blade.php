@extends('layouts.app')
@section('title', $c->number)
@section('crumb') Inventory <small>/ <a href="{{ route('ic.index') }}">Inventory Check</a> / {{ $c->number }}</small> @endsection
@php $draft = $c->state === 'draft'; @endphp
@section('content')
<div class="page-h"><div><h1>{{ $c->number }} @status($c->state)</h1><p>{{ $c->mode === 'lpn' ? 'Per LPN' : 'Per lokasi' }}: <b>{{ $c->title() }}</b> · @dt($c->date) · checker {{ $c->user?->name }}@if($c->approver) · approver {{ $c->approver->name }}@endif</p></div>
  <div class="sp"></div>
  @if(in_array($c->state, ['draft', 'rejected']))<form method="post" action="{{ route('ic.reset', $c) }}">@csrf<button class="btn ghost">↻ Muat ulang stok</button></form>@endif
</div>
<div class="steps"><span class="{{ $draft ? 'on' : 'done' }}">Cek fisik</span><span class="{{ $c->state === 'submitted' ? 'on' : (in_array($c->state, ['approved', 'rejected']) ? 'done' : '') }}">Menunggu approval</span><span class="{{ in_array($c->state, ['approved', 'rejected']) ? 'on' : '' }}">{{ $c->state === 'rejected' ? 'Rejected' : 'Approved & disesuaikan' }}</span></div>
<form method="post" action="{{ route('ic.submit', $c) }}">@csrf
<div class="card line-navy"><div class="card-b flush tbl-wrap"><table class="t">
  <thead><tr><th>Lokasi</th><th>Item</th><th>Lot</th><th>LPN</th><th class="n">Qty sistem</th><th class="n">Qty fisik</th><th class="n">Selisih</th><th>Expiry sistem</th><th>Expiry fisik</th><th>Catatan</th><th>Cetak ulang</th></tr></thead>
  <tbody>@forelse($c->lines as $l)
    <tr class="{{ $l->isDifference() ? 'warn' : '' }}"><td><small>{{ $l->location->full_name }}</small></td><td><b>{{ $l->item->code }}</b><br><small>{{ $l->item->name }}</small></td><td>{{ $l->lot?->name }}</td><td>{{ $l->lpn?->name }}</td>
      <td class="n">@qty($l->qty_before)</td>
      <td class="n">@if($draft)<input type="text" inputmode="decimal" name="rows[{{ $l->id }}][qty]" value="{{ \App\Support\Wms::fmt($l->qty_after) }}" style="width:80px;text-align:right">@else<b>@qty($l->qty_after)</b>@endif {{ $l->item->uom->code }}</td>
      <td class="n">{{ $l->difference() ? ($l->difference() > 0 ? '+' : '').\App\Support\Wms::fmt($l->difference()) : '' }}</td>
      <td>@d($l->expiry_before)</td><td>@if($draft)<input type="date" name="rows[{{ $l->id }}][expiry]" value="{{ optional($l->expiry_after)->format('Y-m-d') }}">@else @d($l->expiry_after) @endif</td>
      <td>@if($draft)<input type="text" name="rows[{{ $l->id }}][remark]" value="{{ $l->remark }}" style="width:130px">@else<small>{{ $l->remark }}</small>@endif</td>
      <td>@if($draft)<input type="checkbox" name="rows[{{ $l->id }}][reprint]" value="1" @checked($l->need_reprint)>@else{{ $l->need_reprint ? '✓' : '' }}@endif</td></tr>
  @empty <tr><td colspan="11" class="empty">Tidak ada stok pada objek ini.</td></tr> @endforelse</tbody>
</table></div></div>
@if($draft)
  <div class="card"><div class="card-b inline"><div class="f" style="flex:1"><label>Catatan</label><input type="text" name="remark" value="{{ $c->remark }}"></div><button class="btn red">Kirim untuk approval</button></div></div>
@endif
</form>
@if($c->remark && !$draft)<div class="alert warn" style="white-space:pre-line">{{ $c->remark }}</div>@endif
@if($c->state === 'submitted' && auth()->user()->isManager())
<div class="card line-green"><div class="card-b actions-row">
  <form method="post" action="{{ route('ic.approve', $c) }}" data-confirm="Setujui dan terapkan selisih ke stok?">@csrf<button class="btn green">✓ Approve ({{ $c->differenceCount() }} selisih)</button></form>
  <form method="post" action="{{ route('ic.reject', $c) }}" class="inline">@csrf<div class="f"><input type="text" name="reason" placeholder="alasan penolakan" required></div><button class="btn ghost">✕ Reject</button></form>
</div></div>
@endif
@endsection
