@extends('layouts.app')
@section('title', 'Riwayat Transaksi')

@section('content')
@php
    $statuses = ['all' => 'Semua', 'pending' => 'Menunggu', 'approved' => 'Disetujui', 'active' => 'Aktif', 'completed' => 'Selesai', 'cancelled' => 'Dibatalkan', 'rejected' => 'Ditolak'];
    $currentRole = $role;
    $currentStatus = request('status', 'all');
@endphp

<div class="tabs">
  @foreach($statuses as $key => $label)
    <a href="?status={{ $key }}" class="pill {{ $currentStatus === $key ? 'on' : '' }}">{{ $label }}</a>
  @endforeach
</div>

<div class="card">
  <table>
    <tr><th>Barang</th><th>{{ $currentRole === 'owner' ? 'Penyewa' : 'Pemilik' }}</th><th>Tanggal</th><th>Status</th><th>Pembayaran</th><th>Total</th><th>Deposit</th><th></th></tr>
    @forelse($bookings as $b)
      <tr>
        <td>{{ $b->item->name }}<div class="muted">#{{ $b->booking_code }}</div></td>
        <td>{{ $currentRole === 'owner' ? $b->renter->name : $b->owner->name }}</td>
        <td>{{ $b->start_date->format('d M') }}–{{ $b->end_date->format('d M Y') }}</td>
        <td><x-status-badge :status="$b->status" /></td>
        <td>@if(in_array($b->status, ['cancelled','rejected']))—@else<x-payment-badge :status="$b->payment_status ?? 'unpaid'" /><div class="muted">{{ $b->payment_method_label }}</div>@endif</td>
        <td>Rp{{ number_format($b->total_price, 0, ',', '.') }}</td>
        <td>{{ $b->deposit > 0 ? 'Rp'.number_format($b->deposit, 0, ',', '.') : '—' }}</td>
        <td style="white-space:nowrap">
          @if($currentRole === 'owner' && ($b->payment_status ?? '') === 'waiting')
            <a href="{{ route('payments.show', $b->id) }}" class="btn primary sm">Periksa Bukti</a>
          @else
            <a href="{{ route('bookings.show', $b->id) }}" class="btn ghost sm">{{ $currentRole === 'owner' && $b->status === 'pending' ? 'Tanggapi' : 'Detail' }}</a>
          @endif
          <a href="{{ route('complaints.create', ['booking' => $b->id]) }}" class="btn ghost sm">Keluhan</a>
        </td>
      </tr>
    @empty
      <tr><td colspan="8" class="muted">Belum ada transaksi.</td></tr>
    @endforelse
  </table>
</div>
{{ $bookings->links('vendor.pagination.sewain') }}
@endsection
