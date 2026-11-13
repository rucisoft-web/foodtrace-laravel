<!doctype html>
<html lang="id">
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<meta name="csrf-token" content="{{ csrf_token() }}">
<title>@yield('title', 'FoodTrace') · FoodTrace</title>
<link rel="icon" href="{{ asset('favicon.png') }}">
<link rel="stylesheet" href="{{ asset('css/foodtrace.css') }}?v={{ config('foodtrace.version') }}">
@stack('head')
</head>
<body>
@php
    $me = auth()->user();
    $nav = fn (...$r) => request()->routeIs(...$r) ? 'on' : '';
    $cnt = \App\Support\NavCounts::get();
@endphp
<div class="app">
  <aside class="side" id="side">
    <a class="brand" href="{{ route('dashboard') }}">
      <img src="{{ asset('img/logo-saranakulina.png') }}" alt="Saranakulina">
      <div class="app-name"><b>FoodTrace</b> · WMS gudang pangan</div>
    </a>
    <nav>
      <a href="{{ route('dashboard') }}" class="{{ $nav('dashboard') }}"><span class="ic">▦</span>Dashboard</a>
      <a href="{{ route('hht.home') }}"><span class="ic">▣</span>Handheld (HHT)</a>

      <div class="grp">Inbound</div>
      <a href="{{ route('inbound.index') }}" class="{{ $nav('inbound.*') }}"><span class="ic">⇩</span>Inbound Document @if($cnt['receipts'])<span class="cnt">{{ $cnt['receipts'] }}</span>@endif</a>
      <a href="{{ route('cartons.index') }}" class="{{ $nav('cartons.*') }}"><span class="ic">▤</span>Tally karton</a>
      <a href="{{ route('putaway.index') }}" class="{{ $nav('putaway.*') }}"><span class="ic">⇢</span>Put Away @if($cnt['putaway'])<span class="cnt">{{ $cnt['putaway'] }}</span>@endif</a>
      <a href="{{ route('qc.index') }}" class="{{ $nav('qc.*') }}"><span class="ic">⚑</span>QC Hold / Release @if($cnt['hold'])<span class="cnt">{{ $cnt['hold'] }}</span>@endif</a>

      <div class="grp">Inventory</div>
      <a href="{{ route('stock.index') }}" class="{{ $nav('stock.index') }}"><span class="ic">≡</span>Stock Status</a>
      <a href="{{ route('lpn.index') }}" class="{{ $nav('lpn.*') }}"><span class="ic">▥</span>LPN List @if($cnt['reprint'])<span class="cnt">{{ $cnt['reprint'] }}</span>@endif</a>
      <a href="{{ route('internal.index') }}" class="{{ $nav('internal.*') }}"><span class="ic">⇄</span>Bin Transfer</a>
      <a href="{{ route('ic.index') }}" class="{{ $nav('ic.*') }}"><span class="ic">☑</span>Inventory Check @if($cnt['ic'])<span class="cnt">{{ $cnt['ic'] }}</span>@endif</a>
      <a href="{{ route('cycle.index') }}" class="{{ $nav('cycle.*') }}"><span class="ic">#</span>Cycle Count</a>
      <a href="{{ route('stock.card') }}" class="{{ $nav('stock.card') }}"><span class="ic">▧</span>Stock Card</a>

      <div class="grp">Outbound</div>
      <a href="{{ route('outbound.index') }}" class="{{ $nav('outbound.*') }}"><span class="ic">⇧</span>Outbound Document @if($cnt['deliveries'])<span class="cnt">{{ $cnt['deliveries'] }}</span>@endif</a>
      <a href="{{ route('jobs.index') }}" class="{{ $nav('jobs.*') }}"><span class="ic">☰</span>Picking Job</a>
      <a href="{{ route('checker.index') }}" class="{{ $nav('checker.*') }}"><span class="ic">✓</span>Checker</a>
      <a href="{{ route('loading.index') }}" class="{{ $nav('loading.*') }}"><span class="ic">⛟</span>Loading Job</a>
      <a href="{{ route('pod.index') }}" class="{{ $nav('pod.*') }}"><span class="ic">✎</span>POD</a>
      <a href="{{ route('butcher.index') }}" class="{{ $nav('butcher.*') }}"><span class="ic">✂</span>Butcher / Repack</a>

      <div class="grp">Laporan</div>
      @foreach (\App\Http\Controllers\ReportController::MENU as $key => $label)
        <a href="{{ route('reports.show', $key) }}" class="{{ request()->routeIs('reports.show') && request()->route('report') === $key ? 'on' : '' }}"><span class="ic">◔</span>{{ $label }}</a>
      @endforeach

      <div class="grp">Integrasi SAP B1</div>
      <a href="{{ route('sap.sync') }}" class="{{ $nav('sap.sync*', 'sap.import') }}"><span class="ic">⇆</span>Dokumen masuk (POR/SOR) @if($cnt['sync_err'])<span class="cnt">{{ $cnt['sync_err'] }}</span>@endif</a>
      <a href="{{ route('sap.queue') }}" class="{{ $nav('sap.queue') }}"><span class="ic">⇪</span>Antrean posting @if($cnt['sap_failed'])<span class="cnt">{{ $cnt['sap_failed'] }}</span>@endif</a>

      <div class="grp">Master Data</div>
      <a href="{{ route('items.index') }}" class="{{ $nav('items.*') }}"><span class="ic">⌗</span>Item Master</a>
      <a href="{{ route('brands.index') }}" class="{{ $nav('brands.*') }}"><span class="ic">®</span>Brand SAP</a>
      <a href="{{ route('uoms.index') }}" class="{{ $nav('uoms.*') }}"><span class="ic">⚖</span>Satuan SAP</a>
      <a href="{{ route('locations.index') }}" class="{{ $nav('locations.*') }}"><span class="ic">⌖</span>Bin / Rak Master</a>
      <a href="{{ route('partners.index', 'source') }}" class="{{ request()->route('kind') === 'source' ? 'on' : '' }}"><span class="ic">⚐</span>Source Master</a>
      <a href="{{ route('partners.index', 'destination') }}" class="{{ request()->route('kind') === 'destination' || $nav('partners.edit') ? 'on' : '' }}"><span class="ic">⚑</span>Destination Master</a>
      <a href="{{ route('templates.index') }}" class="{{ $nav('templates.*') }}"><span class="ic">▮</span>Format barcode karton</a>
      <a href="{{ route('zones.index') }}" class="{{ $nav('zones.*', 'warehouse.*') }}"><span class="ic">❄</span>Zona & gudang</a>
      @if($me->isAdmin())
      <a href="{{ route('users.index') }}" class="{{ $nav('users.*') }}"><span class="ic">☺</span>Pengguna</a>
      <a href="{{ route('ann.index') }}" class="{{ $nav('ann.*') }}"><span class="ic">✉</span>Pengumuman</a>
      @endif

      <div class="grp">Bantuan</div>
      <a href="{{ route('implementation') }}" class="{{ $nav('implementation') }}"><span class="ic">◈</span>Persiapan Implementasi</a>
      <a href="{{ route('help.index') }}" class="{{ $nav('help.*') }}"><span class="ic">?</span>Panduan</a>
    </nav>
  </aside>
  <div class="main">
    <header class="top">
      <button type="button" class="btn ghost sm menu-toggle" onclick="document.getElementById('side').classList.toggle('open')">☰</button>
      <div class="crumb">@yield('crumb', 'FoodTrace')</div>
      <div class="sp"></div>
      <span class="muted no-print" style="font-size:.85rem">{{ \App\Models\Setting::get('company_name') }}</span>
      <button type="button" class="btn ghost sm help-btn" data-help-open title="Bantuan untuk layar ini (tekan ?)">？ Bantuan</button>
      <div class="me"><span class="avatar">{{ $me->initials() }}</span><div>{{ $me->name }}<br><small>{{ $me->roleLabel() }}</small></div></div>
      <a class="btn ghost sm" href="{{ route('password') }}" title="Ganti kata sandi">⚙</a>
      <form method="post" action="{{ route('logout') }}">@csrf<button class="btn ghost sm">Keluar</button></form>
    </header>
    <main class="content">
      @if(session('ok'))<div class="alert ok">{{ session('ok') }}</div>@endif
      @if(session('warn'))<div class="alert warn">{{ session('warn') }}</div>@endif
      @if($errors->any())<div class="alert bad">@foreach($errors->all() as $e)<div>{{ $e }}</div>@endforeach</div>@endif
      @yield('content')
    </main>
  </div>
  @include('help._panel')
</div>
<script src="{{ asset('js/foodtrace.js') }}?v={{ config('foodtrace.version') }}"></script>
@stack('scripts')
</body>
</html>
