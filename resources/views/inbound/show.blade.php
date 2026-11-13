@extends('layouts.app')
@section('title', $doc->number)
@section('crumb') Inbound <small>/ <a href="{{ route('inbound.index') }}">Inbound Document</a> / {{ $doc->number }}</small> @endsection
@php use App\Support\Wms; $open = $doc->isOpen(); $done = $doc->lines->sum(fn ($l) => $l->qtyDone()); $demand = $doc->lines->sum('qty_demand'); @endphp
@section('content')
<div class="page-h">
  <div><h1>{{ $doc->number }} @status($doc->state)</h1>
    <p>{{ $doc->typeLabel() }} · Ref. SAP <b>{{ $doc->origin ?: '-' }}</b> · {{ $doc->partner?->name }} · Owner: {{ $doc->owner?->name ?? 'milik sendiri' }}</p></div>
  <div class="sp"></div>
  @if($lpns->isNotEmpty())<a class="btn ghost" href="{{ route('lpn.labels', ['document' => $doc->id]) }}" target="_blank">⎙ Label LPN ({{ $lpns->count() }})</a>@endif
  <a class="btn ghost" href="{{ route('hht.receipt', $doc) }}">▣ Buka di HHT</a>
  @if($open && auth()->user()->isManager())
    <form method="post" action="{{ route('inbound.cancel', $doc) }}" data-confirm="Batalkan {{ $doc->number }}? Semua hasil scan dihapus.">@csrf<button class="btn ghost">Batalkan</button></form>
  @endif
</div>
<div class="steps">
  <span class="done">SAP POR masuk</span><span class="{{ $open && $doc->dock_id ? 'done' : ($open ? 'on' : 'done') }}">Dock & kontainer</span>
  <span class="{{ $open ? ($done > 0 ? 'on' : '') : 'done' }}">Scan / tally karton</span><span class="{{ $doc->state === 'done' ? 'done' : '' }}">Validasi (GRPO)</span>
  <span class="">Put away</span>
</div>

