# FoodTrace (Laravel 13 + MySQL)

WMS untuk gudang bahan makanan **frozen, chilled dan dry**, dengan fitur dan data yang sama seperti FoodTrace versi Odoo 18.
Tampilan terang dan padat dengan warna dari logo Saranakulina (cokelat, hijau daun) ditambah biru tua dan merah tua.

## Fitur

| Area | Fitur |
|---|---|
| Halaman depan | Masalah gudang makanan dan penyebabnya, fitur food safety / quality / sustainability, pengumuman per peran (diisi Admin), form masuk |
| Dashboard | KPI nilai stok, expired, okupansi, pekerjaan terbuka, SAP; 15 grafik; daftar keputusan hari ini |
| Inbound | Inbound Document dari SAP (POR), receiving dengan dock wajib, no. kontainer, scan karton GS1-128 / template (berat aktual, lbs → kg), LPN 1 SKU + 1 lot, label LPN format Largo dengan QR, tally karton, over receiving, minimum sisa umur, auto karantina |
| Put away | Per zona Frozen / Chilled / Dry, saran bin kosong, rak zona lain ditolak |
| Inventory | Stock Status (Receiving / Available / Picked / Hold / Damage), LPN List + cetak ulang, bin transfer, QC hold / release, Inventory Check dengan approval, cycle count buta, kartu stok |
| Outbound | Outbound Document dari SAP (SOR), customer dengan banyak ShipTo, picking job + reservasi FEFO, assisted picking, staging, checker, loading job, Goods Issue, surat jalan dengan QR POD, POD driver (qty diterima, foto), butcher / repack dengan yield |
| Laporan | Stok per Lokasi, Lokasi per Item, Expired, Item Low, Material In / Out, Transfer Log, Adjustment, IC Discrepancy, Warehouse Transfer; semua ekspor CSV |
| SAP B1 | Dokumen masuk POR / SOR (impor file atau API `POST /api/v1/sync` + `X-WMS-Token`), antrean posting GRPO / Delivery |
| Master | Item (satuan SAP tanpa konversi, brand SAP), Bin / Rak + label QR, Source & Destination + ShipTo, format barcode karton, zona & gudang, pengguna, pengumuman |
| HHT | 14 layar handheld: receiving, put away, picking, checker, loading, inventory check, cycle count, QC hold, butcher out, cek barcode |
| Bantuan | Tombol **？ Bantuan** di setiap layar: apa, kapan, langkah input, arti isian, aturan, tangkapan layar (65 topik). Menu **Persiapan Implementasi**: 9 tahap, 11 peran tim proyek, RACI, risiko |

Pencatatan suhu sengaja tidak dibuat (dikeluarkan dari ruang lingkup).

## Data demo

Data riil Sarana Kulina dari versi Odoo (`database/seeders/data/sarkul`): 842 item, 65 brand, pemasok & customer dengan 389 ShipTo,
riwayat PO / SO SAP diputar ulang sehingga stok akhir sama dengan OnHand SAP, ditambah pekerjaan terbuka (PO, SO, picking job, loading, inventory check, butcher, antrean SAP) dan pengumuman.
Pengguna demo (kata sandi `foodtrace`): `hendra.kepala`, `sari.inbound`, `dewi.outbound`, `agus.operator`, `budi.operator`, `rina.qa`.

## Pemasangan

- **cPanel**: lihat [INSTALL-CPANEL.md](INSTALL-CPANEL.md) (upload zip, wizard `/install` atau terminal).
- **Terminal / server sendiri**:

```bash
composer install --no-dev --optimize-autoloader
cp .env.example .env            # isi APP_URL dan DB_*
php artisan key:generate
php artisan foodtrace:install --demo --email=admin@perusahaan.co.id
# cron: * * * * * cd /path/foodtrace && php artisan schedule:run >> /dev/null 2>&1
```

Persyaratan: PHP 8.3+, MySQL 8 / MariaDB 10.6+, ekstensi pdo_mysql, mbstring, openssl, xml, ctype, fileinfo, bcmath.

## Struktur kode

- `app/Services`: logika WMS (StockService, InboundService, OutboundService, InventoryService, ButcherService, SyncService, SapService, DashboardService).
- `app/Http/Controllers`: satu controller per menu; `HhtController` untuk handheld.
- `config/bantuan.php`: isi panduan per layar; `config/implementasi.php`: menu persiapan implementasi.
- `resources/views`: Blade tanpa build step (CSS di `public/css/foodtrace.css`, Chart.js lokal).
