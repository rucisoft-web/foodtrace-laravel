<?php

namespace App\Support;

/** Konstanta FoodTrace: zona, area, status stok, jenis dokumen (sama dengan versi Odoo). */
class Wms
{
    public const ZONES = ['frozen' => 'Frozen', 'chilled' => 'Chilled', 'dry' => 'Dry'];

    public const AREAS = [
        'rcv' => 'Dock penerimaan', 'in' => 'Staging masuk', 'blk' => 'Palet utuh (bulk)', 'pck' => 'Pecah palet (picking)',
        'qc' => 'Karantina & QC', 'rtn' => 'Retur', 'dmg' => 'Damage', 'wst' => 'Waste', 'out' => 'Staging keluar',
        'prd' => 'Produksi / ruang potong', 'room' => 'Ruang (induk zona)',
    ];

    public const STOCK_STATUS = ['receiving' => 'Receiving', 'available' => 'Available', 'picked' => 'Picked', 'hold' => 'Hold', 'damage' => 'Damage'];

    public const AREA_STATUS = ['rcv' => 'receiving', 'in' => 'receiving', 'out' => 'picked', 'qc' => 'hold', 'rtn' => 'hold', 'dmg' => 'damage', 'wst' => 'damage'];

    public const INBOUND_TYPES = ['po' => 'Purchase Order', 'titipan' => 'Titipan', 'transfer' => 'Warehouse Transfer',
        'retur' => 'Retur', 'butcher' => 'Hasil/sisa potong', 'sampling' => 'Sampling'];

    public const OUTBOUND_TYPES = ['so' => 'Sales Order', 'butcher' => 'Butcher/Produksi', 'dispose' => 'Pemusnahan',
        'transfer' => 'Warehouse Transfer', 'rtv' => 'Return to Vendor', 'chill_frozen' => 'Chill to Frozen', 'others' => 'Lainnya'];

    public const DOC_STATES = ['open' => 'Menunggu', 'ready' => 'Siap', 'done' => 'Selesai', 'cancel' => 'Batal'];

    public const OUT_STAGES = ['open' => 'Open', 'picking' => 'Picking berjalan', 'staged' => 'Di staging', 'checked' => 'Sudah dicek',
        'loading' => 'Loading', 'shipped' => 'Terkirim (GI)', 'delivered' => 'Diterima customer (POD)'];

    public const STATUS_COLORS = ['receiving' => 'navy', 'available' => 'green', 'picked' => 'brown', 'hold' => 'amber', 'damage' => 'red'];

    public static function fmt($n, int $dec = 3): string
    {
        $s = number_format((float) $n, $dec, ',', '.');

        return $dec ? rtrim(rtrim($s, '0'), ',') : $s;
    }

    public static function money($n): string { return 'Rp '.number_format((float) $n, 0, ',', '.'); }

    /** Rupiah ringkas untuk KPI: Rp 44,5 M / Rp 732 jt. */
    public static function moneyShort($n): string
    {
        $n = (float) $n; $a = abs($n);

        return 'Rp '.match (true) {
            $a >= 1e12 => number_format($n / 1e12, 2, ',', '.').' T',
            $a >= 1e9 => number_format($n / 1e9, 2, ',', '.').' M',
            $a >= 1e6 => number_format($n / 1e6, 1, ',', '.').' jt',
            default => number_format($n, 0, ',', '.'),
        };
    }
}
