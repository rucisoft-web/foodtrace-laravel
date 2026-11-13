<?php

namespace App\Services;

use App\Models\Carton;
use App\Models\Document;
use App\Models\DocumentDetail;
use App\Models\Loading;
use App\Models\Location;
use App\Models\PickingJob;
use App\Models\Pod;
use App\Models\Stock;
use App\Support\Seq;
use App\Support\Wms;
use App\Support\WmsException;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

/** Outbound: reservasi FEFO, Picking Job, picking, staging, checker, Loading Job, Goods Issue, POD. */
class OutboundService
{
    /** Reservasi FEFO untuk baris yang belum tercukupi. Mengembalikan jumlah baris yang masih kurang. */
    public static function reserve(Document $doc): int
    {
        if ($doc->type !== 'outbound' || ! $doc->isOpen()) {
            return 0;
        }
        $doc->load('lines.item.uom', 'lines.details', 'partner');
        $short = 0;
        DB::transaction(function () use ($doc, &$short) {
            foreach ($doc->lines as $line) {
                $need = round($line->qty_demand - $line->details->sum('qty'), 3);
                if ($need <= 0.0005) {
                    continue;
                }
                foreach (StockService::fefoStocks($line->item, $doc->owner_id, $doc->partner, $doc->warehouse_id) as $stock) {
                    if ($need <= 0.0005) {
                        break;
                    }
                    $take = round(min($need, $stock->available()), 3);
                    if ($take <= 0) {
                        continue;
                    }
                    $stock->increment('reserved_qty', $take);
                    $doc->details()->create(['document_line_id' => $line->id, 'item_id' => $line->item_id, 'lot_id' => $stock->lot_id,
                        'stock_id' => $stock->id, 'from_location_id' => $stock->location_id, 'lpn_id' => $stock->lpn_id,
                        'result_lpn_id' => abs($take - $stock->qty) < 0.0005 ? $stock->lpn_id : null, 'qty' => $take]);
                    $need = round($need - $take, 3);
                }
                if ($need > 0.0005) {
                    $short++;
                }
            }
            $doc->state = $short ? 'open' : 'ready';
            $doc->save();
        });

        return $short;
    }

    public static function unreserve(Document $doc): void
    {
        DB::transaction(function () use ($doc) {
            foreach ($doc->details()->with('stock')->get() as $d) {
                if ($d->stock) {
                    $d->stock->reserved_qty = max(0, round($d->stock->reserved_qty - $d->qty, 3));
                    $d->stock->save();
                }
                $d->delete();
            }
            $doc->update(['state' => 'open']);
        });
    }

    public static function createPickingJob(Collection $docs, array $vals = []): PickingJob
    {
        $docs = $docs->filter(fn ($d) => $d->type === 'outbound' && $d->isOpen());
        if ($docs->isEmpty()) {
            throw new WmsException('Pilih outbound document yang belum selesai.');
        }

        return DB::transaction(function () use ($docs, $vals) {
            foreach ($docs as $d) {
                self::reserve($d);
            }
            $groups = $docs->pluck('shipping_group')->filter()->unique()->sort()->implode(', ');
            $job = PickingJob::create($vals + ['number' => Seq::next('PJ'), 'description' => $groups ?: 'Picking job', 'user_id' => auth()->id()]);
            Document::whereIn('id', $docs->pluck('id'))->update(['picking_job_id' => $job->id, 'stage' => 'open']);

            return $job;
        });
    }

    /** Assisted picking: lokasi yang discan harus lokasi saran (FEFO), barang harus LPN/lot/item yang benar. */
    public static function pick(DocumentDetail $d, string $locationCode, string $itemCode, $qty = null): string
    {
        $loc = Lookup::location($locationCode);
        if ($loc->id !== $d->from_location_id) {
            throw new WmsException('Lokasi salah. Ambil dari '.$d->from?->full_name.' (FEFO).');
        }
        $code = trim($itemCode);
        if (! in_array($code, array_filter([$d->lpn?->name, $d->lot?->name, $d->item->code, $d->item->barcode]), true)) {
            throw new WmsException("Barang yang discan bukan {$d->item->label()} lot {$d->lot?->name}.");
        }
        DB::transaction(function () use ($d, $qty) {
            if ($qty !== null && $qty !== '') {
                $qty = round((float) $qty, 3);
                if ($qty <= 0 || $qty > $d->qty + 0.0005) {
                    throw new WmsException('Qty ambil harus antara 0 dan '.Wms::fmt($d->qty).'.');
                }
                if ($qty < $d->qty) {
                    // Ambil sebagian (loose picking): LPN dipecah, sisa tetap di rak.
                    $d->stock?->decrement('reserved_qty', round($d->qty - $qty, 3));
                    $d->qty = $qty;
                    $d->result_lpn_id = null;
                    $d->document->update(['state' => 'open']);
                }
            }
            $d->picked = true;
            $d->save();
            if ($d->document->stage === 'open') {
                $d->document->update(['stage' => 'picking']);
            }
        });

        return sprintf('Diambil %s %s %s', Wms::fmt($d->qty), $d->item->uom->code, $d->item->name);
    }

