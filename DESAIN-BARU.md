# Desain baru SEWA-IN (mengikuti kit Blade asli)
- Tailwind & Alpine dihapus. Semua halaman memakai public/css/sewain.css (kit) dengan struktur layar kit.
- Layout: resources/views/layouts/app.blade.php (sidebar + topbar + .screen)
- Halaman ditulis ulang: items (index, show, create, edit), bookings (index, create, show), dashboard, profile (show, edit), auth (login, register, verify-email)
- Komponen: item-card, status-badge, star-rating, map-picker; pagination: vendor/pagination/sewain
- Bug lama yang ikut diperbaiki: route bookings.create/profile.show/reviews.store, field note/address/condition, pemilik barang (user_id)

## Aktor (peran)
- peminjam: Dashboard, Cari Barang, Riwayat Transaksi, Ajukan Keluhan, Profil
- pemilik: Dashboard, Sewakan Barang, Riwayat Transaksi, Konfirmasi Pembayaran, Cari Barang, Ajukan Keluhan, Profil
- admin: Keluhan & Moderasi saja
Akses dibatasi middleware `role:` di routes/web.php. Daftar akun memilih peran; akun lama otomatis disesuaikan oleh migrasi 2026_10_03.

## Pembaruan v7: dashboard per mode dan layout
- Peminjam dan pemilik masing-masing punya dashboard (resources/views/dashboard/peminjam.blade.php dan pemilik.blade.php). Route `dashboard` dibuka untuk keduanya; DashboardController memilih tampilan sesuai mode aktif.
- Mode aktif disimpan di kolom `users.active_mode` (tidak lagi di sesi), jadi tidak reset saat login ulang. Pengalih mode ada di topbar (tanpa popup konfirmasi).
- Login dan register memakai layouts/auth.blade.php (tanpa sidebar).
- Menu mobile berupa drawer dengan tombol hamburger. Leaflet hanya dimuat di halaman yang memakai `@section('needs_map')`.
- Alur verifikasi email yang tidak terpakai dihapus (verifikasi akun memakai KTM).
