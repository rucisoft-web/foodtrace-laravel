@extends('layouts.app')
@section('title', 'Butcher '.($kind === 'in' ? 'In' : 'Out').' baru')
@section('crumb') Outbound <small>/ <a href="{{ route('butcher.index', ['kind' => $kind]) }}">Butcher / Repack</a> / Baru</small> @endsection
@section('content')
<div class="page-h"><div><h1>Butcher {{ $kind === 'in' ? 'In (hasil potong / repack)' : 'Out (bahan ke ruang potong)' }}</h1>
  <p>{{ $kind === 'in' ? 'Setiap baris hasil menjadi lot dan LPN baru di bin tujuan. Bin harus sesuai zona item.' : 'Scan LPN bahan dan isi qty yang dipotong. Stok keluar ke Ruang potong.' }}</p></div></div>
<form method="post" action="{{ route('butcher.store') }}">@csrf<input type="hidden" name="kind" value="{{ $kind }}">
<div class="card line-navy"><div class="card-b grid g3">
  <div class="f"><label>Referensi (SO / work order)</label><input type="text" name="ref" value="{{ old('ref') }}"></div>
  @if($kind === 'in')<div class="f"><label>Bahan dari Butcher Out</label><select name="source_id"><option value="">—</option>@foreach($sources as $s)<option value="{{ $s->id }}" @selected(request('source') == $s->id)>{{ $s->number }} · {{ \App\Support\Wms::fmt($s->total_kg, 2) }} kg · @d($s->date)</option>@endforeach</select><div class="hint">Untuk menghitung yield.</div></div>@endif
</div></div>
<div class="card line-red"><div class="card-b flush tbl-wrap"><table class="t">
  <thead><tr><th>#</th><th>Proses</th>@if($kind === 'out')<th>LPN bahan</th>@else<th>Item hasil</th><th>Bin tujuan</th><th>Lot</th><th>Expiry</th><th>Grade</th><th>Cutting</th>@endif<th>Qty</th></tr></thead>
  <tbody>@for($i = 0; $i < 6; $i++)
    <tr><td>{{ $i + 1 }}</td><td><select name="lines[{{ $i }}][type]">@foreach(\App\Models\Butcher::TYPES as $k => $l)<option value="{{ $k }}">{{ $l }}</option>@endforeach</select></td>
      @if($kind === 'out')<td><input type="text" name="lines[{{ $i }}][code]" placeholder="scan LPN" style="width:160px"></td>
      @else<td><input type="text" name="lines[{{ $i }}][code]" placeholder="item code" style="width:130px"></td>
        <td><input type="text" name="lines[{{ $i }}][location]" list="bins" placeholder="bin" style="width:130px"></td><td><input type="text" name="lines[{{ $i }}][lot]" placeholder="otomatis" style="width:100px"></td>
        <td><input type="date" name="lines[{{ $i }}][expiry]"></td><td><input type="text" name="lines[{{ $i }}][grade]" style="width:70px"></td><td><input type="text" name="lines[{{ $i }}][cutting]" style="width:90px"></td>@endif
      <td><input type="text" name="lines[{{ $i }}][qty]" inputmode="decimal" style="width:80px"></td></tr>
  @endfor</tbody>
</table></div></div>
@if($kind === 'in')<datalist id="bins">@foreach($bins as $b)<option value="{{ $b->barcode ?: $b->full_name }}">{{ $b->full_name }}</option>@endforeach</datalist>@endif
<div class="actions-row"><button class="btn ghost">Simpan draft</button><button class="btn red" name="done" value="1">✓ Simpan & selesaikan</button></div>
</form>
@endsection
