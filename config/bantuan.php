<?php

/*
 * Panduan FoodTrace: satu topik per layar. Setiap topik menjelaskan apa yang dibahas, kapan dilaksanakan,
 * langkah input data, arti setiap isian dan aturan sistem. Gambar = tangkapan layar di public/img/bantuan.
 * Tombol "？ Bantuan" di pojok kanan atas membuka topik sesuai layar (lihat bagian 'rute').
 */

$laporan = fn (string $judul, string $apa, string $kapan, array $kolom, array $tips = []) => [
    'judul' => 'Laporan: '.$judul, 'apa' => $apa, 'kapan' => $kapan, 'peran' => ['manager', 'inbound', 'outbound'],
    'langkah' => [
        'Buka menu <b>Laporan → '.$judul.'</b>.',
        'Isi filter (pencarian, zona, periode bila ada) lalu klik <b>Tampilkan</b>.',
        'Klik <b>⇩ Ekspor CSV (Excel)</b> untuk mengunduh seluruh baris (dibuka langsung di Excel, pemisah titik koma).',
        'Klik <b>⎙ Cetak</b> untuk mencetak tampilan saat ini.',
    ],
    'isian' => $kolom, 'tips' => $tips,
];

return [
    'topik' => [
        // ===================== UMUM =====================
        'umum' => [
            'judul' => 'Mengenal FoodTrace',
            'apa' => 'FoodTrace adalah Warehouse Management System untuk gudang bahan makanan frozen, chilled dan dry. Setiap palet diberi LPN (License Plate Number) dengan satu item dan satu lot, sehingga lokasi, expiry dan riwayatnya selalu bisa ditelusuri dari penerimaan sampai diterima customer.',
            'kapan' => 'Dibaca saat pertama kali memakai FoodTrace atau ketika melatih pengguna baru.',
            'peran' => ['admin', 'manager', 'inbound', 'outbound', 'operator'],
            'langkah' => [
                'Masuk dari halaman depan dengan <b>username atau email</b> dan kata sandi.',
                'Menu kiri dikelompokkan sesuai alur barang: <b>Inbound → Inventory → Outbound → Laporan → Integrasi SAP → Master Data</b>.',
                'Angka merah di samping menu adalah pekerjaan yang menunggu (mis. penerimaan terbuka, LPN perlu cetak ulang).',
                'Operator gudang memakai <b>Handheld (HHT)</b>: tombol besar, scan barcode lalu Enter.',
                'Tekan tombol <b>？ Bantuan</b> di pojok kanan atas (atau tombol <kbd>?</kbd> di keyboard) untuk panduan layar yang sedang dibuka.',
            ],
            'tips' => [
                'Alur utama: SAP PO → Receiving & label LPN → Put away per zona → (QC) → Picking FEFO → Checker → Loading → Goods Issue → POD.',
                'Semua stok tercatat per item + lot + LPN + lokasi + owner; setiap perpindahan masuk kartu stok.',
                'Satuan barang sama dengan satuan persediaan SAP, tanpa konversi.',
            ],
        ],
        'dashboard' => [
            'judul' => 'Dashboard gudang',
            'apa' => 'Ringkasan kondisi gudang untuk pengambilan keputusan: nilai stok, barang kedaluwarsa dan mendekati expiry, okupansi rak, pekerjaan terbuka, status SAP, serta grafik per zona, kelompok barang, brand, customer dan arus barang 30 hari.',
            'kapan' => 'Setiap pagi sebelum briefing shift, dan saat rapat mingguan kepala gudang / QA / manajemen.',
            'peran' => ['admin', 'manager'],
            'langkah' => [
                'Pilih tab zona (Semua, Frozen, Chilled, Dry) untuk menyaring seluruh angka dan grafik.',
                'Baca kotak <b>Perlu keputusan hari ini</b> dan klik <b>Tindak lanjut →</b> pada setiap butir.',
                'Klik angka KPI (mis. Kedaluwarsa, SAP gagal) untuk membuka daftar rinciannya.',
                'Gunakan tabel <b>Mendekati expiry</b> untuk menentukan promo, prioritas kirim atau retur ke pemasok.',
            ],
            'isian' => [
                'Nilai stok' => 'Qty × harga pokok seluruh stok (kecuali ruang potong).',
                'Kedaluwarsa' => 'Nilai stok yang expiry-nya sudah lewat; otomatis diblokir dari picking.',
                'Expired ≤ 30 hari' => 'Nilai stok yang akan kedaluwarsa dalam 30 hari (≤ 90 hari di bawahnya).',
                'Okupansi rak' => 'Persentase bin palet utuh yang terisi.',
                'Pekerjaan terbuka' => 'PO belum diterima / SO jatuh tempo, dan jumlah SO yang stoknya kurang (short).',
                'SAP B1' => 'Posting gagal, antre dan terkirim.',
            ],
            'tips' => ['Grafik "Umur simpan per zona" membantu melihat zona mana yang paling berisiko waste.', 'Data diambil langsung dari transaksi, tidak perlu proses tutup harian.'],
        ],

        // ===================== INBOUND =====================
        'inbound' => [
            'judul' => 'Inbound Document',
            'apa' => 'Daftar dokumen penerimaan: Purchase Order dari SAP B1 (POR), titipan, retur, warehouse transfer, hasil potong dan sampling. Setiap dokumen berisi baris item dan qty yang diharapkan.',
            'kapan' => 'Sebelum kontainer / truk pemasok dibongkar, dan sepanjang hari untuk memantau penerimaan yang belum selesai.',
            'peran' => ['inbound', 'manager'],
            'langkah' => [
                'Dokumen PO masuk otomatis dari SAP (menu Integrasi SAP → Dokumen masuk). Tidak perlu diketik ulang.',
                'Cari dokumen berdasarkan No. PO SAP, nomor RCV, nomor kontainer atau nama pemasok.',
                'Filter <b>Jenis</b> untuk memisahkan PO, titipan, retur dan transfer.',
                'Klik nomor dokumen untuk membuka layar <b>Receiving</b>.',
            ],
            'isian' => [
                'Nomor' => 'Nomor dokumen gudang (RCV/yymm/0001).',
                'Ref. SAP' => 'Nomor PO di SAP B1.',
                'Owner' => 'Pemilik barang. "Milik sendiri" berarti stok perusahaan; selain itu stok titipan.',
                'Dock' => 'Loading dock tempat bongkar (wajib sebelum validasi).',
            ],
            'tips' => ['Tab Menunggu berisi dokumen yang belum divalidasi; urut berdasarkan jadwal kedatangan.'],
        ],
        'receiving' => [
            'judul' => 'Receiving & label LPN',
            'apa' => 'Layar penerimaan satu dokumen: isi dock dan kontainer, scan karton (item berat aktual) atau isi qty per item, simpan palet (LPN), cetak label format Largo dengan QR, lalu validasi sehingga stok masuk dan Goods Receipt PO dikirim ke SAP.',
            'kapan' => 'Saat barang dibongkar di dock, palet demi palet. Validasi dilakukan setelah semua palet diperiksa dan dilabeli.',
            'peran' => ['inbound', 'operator'],
            'langkah' => [
                'Di <b>Header penerimaan</b> pilih <b>Loading dock</b>, isi No. kontainer, ref. DO pemasok, no. polisi dan pengemudi, lalu <b>Simpan header</b>.',
                'Pilih <b>Item (baris PO)</b>. Untuk item <span class="badge b-brown">berat aktual</span> scan barcode setiap karton; berat, lot, expiry dan nomor karton terbaca dari barcode GS1-128 / template.',
                'Bila barcode rusak, isi <b>Berat manual</b>, <b>Lot</b> dan <b>Expiry</b>. Untuk item biasa isi <b>Qty</b>, lot dan expiry.',
                'Klik <b>⇩ Terima</b>. Sistem mengisi LPN terbuka untuk lot tersebut; lot atau expiry berbeda otomatis membuka LPN baru.',
                'Setelah satu palet lengkap, di tabel <b>Palet (LPN)</b> isi No. palet, packaging, grade dan EST lalu klik <b>Simpan palet</b>. Scan berikutnya membuka LPN baru.',
                'Klik <b>⎙ Label</b> (per palet) atau <b>⎙ Label LPN</b> (semua) dan tempel label di palet.',
                'Klik <b>✓ Validasi penerimaan</b>. Stok masuk ke dock dengan status <b>Receiving</b> dan menunggu put away.',
            ],
            'isian' => [
                'Loading dock' => 'Wajib. Validasi ditolak bila kosong.',
                'Barcode karton' => 'Scan barcode pemasok. Dibaca dengan template di Master Data → Format barcode karton.',
                'Qty' => 'Jumlah dalam satuan SAP item (PCE, KG, PKT, dst.).',
                'Lot / batch' => 'Nomor batch pemasok. Kosong = dari barcode atau otomatis nomor dokumen + item.',
                'Expiry' => 'Tanggal kedaluwarsa dari kemasan. Dipakai untuk FEFO dan pengecekan umur simpan.',
                'No. palet, packaging, grade, EST' => 'Info yang dicetak di label LPN (EST = nomor establishment / RPH).',
            ],
            'tips' => [
                '<b>Over receiving</b>: qty melebihi PO + toleransi pemasok ditolak saat validasi. Kelebihan dibuat PO terpisah.',
                '<b>Umur simpan</b>: lot dengan sisa umur di bawah batas minimum item (Item Master) ditolak; lot kedaluwarsa selalu ditolak.',
                '<b>Auto karantina</b>: item bertanda auto karantina langsung masuk lokasi QC (status Hold).',
                'Rincian yang salah bisa dihapus dengan tombol ✕ sebelum validasi; karton ikut terhapus.',
            ],
        ],
        'label-lpn' => [
            'judul' => 'Label LPN (format Largo)',
            'apa' => 'Label palet 150 × 100 mm: kiri berisi ItemCode, nama, QR LPN, tanggal Exp / Rcv / Pack, qty tally / jumlah karton, lot, EST, no. palet, no. kontainer, packaging, grade dan zona; kanan 24 stiker QR kecil untuk ditempel di karton.',
            'kapan' => 'Setelah palet disimpan saat receiving, setelah Butcher In, dan saat LPN ditandai cetak ulang setelah Inventory Check.',
            'peran' => ['inbound', 'operator'],
            'langkah' => [
                'Buka dari Receiving (⎙ Label LPN), LPN List (pilih beberapa lalu ⎙ Cetak label terpilih) atau detail LPN.',
                'Klik <b>⎙ Cetak</b>. Atur printer label ke ukuran kertas 150 × 100 mm, margin 0.',
                'Tempel label utama di sisi palet yang terlihat dari lorong; stiker kecil di karton bila palet dipecah.',
            ],
            'tips' => ['Mencetak label menghapus tanda "perlu cetak ulang" pada LPN.', 'QR berisi nomor LPN; semua layar HHT menerima scan QR ini.'],
        ],
        'cartons' => [
            'judul' => 'Tally karton',
            'apa' => 'Daftar setiap karton item berat aktual (catch weight) yang discan saat receiving: nomor karton, barcode mentah, berat kg / lb, lot, LPN, lokasi dan status (tersedia, karantina, terkirim, waste).',
            'kapan' => 'Untuk rekonsiliasi berat dengan dokumen pemasok, klaim selisih berat, dan menelusuri karton tertentu saat keluhan customer.',
            'peran' => ['inbound', 'manager'],
            'langkah' => [
                'Cari dengan nomor karton, barcode, LPN, item atau nomor PO.',
                'Filter status untuk melihat karton karantina atau yang sudah terkirim.',
                'Klik LPN untuk melihat palet asalnya, atau nomor dokumen untuk penerimaan / pengirimannya.',
                'Ekspor CSV untuk dikirim ke pemasok sebagai lampiran tally.',
            ],
            'tips' => ['Karton berlabel <span class="badge b-amber">manual</span> artinya berat diinput tangan karena barcode tidak terbaca.', 'Karton yang sama tidak bisa discan dua kali.'],
        ],
        'putaway' => [
            'judul' => 'Put Away',
            'apa' => 'Memindahkan LPN yang sudah diterima dari dock (status Receiving) ke rak simpan sesuai zona. Sistem menyarankan bin kosong di zona item, dan menolak rak zona lain.',
            'kapan' => 'Segera setelah validasi penerimaan, terutama untuk barang frozen dan chilled agar rantai dingin tidak terputus.',
            'peran' => ['operator', 'inbound'],
            'langkah' => [
                'Lihat daftar LPN di dock. Kolom <b>Pindahkan ke</b> sudah terisi saran bin kosong zona yang sama.',
                'Bawa palet ke rak, scan / ketik barcode bin yang benar-benar dipakai, lalu klik <b>⇢ Put away</b>.',
                'Di lapangan lebih praktis memakai HHT: menu <b>Put away</b>, scan LPN lalu scan bin.',
            ],
            'isian' => ['Bin tujuan' => 'Barcode lokasi rak, mis. SKI-FZ-A01-1. Harus bin aktif dan zona sesuai item.'],
            'tips' => ['Item frozen hanya bisa ke rak FZ, chilled ke CH, dry ke DR.', 'Bila "rak zona penuh", tambah bin di Master Data atau lakukan bin transfer untuk merapikan.'],
        ],
        'qc' => [
            'judul' => 'QC Hold / Release',
            'apa' => 'Daftar stok yang ditahan: karantina (auto karantina saat receiving atau ditahan QA), retur dan damage. Stok Hold tidak bisa dipicking sampai diputuskan.',
            'kapan' => 'Saat QA menemukan masalah (kemasan rusak, suhu, bau, keluhan customer), dan setiap hari untuk memutuskan barang karantina.',
            'peran' => ['manager', 'inbound'],
            'langkah' => [
                'Untuk menahan: scan <b>LPN</b>, isi <b>Alasan</b>, lalu klik <b>⚑ Tahan</b>. LPN pindah ke lokasi karantina zona item.',
                'Isi <b>Lokasi tujuan</b> bila ingin langsung ke RTN (retur) atau DMG (rusak).',
                'Kepala gudang / QA klik <b>Putuskan</b> pada baris stok untuk merilis atau menolak.',
            ],
            'tips' => ['Item dengan auto release otomatis dirilis setelah hari karantina (perintah terjadwal).', 'Kolom "Sejak" menunjukkan lamanya barang ditahan.'],
        ],
        'qc-release' => [
            'judul' => 'Keputusan QC (rilis / tolak)',
            'apa' => 'Memutuskan stok karantina: dirilis ke rak (status Available) atau ditolak ke damage / waste / retur.',
            'kapan' => 'Setelah hasil pemeriksaan QA atau uji lab keluar.',
            'peran' => ['manager'],
            'langkah' => [
                'Rilis: isi <b>Bin tujuan</b> (sudah disarankan bin kosong zona), qty bila hanya sebagian, catatan hasil QC, klik <b>✓ Rilis</b>.',
                'Tolak: pilih lokasi DMG / WASTE / RTN, qty, alasan penolakan, klik <b>✕ Tolak</b>.',
            ],
            'tips' => ['Setiap keputusan tercatat sebagai dokumen internal (INT) dengan alasan, bisa dilihat di Bin Transfer.'],
        ],

        // ===================== INVENTORY =====================
        'stock' => [
            'judul' => 'Stock Status',
            'apa' => 'Seluruh stok per lokasi, item, lot, LPN dan owner, dengan status Receiving, Available, Picked, Hold atau Damage, qty reservasi dan nilai.',
            'kapan' => 'Kapan saja untuk mencari barang, mengecek ketersediaan sebelum menerima order, atau memeriksa barang mendekati expiry.',
            'peran' => ['manager', 'inbound', 'outbound'],
            'langkah' => [
                'Klik kotak status di atas untuk menyaring (klik lagi untuk melepas).',
                'Cari dengan item, lot, LPN atau lokasi. Filter zona dan kepemilikan (milik sendiri / titipan).',
                'Baris kuning = expiry ≤ 30 hari, merah = sudah kedaluwarsa.',
                'Klik lokasi untuk laporan Stok per Lokasi, atau LPN untuk riwayat palet.',
            ],
            'isian' => ['Reservasi' => 'Qty yang sudah dipesan untuk outbound (FEFO) dan belum diambil.', 'Owner' => 'Pemilik stok titipan; kosong = milik sendiri.'],
            'tips' => ['Status ditentukan area lokasi: dock = Receiving, rak = Available, staging keluar = Picked, QC/retur = Hold, damage/waste = Damage.'],
        ],
        'lpn' => [
            'judul' => 'LPN List',
            'apa' => 'Daftar semua palet (LPN) dengan item, lot, expiry, lokasi, status, qty dan jumlah karton. LPN yang berubah setelah Inventory Check ditandai <span class="badge b-red">cetak ulang</span>.',
            'kapan' => 'Saat mencari palet, mencetak ulang label rusak, atau setelah approval Inventory Check.',
            'peran' => ['inbound', 'operator', 'manager'],
            'langkah' => [
                'Cari LPN, item atau lot. Centang <b>Perlu cetak ulang</b> untuk melihat label yang harus diganti.',
                'Klik <b>⎙ Cetak ulang n label</b> untuk mencetak semua yang ditandai sekaligus.',
                'Atau centang beberapa baris lalu <b>⎙ Cetak label terpilih</b>.',
            ],
            'tips' => ['Aturan: satu LPN hanya berisi satu item dan satu lot.'],
        ],
        'lpn-detail' => [
            'judul' => 'Detail LPN',
            'apa' => 'Info satu palet: QR, item, lot, expiry, no. palet, grade, stok saat ini per lokasi, riwayat pergerakan lengkap dan daftar karton.',
            'kapan' => 'Saat menelusuri satu palet (traceability), misalnya untuk keluhan customer atau recall.',
            'peran' => ['manager', 'inbound', 'outbound'],
            'langkah' => ['Buka dari LPN List, Stock Status atau hasil scan.', 'Gunakan tombol Cetak label, Inventory check atau Kartu stok di kanan atas.'],
        ],
        'bin-transfer' => [
            'judul' => 'Bin Transfer & log pemindahan',
            'apa' => 'Log semua pemindahan internal: put away, bin transfer, QC hold dan release. Setiap pemindahan punya nomor INT, alasan, lokasi asal dan tujuan.',
            'kapan' => 'Saat merapikan rak, memindah palet ke area picking, atau menelusuri siapa memindah barang.',
            'peran' => ['operator', 'inbound', 'manager'],
            'langkah' => [
                'Untuk memindah palet utuh gunakan HHT menu <b>Bin transfer</b>: scan LPN lalu scan bin tujuan.',
                'Di web, filter log berdasarkan alasan atau cari INT / item / LPN.',
                'Klik nomor INT untuk rincian pemindahan.',
            ],
            'tips' => ['Pemindahan dalam satu gudang SAP tidak diposting ke SAP.', 'LPN yang sedang direservasi untuk picking tidak bisa dipindah.'],
        ],
        'ic' => [
            'judul' => 'Inventory Check',
            'apa' => 'Cek fisik per LPN atau per lokasi (format Largo): qty fisik dan expiry dibandingkan dengan sistem. Selisih baru berlaku setelah disetujui Kepala gudang / QA.',
            'kapan' => 'Saat ada keraguan stok, label rusak, sebelum audit, atau sesuai jadwal cek per zona.',
            'peran' => ['operator', 'inbound', 'manager'],
            'langkah' => [
                'Klik <b>+ Inventory check baru</b>, pilih <b>LPN</b> atau <b>Lokasi</b>, scan kodenya, klik <b>Buat & muat stok</b>.',
                'Isi qty fisik dan expiry yang terbaca di kemasan pada setiap baris; centang cetak ulang bila label perlu diganti.',
                'Klik <b>Kirim untuk approval</b>.',
                'Kepala gudang / QA membuka dokumen berstatus <b>Menunggu approval</b> lalu <b>Approve</b> atau <b>Reject</b>.',
            ],
            'tips' => ['Approve menerapkan selisih qty sebagai penyesuaian di kartu stok, memperbarui expiry lot, dan menandai LPN cetak ulang.'],
        ],
        'ic-detail' => [
            'judul' => 'Isi & approval Inventory Check',
            'apa' => 'Baris hasil cek: qty sistem vs fisik, selisih, expiry sistem vs fisik, catatan dan tanda cetak ulang.',
            'kapan' => 'Saat checker mengisi hasil cek dan saat kepala gudang memeriksa selisih.',
            'peran' => ['operator', 'manager'],
            'langkah' => [
                'Draft: ubah qty fisik / expiry / catatan, lalu kirim.',
                'Bila stok berubah sejak dokumen dibuat, klik <b>↻ Muat ulang stok</b>.',
                'Approval: periksa baris berwarna (ada selisih), lalu <b>✓ Approve</b> atau isi alasan dan <b>✕ Reject</b>.',
            ],
            'isian' => ['Qty fisik' => 'Hasil hitung nyata dalam satuan item.', 'Expiry fisik' => 'Tanggal di kemasan bila berbeda dari sistem.', 'Cetak ulang' => 'Tandai bila label LPN harus dicetak ulang.'],
        ],
        'cycle' => [
            'judul' => 'Cycle Count (hitung buta)',
            'apa' => 'Hitung stok per lokasi tanpa melihat qty sistem. Hasil hitungan menunggu persetujuan kepala gudang, lalu selisih dicatat sebagai penyesuaian.',
            'kapan' => 'Rutin (mis. harian per lorong, mingguan per zona) agar tidak perlu stock opname total.',
            'peran' => ['operator', 'manager'],
            'langkah' => [
                'Scan / ketik lokasi lalu klik <b>Muat</b> (bisa rak, area atau zona; turunannya ikut).',
                'Isi <b>Hasil hitung</b> setiap baris lalu <b>Simpan hasil hitung</b>.',
                'Kepala gudang melihat kolom <b>Persetujuan selisih</b>, mencentang baris lalu <b>✓ Setujui & sesuaikan stok</b> atau <b>Tolak (hitung ulang)</b>.',
            ],
            'tips' => ['Di HHT gunakan menu Cycle count.', 'Referensi penyesuaian: CC/yymmdd-jjmm di kartu stok.'],
        ],
        'stock-card' => [
            'judul' => 'Stock Card (kartu stok)',
            'apa' => 'Semua mutasi satu item (opsional per lot) dalam periode: masuk, keluar, pindah, penyesuaian dan butcher, dengan saldo awal dan saldo berjalan.',
            'kapan' => 'Saat rekonsiliasi dengan SAP, investigasi selisih, atau audit.',
            'peran' => ['manager', 'inbound', 'outbound'],
            'langkah' => ['Ketik ItemCode atau barcode, isi lot bila perlu, pilih periode, klik <b>Tampilkan</b>.', 'Klik referensi untuk membuka dokumen asal.'],
            'tips' => ['Mutasi "Pindah" tidak mengubah saldo karena barang tetap di gudang.'],
        ],

        // ===================== OUTBOUND =====================
        'outbound' => [
            'judul' => 'Outbound Document',
            'apa' => 'Sales Order dari SAP B1 (SOR) dan pengeluaran lain (warehouse transfer, retur ke vendor, pemusnahan, butcher). Customer bisa punya banyak ShipTo (outlet tujuan).',
            'kapan' => 'Setiap hari sebelum picking: kelompokkan SO per rute dan tanggal kirim lalu buat Picking Job.',
            'peran' => ['outbound', 'manager'],
            'langkah' => [
                'Filter <b>Rute (shipping group)</b> dan <b>Kirim s.d.</b> tanggal pengiriman. Centang <b>Belum ada picking job</b>.',
                'Centang dokumen yang akan diambil bersama (satu kendaraan / rute).',
                'Isi pengemudi / ekspedisi bila sudah diketahui, centang pakai tote untuk barang kecil.',
                'Klik <b>☰ Buat Picking Job dari terpilih</b>. Stok direservasi FEFO.',
            ],
            'isian' => ['Reservasi' => 'Lengkap = semua baris tercukupi; Short = stok kurang; Belum = belum direservasi.', 'Tahap' => 'Open → Picking → Staging → Dicek → Loading → Terkirim → POD.'],
            'tips' => ['FEFO hanya memakai stok Available yang belum kedaluwarsa dan memenuhi minimum sisa umur simpan customer.'],
        ],
        'outbound-detail' => [
            'judul' => 'Detail outbound & reservasi FEFO',
            'apa' => 'Header SO (customer, ShipTo, rute, prioritas), baris permintaan dan rincian reservasi FEFO: lot, expiry, lokasi dan LPN yang harus diambil, beserta status diambil / dicek / dimuat.',
            'kapan' => 'Saat memeriksa kekurangan stok, mengganti reservasi, atau Goods Issue langsung tanpa loading.',
            'peran' => ['outbound', 'manager'],
            'langkah' => [
                'Klik <b>↻ Reservasi FEFO</b> untuk mencoba mencukupi baris yang masih short (mis. setelah barang masuk).',
                '<b>Batalkan reservasi</b> bila order ditunda (hanya sebelum ada barang diambil).',
                'Kepala gudang dapat <b>Goods Issue langsung</b> untuk pengeluaran tanpa kendaraan (pemusnahan, butcher, ambil sendiri).',
                'Klik <b>⎙ Surat jalan</b> untuk mencetak surat jalan dengan QR POD.',
            ],
        ],
        'picking' => [
            'judul' => 'Picking Job',
            'apa' => 'Daftar job picking: satu job berisi beberapa outbound document. Persentase "siap" menunjukkan qty yang sudah diambil dibanding permintaan.',
            'kapan' => 'Sepanjang shift picking; supervisor memantau job yang belum selesai.',
            'peran' => ['outbound', 'operator'],
            'langkah' => ['Klik nomor job untuk daftar ambil.', 'Picker sebaiknya memakai HHT menu <b>Picking</b>.'],
        ],
        'picking-detail' => [
            'judul' => 'Daftar ambil (assisted picking)',
            'apa' => 'Daftar ambil urut lokasi berdasarkan FEFO. Picker harus scan lokasi yang disarankan dan LPN / lot / item yang benar; salah lokasi atau salah barang ditolak.',
            'kapan' => 'Saat picking, lalu finish trip ke outbound staging.',
            'peran' => ['operator', 'outbound'],
            'langkah' => [
                'Pada setiap baris scan <b>lokasi</b>, scan <b>LPN / lot / item</b>, isi qty bila hanya sebagian (loose picking), klik <b>Ambil</b>.',
                'Setelah semua baris hijau, scan lokasi staging (mis. SKI-OUT) lalu <b>Letakkan di staging</b>.',
            ],
            'tips' => ['Ambil sebagian memecah LPN: sisa tetap di rak dan dokumen kembali short untuk direservasi ulang.'],
        ],
        'checker' => [
            'judul' => 'Checker',
            'apa' => 'Pemeriksaan ulang barang di outbound staging per dokumen: setiap LPN / lot / item discan dan dicocokkan dengan hasil picking. Barang yang tidak ada di dokumen ditolak.',
            'kapan' => 'Setelah picker meletakkan barang di staging dan sebelum dimuat ke kendaraan.',
            'peran' => ['outbound', 'operator'],
            'langkah' => ['Klik dokumen di daftar, scan setiap LPN / lot / item.', 'Dokumen lengkap berubah menjadi <b>Sudah dicek</b> dan siap dibuat loading job.'],
        ],
        'loading' => [
            'judul' => 'Loading Job',
            'apa' => 'Mengelompokkan dokumen yang sudah dicek ke satu kendaraan (no. polisi, pengemudi, tanggal).',
            'kapan' => 'Saat kendaraan siap di dock keluar.',
            'peran' => ['outbound'],
            'langkah' => [
                'Centang dokumen di tabel <b>Siap dimuat</b>.',
                'Isi <b>No. polisi</b>, <b>Pengemudi</b> dan tanggal, klik <b>⛟ Buat loading job</b>.',
            ],
        ],
        'loading-detail' => [
            'judul' => 'Muat & tutup loading',
            'apa' => 'Scan setiap LPN yang naik kendaraan. Tutup loading = Goods Issue semua dokumen, Delivery ke antrean SAP, POD dibuat dan surat jalan siap dicetak.',
            'kapan' => 'Saat barang dinaikkan ke kendaraan sampai kendaraan berangkat.',
            'peran' => ['outbound', 'operator'],
            'langkah' => [
                'Scan LPN di kolom <b>Scan LPN yang dimuat</b> (atau HHT menu Loading).',
                'Setelah progres 100%, klik <b>✓ Tutup loading (GI)</b>.',
                'Cetak <b>⎙ Manifest</b> untuk pengemudi dan <b>⎙ Surat jalan</b> (satu per outlet, dengan QR POD).',
            ],
            'tips' => ['Kepala gudang dapat <b>Tutup paksa</b> bila ada baris yang tidak bisa discan (label rusak), setelah dicek manual.', 'Batalkan loading mengembalikan dokumen ke tahap Sudah dicek.'],
        ],
        'pod' => [
            'judul' => 'Proof of Delivery (POD)',
            'apa' => 'Bukti penerimaan customer: nama penerima, waktu, qty diterima per item, catatan dan foto. Diisi driver / penerima lewat QR di surat jalan tanpa login.',
            'kapan' => 'Saat barang diserahkan di outlet customer; admin outbound memantau POD yang belum masuk dan yang ada selisih.',
            'peran' => ['outbound', 'manager'],
            'langkah' => [
                'Driver scan QR di surat jalan dengan HP, periksa qty, ubah bila ada yang kurang / rusak, isi nama penerima, ambil foto, kirim.',
                'Admin membuka tab <b>Ada selisih</b> untuk ditindaklanjuti (klaim, kirim ulang, retur).',
            ],
            'tips' => ['POD yang sudah diisi tidak bisa diubah lagi dari halaman driver.'],
        ],
        'pod-driver' => [
            'judul' => 'Halaman POD untuk driver',
            'apa' => 'Halaman ponsel yang terbuka dari QR surat jalan. Tidak perlu akun.',
            'kapan' => 'Di lokasi customer saat serah terima.',
            'peran' => ['outbound'],
            'langkah' => ['Scan QR surat jalan.', 'Ubah qty diterima bila berbeda dan isi catatan.', 'Isi nama penerima, ambil foto surat jalan bertanda tangan, klik Kirim.'],
        ],
        'butcher-out' => [
            'judul' => 'Butcher Out (bahan ke ruang potong)',
            'apa' => 'Mengeluarkan bahan (LPN) dari gudang ke ruang potong untuk dipotong, di-rebag, trimming atau repack. Stok keluar dan tercatat di kartu stok sebagai "Butcher".',
            'kapan' => 'Saat bahan diambil untuk produksi / potong sesuai work order atau SO.',
            'peran' => ['operator', 'outbound'],
            'langkah' => [
                'Klik <b>+ Butcher Out baru</b> (atau HHT menu Butcher out).',
                'Isi referensi, lalu tiap baris: proses, scan <b>LPN bahan</b>, qty.',
                'Klik <b>✓ Simpan & selesaikan</b> (atau simpan draft dan selesaikan nanti).',
            ],
            'tips' => ['Qty tidak boleh melebihi stok tersedia LPN.', 'Setelah selesai, catat hasilnya dengan tombol <b>+ Catat hasil (Butcher In)</b>.'],
        ],
        'butcher-in' => [
            'judul' => 'Butcher In (hasil potong / repack)',
            'apa' => 'Mencatat hasil potong sebagai lot dan LPN baru di bin tujuan. Yield = berat hasil ÷ berat bahan Butcher Out.',
            'kapan' => 'Setelah proses potong / repack selesai dan hasil dikemas.',
            'peran' => ['operator', 'inbound'],
            'langkah' => [
                'Klik <b>+ Butcher In baru</b> dan pilih <b>Bahan dari Butcher Out</b>.',
                'Tiap baris: item hasil, bin tujuan (sesuai zona), lot (kosong = otomatis), expiry, grade, cutting, qty.',
                'Klik <b>✓ Simpan & selesaikan</b>, lalu <b>⎙ Label LPN hasil</b>.',
            ],
        ],

        // ===================== LAPORAN =====================
        'laporan-by-location' => $laporan('Stok per Lokasi', 'Isi satu bin, rak, area atau zona: item, lot, expiry, LPN, status, qty dan nilai, dikelompokkan per lokasi.', 'Saat mengecek isi rak, sebelum cycle count, atau menjawab pertanyaan "di rak ini ada apa?".',
            ['Lokasi' => 'Cari dengan barcode (SKI-FZ-A01) atau nama lengkap (FZ/BLK).'], ['Klik nama lokasi di laporan Lokasi per Item untuk lompat ke sini.']),
        'laporan-by-item' => $laporan('Lokasi per Item', 'Semua lokasi tempat satu item berada, urut expiry terdekat (FEFO), dengan lot, LPN, status dan qty.', 'Saat mencari barang untuk order mendesak atau memeriksa sebaran satu SKU.',
            ['Item' => 'Cari dengan ItemCode atau nama.']),
        'laporan-expired' => $laporan('Expired Report', 'Stok yang sudah atau akan kedaluwarsa (≤ 120 hari) dengan sisa hari, lokasi, LPN, qty dan nilai.', 'Mingguan oleh QA dan purchasing untuk promo, prioritas kirim, retur ke pemasok atau pemusnahan.',
            ['Kelompok' => 'Sudah expired, ≤ 30, 31–60, 61–90, 91–120 hari.'], ['Stok expired otomatis diblokir dari picking.']),
        'laporan-item-low' => $laporan('Item Low', 'Item yang stoknya di bawah minimum stok (diisi di Item Master), beserta kekurangannya.', 'Harian oleh purchasing / planner sebelum membuat PO di SAP.', ['Kurang' => 'Minimum stok dikurangi stok saat ini.']),
        'laporan-material-in' => $laporan('Material In', 'Semua penerimaan barang (Goods Receipt) per periode: tanggal, referensi, item, lot, lokasi tujuan, LPN, qty, nilai, pemasok dan operator.', 'Harian untuk rekonsiliasi GRPO dengan SAP dan keuangan.', ['Periode' => 'Default 30 hari terakhir.']),
        'laporan-material-out' => $laporan('Material Out', 'Semua pengeluaran barang (Goods Issue) per periode: customer, lot, lokasi asal, LPN, qty dan nilai.', 'Harian untuk rekonsiliasi Delivery dengan SAP dan penagihan.', ['Periode' => 'Default 30 hari terakhir.']),
        'laporan-transfer-log' => $laporan('Transfer Log', 'Semua pemindahan internal dan butcher: put away, bin transfer, QC hold / release, butcher out / in.', 'Saat investigasi barang hilang atau audit pergerakan.', ['Dari / Ke' => 'Lokasi asal dan tujuan atau label (Ruang potong).']),
        'laporan-adjustment' => $laporan('Cycle Count / Adjustment', 'Semua penyesuaian stok hasil Inventory Check dan Cycle Count yang sudah disetujui.', 'Bulanan oleh kepala gudang dan keuangan untuk mengevaluasi akurasi stok.', ['Dari' => 'Lokasi bila stok berkurang.', 'Ke' => 'Lokasi bila stok bertambah.']),
        'laporan-ic-discrepancy' => $laporan('Inventory Check Discrepancy', 'Baris Inventory Check yang berselisih qty atau expiry, dengan checker dan approver.', 'Mingguan untuk melihat pola selisih per lokasi / operator.', ['Selisih' => 'Qty fisik − qty sistem.']),
        'laporan-warehouse-transfer' => $laporan('Warehouse Transfer', 'Dokumen transfer antar gudang (inbound dan outbound berjenis Warehouse Transfer).', 'Saat memantau barang dalam perjalanan antar gudang.', []),

        // ===================== SAP =====================
        'sap-sync' => [
            'judul' => 'Dokumen masuk SAP (POR / SOR)',
            'apa' => 'File sinkron dari SAP B1: Purchase Order (POR) menjadi Inbound Document, Sales Order (SOR) menjadi Outbound Document. Proses idempoten: nomor yang sama tidak dibuat dua kali.',
            'kapan' => 'Otomatis lewat API setiap kali middleware SAP mengirim; manual bila file dikirim lewat email / flashdisk.',
            'peran' => ['admin', 'inbound', 'outbound'],
            'langkah' => [
                'Manual: pilih file JSON dari SAP atau tempel isinya, klik <b>⇩ Impor & proses</b>.',
                'Periksa tab <b>Gagal</b>. Penyebab umum: ItemCode belum ada di Item Master.',
                'Tambahkan item yang kurang, lalu klik <b>↻ Proses ulang</b>.',
                'Otomatis: berikan URL API dan token (terlihat oleh Admin) ke tim SAP.',
            ],
            'tips' => [
                'Owner sama dengan kode gudang SAP (95-SKI) berarti stok sendiri; owner lain = titipan.',
                'Bila nama outlet SO berbeda dengan nama customer, ShipTo dibuat otomatis.',
                'Satuan mengikuti satuan persediaan SAP item.',
            ],
        ],
        'sap-queue' => [
            'judul' => 'Antrean posting SAP',
            'apa' => 'Dokumen WMS yang dikirim ke SAP B1: Goods Receipt PO (penerimaan) dan Delivery (pengiriman). Mode simulasi memberi DocNum dari WMS sampai Service Layer SAP disambungkan.',
            'kapan' => 'Terjadwal otomatis tiap 5 menit. Admin memeriksa setiap hari posting yang gagal.',
            'peran' => ['admin', 'manager'],
            'langkah' => [
                'Klik <b>⇪ Kirim semua yang antre</b> untuk mengirim sekarang.',
                'Buka tab <b>Gagal</b>, baca kolom error, perbaiki penyebabnya, centang lalu <b>↻ Kirim ulang terpilih</b>.',
            ],
            'tips' => ['Bin transfer internal tidak diposting karena tetap di gudang SAP yang sama.'],
        ],

        // ===================== MASTER =====================
        'items' => [
            'judul' => 'Item Master',
            'apa' => 'Item dari SAP B1 lengkap dengan aturan gudang: zona simpan, satuan SAP, brand, umur simpan, minimum sisa umur saat terima, berat aktual, auto karantina, strategi FEFO / FIFO dan minimum stok.',
            'kapan' => 'Saat persiapan implementasi (impor dari SAP), saat ada item baru, dan saat aturan QA berubah.',
            'peran' => ['admin', 'manager'],
            'langkah' => ['Cari item, filter zona / group / brand / tanda.', 'Klik ItemCode untuk mengubah, atau <b>+ Item baru</b>.', 'Ekspor CSV untuk review aturan bersama QA.'],
        ],
        'item-form' => [
            'judul' => 'Isi data item',
            'apa' => 'Form satu item: data SAP dan aturan food safety.',
            'kapan' => 'Saat menambah / mengubah item.',
            'peran' => ['admin', 'manager'],
            'langkah' => ['Isi ItemCode SAP, nama, satuan persediaan SAP dan zona (wajib).', 'Atur umur simpan, minimum sisa umur saat terima, strategi picking dan minimum stok.', 'Centang berat aktual untuk barang yang dijual per kg dengan berat berbeda per karton, lalu pilih template barcode karton.', 'Klik <b>Simpan item</b>.'],
            'isian' => [
                'Satuan persediaan SAP' => 'Sama dengan InvntryUom SAP; tanpa konversi.',
                'Zona simpan' => 'Frozen / Chilled / Dry. Put away hanya ke rak zona ini.',
                'Umur simpan (hari)' => 'Total umur produk; 0 = memakai standar zona.',
                'Min. sisa umur saat terima' => 'Persen sisa umur minimal saat diterima, mis. 70%.',
                'Berat aktual' => 'Catch weight: scan per karton, berat dari barcode.',
                'Auto karantina' => 'Barang masuk langsung ke lokasi QC (Hold).',
                'Berat per satuan' => 'Untuk menghitung total berat muatan kendaraan.',
            ],
        ],
        'brands' => [
            'judul' => 'Brand SAP', 'apa' => 'Daftar brand dari field U_Brand SAP (mis. LAI - LAITA). Dipakai di item, label LPN dan laporan.', 'kapan' => 'Saat persiapan implementasi dan ketika ada brand baru di SAP.', 'peran' => ['admin'],
            'langkah' => ['Isi kode dan nama di form kanan lalu Simpan.', 'Klik Ubah pada baris untuk mengedit.'],
        ],
        'uoms' => [
            'judul' => 'Satuan SAP', 'apa' => 'Satuan persediaan SAP (KG, PCE, PKT, CTN, dst.). FoodTrace memakai satuan yang sama dengan SAP tanpa faktor konversi, sehingga qty WMS = qty SAP.', 'kapan' => 'Saat persiapan implementasi.', 'peran' => ['admin'],
            'langkah' => ['Tambah kode sesuai SAP.', 'Centang Satuan berat untuk KG / GRAM.'],
        ],
        'locations' => [
            'judul' => 'Bin / Rak Master',
            'apa' => 'Struktur lokasi gudang: gudang → ruang zona (FZ/CH/DR) → area (dock, bulk, picking, QC, retur, damage, staging keluar, ruang potong) → bin. Setiap bin punya barcode untuk discan.',
            'kapan' => 'Saat persiapan implementasi (layout gudang) dan ketika rak ditambah / diubah.',
            'peran' => ['admin', 'manager'],
            'langkah' => [
                'Pilih <b>Induk</b>, isi nama bin, barcode, area; zona kosong = ikut induk.',
                'Centang <b>Bin</b> bila lokasi bisa diisi stok. Klik <b>Simpan lokasi</b>.',
                'Klik <b>⎙ Label barcode lokasi</b> untuk mencetak label QR rak (gunakan pencarian untuk membatasi).',
            ],
            'isian' => ['Area' => 'Menentukan status stok: rcv = Receiving, blk/pck = Available, qc/rtn = Hold, dmg/wst = Damage, out = Picked, prd = ruang potong.', 'Maks. palet / berat' => 'Kapasitas untuk perencanaan.'],
        ],
        'source' => [
            'judul' => 'Source Master (pemasok)', 'apa' => 'Pemasok dan pemilik titipan, dengan toleransi over receiving per pemasok dan kode owner untuk stok titipan.', 'kapan' => 'Saat persiapan implementasi dan saat ada pemasok baru (biasanya otomatis dari PO SAP).', 'peran' => ['admin', 'inbound'],
            'langkah' => ['Cari pemasok, klik kode untuk mengubah.', 'Isi Toleransi over receive (%) sesuai kontrak pemasok.', 'Untuk titipan isi Kode owner sesuai nilai "owner" di file SAP.'],
        ],
        'destination' => [
            'judul' => 'Destination Master (customer & ShipTo)', 'apa' => 'Customer SAP dengan banyak ShipTo (outlet tujuan), rute pengiriman (shipping group) dan minimum sisa umur simpan saat dikirim.', 'kapan' => 'Saat persiapan implementasi dan saat ada customer / outlet baru.', 'peran' => ['admin', 'outbound'],
            'langkah' => ['Filter rute atau cari customer.', 'Klik kode customer untuk mengisi aturan dan ShipTo.'],
            'tips' => ['ShipTo juga dibuat otomatis dari SO SAP bila nama outlet berbeda dari nama customer.'],
        ],
        'partner-form' => [
            'judul' => 'Isi data partner & ShipTo', 'apa' => 'Data pemasok / customer, aturan gudang dan daftar ShipTo.', 'kapan' => 'Saat menambah atau mengubah partner.', 'peran' => ['admin', 'inbound', 'outbound'],
            'langkah' => ['Isi kode SAP (CardCode) dan nama, centang Source dan/atau Destination.', 'Isi toleransi over receive (pemasok) atau min. sisa umur kirim dan rute (customer).', 'Tambah ShipTo di tabel kanan: nama outlet, alamat, kota, lalu Simpan ShipTo.'],
            'isian' => ['Min. sisa umur kirim (%)' => 'Lot dengan sisa umur di bawah nilai ini tidak dipilih FEFO untuk customer ini.', 'Toleransi over receive (%)' => 'Kelebihan qty yang masih diterima dari PO.', 'Rute' => 'Shipping group untuk mengelompokkan picking job.'],
        ],
        'templates' => [
            'judul' => 'Format barcode karton', 'apa' => 'Template untuk membaca berat, lot, expiry dan nomor karton dari barcode pemasok: GS1-128 atau pola regex posisi tetap (termasuk label lbs yang dikonversi ke kg).', 'kapan' => 'Saat ada pemasok / label karton baru untuk item berat aktual.', 'peran' => ['admin', 'inbound'],
            'langkah' => ['Isi nama, jenis (GS1 / Regex), satuan berat, desimal, batas berat min / maks, pola dan contoh.', 'Simpan, lalu uji di kotak <b>Uji baca barcode</b> dengan scan karton asli.', 'Pasangkan template ke item di Item Master.'],
            'tips' => ['Batas berat min / maks mencegah salah baca (mis. 125 kg terbaca dari karton 12,5 kg).', 'Regex memakai grup bernama: weight, lot, expiry (YYMMDD), carton.'],
        ],
        'zones' => [
            'judul' => 'Zona & gudang', 'apa' => 'Standar zona (Frozen, Chilled, Dry): kode, umur simpan standar, warna; serta data gudang, kode gudang SAP, nama perusahaan dan mode posting SAP.', 'kapan' => 'Saat persiapan implementasi.', 'peran' => ['admin'],
            'langkah' => ['Ubah nama, kode, umur simpan standar dan warna zona, Simpan zona.', 'Isi nama gudang, kode gudang SAP (mis. 95-SKI), perusahaan dan CompanyDB SAP, Simpan.'],
        ],
        'users' => [
            'judul' => 'Pengguna & peran', 'apa' => 'Akun pengguna dan perannya: Administrator, Kepala gudang / QA, Admin inbound, Admin outbound, Operator gudang (handheld).', 'kapan' => 'Saat go-live dan setiap ada karyawan baru / keluar.', 'peran' => ['admin'],
            'langkah' => ['Isi nama, username, email (opsional), peran dan kata sandi awal, Simpan.', 'Nonaktifkan akun karyawan yang keluar (jangan dihapus agar riwayat tetap ada).'],
            'tips' => ['Pengguna bisa masuk dengan username atau email.', 'Minta pengguna mengganti kata sandi awal lewat tombol ⚙ di kanan atas.'],
        ],
        'ann' => [
            'judul' => 'Pengumuman', 'apa' => 'Pengumuman yang tampil di halaman depan untuk peran tertentu, dengan tingkat Info / Penting / Darurat dan periode tayang. Hanya Administrator yang bisa mengisi.', 'kapan' => 'Saat ada perubahan prosedur, jadwal stock opname, recall produk, atau informasi shift.', 'peran' => ['admin'],
            'langkah' => ['Isi judul dan isi pengumuman.', 'Pilih tingkat dan peran tujuan (kosong = semua peran).', 'Atur tanggal tayang, centang Sematkan bila perlu di paling atas, klik <b>Simpan & tayangkan</b>.'],
        ],
        'password' => [
            'judul' => 'Ganti kata sandi', 'apa' => 'Mengganti kata sandi akun sendiri.', 'kapan' => 'Saat pertama kali masuk dan secara berkala.', 'peran' => ['admin', 'manager', 'inbound', 'outbound', 'operator'],
            'langkah' => ['Isi kata sandi lama, kata sandi baru (min. 8 karakter) dua kali, Simpan.'],
        ],
        'implementasi' => [
            'judul' => 'Persiapan Implementasi', 'apa' => 'Langkah-langkah mempersiapkan implementasi FoodTrace dari kick-off sampai pasca go-live, serta peran dan tanggung jawab setiap anggota tim proyek.', 'kapan' => 'Sebelum proyek dimulai dan sebagai daftar periksa di setiap rapat proyek.', 'peran' => ['admin', 'manager'],
            'langkah' => ['Baca tahapan berurutan dan centang hasil kerja (deliverable) yang sudah selesai.', 'Tunjuk orang untuk setiap peran di tabel tim proyek.'],
        ],

        // ===================== HHT =====================
        'hht' => [
            'judul' => 'Handheld (HHT): menu utama', 'apa' => 'Layar untuk perangkat genggam / HP operator: tombol besar, satu langkah per layar, scan barcode lalu Enter. Angka merah = pekerjaan menunggu.', 'kapan' => 'Sepanjang shift di lantai gudang.', 'peran' => ['operator'],
            'langkah' => ['Buka alamat FoodTrace di browser HHT, masuk, lalu pilih <b>Handheld (HHT)</b> (simpan sebagai shortcut di layar utama).', 'Pilih tugas: Receiving, Put away, Picking, Checker, Loading, Inventory check, Cycle count, QC hold, Butcher out atau Cek barcode.'],
            'tips' => ['Getar pendek = berhasil, getar panjang = ditolak (baca pesan merah).', 'Scanner HHT harus diset mengirim Enter setelah scan.'],
        ],
        'hht-receiving' => [
            'judul' => 'HHT: Receiving', 'apa' => 'Penerimaan dari HHT: scan dock, scan item, scan karton / isi qty, simpan palet, validasi.', 'kapan' => 'Saat bongkar di dock.', 'peran' => ['operator', 'inbound'],
            'langkah' => ['Pilih / scan nomor PO.', 'Scan barcode dock (mis. DOCK_01).', 'Scan item, lalu scan setiap karton (berat aktual) atau isi qty, lot dan expiry, tekan Terima.', 'Saat palet penuh isi No. palet lalu <b>Simpan palet</b>; cetak label.', 'Setelah semua selesai tekan <b>✓ Validasi penerimaan</b>.'],
        ],
        'hht-putaway' => [
            'judul' => 'HHT: Put away / bin transfer', 'apa' => 'Memindah satu LPN utuh: scan LPN, lihat saran bin, scan bin tujuan.', 'kapan' => 'Setelah validasi penerimaan dan saat merapikan rak.', 'peran' => ['operator'],
            'langkah' => ['Scan LPN (atau pilih dari daftar menunggu put away).', 'Bawa ke bin yang disarankan, scan barcode bin, tekan Pindahkan.'],
            'tips' => ['Rak zona lain ditolak.'],
        ],
        'hht-pick' => [
            'judul' => 'HHT: Picking', 'apa' => 'Sistem menunjukkan satu baris ambil berikutnya (lokasi, item, lot, LPN, qty) berdasarkan FEFO.', 'kapan' => 'Saat picking job berjalan.', 'peran' => ['operator'],
            'langkah' => ['Pilih picking job.', 'Pergi ke lokasi yang ditunjukkan, scan lokasi, scan LPN / lot / item, isi qty bila sebagian, tekan Ambil.', 'Setelah semua diambil, scan lokasi staging dan tekan Finish trip.'],
        ],
        'hht-check' => [
            'judul' => 'HHT: Checker', 'apa' => 'Pemeriksaan barang di staging: scan nomor DO / SO lalu scan setiap LPN / lot / item.', 'kapan' => 'Setelah finish trip, sebelum loading.', 'peran' => ['operator', 'outbound'],
            'langkah' => ['Scan nomor DO / SO (atau pilih dari daftar).', 'Scan setiap barang sampai semua baris ✓.'],
        ],
        'hht-loading' => [
            'judul' => 'HHT: Loading', 'apa' => 'Scan setiap LPN yang naik ke kendaraan, lalu tutup loading.', 'kapan' => 'Saat memuat kendaraan.', 'peran' => ['operator', 'outbound'],
            'langkah' => ['Pilih loading job.', 'Scan setiap LPN yang dinaikkan.', 'Setelah 100% tekan Tutup loading (Goods Issue).'],
        ],
        'hht-ic' => [
            'judul' => 'HHT: Inventory check', 'apa' => 'Cek fisik LPN / lokasi dari HHT dan kirim untuk approval.', 'kapan' => 'Saat cek fisik.', 'peran' => ['operator'],
            'langkah' => ['Scan LPN atau lokasi.', 'Isi qty fisik dan expiry yang terbaca.', 'Tekan Kirim untuk approval.'],
        ],
        'hht-count' => [
            'judul' => 'HHT: Cycle count', 'apa' => 'Hitung buta per lokasi: qty sistem tidak ditampilkan.', 'kapan' => 'Sesuai jadwal cycle count.', 'peran' => ['operator'],
            'langkah' => ['Scan lokasi.', 'Isi hasil hitung setiap baris, Simpan hitungan.'],
        ],
        'hht-hold' => [
            'judul' => 'HHT: QC hold', 'apa' => 'Menahan LPN bermasalah ke lokasi karantina zona.', 'kapan' => 'Begitu menemukan barang bermasalah.', 'peran' => ['operator'],
            'langkah' => ['Scan LPN, pilih alasan, tekan Tahan di karantina.', 'Pindahkan palet secara fisik ke area QC.'],
        ],
        'hht-butcher' => [
            'judul' => 'HHT: Butcher out', 'apa' => 'Mengeluarkan bahan ke ruang potong langsung dari HHT.', 'kapan' => 'Saat mengambil bahan untuk dipotong.', 'peran' => ['operator'],
            'langkah' => ['Scan LPN bahan, isi qty (kosong = semua), pilih proses, isi referensi.', 'Tekan Keluarkan ke ruang potong.'],
        ],
        'hht-lookup' => [
            'judul' => 'HHT: Cek barcode', 'apa' => 'Scan LPN, lokasi atau item untuk melihat isi, lot, expiry dan status.', 'kapan' => 'Kapan saja saat ragu dengan barang atau lokasi.', 'peran' => ['operator'],
            'langkah' => ['Scan barcode apa saja; hasil langsung tampil.'],
        ],
    ],

    // Nama route → topik. Urutan penting: pola pertama yang cocok dipakai.
    'rute' => [
        'dashboard' => 'dashboard',
        'inbound.index' => 'inbound', 'inbound.*' => 'receiving', 'lpn.labels' => 'label-lpn', 'cartons.*' => 'cartons',
        'putaway.*' => 'putaway', 'internal.*' => 'bin-transfer', 'qc.release' => 'qc-release', 'qc.*' => 'qc',
        'stock.index' => 'stock', 'lpn.index' => 'lpn', 'lpn.show' => 'lpn-detail', 'stock.card' => 'stock-card', 'cycle.*' => 'cycle',
        'ic.index' => 'ic', 'ic.create' => 'ic', 'ic.*' => 'ic-detail',
        'outbound.index' => 'outbound', 'outbound.*' => 'outbound-detail', 'jobs.index' => 'picking', 'jobs.*' => 'picking-detail',
        'checker.*' => 'checker', 'loading.index' => 'loading', 'loading.*' => 'loading-detail', 'pod.public' => 'pod-driver', 'pod.*' => 'pod',
        'sap.queue' => 'sap-queue', 'sap.post' => 'sap-queue', 'sap.*' => 'sap-sync',
        'items.index' => 'items', 'items.*' => 'item-form', 'brands.*' => 'brands', 'uoms.*' => 'uoms', 'locations.*' => 'locations',
        'partners.edit' => 'partner-form', 'templates.*' => 'templates', 'zones.*' => 'zones', 'warehouse.*' => 'zones',
        'users.*' => 'users', 'ann.*' => 'ann', 'password' => 'password', 'implementation' => 'implementasi', 'help.index' => 'umum',
        'hht.home' => 'hht', 'hht.receipt*' => 'hht-receiving', 'hht.putaway' => 'hht-putaway', 'hht.jobs' => 'hht-pick', 'hht.pick' => 'hht-pick',
        'hht.check' => 'hht-check', 'hht.loading*' => 'hht-loading', 'hht.ic' => 'hht-ic', 'hht.count' => 'hht-count', 'hht.hold' => 'hht-hold',
        'hht.butcher' => 'hht-butcher', 'hht.lookup' => 'hht-lookup',
    ],

    'kelompok' => [
        'Mulai' => ['umum', 'dashboard', 'implementasi'],
        'Inbound' => ['inbound', 'receiving', 'label-lpn', 'cartons', 'putaway', 'qc', 'qc-release'],
        'Inventory' => ['stock', 'lpn', 'lpn-detail', 'bin-transfer', 'ic', 'ic-detail', 'cycle', 'stock-card'],
        'Outbound' => ['outbound', 'outbound-detail', 'picking', 'picking-detail', 'checker', 'loading', 'loading-detail', 'pod', 'pod-driver', 'butcher-out', 'butcher-in'],
        'Laporan' => ['laporan-by-location', 'laporan-by-item', 'laporan-expired', 'laporan-item-low', 'laporan-material-in', 'laporan-material-out', 'laporan-transfer-log', 'laporan-adjustment', 'laporan-ic-discrepancy', 'laporan-warehouse-transfer'],
        'Integrasi SAP B1' => ['sap-sync', 'sap-queue'],
        'Master Data' => ['items', 'item-form', 'brands', 'uoms', 'locations', 'source', 'destination', 'partner-form', 'templates', 'zones', 'users', 'ann', 'password'],
        'Handheld (HHT)' => ['hht', 'hht-receiving', 'hht-putaway', 'hht-pick', 'hht-check', 'hht-loading', 'hht-ic', 'hht-count', 'hht-hold', 'hht-butcher', 'hht-lookup'],
    ],

    'konsep' => [
        'LPN' => 'License Plate Number: nomor unik per palet (mis. SKI0000123) dengan label QR. Satu LPN = satu item + satu lot.',
        'FEFO' => 'First Expired First Out: barang dengan expiry terdekat diambil lebih dulu. Item tertentu bisa memakai FIFO (tanggal masuk).',
        'Zona' => 'Frozen (≤ -18 °C), Chilled (0–4 °C), Dry (suhu ruang terkendali). Item hanya boleh disimpan di rak zonanya.',
        'Status stok' => 'Receiving (di dock), Available (di rak, bisa dipicking), Picked (di staging keluar), Hold (karantina / retur), Damage (rusak / waste).',
        'Owner / titipan' => 'Stok milik pihak lain yang disimpan di gudang; dipisah dari stok sendiri dan hanya dipakai untuk order owner tersebut.',
        'ShipTo' => 'Alamat / outlet tujuan milik satu customer SAP. Satu customer bisa punya ratusan ShipTo.',
        'Berat aktual' => 'Catch weight: barang dengan berat berbeda per karton (daging, keju blok). Berat dibaca dari barcode karton.',
        'Umur simpan minimum' => 'Persentase sisa umur yang disyaratkan saat diterima (per item) dan saat dikirim (per customer).',
        'Goods Issue / Receipt' => 'Pencatatan barang keluar / masuk yang diposting ke SAP B1 sebagai Delivery / Goods Receipt PO.',
    ],
];
