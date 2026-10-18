@extends('layouts.app')
@section('title', 'Konfirmasi Pembayaran')

@section('content')
@php $waiting = ($booking->payment_status ?? '') === 'waiting'; @endphp
<span style="margin-bottom:14px;display:inline-block"><x-payment-badge :status="$booking->payment_status ?? 'unpaid'" /></span>

<div class="split">
  <div class="card">
    <div class="h2sm">Bukti Transfer dari Peminjam</div>
    @if($booking->payment_proof)
      <div class="thumb" style="height:320px;border-radius:12px;margin-top:10px;background:#fff;overflow:hidden">
        <a href="{{ route('payments.proof', $booking->id) }}" target="_blank" rel="noopener" style="display:block;width:100%;height:100%">
          <img src="{{ route('payments.proof', $booking->id) }}" alt="Bukti transfer" style="object-fit:contain">
        </a>
      </div>
      <p class="muted" style="margin-top:10px">Diunggah oleh {{ $booking->renter->name }} · terakhir diperbarui {{ $booking->updated_at->format('d M Y, H:i') }}</p>
    @else
      <div class="proofbox" style="margin-top:10px">Belum ada bukti transfer.</div>
    @endif
  </div>

  <div class="card" style="align-self:start">
    <div class="h2sm">{{ $booking->item->name }}</div>
    <div class="stat"><span>Peminjam</span><b>{{ $booking->renter->name }}</b></div>
    <div class="stat"><span>Metode</span><b>{{ $booking->payment_method_label }}</b></div>
    <div class="stat"><span>Durasi</span><b>{{ $booking->total_days }} hari</b></div>
    <div class="stat"><span>Sewa</span><b>Rp{{ number_format($booking->total_price - $booking->deposit, 0, ',', '.') }}</b></div>
    @if($booking->deposit > 0)<div class="stat"><span>Deposit</span><b>Rp{{ number_format($booking->deposit, 0, ',', '.') }}</b></div>@endif
    <div class="stat"><span>Total transfer</span><b>Rp{{ number_format($booking->total_price, 0, ',', '.') }}</b></div>
    <div class="line"></div>

    @if($waiting)
      <p class="muted">Periksa nominal &amp; bukti sebelum menerima. Jika tidak sesuai, tolak agar peminjam unggah ulang.</p>
      <div class="row" style="margin-top:10px">
        <form method="POST" action="{{ route('payments.accept', $booking->id) }}" style="flex:1">
          @csrf
          <button type="submit" class="btn ok" style="width:100%;justify-content:center">Terima</button>
        </form>
        <form method="POST" action="{{ route('payments.reject', $booking->id) }}" style="flex:1">
          @csrf
          <button type="submit" class="btn bad" style="width:100%;justify-content:center">Tolak</button>
        </form>
      </div>
    @else
      <p class="muted" style="margin:0">Pembayaran ini sudah diproses atau belum menunggu konfirmasi.</p>
    @endif
    <a href="{{ route('bookings.show', $booking->id) }}" class="btn ghost block" style="margin-top:10px">Lihat Detail Booking</a>
  </div>
</div>
@endsection
