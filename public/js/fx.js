/* SEWA-IN: efek umum (layar pembuka, muncul saat scroll, hitung naik, transisi halaman) */
(function () {
  'use strict';
  var d = document.documentElement;
  window.__fxReady = true;
  if (!d.classList.contains('fx')) { return; }

  var started = false;

  /* ---------- Mulai: dipanggil setelah tirai/layar pembuka terbuka ---------- */
  function go() {
    if (started) { return; }
    started = true;
    d.classList.add('fx-go');
    startReveals();
    document.dispatchEvent(new CustomEvent('fx:go'));
  }

  /* ---------- Judul kata demi kata ---------- */
  function splitWords(el) {
    var i = 0;
    (function walk(node) {
      Array.prototype.slice.call(node.childNodes).forEach(function (n) {
        if (n.nodeType === 3) {
          var frag = document.createDocumentFragment();
          n.textContent.split(/(\s+)/).forEach(function (part) {
            if (!part) { return; }
            if (/^\s+$/.test(part)) { frag.appendChild(document.createTextNode(' ')); return; }
            var w = document.createElement('span'); w.className = 'fx-w';
            var s = document.createElement('span'); s.style.setProperty('--i', i++); s.textContent = part;
            w.appendChild(s); frag.appendChild(w);
          });
          node.replaceChild(frag, n);
        } else if (n.nodeType === 1 && n.tagName !== 'BR') {
          walk(n);
        }
      });
    })(el);
  }
  Array.prototype.forEach.call(document.querySelectorAll('[data-reveal="words"]'), splitWords);

  /* ---------- Tandai elemen landing agar muncul bertahap saat di-scroll ---------- */
  if (document.body.classList.contains('fx-landing')) {
    var auto = '.search-card,.stat-box,.chip,.block .eyebrow,.block .title,.block .sub,.block .grid > *,.empty,.step,.trust > div,.cta-band,.more';
    Array.prototype.forEach.call(document.querySelectorAll(auto), function (el) {
      if (el.hasAttribute('data-reveal')) { return; }
      el.setAttribute('data-reveal', '');
      var idx = Array.prototype.indexOf.call(el.parentNode.children, el);
      el.style.setProperty('--d', Math.min(idx, 8) * 70 + 'ms');
    });
  }

  function countUp(b) {
    if (!b || b.__fxDone) { return; }
    b.__fxDone = true;
    var raw = b.textContent.trim();
    var n = parseInt(raw.replace(/\D/g, ''), 10);
    if (isNaN(n) || n <= 0) { return; }
    var t0 = null, dur = 1400;
    b.textContent = '0';
    (function step(ts) {
      if (t0 === null) { t0 = ts; }
      var p = Math.min((ts - t0) / dur, 1), e = 1 - Math.pow(1 - p, 3);
      b.textContent = Math.round(n * e).toLocaleString('id-ID');
      if (p < 1) { requestAnimationFrame(step); } else { b.textContent = raw; }
    })(performance.now());
  }

  function show(el) {
    el.classList.add('in');
    if (el.classList.contains('stat-box')) { countUp(el.querySelector('b')); }
  }

  function startReveals() {
    var els = Array.prototype.slice.call(document.querySelectorAll('[data-reveal]'));
    els.forEach(function (el) {
      var dl = el.getAttribute('data-delay');
      if (dl) { el.style.setProperty('--d', dl + 'ms'); }
    });
    if (!('IntersectionObserver' in window)) { els.forEach(show); return; }
    var io = new IntersectionObserver(function (entries) {
      entries.forEach(function (e) {
        if (e.isIntersecting) { show(e.target); io.unobserve(e.target); }
      });
    }, { threshold: 0.12, rootMargin: '0px 0px -6% 0px' });
    els.forEach(function (el) { io.observe(el); });
  }

  /* ---------- Navbar berubah saat di-scroll ---------- */
  var nav = document.querySelector('header.nav');
  if (nav) {
    var onScroll = function () { nav.classList.toggle('scrolled', window.scrollY > 24); };
    window.addEventListener('scroll', onScroll, { passive: true });
    onScroll();
  }

  /* ---------- Layar pembuka ---------- */
  function intro() {
    try { sessionStorage.setItem('fxIntro', '1'); } catch (e) {}
    var s = document.createElement('div');
    s.className = 'fx-splash';
    s.setAttribute('aria-hidden', 'true');
    s.innerHTML =
      '<div class="fx-splash-tag"><span class="hole"></span>' +
      '<div class="body"><svg class="splash-mark" xmlns="http://www.w3.org/2000/svg" viewBox="2 9 60 46" width="78" height="60" aria-hidden="true"><path d="M20 12h36a4 4 0 0 1 4 4v32a4 4 0 0 1-4 4H20L4 32Z" fill="#E8A33D"/><circle cx="17.5" cy="32" r="3.6" fill="#1E2A4A"/><path transform="translate(41.5 32) scale(.9) translate(-32 -38.75)" d="M41 32C41 28 37 25.5 32 25.5S23 28 23 32c0 4.5 4.5 5.8 9 6.8s9 2.2 9 6.7c0 4-4 6.5-9 6.5s-9-2.5-9-6.5" fill="none" stroke="#1E2A4A" stroke-width="6.4" stroke-linecap="round" stroke-linejoin="round"/></svg><b>SEWA<i>-IN</i></b><small>Sewa barang antar mahasiswa</small></div></div>' +
      '<div class="fx-splash-bar"><i></i></div>';
    document.body.appendChild(s);
    d.classList.add('fx-lock');
    d.classList.remove('fx-intro');

    var done = false, timer;
    function out() {
      if (done) { return; }
      done = true;
      clearTimeout(timer);
      s.classList.add('out');
      setTimeout(go, 160);
      setTimeout(function () { d.classList.remove('fx-lock'); if (s.parentNode) { s.parentNode.removeChild(s); } }, 1000);
    }
    timer = setTimeout(out, 1750);
    ['click', 'keydown', 'touchstart'].forEach(function (ev) {
      window.addEventListener(ev, out, { once: true, passive: true });
    });
  }

  if (d.classList.contains('fx-cover')) {
    // Tiba lewat transisi halaman: tirai naik membuka halaman
    requestAnimationFrame(function () {
      requestAnimationFrame(function () {
        d.classList.add('fx-uncover');
        setTimeout(go, 240);
        setTimeout(function () { d.classList.remove('fx-cover', 'fx-uncover'); }, 750);
      });
    });
  } else if (d.classList.contains('fx-intro')) {
    intro();
  } else {
    requestAnimationFrame(go);
  }

  /* ---------- Transisi tirai antar halaman ---------- */
  document.addEventListener('click', function (e) {
    if (e.defaultPrevented || e.button !== 0 || e.metaKey || e.ctrlKey || e.shiftKey || e.altKey) { return; }
    var a = e.target.closest && e.target.closest('a[href]');
    if (!a || (a.target && a.target !== '_self') || a.hasAttribute('download') || a.hasAttribute('data-no-fx')) { return; }
    var url;
    try { url = new URL(a.href, location.href); } catch (err) { return; }
    if (url.origin !== location.origin || (url.protocol !== 'http:' && url.protocol !== 'https:')) { return; }
    if (url.pathname === location.pathname && url.search === location.search) { return; }
    e.preventDefault();
    try { sessionStorage.setItem('fxT', '1'); } catch (err) {}
    d.classList.add('fx-leave');
    setTimeout(function () { location.href = url.href; }, 380);
    setTimeout(function () { d.classList.remove('fx-leave'); }, 5000);
  });
  window.addEventListener('pageshow', function (e) {
    if (e.persisted) { d.classList.remove('fx-leave'); try { sessionStorage.removeItem('fxT'); } catch (err) {} }
  });
})();
