@extends('layouts.app')
@section('title', 'Akun')

@section('content')
@php $u = auth()->user(); @endphp

<div class="card person" style="margin-bottom:16px;max-width:720px">
  <img class="avatar lg" src="{{ $u->avatar_url }}" alt="">
  <div>
    <h2 style="margin:0">{{ $u->name }}</h2>
    <div class="muted">{{ $u->email }}@if($u->nim) · NIM {{ $u->nim }}@endif</div>
    <div style="margin-top:8px"><span class="badge {{ $u->isPemilik() ? 'warn' : 'ok' }}">Mode {{ $u->isPemilik() ? 'Pemilik' : 'Peminjam' }}</span></div>
  </div>
</div>

<div class="acclist">
  <a href="{{ route('profile.show') }}" class="card acc">
    <div><div class="h2sm">Profil Saya</div><div class="muted">Lihat profil publik, barang, dan ulasan yang kamu terima.</div></div>
    <span class="chev">›</span>
  </a>
  <a href="{{ route('profile.edit') }}" class="card acc">
    <div><div class="h2sm">Edit Profil</div><div class="muted">Ubah foto, nama, nomor HP, bio, dan lokasi default.</div></div>
    <span class="chev">›</span>
  </a>
  <a href="{{ route('profile.edit') }}#rekening" class="card acc">
    <div><div class="h2sm">Rekening &amp; QRIS</div><div class="muted">
      @if($u->bank_name && $u->account_number){{ $u->bank_name }} · {{ $u->account_number }}@else Belum diisi. Dibutuhkan jika kamu menyewakan barang.@endif
    </div></div>
    <span class="chev">›</span>
  </a>
  <a href="{{ route('verification.ktm') }}" class="card acc">
    <div>
      <div class="h2sm">Verifikasi KTM</div>
      <div class="muted">Dibutuhkan untuk menyewa dan menyewakan barang.</div>
    </div>
    <span class="badge {{ $u->isVerified() ? 'ok' : ($u->verification_status === 'rejected' ? 'bad' : 'warn') }}">{{ $u->verificationLabel() }}</span>
  </a>
  <a href="{{ route('notifications.index') }}" class="card acc">
    <div><div class="h2sm">Notifikasi</div><div class="muted">Riwayat pemberitahuan dari transaksi dan admin.</div></div>
    <span class="chev">›</span>
  </a>
</div>

<form method="POST" action="{{ route('logout') }}" style="margin-top:20px">
  @csrf
  <button type="submit" class="btn ghost">Keluar dari akun</button>
</form>
@endsection
