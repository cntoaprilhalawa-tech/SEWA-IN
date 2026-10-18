@extends('layouts.auth')
@section('title', 'Daftar')
@section('side_title', 'Satu akun, bisa menyewa sekaligus menyewakan.')
@section('side_text', 'Daftar dengan NIM dan foto KTM. Setelah admin memverifikasi, kamu bisa berpindah antara mode Peminjam dan Pemilik kapan saja.')

@section('content')
<h1>Buat akun</h1>
<p class="lead">Isi data diri kamu. Verifikasi KTM biasanya diproses admin dalam waktu singkat.</p>

<form action="{{ route('register') }}" method="POST" enctype="multipart/form-data" data-busy-text="Membuat akun…" novalidate>
  @csrf

  <fieldset>
    <span class="legend">Mulai sebagai</span>
    <div class="choices">
      <label class="choice">
        <input type="radio" name="role" value="peminjam" {{ old('role', request('role') === 'pemilik' ? 'pemilik' : 'peminjam') === 'peminjam' ? 'checked' : '' }}>
        <span class="box"><b>Peminjam</b><span>Menyewa barang dari orang lain</span></span>
      </label>
      <label class="choice">
        <input type="radio" name="role" value="pemilik" {{ old('role', request('role')) === 'pemilik' ? 'checked' : '' }}>
        <span class="box"><b>Pemilik barang</b><span>Menyewakan barang milikmu</span></span>
      </label>
    </div>
    <p class="hint">Ini hanya peran awal. Kamu bisa beralih kapan saja dari bagian atas halaman.</p>
    @error('role')
      <p class="err" role="alert"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" aria-hidden="true"><circle cx="12" cy="12" r="9"/><path d="M12 8v5M12 16.5v.01"/></svg><span>{{ $message }}</span></p>
    @enderror
  </fieldset>

  <div class="field @error('name') has-error @enderror">
    <label for="name">Nama lengkap</label>
    <div class="control">
      <svg class="ico" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><circle cx="12" cy="8" r="4"/><path d="M4 20c1.2-3.6 4.2-5 8-5s6.8 1.4 8 5"/></svg>
      <input id="name" name="name" type="text" autocomplete="name" placeholder="Sesuai KTM" value="{{ old('name') }}" required autofocus>
    </div>
    @error('name')
      <p class="err" role="alert"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" aria-hidden="true"><circle cx="12" cy="12" r="9"/><path d="M12 8v5M12 16.5v.01"/></svg><span>{{ $message }}</span></p>
    @enderror
  </div>

  <div class="field @error('nim') has-error @enderror">
    <label for="nim">NIM</label>
    <div class="control">
      <svg class="ico" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><rect x="3" y="5" width="18" height="14" rx="2.5"/><circle cx="9" cy="11" r="2"/><path d="M6.5 16c.5-1.5 1.5-2 2.5-2s2 .5 2.5 2M14 10h4M14 13h3"/></svg>
      <input id="nim" name="nim" type="text" inputmode="numeric" placeholder="Nomor induk mahasiswa" value="{{ old('nim') }}" required>
    </div>
    @error('nim')
      <p class="err" role="alert"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" aria-hidden="true"><circle cx="12" cy="12" r="9"/><path d="M12 8v5M12 16.5v.01"/></svg><span>{{ $message }}</span></p>
    @enderror
  </div>

  <div class="field @error('ktm_image') has-error @enderror">
    <label for="ktm_image">Foto KTM</label>
    <input id="ktm_image" name="ktm_image" type="file" accept="image/*" required>
    <p class="hint">Dipakai admin untuk verifikasi. Format JPG, PNG, atau WEBP, maksimal 2MB.</p>
    @error('ktm_image')
      <p class="err" role="alert"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" aria-hidden="true"><circle cx="12" cy="12" r="9"/><path d="M12 8v5M12 16.5v.01"/></svg><span>{{ $message }}</span></p>
    @enderror
  </div>

  <div class="field @error('email') has-error @enderror">
    <label for="email">Email</label>
    <div class="control">
      <svg class="ico" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><rect x="3" y="5" width="18" height="14" rx="2.5"/><path d="m4 7 8 6 8-6"/></svg>
      <input id="email" name="email" type="email" autocomplete="email" inputmode="email" placeholder="nama@email.com" value="{{ old('email') }}" required>
    </div>
    @error('email')
      <p class="err" role="alert"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" aria-hidden="true"><circle cx="12" cy="12" r="9"/><path d="M12 8v5M12 16.5v.01"/></svg><span>{{ $message }}</span></p>
    @enderror
  </div>

  <div class="field @error('password') has-error @enderror">
    <label for="password">Kata sandi</label>
    <div class="control hasbtn">
      <svg class="ico" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><rect x="4.5" y="10.5" width="15" height="10" rx="2.5"/><path d="M8 10.5V8a4 4 0 0 1 8 0v2.5"/></svg>
      <input id="password" name="password" type="password" autocomplete="new-password" placeholder="Buat kata sandi" required>
      <button type="button" class="eye" data-toggle-pass="password" aria-label="Tampilkan kata sandi">
        <svg class="show" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M2 12s3.6-7 10-7 10 7 10 7-3.6 7-10 7S2 12 2 12Z"/><circle cx="12" cy="12" r="3"/></svg>
        <svg class="off" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M3 3l18 18M10.6 5.1A9.7 9.7 0 0 1 12 5c6.4 0 10 7 10 7a17 17 0 0 1-3.2 4M6.5 6.6C3.6 8.4 2 12 2 12s3.6 7 10 7c1.6 0 3-.4 4.3-1M9.9 9.9a3 3 0 0 0 4.2 4.2"/></svg>
      </button>
    </div>
    @error('password')
      <p class="err" role="alert"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" aria-hidden="true"><circle cx="12" cy="12" r="9"/><path d="M12 8v5M12 16.5v.01"/></svg><span>{{ $message }}</span></p>
    @enderror
  </div>

  <div class="field">
    <label for="password_confirmation">Ulangi kata sandi</label>
    <div class="control hasbtn">
      <svg class="ico" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><rect x="4.5" y="10.5" width="15" height="10" rx="2.5"/><path d="M8 10.5V8a4 4 0 0 1 8 0v2.5"/></svg>
      <input id="password_confirmation" name="password_confirmation" type="password" autocomplete="new-password" placeholder="Ketik ulang kata sandi" required>
      <button type="button" class="eye" data-toggle-pass="password_confirmation" aria-label="Tampilkan kata sandi">
        <svg class="show" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M2 12s3.6-7 10-7 10 7 10 7-3.6 7-10 7S2 12 2 12Z"/><circle cx="12" cy="12" r="3"/></svg>
        <svg class="off" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M3 3l18 18M10.6 5.1A9.7 9.7 0 0 1 12 5c6.4 0 10 7 10 7a17 17 0 0 1-3.2 4M6.5 6.6C3.6 8.4 2 12 2 12s3.6 7 10 7c1.6 0 3-.4 4.3-1M9.9 9.9a3 3 0 0 0 4.2 4.2"/></svg>
      </button>
    </div>
  </div>

  <x-human-check />

  <button type="submit" class="submit">Buat akun</button>
</form>

<div class="switch">
  Sudah punya akun?<br>
  <a href="{{ route('login') }}">Masuk</a>
</div>
@endsection
