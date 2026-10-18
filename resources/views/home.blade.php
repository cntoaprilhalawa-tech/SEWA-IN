<!DOCTYPE html>
<html lang="id" data-fx-intro>
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<title>SEWA-IN — Sewa barang antar mahasiswa, mudah dan aman</title>
<meta name="description" content="SEWA-IN mempertemukan mahasiswa yang butuh barang dengan mahasiswa yang menyewakannya. Cari, booking, dan bayar dalam satu tempat.">
<link rel="preconnect" href="https://fonts.googleapis.com">
<link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@600;700;800&family=Inter:wght@400;500;600&display=swap" rel="stylesheet">
<link rel="stylesheet" href="{{ asset('css/landing.css') }}">
@include('partials.head')
<script src="{{ asset('js/landing-fx.js') }}" defer></script>
</head>
<body class="fx-landing">

<header class="nav" data-reveal="down">
  <div class="wrap">
    <a href="{{ route('home') }}" class="logo" aria-label="SEWA-IN, ke beranda"><x-logo :height="30" /></a>
    <nav class="nav-links">
      <a href="{{ route('items.index') }}">Cari Barang</a>
      <a href="#cara-kerja">Cara Kerja</a>
      <x-theme-toggle />
      <a href="{{ route('login') }}">Masuk</a>
      <a href="{{ route('register') }}" class="cta">Daftar</a>
    </nav>
  </div>
</header>

<section class="hero">
  {{-- Efek latar: gelembung naik, cahaya mengikuti kursor, label barang melayang --}}
  <div class="fx-bubbles" aria-hidden="true"></div>
  <div class="fx-glow" aria-hidden="true"></div>
  <div class="fx-layer" aria-hidden="true">
    {{-- Label hanya di sisi kanan (sekitar panel alur) supaya tidak menimpa judul --}}
    <div class="ftag" data-depth="0.7" style="--r:-5deg;--d:.05s;--o:.8;--w:140px;left:56%;top:1%"><div class="ftag-in"><b>Tenda Dome</b><span>Rp25.000 <small>/hari</small></span></div></div>
    <div class="ftag" data-depth="0.4" style="--r:4deg;--d:.2s;--o:.55;--w:124px;left:69%;top:0%"><div class="ftag-in"><b>Proyektor</b><span>Rp50.000 <small>/hari</small></span></div></div>
    <div class="ftag" data-depth="1.3" style="--r:5deg;--d:.35s;--w:156px;right:2.5%;top:1%"><div class="ftag-in"><b>Kamera Mirrorless</b><span>Rp80.000 <small>/hari</small></span></div></div>
    <div class="ftag" data-depth="1.2" style="--r:6deg;--d:.65s;--w:140px;right:1.5%;bottom:13%"><div class="ftag-in"><b>Toga Wisuda</b><span>Rp35.000 <small>/hari</small></span></div></div>
    <div class="ftag hide-md" data-depth="0.6" style="--r:-3deg;--d:.8s;--o:.6;--w:132px;left:42%;bottom:9%"><div class="ftag-in"><b>Speaker Portable</b><span>Rp30.000 <small>/hari</small></span></div></div>
  </div>
  <div class="wrap">
    <div class="hero-copy">
    <h1 data-reveal="words">Butuh barang? <em>Sewa dari sesama mahasiswa.</em></h1>
    <p class="lead" data-reveal data-delay="520">Tenda, kamera, proyektor, hingga pakaian formal. Cari, booking, dan bayar dalam satu tempat, dengan pemilik yang sudah terverifikasi KTM.</p>
    <div class="hero-actions" data-reveal data-delay="680">
      <a href="{{ route('items.index') }}" class="btn primary">Jelajahi Barang</a>
      <a href="{{ route('register', ['role' => 'pemilik']) }}" class="btn outline">Sewakan Barangmu</a>
    </div>
    </div>
    <aside class="hero-panel" aria-hidden="true" data-reveal="right" data-delay="450">
      <p class="hp-title">Alur sewa di SEWA-IN</p>
      <div class="hp-row"><div class="hp-dot">✓</div><div><b>Booking disetujui</b><span>Pemilik menyetujui pengajuanmu</span></div></div>
      <div class="hp-row"><div class="hp-dot">✓</div><div><b>Pembayaran diterima</b><span>Transfer, QRIS, atau tunai</span></div></div>
      <div class="hp-row"><div class="hp-dot">✓</div><div><b>Barang dikembalikan</b><span>Deposit kembali sesuai kondisi barang</span></div></div>
    </aside>
  </div>
</section>

