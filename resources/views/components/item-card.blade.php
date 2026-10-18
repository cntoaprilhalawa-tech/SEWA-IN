@php
    $cover = isset($item->images) && $item->images->count() > 0 ? $item->images->first()->image_path : null;
@endphp
<a href="{{ route('items.show', $item->id) }}" class="itemcard">
  <div class="thumb">
    @if($cover)
      <img src="{{ asset('storage/'.$cover) }}" alt="{{ $item->name }}" loading="lazy">
    @else
      🏷️
    @endif
  </div>
  <div class="body">
    <p class="nm">{{ $item->name }}</p>
    <div class="price">Rp{{ number_format($item->price_per_day, 0, ',', '.') }}/hari</div>
    <div class="meta">{{ $item->category->name ?? 'Tanpa kategori' }}@if($item->address) · {{ \Illuminate\Support\Str::limit($item->address, 24) }}@endif</div>
    <span class="badge {{ $item->is_available ? 'ok' : 'bad' }}" style="margin-top:8px">{{ $item->is_available ? 'Tersedia' : 'Tidak tersedia' }}</span>
  </div>
</a>
