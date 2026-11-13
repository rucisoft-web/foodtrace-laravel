<?php

namespace App\Services;

use App\Models\Butcher;
use App\Models\Location;
use App\Models\Lot;
use App\Models\Lpn;
use App\Models\Stock;
use App\Support\Seq;
use App\Support\WmsException;
use Illuminate\Support\Facades\DB;

/**
 * Butcher Out: bahan (LPN) keluar dari gudang ke ruang potong (lokasi produksi).
 * Butcher In: hasil potong/repack masuk sebagai lot dan LPN baru. Yield = berat hasil / berat bahan.
 */
class ButcherService
{
    public static function productionLocation(int $warehouseId): Location
    {
        $loc = Location::where('warehouse_id', $warehouseId)->where('area', 'prd')->first();
        if (! $loc) {
            throw new WmsException('Lokasi produksi (ruang potong) belum ada.');
        }

        return $loc;
    }

    public static function kg($item, float $qty): float
    {
        return round($item->uom->is_weight ? ($item->uom->code === 'GRAM' ? $qty / 1000 : $qty) : $qty * $item->weight_kg, 3);
    }

    public static function newNumber(string $kind): string
    {
        return Seq::next($kind === 'in' ? 'BI' : 'BO');
    }

    public static function done(Butcher $doc): void
    {
        if ($doc->state !== 'draft') {
            return;
        }
        if ($doc->lines()->doesntExist()) {
            throw new WmsException('Isi baris dulu.');
        }
        DB::transaction(function () use ($doc) {
            $total = 0;
            foreach ($doc->lines()->with('item.uom', 'lpn', 'location')->get() as $line) {
                if ($line->qty <= 0) {
                    throw new WmsException("Qty {$line->item->label()} harus lebih dari 0.");
                }
                $line->qty_kg = self::kg($line->item, $line->qty);
                if ($doc->kind === 'out') {
                    $stock = Stock::where('item_id', $line->item_id)->where('qty', '>', 0)
                        ->when($line->lpn_id, fn ($q) => $q->where('lpn_id', $line->lpn_id))
                        ->when(! $line->lpn_id && $line->location_id, fn ($q) => $q->where('location_id', $line->location_id))
                        ->when($line->lot_id, fn ($q) => $q->where('lot_id', $line->lot_id))->first();
                    if (! $stock) {
                        throw new WmsException("Scan LPN atau isi lokasi asal untuk {$line->item->label()}.");
                    }
                    if ($line->qty > $stock->available() + 0.0005) {
                        throw new WmsException("Qty {$line->item->label()} melebihi stok tersedia ".\App\Support\Wms::fmt($stock->available()).'.');
                    }
                    $stock->qty = round($stock->qty - $line->qty, 3);
                    $stock->save();
                    StockService::ledger(['reference' => $doc->number, 'kind' => 'butcher', 'item_id' => $line->item_id, 'lot_id' => $stock->lot_id,
                        'from_location_id' => $stock->location_id, 'to_label' => 'Ruang potong', 'lpn_id' => $stock->lpn_id,
                        'owner_id' => $stock->owner_id, 'qty' => $line->qty]);
                    $line->lpn_id ??= $stock->lpn_id;
                    if ($stock->qty <= 0.0005 && $stock->reserved_qty <= 0.0005) {
                        $stock->delete();
                    }
                    $line->lot_id = $stock->lot_id;
                    $line->location_id ??= $stock->location_id;
                } else {
                    if (! $line->location) {
                        throw new WmsException("Isi lokasi simpan untuk hasil {$line->item->label()}.");
                    }
                    StockService::checkZone($line->item, $line->location);
                    $lot = Lot::firstOrCreate(['item_id' => $line->item_id, 'name' => $line->lot_name ?: $doc->number.'-'.$line->id], [
                        'expiry_date' => $line->expiry_date ?? now()->addDays($line->item->shelfLife()), 'grade' => $line->grade,
                        'brand' => $line->brand, 'cutting_type' => $line->cutting_type, 'pack_date' => now()->toDateString()]);
                    $lpn = Lpn::create(['name' => Seq::lpn(), 'item_id' => $line->item_id, 'lot_id' => $lot->id, 'closed' => true,
                        'pack_date' => now()->toDateString(), 'remark' => Butcher::TYPES[$line->process_type] ?? null]);
                    StockService::add($line->item, $line->location, $line->qty, $lot, $lpn);
                    StockService::ledger(['reference' => $doc->number, 'kind' => 'butcher', 'item_id' => $line->item_id, 'lot_id' => $lot->id,
                        'from_label' => 'Ruang potong', 'to_location_id' => $line->location_id, 'result_lpn_id' => $lpn->id, 'qty' => $line->qty]);
                    $line->lot_id = $lot->id;
                    $line->lpn_id = $lpn->id;
                }
                $line->save();
                $total += $line->qty_kg;
            }
            $doc->update(['state' => 'done', 'total_kg' => round($total, 3)]);
        });
    }
}
