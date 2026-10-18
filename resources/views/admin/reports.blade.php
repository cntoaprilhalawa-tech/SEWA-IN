@extends('layouts.app')
@section('title', 'Laporan Transaksi')

@section('content')
<form method="GET" action="{{ route('admin.reports') }}" class="searchbar" style="margin-bottom:16px">
  <input type="date" name="from" value="{{ request('from') }}" title="Dari tanggal">
  <input type="date" name="to" value="{{ request('to') }}" title="Sampai tanggal">
  <select name="status">
    <option value="">Semua status</option>
    @foreach(['pending' => 'Pending', 'approved' => 'Disetujui', 'active' => 'Aktif', 'completed' => 'Selesai', 'cancelled' => 'Dibatalkan', 'rejected' => 'Ditolak'] as $v => $l)
      <option value="{{ $v }}" @selected(request('status') === $v)>{{ $l }}</option>
    @endforeach
  </select>
  <button type="submit" class="btn primary">Terapkan</button>
  <a href="{{ route('admin.reports.export', request()->query()) }}" class="btn ghost">Unduh CSV</a>
</form>
@error('to') <div class="error">{{ $message }}</div> @enderror

<div class="row" style="margin-bottom:6px">
  <div class="col card"><div class="muted">Total transaksi</div><div style="font-size:1.4rem;font-weight:800">{{ $summary['count'] }}</div></div>
  <div class="col card"><div class="muted">Selesai</div><div style="font-size:1.4rem;font-weight:800">{{ $summary['completed'] }}</div></div>
  <div class="col card"><div class="muted">Nilai sewa selesai</div><div style="font-size:1.4rem;font-weight:800">Rp{{ number_format($summary['revenue'], 0, ',', '.') }}</div></div>
  <div class="col card"><div class="muted">Denda</div><div style="font-size:1.4rem;font-weight:800">Rp{{ number_format($summary['late_fee'], 0, ',', '.') }}</div></div>
</div>

<div class="card" style="margin-top:16px;overflow-x:auto">
  <table>
    <tr><th>Kode</th><th>Barang</th><th>Peminjam → Pemilik</th><th>Periode</th><th>Status</th><th>Bayar</th><th>Total sewa</th><th>Denda</th></tr>
    @forelse($bookings as $b)
      <tr>
        <td>{{ $b->booking_code }}</td>
        <td>{{ $b->item->name ?? '—' }}</td>
        <td>{{ $b->renter->name ?? '—' }} → {{ $b->owner->name ?? '—' }}</td>
        <td>{{ $b->start_date->format('d M') }} – {{ $b->end_date->format('d M Y') }}</td>
        <td>{{ $b->status }}</td>
        <td>{{ $b->payment_status }}</td>
        <td>Rp{{ number_format($b->total_price, 0, ',', '.') }}</td>
        <td>Rp{{ number_format($b->late_fee, 0, ',', '.') }}</td>
      </tr>
    @empty
      <tr><td colspan="8" class="muted">Tidak ada transaksi pada filter ini.</td></tr>
    @endforelse
  </table>
  <div style="margin-top:12px">{{ $bookings->links() }}</div>
</div>
@endsection
