<?php

namespace App\Services;

use App\Models\Carton;
use App\Models\Document;
use App\Models\Item;
use App\Models\Location;
use App\Models\Lot;
use App\Models\Lpn;
use App\Models\Stock;
use App\Models\StockMove;
use App\Support\Seq;
use App\Support\Wms;
use App\Support\WmsException;
use Illuminate\Support\Facades\DB;

/**
 * Inti pencatatan stok: menambah/mengurangi stok per item + lot + LPN + lokasi + owner, mencatat kartu stok,
 * dan menegakkan aturan gudang (zona wajib, 1 SKU + 1 lot per LPN, umur simpan).
 */
class StockService
{
    /** Bila true, aturan validasi dilewati (dipakai untuk memutar ulang riwayat demo). */
    public static bool $skipChecks = false;

    /** Tanggal paksa untuk kartu stok (riwayat demo). */
    public static ?\DateTimeInterface $forceDate = null;

    public static function now(): \DateTimeInterface
    {
        return self::$forceDate ?? now();
    }

    // ------------------------------------------------------------------ aturan

    public static function checkZone(Item $item, ?Location $dest): void
    {
        if (self::$skipChecks || ! $dest || ! $item->zone) {
            return;
        }
        if ($dest->eff_zone && $dest->eff_zone !== $item->zone) {
            throw new WmsException(sprintf('%s wajib di zona %s, tetapi lokasi tujuan %s ada di zona %s.',
                $item->label(), Wms::ZONES[$item->zone] ?? $item->zone, $dest->full_name, Wms::ZONES[$dest->eff_zone] ?? $dest->eff_zone));
        }
    }

    /** Satu LPN hanya untuk 1 SKU dan 1 lot (aturan Largo). */
    public static function checkLpn(?Lpn $lpn, Item $item, ?Lot $lot): void
    {
        if (! $lpn || self::$skipChecks) {
            return;
        }
        $other = Stock::where('lpn_id', $lpn->id)->where('qty', '>', 0)
            ->where(fn ($q) => $q->where('item_id', '!=', $item->id)->orWhere(fn ($w) => $lot ? $w->where('lot_id', '!=', $lot->id)->orWhereNull('lot_id') : $w->whereNotNull('lot_id')))
            ->exists();
        if ($other || ($lpn->item_id && $lpn->item_id !== $item->id) || ($lpn->lot_id && $lot && $lpn->lot_id !== $lot->id)) {
            throw new WmsException("LPN {$lpn->name} hanya boleh berisi 1 SKU dan 1 lot.");
        }
    }

    public static function checkReceiveLife(Item $item, ?Lot $lot): void
    {
        if (self::$skipChecks || ! $item->min_receive_life_pct || ! $lot) {
            return;
        }
        $pct = $lot->remainingLifePct($item);
        if ($pct !== null && $pct < $item->min_receive_life_pct) {
            throw new WmsException(sprintf('Lot %s (%s) tinggal %.0f%% umur simpan, di bawah batas terima item %d%%.',
                $lot->name, $item->label(), $pct, $item->min_receive_life_pct));
        }
    }

    public static function checkShipLife(Item $item, ?Lot $lot, ?\App\Models\Partner $customer): void
    {
        if (self::$skipChecks || ! $lot) {
            return;
        }
        if ($lot->isExpired()) {
            throw new WmsException("Lot {$lot->name} ({$item->label()}) sudah kedaluwarsa dan diblokir.");
        }
        $min = $customer?->min_shelf_life_pct;
        if ($min) {
            $pct = $lot->remainingLifePct($item);
            if ($pct !== null && $pct < $min) {
                throw new WmsException(sprintf('Lot %s (%s) tinggal %.0f%% umur simpan, di bawah batas pelanggan %s %d%%.',
                    $lot->name, $item->label(), $pct, $customer->name, $min));
            }
        }
    }

    // ------------------------------------------------------------------ mutasi dasar