<div class="grid g3">
  <div class="card line-navy">
    <div class="card-h"><h3>Header penerimaan</h3></div>
    <div class="card-b">
      <form method="post" action="{{ route('inbound.header', $doc) }}">@csrf
        <div class="grid g2">
          <div class="f"><label>Loading dock <span class="req">*</span></label><select name="dock_id" @disabled(!$open)><option value="">— pilih dock —</option>@foreach($docks as $k)<option value="{{ $k->id }}" @selected($doc->dock_id === $k->id)>{{ $k->name }}</option>@endforeach</select></div>
          <div class="f"><label>No. kontainer</label><input type="text" name="container_no" value="{{ $doc->container_no }}" @disabled(!$open)></div>
          <div class="f"><label>Ref. vendor / DO pemasok</label><input type="text" name="vendor_ref" value="{{ $doc->vendor_ref }}" @disabled(!$open)></div>
          <div class="f"><label>No. polisi</label><input type="text" name="vehicle_plate" value="{{ $doc->vehicle_plate }}" @disabled(!$open)></div>
          <div class="f span2"><label>Pengemudi</label><input type="text" name="driver" value="{{ $doc->driver }}" @disabled(!$open)></div>
        </div>
        @if($open)<button class="btn ghost sm">Simpan header</button>@endif
      </form>
      <hr>
      <table class="t">
        <tr><td>Jadwal</td><td>@d($doc->scheduled_at)</td></tr>
        <tr><td>Toleransi over receiving</td><td>{{ Wms::fmt($doc->partner?->over_receive_pct ?? 0, 2) }}%</td></tr>
        <tr><td>Progres qty</td><td><div class="progress"><i style="width:{{ $demand ? min(100, $done / $demand * 100) : 0 }}%"></i></div>{{ Wms::fmt($done) }} / {{ Wms::fmt($demand) }}</td></tr>
        @if($doc->done_at)<tr><td>Selesai</td><td>@dt($doc->done_at)</td></tr>@endif
        @foreach($doc->sapPostings as $p)<tr><td>SAP {{ \App\Models\SapPosting::TYPES[$p->doc_type] ?? $p->doc_type }}</td><td>@status($p->state) {{ $p->sap_doc_num ? 'DocNum '.$p->sap_doc_num : '' }}</td></tr>@endforeach
      </table>
    </div>
  </div>

  <div class="card line-red span2">
    <div class="card-h"><h3>Scan karton / input qty</h3><div class="sp"></div><small class="muted">1 LPN = 1 SKU + 1 lot. Lot/expiry berbeda otomatis membuka LPN baru.</small></div>
    <div class="card-b">
    @if($open)
      <form method="post" action="{{ route('inbound.scan', $doc) }}" class="grid g4">@csrf
        <div class="f span2"><label>Item (baris PO)</label><select name="line_id" required>
          @foreach($doc->lines as $l)<option value="{{ $l->id }}" @selected(session('line_id', $doc->lines->first(fn ($x) => $x->qtyDone() < $x->qty_demand)?->id) === $l->id)>{{ $l->item->code }} · {{ $l->item->name }} ({{ Wms::fmt($l->qtyDone()) }}/{{ Wms::fmt($l->qty_demand) }} {{ $l->uom->code }}){{ $l->item->catch_weight ? ' · berat aktual' : '' }}</option>@endforeach
        </select></div>
        <div class="f span2"><label>Barcode karton (GS1-128 / template)</label><input type="text" name="barcode" placeholder="scan barcode karton" data-autofocus autocomplete="off"></div>
        <div class="f"><label>Qty (item biasa)</label><input type="text" name="qty" inputmode="decimal" placeholder="mis. 40"></div>
        <div class="f"><label>Berat manual (kg)</label><input type="text" name="weight" inputmode="decimal" placeholder="bila barcode rusak"></div>
        <div class="f"><label>Lot / batch</label><input type="text" name="lot" placeholder="kosong = dari barcode / otomatis"></div>
        <div class="f"><label>Expiry</label><input type="date" name="expiry"></div>
        <div class="span4"><button class="btn red">⇩ Terima</button> <small class="muted">Item berat aktual (catch weight): scan per karton, berat dibaca dari barcode. Item biasa: isi qty.</small></div>
      </form>
    @else
      <div class="alert ok">Dokumen {{ $doc->stateLabel() }}. @if($doc->state === 'done')Lanjutkan ke <a href="{{ route('putaway.index') }}">Put Away</a>.@endif</div>
    @endif
    </div>
  </div>
</div>

<div class="card line-navy">
  <div class="card-h"><h3>Baris PO</h3></div>
  <div class="card-b flush tbl-wrap"><table class="t">
    <thead><tr><th>#</th><th>Item</th><th>Zona</th><th>Brand</th><th class="n">PO</th><th class="n">Diterima</th><th>Progres</th><th>Rincian (lot · LPN · lokasi)</th></tr></thead>
    <tbody>@foreach($doc->lines as $l)
      @php $q = $l->qtyDone(); @endphp
      <tr class="{{ $l->qty_demand && $q > $l->qty_demand + 0.0005 ? 'bad' : ($q >= $l->qty_demand && $q > 0 ? 'ok' : '') }}">
        <td>{{ $loop->iteration }}</td><td><b>{{ $l->item->code }}</b> {{ $l->item->name }}@if($l->item->catch_weight) <span class="badge b-brown">berat aktual</span>@endif @if($l->item->auto_quarantine) <span class="badge b-amber">auto karantina</span>@endif</td>
        <td>@zone($l->item->zone)</td><td><small>{{ $l->item->brand?->label() }}</small></td>
        <td class="n">@qty($l->qty_demand) {{ $l->uom->code }}</td><td class="n"><b>@qty($q)</b></td>
        <td><div class="progress"><i style="width:{{ $l->qty_demand ? min(100, $q / $l->qty_demand * 100) : 0 }}%"></i></div></td>
        <td>@foreach($l->details as $d)<div><small>{{ $d->lot?->name }} · exp @d($d->lot?->expiry_date) · {{ $d->resultLpn?->name }} · @qty($d->qty){{ $d->to ? ' → '.$d->to->name : '' }}</small>
          @if($open)<form method="post" action="{{ route('inbound.detail.delete', [$doc, $d]) }}" style="display:inline" data-confirm="Hapus rincian ini (karton ikut terhapus)?">@csrf @method('delete')<button class="btn ghost sm" title="hapus">✕</button></form>@endif</div>@endforeach</td></tr>
    @endforeach</tbody>
  </table></div>
