@extends('layouts.app')
@section('title', 'Notifikasi')

@section('content')
<div class="card" style="max-width:720px">
  @forelse($notifications as $n)
    @php $d = $n->data; $isNew = in_array($n->id, $unreadIds, true); @endphp
    <div style="padding:10px 0;border-bottom:1px solid var(--line)">
      <div style="display:flex;justify-content:space-between;gap:10px">
        <b>@if($isNew)<span class="badge warn" style="margin-right:6px">Baru</span>@endif{{ $d['title'] ?? 'Notifikasi' }}</b>
        <span class="muted">{{ $n->created_at->diffForHumans() }}</span>
      </div>
      <p class="muted" style="margin:4px 0 0">{{ $d['body'] ?? '' }}</p>
      @if(!empty($d['url']))<a href="{{ $d['url'] }}" style="font-size:.85rem">Buka</a>@endif
    </div>
  @empty
    <p class="muted" style="margin:0">Belum ada notifikasi.</p>
  @endforelse
  <div style="margin-top:12px">{{ $notifications->links('vendor.pagination.sewain') }}</div>
</div>
@endsection
