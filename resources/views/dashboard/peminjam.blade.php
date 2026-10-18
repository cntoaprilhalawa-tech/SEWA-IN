@extends('layouts.app')
@section('title', 'Dashboard Peminjam')

@section('content')
<div class="pagehead">
  <div>
    <h2>Halo, {{ $user->name }}</h2>
    <div class="muted">Pantau sewaanmu dan temukan barang yang kamu butuhkan.</div>
  </div>
  <div class="row" style="gap:8px">
    <a href="{{ route('bookings.index') }}" class="btn ghost">Riwayat Transaksi</a>
    <a href="{{ route('items.index') }}" class="btn primary">Cari Barang</a>
  </div>
</div>

<form method="GET" action="{{ route('items.index') }}" class="searchbar" style="margin-bottom:16px">
  <input type="text" name="q" placeholder="Mau sewa apa? Tenda, kamera, toga…">
  <button type="submit" class="btn primary">Cari</button>
</form>

<div class="stats">
  <div class="card"><div class="muted">Sedang berjalan</div><div class="n">{{ $stats['active'] }}</div></div>
  <div class="card"><div class="muted">Menunggu persetujuan</div><div class="n">{{ $stats['pending'] }}</div></div>
  <div class="card"><div class="muted">Perlu dibayar</div><div class="n">{{ $stats['to_pay'] }}</div></div>
  <div class="card"><div class="muted">Selesai</div><div class="n">{{ $stats['completed'] }}</div></div>
</div>

<div class="card" style="margin-bottom:16px">
  <div class="h2sm">Perlu tindakanmu</div>
  @if($toPay->isEmpty() && $toReview->isEmpty() && $dueSoon->isEmpty())
    <p class="muted" style="margin:6px 0 0">Tidak ada yang perlu dikerjakan sekarang.</p>
  @else
    <table>
      @foreach($toPay as $b)
        <tr>
          <td>Bayar sewa <b>{{ $b->item->name }}</b><div class="muted">Rp{{ number_format($b->total_price, 0, ',', '.') }} · #{{ $b->booking_code }}</div></td>
          <td style="text-align:right"><a href="{{ route('bookings.show', $b->id) }}" class="btn primary sm">Bayar</a></td>
        </tr>
      @endforeach
      @foreach($dueSoon as $b)
        <tr>
          <td>Kembalikan <b>{{ $b->item->name }}</b><div class="muted">Batas {{ $b->end_date->format('d M Y') }}</div></td>
          <td style="text-align:right"><a href="{{ route('bookings.show', $b->id) }}" class="btn ghost sm">Detail</a></td>
        </tr>
      @endforeach
      @foreach($toReview as $b)
        <tr>
          <td>Beri ulasan untuk <b>{{ $b->owner->name }}</b><div class="muted">{{ $b->item->name }}</div></td>
          <td style="text-align:right"><a href="{{ route('bookings.show', $b->id) }}" class="btn ghost sm">Ulas</a></td>
        </tr>
      @endforeach
    </table>
  @endif
</div>

<div class="card" style="margin-bottom:16px">
  <div class="cardhead">
    <div class="h2sm" style="margin:0">Sewaan berjalan</div>
    <a href="{{ route('bookings.index') }}" class="muted">Lihat semua</a>
  </div>
  <table>
    @forelse($currentBookings as $b)
      <tr>
        <td>{{ $b->item->name }}<div class="muted">Pemilik: {{ $b->owner->name }} · {{ $b->start_date->format('d M') }}–{{ $b->end_date->format('d M') }}</div></td>
        <td><x-status-badge :status="$b->status" /></td>
        <td style="text-align:right"><a href="{{ route('bookings.show', $b->id) }}" class="btn ghost sm">Detail</a></td>
      </tr>
    @empty
      <tr><td class="muted">Belum ada sewaan berjalan. <a href="{{ route('items.index') }}"><b>Cari barang</b></a> untuk mulai.</td></tr>
    @endforelse
  </table>
</div>

@if($latestItems->count())
  <div class="cardhead" style="margin-top:8px"><div class="h2sm" style="font-size:1.05rem">Barang terbaru</div><a href="{{ route('items.index') }}" class="muted">Lihat semua</a></div>
  <div class="grid tight">
    @foreach($latestItems as $item)<x-item-card :item="$item" />@endforeach
  </div>
@endif
@endsection
