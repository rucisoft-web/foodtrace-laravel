<!doctype html>
<html lang="id">
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<title>FoodTrace · WMS gudang pangan frozen, chilled & dry</title>
<meta name="description" content="FoodTrace: sistem manajemen gudang untuk bahan makanan frozen, chilled dan dry, mengacu pada food safety, food quality dan sustainability.">
<link rel="icon" href="{{ asset('favicon.png') }}">
<link rel="stylesheet" href="{{ asset('css/foodtrace.css') }}?v={{ config('foodtrace.version') }}">
</head>
<body class="lp">
<header class="lp-top">
  <div class="in">
    <img src="{{ asset('img/logo-saranakulina.png') }}" alt="Saranakulina">
    <span style="border-left:1px solid var(--line-2);padding-left:14px;font-weight:700;color:var(--maroon)">FoodTrace <small class="muted" style="font-weight:500">WMS</small></span>
    <div class="sp"></div>
    <nav>
      <a href="#masalah">Masalah</a><a href="#fitur">Fitur</a><a href="#pilar">Food safety</a><a href="#alur">Alur kerja</a><a href="#pengumuman">Pengumuman</a>
    </nav>
    @auth<a class="btn" href="{{ route('dashboard') }}">Buka aplikasi →</a>@else<a class="btn red" href="#masuk">Masuk</a>@endauth
  </div>
</header>

<section class="hero">
  <div class="wrap grid">
    <div>
      <span class="eyebrow">Warehouse Management System</span>
      <h1>Setiap karton makanan <em>tertelusur</em>, dari dock terima sampai meja pelanggan.</h1>
      <p class="lead">FoodTrace mengelola gudang bahan makanan <b>frozen</b>, <b>chilled</b> dan <b>dry</b> sampai tingkat lot, tanggal kedaluwarsa,
        palet (LPN), bin, dan berat aktual per karton. Dokumen PO dan SO datang langsung dari SAP Business One; hasil kerja gudang kembali ke SAP otomatis.</p>
      <div class="pills"><span class="pill f">❄ Frozen ≤ -18 °C</span><span class="pill c">◐ Chilled 0–4 °C</span><span class="pill d">☀ Dry suhu ruang</span>
        <span class="pill">FEFO</span><span class="pill">LPN + QR</span><span class="pill">Handheld</span><span class="pill">SAP B1</span></div>
      <div class="grid g3" style="margin-top:22px">
        <div class="kpi"><div class="l">Penelusuran</div><div class="v">Lot · LPN</div><div class="s">recall dalam hitungan menit</div></div>
        <div class="kpi red"><div class="l">Kedaluwarsa</div><div class="v">FEFO</div><div class="s">lot lewat tanggal diblokir</div></div>
        <div class="kpi green"><div class="l">Pemborosan</div><div class="v">↓ Waste</div><div class="s">peringatan 30/60/90 hari</div></div>
      </div>
    </div>
    <div id="masuk">
      <div class="login-card">
        <div class="in">
          @auth
            <h2>Halo, {{ auth()->user()->name }}</h2>
            <p class="muted">Anda sudah masuk sebagai {{ auth()->user()->roleLabel() }}.</p>
            <p class="actions"><a class="btn lg" href="{{ route('dashboard') }}">Dashboard</a><a class="btn ghost lg" href="{{ route('hht.home') }}">Handheld</a></p>
          @else
            <h2>Masuk ke FoodTrace</h2>
            <p class="muted" style="margin-top:0">Gunakan nama pengguna atau email yang diberikan Admin.</p>
            @if(session('ok'))<div class="alert ok">{{ session('ok') }}</div>@endif
            @if($errors->any())<div class="alert bad">@foreach($errors->all() as $e)<div>{{ $e }}</div>@endforeach</div>@endif
            <form method="post" action="{{ url('/login') }}">
              @csrf
              <div class="f"><label>Nama pengguna / email</label><input type="text" name="login" value="{{ old('login') }}" required autocomplete="username" autofocus></div>
              <div class="f"><label>Kata sandi</label><input type="password" name="password" required autocomplete="current-password"></div>
              <div class="inline" style="justify-content:space-between;margin-bottom:12px">
                <label class="chk"><input type="checkbox" name="remember" value="1"> Ingat saya</label>
                <label class="chk"><input type="checkbox" name="hht" value="1"> Buka mode handheld</label>
              </div>
              <button class="btn lg" style="width:100%;justify-content:center">Masuk</button>
            </form>
          @endauth
        </div>
      </div>
      <div class="card" style="margin-top:14px">
        <div class="card-h"><h3>Pengumuman terbaru</h3><div class="sp"></div><a href="#pengumuman" class="muted">semua →</a></div>
        <div class="card-b">
          @forelse($anns->take(3) as $a)
            <div class="ann {{ $a->level }}"><h4>@if($a->pinned)📌 @endif{{ $a->title }}</h4><div class="meta">{{ implode(', ', $a->roleLabels()) }} · {{ $a->created_at->translatedFormat('d M Y') }}</div></div>
          @empty <div class="muted">Belum ada pengumuman.</div> @endforelse
        </div>
      </div>
    </div>
  </div>
