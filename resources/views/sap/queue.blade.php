@extends('layouts.app')
@section('title', 'Antrean posting SAP')
@section('crumb') Integrasi SAP B1 <small>/ Antrean posting</small> @endsection
@section('content')
<div class="page-h"><div><h1>Antrean posting SAP B1</h1><p>Goods Receipt PO dan Delivery dari WMS dikirim ke SAP. Mode: <b>{{ $mode === 'simulation' ? 'simulasi (DocNum dibuat WMS)' : 'Service Layer' }}</b>. Terjadwal tiap 5 menit; bisa dikirim manual.</p></div>
  <div class="sp"></div><form method="post" action="{{ route('sap.post') }}">@csrf<button class="btn red">⇪ Kirim semua yang antre ({{ $counts['queued'] ?? 0 }})</button></form></div>
<div class="tabs"><a href="{{ route('sap.queue') }}" class="{{ request('state') ? '' : 'on' }}">Semua</a>@foreach(\App\Models\SapPosting::STATES as $k => $l)<a href="{{ route('sap.queue', ['state' => $k]) }}" class="{{ request('state') === $k ? 'on' : '' }}">{{ $l }} <span class="badge {{ $k === 'failed' ? 'b-red' : ($k === 'queued' ? 'b-navy' : 'b-grey') }}">{{ $counts[$k] ?? 0 }}</span></a>@endforeach</div>
<form method="post" action="{{ route('sap.post') }}">@csrf
<div class="card"><div class="card-h"><h3>{{ number_format($rows->total()) }} posting</h3><div class="sp"></div><button class="btn ghost sm">↻ Kirim ulang terpilih</button></div>
<div class="card-b flush tbl-wrap"><table class="t">
  <thead><tr><th><input type="checkbox" data-check-all=".sp"></th><th>Ref</th><th>Jenis SAP</th><th>Dokumen</th><th>Partner</th><th class="n">Qty</th><th class="n">Berat</th><th>Status</th><th>DocNum SAP</th><th class="n">Coba</th><th>Dibuat</th></tr></thead>
  <tbody>@forelse($rows as $p)
    <tr class="{{ $p->state === 'failed' ? 'bad' : '' }}"><td>@if($p->state !== 'posted')<input type="checkbox" class="sp" name="ids[]" value="{{ $p->id }}">@endif</td><td><b>{{ $p->ref }}</b></td><td>{{ \App\Models\SapPosting::TYPES[$p->doc_type] ?? $p->doc_type }}</td>
      <td>@if($p->document)<a href="{{ $p->document->show() }}">{{ $p->document->number }}</a><br><small>{{ $p->document->origin }}</small>@endif</td><td><small>{{ $p->document?->partner?->name }}</small></td>
      <td class="n">@qty($p->total_qty)</td><td class="n">{{ $p->total_weight ? \App\Support\Wms::fmt($p->total_weight, 2).' kg' : '' }}</td>
      <td>@status($p->state)@if($p->error)<br><small style="color:var(--maroon)">{{ $p->error }}</small>@endif</td><td>{{ $p->sap_doc_num }}<br><small>@dt($p->posted_at)</small></td><td class="n">{{ $p->attempts }}</td><td>@dt($p->created_at)</td></tr>
  @empty <tr><td colspan="11" class="empty">Antrean kosong.</td></tr> @endforelse</tbody>
</table></div></div>
</form>
{{ $rows->links() }}
@endsection
