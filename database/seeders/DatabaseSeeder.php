<?php

namespace Database\Seeders;

use App\Models\BarcodeTemplate;
use App\Models\Location;
use App\Models\Setting;
use App\Models\Uom;
use App\Models\User;
use App\Models\Warehouse;
use App\Models\Zone;
use Illuminate\Database\Seeder;
use Illuminate\Support\Str;

/**
 * Data master yang dibutuhkan setiap instalasi: pengaturan, standar zona, satuan SAP, akun admin,
 * gudang GENERAL WAREHOUSE (SAP 95-SKI) beserta struktur lokasi, dan format barcode karton.
 */
class DatabaseSeeder extends Seeder
{
    public const ZONE_CODE = ['frozen' => 'FZ', 'chilled' => 'CH', 'dry' => 'DR'];
    // Rak palet (baris x bay x level) per zona.
    public const RACKS = ['frozen' => [6, 6, 4], 'chilled' => [6, 8, 4], 'dry' => [8, 8, 4]];

    public function run(): void
    {
        foreach ([
            'company_name' => 'PT Sarana Kulina Intisejahtera', 'company_city' => 'Jakarta', 'sap_company_db' => 'SARKUL',
            'sap_mode' => 'simulation', 'sync_token' => Str::random(32),
        ] as $k => $v) {
            if (! Setting::find($k)) {
                Setting::put($k, $v);
            }
        }

        // Standar QA yang disetujui 10 November 2026.
        foreach ([['frozen', 'Frozen', 'FZ', 540, '#1e3a8a', 1], ['chilled', 'Chilled', 'CH', 180, '#0f766e', 2], ['dry', 'Dry', 'DR', 365, '#9a3412', 3]] as [$z, $n, $c, $life, $color, $seq]) {
            Zone::updateOrCreate(['zone' => $z], ['name' => $n, 'code' => $c, 'shelf_life_days' => $life, 'color' => $color, 'sequence' => $seq]);
        }

        // Satuan persediaan SAP B1. Tidak ada konversi antarsatuan: master SAP tidak membawa faktornya.
        foreach (['PCE' => 'Piece', 'PCK' => 'Pack', 'PKT' => 'Paket', 'JAR' => 'Jar', 'BTL' => 'Botol', 'CTN' => 'Karton', 'CAN' => 'Kaleng',
            'BAG' => 'Bag', 'PAIL' => 'Pail', 'TIN' => 'Tin', 'PET' => 'Pet', 'TUBE' => 'Tube', 'KG' => 'Kilogram', 'GRAM' => 'Gram'] as $code => $name) {
            Uom::updateOrCreate(['code' => $code], ['name' => $name, 'is_weight' => in_array($code, ['KG', 'GRAM'])]);
        }

        User::firstOrCreate(['username' => 'admin'], ['name' => 'Administrator', 'email' => 'admin@foodtrace.local', 'role' => 'admin',
            'password' => Str::random(16)]);

        if (! Warehouse::exists()) {
            $this->warehouse();
        }

        if (! BarcodeTemplate::exists()) {
            BarcodeTemplate::create(['name' => 'GS1-128 berat kg (AI 3102)', 'kind' => 'gs1', 'sequence' => 1,
                'sample' => '(01)09312345678907(3102)001250(10)LOT123(17)270630(21)00001']);
            BarcodeTemplate::create(['name' => 'Label US berat lbs (posisi tetap)', 'kind' => 'regex', 'sequence' => 2, 'weight_unit' => 'lb',
                'weight_decimals' => 2, 'pattern' => '^(?P<carton>\d{6})(?P<weight>\d{4})(?P<lot>[A-Z0-9]{5})$', 'sample' => '1234562450AB123']);
        }
    }

    private function warehouse(): void
    {
        $wh = Warehouse::create(['code' => 'SKI', 'name' => 'GENERAL WAREHOUSE', 'sap_code' => '95-SKI', 'address' => 'Jakarta']);
        $stock = Location::create(['warehouse_id' => $wh->id, 'name' => 'Stock', 'barcode' => 'SKI-STOCK', 'is_bin' => false]);
        $now = now();
        foreach (self::ZONE_CODE as $zone => $code) {
            $room = Location::create(['warehouse_id' => $wh->id, 'parent_id' => $stock->id, 'name' => $code, 'zone' => $zone, 'area' => 'room',
                'barcode' => "SKI-{$code}", 'is_bin' => false]);
            $blk = Location::create(['warehouse_id' => $wh->id, 'parent_id' => $room->id, 'name' => 'BLK', 'area' => 'blk',
                'barcode' => "SKI-{$code}-BLK", 'is_bin' => false]);
            [$rows, $bays, $levels] = self::RACKS[$zone];
            $vals = [];
            for ($r = 0; $r < $rows; $r++) {
                for ($b = 1; $b <= $bays; $b++) {
                    for ($lv = 1; $lv <= $levels; $lv++) {
                        $name = chr(65 + $r).sprintf('%02d', $b).'-'.$lv;
                        $vals[] = ['warehouse_id' => $wh->id, 'parent_id' => $blk->id, 'name' => $name, 'full_name' => $blk->full_name.'/'.$name,
                            'barcode' => "SKI-{$code}-{$name}", 'area' => 'blk', 'eff_zone' => $zone, 'is_bin' => true, 'max_pallets' => 1,
                            'max_weight' => 1000, 'active' => true, 'created_at' => $now, 'updated_at' => $now];
                    }
                }
            }
            Location::insert($vals);
            foreach ([['PCK', 'pck', null], ['QC', 'qc', $zone], ['DMG', 'dmg', $zone]] as [$n, $area, $z]) {
                Location::create(['warehouse_id' => $wh->id, 'parent_id' => $room->id, 'name' => $n, 'area' => $area, 'zone' => $z, 'barcode' => "SKI-{$code}-{$n}"]);
            }
        }
        foreach ([1, 2, 3] as $n) {
            Location::create(['warehouse_id' => $wh->id, 'parent_id' => $stock->id, 'name' => sprintf('DOCK_%02d', $n), 'area' => 'rcv', 'barcode' => sprintf('DOCK_%02d', $n)]);
        }
        Location::create(['warehouse_id' => $wh->id, 'parent_id' => $stock->id, 'name' => 'OUT-STAGING', 'area' => 'out', 'barcode' => 'SKI-OUT']);
        Location::create(['warehouse_id' => $wh->id, 'parent_id' => $stock->id, 'name' => 'RTN', 'area' => 'rtn', 'barcode' => 'SKI-RTN']);
        Location::create(['warehouse_id' => $wh->id, 'parent_id' => $stock->id, 'name' => 'WASTE', 'area' => 'wst', 'barcode' => 'SKI-WST']);
        Location::create(['warehouse_id' => $wh->id, 'parent_id' => $stock->id, 'name' => 'BUTCHER-ROOM', 'area' => 'prd', 'zone' => 'chilled', 'barcode' => 'SKI-PRD']);
    }
}
