@props(['status'])
@php
    $map = [
        'unpaid'   => ['warn', 'Belum dibayar'],
        'waiting'  => ['warn', 'Menunggu konfirmasi'],
        'paid'     => ['ok', 'Lunas'],
        'rejected' => ['bad', 'Bukti ditolak'],
    ];
    [$tone, $label] = $map[$status] ?? ['warn', ucfirst($status)];
@endphp
<span class="badge {{ $tone }}">{{ $label }}</span>