</section>

<section class="sec" id="masalah">
  <div class="wrap">
    <span class="eyebrow">Tantangan gudang pangan</span>
    <h2 class="t2">Masalah yang sering terjadi di gudang frozen, chilled dan dry</h2>
    <p class="sub">Gudang bahan makanan berbeda dari gudang barang umum: produknya punya umur, sensitif suhu, dijual per berat, dan bila salah kirim bisa membahayakan konsumen. Berikut masalah yang paling sering muncul beserta penyebabnya.</p>
    <div class="grid g3">
      @foreach([
        ['Barang kedaluwarsa menumpuk dan terbuang', 'Lot lama tertutup palet baru di rak, picker mengambil yang paling dekat, bukan yang paling cepat kedaluwarsa.', 'Tidak ada pencatatan lot & tanggal kedaluwarsa per palet, picking memakai FIFO "kira-kira" dan tidak ada peringatan dini.'],
        ['Recall lambat dan tidak lengkap', 'Saat pemasok menarik satu lot, gudang butuh berhari-hari mencari di bin mana dan ke pelanggan siapa lot itu terkirim.', 'Lot tidak dicatat saat terima dan saat kirim; surat jalan hanya berisi nama item, tanpa lot dan palet.'],
        ['Barang disimpan di zona suhu yang salah', 'Keju chilled tersimpan di rak dry atau daging frozen menunggu terlalu lama di dock sehingga rantai dingin putus.', 'Bin tidak terikat zona, operator bebas meletakkan barang, dan tidak ada status "belum disimpan" yang terpantau.'],
        ['Selisih stok fisik vs SAP', 'Stok di SAP berbeda dengan isi rak; penjualan dijanjikan padahal barang tidak ada, atau ada barang "hilang" yang ternyata salah bin.', 'Input manual berlapis (kertas → Excel → SAP), pemindahan antar-bin tidak dicatat, satuan PCK dan PCE tertukar.'],
        ['Selisih berat daging & keju', 'Karton dijual per kg tetapi diterima per karton; total berat di faktur pemasok tidak sama dengan yang dicatat gudang.', 'Berat per karton tidak dicatat, label lbs dari pemasok Amerika dikonversi manual, barcode GS1 tidak dibaca.'],
        ['Salah kirim ke pelanggan', 'Item atau lot yang salah termuat ke truk, pelanggan hotel menolak barang dengan sisa umur terlalu pendek.', 'Tidak ada pemeriksaan (checker) dan scan saat muat; syarat sisa umur tiap pelanggan hanya diingat orang tertentu.'],
        ['Barang rusak / karantina ikut terjual', 'Produk yang menunggu hasil uji QA atau kemasan penyok tetap bisa diambil picker.', 'Status stok (karantina, rusak) tidak terpisah dari stok tersedia di sistem; keputusan QA hanya dicatat di kertas.'],
        ['Bukti terima pelanggan tidak jelas', 'Klaim kurang kirim datang seminggu kemudian tanpa bukti siapa yang menerima dan berapa jumlahnya.', 'Surat jalan kertas kembali terlambat, tidak ada foto dan tanda terima digital.'],
        ['Kerja gudang tergantung orang', 'Saat kepala gudang cuti, tidak ada yang tahu prioritas kirim, lokasi barang atau cara memproses retur.', 'Prosedur tidak terdokumentasi di sistem; tidak ada panduan per layar dan per peran.'],
      ] as [$t, $d, $c])
        <div class="prob"><h4>{{ $t }}</h4><div>{{ $d }}</div><div class="cause"><b>Penyebab:</b> {{ $c }}</div></div>
      @endforeach
    </div>
  </div>
