@extends('layouts.app')
@section('title', $s->number)
@section('crumb') Integrasi SAP B1 <small>/ <a href="{{ route('sap.sync') }}">Dokumen masuk</a> / {{ $s->number }}</small> @endsection
@section('content')
<div class="page-h"><div><h1>{{ $s->number }} @status($s->state)</h1><p>{{ $s->direction === 'inbound' ? 'Purchase Order (POR)' : 'Sales Order (SOR)' }} · {{ $s->doc_type }} · {{ $s->partner_name }} ({{ $s->partner_code }})</p></div>
  <div class="sp"></div>@if($s->state !== 'done')<form method="post" action="{{ route('sap.sync.process', $s) }}">@csrf<button class="btn red">↻ Proses ulang</button></form>@endif
  @if($s->document)<a class="btn ghost" href="{{ $s->document->show() }}">→ {{ $s->document->number }}</a>@endif</div>
@if($s->error)<div class="alert bad">{{ $s->error }}</div>@endif
<div class="grid g3">
  <div class="card line-navy"><div class="card-b"><dl class="kv">
    <dt>Gudang</dt><dd>{{ $s->warehouse_code }}</dd><dt>Owner</dt><dd>{{ $s->owner_code }}</dd><dt>Rute</dt><dd>{{ $s->shipping_group ?: '-' }}</dd><dt>Ref. vendor</dt><dd>{{ $s->vendor_ref ?: '-' }}</dd>
    <dt>Kontainer</dt><dd>{{ $s->container_no ?: '-' }}</dd><dt>Tgl dokumen</dt><dd>@d($s->doc_date)</dd><dt>Tgl kirim</dt><dd>@d($s->delivery_date)</dd><dt>Prioritas</dt><dd>{{ $s->priority ?: '-' }}</dd>
    <dt>Web trans / DocEntry</dt><dd>{{ $s->web_trans_no }} / {{ $s->doc_entry }}</dd><dt>Diterima</dt><dd>@dt($s->created_at)</dd></dl></div></div>
  <div class="card line-brown span2"><div class="card-h"><h3>Payload</h3></div><div class="card-b"><pre style="max-height:520px;overflow:auto;font-size:.82rem;margin:0">{{ $s->payload }}</pre></div></div>
</div>
@endsection
