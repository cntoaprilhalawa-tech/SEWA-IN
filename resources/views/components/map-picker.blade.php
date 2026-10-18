@props(['lat' => null, 'lng' => null])
<div id="location-map" class="map" style="height:300px"></div>
<input type="hidden" id="latitude" name="latitude" value="{{ old('latitude', $lat) }}">
<input type="hidden" id="longitude" name="longitude" value="{{ old('longitude', $lng) }}">
<div class="help">Klik peta untuk menandai lokasi.</div>
@push('scripts')
<script>
  (function () {
    var lat = {{ (float) old('latitude', $lat ?: 0.9167) }}, lng = {{ (float) old('longitude', $lng ?: 104.45) }};
    var map = L.map('location-map').setView([lat, lng], 14), marker = null;
    L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', { attribution: '&copy; OpenStreetMap' }).addTo(map);
    @if(old('latitude', $lat))
      marker = L.marker([lat, lng]).addTo(map);
    @endif
    map.on('click', function (e) {
      if (marker) marker.setLatLng(e.latlng); else marker = L.marker(e.latlng).addTo(map);
      document.getElementById('latitude').value = e.latlng.lat.toFixed(8);
      document.getElementById('longitude').value = e.latlng.lng.toFixed(8);
    });
  })();
</script>
@endpush
