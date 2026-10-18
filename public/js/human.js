/* SEWA-IN: widget "Verifikasi bahwa saya manusia" (proof-of-work, divalidasi di server) */
(function () {
  'use strict';

  /* ---------- SHA-256 murni JS (jalan juga di http biasa, tanpa crypto.subtle) ---------- */
  var K = [], H0 = [];
  (function () {
    for (var p = 2, n = 0; n < 64; p++) {
      var prime = true;
      for (var j = 2; j * j <= p; j++) { if (p % j === 0) { prime = false; break; } }
      if (!prime) { continue; }
      if (n < 8) { H0.push((Math.pow(p, 0.5) * 4294967296) | 0); }
      K.push((Math.pow(p, 1 / 3) * 4294967296) | 0);
      n++;
    }
  })();
  var W = new Array(64);
  function sha256(str) {
    var bytes = [], i;
    for (i = 0; i < str.length; i++) { bytes.push(str.charCodeAt(i) & 255); }
    var bitLen = bytes.length * 8;
    bytes.push(0x80);
    while (bytes.length % 64 !== 56) { bytes.push(0); }
    bytes.push(0, 0, 0, 0, (bitLen >>> 24) & 255, (bitLen >>> 16) & 255, (bitLen >>> 8) & 255, bitLen & 255);
    var h = H0.slice();
    for (var off = 0; off < bytes.length; off += 64) {
      for (i = 0; i < 16; i++) {
        W[i] = (bytes[off + i * 4] << 24) | (bytes[off + i * 4 + 1] << 16) | (bytes[off + i * 4 + 2] << 8) | bytes[off + i * 4 + 3];
      }
      for (i = 16; i < 64; i++) {
        var a15 = W[i - 15], a2 = W[i - 2];
        var s0 = ((a15 >>> 7) | (a15 << 25)) ^ ((a15 >>> 18) | (a15 << 14)) ^ (a15 >>> 3);
        var s1 = ((a2 >>> 17) | (a2 << 15)) ^ ((a2 >>> 19) | (a2 << 13)) ^ (a2 >>> 10);
        W[i] = (W[i - 16] + s0 + W[i - 7] + s1) | 0;
      }
      var a = h[0], b = h[1], c = h[2], d = h[3], e = h[4], f = h[5], g = h[6], hh = h[7];
      for (i = 0; i < 64; i++) {
        var S1 = ((e >>> 6) | (e << 26)) ^ ((e >>> 11) | (e << 21)) ^ ((e >>> 25) | (e << 7));
        var ch = (e & f) ^ (~e & g);
        var t1 = (hh + S1 + ch + K[i] + W[i]) | 0;
        var S0 = ((a >>> 2) | (a << 30)) ^ ((a >>> 13) | (a << 19)) ^ ((a >>> 22) | (a << 10));
        var maj = (a & b) ^ (a & c) ^ (b & c);
        var t2 = (S0 + maj) | 0;
        hh = g; g = f; f = e; e = (d + t1) | 0; d = c; c = b; b = a; a = (t1 + t2) | 0;
      }
      h[0] = (h[0] + a) | 0; h[1] = (h[1] + b) | 0; h[2] = (h[2] + c) | 0; h[3] = (h[3] + d) | 0;
      h[4] = (h[4] + e) | 0; h[5] = (h[5] + f) | 0; h[6] = (h[6] + g) | 0; h[7] = (h[7] + hh) | 0;
    }
    var hex = '';
    for (i = 0; i < 8; i++) { hex += ('00000000' + (h[i] >>> 0).toString(16)).slice(-8); }
    return hex;
  }
  window.__sewainSha256 = sha256; // dipakai untuk pengujian

  /* ---------- Mencari jawaban tantangan (per potongan agar layar tetap mulus) ---------- */
  function solve(ch, done, fail) {
    var n = 0, max = ch.maxnumber;
    (function step() {
      var end = Math.min(n + 2500, max + 1);
      for (; n < end; n++) {
        if (sha256(ch.salt + n) === ch.challenge) { done(n); return; }
      }
      if (n > max) { fail(); return; }
      setTimeout(step, 0);
    })();
  }

  var TEXT = {
    idle: ['Verifikasi bahwa saya manusia', ''],
    loading: ['Memverifikasi…', 'Sebentar saja'],
    ok: ['Terverifikasi', 'Kamu manusia, silakan lanjut'],
    error: ['Verifikasi gagal', 'Ketuk untuk mencoba lagi']
  };

  function init(root) {
    var box = root.querySelector('.human-box');
    var input = root.querySelector('input[name="human"]');
    var label = root.querySelector('[data-human-text]');
    var sub = root.querySelector('[data-human-sub]');
    var msg = root.querySelector('[data-human-msg]');
    var form = root.closest('form');
    var url = root.getAttribute('data-url');
    var busy = false, expireTimer;

    function set(state, extraMsg) {
      root.setAttribute('data-state', state);
      box.setAttribute('aria-checked', state === 'ok' ? 'true' : 'false');
      label.textContent = TEXT[state][0];
      sub.textContent = TEXT[state][1];
      if (extraMsg !== undefined) { msg.textContent = extraMsg; }
    }

    function run() {
      if (busy || root.getAttribute('data-state') === 'ok') { return; }
      busy = true; input.value = ''; set('loading', '');
      var t0 = Date.now();
      fetch(url, { headers: { 'Accept': 'application/json' }, cache: 'no-store', credentials: 'same-origin' })
        .then(function (r) { if (!r.ok) { throw new Error('http ' + r.status); } return r.json(); })
        .then(function (ch) {
          solve(ch, function (n) {
            var payload = btoa(JSON.stringify({ salt: ch.salt, number: n, challenge: ch.challenge, signature: ch.signature }));
            // tahan sebentar agar animasi "memverifikasi" sempat terlihat
            setTimeout(function () {
              input.value = payload; busy = false; set('ok', '');
              clearTimeout(expireTimer);
              expireTimer = setTimeout(reset, 9 * 60 * 1000); // tantangan kedaluwarsa 10 menit
            }, Math.max(0, 650 - (Date.now() - t0)));
          }, function () { busy = false; set('error', 'Tidak dapat menyelesaikan tantangan. Muat ulang halaman.'); });
        })
        .catch(function () { busy = false; set('error', 'Tidak dapat terhubung ke server. Periksa koneksi, lalu coba lagi.'); });
    }

    function reset() { busy = false; input.value = ''; set('idle', ''); }

    box.addEventListener('click', run);
    if (form) {
      form.addEventListener('submit', function (e) {
        if (!input.value) {
          e.preventDefault();
          if (!busy) { set('idle', 'Centang verifikasi ini dulu sebelum melanjutkan.'); }
          root.classList.remove('shake'); void root.offsetWidth; root.classList.add('shake');
          box.focus();
        }
      });
    }
    window.addEventListener('pageshow', function (e) { if (e.persisted) { reset(); } });
    set('idle');
  }

  Array.prototype.forEach.call(document.querySelectorAll('[data-human]'), init);
})();
