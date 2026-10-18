@php $v = fn($k, $d = null) => old($k, isset($item) ? $item->{$k} : $d); @endphp
<div class="h2sm">1. Informasi Barang</div>
<label for="name">Nama barang</label>
<input type="text" id="name" name="name" value="{{ $v('name') }}" required placeholder="Misal: Tenda dome kapasitas 4 orang">
@error('name') <div class="error">{{ $message }}</div> @enderror

<div class="row">
  <div class="col">
    <label for="category_id">Kategori</label>
    <select id="category_id" name="category_id" required>
      <option value="">Pilih kategori</option>
      @foreach($categories ?? [] as $cat)
        <option value="{{ $cat->id }}" @selected($v('category_id') == $cat->id)>{{ $cat->name }}</option>
      @endforeach
    </select>
    @error('category_id') <div class="error">{{ $message }}</div> @enderror
  </div>
  <div class="col">
    <label for="condition">Kondisi</label>
    <select id="condition" name="condition" required>
      @foreach(['baru' => 'Baru', 'baik' => 'Baik (normal)', 'cukup' => 'Cukup (ada minus sedikit)'] as $val => $lbl)
        <option value="{{ $val }}" @selected($v('condition', 'baik') == $val)>{{ $lbl }}</option>
      @endforeach
    </select>
    @error('condition') <div class="error">{{ $message }}</div> @enderror
  </div>
</div>

<label for="description">Deskripsi lengkap</label>
<textarea id="description" name="description" required placeholder="Jelaskan spesifikasi, kelengkapan, dan minus jika ada.">{{ $v('description') }}</textarea>
@error('description') <div class="error">{{ $message }}</div> @enderror

<div class="line"></div>
<div class="h2sm">2. Harga &amp; Deposit</div>
<div class="row">
  <div class="col">
    <label for="price_per_day">Harga sewa per hari (Rp)</label>
    <input type="number" id="price_per_day" name="price_per_day" min="1" value="{{ $v('price_per_day') !== null ? (int) $v('price_per_day') : '' }}" required>
    @error('price_per_day') <div class="error">{{ $message }}</div> @enderror
  </div>
  <div class="col">
    <label for="deposit">Deposit / uang jaminan (Rp)</label>
    <input type="number" id="deposit" name="deposit" min="0" value="{{ (int) $v('deposit', 0) }}">
    <div class="help">Dikembalikan ke penyewa setelah barang kembali.</div>
    @error('deposit') <div class="error">{{ $message }}</div> @enderror
  </div>
</div>

<div class="line"></div>
<div class="h2sm">3. Foto Barang (maks. 5)</div>
@isset($item)
  @if($item->images->count())
    <div class="thumbedit">
      @foreach($item->images as $image)
        <div>
          <img src="{{ asset('storage/'.$image->image_path) }}" alt="">
          <label><input type="checkbox" name="delete_images[]" value="{{ $image->id }}"> Hapus</label>
        </div>
      @endforeach
    </div>
  @endif
@endisset
<label for="images">{{ isset($item) ? 'Tambah foto baru' : 'Unggah foto' }}</label>
<input id="images" name="images[]" type="file" multiple accept="image/*">
<div class="help">JPG, PNG, atau WEBP, maksimal 2MB per file.</div>
@error('images') <div class="error">{{ $message }}</div> @enderror
@error('images.*') <div class="error">{{ $message }}</div> @enderror

<div class="line"></div>
<div class="h2sm">4. Lokasi Pengambilan</div>
<label for="address">Alamat lengkap / patokan</label>
<input type="text" id="address" name="address" value="{{ $v('address', auth()->user()->address ?? '') }}">
<label>Tandai di peta</label>
<x-map-picker :lat="isset($item) ? $item->latitude : auth()->user()->latitude" :lng="isset($item) ? $item->longitude : auth()->user()->longitude" />
