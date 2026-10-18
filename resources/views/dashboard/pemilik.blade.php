@extends('layouts.app')
@section('title', 'Dashboard Pemilik')

@section('content')
<div class="pagehead">
  <div>
    <h2>Halo, {{ $user->name }}</h2>
    <div class="muted">Kelola barang yang kamu sewakan dan tanggapi permintaan masuk.</div>
  </div>
  <div class="row" style="gap:8px">
    <a href="{{ route('bookings.index') }}" class="btn ghost">Riwayat Transaksi</a>
    <a href="{{ route('items.create') }}" class="btn primary">+ Sewakan Barang</a>
  </div>
</div>

<div class="stats">
  <div class="card"><div class="muted">Barang saya</div><div class="n">{{ $stats['items'] }}</div></div>
  <div class="card"><div class="muted">Permintaan masuk</div><div class="n">{{ $stats['requests'] }}</div></div>
  <div class="card"><div class="muted">Menunggu konfirmasi bayar</div><div class="n">{{ $stats['waiting_payments'] }}</div></div>
  <div class="card"><div class="muted">Sedang disewa</div><div class="n">{{ $stats['active'] }}</div></div>
  <div class="card"><div class="muted">Pendapatan (lunas)</div><div class="n" style="font-size:1.25rem">Rp{{ number_format($stats['income'], 0, ',', '.') }}</div></div>
  <div class="card"><div class="muted">Rating rata-rata</div><div class="n">⭐ {{ number_format($stats['rating'], 1) }}</div></div>
</div>

<div class="cols2">
  <div class="card">
    <div class="cardhead"><div class="h2sm" style="margin:0">Permintaan Masuk</div></div>
    <table>
      @forelse($pendingRequests->take(5) as $booking)
        <tr>
          <td>{{ $booking->item->name }}<div class="muted">Dari {{ $booking->renter->name }} · {{ $booking->start_date->format('d M') }}–{{ $booking->end_date->format('d M') }}</div></td>
          <td style="text-align:right"><a href="{{ route('bookings.show', $booking->id) }}" class="btn primary sm">Tanggapi</a></td>
        </tr>
      @empty
        <tr><td class="muted">Belum ada permintaan masuk.</td></tr>
      @endforelse
    </table>
  </div>

  <div class="card">
    <div class="cardhead">
      <div class="h2sm" style="margin:0">Menunggu Konfirmasi Pembayaran</div>
      <a href="{{ route('payments.index') }}" class="muted">Lihat semua</a>
    </div>
    <table>
      @forelse($waitingPayments as $booking)
        <tr>
          <td>{{ $booking->item->name }}<div class="muted">{{ $booking->renter->name }} · Rp{{ number_format($booking->total_price, 0, ',', '.') }}</div></td>
          <td style="text-align:right"><a href="{{ route('payments.show', $booking->id) }}" class="btn primary sm">Periksa</a></td>
        </tr>
      @empty
        <tr><td class="muted">Tidak ada bukti bayar yang menunggu.</td></tr>
      @endforelse
    </table>
  </div>
</div>

<div class="cardhead" style="margin-top:8px"><div class="h2sm" style="font-size:1.05rem">Barang Saya</div></div>
@if($myItems->count() > 0)
  <div class="grid tight">
    @foreach($myItems as $item)
      <div class="itemcard">
        <a href="{{ route('items.show', $item->id) }}" class="thumb" style="display:flex">
          @if($item->images->first())<img src="{{ asset('storage/'.$item->images->first()->image_path) }}" alt="{{ $item->name }}">@else 🏷️ @endif
        </a>
        <div class="body">
          <p class="nm">{{ $item->name }}</p>
          <div class="price">Rp{{ number_format($item->price_per_day, 0, ',', '.') }}/hari</div>
          <span class="badge {{ $item->is_available ? 'ok' : 'bad' }}" style="margin-top:8px">{{ $item->is_available ? 'Tersedia' : 'Tidak tersedia' }}</span>
          <div class="row" style="margin-top:10px;gap:6px">
            <a href="{{ route('items.edit', $item->id) }}" class="btn ghost sm">Edit</a>
            <form action="{{ route('items.toggle', $item->id) }}" method="POST">
              @csrf @method('PATCH')
              <button type="submit" class="btn ghost sm">{{ $item->is_available ? 'Nonaktifkan' : 'Aktifkan' }}</button>
            </form>
            <form action="{{ route('items.destroy', $item->id) }}" method="POST" onsubmit="return confirm('Hapus barang ini?')">
              @csrf @method('DELETE')
              <button type="submit" class="btn bad sm">Hapus</button>
            </form>
          </div>
        </div>
      </div>
    @endforeach
  </div>
@else
  <div class="card" style="text-align:center">
    <div class="h2sm">Belum ada barang</div>
    <p class="muted">Mulai sewakan barangmu untuk mendapat penghasilan tambahan.</p>
    <a href="{{ route('items.create') }}" class="btn primary">+ Sewakan Barang</a>
  </div>
@endif
@endsection
