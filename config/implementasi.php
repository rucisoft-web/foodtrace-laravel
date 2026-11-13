<?php

/*
 * Menu "Persiapan Implementasi": tahapan proyek FoodTrace dan peran tim proyek.
 */

return [
    'ringkas' => 'Implementasi WMS gudang makanan berhasil bila tiga hal siap bersamaan: data master yang bersih (item, lokasi, partner), prosedur lapangan yang disepakati (SOP penerimaan sampai POD), dan orang yang terlatih memakai handheld. Rencana di bawah ini untuk satu gudang dengan tiga zona (Frozen, Chilled, Dry), kurang lebih 8–10 minggu.',

    'fase' => [
        [
            'kode' => '0', 'judul' => 'Inisiasi & kick-off', 'durasi' => 'Minggu 1',
            'tujuan' => 'Menyamakan tujuan, ruang lingkup, jadwal dan penanggung jawab.',
            'langkah' => [
                'Tetapkan sponsor proyek dan project manager; bentuk tim inti (lihat tabel peran).',
                'Tentukan ruang lingkup: gudang, zona, jenis dokumen SAP (PO, SO, transfer, retur), titipan, butcher.',
                'Tetapkan target terukur: akurasi stok ≥ 99,5%, waste expired turun, waktu picking per order, 100% LPN berlabel.',
                'Sepakati jadwal go-live (hindari akhir bulan / musim puncak), jadwal rapat mingguan dan jalur eskalasi.',
                'Rapat kick-off dengan semua kepala bagian: gudang, QA, purchasing, sales admin, IT, keuangan.',
            ],
            'hasil' => ['Project charter & jadwal', 'Daftar tim dan peran (RACI)', 'Daftar KPI awal (baseline)'],
        ],
        [
            'kode' => '1', 'judul' => 'Analisis proses & desain', 'durasi' => 'Minggu 1–2',
            'tujuan' => 'Memetakan proses saat ini dan menyepakati proses dengan FoodTrace.',
            'langkah' => [
                'Observasi lapangan: bongkar kontainer, tally karton, penyimpanan, picking, loading, dokumen kertas yang dipakai.',
                'Petakan masalah saat ini: barang tidak ketemu, salah lot, expired di rak, selisih berat, salah kirim outlet.',
                'Desain alur To-Be: SAP PO → Receiving & label LPN → Put away per zona → QC → Picking FEFO → Checker → Loading → GI → POD.',
                'Tetapkan aturan bisnis: minimum sisa umur terima per item, minimum sisa umur kirim per customer, toleransi over receiving per pemasok, item auto karantina.',
                'Tetapkan siapa yang menyetujui: inventory check, cycle count, rilis QC, tutup paksa loading.',
            ],
            'hasil' => ['Dokumen alur proses To-Be', 'Daftar aturan food safety per item / customer / pemasok', 'Matriks persetujuan'],
        ],
        [
            'kode' => '2', 'judul' => 'Layout gudang & perangkat', 'durasi' => 'Minggu 2–4',
            'tujuan' => 'Gudang fisik siap discan: setiap lokasi punya barcode, perangkat tersedia.',
            'langkah' => [
                'Gambar layout per zona: dock, staging masuk, rak palet utuh (bulk), area picking, QC / karantina, retur, damage, staging keluar, ruang potong.',
                'Tetapkan konvensi kode lokasi, mis. SKI-FZ-A01-1 (gudang-zona-rak-level).',
                'Input struktur lokasi di Master Data → Bin / Rak Master; cetak dan tempel label barcode lokasi.',
                'Siapkan perangkat: HHT / HP Android dengan scanner (set kirim Enter), printer label 150 × 100 mm, printer A4 untuk surat jalan, Wi-Fi yang menjangkau cold room.',
                'Uji sinyal Wi-Fi dan baterai HHT di dalam ruang frozen (suhu rendah menguras baterai).',
            ],
            'hasil' => ['Layout gudang & daftar lokasi', 'Label barcode lokasi terpasang', 'Perangkat teruji di semua zona'],
        ],
        [
            'kode' => '3', 'judul' => 'Data master', 'durasi' => 'Minggu 2–5',
            'tujuan' => 'Data master bersih dan sama dengan SAP.',
            'langkah' => [
                'Ekspor item dari SAP (OITM): ItemCode, nama, group, brand (U_Brand), satuan persediaan, harga pokok.',
                'Lengkapi per item bersama QA: zona simpan, umur simpan, minimum sisa umur terima, berat aktual, auto karantina, minimum stok, berat per satuan.',
                'Ekspor pemasok dan customer (OCRD) beserta ShipTo; isi rute (shipping group), minimum sisa umur kirim, toleransi over receiving.',
                'Daftarkan template barcode karton untuk item berat aktual; uji dengan karton asli setiap pemasok.',
                'Isi Satuan SAP, Brand SAP, zona dan kode gudang SAP.',
            ],
            'hasil' => ['Item Master tervalidasi QA', 'Source & Destination Master + ShipTo', 'Template barcode teruji'],
        ],
        [
            'kode' => '4', 'judul' => 'Integrasi SAP B1', 'durasi' => 'Minggu 3–6',
            'tujuan' => 'Dokumen mengalir otomatis antara SAP dan FoodTrace.',
            'langkah' => [
                'Tim SAP menyiapkan ekspor POR (PO) dan SOR (SO) ke format file sinkron FoodTrace.',
                'Sambungkan ke API FoodTrace (POST /api/v1/sync, header X-WMS-Token) atau impor file manual.',
                'Uji posting balik: Goods Receipt PO dan Delivery (mode simulasi dulu, lalu Service Layer).',
                'Uji kasus khusus: titipan (owner), ShipTo baru, item belum terdaftar, partial delivery, retur.',
                'Tetapkan prosedur bila integrasi gagal (siapa memeriksa antrean SAP setiap pagi).',
            ],
            'hasil' => ['Integrasi PO / SO berjalan', 'Posting GRPO / Delivery teruji', 'SOP penanganan error sinkron'],
        ],
        [
            'kode' => '5', 'judul' => 'Uji coba (UAT) & SOP', 'durasi' => 'Minggu 5–7',
            'tujuan' => 'Pengguna kunci membuktikan sistem sesuai proses dan aturan.',
            'langkah' => [
                'Susun skenario uji end-to-end per zona memakai data nyata (lihat data demo sebagai contoh).',
                'Uji aturan: zona salah ditolak, over receiving ditolak, lot expired ditolak, FEFO, salah ambil ditolak checker.',
                'Catat temuan, perbaiki konfigurasi / data, ulangi sampai lulus.',
                'Tulis SOP singkat per peran (gunakan menu Panduan sebagai dasar) dan tempel di area kerja.',
                'Tanda tangan berita acara UAT oleh kepala gudang dan QA.',
            ],
            'hasil' => ['Skenario & hasil UAT', 'SOP per peran', 'Berita acara UAT'],
        ],
        [
            'kode' => '6', 'judul' => 'Pelatihan', 'durasi' => 'Minggu 6–8',
            'tujuan' => 'Semua pengguna mampu menjalankan tugasnya tanpa pendamping.',
            'langkah' => [
                'Latih key user lebih dulu (train the trainer), lalu key user melatih operator per shift.',
                'Operator: praktik HHT langsung di gudang (receiving, put away, picking, checker, loading, cycle count).',
                'Admin inbound / outbound: dokumen, label, picking job, loading, surat jalan, POD.',
                'Kepala gudang / QA: dashboard, approval, QC hold / release, laporan.',
                'Buat akun pengguna dengan peran yang benar; uji masuk setiap orang.',
            ],
            'hasil' => ['Daftar hadir & penilaian pelatihan', 'Akun pengguna aktif'],
        ],
        [
            'kode' => '7', 'judul' => 'Cut-over & go-live', 'durasi' => 'Minggu 8–9',
            'tujuan' => 'Stok awal akurat dan operasi pindah ke FoodTrace.',
            'langkah' => [
                'Bekukan penerimaan / pengiriman (cut-off) sesuai jadwal yang disepakati dengan sales dan purchasing.',
                'Stock opname per lokasi: setiap palet diberi LPN dan label, catat lot dan expiry nyata.',
                'Muat stok awal (per item + lot + LPN + lokasi + owner); cocokkan total dengan SAP OnHand per item.',
                'Tarik PO / SO terbuka dari SAP.',
                'Go-live: tim proyek mendampingi di lantai gudang setiap shift selama minimal 2 minggu (hypercare).',
            ],
            'hasil' => ['Berita acara stock opname & rekonsiliasi SAP', 'Checklist go-live', 'Jadwal pendampingan'],
        ],
        [
            'kode' => '8', 'judul' => 'Stabilisasi & perbaikan berkelanjutan', 'durasi' => 'Minggu 9 dst.',
            'tujuan' => 'Sistem dipakai disiplin dan memberi hasil terukur.',
            'langkah' => [
                'Rapat harian 15 menit selama hypercare: masalah, solusi, siapa.',
                'Jalankan cycle count rutin; pantau laporan Adjustment dan IC Discrepancy.',
                'Pantau KPI di Dashboard: akurasi stok, nilai expired, short order, POD selisih, SAP gagal.',
                'Evaluasi bulan pertama dan rencanakan perbaikan (aturan, layout, pelatihan ulang).',
            ],
            'hasil' => ['Laporan evaluasi go-live', 'Rencana perbaikan'],
        ],
    ],

    'peran' => [
        ['nama' => 'Sponsor proyek', 'siapa' => 'Direktur operasional / pemilik usaha', 'tugas' => [
            'Menetapkan tujuan bisnis dan target yang harus dicapai.',
            'Menyetujui anggaran, jadwal go-live dan perubahan ruang lingkup.',
            'Memutus hambatan lintas departemen (sales, purchasing, keuangan).',
            'Hadir di kick-off, review bulanan dan keputusan go / no-go.',
        ]],
        ['nama' => 'Project manager', 'siapa' => 'Manajer supply chain / PMO', 'tugas' => [
            'Menyusun dan menjaga jadwal, daftar tugas, risiko dan isu.',
            'Memimpin rapat mingguan dan melapor ke sponsor.',
            'Memastikan setiap hasil kerja (deliverable) selesai dan disetujui.',
            'Mengelola permintaan perubahan dan keputusan go-live.',
        ]],
        ['nama' => 'Process owner gudang', 'siapa' => 'Kepala gudang', 'tugas' => [
            'Memiliki proses gudang To-Be dan SOP lapangan.',
            'Menyetujui layout, kode lokasi dan aturan put away / picking.',
            'Menunjuk key user per shift dan memastikan operator ikut pelatihan.',
            'Menyetujui hasil stock opname dan penyesuaian stok.',
        ]],
        ['nama' => 'QA / food safety', 'siapa' => 'Kepala QA', 'tugas' => [
            'Menetapkan umur simpan, minimum sisa umur terima dan kirim, item auto karantina.',
            'Menetapkan standar suhu zona dan alur QC hold / release / reject.',
            'Menguji aturan food safety saat UAT.',
            'Memakai laporan Expired dan QC untuk keputusan harian.',
        ]],
        ['nama' => 'Key user inbound', 'siapa' => 'Admin / supervisor penerimaan', 'tugas' => [
            'Menguji receiving, label LPN, tally karton, put away dan template barcode pemasok.',
            'Membantu melengkapi Source Master dan toleransi over receiving.',
            'Melatih checker penerimaan dan operator put away.',
        ]],
        ['nama' => 'Key user outbound', 'siapa' => 'Admin / supervisor pengiriman', 'tugas' => [
            'Menguji picking job, checker, loading, surat jalan dan POD.',
            'Melengkapi Destination Master: ShipTo, rute, minimum sisa umur kirim.',
            'Melatih picker, checker dan driver (POD lewat QR).',
        ]],
        ['nama' => 'Data steward', 'siapa' => 'Staf master data / admin SAP', 'tugas' => [
            'Mengekspor dan membersihkan data item, partner, ShipTo dan brand dari SAP.',
            'Menjaga Item Master FoodTrace tetap sama dengan SAP setelah go-live.',
            'Memeriksa dokumen sinkron yang gagal karena item belum terdaftar.',
        ]],
        ['nama' => 'Konsultan / admin SAP B1', 'siapa' => 'Tim SAP internal atau partner SAP', 'tugas' => [
            'Menyiapkan ekspor POR / SOR dan pengiriman ke API FoodTrace.',
            'Menyiapkan posting balik GRPO / Delivery lewat Service Layer.',
            'Menguji dan memantau integrasi.',
        ]],
        ['nama' => 'IT & infrastruktur', 'siapa' => 'Tim IT', 'tugas' => [
            'Menyiapkan hosting (cPanel / server), domain, SSL, backup database harian.',
            'Menyediakan Wi-Fi yang menjangkau cold room, HHT, printer label dan printer A4.',
            'Mengelola akun pengguna bersama Administrator FoodTrace.',
        ]],
        ['nama' => 'Administrator FoodTrace', 'siapa' => 'Staf IT / key user senior', 'tugas' => [
            'Mengelola pengguna, peran dan pengumuman di halaman depan.',
            'Memantau antrean posting SAP setiap hari.',
            'Menjadi kontak pertama untuk pertanyaan pengguna.',
        ]],
        ['nama' => 'Trainer', 'siapa' => 'Key user / konsultan implementasi', 'tugas' => [
            'Menyusun materi pelatihan per peran berdasarkan menu Panduan.',
            'Melatih dan menilai kemampuan pengguna.',
            'Mendampingi di lantai gudang saat hypercare.',
        ]],
    ],

    // R = pelaksana, A = penanggung jawab akhir, C = dimintai masukan, I = diberi informasi.
    'raci' => [
        'kolom' => ['Sponsor', 'PM', 'Kepala gudang', 'QA', 'Key user', 'Data steward', 'SAP', 'IT'],
        'baris' => [
            'Ruang lingkup & jadwal' => ['A', 'R', 'C', 'C', 'I', 'I', 'C', 'C'],
            'Proses To-Be & SOP' => ['I', 'C', 'A', 'C', 'R', 'I', 'I', 'I'],
            'Aturan food safety' => ['I', 'I', 'C', 'A', 'C', 'R', 'I', 'I'],
            'Layout & label lokasi' => ['I', 'C', 'A', 'I', 'R', 'I', 'I', 'C'],
            'Data master' => ['I', 'C', 'C', 'C', 'C', 'A', 'R', 'I'],
            'Integrasi SAP' => ['I', 'A', 'I', 'I', 'C', 'C', 'R', 'R'],
            'Perangkat & hosting' => ['I', 'C', 'C', 'I', 'I', 'I', 'I', 'A'],
            'UAT' => ['I', 'A', 'R', 'R', 'R', 'C', 'C', 'C'],
            'Pelatihan' => ['I', 'A', 'C', 'C', 'R', 'I', 'I', 'I'],
            'Stock opname & go-live' => ['A', 'R', 'R', 'C', 'R', 'C', 'C', 'C'],
        ],
    ],

    'risiko' => [
        ['risiko' => 'Data item / lot tidak bersih', 'mitigasi' => 'Validasi Item Master bersama QA sebelum UAT; stock opname per LPN saat cut-over.'],
        ['risiko' => 'Wi-Fi atau baterai HHT gagal di ruang frozen', 'mitigasi' => 'Survei sinyal di fase layout; baterai cadangan dan charger di luar cold room.'],
        ['risiko' => 'Barcode karton pemasok tidak terbaca', 'mitigasi' => 'Uji template dengan karton asli; berat manual sebagai cadangan.'],
        ['risiko' => 'Pengguna kembali ke kertas', 'mitigasi' => 'Pendampingan hypercare, KPI harian, dukungan kepala gudang.'],
        ['risiko' => 'Integrasi SAP terlambat', 'mitigasi' => 'Mulai dengan impor file manual; antrean posting mode simulasi.'],
    ],
];