</section>

<section class="sec alt" id="fitur">
  <div class="wrap">
    <span class="eyebrow">Fitur FoodTrace</span>
    <h2 class="t2">Fitur WMS yang dirancang khusus untuk gudang makanan</h2>
    <p class="sub">Setiap fitur menjawab salah satu masalah di atas. Aturan diterapkan oleh sistem, bukan sekadar diingat.</p>
    <div class="grid g3">
      @foreach([
        ['Inbound dari SAP & receiving per palet', ['PO masuk otomatis dari SAP (POR) lengkap dengan container', 'Loading dock wajib diisi', 'Scan karton GS1-128 / label lbs → berat, lot, kedaluwarsa', 'Tolak over receiving & sisa umur di bawah batas']],
        ['Label LPN format Largo dengan QR', ['1 palet = 1 item + 1 lot', 'Label 150×100 mm: Exp, Rcv, Pack, lot, EST, container', '24 stiker QR kecil untuk karton', 'Cetak ulang otomatis bila qty/expiry berubah']],
        ['Put away per zona Frozen/Chilled/Dry', ['Setiap item punya zona wajib, setiap bin satu zona', 'Saran bin kosong di zona yang benar', 'Scan beberapa LPN sekaligus lalu satu scan lokasi', 'Stok di dock tampil sebagai "Receiving" sampai disimpan']],
        ['Karantina & QC release', ['Item tertentu otomatis masuk area QC saat diterima', 'QC Hold per LPN dari handheld', 'Release sebagian GOOD dan sebagian DAMAGE', 'Auto release setelah durasi karantina']],
        ['Outbound: picking, checker, loading, POD', ['SO dari SAP (SOR) dengan banyak ShipTo per pelanggan', 'Picking Job per rute, saran lokasi FEFO', 'Checker & scan muat menolak barang yang salah', 'Surat jalan ber-QR → driver isi POD + foto']],
        ['Master data selaras SAP', ['Item, Brand SAP, satuan SAP tanpa konversi palsu', 'Source Master (pemasok) & Destination Master (pelanggan)', 'Batas sisa umur per pelanggan, toleransi per pemasok', 'Bin/rak berbarcode siap cetak']],
        ['Inventory check, cycle count & butcher', ['Cek fisik per LPN/lokasi dengan approval', 'Hitung buta (blind count) dari handheld', 'Butcher Out/In: rebag, trimming, repack + yield', 'Kartu stok lengkap per item, lot dan LPN']],
        ['Integrasi SAP B1', ['API sinkron POR/SOR idempoten (X-WMS-Token)', 'Antrean posting GRPO, Delivery, Transfer', 'Dokumen gagal tampil merah, bisa diproses ulang', 'Mode simulasi sebelum Service Layer tersambung']],
        ['Laporan & dashboard keputusan', ['Nilai stok per zona, status & item group', 'Kedaluwarsa 1/2/3/4/6 bulan, item low', 'Stok per Lokasi & Lokasi per Item', 'Arus barang 30 hari, okupansi rak, kinerja SAP']],
      ] as [$t, $pts])
        <div class="feat"><h4>{{ $t }}</h4><ul>@foreach($pts as $p)<li>{{ $p }}</li>@endforeach</ul></div>
      @endforeach
    </div>
    @if(file_exists(public_path('img/bantuan/dashboard.jpg')))
    <div class="grid g2" style="margin-top:22px">
      <div class="shot"><img src="{{ asset('img/bantuan/dashboard.jpg') }}" alt="Dashboard FoodTrace" loading="lazy"></div>
      <div class="shot"><img src="{{ asset('img/bantuan/hht.jpg') }}" alt="Handheld FoodTrace" loading="lazy" style="max-height:420px;object-fit:cover;object-position:top"></div>
    </div>
    @endif
  </div>
</section>

