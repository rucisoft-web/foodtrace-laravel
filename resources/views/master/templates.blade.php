@extends('layouts.app')
@section('title', 'Format barcode karton')
@section('crumb') Master Data <small>/ Format barcode karton</small> @endsection
@php $e = $edit; @endphp
@section('content')
<div class="page-h"><div><h1>Format barcode karton</h1><p>Template untuk membaca berat, lot, expiry dan nomor karton dari barcode pemasok: GS1-128 (AI 3102 / 3202, 10, 17, 21) atau pola regex posisi tetap. Pasangkan template di Item Master.</p></div></div>
<div class="grid g3">
  <div class="span2">
    <div class="card line-navy"><div class="card-b flush"><table class="t"><thead><tr><th>#</th><th>Nama</th><th>Jenis</th><th>Pola</th><th>Satuan</th><th class="n">Item</th><th>Contoh</th><th></th></tr></thead><tbody>
      @foreach($rows as $t)<tr><td>{{ $t->sequence }}</td><td><b>{{ $t->name }}</b>{!! $t->active ? '' : ' <span class="badge b-grey">nonaktif</span>' !!}</td><td>{{ strtoupper($t->kind) }}</td><td class="mono"><small>{{ $t->pattern }}</small></td>
        <td>{{ $t->weight_unit }} ({{ $t->weight_decimals }} des.)</td><td class="n">{{ $t->items_count }}</td><td class="mono"><small>{{ $t->sample }}</small></td><td><a class="btn ghost sm" href="{{ route('templates.index', ['edit' => $t->id]) }}">Ubah</a></td></tr>@endforeach
    </tbody></table></div></div>
    <div class="card line-green"><div class="card-h"><h3>Uji baca barcode</h3></div><div class="card-b">
      <form method="post" action="{{ route('templates.test') }}" class="inline">@csrf
        <div class="f"><label>Template</label><select name="id">@foreach($rows as $t)<option value="{{ $t->id }}" @selected(($result['template'] ?? '') === $t->name)>{{ $t->name }}</option>@endforeach</select></div>
        <div class="f" style="flex:1"><label>Barcode (scan)</label><input type="text" name="barcode" value="{{ $result['barcode'] ?? $rows->first()?->sample }}" required></div><button class="btn">Uji</button></form>
      @if($result)
        @if($result['ok'])<div class="alert ok" style="margin-top:10px">Terbaca: berat <b>{{ $result['data']['weight_kg'] }} kg</b>{{ $result['data']['weight_lb'] ? ' ('.$result['data']['weight_lb'].' lb)' : '' }} · lot <b>{{ $result['data']['lot'] ?? '-' }}</b> · expiry <b>{{ $result['data']['expiry'] ?? '-' }}</b> · karton <b>{{ $result['data']['carton'] ?? '-' }}</b></div>
        @else<div class="alert bad" style="margin-top:10px">{{ $result['error'] }}</div>@endif
      @endif
    </div></div>
  </div>
  <div class="card line-red"><div class="card-h"><h3>{{ $e ? 'Ubah template' : 'Template baru' }}</h3></div><div class="card-b">
    <form method="post" action="{{ route('templates.save') }}">@csrf<input type="hidden" name="id" value="{{ $e?->id }}">
      <div class="f"><label>Nama *</label><input type="text" name="name" value="{{ $e?->name }}" required></div>
      <div class="grid g2" style="gap:8px">
        <div class="f"><label>Jenis</label><select name="kind"><option value="gs1">GS1-128</option><option value="regex" @selected($e?->kind === 'regex')>Regex</option></select></div>
        <div class="f"><label>Urutan</label><input type="number" name="sequence" value="{{ $e?->sequence ?? 10 }}"></div>
        <div class="f"><label>Satuan berat</label><select name="weight_unit"><option value="kg">kg</option><option value="lb" @selected($e?->weight_unit === 'lb')>lb (dikonversi ke kg)</option></select></div>
        <div class="f"><label>Desimal berat</label><input type="number" name="weight_decimals" value="{{ $e?->weight_decimals ?? 2 }}" min="0" max="5"></div>
        <div class="f"><label>Berat min (kg)</label><input type="text" name="min_weight" value="{{ $e?->min_weight }}"></div>
        <div class="f"><label>Berat maks (kg)</label><input type="text" name="max_weight" value="{{ $e?->max_weight }}"></div>
      </div>
      <div class="f"><label>Pola regex</label><input type="text" name="pattern" value="{{ $e?->pattern }}" class="mono" placeholder="^(?P<carton>\d{6})(?P<weight>\d{4})(?P<lot>\w{5})$"><div class="hint">Grup bernama: weight, lot, expiry (YYMMDD), carton.</div></div>
      <div class="f"><label>Contoh barcode</label><input type="text" name="sample" value="{{ $e?->sample }}" class="mono"></div>
      <label class="chk"><input type="checkbox" name="active" value="1" @checked($e?->active ?? true)> Aktif</label>
      <button class="btn red">Simpan template</button>
    </form>
  </div></div>
</div>
@endsection
