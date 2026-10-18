<!DOCTYPE html>
<html lang="id">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<meta name="csrf-token" content="{{ csrf_token() }}">
<title>SEWA-IN — @yield('title', 'Beranda')</title>
<link rel="preconnect" href="https://fonts.googleapis.com">
<link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@600;700;800&family=Inter:wght@400;500;600&display=swap" rel="stylesheet">
@hasSection('needs_map')
<link rel="stylesheet" href="https://unpkg.com/leaflet@1.9.4/dist/leaflet.css">
@endif
<link rel="stylesheet" href="{{ asset('css/sewain.css') }}">
@include('partials.head')
</head>
<body>
<div class="app">
  <aside class="side" id="sidebar">
    <a href="{{ route('home') }}" class="brand" aria-label="SEWA-IN, ke beranda"><x-logo :height="26" /></a>

    @auth
      @php
        $me = auth()->user();
        $unread = $me->unreadNotifications()->count();
      @endphp

      @if($me->isAdmin())
        {{-- ===== Aktor: ADMIN ===== --}}
        <div class="navgroup">Admin</div>
        <a href="{{ route('admin.moderation') }}" class="navitem {{ request()->routeIs('admin.moderation', 'admin.complaints.*', 'admin.reviews.*') ? 'active' : '' }}">Keluhan &amp; Moderasi</a>
        <a href="{{ route('admin.verifications') }}" class="navitem {{ request()->routeIs('admin.verifications*') ? 'active' : '' }}">Verifikasi KTM</a>
        <a href="{{ route('admin.users') }}" class="navitem {{ request()->routeIs('admin.users*') ? 'active' : '' }}">Pengguna</a>
        <a href="{{ route('admin.categories') }}" class="navitem {{ request()->routeIs('admin.categories*') ? 'active' : '' }}">Kategori</a>
        <a href="{{ route('admin.reports') }}" class="navitem {{ request()->routeIs('admin.reports*') ? 'active' : '' }}">Laporan</a>

      @elseif($me->isPemilik())
        {{-- ===== Mode: PEMILIK BARANG ===== --}}
        @php $waitingPay = \App\Models\Booking::where('owner_id', $me->id)->where('payment_status', 'waiting')->count(); @endphp
        <div class="navgroup">Pemilik</div>
        <a href="{{ route('dashboard') }}" class="navitem {{ request()->routeIs('dashboard') ? 'active' : '' }}">Dashboard</a>
        <a href="{{ route('items.create') }}" class="navitem {{ request()->routeIs('items.create', 'items.edit') ? 'active' : '' }}">Sewakan Barang</a>
        <a href="{{ route('bookings.index') }}" class="navitem {{ request()->routeIs('bookings.*') ? 'active' : '' }}">Riwayat Transaksi</a>
        <a href="{{ route('payments.index') }}" class="navitem {{ request()->routeIs('payments.*') ? 'active' : '' }}">Konfirmasi Pembayaran @if($waitingPay > 0)<span class="badge bad">{{ $waitingPay }}</span>@endif</a>
        <a href="{{ route('items.index') }}" class="navitem {{ request()->routeIs('items.index', 'items.show') ? 'active' : '' }}">Cari Barang</a>
        <a href="{{ route('complaints.create') }}" class="navitem {{ request()->routeIs('complaints.*') ? 'active' : '' }}">Ajukan Keluhan</a>

      @else
        {{-- ===== Mode: PEMINJAM ===== --}}
        <div class="navgroup">Peminjam</div>
        <a href="{{ route('dashboard') }}" class="navitem {{ request()->routeIs('dashboard') ? 'active' : '' }}">Dashboard</a>
        <a href="{{ route('items.index') }}" class="navitem {{ request()->routeIs('items.index', 'items.show') ? 'active' : '' }}">Cari Barang</a>
        <a href="{{ route('bookings.index') }}" class="navitem {{ request()->routeIs('bookings.*') ? 'active' : '' }}">Riwayat Transaksi</a>
        <a href="{{ route('complaints.create') }}" class="navitem {{ request()->routeIs('complaints.*') ? 'active' : '' }}">Ajukan Keluhan</a>
      @endif

    @else
      <div class="navgroup">Menu</div>
      <a href="{{ route('home') }}" class="navitem">Beranda</a>
      <a href="{{ route('items.index') }}" class="navitem {{ request()->routeIs('items.index', 'items.show') ? 'active' : '' }}">Cari Barang</a>
      <div class="navgroup">Akun</div>
      <a href="{{ route('login') }}" class="navitem {{ request()->routeIs('login') ? 'active' : '' }}">Masuk</a>
      <a href="{{ route('register') }}" class="navitem {{ request()->routeIs('register') ? 'active' : '' }}">Daftar</a>
    @endauth

    @auth
      {{-- Blok akun di dasar sidebar: nama + ikon pengaturan (non-admin) + keluar --}}
      <div class="sidefoot">
        <div class="sidefoot-user">
          <img class="sf-avatar" src="{{ $me->avatar_url }}" alt="">
          <div class="sf-name">
            <b>{{ $me->name }}</b>
            <span>{{ $me->isAdmin() ? 'Admin' : ($me->isPemilik() ? 'Pemilik' : 'Peminjam') }}</span>
          </div>
          @unless($me->isAdmin())
            <a href="{{ route('account') }}" class="sf-gear {{ request()->routeIs('account', 'profile.*', 'verification.ktm*') ? 'active' : '' }}" title="Pengaturan akun" aria-label="Pengaturan akun">
              <svg viewBox="0 0 24 24" width="20" height="20" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><circle cx="12" cy="12" r="3"/><path d="M19.4 15a1.65 1.65 0 0 0 .33 1.82l.06.06a2 2 0 1 1-2.83 2.83l-.06-.06a1.65 1.65 0 0 0-1.82-.33 1.65 1.65 0 0 0-1 1.51V21a2 2 0 1 1-4 0v-.09a1.65 1.65 0 0 0-1-1.51 1.65 1.65 0 0 0-1.82.33l-.06.06a2 2 0 1 1-2.83-2.83l.06-.06a1.65 1.65 0 0 0 .33-1.82 1.65 1.65 0 0 0-1.51-1H3a2 2 0 1 1 0-4h.09a1.65 1.65 0 0 0 1.51-1 1.65 1.65 0 0 0-.33-1.82l-.06-.06a2 2 0 1 1 2.83-2.83l.06.06a1.65 1.65 0 0 0 1.82.33h0a1.65 1.65 0 0 0 1-1.51V3a2 2 0 1 1 4 0v.09a1.65 1.65 0 0 0 1 1.51h0a1.65 1.65 0 0 0 1.82-.33l.06-.06a2 2 0 1 1 2.83 2.83l-.06.06a1.65 1.65 0 0 0-.33 1.82v0a1.65 1.65 0 0 0 1.51 1H21a2 2 0 1 1 0 4h-.09a1.65 1.65 0 0 0-1.51 1z"/></svg>
            </a>
          @endunless
        </div>
        <form method="POST" action="{{ route('logout') }}" class="navform">
          @csrf
          <button type="submit" class="sf-logout">Keluar</button>
        </form>
      </div>
    @endauth
  </aside>
  <div class="scrim" id="scrim"></div>

  <main class="main">
    <div class="topbar">
      <div class="topleft">
        <button type="button" class="menubtn" id="menubtn" aria-label="Buka menu" aria-controls="sidebar" aria-expanded="false">☰</button>
        <h1>@yield('title', 'Beranda')</h1>
      </div>
      <div class="who">
        <x-theme-toggle />
        @auth
          <a href="{{ route('notifications.index') }}" class="bell {{ request()->routeIs('notifications.*') ? 'on' : '' }}" title="Notifikasi" aria-label="Notifikasi{{ $unread > 0 ? ' ('.$unread.' baru)' : '' }}">
            <svg viewBox="0 0 24 24" width="20" height="20" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M18 8a6 6 0 0 0-12 0c0 7-3 9-3 9h18s-3-2-3-9"/><path d="M13.7 21a2 2 0 0 1-3.4 0"/></svg>
            @if($unread > 0)<span class="bell-n">{{ $unread > 9 ? '9+' : $unread }}</span>@endif
          </a>
          @if(auth()->user()->isAdmin())
            <span class="badge bad">Admin</span>
          @else
            {{-- Pengalih mode: yang aktif ditandai, yang lain adalah tombol untuk berpindah --}}
            <form method="POST" action="{{ route('role.switch') }}" class="modeswitch" title="Beralih mode">
              @csrf
              @if(auth()->user()->isPemilik())
                <button type="submit" class="seg">Peminjam</button><span class="seg on">Pemilik</span>
              @else
                <span class="seg on">Peminjam</span><button type="submit" class="seg">Pemilik</button>
              @endif
            </form>
          @endif
          <span class="muted uname">{{ auth()->user()->name }}</span>
        @else
          <span class="muted">Tamu</span>
        @endauth
      </div>
    </div>
    <section class="screen">
      @auth
        @if(!auth()->user()->isAdmin() && !auth()->user()->isVerified() && !request()->routeIs('verification.ktm*'))
          <div class="card flash warn">
            KTM kamu belum terverifikasi ({{ strtolower(auth()->user()->verificationLabel()) }}), jadi sebagian fitur belum bisa dipakai.
            <a href="{{ route('verification.ktm') }}"><b>Lihat status verifikasi</b></a>
          </div>
        @endif
      @endauth
      @if(session('success'))
        <div class="card flash">{{ session('success') }}</div>
      @endif
      @if(session('error'))
        <div class="card flash bad">{{ session('error') }}</div>
      @endif
      @if(session('status'))
        <div class="card flash">{{ session('status') }}</div>
      @endif
      @yield('content')
    </section>
  </main>
</div>
@hasSection('needs_map')
<script src="https://unpkg.com/leaflet@1.9.4/dist/leaflet.js"></script>
@endif
<script>
  (function () {
    var btn = document.getElementById('menubtn'), scrim = document.getElementById('scrim');
    function toggle(open) {
      document.body.classList.toggle('nav-open', open);
      btn.setAttribute('aria-expanded', open ? 'true' : 'false');
    }
    btn.addEventListener('click', function () { toggle(!document.body.classList.contains('nav-open')); });
    scrim.addEventListener('click', function () { toggle(false); });
  })();
</script>
@stack('scripts')
</body>
</html>
