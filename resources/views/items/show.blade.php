@extends('layouts.app')
@section('title', $item->name)
@section('needs_map', '1')

@section('content')
@php
    $images = $item->images->map(fn($i) => asset('storage/'.$i->image_path))->values();
    $isOwner = auth()->check() && auth()->id() === $item->user_id;
    $owner = $item->user;
@endphp
<div class="split">
  <div>
    <div class="gallery">
      <div class="thumb">
        @if($images->count())
          <img id="mainImg" src="{{ $images->first() }}" alt="{{ $item->name }}">
        @else
          🏷️
        @endif
      </div>
      @if($images->count() > 1)
        <div class="thumbs">
          @foreach($images as $i => $src)
            <button type="button" class="{{ $i === 0 ? 'on' : '' }}" data-src="{{ $src }}"><img src="{{ $src }}" alt=""></button>
          @endforeach
        </div>
      @endif
    </div>

    <h2 style="margin:16px 0 4px">{{ $item->name }}</h2>
    <div class="muted">
      {{ $item->category->name ?? 'Tanpa kategori' }} · Milik <a href="{{ route('user.profile', $owner->id) }}">{{ $owner->name }}</a>
      · ⭐ {{ number_format($item->average_rating ?? 0, 1) }} ({{ $item->reviews->count() }} ulasan)
    </div>
    <div class="line"></div>

    <div class="h2sm">Deskripsi</div>
    <p class="muted" style="white-space:pre-line">{{ $item->description }}</p>

    <div class="h2sm">Ketersediaan</div>
    <div class="card" style="margin-bottom:16px">
      <div class="cardhead">
        <button type="button" class="btn ghost sm" id="calPrev">‹</button>
        <b id="calTitle"></b>
        <button type="button" class="btn ghost sm" id="calNext">›</button>
      </div>
      <div class="cal" id="calGrid"></div>
      <p class="muted" style="margin:10px 0 0"><span class="badge ok">Tersedia</span> <span class="badge bad">Disewa</span></p>
    </div>

    @if($item->latitude && $item->longitude)
      <div class="h2sm">Lokasi Pengambilan</div>
      @if($item->address)<div class="muted">{{ $item->address }}</div>@endif
      <div id="item-map" class="map"></div>
    @endif

    <div class="line"></div>
    <div class="h2sm">Ulasan ({{ $item->reviews->count() }})</div>
    @forelse($item->reviews as $review)
      <div class="review">
        <img class="avatar" src="{{ $review->reviewer->avatar ? asset('storage/'.$review->reviewer->avatar) : 'https://ui-avatars.com/api/?background=E8A33D&color=141d34&name='.urlencode($review->reviewer->name) }}" alt="">
        <div>
          <b>{{ $review->reviewer->name }}</b>
          <div><x-star-rating :rating="$review->rating" /> <span class="muted">{{ $review->created_at->diffForHumans() }}</span></div>
          <p class="muted" style="margin:6px 0 0">{{ $review->comment }}</p>
        </div>
      </div>
    @empty
      <p class="muted">Belum ada ulasan untuk barang ini.</p>
    @endforelse
  </div>

  <div class="card" style="align-self:start">
    <div class="stat"><span>Harga sewa</span><b>Rp{{ number_format($item->price_per_day, 0, ',', '.') }} / hari</b></div>
    <div class="stat"><span>Status</span>
      <span class="badge {{ $item->is_available ? 'ok' : 'bad' }}">{{ $item->is_available ? 'Tersedia' : 'Tidak tersedia' }}</span>
    </div>
    <div class="stat"><span>Kondisi</span><b>{{ ucfirst($item->condition) }}</b></div>
    @if($item->deposit > 0)
      <div class="stat"><span>Deposit dibutuhkan</span><b>Rp{{ number_format($item->deposit, 0, ',', '.') }}</b></div>
    @endif
    <div class="line"></div>
    <p class="muted" style="margin:0 0 12px">
      @if($item->deposit > 0) Deposit dikembalikan penuh jika barang kembali dalam kondisi baik.
      @else Barang ini tidak memerlukan deposit. @endif
    </p>

    @auth
      @if($isOwner)
        <a href="{{ route('items.edit', $item->id) }}" class="btn primary block">Edit Barang</a>
        <form action="{{ route('items.destroy', $item->id) }}" method="POST" onsubmit="return confirm('Hapus barang ini?')" style="margin-top:8px">
          @csrf
          @method('DELETE')
          <button type="submit" class="btn ghost block">Hapus</button>
        </form>
      @elseif(auth()->user()->isPeminjam())
        @if($item->is_available)
          <a href="{{ route('bookings.create', $item->id) }}" class="btn primary block">Ajukan Booking</a>
        @else
          <button type="button" class="btn ghost block" disabled>Barang Tidak Tersedia</button>
        @endif
      @elseif(auth()->user()->isPemilik())
        <p class="muted" style="margin:0 0 10px">Kamu sedang dalam mode Pemilik. Beralih ke mode Peminjam untuk menyewa barang ini.</p>
        <form method="POST" action="{{ route('role.switch') }}">
          @csrf
          <input type="hidden" name="next" value="{{ request()->getRequestUri() }}">
          <button type="submit" class="btn primary block">Beralih ke Mode Peminjam</button>
        </form>
      @else
        <p class="muted" style="margin:0">Akun admin tidak dapat menyewa barang.</p>
      @endif
    @else
      <a href="{{ route('login') }}" class="btn primary block">Masuk untuk Menyewa</a>
    @endauth
  </div>
