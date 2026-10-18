@props(['status'])
@php
    $map = [
        'pending'   => ['warn', 'Menunggu Respons'],
        'approved'  => ['ok', 'Disetujui'],
        'active'    => ['ok', 'Sedang Disewa'],
        'completed' => ['ok', 'Selesai'],
        'cancelled' => ['bad', 'Dibatalkan'],
        'rejected'  => ['bad', 'Ditolak'],
    ];
    [$tone, $label] = $map[$status] ?? ['warn', ucfirst($status)];
@endphp
<span class="badge {{ $tone }}">{{ $label }}</span>
