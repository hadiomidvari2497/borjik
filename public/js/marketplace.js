/* اسلایدر تصاویر آگهی — بدون وابستگی؛ سازگار با راست‌به‌چپ.
   ساختار: [data-gallery] > .track > .slide ، .nav-btn.prev/.next ، .dots ، .thumbs ، .pos */
(function () {
  'use strict';

  function faDigits(n) {
    return String(n).replace(/\d/g, function (d) { return '۰۱۲۳۴۵۶۷۸۹'[d]; });
  }

  function clamp(i, n) { return Math.max(0, Math.min(n - 1, i)); }

  /* نمایش تمام‌صفحه‌ی تصاویر با <dialog>؛ جهت‌ها برای RTL (چپ = بعدی). */
  function initLightbox(root, getCurrent, sync) {
    var dlg = document.querySelector('[data-lightbox]');
    if (!dlg || typeof dlg.showModal !== 'function') { return; }
    var imgs = Array.prototype.slice.call(root.querySelectorAll('.slide img'));
    if (!imgs.length) { return; }
    var big = dlg.querySelector('[data-lb-img]');
    var prev = dlg.querySelector('[data-lb-prev]');
    var next = dlg.querySelector('[data-lb-next]');
    var pos = dlg.querySelector('[data-lb-pos]');
    var stage = dlg.querySelector('.lb-stage');
    var cur = 0;

    function show(i) {
      cur = clamp(i, imgs.length);
      var img = imgs[cur];
      big.src = img.currentSrc || img.src;
      big.alt = img.alt || '';
      if (pos) { pos.textContent = faDigits(cur + 1) + ' از ' + faDigits(imgs.length); }
      if (prev) { prev.disabled = cur === 0; }
      if (next) { next.disabled = cur === imgs.length - 1; }
      [cur - 1, cur + 1].forEach(function (n) {
        if (imgs[n]) { var pre = new Image(); pre.src = imgs[n].currentSrc || imgs[n].src; }
      });
    }
    function open(i) {
      show(i);
      if (!dlg.open) { dlg.showModal(); }
      document.documentElement.style.overflow = 'hidden';
    }
    function close() { if (dlg.open) { dlg.close(); } }

    dlg.addEventListener('close', function () {
      document.documentElement.style.overflow = '';
      if (sync) { sync(cur); }
    });
    Array.prototype.forEach.call(root.querySelectorAll('[data-lightbox-open]'), function (b) {
      b.addEventListener('click', function () { open(getCurrent ? getCurrent() : 0); });
    });
    imgs.forEach(function (img, i) {
      img.addEventListener('click', function (e) { if (!e.defaultPrevented) { open(i); } });
    });
    if (prev) { prev.addEventListener('click', function () { show(cur - 1); }); }
    if (next) { next.addEventListener('click', function () { show(cur + 1); }); }
    var closeBtn = dlg.querySelector('[data-lb-close]');
    if (closeBtn) { closeBtn.addEventListener('click', close); }
    dlg.addEventListener('click', function (e) { if (e.target === dlg || e.target === stage) { close(); } });
    dlg.addEventListener('keydown', function (e) {
      if (e.key === 'ArrowLeft') { e.preventDefault(); show(cur + 1); }
      else if (e.key === 'ArrowRight') { e.preventDefault(); show(cur - 1); }
      else if (e.key === 'Home') { e.preventDefault(); show(0); }
      else if (e.key === 'End') { e.preventDefault(); show(imgs.length - 1); }
    });
    // کشیدن انگشتی (RTL: کشیدن به چپ = تصویر بعدی)
    var sx = null;
    stage.addEventListener('pointerdown', function (e) { sx = e.clientX; });
    stage.addEventListener('pointerup', function (e) {
      if (sx === null) { return; }
      var dx = e.clientX - sx; sx = null;
      if (Math.abs(dx) > 50) { show(dx < 0 ? cur + 1 : cur - 1); }
    });
    stage.addEventListener('pointercancel', function () { sx = null; });
  }

  /* اشتراک‌گذاری: Web Share در موبایل، در غیر این صورت کپی لینک. */
  function initShare() {
    Array.prototype.forEach.call(document.querySelectorAll('[data-share]'), function (btn) {
      btn.addEventListener('click', function () {
        var url = btn.getAttribute('data-url') || window.location.href;
        var title = btn.getAttribute('data-title') || document.title;
        var label = btn.querySelector('span');
        function flash(msg) {
          btn.classList.add('done');
          var old = label ? label.textContent : '';
          if (label) { label.textContent = msg; }
          setTimeout(function () { btn.classList.remove('done'); if (label) { label.textContent = old; } }, 2000);
        }
        function fallback() {
          var ta = document.createElement('textarea');
          ta.value = url; ta.setAttribute('readonly', '');
          ta.style.position = 'fixed'; ta.style.opacity = '0';
          document.body.appendChild(ta); ta.select();
          try { document.execCommand('copy'); flash('لینک کپی شد'); } catch (e) { window.prompt('لینک آگهی:', url); }
          document.body.removeChild(ta);
        }
        if (navigator.share) { navigator.share({ title: title, url: url }).catch(function () {}); return; }
        if (navigator.clipboard && navigator.clipboard.writeText) {
          navigator.clipboard.writeText(url).then(function () { flash('لینک کپی شد'); }, fallback);
        } else { fallback(); }
      });
    });
  }

  /* توضیحات بلند: جمع‌شده با دکمه‌ی «نمایش بیشتر»؛ بدون JS کل متن دیده می‌شود. */
  function initCollapsible() {
    Array.prototype.forEach.call(document.querySelectorAll('[data-collapsible]'), function (box) {
      var btn = box.parentNode.querySelector('[data-collapse-toggle]');
      if (!btn) { return; }
      var natural = box.scrollHeight;
      box.classList.add('is-clamped');
      if (natural <= box.clientHeight + 8) { box.classList.remove('is-clamped'); return; }
      btn.hidden = false;
      var label = btn.querySelector('span');
      box.addEventListener('transitionend', function (e) {
        if (e.propertyName === 'max-height' && box.classList.contains('is-open')) { box.style.maxHeight = 'none'; }
      });
      btn.addEventListener('click', function () {
        var open = btn.getAttribute('aria-expanded') === 'true';
        if (!open) {
          box.style.maxHeight = box.scrollHeight + 'px';
          box.classList.remove('is-clamped'); box.classList.add('is-open');
        } else {
          box.style.maxHeight = box.scrollHeight + 'px';
          void box.offsetHeight;
          box.classList.remove('is-open'); box.classList.add('is-clamped');
          box.style.maxHeight = '';
        }
        btn.setAttribute('aria-expanded', open ? 'false' : 'true');
        if (label) { label.textContent = open ? 'نمایش بیشتر' : 'نمایش کمتر'; }
      });
    });
  }

  function initGallery(root) {
    var track = root.querySelector('.track');
    var slides = Array.prototype.slice.call(root.querySelectorAll('.slide'));
    var current = 0;
    var goTo = null;
    initLightbox(root, function () { return current; }, function (i) { if (goTo) { goTo(i); } });
    if (!track || slides.length < 2) { return; }

    var prev = root.querySelector('.nav-btn.prev');
    var next = root.querySelector('.nav-btn.next');
    var dotsBox = root.querySelector('.dots');
    var thumbs = Array.prototype.slice.call(root.querySelectorAll('.thumbs button'));
    var pos = root.querySelector('.pos');
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

    goTo = go;

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
    initShare();
    initCollapsible();
  }
  if (document.readyState === 'loading') { document.addEventListener('DOMContentLoaded', boot); } else { boot(); }
})();
