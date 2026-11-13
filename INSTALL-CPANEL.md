# Memasang FoodTrace di cPanel

Paket `foodtrace-laravel-v1.0.x.zip` sudah berisi folder `vendor/`, jadi tidak perlu Composer di server.

## 1. Siapkan di cPanel

1. **MultiPHP Manager**: pilih **PHP 8.3** (atau lebih baru) untuk domain / subdomain FoodTrace.
   Pastikan ekstensi `pdo_mysql`, `mbstring`, `openssl`, `xml`, `ctype`, `fileinfo`, `bcmath` aktif (Select PHP Version → Extensions).
2. **MySQL Databases**: buat database dan pengguna, lalu **Add User To Database** dengan **ALL PRIVILEGES**.
   Catat nama database, pengguna dan kata sandi (di cPanel biasanya berawalan nama akun, mis. `akun_foodtrace`).
3. **Subdomains / Domains**: buat subdomain (mis. `foodtrace.domainanda.id`) dengan **Document Root** `foodtrace/public`.

## 2. Upload

1. **File Manager** → buka folder home (`/home/akun`), **Upload** zip FoodTrace.
2. Klik kanan zip → **Extract** → hasilnya folder `/home/akun/foodtrace` (zip sudah berisi folder induk `foodtrace/`).
3. Pastikan folder `foodtrace/storage`, `foodtrace/bootstrap/cache` dan `foodtrace/public/uploads` dapat ditulis (permission 755 / 775).

> Bila document root tidak bisa diarahkan ke `public/`, pindahkan isi folder `foodtrace/` ke document root; file `.htaccess` di akar paket meneruskan permintaan ke `public/` dan memblokir `.env`.

## 3a. Pasang lewat browser (wizard)

Buka `https://foodtrace.domainanda.id/install`, isi alamat aplikasi, database, email & kata sandi admin, centang data demo bila perlu, klik **Pasang FoodTrace**.
Wizard membuat file `.env`, semua tabel, data master, akun admin dan (opsional) data demo.

## 3b. Pasang lewat Terminal cPanel

```bash
cd ~/foodtrace
cp .env.example .env
nano .env        # ubah APP_URL, DB_HOST=localhost, DB_DATABASE, DB_USERNAME, DB_PASSWORD
php artisan key:generate --force
php artisan foodtrace:install --demo --email=EMAIL_ADMIN --password='KATA_SANDI_ADMIN'
```

Hapus `--demo` untuk pemasangan produksi tanpa data contoh. Bila perintah `php` bukan PHP 8.3, pakai jalur lengkap, mis. `/opt/cpanel/ea-php83/root/usr/bin/php artisan ...`.
Setelah selesai, kunci keamanan: pastikan `.env` berisi `APP_ENV=production` dan `APP_DEBUG=false`.

## 4. Cron (posting SAP & auto release karantina)

**Cron Jobs** → tambahkan setiap menit:

```
* * * * * cd /home/akun/foodtrace && php artisan schedule:run >> /dev/null 2>&1
```

## 5. Masuk

Buka alamat aplikasi, masuk dengan **admin** atau email admin. Lalu:

1. Ganti kata sandi (⚙ kanan atas) bila perlu, tambah pengguna di **Pengguna**.
2. Isi **Zona & gudang** (kode gudang SAP), **Item Master**, **Bin / Rak Master** (atau pakai data demo).
3. Baca **Bantuan → Persiapan Implementasi** untuk langkah proyek berikutnya.

## Pembaruan versi

Upload zip versi baru, ekstrak menimpa folder (file `.env`, `storage/` dan `public/uploads/` tetap), lalu jalankan `php artisan migrate --force`.
