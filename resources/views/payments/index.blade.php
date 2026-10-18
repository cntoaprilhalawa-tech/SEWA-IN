@extends('layouts.app')
@section('title', 'Konfirmasi Pembayaran')

@section('content')
<p class="muted" style="margin-top:0">Bukti transfer dari penyewa yang menunggu kamu periksa.</p>
<div class="card">
  <table>
    <tr><th>Barang</th><th>Peminjam</th><th>Tanggal</th><th>Total transfer</th><th></th></tr>
    @forelse($bookings as $b)
      <tr>
        <td>{{ $b->item->name }}<div class="muted">#{{ $b->booking_code }}</div></td>
        <td>{{ $b->renter->name }}</td>
        <td>{{ $b->start_date->format('d M') }}–{{ $b->end_date->format('d M Y') }}</td>
        <td>Rp{{ number_format($b->total_price, 0, ',', '.') }}</td>
        <td><a href="{{ route('payments.show', $b->id) }}" class="btn primary sm">Periksa Bukti</a></td>
      </tr>
    @empty
      <tr><td colspan="5" class="muted">Tidak ada pembayaran yang menunggu konfirmasi.</td></tr>
    @endforelse
  </table>
</div>
{{ $bookings->links('vendor.pagination.sewain') }}
@endsection