<section class="sec" id="pilar">
  <div class="wrap">
    <span class="eyebrow">Acuan</span>
    <h2 class="t2">Food safety, food quality & sustainability</h2>
    <p class="sub">FoodTrace mengikuti prinsip keamanan pangan (HACCP, ketertelusuran ala ISO 22000 / BRCGS Storage & Distribution, CPPOB) dan pengurangan susut pangan.</p>
    <div class="grid g3">
      <div class="pillar safety"><h3>Food safety</h3><ul>
        <li>Ketertelusuran satu langkah ke belakang (pemasok, container, lot) dan satu langkah ke depan (pelanggan, ShipTo)</li>
        <li>Lot kedaluwarsa diblokir otomatis, tidak bisa dikirim</li>
        <li>Karantina memisahkan stok yang belum lolos QA</li>
        <li>Zona wajib mencegah produk disimpan di ruang yang salah</li></ul></div>
      <div class="pillar quality"><h3>Food quality</h3><ul>
        <li>FEFO: lot terdekat kedaluwarsa keluar lebih dulu</li>
        <li>Batas sisa umur saat terima (per item) dan saat kirim (per pelanggan)</li>
        <li>Berat aktual per karton, klaim selisih Lbs–Kg berbasis data</li>
        <li>POD dengan qty diterima, catatan dan foto</li></ul></div>
      <div class="pillar sust"><h3>Sustainability</h3><ul>
        <li>Peringatan kedaluwarsa 30/60/90 hari untuk promo atau retur sebelum terbuang</li>
        <li>Butcher & repack: sisa potong menjadi produk baru, yield terukur</li>
        <li>Paperless: label, checker, loading dan POD lewat handheld</li>
        <li>Okupansi rak terukur, energi ruang dingin dipakai efisien</li></ul></div>
    </div>
  </div>
</section>

<section class="sec alt" id="alur">
  <div class="wrap">
    <span class="eyebrow">Alur kerja</span>
    <h2 class="t2">Dari PO SAP sampai bukti terima pelanggan</h2>
    <div class="flow" style="margin-top:18px">
      <div><b>Inbound Document</b>PO dari SAP (POR) masuk otomatis.</div>
      <div><b>Receiving</b>Dock, scan karton per palet, cetak label LPN.</div>
      <div><b>Put Away</b>Scan LPN → scan bin di zona yang benar.</div>
      <div><b>Picking Job</b>SO dari SAP (SOR), ambil sesuai FEFO.</div>
      <div><b>Checker & Loading</b>Cocokkan LPN, scan saat muat, Goods Issue.</div>
      <div><b>POD</b>Driver isi penerima, qty, foto lewat QR.</div>
    </div>
  </div>
</section>

<section class="sec" id="pengumuman">
  <div class="wrap">
    <span class="eyebrow">Pengumuman</span>
    <h2 class="t2">Pengumuman untuk tim gudang</h2>
    <p class="sub">Diisi oleh Admin. Pilih peran untuk melihat pengumuman yang ditujukan kepada Anda.</p>
    <div class="tabs">
      <a href="{{ url('/') }}#pengumuman" class="{{ $role ? '' : 'on' }}">Semua</a>
      @foreach(\App\Models\User::ROLES as $k => $l)<a href="{{ url('/?peran='.$k) }}#pengumuman" class="{{ $role === $k ? 'on' : '' }}">{{ $l }}</a>@endforeach
    </div>
    <div class="grid g2">
      @forelse($anns as $a)
        <div class="ann {{ $a->level }}">
          <h4>@if($a->pinned)📌 @endif{{ $a->title }} <span class="badge {{ ['info' => 'b-navy', 'penting' => 'b-amber', 'darurat' => 'b-red'][$a->level] ?? 'b-grey' }}">{{ \App\Models\Announcement::LEVELS[$a->level] ?? $a->level }}</span></h4>
          <div class="meta">Untuk: {{ implode(', ', $a->roleLabels()) }} · {{ $a->created_at->translatedFormat('d M Y') }}@if($a->ends_at) · berlaku s.d. {{ $a->ends_at->translatedFormat('d M Y') }}@endif</div>
          <div>{!! nl2br(e($a->body)) !!}</div>
        </div>
      @empty <div class="muted">Belum ada pengumuman untuk peran ini.</div> @endforelse
    </div>
  </div>
</section>

<footer class="lp-foot"><div class="wrap inline" style="justify-content:space-between">
  <span>© {{ date('Y') }} {{ \App\Models\Setting::get('company_name', 'PT Sarana Kulina Intisejahtera') }} · FoodTrace v{{ config('foodtrace.version') }}</span>
  <span>Fine foods & dairy products</span></div></footer>
</body>
</html>
