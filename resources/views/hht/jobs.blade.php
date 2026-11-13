@extends('layouts.hht')
@section('title', 'Picking job')
@section('back', route('hht.home'))
@section('content')
@forelse($jobs as $j)<a class="row" href="{{ route('hht.pick', $j) }}"><b>{{ $j->number }}</b> · {{ $j->documents_count }} dokumen<br><small>{{ $j->description }} · siap {{ $j->readyPct() }}%</small><div class="progress" style="margin-top:4px"><i style="width:{{ $j->readyPct() }}%"></i></div></a>
@empty <p class="muted">Tidak ada picking job berjalan.</p> @endforelse
@endsection
