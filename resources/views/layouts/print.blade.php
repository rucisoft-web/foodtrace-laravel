<!doctype html>
<html lang="id">
<head>
<meta charset="utf-8"><meta name="viewport" content="width=device-width, initial-scale=1">
<title>@yield('title') · FoodTrace</title>
<link rel="icon" href="{{ asset('favicon.png') }}">
<style>
  * { box-sizing: border-box; }
  body { font-family: Arial, Helvetica, sans-serif; color: #000; margin: 0; background: #e9eaee; font-size: 12px; }
  .bar { background: #1b2a4e; color: #fff; padding: 8px 14px; display: flex; gap: 10px; align-items: center; position: sticky; top: 0; }
  .bar b { flex: 1; } .bar button, .bar a { background: #7f1d2d; color: #fff; border: 0; padding: 6px 14px; border-radius: 4px; font-weight: 700; cursor: pointer; text-decoration: none; font-size: 13px; }
  .bar a { background: rgba(255,255,255,.15); }
  .sheet { background: #fff; margin: 14px auto; padding: 14mm; width: 210mm; min-height: 280mm; box-shadow: 0 2px 10px rgba(0,0,0,.12); }
  .doc-h { display: flex; gap: 14px; align-items: flex-start; border-bottom: 2px solid #1b2a4e; padding-bottom: 8px; margin-bottom: 10px; position: relative; }
  .doc-h::after { content: ""; position: absolute; left: 0; right: 0; bottom: -5px; height: 2px; background: linear-gradient(90deg, #1b2a4e 0 40%, #7f1d2d 40% 70%, #47302a 70% 85%, #7aa83a 85%); }
  .doc-h img.logo { height: 38px; } .doc-h .sp { flex: 1; } .doc-h h2 { margin: 0; font-size: 18px; color: #1b2a4e; }
  table.p { width: 100%; border-collapse: collapse; margin-top: 8px; }
  table.p th, table.p td { border: 1px solid #555; padding: 4px 6px; text-align: left; vertical-align: top; }
  table.p th { background: #eef0f5; font-size: 11px; } .n { text-align: right !important; white-space: nowrap; }
  .meta { display: grid; grid-template-columns: repeat(3, 1fr); gap: 4px 14px; margin: 6px 0; }
  .sign { display: grid; grid-template-columns: repeat(3, 1fr); gap: 20px; margin-top: 40px; text-align: center; }
  .sign div { padding-top: 60px; border-bottom: 1px dotted #000; }
  .muted { color: #555; }
  @media print { body { background: #fff; } .bar { display: none; } .sheet { margin: 0; box-shadow: none; width: auto; min-height: 0; padding: 0; page-break-after: always; } }
  @yield('style')
</style>
</head>
<body>
<div class="bar"><b>@yield('title')</b><a href="javascript:history.back()">← Kembali</a><button onclick="window.print()">⎙ Cetak</button></div>
@yield('content')
@if(request('auto'))<script>window.addEventListener('load', () => window.print());</script>@endif
</body>
</html>
