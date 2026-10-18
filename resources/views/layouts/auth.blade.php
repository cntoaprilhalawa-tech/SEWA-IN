<!DOCTYPE html>
<html lang="id">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<meta name="csrf-token" content="{{ csrf_token() }}">
<title>SEWA-IN — @yield('title', 'Masuk')</title>
<link rel="preconnect" href="https://fonts.googleapis.com">
<link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@600;700;800&family=Inter:wght@400;500;600&display=swap" rel="stylesheet">
<link rel="stylesheet" href="{{ asset('css/sewain.css') }}">
<link rel="stylesheet" href="{{ asset('css/auth.css') }}">
@include('partials.head')
</head>
<body class="auth2">

<aside class="auth2-side">
  <a href="{{ route('home') }}" class="auth2-brand" aria-label="SEWA-IN, ke beranda"><x-logo :height="34" /></a>

  <div class="auth2-hero">
    <h2>@yield('side_title', 'Butuh barang sebentar? Pinjam dari teman kampus.')</h2>
    <p>@yield('side_text', 'Tenda, kamera, toga, proyektor. Sewa harian dari mahasiswa yang KTM-nya sudah diverifikasi.')</p>
  </div>

  <div class="tags" aria-hidden="true">
    <div class="tag" style="--r:-5deg;--d:.05s;left:0;top:44px">
      <svg class="tag-string" viewBox="0 0 28 48"><path d="M14 48 C14 30 4 22 8 6 M14 48 C14 30 24 22 20 6" fill="none" stroke="#d9c7a0" stroke-width="2" stroke-linecap="round"/></svg>
      <span class="tag-hole"></span>
      <div class="tag-body">
        <p class="tag-name">Tenda Dome 4 Orang</p>
        <p class="tag-price">Rp25.000 <small>/hari</small></p>
        <span class="tag-state ok">Tersedia</span>
      </div>
    </div>
    <div class="tag" style="--r:4deg;--d:.18s;left:206px;top:0">
      <svg class="tag-string" viewBox="0 0 28 48"><path d="M14 48 C14 30 4 22 8 6 M14 48 C14 30 24 22 20 6" fill="none" stroke="#d9c7a0" stroke-width="2" stroke-linecap="round"/></svg>
      <span class="tag-hole"></span>
      <div class="tag-body">
        <p class="tag-name">Kamera Mirrorless</p>
        <p class="tag-price">Rp80.000 <small>/hari</small></p>
        <span class="tag-state ok">Tersedia</span>
      </div>
    </div>
    <div class="tag" style="--r:-2deg;--d:.31s;left:84px;top:168px">
      <svg class="tag-string" viewBox="0 0 28 48"><path d="M14 48 C14 30 4 22 8 6 M14 48 C14 30 24 22 20 6" fill="none" stroke="#d9c7a0" stroke-width="2" stroke-linecap="round"/></svg>
      <span class="tag-hole"></span>
      <div class="tag-body">
        <p class="tag-name">Toga Wisuda</p>
        <p class="tag-price">Rp35.000 <small>/hari</small></p>
        <span class="tag-state busy">Sedang disewa</span>
      </div>
    </div>
  </div>

  <ol class="steps">
    <li><b>1</b><span>Cari barang yang kamu butuhkan</span></li>
    <li><b>2</b><span>Ajukan sewa dan tunggu persetujuan</span></li>
    <li><b>3</b><span>Ambil barang, lalu kembalikan tepat waktu</span></li>
  </ol>
</aside>

<main class="auth2-main">
  <div class="auth2-top"><x-theme-toggle /><a href="{{ route('home') }}">← Kembali ke beranda</a></div>
  <div class="auth2-col">
    @if(session('success'))<div class="alert">{{ session('success') }}</div>@endif
    @if(session('status'))<div class="alert">{{ session('status') }}</div>@endif
    @if(session('error'))<div class="alert bad">{{ session('error') }}</div>@endif
    @yield('content')
  </div>
</main>

<script>
  document.querySelectorAll('[data-toggle-pass]').forEach(function (btn) {
    btn.addEventListener('click', function () {
      var input = document.getElementById(btn.getAttribute('data-toggle-pass'));
      var show = input.type === 'password';
      input.type = show ? 'text' : 'password';
      btn.classList.toggle('on', show);
      btn.setAttribute('aria-label', show ? 'Sembunyikan kata sandi' : 'Tampilkan kata sandi');
    });
  });
  document.querySelectorAll('form[data-busy-text]').forEach(function (form) {
    var btn = form.querySelector('button[type=submit]'), label = btn.textContent;
    form.addEventListener('submit', function (e) {
      // tunggu handler lain (mis. verifikasi "bukan robot"); jika submit dibatalkan, tombol tidak dikunci
      setTimeout(function () {
        if (e.defaultPrevented) { return; }
        btn.disabled = true; btn.setAttribute('aria-busy', 'true'); btn.textContent = form.getAttribute('data-busy-text');
      }, 0);
    });
    window.addEventListener('pageshow', function () { btn.disabled = false; btn.removeAttribute('aria-busy'); btn.textContent = label; });
  });
</script>
<script src="{{ asset('js/human.js') }}" defer></script>
</body>
</html>
