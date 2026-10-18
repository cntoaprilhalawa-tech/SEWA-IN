/* SEWA-IN: efek hero landing (label melayang, gelembung, cahaya kursor, panel 3D) */
(function () {
  'use strict';
  var d = document.documentElement;
  if (!d.classList.contains('fx')) { return; }
  var hero = document.querySelector('.hero');
  if (!hero) { return; }

  var tags = Array.prototype.slice.call(hero.querySelectorAll('.ftag'));
  var glow = hero.querySelector('.fx-glow');
  var bubbles = hero.querySelector('.fx-bubbles');
  var panel = hero.querySelector('.hero-panel');
  var fine = window.matchMedia('(hover: hover) and (pointer: fine)').matches;

  var mouse = { x: 0, y: 0, px: -9999, py: -9999 };
  var cur = { x: 0, y: 0 };
  var glowPos = { x: 0, y: 0, tx: 0, ty: 0 };
  var inView = true;

  tags.forEach(function (t) { t.__rx = 0; t.__ry = 0; });

  if ('IntersectionObserver' in window) {
    new IntersectionObserver(function (en) { inView = en[0].isIntersecting; }).observe(hero);
  }

  function setPoint(cx, cy) {
    var r = hero.getBoundingClientRect();
    mouse.x = (cx - r.left) / r.width - 0.5;
    mouse.y = (cy - r.top) / r.height - 0.5;
    mouse.px = cx; mouse.py = cy;
    glowPos.tx = cx - r.left; glowPos.ty = cy - r.top;
    hero.classList.add('fx-hover');
  }
  function clearPoint() {
    mouse.x = 0; mouse.y = 0; mouse.px = -9999; mouse.py = -9999;
    hero.classList.remove('fx-hover');
  }
  if (fine) {
    hero.addEventListener('mousemove', function (e) { setPoint(e.clientX, e.clientY); });
    hero.addEventListener('mouseleave', clearPoint);
  }
  // Layar sentuh: jari bertindak seperti kursor (label menghindar, cahaya mengikuti)
  var touchTimer;
  function onTouch(e) {
    var t = e.touches && e.touches[0];
    if (!t) { return; }
    clearTimeout(touchTimer);
    setPoint(t.clientX, t.clientY);
  }
  hero.addEventListener('touchstart', onTouch, { passive: true });
  hero.addEventListener('touchmove', onTouch, { passive: true });
  function onTouchEnd() { clearTimeout(touchTimer); touchTimer = setTimeout(clearPoint, 600); }
  hero.addEventListener('touchend', onTouchEnd, { passive: true });
  hero.addEventListener('touchcancel', onTouchEnd, { passive: true });

  function tick() {
    if (inView && !document.hidden) {
      var time = Date.now() * 0.001;
      cur.x += (mouse.x - cur.x) * 0.06;
      cur.y += (mouse.y - cur.y) * 0.06;

      tags.forEach(function (t, i) {
        var depth = parseFloat(t.getAttribute('data-depth')) || 0.5;
        var rect = t.getBoundingClientRect();
        var cx = rect.left + rect.width / 2, cy = rect.top + rect.height / 2;
        var dx = mouse.px - cx, dy = mouse.py - cy;
        var dist = Math.sqrt(dx * dx + dy * dy) || 1;
        var tx = 0, ty = 0;
        if (dist < 260) {
          var force = (260 - dist) / 260;
          tx = (dx / dist) * force * -70;
          ty = (dy / dist) * force * -70;
        }
        t.__rx += (tx - t.__rx) * 0.1;
        t.__ry += (ty - t.__ry) * 0.1;
        var dur = [6, 8, 7, 9, 6.5, 7.5][i % 6];
        var ph = (time + i * 0.9) * (Math.PI * 2 / dur);
        var fy = Math.sin(ph) * 10, fx = Math.cos(ph * 0.6) * 5;
        t.style.transform = 'translate(' + (cur.x * depth * -46 + t.__rx + fx) + 'px,' + (cur.y * depth * -46 + t.__ry + fy) + 'px)';
      });

      if (glow) {
        glowPos.x += (glowPos.tx - glowPos.x) * 0.12;
        glowPos.y += (glowPos.ty - glowPos.y) * 0.12;
        glow.style.transform = 'translate(' + glowPos.x + 'px,' + glowPos.y + 'px)';
      }
      if (panel) {
        panel.style.transform = 'perspective(900px) rotateY(' + (cur.x * -7) + 'deg) rotateX(' + (cur.y * 6) + 'deg)';
      }
    }
    requestAnimationFrame(tick);
  }
  requestAnimationFrame(tick);

  /* ---------- Gelembung naik ---------- */
  function setH() { if (bubbles) { bubbles.style.setProperty('--h', (hero.offsetHeight + 60) + 'px'); } }
  setH();
  window.addEventListener('resize', setH);

  var count = 0;
  function spawn() {
    if (!bubbles || !inView || document.hidden || count > 16) { return; }
    var b = document.createElement('i');
    b.className = 'fx-bubble';
    var size = Math.random() * 20 + 8;
    b.style.width = b.style.height = size + 'px';
    b.style.left = Math.random() * 100 + '%';
    b.style.setProperty('--t', (Math.random() * 6 + 7) + 's');
    b.style.setProperty('--dx', ((Math.random() - 0.5) * 80) + 'px');
    count++;
    b.addEventListener('animationend', function () { count--; if (b.parentNode) { b.parentNode.removeChild(b); } });
    bubbles.appendChild(b);
  }
  document.addEventListener('fx:go', function () {
    for (var i = 0; i < 5; i++) { setTimeout(spawn, i * 260); }
    setInterval(spawn, 520);
  });
})();
