<?php

namespace App\Services;

use App\Models\BarcodeTemplate;
use App\Models\Carton;
use App\Models\Document;
use App\Models\DocumentDetail;
use App\Models\DocumentLine;
use App\Models\Location;
use App\Models\Lot;
use App\Models\Lpn;
use App\Support\Seq;
use App\Support\Wms;
use App\Support\WmsException;
use Illuminate\Support\Facades\DB;

/** Receiving: scan karton / input qty per palet (LPN), simpan palet + label, validasi penerimaan. */
class InboundService
{
    public static function docks(Document $doc)
    {
        return Location::where('warehouse_id', $doc->warehouse_id)->where('area', 'rcv')->where('active', true)->orderBy('name')->get();
    }

    public static function findOrCreateLot(\App\Models\Item $item, ?string $name, $expiry, Document $doc): Lot
    {
        $name = trim((string) $name) ?: $doc->number.'-'.$item->code;
        $lot = Lot::firstOrCreate(['item_id' => $item->id, 'name' => $name], [
            'expiry_date' => $expiry ?: now()->addDays($item->shelfLife()), 'brand' => $item->brand?->label(),
        ]);
        if ($expiry && ! $lot->wasRecentlyCreated && ! $lot->expiry_date) {
            $lot->update(['expiry_date' => $expiry]);
        }

        return $lot;
    }

    /**
     * Satu scan karton (item berat aktual / barcode) atau input qty (item biasa). Mengisi LPN terbuka per lot;
     * bila belum ada LPN terbuka untuk lot tsb, LPN baru dibuat (1 SKU + 1 lot per LPN).
     */
    public static function scan(Document $doc, DocumentLine $line, ?string $barcode = null, float $manualWeight = 0, ?string $lotName = null, ?string $expiry = null, float $qty = 0): string
    {
        if (! $doc->isOpen() || $doc->type !== 'inbound') {
            throw new WmsException("{$doc->number} bukan penerimaan yang terbuka.");
        }
        $item = $line->item;
        $barcode = trim((string) $barcode);
        $cartonNo = null; $weight = 0; $weightLb = 0;
        if ($item->catch_weight || $barcode !== '') {
            $data = [];
            if ($barcode !== '') {
                try {
                    [, $data] = BarcodeTemplate::parseForItem($item, $barcode);
                } catch (WmsException $e) {
                    if (! $manualWeight) {
                        throw $e;
                    }
                }
            } elseif (! $manualWeight) {
                throw new WmsException('Scan barcode karton atau isi berat manual.');
            }
            $weight = $data['weight_kg'] ?? $manualWeight;
            $weightLb = $data['weight_lb'] ?? 0;
            $lotName = ($data['lot'] ?? null) ?: $lotName;
            $expiry = ($data['expiry'] ?? null) ?: $expiry;
            $cartonNo = ($data['carton'] ?? null) ?: ($barcode !== '' ? substr($barcode, -12) : 'M'.str_pad((string) (Carton::count() + 1), 6, '0', STR_PAD_LEFT));
            if (Carton::where('item_id', $item->id)->where('name', $cartonNo)->exists()) {
                throw new WmsException("Karton {$cartonNo} sudah discan.");
            }
            $add = $line->uom->is_weight ? $weight : 1;
        } else {
            $add = $qty;
            if ($add <= 0) {
                throw new WmsException('Isi qty yang diterima.');
            }
        }

        return DB::transaction(function () use ($doc, $line, $item, $lotName, $expiry, $add, $cartonNo, $weight, $weightLb, $barcode, $manualWeight) {
            $lot = self::findOrCreateLot($item, $lotName, $expiry, $doc);
            StockService::checkReceiveLife($item, $lot);
            $detail = DocumentDetail::where('document_line_id', $line->id)->where('lot_id', $lot->id)
                ->whereHas('resultLpn', fn ($q) => $q->where('closed', false))->first();
            if (! $detail) {
                $lpn = Lpn::create(['name' => Seq::lpn(), 'item_id' => $item->id, 'lot_id' => $lot->id]);
                $detail = $doc->details()->create(['document_line_id' => $line->id, 'item_id' => $item->id, 'lot_id' => $lot->id,
                    'to_location_id' => $doc->dock_id, 'result_lpn_id' => $lpn->id, 'qty' => 0, 'picked' => true]);
            }
            $detail->qty = round($detail->qty + $add, 3);
            $detail->save();
            $lpn = $detail->resultLpn;
            if ($cartonNo) {
                Carton::create(['name' => $cartonNo, 'raw_barcode' => $barcode ?: null, 'item_id' => $item->id, 'lot_id' => $lot->id,
                    'weight_kg' => $weight, 'weight_lb' => $weightLb, 'owner_id' => $doc->owner_id, 'in_document_id' => $doc->id,
                    'lpn_id' => $lpn->id, 'manual_weight' => $barcode === '' || (bool) $manualWeight, 'state' => 'available']);

                return sprintf('%s · %s kg → %s', $cartonNo, Wms::fmt($weight, 2), $lpn->name);
            }

            return sprintf('+%s %s → %s', Wms::fmt($add), $line->uom->code, $lpn->name);
        });
    }