    /** Tambah stok di lokasi (atau buat baris baru). */
    public static function add(Item $item, Location $loc, float $qty, ?Lot $lot = null, ?Lpn $lpn = null, ?int $ownerId = null, ?\DateTimeInterface $inDate = null): Stock
    {
        $stock = Stock::where(['item_id' => $item->id, 'location_id' => $loc->id, 'lot_id' => $lot?->id, 'lpn_id' => $lpn?->id, 'owner_id' => $ownerId])->first();
        if ($stock) {
            $stock->qty = round($stock->qty + $qty, 3);
            $stock->save();
        } else {
            $stock = Stock::create(['item_id' => $item->id, 'location_id' => $loc->id, 'lot_id' => $lot?->id, 'lpn_id' => $lpn?->id,
                'owner_id' => $ownerId, 'qty' => round($qty, 3), 'in_date' => $inDate ?? self::now()]);
        }
        if ($lpn && (! $lpn->item_id || ! $lpn->lot_id)) {
            $lpn->update(['item_id' => $item->id, 'lot_id' => $lot?->id]);
        }

        return $stock;
    }

    public static function ledger(array $vals): StockMove
    {
        $item = Item::with('uom')->find($vals['item_id']);

        return StockMove::create($vals + [
            'date' => self::now(), 'uom' => $item->uom->code, 'value' => round(($vals['qty'] ?? 0) * $item->cost, 2),
            'user_id' => auth()->id(),
        ]);
    }

    /**
     * Pindah sebagian/seluruh stok ke lokasi lain (put away, bin transfer, QC hold/release).
     * Bila seluruh qty dipindah, LPN ikut; bila sebagian, LPN tujuan bisa diberikan (atau tanpa LPN).
     */
    public static function move(Stock $stock, Location $dest, float $qty, string $reference, ?Document $doc = null, bool $keepLpn = null, string $kind = 'internal'): Stock
    {
        $qty = round($qty, 3);
        if ($qty <= 0 || $qty > $stock->available() + 0.0005) {
            throw new WmsException(sprintf('Qty pindah harus antara 0 dan %s (stok tersedia %s).', Wms::fmt($stock->available()), $stock->item->label()));
        }
        $item = $stock->item;
        self::checkZone($item, $dest);
        $whole = abs($qty - $stock->qty) < 0.0005;
        $lpn = ($keepLpn ?? $whole) ? $stock->lpn : null;

        return DB::transaction(function () use ($stock, $dest, $qty, $reference, $doc, $lpn, $kind, $item) {
            $from = $stock->location_id;
            $stock->qty = round($stock->qty - $qty, 3);
            $stock->save();
            $new = self::add($item, $dest, $qty, $stock->lot, $lpn, $stock->owner_id, $stock->in_date);
            self::ledger(['reference' => $reference, 'kind' => $kind, 'document_id' => $doc?->id, 'item_id' => $item->id,
                'lot_id' => $stock->lot_id, 'from_location_id' => $from, 'to_location_id' => $dest->id, 'lpn_id' => $stock->lpn_id,
                'result_lpn_id' => $lpn?->id, 'owner_id' => $stock->owner_id, 'qty' => $qty]);
            if ($lpn) {
                Carton::where('lpn_id', $lpn->id)->update(['location_id' => $dest->id,
                    'state' => $dest->area === 'qc' ? 'quarantine' : (in_array($dest->area, ['dmg', 'wst']) ? 'waste' : 'available')]);
            }
            if ($stock->qty <= 0.0005 && $stock->reserved_qty <= 0.0005) {
                $stock->delete();
            }

            return $new;
        });
    }

    /** Pindah internal dengan dokumen (bin transfer, QC hold, put away web): satu dokumen per pemindahan. */
    public static function transfer(Stock $stock, Location $dest, float $qty, string $reason): Document
    {
        return DB::transaction(function () use ($stock, $dest, $qty, $reason) {
            $doc = Document::create(['number' => Seq::next('INT'), 'type' => 'internal', 'warehouse_id' => $stock->location->warehouse_id,
                'state' => 'done', 'reason' => $reason, 'scheduled_at' => self::now(), 'done_at' => self::now(),
                'owner_id' => $stock->owner_id, 'user_id' => auth()->id(), 'done_by' => auth()->id()]);
            $line = $doc->lines()->create(['item_id' => $stock->item_id, 'uom_id' => $stock->item->uom_id, 'qty_demand' => $qty]);
            $doc->details()->create(['document_line_id' => $line->id, 'item_id' => $stock->item_id, 'lot_id' => $stock->lot_id,
                'from_location_id' => $stock->location_id, 'to_location_id' => $dest->id, 'lpn_id' => $stock->lpn_id,
                'result_lpn_id' => abs($qty - $stock->qty) < 0.0005 ? $stock->lpn_id : null, 'qty' => $qty, 'picked' => true]);
            self::move($stock, $dest, $qty, $doc->number, $doc);

            return $doc;
        });
    }

