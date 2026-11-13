@extends('layouts.app')
@section('title', $partner->exists ? $partner->name : 'Partner baru')
@section('crumb') Master Data <small>/ <a href="{{ route('partners.index', $partner->is_source && !$partner->is_destination ? 'source' : 'destination') }}">{{ $partner->is_source && !$partner->is_destination ? 'Source' : 'Destination' }} Master</a> / {{ $partner->code ?: 'Baru' }}</small> @endsection
@section('content')
<div class="page-h"><div><h1>{{ $partner->exists ? $partner->name : 'Partner baru' }}</h1>@if($partner->exists)<p>{{ $partner->code }} · {{ $partner->shipTos->count() }} ShipTo</p>@endif</div></div>
<div class="grid g3">
  <div class="card line-navy"><div class="card-h"><h3>Data partner</h3></div><div class="card-b">
    <form method="post" action="{{ route('partners.save', $partner->exists ? $partner : null) }}">@csrf
      <div class="f"><label>Kode SAP (CardCode) *</label><input type="text" name="code" value="{{ old('code', $partner->code) }}" required></div>
      <div class="f"><label>Nama *</label><input type="text" name="name" value="{{ old('name', $partner->name) }}" required></div>
      <label class="chk"><input type="checkbox" name="is_source" value="1" @checked(old('is_source', $partner->is_source))> Source (pemasok / pemilik titipan)</label>
      <label class="chk"><input type="checkbox" name="is_destination" value="1" @checked(old('is_destination', $partner->is_destination))> Destination (customer)</label>
      <div class="grid g2" style="gap:8px;margin-top:8px">
        <div class="f"><label>Toleransi over receive (%)</label><input type="text" name="over_receive_pct" value="{{ old('over_receive_pct', $partner->over_receive_pct) }}"></div>
        <div class="f"><label>Min. sisa umur kirim (%)</label><input type="number" name="min_shelf_life_pct" value="{{ old('min_shelf_life_pct', $partner->min_shelf_life_pct) }}" min="0" max="100"></div>
        <div class="f"><label>Kode owner (titipan)</label><input type="text" name="owner_code" value="{{ old('owner_code', $partner->owner_code) }}"></div>
        <div class="f"><label>Rute (shipping group)</label><input type="text" name="shipping_group" value="{{ old('shipping_group', $partner->shipping_group) }}"></div>
      </div>
      <div class="f"><label>Alamat</label><input type="text" name="address" value="{{ old('address', $partner->address) }}"></div>
      <div class="grid g2" style="gap:8px"><div class="f"><label>Kota</label><input type="text" name="city" value="{{ old('city', $partner->city) }}"></div><div class="f"><label>Telepon</label><input type="text" name="phone" value="{{ old('phone', $partner->phone) }}"></div></div>
      <label class="chk"><input type="checkbox" name="active" value="1" @checked(old('active', $partner->active ?? true))> Aktif</label>
      <button class="btn red">Simpan</button>
    </form>
  </div></div>
  <div class="span2">
  @if($partner->exists && $partner->is_destination)
    <div class="card line-red"><div class="card-h"><h3>ShipTo / outlet ({{ $partner->shipTos->count() }})</h3></div>
      <div class="card-b flush tbl-wrap" style="max-height:420px;overflow:auto"><table class="t"><thead><tr><th>Nama outlet</th><th>Kode</th><th>Alamat</th><th>Kota</th><th></th></tr></thead><tbody>
      @forelse($partner->shipTos as $s)<tr><td><b>{{ $s->name }}</b></td><td>{{ $s->code }}</td><td><small>{{ $s->address }}</small></td><td>{{ $s->city }}</td>
        <td class="actions-row"><a class="btn ghost sm" href="#" onclick="let f=document.getElementById('sf');f.id.value='{{ $s->id }}';['name','code','address','city','phone'].forEach(k=>f[k].value=this.dataset[k]||'');return false" data-name="{{ $s->name }}" data-code="{{ $s->code }}" data-address="{{ $s->address }}" data-city="{{ $s->city }}" data-phone="{{ $s->phone }}">Ubah</a>
          <form method="post" action="{{ route('partners.shipto', $partner) }}" data-confirm="Hapus ShipTo {{ $s->name }}?">@csrf<input type="hidden" name="delete" value="{{ $s->id }}"><button class="btn ghost sm">✕</button></form></td></tr>
      @empty<tr><td colspan="5" class="empty">Belum ada ShipTo. ShipTo juga dibuat otomatis dari SO SAP (destination_outlet).</td></tr>@endforelse</tbody></table></div>
      <div class="card-b"><form method="post" action="{{ route('partners.shipto', $partner) }}" id="sf" class="grid g4">@csrf<input type="hidden" name="id">
        <div class="f span2"><label>Nama outlet *</label><input type="text" name="name" required></div><div class="f"><label>Kode</label><input type="text" name="code"></div><div class="f"><label>Telepon</label><input type="text" name="phone"></div>
        <div class="f span2"><label>Alamat</label><input type="text" name="address"></div><div class="f"><label>Kota</label><input type="text" name="city"></div><div class="f" style="align-self:end"><button class="btn red">Simpan ShipTo</button></div></form></div>
    </div>
  @endif
  @if($docs->isNotEmpty())
    <div class="card line-navy"><div class="card-h"><h3>Dokumen terakhir</h3></div><div class="card-b flush"><table class="t">@foreach($docs as $d)<tr><td><a href="{{ $d->show() }}">{{ $d->number }}</a></td><td>{{ $d->origin }}</td><td>{{ $d->typeLabel() }}</td><td>@d($d->scheduled_at)</td><td>@status($d->state)</td></tr>@endforeach</table></div></div>
  @endif
  </div>
</div>
@endsection
