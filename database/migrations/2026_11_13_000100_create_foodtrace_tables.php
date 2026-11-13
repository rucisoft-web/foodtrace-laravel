<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Skema FoodTrace (WMS gudang makanan cold chain), setara dengan add-on Odoo foodtrace + foodtrace_mobile.
 * Stok dicatat per item + lot + LPN + lokasi + owner (tabel stocks), setiap perpindahan tercatat di stock_moves.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('settings', function (Blueprint $t) {
            $t->string('key', 80)->primary();
            $t->text('value')->nullable();
        });

        // Standar zona penyimpanan: Frozen, Chilled, Dry.
        Schema::create('zones', function (Blueprint $t) {
            $t->id();
            $t->string('zone', 10)->unique(); // frozen, chilled, dry
            $t->string('name', 40);
            $t->string('code', 6); // FZ, CH, DR
            $t->unsignedInteger('shelf_life_days')->default(365);
            $t->string('color', 9)->default('#1e3a8a');
            $t->unsignedSmallInteger('sequence')->default(10);
            $t->timestamps();
        });

        // Satuan persediaan SAP (OITM.InvntryUom). Tanpa faktor konversi antarsatuan.
        Schema::create('uoms', function (Blueprint $t) {
            $t->id();
            $t->string('code', 10)->unique();
            $t->string('name', 40);
            $t->boolean('is_weight')->default(false);
            $t->timestamps();
        });

        // Brand SAP (U_Brand), mis. "LAI - LAITA".
        Schema::create('brands', function (Blueprint $t) {
            $t->id();
            $t->string('code', 20)->unique();
            $t->string('name', 120);
            $t->boolean('active')->default(true);
            $t->timestamps();
        });

        Schema::create('items', function (Blueprint $t) {
            $t->id();
            $t->string('code', 40)->unique(); // ItemCode SAP
            $t->string('name');
            $t->string('barcode', 60)->nullable()->index();
            $t->string('item_group', 60)->nullable()->index();
            $t->string('sub_group', 60)->nullable();
            $t->foreignId('brand_id')->nullable()->constrained()->nullOnDelete();
            $t->foreignId('uom_id')->constrained();
            $t->string('zone', 10)->index();
            $t->boolean('catch_weight')->default(false);
            $t->string('country', 60)->nullable();
            $t->decimal('cost', 18, 2)->default(0);
            $t->decimal('last_price', 18, 2)->default(0);
            $t->unsignedInteger('shelf_life_days')->default(0);
            $t->unsignedTinyInteger('min_receive_life_pct')->default(0);
            $t->boolean('auto_quarantine')->default(false);
            $t->unsignedSmallInteger('quarantine_days')->default(7);
            $t->boolean('auto_release')->default(false);
            $t->decimal('units_per_pallet', 14, 3)->default(0);
            $t->boolean('multi_qty')->default(false);
            $t->string('picking_strategy', 4)->default('fefo');
            $t->decimal('min_stock', 14, 3)->default(0);
            $t->decimal('weight_kg', 10, 3)->default(0); // berat per satuan, untuk total berat muatan
            $t->boolean('active')->default(true);
            $t->timestamps();
        });

        Schema::create('barcode_templates', function (Blueprint $t) {
            $t->id();
            $t->unsignedSmallInteger('sequence')->default(10);
            $t->string('name');
            $t->string('kind', 6)->default('gs1'); // gs1, regex
            $t->string('pattern')->nullable();
            $t->string('weight_unit', 2)->default('kg');
            $t->unsignedTinyInteger('weight_decimals')->default(2);
            $t->decimal('min_weight', 10, 3)->default(0);
            $t->decimal('max_weight', 10, 3)->default(0);
            $t->string('sample')->nullable();
            $t->boolean('active')->default(true);
            $t->timestamps();
        });
        Schema::create('barcode_template_item', function (Blueprint $t) {
            $t->foreignId('barcode_template_id')->constrained()->cascadeOnDelete();
            $t->foreignId('item_id')->constrained()->cascadeOnDelete();
            $t->primary(['barcode_template_id', 'item_id']);
        });

        Schema::create('warehouses', function (Blueprint $t) {
            $t->id();
            $t->string('code', 10)->unique();
            $t->string('name');
            $t->string('sap_code', 20)->nullable()->index(); // nilai "warehouse" di file sinkron, mis. 95-SKI
            $t->string('address')->nullable();
            $t->timestamps();
        });

        Schema::create('locations', function (Blueprint $t) {
            $t->id();
            $t->foreignId('warehouse_id')->constrained();
            $t->foreignId('parent_id')->nullable()->constrained('locations')->nullOnDelete();
            $t->string('name', 40);
            $t->string('full_name', 160)->index();
            $t->string('barcode', 60)->nullable()->unique();
            $t->string('zone', 10)->nullable(); // kosong = ikut induk
            $t->string('area', 6)->nullable(); // rcv, in, blk, pck, qc, rtn, dmg, wst, out, prd, room
            $t->string('eff_zone', 10)->nullable()->index(); // zona efektif (diturunkan dari induk)
            $t->boolean('is_bin')->default(true); // lokasi daun yang bisa diisi stok
            $t->unsignedSmallInteger('max_pallets')->default(0);
            $t->decimal('max_weight', 10, 2)->default(0);
            $t->boolean('active')->default(true);
            $t->timestamps();
            $t->index(['area', 'eff_zone']);
        });

        // Source Master (pemasok) dan Destination Master (pelanggan). Satu partner bisa keduanya.
        Schema::create('partners', function (Blueprint $t) {
            $t->id();
            $t->string('code', 30)->unique(); // CardCode SAP
            $t->string('name');
            $t->boolean('is_source')->default(false)->index();
            $t->boolean('is_destination')->default(false)->index();
            $t->string('owner_code', 30)->nullable()->index(); // nilai "owner" di file sinkron (titipan)
            $t->unsignedTinyInteger('min_shelf_life_pct')->default(0);
            $t->decimal('over_receive_pct', 5, 2)->default(0);
            $t->string('shipping_group', 40)->nullable();
            $t->string('address')->nullable();
            $t->string('city', 80)->nullable();
            $t->string('phone', 40)->nullable();
            $t->boolean('active')->default(true);
            $t->timestamps();
        });
        // ShipTo SAP: satu pelanggan banyak outlet tujuan.
        Schema::create('ship_tos', function (Blueprint $t) {
            $t->id();
            $t->foreignId('partner_id')->constrained()->cascadeOnDelete();
            $t->string('code', 60)->nullable();
            $t->string('name');
            $t->string('address')->nullable();
            $t->string('city', 80)->nullable();
            $t->string('phone', 40)->nullable();
            $t->timestamps();
            $t->index(['partner_id', 'name']);
        });

        Schema::create('lots', function (Blueprint $t) {
            $t->id();
            $t->foreignId('item_id')->constrained();
            $t->string('name', 80);
            $t->dateTime('expiry_date')->nullable()->index();
            $t->date('mfg_date')->nullable();
            $t->date('pack_date')->nullable();
            $t->string('grade', 40)->nullable();
            $t->string('brand', 120)->nullable();
            $t->string('cutting_type', 80)->nullable();
            $t->string('est', 40)->nullable(); // EST / RPH
            $t->timestamps();
            $t->unique(['item_id', 'name']);
        });

        // LPN palet: 1 SKU + 1 lot.
        Schema::create('lpns', function (Blueprint $t) {
            $t->id();
            $t->string('name', 30)->unique();
            $t->foreignId('item_id')->nullable()->constrained();
            $t->foreignId('lot_id')->nullable()->constrained();
            $t->string('pallet_no', 30)->nullable();
            $t->date('mfg_date')->nullable();
            $t->date('pack_date')->nullable();
            $t->string('remark', 120)->nullable();
            $t->string('packaging', 60)->nullable();
            $t->boolean('closed')->default(false);
            $t->boolean('need_reprint')->default(false)->index();
            $t->timestamps();
        });

        // Stok (quant). Status (Receiving/Available/Picked/Hold/Damage) diturunkan dari area lokasi.
        Schema::create('stocks', function (Blueprint $t) {
            $t->id();
            $t->foreignId('item_id')->constrained();
            $t->foreignId('lot_id')->nullable()->constrained();
            $t->foreignId('lpn_id')->nullable()->constrained();
            $t->foreignId('location_id')->constrained();
            $t->foreignId('owner_id')->nullable()->constrained('partners');
            $t->decimal('qty', 16, 3)->default(0);
            $t->decimal('reserved_qty', 16, 3)->default(0);
            $t->decimal('counted_qty', 16, 3)->nullable(); // cycle count (hitung buta)
            $t->foreignId('counted_by')->nullable()->constrained('users')->nullOnDelete();
            $t->dateTime('in_date')->nullable();
            $t->timestamps();
            $t->index(['item_id', 'location_id']);
            $t->index(['location_id', 'qty']);
        });

        Schema::create('sync_documents', function (Blueprint $t) {
            $t->id();
            $t->string('number', 60)->unique();
            $t->string('direction', 10); // inbound, outbound
            $t->string('doc_type', 60)->nullable();
            $t->string('obj_type', 10)->nullable();
            $t->string('web_trans_no', 40)->nullable();
            $t->string('doc_entry', 30)->nullable();
            $t->string('partner_code', 30)->nullable();
            $t->string('partner_name')->nullable();
            $t->string('warehouse_code', 20)->nullable();
            $t->string('owner_code', 20)->nullable();
            $t->string('shipping_group', 40)->nullable();
            $t->string('vendor_ref', 120)->nullable();
            $t->string('container_no', 40)->nullable();
            $t->date('doc_date')->nullable();
            $t->date('delivery_date')->nullable();
            $t->string('priority', 20)->nullable();
            $t->string('sap_status', 20)->nullable();
            $t->unsignedInteger('line_count')->default(0);
            $t->longText('payload');
            $t->string('state', 10)->default('new')->index(); // new, done, error
            $t->text('error')->nullable();
            $t->unsignedBigInteger('document_id')->nullable()->index();
            $t->timestamps();
        });

        Schema::create('picking_jobs', function (Blueprint $t) {
            $t->id();
            $t->string('number', 30)->unique();
            $t->string('description')->nullable();
            $t->boolean('use_tote')->default(false);
            $t->string('driver', 60)->nullable();
            $t->string('transporter', 60)->nullable();
            $t->string('state', 12)->default('in_progress')->index(); // in_progress, done, cancel
            $t->foreignId('user_id')->nullable()->constrained()->nullOnDelete();
            $t->timestamps();
        });

        Schema::create('loadings', function (Blueprint $t) {
            $t->id();
            $t->string('number', 30)->unique();
            $t->date('date');
            $t->string('driver', 60)->nullable();
            $t->string('vehicle_plate', 20)->nullable();
            $t->string('state', 8)->default('open')->index(); // open, close, cancel
            $t->timestamps();
        });

        // Dokumen gudang: inbound (receiving), outbound (delivery), internal (put away / bin transfer).
        Schema::create('documents', function (Blueprint $t) {
            $t->id();
            $t->string('number', 30)->unique();
            $t->string('type', 10)->index(); // inbound, outbound, internal
            $t->foreignId('warehouse_id')->constrained();
            $t->string('origin', 60)->nullable()->index(); // nomor dokumen SAP
            $t->foreignId('partner_id')->nullable()->constrained();
            $t->foreignId('ship_to_id')->nullable()->constrained()->nullOnDelete();
            $t->foreignId('owner_id')->nullable()->constrained('partners');
            $t->dateTime('scheduled_at')->nullable()->index();
            $t->dateTime('done_at')->nullable()->index();
            $t->string('state', 10)->default('open')->index(); // open, ready, done, cancel
            $t->string('inbound_type', 12)->nullable();
            $t->string('outbound_type', 14)->nullable();
            $t->string('shipping_group', 40)->nullable()->index();
            $t->string('priority', 20)->nullable();
            $t->string('vehicle_plate', 20)->nullable();
            $t->string('driver', 60)->nullable();
            $t->string('delivery_note', 60)->nullable();
            $t->foreignId('dock_id')->nullable()->constrained('locations')->nullOnDelete();
            $t->string('container_no', 40)->nullable();
            $t->string('vendor_ref', 120)->nullable();
            $t->foreignId('sync_document_id')->nullable()->constrained()->nullOnDelete();
            $t->string('stage', 10)->default('open')->index(); // outbound: open, picking, staged, checked, loading, shipped, delivered
            $t->foreignId('staging_location_id')->nullable()->constrained('locations')->nullOnDelete();
            $t->foreignId('picking_job_id')->nullable()->constrained()->nullOnDelete();
            $t->foreignId('loading_id')->nullable()->constrained()->nullOnDelete();
            $t->string('reason')->nullable(); // internal: QC Hold, Bin transfer HHT, dst.
            $t->text('remark')->nullable();
            $t->foreignId('user_id')->nullable()->constrained()->nullOnDelete();
            $t->foreignId('done_by')->nullable()->constrained('users')->nullOnDelete();
            $t->timestamps();
        });

        Schema::create('document_lines', function (Blueprint $t) {
            $t->id();
            $t->foreignId('document_id')->constrained()->cascadeOnDelete();
            $t->foreignId('item_id')->constrained();
            $t->foreignId('uom_id')->constrained();
            $t->decimal('qty_demand', 16, 3)->default(0);
            $t->string('description')->nullable();
            $t->unsignedSmallInteger('line_no')->default(0);
            $t->timestamps();
        });

        // Rincian per lot/LPN/lokasi (move line): hasil scan receiving, reservasi FEFO picking, dst.
        Schema::create('document_details', function (Blueprint $t) {
            $t->id();
            $t->foreignId('document_id')->constrained()->cascadeOnDelete();
            $t->foreignId('document_line_id')->constrained()->cascadeOnDelete();
            $t->foreignId('item_id')->constrained();
            $t->foreignId('lot_id')->nullable()->constrained();
            $t->foreignId('stock_id')->nullable()->constrained()->nullOnDelete(); // stok yang direservasi
            $t->foreignId('from_location_id')->nullable()->constrained('locations');
            $t->foreignId('to_location_id')->nullable()->constrained('locations');
            $t->foreignId('lpn_id')->nullable()->constrained(); // LPN asal
            $t->foreignId('result_lpn_id')->nullable()->constrained('lpns'); // LPN tujuan
            $t->decimal('qty', 16, 3)->default(0);
            $t->boolean('picked')->default(false);
            $t->boolean('checked')->default(false);
            $t->boolean('loaded')->default(false);
            $t->timestamps();
        });

        // Kartu stok: setiap perpindahan selesai.
        Schema::create('stock_moves', function (Blueprint $t) {
            $t->id();
            $t->dateTime('date')->index();
            $t->string('reference', 60)->index();
            $t->string('kind', 10)->index(); // in, out, internal, adjust, butcher
            $t->foreignId('document_id')->nullable()->constrained()->nullOnDelete();
            $t->foreignId('item_id')->constrained();
            $t->foreignId('lot_id')->nullable()->constrained();
            $t->foreignId('from_location_id')->nullable()->constrained('locations');
            $t->foreignId('to_location_id')->nullable()->constrained('locations');
            $t->string('from_label', 40)->nullable(); // Pemasok, Pelanggan, Penyesuaian, Ruang potong
            $t->string('to_label', 40)->nullable();
            $t->foreignId('lpn_id')->nullable()->constrained();
            $t->foreignId('result_lpn_id')->nullable()->constrained('lpns');
            $t->foreignId('owner_id')->nullable()->constrained('partners');
            $t->foreignId('partner_id')->nullable()->constrained();
            $t->decimal('qty', 16, 3);
            $t->string('uom', 10);
            $t->decimal('value', 18, 2)->default(0);
            $t->foreignId('user_id')->nullable()->constrained()->nullOnDelete();
            $t->timestamps();
            $t->index(['item_id', 'date']);
        });

        Schema::create('cartons', function (Blueprint $t) {
            $t->id();
            $t->string('name', 60);
            $t->string('raw_barcode', 160)->nullable();
            $t->foreignId('item_id')->constrained();
            $t->foreignId('lot_id')->nullable()->constrained();
            $t->foreignId('lpn_id')->nullable()->constrained();
            $t->foreignId('location_id')->nullable()->constrained();
            $t->foreignId('owner_id')->nullable()->constrained('partners');
            $t->decimal('weight_kg', 10, 3)->default(0);
            $t->decimal('weight_lb', 10, 3)->default(0);
            $t->string('state', 12)->default('available')->index(); // available, quarantine, shipped, waste
            $t->foreignId('in_document_id')->nullable()->constrained('documents')->nullOnDelete();
            $t->foreignId('out_document_id')->nullable()->constrained('documents')->nullOnDelete();
            $t->boolean('manual_weight')->default(false);
            $t->timestamps();
            $t->unique(['item_id', 'name']);
        });

        // Antrean posting ke SAP B1 (GRPO, Delivery, Transfer). Mode simulasi sampai Service Layer disambungkan.
        Schema::create('sap_postings', function (Blueprint $t) {
            $t->id();
            $t->string('ref', 80)->unique();
            $t->foreignId('document_id')->nullable()->constrained()->nullOnDelete();
            $t->string('doc_type', 12); // grpo, delivery, transfer, adjustment, return
            $t->string('sap_company_db', 40)->nullable();
            $t->string('state', 8)->default('queued')->index(); // queued, posted, failed
            $t->string('sap_doc_num', 20)->nullable();
            $t->text('error')->nullable();
            $t->unsignedSmallInteger('attempts')->default(0);
            $t->dateTime('posted_at')->nullable();
            $t->decimal('total_qty', 16, 3)->default(0);
            $t->decimal('total_weight', 16, 3)->default(0);
            $t->timestamps();
        });

        Schema::create('pods', function (Blueprint $t) {
            $t->id();
            $t->string('name', 30);
            $t->foreignId('document_id')->constrained()->cascadeOnDelete();
            $t->string('token', 40)->unique();
            $t->string('state', 6)->default('open'); // open, done
            $t->string('received_by', 80)->nullable();
            $t->dateTime('received_at')->nullable();
            $t->string('photo_path')->nullable();
            $t->text('remark')->nullable();
            $t->boolean('has_difference')->default(false);
            $t->timestamps();
        });
        Schema::create('pod_lines', function (Blueprint $t) {
            $t->id();
            $t->foreignId('pod_id')->constrained()->cascadeOnDelete();
            $t->foreignId('item_id')->constrained();
            $t->string('uom', 10)->nullable();
            $t->decimal('qty_sent', 16, 3)->default(0);
            $t->decimal('qty_received', 16, 3)->default(0);
            $t->string('remark')->nullable();
            $t->timestamps();
        });

        Schema::create('inventory_checks', function (Blueprint $t) {
            $t->id();
            $t->string('number', 30)->unique();
            $t->string('mode', 10)->default('location'); // lpn, location
            $t->foreignId('location_id')->nullable()->constrained();
            $t->foreignId('lpn_id')->nullable()->constrained();
            $t->dateTime('date');
            $t->foreignId('user_id')->nullable()->constrained()->nullOnDelete();
            $t->foreignId('approved_by')->nullable()->constrained('users')->nullOnDelete();
            $t->string('state', 10)->default('draft')->index(); // draft, submitted, approved, rejected
            $t->text('remark')->nullable();
            $t->timestamps();
        });
        Schema::create('inventory_check_lines', function (Blueprint $t) {
            $t->id();
            $t->foreignId('inventory_check_id')->constrained()->cascadeOnDelete();
            $t->foreignId('stock_id')->nullable()->constrained()->nullOnDelete();
            $t->foreignId('item_id')->constrained();
            $t->foreignId('lot_id')->nullable()->constrained();
            $t->foreignId('lpn_id')->nullable()->constrained();
            $t->foreignId('location_id')->constrained();
            $t->decimal('qty_before', 16, 3)->default(0);
            $t->decimal('qty_after', 16, 3)->default(0);
            $t->dateTime('expiry_before')->nullable();
            $t->dateTime('expiry_after')->nullable();
            $t->boolean('need_reprint')->default(false);
            $t->string('remark')->nullable();
            $t->timestamps();
        });

        Schema::create('butchers', function (Blueprint $t) {
            $t->id();
            $t->string('number', 30)->unique();
            $t->string('kind', 3)->default('out'); // out, in
            $t->string('ref', 60)->nullable();
            $t->foreignId('source_id')->nullable()->constrained('butchers')->nullOnDelete();
            $t->dateTime('date');
            $t->foreignId('user_id')->nullable()->constrained()->nullOnDelete();
            $t->string('state', 8)->default('draft'); // draft, done, cancel
            $t->decimal('total_kg', 14, 3)->default(0);
            $t->timestamps();
        });
        Schema::create('butcher_lines', function (Blueprint $t) {
            $t->id();
            $t->foreignId('butcher_id')->constrained()->cascadeOnDelete();
            $t->string('process_type', 12)->default('rebag'); // rebag, nonrebag, trimming, sisa_potong, repack
            $t->foreignId('lpn_id')->nullable()->constrained();
            $t->foreignId('item_id')->constrained();
            $t->foreignId('lot_id')->nullable()->constrained();
            $t->foreignId('location_id')->nullable()->constrained();
            $t->decimal('qty', 16, 3)->default(0);
            $t->decimal('qty_kg', 16, 3)->default(0);
            $t->string('grade', 40)->nullable();
            $t->string('brand', 120)->nullable();
            $t->string('cutting_type', 80)->nullable();
            $t->string('lot_name', 80)->nullable();
            $t->dateTime('expiry_date')->nullable();
            $t->timestamps();
        });

        // Pengumuman di landing page, diisi Admin, ditujukan ke peran tertentu (kosong = semua peran).
        Schema::create('announcements', function (Blueprint $t) {
            $t->id();
            $t->string('title');
            $t->text('body');
            $t->json('roles')->nullable();
            $t->string('level', 10)->default('info'); // info, penting, darurat
            $t->boolean('pinned')->default(false);
            $t->date('starts_at')->nullable();
            $t->date('ends_at')->nullable();
            $t->foreignId('user_id')->nullable()->constrained()->nullOnDelete();
            $t->timestamps();
        });

        Schema::create('sequences', function (Blueprint $t) {
            $t->string('code', 40)->primary();
            $t->unsignedInteger('next')->default(1);
        });
    }

    public function down(): void
    {
        foreach (['sequences', 'announcements', 'butcher_lines', 'butchers', 'inventory_check_lines', 'inventory_checks', 'pod_lines', 'pods',
            'sap_postings', 'cartons', 'stock_moves', 'document_details', 'document_lines', 'documents', 'loadings',
            'picking_jobs', 'sync_documents', 'stocks', 'lpns', 'lots', 'ship_tos', 'partners', 'locations', 'warehouses',
            'barcode_template_item', 'barcode_templates', 'items', 'brands', 'uoms', 'zones', 'settings'] as $table) {
            Schema::dropIfExists($table);
        }
    }
};
