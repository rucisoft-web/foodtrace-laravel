<!doctype html>
<html lang="id">
<head>
<meta charset="utf-8"><meta name="viewport" content="width=device-width, initial-scale=1">
<title>Pasang FoodTrace</title>
<link rel="icon" href="{{ asset('favicon.png') }}">
<link rel="stylesheet" href="{{ asset('css/foodtrace.css') }}">
</head>
<body>
<div class="wrap" style="max-width:860px;padding-top:30px;padding-bottom:40px">
  <div class="login-card"><div class="in">
    <img src="{{ asset('img/logo-saranakulina.png') }}" alt="" style="height:44px">
    <h1 style="margin-top:12px">Pasang FoodTrace</h1>
    <p class="muted">Wizard ini membuat file <code>.env</code>, semua tabel, data master (zona, satuan SAP, gudang & rak), akun admin, dan (opsional) data demo Sarana Kulina.</p>
    @if($errors->any())<div class="alert bad">@foreach($errors->all() as $e)<div>{{ $e }}</div>@endforeach</div>@endif
    <h3>Pemeriksaan server</h3>
    <table class="t" style="margin-bottom:16px">@foreach($checks as $label => $ok)<tr><td>{{ $label }}</td><td class="n">{!! $ok ? '<span class="badge b-green">✓</span>' : '<span class="badge b-red">✗</span>' !!}</td></tr>@endforeach</table>
    <form method="post" action="{{ url('/install') }}">
      @csrf
      <div class="grid g2">
        <div class="f"><label>Alamat aplikasi (APP_URL)</label><input type="text" name="app_url" value="{{ old('app_url', $url) }}" required></div>
        <div class="f"><label>Nama perusahaan</label><input type="text" name="company" value="{{ old('company', 'PT Sarana Kulina Intisejahtera') }}"></div>
      </div>
      <h3>Database MySQL / MariaDB</h3>
      <div class="grid g4">
        <div class="f span2"><label>Host</label><input type="text" name="db_host" value="{{ old('db_host', 'localhost') }}" required></div>
        <div class="f"><label>Port</label><input type="number" name="db_port" value="{{ old('db_port', 3306) }}" required></div>
        <div class="f"></div>
        <div class="f span2"><label>Nama database</label><input type="text" name="db_database" value="{{ old('db_database') }}" required placeholder="akuncpanel_foodtrace"></div>
        <div class="f"><label>Pengguna</label><input type="text" name="db_username" value="{{ old('db_username') }}" required></div>
        <div class="f"><label>Kata sandi</label><input type="password" name="db_password"></div>
      </div>
      <h3>Akun Administrator</h3>
      <div class="grid g3">
        <div class="f"><label>Email admin (dipakai untuk masuk)</label><input type="email" name="admin_email" value="{{ old('admin_email') }}" required></div>
        <div class="f"><label>Kata sandi (min. 8)</label><input type="password" name="admin_password" required></div>
        <div class="f"><label>Ulangi kata sandi</label><input type="password" name="admin_password_confirmation" required></div>
      </div>
      <label class="chk" style="margin:8px 0 16px"><input type="checkbox" name="demo" value="1" {{ old('demo', true) ? 'checked' : '' }}> Muat data demo Sarana Kulina (842 item, PO/SO riil SAP, ±1–2 menit). Kosongkan untuk produksi.</label>
      <button class="btn lg red">Pasang FoodTrace</button>
    </form>
  </div></div>
</div>
</body>
</html>
