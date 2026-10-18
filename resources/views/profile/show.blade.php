@extends('layouts.app')
@section('title', 'Profil '.$user->name)
@section('needs_map', '1')

@section('content')
@php $avatar = $user->avatar ? asset('storage/'.$user->avatar) : 'https://ui-avatars.com/api/?background=E8A33D&color=141d34&size=128&name='.urlencode($user->name); @endphp
<div class="split">
  <div>
    <div class="card" style="margin-bottom:16px">
      <div class="person">
        <img class="avatar lg" src="{{ $avatar }}" alt="">
        <div>
          <h2 style="margin:0">{{ $user->name }}</h2>
          <div class="muted">NIM: {{ $user->nim ?? '-' }}</div>
          <div><x-star-rating :rating="$avgRating ?? 0" :count="$reviews->count()" /></div>
          <div class="muted">Bergabung sejak {{ $user->created_at->translatedFormat('F Y') }}</div>
        </div>
      </div>
      @if(auth()->id() === $user->id)
        <a href="{{ route('profile.edit') }}" class="btn ghost" style="margin-top:14px">Edit Profil</a>
      @endif
    </div>

    <div class="h2sm">Barang Disewakan ({{ $items->count() }})</div>
    @if($items->count())
      <div class="grid tight" style="margin-bottom:20px">
        @foreach($items as $item)<x-item-card :item="$item" />@endforeach
      </div>
    @else
      <p class="muted">Belum ada barang yang disewakan.</p>
    @endif

    <div class="h2sm">Ulasan ({{ $reviews->count() }})</div>
    @forelse($reviews as $review)
      <div class="review">
        <img class="avatar" src="{{ $review->reviewer->avatar ? asset('storage/'.$review->reviewer->avatar) : 'https://ui-avatars.com/api/?background=E8A33D&color=141d34&name='.urlencode($review->reviewer->name) }}" alt="">
        <div>
          <b>{{ $review->reviewer->name }}</b>
          <div><x-star-rating :rating="$review->rating" /> <span class="muted">{{ $review->created_at->diffForHumans() }}</span></div>
          <p class="muted" style="margin:6px 0 0">{{ $review->comment }}</p>
        </div>
      </div>
    @empty
      <p class="muted">Belum ada ulasan.</p>
    @endforelse
  </div>

  <div class="card" style="align-self:start">
    <div class="h2sm">Kontak</div>
    <div class="stat"><span>Email</span><b>{{ $user->email }}</b></div>
    <div class="stat"><span>Telepon</span><b>{{ $user->phone ?? 'Belum diatur' }}</b></div>
    <div class="line"></div>
    <div class="h2sm">Bio</div>
    <p class="muted" style="margin:0">{{ $user->bio ?? 'Belum ada bio.' }}</p>
    @if($user->latitude && $user->longitude)
      <div class="line"></div>
      <div class="h2sm">Lokasi</div>
      @if($user->address)<div class="muted">{{ $user->address }}</div>@endif
      <div id="user-map" class="map"></div>
    @endif
  </div>
</div>
@endsection

@if($user->latitude && $user->longitude)
@push('scripts')
<script>
  var map = L.map('user-map').setView([{{ $user->latitude }}, {{ $user->longitude }}], 14);
  L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', { attribution: '&copy; OpenStreetMap' }).addTo(map);
  L.marker([{{ $user->latitude }}, {{ $user->longitude }}]).addTo(map);
</script>
@endpush
@endif
