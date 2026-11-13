@extends('layouts.hht')
@section('title', 'Cycle count')
@section('back', $loc ? route('hht.count') : route('hht.home'))
@section('content')
@if(!$loc)
  <form method="get"><label>Scan lokasi</label><input type="text" name="location" class="scan" data-scan autocomplete="off"></form>
  <p class="muted">Hitung buta: qty sistem tidak ditampilkan.</p>
@else
  <div class="row"><b>{{ $loc->full_name }}</b></div>
  <form method="post">@csrf
  @forelse($stocks as $s)<div class="row"><b>{{ $s->item->code }}</b> {{ $s->item->name }}<br><small>{{ $s->location->name }} · lot {{ $s->lot?->name }} · {{ $s->lpn?->name }}</small>
    <div class="f" style="margin-top:6px"><label>Hasil hitung ({{ $s->item->uom->code }})</label><input type="text" inputmode="decimal" name="counts[{{ $s->id }}]"></div></div>
  @empty <p class="muted">Tidak ada stok di lokasi ini.</p> @endforelse
  @if($stocks->isNotEmpty())<button class="btn red block">Simpan hitungan</button>@endif</form>
@endif
@endsection
