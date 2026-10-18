@extends('layouts.app')
@section('title', 'Verifikasi KTM')

@section('content')
<div class="card">
  <div class="h2sm">Menunggu verifikasi ({{ $pending->count() }})</div>
  @forelse($pending as $u)
    <div style="display:flex;gap:16px;flex-wrap:wrap;padding:12px 0;border-bottom:1px solid var(--line)">
      <a href="{{ route('ktm.image', $u->id) }}" target="_blank" rel="noopener">
        <img src="{{ route('ktm.image', $u->id) }}" alt="KTM {{ $u->name }}" style="width:220px;max-width:100%;border-radius:10px;border:1px solid var(--line)">
      </a>
      <div style="flex:1;min-width:220px">
        <b>{{ $u->name }}</b>
        <div class="muted">NIM: {{ $u->nim }} · {{ $u->email }}</div>
        <div class="muted">Mendaftar {{ $u->created_at->diffForHumans() }}</div>
        <form action="{{ route('admin.verifications.approve', $u->id) }}" method="POST" style="margin-top:10px">
          @csrf
          <button type="submit" class="btn ok">Setujui</button>
        </form>
        <details class="fold">
          <summary class="btn bad">Tolak</summary>
          <form action="{{ route('admin.verifications.reject', $u->id) }}" method="POST">
            @csrf
            <label for="note{{ $u->id }}">Alasan penolakan</label>
            <input type="text" id="note{{ $u->id }}" name="note" maxlength="200" required placeholder="Foto buram / NIM tidak cocok / dll">
            <button type="submit" class="btn bad" style="margin-top:8px">Kirim Penolakan</button>
          </form>
        </details>
      </div>
    </div>
  @empty
    <p class="muted" style="margin:0">Tidak ada pengajuan yang menunggu.</p>
  @endforelse
</div>

<div class="card" style="margin-top:16px">
  <div class="h2sm">Riwayat terbaru</div>
  <table>
    <tr><th>Nama</th><th>NIM</th><th>Status</th><th>Catatan</th></tr>
    @forelse($recent as $u)
      <tr>
        <td>{{ $u->name }}</td>
        <td>{{ $u->nim }}</td>
        <td><span class="badge {{ $u->verification_status === 'verified' ? 'ok' : 'bad' }}">{{ $u->verificationLabel() }}</span></td>
        <td class="muted">{{ $u->verification_note ?? '—' }}</td>
      </tr>
    @empty
      <tr><td colspan="4" class="muted">Belum ada riwayat.</td></tr>
    @endforelse
  </table>
</div>
@endsection
