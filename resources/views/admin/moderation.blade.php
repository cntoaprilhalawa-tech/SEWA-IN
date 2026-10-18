@extends('layouts.app')
@section('title', 'Keluhan & Moderasi')

@section('content')
<div class="row" style="margin-bottom:6px">
  <div class="col card"><div class="muted">Keluhan baru</div><div style="font-size:1.6rem;font-weight:800">{{ $stat['new'] }}</div></div>
  <div class="col card"><div class="muted">Sedang ditinjau</div><div style="font-size:1.6rem;font-weight:800">{{ $stat['reviewing'] }}</div></div>
  <div class="col card"><div class="muted">Selesai bulan ini</div><div style="font-size:1.6rem;font-weight:800">{{ $stat['resolved_this_month'] }}</div></div>
</div>

<div class="card" style="margin-top:16px">
  <div class="h2sm">Antrean Keluhan</div>
  <table>
    <tr><th>Pelapor → Terlapor</th><th>Booking</th><th>Isi &amp; bukti</th><th>Status</th><th>Tindak lanjut</th></tr>
    @forelse($complaints as $k)
      <tr>
        <td>{{ $k->reporter->name }} → {{ $k->reported->name }}</td>
        <td>{{ $k->booking->item->name ?? '—' }}</td>
        <td class="muted">{{ $k->content }}
          @if($k->evidence)<div><a href="{{ route('complaints.evidence', $k->id) }}" target="_blank" rel="noopener">Lihat bukti</a></div>@endif
          @if($k->admin_action)<div><b>Tindakan:</b> {{ ['none' => 'Tanpa tindakan', 'warning' => 'Teguran', 'suspend' => 'Suspend', 'dismissed' => 'Keluhan ditolak'][$k->admin_action] ?? $k->admin_action }}@if($k->admin_note) — {{ $k->admin_note }}@endif</div>@endif
        </td>
        <td><span class="badge {{ $k->status == 'new' ? 'bad' : ($k->status == 'reviewing' ? 'warn' : 'ok') }}">{{ ['new' => 'Baru', 'reviewing' => 'Ditinjau', 'resolved' => 'Selesai'][$k->status] ?? $k->status }}</span></td>
        <td>
          <form method="POST" action="{{ route('admin.complaints.update', $k->id) }}">
            @csrf
            <select name="status">
              <option value="new" @selected($k->status == 'new')>Baru</option>
              <option value="reviewing" @selected($k->status == 'reviewing')>Ditinjau</option>
              <option value="resolved" @selected($k->status == 'resolved')>Selesai</option>
            </select>
            @if(!in_array($k->admin_action, ['warning', 'suspend'], true))
              <select name="admin_action" style="margin-top:6px">
                <option value="">Tindakan…</option>
                <option value="none">Tanpa tindakan</option>
                <option value="dismissed">Tolak keluhan</option>
                <option value="warning">Beri teguran ke terlapor</option>
                <option value="suspend">Suspend terlapor</option>
              </select>
            @endif
            <input type="text" name="admin_note" maxlength="500" placeholder="Catatan / alasan" value="{{ $k->admin_note }}" style="margin-top:6px">
            <button type="submit" class="btn ghost sm" style="margin-top:6px">Simpan</button>
          </form>
        </td>
      </tr>
    @empty
      <tr><td colspan="5" class="muted">Belum ada keluhan.</td></tr>
    @endforelse
  </table>
</div>

<div class="card" style="margin-top:16px">
  <div class="h2sm">Moderasi Rating &amp; Review</div>
  <table>
    <tr><th>Ulasan</th><th>Barang</th><th>Rating</th><th>Status</th><th></th></tr>
    @forelse($reviews as $r)
      <tr>
        <td>"{{ \Illuminate\Support\Str::limit($r->comment, 60) }}"<div class="muted">oleh {{ $r->reviewer->name ?? '-' }}</div></td>
        <td>{{ $r->item->name ?? '—' }}</td>
        <td class="stars">{{ str_repeat('★', $r->rating) }}{{ str_repeat('☆', 5 - $r->rating) }}</td>
        <td><span class="badge {{ $r->is_hidden ? 'bad' : 'ok' }}">{{ $r->is_hidden ? 'Disembunyikan' : 'Aktif' }}</span></td>
        <td>
          <form method="POST" action="{{ route('admin.reviews.toggle', $r->id) }}">
            @csrf
            <button type="submit" class="btn ghost sm">{{ $r->is_hidden ? 'Tampilkan' : 'Sembunyikan' }}</button>
          </form>
        </td>
      </tr>
    @empty
      <tr><td colspan="5" class="muted">Belum ada ulasan.</td></tr>
    @endforelse
  </table>
</div>
@endsection