<div class="wrap">
  <form class="search-card" method="GET" action="{{ route('items.index') }}" id="heroSearch">
    <div class="search-grid">
      <div class="full">
        <label for="q">Mau sewa apa?</label>
        <input type="text" id="q" name="q" placeholder="Contoh: tenda, kamera, toga…">
      </div>
      <div>
        <label for="from">Mulai</label>
        <input type="date" id="from" name="available_from" min="{{ now()->toDateString() }}">
      </div>
      <div>
        <label for="to">Selesai</label>
        <input type="date" id="to" name="available_to" min="{{ now()->toDateString() }}">
      </div>
      <button type="submit" class="btn primary full">Cari</button>
    </div>
  </form>

  <div class="stats">
    <div class="stat-box"><b>{{ number_format($stats['items'], 0, ',', '.') }}</b><span>Barang tersedia</span></div>
    <div class="stat-box"><b>{{ number_format($stats['members'], 0, ',', '.') }}</b><span>Anggota terverifikasi</span></div>
    <div class="stat-box"><b>{{ number_format($stats['deals'], 0, ',', '.') }}</b><span>Transaksi selesai</span></div>
  </div>

  @if($categories->isNotEmpty())
    <section class="block">
      <div class="eyebrow">Kategori</div>
      <h2 class="title">Telusuri berdasarkan kebutuhan</h2>
      <div class="chips">
        @foreach($categories as $c)
          <a class="chip" href="{{ route('items.index', ['category' => $c->id]) }}">{{ $c->name }}<small>{{ $c->items_count }}</small></a>
        @endforeach
      </div>
    </section>
  @endif

  <section class="block">
    <div class="eyebrow">Terbaru</div>
    <h2 class="title">Baru ditambahkan</h2>
    <p class="sub">Barang yang baru saja disewakan dan siap dipesan.</p>
    @if($latestItems->isEmpty())
      <div class="empty">Belum ada barang yang disewakan. Jadilah yang pertama!</div>
    @else
      <div class="grid">
        @foreach($latestItems as $item)
          <x-item-card :item="$item" />
        @endforeach
      </div>
      <div class="more"><a href="{{ route('items.index') }}" class="btn dark">Lihat semua barang</a></div>
    @endif
  </section>

  <section class="block" id="cara-kerja">
    <div class="eyebrow">Cara kerja</div>
    <h2 class="title">Tiga langkah, selesai</h2>
    <p class="sub">Tanpa ribet bertukar kontak dan negosiasi di chat yang panjang.</p>
    <div class="steps">
      <div class="step"><div class="no">1</div><h3>Cari &amp; pilih tanggal</h3><p>Temukan barang yang kamu butuhkan, lalu cek ketersediaannya di tanggal yang diinginkan.</p></div>
      <div class="step"><div class="no">2</div><h3>Ajukan &amp; bayar</h3><p>Pemilik menyetujui pengajuanmu. Setelah itu bayar lewat transfer, QRIS, atau tunai saat ambil barang.</p></div>
      <div class="step"><div class="no">3</div><h3>Pakai &amp; kembalikan</h3><p>Ambil barang, pakai, lalu kembalikan tepat waktu. Beri ulasan agar komunitas makin terpercaya.</p></div>
    </div>
  </section>

  <section class="block">
    <div class="eyebrow">Kenapa SEWA-IN</div>
    <h2 class="title">Dibuat agar kamu tenang bertransaksi</h2>
    <div class="trust" style="margin-top:22px">
      <div><b>Terverifikasi KTM</b><span>Setiap anggota diverifikasi admin sebelum bertransaksi.</span></div>
      <div><b>Tanggal tidak bentrok</b><span>Sistem mencegah dua orang memesan barang yang sama.</span></div>
      <div><b>Deposit &amp; denda jelas</b><span>Aturan keterlambatan dan deposit tercatat transparan.</span></div>
      <div><b>Ulasan dua arah</b><span>Peminjam dan pemilik saling menilai setelah transaksi.</span></div>
    </div>
  </section>

  <div class="cta-band">
    <div>
      <h2>Punya barang yang jarang terpakai?</h2>
      <p>Sewakan dan dapatkan penghasilan tambahan. Satu akun bisa menjadi peminjam sekaligus pemilik.</p>
    </div>
    <a href="{{ route('register', ['role' => 'pemilik']) }}" class="btn primary">Daftar Gratis</a>
  </div>
</div>

<footer>
  <div class="wrap">
    <span>© {{ date('Y') }} SEWA-IN. Platform sewa barang antar mahasiswa.</span>
    <span><a href="{{ route('login') }}">Masuk</a> · <a href="{{ route('register') }}">Daftar</a></span>
  </div>
</footer>

<script>
  // Tanggal selesai tidak boleh sebelum tanggal mulai
  var f = document.getElementById('from'), t = document.getElementById('to');
  f.addEventListener('change', function () {
    if (f.value) { t.min = f.value; if (t.value && t.value < f.value) { t.value = f.value; } }
  });
  // Kirim filter tanggal hanya jika keduanya diisi
  document.getElementById('heroSearch').addEventListener('submit', function () {
    if (!(f.value && t.value)) { f.disabled = true; t.disabled = true; }
  });
</script>
</body>
</html>