    /** Finish trip: semua barang diambil lalu diletakkan di outbound staging. */
    public static function stage(Collection $docs, string $locationCode): string
    {
        $loc = Lookup::location($locationCode);
        if ($loc->area !== 'out') {
            throw new WmsException("{$loc->full_name} bukan lokasi outbound staging.");
        }
        foreach ($docs as $doc) {
            if ($doc->details()->where('picked', false)->exists() || ! $doc->details()->exists()) {
                throw new WmsException("{$doc->number}: masih ada baris yang belum diambil.");
            }
        }
        Document::whereIn('id', $docs->pluck('id'))->update(['stage' => 'staged', 'staging_location_id' => $loc->id]);

        return sprintf('%d dokumen di staging %s, siap dicek checker.', $docs->count(), $loc->name);
    }

    /** Checker scan LPN / lot / item: cocokkan dengan hasil picking. Barang salah ditolak. */
    public static function check(Document $doc, string $code): string
    {
        $code = trim($code);
        $d = $doc->details()->with('item', 'lot', 'lpn', 'resultLpn')->where('checked', false)->get()
            ->first(fn ($d) => in_array($code, array_filter([$d->resultLpn?->name, $d->lpn?->name, $d->lot?->name, $d->item->code, $d->item->barcode]), true));
        if (! $d) {
            throw new WmsException("{$code} tidak ada di dokumen ini atau sudah dicek.");
        }
        $d->update(['checked' => true]);
        if (! $doc->details()->where('checked', false)->exists()) {
            $doc->update(['stage' => 'checked']);

            return "{$doc->number} lengkap, siap dimuat.";
        }

        return 'OK '.$d->item->label();
    }

    public static function loadScan(Loading $job, string $code): string
    {
        $code = trim($code);
        $lines = $job->details()->with('item', 'lot', 'lpn', 'resultLpn', 'document')->where('loaded', false)->get();
        $byLpn = $lines->filter(fn ($d) => in_array($code, array_filter([$d->resultLpn?->name, $d->lpn?->name]), true));
        $target = $byLpn->isNotEmpty() ? $byLpn : $lines->filter(fn ($d) => in_array($code, array_filter([$d->lot?->name, $d->item->code]), true))->take(1);
        if ($target->isEmpty()) {
            throw new WmsException("{$code} bukan muatan kendaraan ini atau sudah dimuat.");
        }
        DocumentDetail::whereIn('id', $target->pluck('id'))->update(['loaded' => true]);
        Document::whereIn('id', $target->pluck('document_id'))->update(['stage' => 'loading']);

        return 'Dimuat: '.$target->map(fn ($d) => $d->item->name)->unique()->implode(', ');
    }

