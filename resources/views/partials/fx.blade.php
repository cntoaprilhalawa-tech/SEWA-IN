{{-- Efek & animasi SEWA-IN: dimuat di landing, login/daftar, dan layout aplikasi.
     Semua efek mati otomatis jika pengguna memilih "kurangi gerakan" di sistemnya. --}}
<link rel="stylesheet" href="{{ asset('css/fx.css') }}">
<script>
(function () {
  var d = document.documentElement;
  try {
    if (window.matchMedia('(prefers-reduced-motion: reduce)').matches) { return; }
    d.classList.add('fx');
    var arrivedByTransition = false;
    try {
      if (sessionStorage.getItem('fxT')) { d.classList.add('fx-cover'); sessionStorage.removeItem('fxT'); arrivedByTransition = true; }
    } catch (e) {}
    // Layar pembuka hanya sekali per sesi, dan tidak diputar jika tiba lewat transisi halaman
    if (d.hasAttribute('data-fx-intro') && !arrivedByTransition) {
      var seen = false;
      try { seen = !!sessionStorage.getItem('fxIntro'); } catch (e) {}
      if (!seen) { d.classList.add('fx-intro'); }
    }
    // Pengaman: jika fx.js gagal dimuat, semua konten ditampilkan normal
    setTimeout(function () {
      if (!window.__fxReady) { d.classList.remove('fx', 'fx-intro', 'fx-cover'); }
    }, 2500);
  } catch (e) {}
})();
</script>
<script src="{{ asset('js/fx.js') }}" defer></script>
