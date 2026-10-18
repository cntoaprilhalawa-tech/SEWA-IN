@extends('layouts.auth')
@section('title', 'Masuk')

@section('content')
<h1>Selamat Datang <span class="wave" aria-hidden="true">👋</span></h1>
<p class="lead">Masuk ke SEWA-IN untuk mulai menyewa atau menyewakan barang dengan mudah di lingkungan kampusmu.</p>

<form action="{{ route('login') }}" method="POST" data-busy-text="Memproses…" novalidate>
  @csrf

  <div class="field @error('email') has-error @enderror">
    <label for="email">Email</label>
    <div class="control">
      <svg class="ico" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><rect x="3" y="5" width="18" height="14" rx="2.5"/><path d="m4 7 8 6 8-6"/></svg>
      <input id="email" name="email" type="email" autocomplete="email" inputmode="email" placeholder="nama@email.com" value="{{ old('email') }}" required autofocus>
    </div>
    @error('email')
      <p class="err" role="alert"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" aria-hidden="true"><circle cx="12" cy="12" r="9"/><path d="M12 8v5M12 16.5v.01"/></svg><span>{{ $message }}</span></p>
    @enderror
  </div>

  <div class="field @error('password') has-error @enderror">
    <label for="password">Kata sandi</label>
    <div class="control hasbtn">
      <svg class="ico" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><rect x="4.5" y="10.5" width="15" height="10" rx="2.5"/><path d="M8 10.5V8a4 4 0 0 1 8 0v2.5"/></svg>
      <input id="password" name="password" type="password" autocomplete="current-password" placeholder="Masukkan kata sandi" required>
      <button type="button" class="eye" data-toggle-pass="password" aria-label="Tampilkan kata sandi">
        <svg class="show" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M2 12s3.6-7 10-7 10 7 10 7-3.6 7-10 7S2 12 2 12Z"/><circle cx="12" cy="12" r="3"/></svg>
        <svg class="off" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M3 3l18 18M10.6 5.1A9.7 9.7 0 0 1 12 5c6.4 0 10 7 10 7a17 17 0 0 1-3.2 4M6.5 6.6C3.6 8.4 2 12 2 12s3.6 7 10 7c1.6 0 3-.4 4.3-1M9.9 9.9a3 3 0 0 0 4.2 4.2"/></svg>
      </button>
    </div>
    @error('password')
      <p class="err" role="alert"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" aria-hidden="true"><circle cx="12" cy="12" r="9"/><path d="M12 8v5M12 16.5v.01"/></svg><span>{{ $message }}</span></p>
    @enderror
  </div>

  <label class="check"><input type="checkbox" name="remember" value="1"> Ingat saya di perangkat ini</label>

  <x-human-check />

  <button type="submit" class="submit">Masuk</button>
</form>

<div class="switch">
  Baru di SEWA-IN? Daftar dengan NIM dan foto KTM untuk mulai menyewa atau menyewakan barang.<br>
  <a href="{{ route('register') }}">Buat akun</a>
</div>
@endsection