</div>
@endsection

@push('scripts')
<script>
  document.querySelectorAll('.gallery .thumbs button').forEach(function (b) {
    b.addEventListener('click', function () {
      document.getElementById('mainImg').src = b.dataset.src;
      document.querySelectorAll('.gallery .thumbs button').forEach(function (x) { x.classList.remove('on'); });
      b.classList.add('on');
    });
  });

  var booked = @json($bookedDates ?? []);
  var months = ['Januari','Februari','Maret','April','Mei','Juni','Juli','Agustus','September','Oktober','November','Desember'];
  var now = new Date(), cm = now.getMonth(), cy = now.getFullYear();
  function pad(n) { return String(n).padStart(2, '0'); }
  function drawCal() {
    document.getElementById('calTitle').textContent = months[cm] + ' ' + cy;
    var html = ['Min','Sen','Sel','Rab','Kam','Jum','Sab'].map(function (d) { return '<div class="h">' + d + '</div>'; }).join('');
    var first = new Date(cy, cm, 1).getDay(), days = new Date(cy, cm + 1, 0).getDate();
    for (var i = 0; i < first; i++) html += '<div></div>';
    var today = new Date(); today.setHours(0, 0, 0, 0);
    for (var d = 1; d <= days; d++) {
      var key = cy + '-' + pad(cm + 1) + '-' + pad(d);
      var cls = new Date(cy, cm, d) < today ? 'past' : (booked.indexOf(key) > -1 ? 'off' : '');
      html += '<div class="d ' + cls + '">' + d + '</div>';
    }
    document.getElementById('calGrid').innerHTML = html;
  }
  document.getElementById('calNext').onclick = function () { cm++; if (cm > 11) { cm = 0; cy++; } drawCal(); };
  document.getElementById('calPrev').onclick = function () {
    if (cy === now.getFullYear() && cm === now.getMonth()) return;
    cm--; if (cm < 0) { cm = 11; cy--; } drawCal();
  };
  drawCal();

  @if($item->latitude && $item->longitude)
    var map = L.map('item-map').setView([{{ $item->latitude }}, {{ $item->longitude }}], 15);
    L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', { attribution: '&copy; OpenStreetMap' }).addTo(map);
    L.marker([{{ $item->latitude }}, {{ $item->longitude }}]).addTo(map);
  @endif
</script>
@endpush
