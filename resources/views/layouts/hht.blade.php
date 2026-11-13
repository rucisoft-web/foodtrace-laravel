<!doctype html>
<html lang="id">
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width, initial-scale=1, maximum-scale=1">
<meta name="csrf-token" content="{{ csrf_token() }}">
<meta name="theme-color" content="#1b2a4e">
<title>@yield('title', 'HHT') · FoodTrace</title>
<link rel="icon" href="{{ asset('favicon.png') }}">
<link rel="stylesheet" href="{{ asset('css/foodtrace.css') }}?v={{ config('foodtrace.version') }}">
</head>
<body style="background:#e9eaee">
<div class="hht">
  <div class="hht-bar">
    @hasSection('back')<a class="hb" href="@yield('back')">‹</a>@endif
    <div class="title">@yield('title', 'FoodTrace HHT')</div>
    @auth<a class="hb" href="{{ route('help.show', \App\Http\Controllers\HelpController::topicForCurrent()) }}" title="Bantuan layar ini">?</a><a class="hb" href="{{ route('hht.home') }}" title="Menu HHT">☰</a>@endauth
  </div>
  <div class="hht-body">
    @if(session('ok'))<div class="msg ok">✓ {{ session('ok') }}</div>@endif
    @if(session('warn'))<div class="msg err">{{ session('warn') }}</div>@endif
    @if($errors->any())<div class="msg err">@foreach($errors->all() as $e)<div>✕ {{ $e }}</div>@endforeach</div>@endif
    @yield('content')
  </div>
</div>
<script src="{{ asset('js/foodtrace.js') }}?v={{ config('foodtrace.version') }}"></script>
<script>
  // HHT: fokus otomatis ke kolom scan pertama yang kosong; Enter dari scanner langsung submit.
  (function () { const f = document.querySelector('[data-scan]') || [...document.querySelectorAll('.hht-body input[type=text]')].find(i => !i.value); if (f) f.focus(); })();
  @if(session('ok'))try { navigator.vibrate && navigator.vibrate(60); } catch (e) {}@endif
  @if($errors->any())try { navigator.vibrate && navigator.vibrate([120, 80, 120]); } catch (e) {}@endif
</script>
</body>
</html>
