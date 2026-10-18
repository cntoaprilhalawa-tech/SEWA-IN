{{-- Bagian <head> bersama: ikon, tema terang/gelap, dan efek. Dipasang di landing, auth, dan layout aplikasi. --}}
<link rel="icon" type="image/svg+xml" href="{{ asset('favicon.svg') }}">
<link rel="icon" type="image/png" sizes="32x32" href="{{ asset('logo/favicon-32.png') }}">
<link rel="apple-touch-icon" href="{{ asset('logo/apple-touch-icon.png') }}">
<meta name="theme-color" content="#1E2A4A">
<script>
/* Tema dipasang SEBELUM halaman digambar agar tidak berkedip: pilihan tersimpan, jika belum ada ikuti sistem. */
(function () {
  var t = null;
  try { t = localStorage.getItem('sewain-theme'); } catch (e) {}
  if (t !== 'light' && t !== 'dark') {
    t = (window.matchMedia && window.matchMedia('(prefers-color-scheme: dark)').matches) ? 'dark' : 'light';
  }
  var d = document.documentElement;
  d.setAttribute('data-theme', t);
  d.style.colorScheme = t;
})();
</script>
@include('partials.fx')
<link rel="stylesheet" href="{{ asset('css/theme.css') }}">
<script src="{{ asset('js/theme.js') }}" defer></script>
