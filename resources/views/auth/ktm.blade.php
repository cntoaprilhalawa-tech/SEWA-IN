@extends('layouts.app')
@section('title', 'Verifikasi KTM')

@section('content')
<div class="card" style="max-width:520px">
  <div class="h2sm">Status verifikasi</div>
  <p style="margin:0 0 6px">
    <span class="badge {{ $user->verification_status === 'verified' ? 'ok' : ($user->verification_status === 'rejected' ? 'bad' : 'warn') }}">{{ $user->verificationLabel() }}</span>
  </p>
  <p class="muted" style="margin:0">NIM: {{ $user->nim }}</p>

  @if($user->verification_status === 'verified')
    <p style="margin-top:12px">Akunmu sudah terverifikasi. Kamu bisa menyewa maupun menyewakan barang.</p>
    <a href="{{ route($user->homeRoute()) }}" class="btn primary">Lanjut</a>

  @elseif($user->verification_status === 'pending')
    <p class="muted" style="margin-top:12px">KTM kamu sedang ditinjau admin. Kamu akan menerima notifikasi setelah selesai.</p>
    @if($user->ktm_image)
      <img src="{{ route('ktm.image', $user->id) }}" alt="KTM" style="max-width:100%;border-radius:10px;margin-top:8px">
    @endif

  @else
    @if($user->verification_status === 'rejected')
      <p class="error" style="margin-top:12px">Verifikasi ditolak.{{ $user->verification_note ? ' Alasan: '.$user->verification_note : '' }}</p>
    @endif
    <form action="{{ route('verification.ktm.upload') }}" method="POST" enctype="multipart/form-data" style="margin-top:12px">
      @csrf
      <label for="ktm_image">Foto KTM (JPG/PNG/WEBP, maks 2MB)</label>
      <input type="file" id="ktm_image" name="ktm_image" accept="image/*" required>
      @error('ktm_image') <div class="error">{{ $message }}</div> @enderror
      <button type="submit" class="btn primary" style="margin-top:12px">Kirim untuk Diverifikasi</button>
    </form>
  @endif
</div>
@endsection
