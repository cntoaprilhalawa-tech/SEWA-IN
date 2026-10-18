@extends('layouts.app')
@section('title', 'Edit Barang')
@section('needs_map', '1')

@section('content')
<form class="card" style="max-width:720px" action="{{ route('items.update', $item->id) }}" method="POST" enctype="multipart/form-data">
  @csrf
  @method('PUT')
  @include('items._form')
  <div class="row" style="margin-top:18px;gap:8px">
    <button type="submit" class="btn primary">Simpan Perubahan</button>
    <a href="{{ route('items.show', $item->id) }}" class="btn ghost">Lihat Barang</a>
    <a href="{{ route('dashboard') }}" class="btn ghost">Batal</a>
  </div>
</form>
@endsection
