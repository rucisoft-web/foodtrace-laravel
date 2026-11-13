<div class="page-h">
  <div><h1>{{ $title }}</h1>@isset($sub)<p>{{ $sub }}</p>@endisset</div>
  <div class="sp"></div>
  <a class="btn ghost" href="{{ request()->fullUrlWithQuery(['export' => 'csv']) }}">⇩ Ekspor CSV (Excel)</a>
  <button class="btn ghost" onclick="window.print()">⎙ Cetak</button>
</div>
<form class="filters no-print" method="get">
  @if(!empty($search))<div class="f"><label>{{ $search }}</label><input type="search" name="q" value="{{ request('q') }}" placeholder="{{ $placeholder ?? '' }}" data-autofocus></div>@endif
  <div class="f"><label>Zona</label><select name="zone" data-submit><option value="">Semua</option>@foreach(\App\Support\Wms::ZONES as $k => $l)<option value="{{ $k }}" @selected(request('zone') === $k)>{{ $l }}</option>@endforeach</select></div>
  {{ $slot ?? '' }}
  @if(!empty($dates))<div class="f"><label>Dari</label><input type="date" name="from" value="{{ $from }}"></div><div class="f"><label>Sampai</label><input type="date" name="to" value="{{ $to }}"></div>@endif
  <button class="btn">Tampilkan</button>
</form>
