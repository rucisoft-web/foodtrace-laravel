<?php

namespace App\Services;

use App\Models\InventoryCheck;
use App\Models\Location;
use App\Models\Lpn;
use App\Models\Stock;
use App\Support\Seq;
use App\Support\WmsException;
use Illuminate\Support\Facades\DB;

/** Inventory Check ala Largo: cek fisik per LPN/lokasi, selisih disetujui kepala gudang; dan cycle count buta. */
class InventoryService
{
    public static function create(?Lpn $lpn, ?Location $loc): InventoryCheck
    {
        $check = InventoryCheck::create(['number' => Seq::next('IC'), 'mode' => $lpn ? 'lpn' : 'location', 'lpn_id' => $lpn?->id,
            'location_id' => $loc?->id, 'date' => StockService::now(), 'user_id' => auth()->id()]);
        self::load($check);

        return $check;
    }

    public static function load(InventoryCheck $check): void
    {
        if ($check->state !== 'draft') {
            return;
        }
        $check->lines()->delete();
        $q = Stock::with('lot')->where('qty', '>', 0);
        if ($check->mode === 'lpn') {
            if (! $check->lpn_id) {
                throw new WmsException('Pilih / scan LPN.');
            }
            $q->where('lpn_id', $check->lpn_id);
        } else {
            if (! $check->location) {
                throw new WmsException('Pilih / scan lokasi.');
            }
            $q->whereIn('location_id', $check->location->descendantIds());
        }
        foreach ($q->get() as $s) {
            $check->lines()->create(['stock_id' => $s->id, 'item_id' => $s->item_id, 'lot_id' => $s->lot_id, 'lpn_id' => $s->lpn_id,
                'location_id' => $s->location_id, 'qty_before' => $s->qty, 'qty_after' => $s->qty,
                'expiry_before' => $s->lot?->expiry_date, 'expiry_after' => $s->lot?->expiry_date]);
        }
    }

    public static function submit(InventoryCheck $check, array $rows = []): void
    {
        foreach ($rows as $id => $row) {
            $line = $check->lines()->find($id);
            if (! $line) {
                continue;
            }
            $line->qty_after = (float) ($row['qty'] ?? $line->qty_after);
            if (! empty($row['expiry'])) {
                $line->expiry_after = $row['expiry'];
            }
            $line->remark = $row['remark'] ?? $line->remark;
            $line->need_reprint = ! empty($row['reprint']) || ($line->lpn_id && $line->isDifference());
            $line->save();
        }
        if (! $check->lines()->exists()) {
            throw new WmsException('Belum ada baris hasil cek.');
        }
        $check->update(['state' => 'submitted']);
    }

    /** Terapkan selisih qty sebagai penyesuaian, ubah expiry lot, tandai LPN perlu cetak ulang. */
    public static function approve(InventoryCheck $check): void
    {
        if ($check->state !== 'submitted') {
            throw new WmsException('Hanya dokumen yang menunggu approval yang bisa disetujui.');
        }
        DB::transaction(function () use ($check) {
            foreach ($check->lines()->with('lot', 'stock')->get() as $line) {
                if ($line->expiryChanged() && $line->lot) {
                    $line->lot->update(['expiry_date' => $line->expiry_after]);
                }
                if (abs($line->difference()) > 0.0005) {
                    $stock = $line->stock ?? Stock::firstOrCreate(['item_id' => $line->item_id, 'lot_id' => $line->lot_id,
                        'lpn_id' => $line->lpn_id, 'location_id' => $line->location_id, 'owner_id' => null], ['qty' => 0]);
                    StockService::adjust($stock, $line->qty_after, $check->number);
                }
                if ($line->need_reprint && $line->lpn) {
                    $line->lpn->update(['need_reprint' => true]);
                }
            }
            $check->update(['state' => 'approved', 'approved_by' => auth()->id()]);
        });
    }

    /** Cycle count: setujui hitungan (counted_qty) menjadi stok. */
    public static function applyCounts($stocks, string $reference): int
    {
        $n = 0;
        DB::transaction(function () use ($stocks, $reference, &$n) {
            foreach ($stocks as $s) {
                if ($s->counted_qty === null) {
                    continue;
                }
                StockService::adjust($s, $s->counted_qty, $reference);
                if ($s->exists) {
                    $s->update(['counted_qty' => null, 'counted_by' => null]);
                }
                $n++;
            }
        });

        return $n;
    }
}
