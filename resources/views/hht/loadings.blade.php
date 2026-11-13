@extends('layouts.hht')
@section('title', 'Loading')
@section('back', route('hht.home'))
@section('content')
@forelse($jobs as $j)<a class="row" href="{{ route('hht.loading', $j) }}"><b>{{ $j->number }}</b> · {{ $j->vehicle_plate }}<br><small>{{ $j->driver }} · {{ $j->documents_count }} dokumen</small></a>
@empty <p class="muted">Tidak ada loading job terbuka. Buat dari web (Loading Job).</p> @endforelse
@endsection
