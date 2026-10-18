@extends('layouts.app')
@section('title', 'Cari Barang')
@section('needs_map', '1')

@section('content')
<form method="GET" action="{{ route('items.index') }}" class="searchbar">
  <input type="text" name="q" value="{{ request('q') }}" placeholder="Cari nama barang…">
  <select name="category">
    <option value="">Semua kategori</option>
    @foreach($categories ?? [] as $category)
      <option value="{{ $category->id }}" @selected(request('category') == $category->id)>{{ $category->name }}</option>
    @endforeach
  </select>
  <select name="condition">
    <option value="">Semua kondisi</option>
    @foreach(['baru' => 'Baru', 'baik' => 'Baik', 'cukup' => 'Cukup'] as $v => $l)
      <option value="{{ $v }}" @selected(request('condition') == $v)>{{ $l }}</option>
    @endforeach
  </select>
  <label class="field"><span>Tersedia dari</span>
    <input type="date" name="available_from" value="{{ request('available_from') }}" min="{{ now()->toDateString() }}">
  </label>
  <label class="field"><span>Sampai</span>
    <input type="date" name="available_to" value="{{ request('available_to') }}" min="{{ now()->toDateString() }}">
  </label>
  <select name="sort">
    <option value="newest" @selected(request('sort', 'newest') == 'newest')>Urutkan: Terbaru</option>
    <option value="price_asc" @selected(request('sort') == 'price_asc')>Harga terendah</option>
    <option value="price_desc" @selected(request('sort') == 'price_desc')>Harga tertinggi</option>
    <option value="rating" @selected(request('sort') == 'rating')>Rating tertinggi</option>
  </select>
  <button type="submit" class="btn primary">Cari</button>
  <button type="button" class="btn ghost" id="toggleMap">Lihat Peta</button>
</form>

<div class="card" id="mapCard" style="display:none;margin-top:16px;padding:8px">
  <div id="items-map" class="map" style="height:420px;margin:0"></div>
</div>

<div class="grid" id="itemGrid">
  @forelse($items as $item)
    <x-item-card :item="$item" />
  @empty
    <p class="muted">Belum ada barang yang cocok dengan pencarianmu.</p>
  @endforelse
</div>

{{ $items->links('vendor.pagination.sewain') }}
@endsection

@push('scripts')
<script>
  var mapReady = false, mapShown = false;
  document.getElementById('toggleMap').addEventListener('click', function () {
    mapShown = !mapShown;
    document.getElementById('mapCard').style.display = mapShown ? 'block' : 'none';
    document.getElementById('itemGrid').style.display = mapShown ? 'none' : '';
    this.textContent = mapShown ? 'Lihat Daftar' : 'Lihat Peta';
    if (mapShown) { mapReady ? window.itemsMap.invalidateSize() : initMap(); }
  });

  function initMap() {
    mapReady = true;
    var map = L.map('items-map').setView([0.9167, 104.45], 12);
    window.itemsMap = map;
    L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', { attribution: '&copy; OpenStreetMap' }).addTo(map);
    var data = @json(array_values(iterator_to_array($items)));
    var bounds = [];
    data.forEach(function (it) {
      if (!it.latitude || !it.longitude) return;
      var price = new Intl.NumberFormat('id-ID').format(it.price_per_day);
      L.marker([it.latitude, it.longitude]).addTo(map).bindPopup(
        '<b>' + it.name + '</b><br>Rp' + price + '/hari<br><a href="/items/' + it.id + '">Lihat detail</a>'
      );
      bounds.push([it.latitude, it.longitude]);
    });
    if (bounds.length) map.fitBounds(bounds, { padding: [30, 30] });
  }
</script>
@endpush
