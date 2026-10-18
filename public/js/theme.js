/* SEWA-IN: tombol tema terang/gelap. Pilihan disimpan di localStorage ('sewain-theme'). */
(function () {
  'use strict';
  var d = document.documentElement;

  function current() { return d.getAttribute('data-theme') === 'dark' ? 'dark' : 'light'; }

  function sync(t) {
    Array.prototype.forEach.call(document.querySelectorAll('[data-theme-toggle]'), function (b) {
      b.setAttribute('aria-pressed', t === 'dark' ? 'true' : 'false');
      b.setAttribute('aria-label', t === 'dark' ? 'Ganti ke mode terang' : 'Ganti ke mode gelap');
    });
  }

  function apply(t, save) {
    d.classList.add('theme-anim');
    d.setAttribute('data-theme', t);
    d.style.colorScheme = t;
    if (save) { try { localStorage.setItem('sewain-theme', t); } catch (e) {} }
    sync(t);
    document.dispatchEvent(new CustomEvent('themechange', { detail: t }));
    setTimeout(function () { d.classList.remove('theme-anim'); }, 420);
  }

  document.addEventListener('click', function (e) {
    var b = e.target.closest && e.target.closest('[data-theme-toggle]');
    if (b) { apply(current() === 'dark' ? 'light' : 'dark', true); }
  });

  // Jika pengguna belum memilih sendiri, ikuti perubahan tema sistem secara langsung
  if (window.matchMedia) {
    var mq = window.matchMedia('(prefers-color-scheme: dark)');
    var onChange = function (e) {
      var saved = null;
      try { saved = localStorage.getItem('sewain-theme'); } catch (err) {}
      if (saved !== 'light' && saved !== 'dark') { apply(e.matches ? 'dark' : 'light', false); }
    };
    if (mq.addEventListener) { mq.addEventListener('change', onChange); } else if (mq.addListener) { mq.addListener(onChange); }
  }

  sync(current());
})();
