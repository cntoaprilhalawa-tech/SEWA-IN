@extends('layouts.app')
@section('title', 'Form Booking')

@section('content')
<div class="split">
  <form class="card" method="POST" action="{{ route('bookings.store') }}" id="bookingForm" enctype="multipart/form-data">
    @csrf
    <input type="hidden" name="item_id" value="{{ $item->id }}">
    <div class="h2sm">Detail Sewa</div>
    <div class="row">
      <div class="col">
        <label for="start_date">Tanggal mulai</label>
        <input type="date" id="start_date" name="start_date" value="{{ old('start_date') }}" min="{{ date('Y-m-d') }}" required>
        @error('start_date') <div class="error">{{ $message }}</div> @enderror
      </div>
      <div class="col">
        <label for="end_date">Tanggal selesai</label>
        <input type="date" id="end_date" name="end_date" value="{{ old('end_date') }}" min="{{ date('Y-m-d') }}" required>
        @error('end_date') <div class="error">{{ $message }}</div> @enderror
      </div>
    </div>
    @error('item_id') <div class="error">{{ $message }}</div> @enderror
    <div class="error" id="conflict" style="display:none">Tanggal yang dipilih bertabrakan dengan booking lain. Silakan pilih tanggal lain.</div>

    <label for="note">Catatan untuk pemilik (opsional)</label>
    <textarea id="note" name="note" placeholder="Contoh: mau diambil sore hari">{{ old('note') }}</textarea>
    @error('note') <div class="error">{{ $message }}</div> @enderror

    <div class="line"></div>
    <div class="h2sm">Metode Pembayaran</div>
    @php
        $own = $item->user;
        $hasBank = $own->bank_name && $own->account_number;
        $hasQris = (bool) $own->qris_image;
        $methods = [
            'cash' => ['Tunai (COD)', 'Bayar langsung ke pemilik saat barang diambil.', true],
            'transfer' => ['Transfer bank', $hasBank ? $own->bank_name.' — '.$own->account_number : 'Pemilik belum mengatur rekening.', $hasBank],
            'qris' => ['QRIS', $hasQris ? 'Scan kode QRIS pemilik dengan m-banking atau e-wallet.' : 'Pemilik belum mengunggah kode QRIS.', $hasQris],
        ];
        $selected = old('payment_method', $hasBank ? 'transfer' : ($hasQris ? 'qris' : 'cash'));
    @endphp
    @foreach($methods as $key => [$label, $hint, $enabled])
      <label class="card" style="display:flex;gap:12px;align-items:flex-start;margin:8px 0 0;padding:12px 14px;{{ $enabled ? 'cursor:pointer' : 'opacity:.55' }}">
        <input type="radio" name="payment_method" value="{{ $key }}" style="width:auto;margin-top:3px" {{ $enabled ? '' : 'disabled' }} {{ $selected === $key && $enabled ? 'checked' : '' }}>
        <span><b>{{ $label }}</b><br><span class="muted">{{ $hint }}</span></span>
      </label>
    @endforeach
    <label class="card" style="display:flex;gap:12px;align-items:flex-start;margin:8px 0 0;padding:12px 14px;opacity:.55">
      <input type="radio" disabled style="width:auto;margin-top:3px">
      <span><b>PayLater</b> <span class="badge warn">Segera hadir</span><br><span class="muted">Cicilan lewat mitra bank/fintech, butuh integrasi payment gateway.</span></span>
    </label>
    @error('payment_method') <div class="error">{{ $message }}</div> @enderror

    <div id="panel-transfer" class="card" style="background:var(--bg);margin-top:12px;display:none">
      <div class="stat"><span>Bank</span><b>{{ $own->bank_name }}</b></div>
      <div class="stat"><span>No. rekening</span><b><span id="rekText">{{ $own->account_number }}</span> <button type="button" class="btn ghost sm" id="copyRek">Salin</button></b></div>
      <div class="stat"><span>a.n.</span><b>{{ $own->name }}</b></div>
    </div>
    <div id="panel-qris" class="card" style="background:var(--bg);margin-top:12px;display:none;text-align:center">
      @if($hasQris)<img src="{{ asset('storage/'.$own->qris_image) }}" alt="QRIS {{ $own->name }}" style="max-width:240px;width:100%;border-radius:12px"><div class="muted" style="margin-top:6px">a.n. {{ $own->name }}</div>@endif
    </div>
    <div id="panel-cash" class="card" style="background:var(--bg);margin-top:12px;display:none">
      <p class="muted" style="margin:0">Kamu membayar tunai langsung ke {{ $own->name }} saat barang diambil. Kontak: {{ $own->phone ?? '-' }}</p>
    </div>

    <div id="proofWrap" class="muted" style="display:none">
      <p style="margin:12px 0 0">Pembayaran dilakukan <b>setelah pemilik menyetujui</b> booking. Kamu akan mengunggah bukti transfer dari halaman Detail Booking.</p>
    </div>

    <button type="submit" class="btn primary" id="submitBtn" style="margin-top:16px">Ajukan Booking</button>
  </form>

  <div>
    <div class="card" style="margin-bottom:16px">
      <div class="thumb" style="height:150px;border-radius:12px;overflow:hidden">
        @if($item->images->first())<img src="{{ asset('storage/'.$item->images->first()->image_path) }}" alt="">@else 🏷️ @endif
      </div>
      <div class="h2sm" style="margin-top:12px">{{ $item->name }}</div>
      <div class="muted">{{ $item->category->name ?? '' }} · Milik {{ $item->user->name }}</div>
    </div>

    <div class="card" id="ringkasan">
      <div class="h2sm">Ringkasan Biaya</div>
      <div class="stat"><span>Rp{{ number_format($item->price_per_day, 0, ',', '.') }} × <span id="jmlHari">-</span> hari</span><b id="subtotal">—</b></div>
      @if($item->deposit > 0)
        <div class="stat"><span>Deposit (dikembalikan)</span><b>Rp{{ number_format($item->deposit, 0, ',', '.') }}</b></div>
      @endif
      <div class="line"></div>
      <div class="stat" style="font-size:1rem"><span>Total dibayar</span><b id="total">—</b></div>
    </div>
  </div>