</div>

<div class="card line-brown">
  <div class="card-h"><h3>Palet (LPN)</h3><div class="sp"></div><small class="muted">"Simpan palet" menutup LPN dan mengisi info label (No. palet, packaging, grade, EST).</small></div>
  <div class="card-b flush tbl-wrap"><table class="t">
    <thead><tr><th>LPN</th><th>Item</th><th>Lot</th><th class="n">Qty</th><th class="n">Karton</th><th>Status</th><th>Info palet</th><th></th></tr></thead>
    <tbody>@forelse($lpns as $p)
      <tr><td><a href="{{ route('lpn.show', $p) }}"><b>{{ $p->name }}</b></a></td><td>{{ $p->item?->code }}</td><td>{{ $p->lot?->name }}<br><small>exp @d($p->lot?->expiry_date)</small></td>
        <td class="n">@qty($lpnQty[$p->id] ?? 0)</td><td class="n">{{ $p->cartons_count }}</td><td>{!! $p->closed ? '<span class="badge b-grey">Tertutup</span>' : '<span class="badge b-green">Terbuka</span>' !!}</td>
        <td>@if($open)
          <form method="post" action="{{ route('inbound.pallet', [$doc, $p]) }}" class="inline">@csrf
            <div class="f"><input type="text" name="pallet_no" value="{{ $p->pallet_no }}" placeholder="No. palet" style="width:80px"></div>
            <div class="f"><input type="text" name="packaging" value="{{ $p->packaging }}" placeholder="Packaging" style="width:90px"></div>
            <div class="f"><input type="text" name="grade" value="{{ $p->lot?->grade }}" placeholder="Grade" style="width:70px"></div>
            <div class="f"><input type="text" name="est" value="{{ $p->lot?->est }}" placeholder="EST" style="width:70px"></div>
            <button class="btn ghost sm">Simpan palet</button>
          </form>
          @else <small>{{ collect(['Palet '.$p->pallet_no => $p->pallet_no, $p->packaging => $p->packaging, 'Grade '.$p->lot?->grade => $p->lot?->grade])->filter()->keys()->implode(' · ') }}</small> @endif</td>
        <td><a class="btn ghost sm" href="{{ route('lpn.labels', ['ids' => $p->id]) }}" target="_blank">⎙ Label</a></td></tr>
    @empty <tr><td colspan="8" class="empty">Belum ada palet. Scan karton atau isi qty untuk mulai.</td></tr> @endforelse</tbody>
  </table></div>
</div>

@if($open)
<div class="card line-green"><div class="card-b">
  <form method="post" action="{{ route('inbound.validate', $doc) }}" class="inline" data-confirm="Validasi penerimaan {{ $doc->number }}? Stok masuk dan Goods Receipt PO dikirim ke antrean SAP.">@csrf
    <div class="f"><label>Loading dock</label><select name="dock_id" required><option value="">— pilih —</option>@foreach($docks as $k)<option value="{{ $k->id }}" @selected($doc->dock_id === $k->id)>{{ $k->name }}</option>@endforeach</select></div>
    <button class="btn green lg">✓ Validasi penerimaan</button>
    <small class="muted">Dicek: dock wajib, over receiving (toleransi pemasok), sisa umur simpan minimum, auto karantina.</small>
  </form>
</div></div>
@endif
@endsection
