@extends('layouts.app')
@section('title', 'Dokumen masuk SAP')
@section('crumb') Integrasi SAP B1 <small>/ Dokumen masuk (POR/SOR)</small> @endsection
@section('content')
<div class="page-h"><div><h1>Dokumen masuk SAP B1</h1><p>File sinkron Purchase Order (POR) dan Sales Order (SOR) dari SAP. Idempoten per nomor: dokumen yang sama tidak dibuat dua kali.</p></div></div>
<div class="grid g3">
  <div class="card line-navy span2">
    <div class="card-h"><h3>Daftar dokumen</h3><div class="sp"></div>
      <div class="tabs" style="margin:0;border:0"><a href="{{ route('sap.sync') }}" class="{{ request('state') ? '' : 'on' }}">Semua</a>@foreach(\App\Models\SyncDocument::STATES as $k => $l)<a href="{{ route('sap.sync', ['state' => $k]) }}" class="{{ request('state') === $k ? 'on' : '' }}">{{ $l }} <span class="badge {{ $k === 'error' ? 'b-red' : 'b-grey' }}">{{ $counts[$k] ?? 0 }}</span></a>@endforeach</div></div>
    <form class="filters card-b" method="get" style="margin:0"><input type="hidden" name="state" value="{{ request('state') }}">
      <div class="f"><input type="search" name="q" value="{{ request('q') }}" placeholder="nomor / partner"></div>
      <div class="f"><select name="direction" data-submit><option value="">Semua arah</option><option value="inbound" @selected(request('direction') === 'inbound')>Inbound (POR)</option><option value="outbound" @selected(request('direction') === 'outbound')>Outbound (SOR)</option></select></div><button class="btn ghost">Cari</button></form>
    <div class="card-b flush tbl-wrap"><table class="t">
      <thead><tr><th>Nomor SAP</th><th>Arah</th><th>Jenis</th><th>Partner</th><th>Tanggal</th><th class="n">Baris</th><th>Status</th><th>Dokumen WMS</th></tr></thead>
      <tbody>@forelse($rows as $s)
        <tr class="{{ $s->state === 'error' ? 'bad' : '' }}"><td><a href="{{ route('sap.sync.show', $s) }}"><b>{{ $s->number }}</b></a></td><td>{{ $s->direction === 'inbound' ? '⇩ POR' : '⇧ SOR' }}</td><td><small>{{ $s->doc_type }}</small></td>
          <td>{{ $s->partner_name }}<br><small>{{ $s->partner_code }}</small></td><td>@d($s->doc_date)</td><td class="n">{{ $s->line_count }}</td><td>@status($s->state)@if($s->error)<br><small style="color:var(--maroon)">{{ \Illuminate\Support\Str::limit($s->error, 80) }}</small>@endif</td>
          <td>@if($s->document)<a href="{{ $s->document->show() }}">{{ $s->document->number }}</a>@elseif($s->state === 'error')<form method="post" action="{{ route('sap.sync.process', $s) }}">@csrf<button class="btn ghost sm">↻ Proses ulang</button></form>@endif</td></tr>
      @empty <tr><td colspan="8" class="empty">Belum ada dokumen.</td></tr> @endforelse</tbody>
    </table></div>
  </div>
  <div>
    <div class="card line-red"><div class="card-h"><h3>Impor file sinkron</h3></div><div class="card-b">
      <form method="post" action="{{ route('sap.import') }}" enctype="multipart/form-data">@csrf
        <div class="f"><label>File JSON dari SAP</label><input type="file" name="file" accept=".json,.txt,application/json"></div>
        <div class="f"><label>atau tempel isi JSON</label><textarea name="json" rows="7" placeholder='[{"number": "POR-...", "inbound_type": "Purchase Order", "items": [...]}]'>{{ old('json') }}</textarea></div>
        <button class="btn red">⇩ Impor & proses</button>
      </form>
    </div></div>
    <div class="card line-navy"><div class="card-h"><h3>API otomatis</h3></div><div class="card-b">
      <p class="muted" style="margin-top:0">Middleware SAP dapat mengirim langsung:</p>
      <code style="display:block;white-space:pre-wrap;background:#f6f7fa;padding:8px;border-radius:4px">POST {{ url('/api/v1/sync') }}
X-WMS-Token: {{ $token ?? '•••• (hanya Admin)' }}
Content-Type: application/json</code>
      <p class="muted">Item yang belum ada di Item Master membuat dokumen gagal; tambahkan item lalu "Proses ulang".</p>
    </div></div>
  </div>
</div>
{{ $rows->links() }}
@endsection