</div>
@endsection

@push('scripts')
<script>
  // Ringkasan dihitung di browser; angka final tetap dihitung ulang di controller.
  var harga = {{ (float) $item->price_per_day }}, deposit = {{ (float) ($item->deposit ?? 0) }};
  var booked = @json($bookedDates ?? []);
  var form = document.getElementById('bookingForm');
  var rp = function (n) { return 'Rp' + Math.round(n).toLocaleString('id-ID'); };
  function hitung() {
    var s = form.start_date.value, e = form.end_date.value;
    if (s) form.end_date.min = s;
    if (!s || !e) return;
    var a = new Date(s), b = new Date(e);
    if (b < a) { return; }
    var hari = Math.round((b - a) / 86400000) + 1, bentrok = false;
    for (var d = new Date(a); d <= b; d.setDate(d.getDate() + 1)) {
      var key = d.getFullYear() + '-' + String(d.getMonth() + 1).padStart(2, '0') + '-' + String(d.getDate()).padStart(2, '0');
      if (booked.indexOf(key) > -1) { bentrok = true; break; }
    }
    document.getElementById('jmlHari').textContent = hari;
    document.getElementById('subtotal').textContent = rp(harga * hari);
    document.getElementById('total').textContent = rp(harga * hari + deposit);
    document.getElementById('conflict').style.display = bentrok ? 'block' : 'none';
    document.getElementById('submitBtn').disabled = bentrok;
  }
  form.start_date.addEventListener('change', hitung);
  form.end_date.addEventListener('change', hitung);
  hitung();

  function showMethod() {
    var r = form.querySelector('input[name=payment_method]:checked'), m = r ? r.value : '';
    ['transfer', 'qris', 'cash'].forEach(function (k) { document.getElementById('panel-' + k).style.display = (m === k) ? 'block' : 'none'; });
    document.getElementById('proofWrap').style.display = (m === 'transfer' || m === 'qris') ? 'block' : 'none';
  }
  form.querySelectorAll('input[name=payment_method]').forEach(function (r) { r.addEventListener('change', showMethod); });
  showMethod();
  var cp = document.getElementById('copyRek');
  if (cp) cp.addEventListener('click', function () {
    var t = document.getElementById('rekText').textContent.trim();
    if (navigator.clipboard) navigator.clipboard.writeText(t);
    cp.textContent = 'Tersalin';
  });
</script>
@endpush
