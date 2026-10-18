@extends('layouts.app')
@section('title', 'Pengguna')

@section('content')
<form method="GET" action="{{ route('admin.users') }}" class="searchbar" style="margin-bottom:16px">
  <input type="text" name="q" value="{{ request('q') }}" placeholder="Cari nama, email, atau NIM…">
  <button type="submit" class="btn primary">Cari</button>
</form>

<div class="card">
  <table>
    <tr><th>Pengguna</th><th>Verifikasi</th><th>Teguran</th><th>Status akun</th><th>Tindakan</th></tr>
    @forelse($users as $u)
      <tr>
        <td><b>{{ $u->name }}</b><div class="muted">{{ $u->nim }} · {{ $u->email }}</div></td>
        <td><span class="badge {{ $u->verification_status === 'verified' ? 'ok' : ($u->verification_status === 'rejected' ? 'bad' : 'warn') }}">{{ $u->verificationLabel() }}</span></td>
        <td>{{ $u->warning_count }} / {{ \App\Models\User::MAX_WARNINGS }}</td>
        <td>
          @if($u->isSuspended())
            <span class="badge bad">Disuspend</span>
            <div class="muted">{{ $u->suspend_reason }}</div>
          @else
            <span class="badge ok">Aktif</span>
          @endif
        </td>
        <td>
          <details class="fold">
            <summary class="btn ghost sm">Kelola</summary>
            <form action="{{ route('admin.users.warn', $u->id) }}" method="POST">
              @csrf
              <input type="text" name="reason" maxlength="200" placeholder="Alasan teguran" required>
              <button type="submit" class="btn ghost sm" style="margin-top:6px">Beri Teguran</button>
            </form>
            @if($u->isSuspended())
              <form action="{{ route('admin.users.unsuspend', $u->id) }}" method="POST" style="margin-top:6px">
                @csrf
                <button type="submit" class="btn ok sm">Cabut Suspend</button>
              </form>
            @else
              <form action="{{ route('admin.users.suspend', $u->id) }}" method="POST" style="margin-top:6px">
                @csrf
                <input type="text" name="reason" maxlength="200" placeholder="Alasan suspend" required>
                <button type="submit" class="btn bad sm" style="margin-top:6px">Suspend</button>
              </form>
            @endif
          </details>
        </td>
      </tr>
    @empty
      <tr><td colspan="5" class="muted">Tidak ada pengguna.</td></tr>
    @endforelse
  </table>
  <div style="margin-top:12px">{{ $users->links() }}</div>
</div>
@endsection
