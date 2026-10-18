# Perbaikan SEWA-IN

Setelah menimpa file:

    php artisan migrate        # 3 migrasi baru (denda/deposit, verifikasi/notifikasi/dll)
    php artisan schedule:run   # pasang di cron tiap menit agar booking kedaluwarsa otomatis

Akun yang sudah ada otomatis berstatus "terverifikasi". Akun baru wajib unggah KTM.
Admin: /admin/verifications, /admin/users, /admin/categories, /admin/reports.

## v7 (layout, menu, dashboard)
Jalankan `php artisan migrate` (menambah kolom users.active_mode).
- Dashboard terpisah untuk peminjam dan pemilik; statistik dipisah (renter_id vs owner_id).
- Pengalih mode dipindah ke topbar, mode disimpan di database.
- Layout khusus login/register; banner jika KTM belum terverifikasi; menu pemilik ditambah "Cari Barang"; urutan menu admin disesuaikan; "Profil Saya" tidak lagi aktif di profil orang lain.
- Menu mobile jadi drawer; Leaflet dimuat hanya saat dibutuhkan.
- Dihapus: route/controller/view verifikasi email (tidak terpakai dan berpotensi error).
- Fix: menu "Cari Barang" untuk pemilik tidak lagi memantul ke dashboard (ItemController@index hanya mengalihkan admin).
- Fix: pagination halaman notifikasi memakai tampilan sewain (sebelumnya ikon panah membesar).
- Menu Akun dipindah: blok nama + ikon gear + tombol Keluar di dasar sidebar, halaman pusat akun di /akun (route `account`), lonceng notifikasi dengan angka di topbar.
- Redesain halaman masuk & daftar: layout dua panel (label gantung + 3 langkah), ikon di field, tombol lihat kata sandi, kartu pilihan peran, state error dan loading. CSS baru: public/css/auth.css.
- Efek & animasi: layar pembuka (sekali per sesi), hero masuk bertahap, label barang melayang yang menghindari kursor, gelembung naik, cahaya mengikuti kursor, panel miring 3D, muncul saat scroll, angka statistik menghitung naik, transisi tirai antar halaman. File baru: public/css/fx.css, public/js/fx.js, public/js/landing-fx.js, resources/views/partials/fx.blade.php. Otomatis mati jika pengguna memilih 'kurangi gerakan'.
- Efek di HP: label barang tampil sebagai barisan kecil di bawah tombol hero; jari bertindak seperti kursor (label menghindar, cahaya mengikuti).
- Verifikasi "saya bukan robot" di login & daftar (tanpa layanan pihak ketiga): proof-of-work yang divalidasi server (App\Services\HumanCheck), tantangan sekali pakai & kedaluwarsa 10 menit, endpoint /human/challenge dibatasi 30 permintaan/menit. Fix: login hanya memakai email & password untuk Auth::attempt.
- Mode terang/gelap untuk seluruh web: tombol di navbar landing, halaman login/daftar, dan topbar aplikasi. Mengikuti pengaturan sistem, pilihan tersimpan, tanpa kedip saat dimuat. File: public/css/theme.css, public/js/theme.js, partials/head.blade.php.
- Logo baru (label harga + huruf S, navy & amber): public/logo/*, public/favicon.svg, komponen <x-logo>. Dipakai di navbar, sidebar, login/daftar, layar pembuka, dan favicon.
- Opsi MySQL (WAMP): perintah `php artisan sewain:copy-sqlite` untuk memindahkan data SQLite ke MySQL (SQLite hanya dibaca), panduan PINDAH-KE-MYSQL.txt, dan Schema::defaultStringLength(191). Default tetap SQLite.
