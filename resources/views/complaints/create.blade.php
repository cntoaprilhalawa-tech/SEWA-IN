@extends('layouts.app')
@section('title', 'Ajukan Keluhan')

@section('content')
<form class="card" style="max-width:520px" method="POST" action="{{ route('complaints.store') }}" enctype="multipart/form-data">
  @csrf
  <label for="booking_id" style="margin-top:0">Booking terkait</label>
  <select id="booking_id" name="booking_id" style="width:100%" required>
    @forelse($bookings as $b)
      <option value="{{ $b->id }}"
              data-other="{{ auth()->id() === $b->renter_id ? 'Pemilik — '.$b->owner->name : 'Peminjam — '.$b->renter->name }}"
              @selected(old('booking_id', request('booking')) == $b->id)>
        {{ $b->item->name }} — {{ $b->start_date->format('d M') }} s/d {{ $b->end_date->format('d M') }}
      </option>
    @empty
      <option value="">Belum ada booking</option>
    @endforelse
  </select>
  @error('booking_id') <div class="error">{{ $message }}</div> @enderror

  <label>Pihak yang dikeluhkan</label>
  <input type="text" id="other" value="" readonly>

  <label for="content">Isi keluhan</label>
  <textarea id="content" name="content" placeholder="Jelaskan kejadiannya secara singkat…" required>{{ old('content') }}</textarea>
  @error('content') <div class="error">{{ $message }}</div> @enderror

  <label for="evidence">Bukti foto (opsional, maks 2MB)</label>
  <input type="file" id="evidence" name="evidence" accept="image/*">
  @error('evidence') <div class="error">{{ $message }}</div> @enderror

  <button type="submit" class="btn primary" style="margin-top:16px" @disabled($bookings->isEmpty())>Kirim Keluhan</button>
</form>
@endsection

@push('scripts')
<script>
  var sel = document.getElementById('booking_id'), other = document.getElementById('other');
  function setOther() { var o = sel.options[sel.selectedIndex]; other.value = o && o.dataset.other ? o.dataset.other : '—'; }
  sel.addEventListener('change', setOther); setOther();
</script>
@endpush