    /** Pindahkan satu LPN utuh (semua stoknya) ke lokasi tujuan. */
    public static function moveLpn(Lpn $lpn, Location $dest, string $reason = 'Bin transfer'): int
    {
        $stocks = $lpn->liveStocks()->with('item', 'location')->get();
        if ($stocks->isEmpty()) {
            throw new WmsException("LPN {$lpn->name} kosong.");
        }
        $n = 0;
        foreach ($stocks as $s) {
            if ($s->location_id === $dest->id) {
                continue;
            }
            if ($s->reserved_qty > 0) {
                throw new WmsException("LPN {$lpn->name} sedang direservasi untuk picking; batalkan reservasi dulu.");
            }
            self::transfer($s, $dest, $s->qty, $reason);
            $n++;
        }

        return $n;
    }

    /** Penyesuaian qty (inventory check / cycle count) dengan kartu stok "adjust". */
    public static function adjust(Stock $stock, float $newQty, string $reference): void
    {
        $diff = round($newQty - $stock->qty, 3);
        if (abs($diff) < 0.0005) {
            return;
        }
        $stock->qty = round($newQty, 3);
        $stock->counted_qty = null;
        $stock->save();
        self::ledger(['reference' => $reference, 'kind' => 'adjust', 'item_id' => $stock->item_id, 'lot_id' => $stock->lot_id,
            'from_location_id' => $diff < 0 ? $stock->location_id : null, 'to_location_id' => $diff > 0 ? $stock->location_id : null,
            'from_label' => $diff > 0 ? 'Penyesuaian' : null, 'to_label' => $diff < 0 ? 'Penyesuaian' : null,
            'lpn_id' => $stock->lpn_id, 'result_lpn_id' => $stock->lpn_id, 'owner_id' => $stock->owner_id, 'qty' => abs($diff)]);
        if ($stock->qty <= 0.0005 && $stock->reserved_qty <= 0.0005) {
            $stock->delete();
        }
    }

    /** Stok tersedia untuk picking FEFO: area simpan (blk/pck/tanpa area), belum kedaluwarsa, memenuhi batas pelanggan. */
    public static function fefoStocks(Item $item, ?int $ownerId = null, ?\App\Models\Partner $customer = null, ?int $warehouseId = null)
    {
        $min = $customer?->min_shelf_life_pct ?: 0;
        $life = $item->shelfLife();

        return Stock::with('lot', 'location', 'lpn')
            ->where('item_id', $item->id)->where('qty', '>', 0)->whereColumn('qty', '>', 'reserved_qty')
            ->where('owner_id', $ownerId)
            ->whereHas('location', fn ($q) => $q->where(fn ($w) => $w->whereIn('area', ['blk', 'pck'])->orWhereNull('area'))
                ->when($warehouseId, fn ($w) => $w->where('warehouse_id', $warehouseId)))
            ->get()
            ->filter(function (Stock $s) use ($min, $life) {
                if (! $s->lot || ! $s->lot->expiry_date) {
                    return true;
                }
                if ($s->lot->expiry_date->lte(self::now())) {
                    return false;
                }

                return ! $min || (self::now()->diffInSeconds($s->lot->expiry_date) / 86400 / $life * 100) >= $min;
            })
            ->sortBy(fn (Stock $s) => [$item->picking_strategy === 'fifo' ? optional($s->in_date)->timestamp : optional($s->lot?->expiry_date)->timestamp ?? PHP_INT_MAX,
                $s->location?->area === 'pck' ? 0 : 1, $s->location?->full_name])
            ->values();
    }
}