    /** Goods Issue satu outbound document: stok keluar, karton terkirim, antrean SAP Delivery. */
    public static function goodsIssue(Document $doc): void
    {
        if ($doc->state === 'done') {
            return;
        }
        DB::transaction(function () use ($doc) {
            $doc->load('details.stock', 'details.item', 'details.lot', 'partner');
            if ($doc->details->isEmpty()) {
                throw new WmsException("{$doc->number}: belum ada barang yang direservasi/diambil.");
            }
            foreach ($doc->details as $d) {
                StockService::checkShipLife($d->item, $d->lot, $doc->partner);
                $stock = $d->stock;
                if (! $stock || $stock->qty + 0.0005 < $d->qty) {
                    throw new WmsException("Stok {$d->item->label()} lot {$d->lot?->name} di {$d->from?->full_name} tidak cukup.");
                }
                $stock->qty = round($stock->qty - $d->qty, 3);
                $stock->reserved_qty = max(0, round($stock->reserved_qty - $d->qty, 3));
                $stock->save();
                StockService::ledger(['reference' => $doc->number, 'kind' => 'out', 'document_id' => $doc->id, 'item_id' => $d->item_id,
                    'lot_id' => $d->lot_id, 'from_location_id' => $d->from_location_id, 'to_label' => 'Pelanggan', 'lpn_id' => $d->lpn_id,
                    'owner_id' => $doc->owner_id, 'partner_id' => $doc->partner_id, 'qty' => $d->qty]);
                if ($d->item->catch_weight && $d->lpn_id && abs($stock->qty) < 0.0005) {
                    Carton::where('lpn_id', $d->lpn_id)->where('state', 'available')->update(['state' => 'shipped', 'out_document_id' => $doc->id]);
                }
                if ($stock->qty <= 0.0005 && $stock->reserved_qty <= 0.0005) {
                    $d->stock_id = null;
                    $d->save();
                    $stock->delete();
                }
            }
            $doc->update(['state' => 'done', 'stage' => 'shipped', 'done_at' => StockService::now(), 'done_by' => auth()->id()]);
            SapService::enqueue($doc);
            if ($doc->picking_job_id && ! Document::where('picking_job_id', $doc->picking_job_id)->whereIn('state', ['open', 'ready'])->exists()) {
                PickingJob::where('id', $doc->picking_job_id)->update(['state' => 'done']);
            }
        });
    }

    /** Loading selesai: Goods Issue untuk semua outbound, buat POD per outbound. */
    public static function closeLoading(Loading $job, bool $force = false): string
    {
        $missing = $job->details()->where('loaded', false)->count();
        if ($missing && ! $force) {
            throw new WmsException("{$missing} baris belum discan muat.");
        }
        DB::transaction(function () use ($job) {
            foreach ($job->documents as $doc) {
                $skip = StockService::$skipChecks;
                StockService::$skipChecks = true; // aturan sudah dicek saat picking/checker
                try {
                    self::goodsIssue($doc);
                } finally {
                    StockService::$skipChecks = $skip;
                }
                if (! $doc->pod) {
                    self::createPod($doc);
                }
            }
            $job->update(['state' => 'close']);
        });

        return "{$job->number} ditutup. Goods Issue terkirim ke antrean SAP, surat jalan siap dicetak.";
    }

    public static function cancelLoading(Loading $job): void
    {
        DB::transaction(function () use ($job) {
            foreach ($job->documents as $doc) {
                $doc->details()->update(['loaded' => false]);
                if ($doc->state !== 'done') {
                    $doc->update(['stage' => 'checked']);
                }
                $doc->update(['loading_id' => null]);
            }
            $job->update(['state' => 'cancel']);
        });
    }

    public static function createPod(Document $doc): Pod
    {
        $pod = Pod::create(['name' => $doc->number, 'document_id' => $doc->id, 'token' => Str::random(32)]);
        foreach ($doc->details()->with('item.uom')->get()->groupBy('item_id') as $rows) {
            $q = round($rows->sum('qty'), 3);
            $pod->lines()->create(['item_id' => $rows[0]->item_id, 'uom' => $rows[0]->item->uom->code, 'qty_sent' => $q, 'qty_received' => $q]);
        }

        return $pod;
    }

    public static function submitPod(Pod $pod, ?string $receivedBy, array $lines, ?string $photoPath = null, ?string $remark = null): void
    {
        if ($pod->state === 'done') {
            throw new WmsException('POD ini sudah diisi.');
        }
        if (trim((string) $receivedBy) === '') {
            throw new WmsException('Nama penerima wajib diisi.');
        }
        DB::transaction(function () use ($pod, $receivedBy, $lines, $photoPath, $remark) {
            $diff = false;
            foreach ($pod->lines as $l) {
                if (isset($lines[$l->id])) {
                    $l->qty_received = (float) ($lines[$l->id]['qty'] ?? 0);
                    $l->remark = $lines[$l->id]['remark'] ?? null;
                    $l->save();
                }
                $diff = $diff || abs($l->qty_received - $l->qty_sent) > 0.0005;
            }
            $pod->update(['received_by' => $receivedBy, 'remark' => $remark, 'photo_path' => $photoPath, 'state' => 'done',
                'received_at' => StockService::now(), 'has_difference' => $diff]);
            $pod->document->update(['stage' => 'delivered']);
        });
    }

    /** Lokasi staging default gudang. */
    public static function stagingLocation(int $warehouseId): ?Location
    {
        return Location::where('warehouse_id', $warehouseId)->where('area', 'out')->orderBy('id')->first();
    }
}
