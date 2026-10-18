@extends('layouts.app')
@section('title', 'Pengaturan Profil')
@section('needs_map', '1')

@section('content')
@php $u = auth()->user(); @endphp
<form class="card" style="max-width:640px" method="POST" action="{{ route('profile.update') }}" enctype="multipart/form-data">
  @csrf
  @method('PUT')

  <div class="person">
    <img class="avatar lg" id="avatar-preview" src="{{ $u->avatar ? asset('storage/'.$u->avatar) : 'https://ui-avatars.com/api/?background=E8A33D&color=141d34&size=128&name='.urlencode($u->name) }}" alt="">
    <div style="flex:1">
      <label for="avatar" style="margin-top:0">Foto profil</label>
      <input id="avatar" name="avatar" type="file" accept="image/*">
      <div class="help">JPG, PNG, atau WEBP, maksimal 2MB.</div>
      @error('avatar') <div class="error">{{ $message }}</div> @enderror
    </div>
  </div>

  <label for="name">Nama lengkap</label>
  <input type="text" id="name" name="name" value="{{ old('name', $u->name) }}" required>
  @error('name') <div class="error">{{ $message }}</div> @enderror

  <label for="nim">NIM</label>
  <input type="text" id="nim" value="{{ $u->nim }}" readonly>

  <label for="phone">No. telepon / WhatsApp</label>
  <input type="text" id="phone" name="phone" value="{{ old('phone', $u->phone) }}">
  @error('phone') <div class="error">{{ $message }}</div> @enderror

  <label for="bio">Bio</label>
  <textarea id="bio" name="bio">{{ old('bio', $u->bio) }}</textarea>
  @error('bio') <div class="error">{{ $message }}</div> @enderror

  <div class="line"></div>
  <div class="h2sm" id="rekening">Rekening untuk menerima pembayaran</div>
  <div class="row">
    <div class="col">
      <label for="bank_name">Nama bank</label>
      <input type="text" id="bank_name" name="bank_name" value="{{ old('bank_name', $u->bank_name) }}" placeholder="Contoh: BRI">
      @error('bank_name') <div class="error">{{ $message }}</div> @enderror
    </div>
    <div class="col">
      <label for="account_number">Nomor rekening</label>
      <input type="text" id="account_number" name="account_number" value="{{ old('account_number', $u->account_number) }}">
      @error('account_number') <div class="error">{{ $message }}</div> @enderror
    </div>
  </div>

  <label for="qris_image">Kode QRIS (opsional)</label>
  @if($u->qris_image)
    <img src="{{ asset('storage/'.$u->qris_image) }}" alt="QRIS" style="width:180px;border-radius:12px;border:1px solid var(--line);display:block;margin-bottom:8px">
  @endif
  <input type="file" id="qris_image" name="qris_image" accept="image/*">
  <div class="help">Unggah gambar QRIS dari aplikasi bank atau e-wallet kamu (GoPay, OVO, DANA, m-banking). Penyewa akan memindainya saat membayar.</div>
  @error('qris_image') <div class="error">{{ $message }}</div> @enderror

  <div class="line"></div>
  <div class="h2sm">Lokasi default</div>
  <label for="address">Alamat</label>
  <input type="text" id="address" name="address" value="{{ old('address', $u->address) }}">
  <label>Tandai di peta</label>
  <x-map-picker :lat="$u->latitude" :lng="$u->longitude" />

  <div class="row" style="margin-top:18px;gap:8px">
    <button type="submit" class="btn primary">Simpan Perubahan</button>
    <a href="{{ route('profile.show') }}" class="btn ghost">Batal</a>
  </div>
</form>
@endsection

@push('scripts')
<script>
  document.getElementById('avatar').addEventListener('change', function (e) {
    var f = e.target.files[0]; if (!f) return;
    var r = new FileReader(); r.onload = function (ev) { document.getElementById('avatar-preview').src = ev.target.result; }; r.readAsDataURL(f);
  });
</script>
@endpush