    /** "Simpan palet": tutup LPN (scan berikutnya membuka LPN baru), isi info palet. */
    public static function closePallet(Document $doc, Lpn $lpn, array $info = []): void
    {
        if (! $doc->details()->where('result_lpn_id', $lpn->id)->exists()) {
            throw new WmsException('LPN bukan bagian dokumen ini.');
        }
        $lpn->update(['closed' => true, 'pallet_no' => $info['pallet_no'] ?? $lpn->pallet_no, 'remark' => $info['remark'] ?? $lpn->remark,
            'mfg_date' => $info['mfg_date'] ?? $lpn->mfg_date, 'pack_date' => $info['pack_date'] ?? now()->toDateString(),
            'packaging' => $info['packaging'] ?? $lpn->packaging]);
        $lotVals = array_filter(['grade' => $info['grade'] ?? null, 'est' => $info['est'] ?? null, 'mfg_date' => $info['mfg_date'] ?? null]);
        if ($lotVals && $lpn->lot) {
            $lpn->lot->update($lotVals);
        }
    }

    public static function removeDetail(Document $doc, DocumentDetail $detail): void
    {
        if (! $doc->isOpen() || $detail->document_id !== $doc->id) {
            throw new WmsException('Baris tidak bisa dihapus.');
        }
        DB::transaction(function () use ($detail) {
            Carton::where('lpn_id', $detail->result_lpn_id)->where('in_document_id', $detail->document_id)->delete();
            $lpn = $detail->resultLpn;
            $detail->delete();
            if ($lpn && ! $lpn->stocks()->exists() && ! DocumentDetail::where('result_lpn_id', $lpn->id)->exists()) {
                $lpn->delete();
            }
        });
    }

    /**
     * Validasi penerimaan: dock wajib, cek over receiving, umur simpan, auto karantina, lalu stok masuk
     * ke dock (status Receiving) atau lokasi yang dipilih; Goods Receipt masuk antrean SAP.
     */
    public static function validate(Document $doc, array $vals = []): string
    {
        if (! $doc->isOpen() || $doc->type !== 'inbound') {
            throw new WmsException("{$doc->number} sudah selesai atau dibatalkan.");
        }
        $doc->fill(array_filter($vals, fn ($v) => $v !== null && $v !== ''));
        $docks = self::docks($doc);
        if (! StockService::$skipChecks && ! $doc->dock_id && $docks->isNotEmpty()) {
            throw new WmsException("{$doc->number}: isi Loading dock (mis. DOCK_01) sebelum validasi penerimaan.");
        }
        $doc->load('lines.details.lot', 'lines.item.uom', 'lines.uom', 'partner');
        if ($doc->details()->sum('qty') <= 0) {
            throw new WmsException('Belum ada barang yang diterima.');
        }
        $tol = (float) ($doc->partner?->over_receive_pct ?? 0);
        foreach ($doc->lines as $line) {
            $done = $line->qtyDone();
            $limit = $line->qty_demand * (1 + $tol / 100);
            if (! StockService::$skipChecks && $line->qty_demand && $done > $limit + 0.0005) {
                throw new WmsException(sprintf('Over receiving %s: diterima %s, PO %s (toleransi %s%%). Buat inbound document (PO) terpisah untuk kelebihannya.',
                    $line->item->label(), Wms::fmt($done), Wms::fmt($line->qty_demand), Wms::fmt($tol, 2)));
            }
        }

        return DB::transaction(function () use ($doc) {
            $dock = $doc->dock_id ? Location::find($doc->dock_id) : null;
            $held = 0;
            foreach ($doc->lines as $line) {
                $item = $line->item;
                foreach ($line->details as $d) {
                    if ($d->qty <= 0) {
                        $d->delete();
                        continue;
                    }
                    StockService::checkReceiveLife($item, $d->lot);
                    $dest = $d->to_location_id ? Location::find($d->to_location_id) : $dock;
                    $dest ??= Location::where('warehouse_id', $doc->warehouse_id)->whereNull('parent_id')->first();
                    if ($item->auto_quarantine) {
                        $qc = $dest->qcLocation($item->zone);
                        if ($qc) {
                            $dest = $qc;
                            $held++;
                        }
                    }
                    StockService::checkZone($item, $dest);
                    StockService::checkLpn($d->resultLpn, $item, $d->lot);
                    $d->update(['to_location_id' => $dest->id]);
                    StockService::add($item, $dest, $d->qty, $d->lot, $d->resultLpn, $doc->owner_id);
                    $d->resultLpn?->update(['closed' => true, 'pack_date' => $d->resultLpn->pack_date ?? StockService::now()]);
                    StockService::ledger(['reference' => $doc->number, 'kind' => 'in', 'document_id' => $doc->id, 'item_id' => $item->id,
                        'lot_id' => $d->lot_id, 'from_label' => 'Pemasok', 'to_location_id' => $dest->id, 'result_lpn_id' => $d->result_lpn_id,
                        'owner_id' => $doc->owner_id, 'partner_id' => $doc->partner_id, 'qty' => $d->qty]);
                    Carton::where('in_document_id', $doc->id)->where('lpn_id', $d->result_lpn_id)
                        ->update(['location_id' => $dest->id, 'state' => $dest->area === 'qc' ? 'quarantine' : 'available']);
                }
            }
            $doc->state = 'done';
            $doc->done_at = StockService::now();
            $doc->done_by = auth()->id();
            $doc->save();
            SapService::enqueue($doc);
            $lpns = $doc->details()->whereNotNull('result_lpn_id')->distinct()->count('result_lpn_id');

            return sprintf('%s selesai. %d LPN siap put away%s.', $doc->number, $lpns, $held ? ", {$held} ke karantina" : '');
        });
    }
}
