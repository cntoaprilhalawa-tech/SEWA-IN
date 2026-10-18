@extends('layouts.app')
@section('title', 'Kategori Barang')

@section('content')
<form class="card" style="max-width:520px;margin-bottom:16px" method="POST" action="{{ route('admin.categories.store') }}">
  @csrf
  <div class="h2sm">Tambah kategori</div>
  <label for="name" style="margin-top:0">Nama</label>
  <input type="text" id="name" name="name" value="{{ old('name') }}" maxlength="100" required>
  @error('name') <div class="error">{{ $message }}</div> @enderror
  <label for="icon">Ikon (opsional)</label>
  <input type="text" id="icon" name="icon" value="{{ old('icon') }}" maxlength="50" placeholder="laptop, camera, …">
  <button type="submit" class="btn primary" style="margin-top:12px">Tambah</button>
</form>

<div class="card">
  <table>
    <tr><th>Nama</th><th>Ikon</th><th>Barang</th><th></th></tr>
    @foreach($categories as $c)
      <tr>
        <td colspan="2">
          <form action="{{ route('admin.categories.update', $c->id) }}" method="POST" style="display:flex;gap:8px;flex-wrap:wrap">
            @csrf @method('PUT')
            <input type="text" name="name" value="{{ $c->name }}" maxlength="100" required style="flex:2;min-width:140px">
            <input type="text" name="icon" value="{{ $c->icon }}" maxlength="50" style="flex:1;min-width:90px">
            <button type="submit" class="btn ghost sm">Simpan</button>
          </form>
        </td>
        <td>{{ $c->items_count }}</td>
        <td>
          <form action="{{ route('admin.categories.destroy', $c->id) }}" method="POST" onsubmit="return confirm('Hapus kategori ini?')">
            @csrf @method('DELETE')
            <button type="submit" class="btn bad sm" @disabled($c->items_count > 0)>Hapus</button>
          </form>
        </td>
      </tr>
    @endforeach
  </table>
</div>
@endsection
