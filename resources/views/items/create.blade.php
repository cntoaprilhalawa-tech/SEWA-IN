@extends('layouts.app')
@section('title', 'Sewakan Barang')
@section('needs_map', '1')

@section('content')
<form class="card" style="max-width:720px" action="{{ route('items.store') }}" method="POST" enctype="multipart/form-data">
  @csrf
  @include('items._form')
  <div class="row" style="margin-top:18px;gap:8px">
    <button type="submit" class="btn primary">Simpan Barang</button>
    <a href="{{ route('dashboard') }}" class="btn ghost">Batal</a>
  </div>
</form>
@endsection
