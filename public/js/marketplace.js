/* اسلایدر تصاویر آگهی — بدون وابستگی؛ سازگار با راست‌به‌چپ.
   ساختار: [data-gallery] > .track > .slide ، .nav-btn.prev/.next ، .dots ، .thumbs ، .pos */
(function () {
  'use strict';

  function faDigits(n) {
    return String(n).replace(/\d/g, function (d) { return '۰۱۲۳۴۵۶۷۸۹'[d]; });
  }

  function initGallery(root) {
    var track = root.querySelector('.track');
    var slides = Array.prototype.slice.call(root.querySelectorAll('.slide'));
    if (!track || slides.length < 2) { return; }

    var prev = root.querySelector('.nav-btn.prev');
    var next = root.querySelector('.nav-btn.next');
    var dotsBox = root.querySelector('.dots');
    var thumbs = Array.prototype.slice.call(root.querySelectorAll('.thumbs button'));
    var pos = root.querySelector('.pos');
    var current = 0;
    var ticking = false;

    var dots = slides.map(function (_, i) {
      if (!dotsBox) { return null; }
      var b = document.createElement('button');
      b.type = 'button';
      b.setAttribute('aria-label', 'تصویر ' + faDigits(i + 1));
      b.addEventListener('click', function () { go(i); });
      dotsBox.appendChild(b);
      return b;
    });

    function render() {
      if (pos) { pos.textContent = faDigits(current + 1) + ' از ' + faDigits(slides.length); }
      if (prev) { prev.disabled = current === 0; }
      if (next) { next.disabled = current === slides.length - 1; }
      dots.forEach(function (d, i) { if (d) { d.setAttribute('aria-current', i === current ? 'true' : 'false'); } });
      thumbs.forEach(function (t, i) {
        var active = i === current;
        t.setAttribute('aria-current', active ? 'true' : 'false');
        if (active && t.parentNode) {
          var box = t.parentNode;
          var target = t.offsetLeft - (box.clientWidth - t.clientWidth) / 2;
          if (box.scrollTo) { box.scrollTo({ left: target, behavior: 'smooth' }); }
        }
      });
    }

    // موقعیت اسلاید فعلی را از روی اسکرول افقی (در RTL مقدار منفی است) حساب می‌کنیم.
    function indexFromScroll() {
      var w = track.clientWidth || 1;
      var i = Math.round(Math.abs(track.scrollLeft) / w);
      return Math.max(0, Math.min(slides.length - 1, i));
    }

    function go(i) {
      i = Math.max(0, Math.min(slides.length - 1, i));
      var w = track.clientWidth;
      // RTL: اسلاید اول سمت راست است، پس اسکرول به سمت منفی می‌رود.
      var dir = getComputedStyle(track).direction === 'rtl' ? -1 : 1;
      track.scrollTo({ left: dir * i * w, behavior: 'smooth' });
      current = i;
      render();
    }

    track.addEventListener('scroll', function () {
      if (ticking) { return; }
      ticking = true;
      window.requestAnimationFrame(function () {
        ticking = false;
        var i = indexFromScroll();
        if (i !== current) { current = i; render(); }
      });
    }, { passive: true });

    if (prev) { prev.addEventListener('click', function () { go(current - 1); }); }
    if (next) { next.addEventListener('click', function () { go(current + 1); }); }
    thumbs.forEach(function (t, i) { t.addEventListener('click', function () { go(i); }); });

    // کلیدهای جهت‌دار (در RTL چپ = بعدی)
    root.addEventListener('keydown', function (e) {
      if (e.key === 'ArrowLeft') { e.preventDefault(); go(current + 1); }
      else if (e.key === 'ArrowRight') { e.preventDefault(); go(current - 1); }
      else if (e.key === 'Home') { e.preventDefault(); go(0); }
      else if (e.key === 'End') { e.preventDefault(); go(slides.length - 1); }
    });

    // درگ با ماوس روی دسکتاپ (لمس و تاچ‌پد خودشان اسکرول می‌کنند)
    var down = false, startX = 0, startScroll = 0, moved = false;
    track.addEventListener('pointerdown', function (e) {
      if (e.pointerType !== 'mouse' || e.button !== 0) { return; }
      down = true; moved = false; startX = e.clientX; startScroll = track.scrollLeft;
      track.style.scrollSnapType = 'none';
      track.style.scrollBehavior = 'auto';
    });
    window.addEventListener('pointermove', function (e) {
      if (!down) { return; }
      var dx = e.clientX - startX;
      if (Math.abs(dx) > 3) { moved = true; }
      track.scrollLeft = startScroll - dx;
    });
    window.addEventListener('pointerup', function () {
      if (!down) { return; }
      down = false;
      track.style.scrollSnapType = '';
      track.style.scrollBehavior = '';
      go(indexFromScroll());
    });
    track.addEventListener('click', function (e) { if (moved) { e.preventDefault(); moved = false; } }, true);
    track.addEventListener('dragstart', function (e) { e.preventDefault(); });

    window.addEventListener('resize', function () { go(current); });

    render();
  }

  function boot() {
    Array.prototype.forEach.call(document.querySelectorAll('[data-gallery]'), initGallery);
  }
  if (document.readyState === 'loading') { document.addEventListener('DOMContentLoaded', boot); } else { boot(); }
})();
