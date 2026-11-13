@extends('layouts.hht')
@section('title', 'Receiving')
@section('back', route('hht.home'))
@section('content')
<form method="get"><input type="text" name="q" class="scan" placeholder="scan / ketik No. PO, RCV, kontainer" value="{{ request('q') }}" data-scan></form>
<div style="margin-top:10px">@forelse($docs as $d)
  <a class="row" href="{{ route('hht.receipt', $d) }}"><b>{{ $d->number }}</b> · {{ $d->origin }}<br><small>{{ $d->partner?->name }} · {{ $d->lines_count }} item · {{ \App\Support\Ui::date($d->scheduled_at) }}{{ $d->container_no ? ' · '.$d->container_no : '' }}</small></a>
@empty <p class="muted">Tidak ada penerimaan terbuka.</p> @endforelse</div>
@endsection
