/*
 * انتخابگر تاریخ شمسی (بدون وابستگی). هر <input class="jdate"> کاربر را مجبور به انتخاب از تقویم می‌کند.
 * مقدار فیلد به صورت «۱۴۰۵/۰۷/۲۲» ارسال می‌شود (سرور ارقام فارسی و لاتین را می‌پذیرد).
 * ویژگی‌ها: data-min / data-max (today یا ۱۴۰۵/۰۱/۰۱)، data-clearable="1" برای دکمه‌ی «پاک کردن».
 */
(function (root) {
  'use strict';
  var div = function (a, b) { return ~~(a / b); }, mod = function (a, b) { return a - ~~(a / b) * b; };
  var BREAKS = [-61, 9, 38, 199, 426, 686, 756, 818, 1111, 1181, 1210, 1635, 2060, 2097, 2192, 2262, 2324, 2394, 2456, 3178];

  function jalCal(jy, withoutLeap) {
    var bl = BREAKS.length, gy = jy + 621, leapJ = -14, jp = BREAKS[0], jm, jump, leap, leapG, march, n, i;
    for (i = 1; i < bl; i += 1) {
      jm = BREAKS[i]; jump = jm - jp;
      if (jy < jm) break;
      leapJ = leapJ + div(jump, 33) * 8 + div(mod(jump, 33), 4); jp = jm;
    }
    n = jy - jp;
    leapJ = leapJ + div(n, 33) * 8 + div(mod(n, 33) + 3, 4);
    if (mod(jump, 33) === 4 && jump - n === 4) leapJ += 1;
    leapG = div(gy, 4) - div((div(gy, 100) + 1) * 3, 4) - 150;
    march = 20 + leapJ - leapG;
    if (withoutLeap) return { gy: gy, march: march };
    if (jump - n < 6) n = n - jump + div(jump + 4, 33) * 33;
    leap = mod(mod(n + 1, 33) - 1, 4);
    if (leap === -1) leap = 4;
    return { leap: leap, gy: gy, march: march };
  }
  function g2d(gy, gm, gd) {
    var d = div((gy + div(gm - 8, 6) + 100100) * 1461, 4) + div(153 * mod(gm + 9, 12) + 2, 5) + gd - 34840408;
    return d - div(div(gy + 100100 + div(gm - 8, 6), 100) * 3, 4) + 752;
  }
  function d2g(jdn) {
    var j = 4 * jdn + 139361631;
    j = j + div(div(4 * jdn + 183187720, 146097) * 3, 4) * 4 - 3908;
    var i = div(mod(j, 1461), 4) * 5 + 308, gd = div(mod(i, 153), 5) + 1, gm = mod(div(i, 153), 12) + 1;
    return { gy: div(j, 1461) - 100100 + div(8 - gm, 6), gm: gm, gd: gd };
  }
  function j2d(jy, jm, jd) {
    var r = jalCal(jy, true);
    return g2d(r.gy, 3, r.march) + (jm - 1) * 31 - div(jm, 7) * (jm - 7) + jd - 1;
  }
  function d2j(jdn) {
    var gy = d2g(jdn).gy, jy = gy - 621, r = jalCal(jy, false), jdn1f = g2d(gy, 3, r.march), jd, jm, k = jdn - jdn1f;
    if (k >= 0) {
      if (k <= 185) { return { jy: jy, jm: 1 + div(k, 31), jd: mod(k, 31) + 1 }; }
      k -= 186;
    } else { jy -= 1; k += 179; if (r.leap === 1) k += 1; }
    jm = 7 + div(k, 30); jd = mod(k, 30) + 1;
    return { jy: jy, jm: jm, jd: jd };
  }
  function isLeap(jy) { return jalCal(jy, false).leap === 0; }
  function monthLength(jy, jm) { return jm <= 6 ? 31 : (jm <= 11 ? 30 : (isLeap(jy) ? 30 : 29)); }
  function toJalali(gy, gm, gd) { return d2j(g2d(gy, gm, gd)); }
  function toGregorian(jy, jm, jd) { return d2g(j2d(jy, jm, jd)); }

  var FA = '۰۱۲۳۴۵۶۷۸۹';
  var fa = function (n) { return String(n).replace(/\d/g, function (d) { return FA[d]; }); };
  var latin = function (s) {
    return String(s).replace(/[۰-۹]/g, function (c) { return c.charCodeAt(0) - 1776; }).replace(/[٠-٩]/g, function (c) { return c.charCodeAt(0) - 1632; });
  };
  var pad = function (n) { return (n < 10 ? '0' : '') + n; };
  var key = function (d) { return d.y * 10000 + d.m * 100 + d.d; };
  var MONTHS = ['فروردین', 'اردیبهشت', 'خرداد', 'تیر', 'مرداد', 'شهریور', 'مهر', 'آبان', 'آذر', 'دی', 'بهمن', 'اسفند'];
  var WEEK = ['ش', 'ی', 'د', 'س', 'چ', 'پ', 'ج'];

  function today() { var t = new Date(), j = toJalali(t.getFullYear(), t.getMonth() + 1, t.getDate()); return { y: j.jy, m: j.jm, d: j.jd }; }
  function parse(str) {
    var m = /^(\d{4})\/(\d{1,2})\/(\d{1,2})$/.exec(latin(str || '').trim());
    if (!m) return null;
    var d = { y: +m[1], m: +m[2], d: +m[3] };
    return (d.m >= 1 && d.m <= 12 && d.d >= 1 && d.d <= monthLength(d.y, d.m)) ? d : null;
  }
  function format(d) { return fa(d.y + '/' + pad(d.m) + '/' + pad(d.d)); }
  function bound(raw, fallback) { if (!raw) return fallback; if (raw === 'today') return today(); return parse(raw) || fallback; }

  var openPicker = null;

  function Picker(input) {
    var self = this, t = today();
    this.input = input;
    this.min = bound(input.getAttribute('data-min'), { y: t.y - 100, m: 1, d: 1 });
    this.max = bound(input.getAttribute('data-max'), { y: t.y + 10, m: 12, d: 29 });
    this.clearable = input.getAttribute('data-clearable') === '1';
    input.readOnly = true;
    input.setAttribute('autocomplete', 'off');
    var cur = parse(input.value); if (cur) input.value = format(cur);

    var wrap = document.createElement('div'); wrap.className = 'jd-wrap';
    input.parentNode.insertBefore(wrap, input); wrap.appendChild(input);
    var ic = document.createElement('span'); ic.className = 'jd-ic'; ic.setAttribute('aria-hidden', 'true'); ic.textContent = '📅'; wrap.appendChild(ic);
    this.wrap = wrap;

    this.pop = document.createElement('div'); this.pop.className = 'jd-pop'; this.pop.hidden = true;
    this.pop.setAttribute('role', 'dialog'); this.pop.setAttribute('aria-label', 'انتخاب تاریخ');
    wrap.appendChild(this.pop);

    var open = function () { self.open(); };
    input.addEventListener('click', open); input.addEventListener('focus', open); ic.addEventListener('click', open);
    input.addEventListener('keydown', function (e) { if (e.key === 'Enter' || e.key === ' ') { e.preventDefault(); open(); } });
  }

  Picker.prototype.open = function () {
    if (openPicker && openPicker !== this) openPicker.close();
    var v = parse(this.input.value) || this.clamp(today());
    this.view = { y: v.y, m: v.m };
    this.render(); this.pop.hidden = false; openPicker = this;
  };
  Picker.prototype.close = function () { this.pop.hidden = true; if (openPicker === this) openPicker = null; };
  Picker.prototype.clamp = function (d) { if (key(d) < key(this.min)) return this.min; if (key(d) > key(this.max)) return this.max; return d; };
  Picker.prototype.set = function (d) {
    this.input.value = d ? format(d) : '';
    this.input.dispatchEvent(new Event('change', { bubbles: true }));
    this.close();
  };

  Picker.prototype.render = function () {
    var self = this, v = this.view, sel = parse(this.input.value), t = today(), html = '';
    var yOpts = '';
    for (var y = this.min.y; y <= this.max.y; y++) yOpts += '<option value="' + y + '"' + (y === v.y ? ' selected' : '') + '>' + fa(y) + '</option>';
    var mOpts = MONTHS.map(function (n, i) { return '<option value="' + (i + 1) + '"' + (i + 1 === v.m ? ' selected' : '') + '>' + n + '</option>'; }).join('');

    html += '<div class="jd-head"><button type="button" class="jd-nav" data-go="-1" aria-label="ماه قبل">›</button>' +
      '<select class="jd-m" aria-label="ماه">' + mOpts + '</select><select class="jd-y" aria-label="سال">' + yOpts + '</select>' +
      '<button type="button" class="jd-nav" data-go="1" aria-label="ماه بعد">‹</button></div>';
    html += '<div class="jd-grid">' + WEEK.map(function (w) { return '<b>' + w + '</b>'; }).join('');

    var g = toGregorian(v.y, v.m, 1), first = (new Date(g.gy, g.gm - 1, g.gd).getDay() + 1) % 7, len = monthLength(v.y, v.m), i;
    for (i = 0; i < first; i++) html += '<i></i>';
    for (i = 1; i <= len; i++) {
      var d = { y: v.y, m: v.m, d: i }, off = key(d) < key(this.min) || key(d) > key(this.max);
      var cls = (sel && key(sel) === key(d) ? ' on' : '') + (key(t) === key(d) ? ' now' : '');
      html += '<button type="button" class="jd-d' + cls + '" data-d="' + i + '"' + (off ? ' disabled' : '') + '>' + fa(i) + '</button>';
    }
    html += '</div><div class="jd-foot"><button type="button" class="jd-t"' + (key(this.clamp(t)) === key(t) ? '' : ' disabled') + '>امروز</button>' +
      (this.clearable ? '<button type="button" class="jd-c">پاک کردن</button>' : '') + '<button type="button" class="jd-x">بستن</button></div>';
    this.pop.innerHTML = html;

    var pop = this.pop;
    pop.querySelector('.jd-m').onchange = function () { self.view.m = +this.value; self.render(); };
    pop.querySelector('.jd-y').onchange = function () { self.view.y = +this.value; self.render(); };
    [].forEach.call(pop.querySelectorAll('.jd-nav'), function (b) {
      b.onclick = function () {
        var n = self.view.m + (+b.getAttribute('data-go')), y = self.view.y;
        if (n < 1) { n = 12; y -= 1; } else if (n > 12) { n = 1; y += 1; }
        if (y < self.min.y || y > self.max.y) return;
        self.view = { y: y, m: n }; self.render();
      };
    });
    [].forEach.call(pop.querySelectorAll('.jd-d'), function (b) {
      b.onclick = function () { self.set({ y: v.y, m: v.m, d: +b.getAttribute('data-d') }); };
    });
    pop.querySelector('.jd-t').onclick = function () { self.set(t); };
    pop.querySelector('.jd-x').onclick = function () { self.close(); };
    var c = pop.querySelector('.jd-c'); if (c) c.onclick = function () { self.set(null); };
  };

  function isoToJalali(value) {
    var m = /^(\d{4})-(\d{2})-(\d{2})/.exec(String(value || ''));
    if (!m) return '';
    var j = toJalali(+m[1], +m[2], +m[3]);
    return format({ y: j.jy, m: j.jm, d: j.jd });
  }

  function jalaliToIso(value) {
    var j = parse(value);
    if (!j) return '';
    var g = toGregorian(j.y, j.m, j.d);
    return g.gy + '-' + pad(g.gm) + '-' + pad(g.gd);
  }

  // Keep the server-facing field in ISO/Gregorian format while users see a Jalali picker.
  function adaptNativeDateInputs() {
    [].forEach.call(document.querySelectorAll('input[type="date"], input[type="datetime-local"]'), function (el) {
      if (el.getAttribute('data-jdate-adapted') === '1' || !el.name) return;

      var kind = el.type;
      var name = el.name;
      var initial = el.value || '';
      var min = el.getAttribute('min') || '';
      var max = el.getAttribute('max') || '';
      var disabled = el.disabled;
      var hidden = document.createElement('input');
      hidden.type = 'hidden';
      hidden.name = name;
      hidden.value = '';
      hidden.disabled = disabled;
      hidden.setAttribute('data-jdate-value-for', name);
      el.removeAttribute('name');
      el.type = 'text';
      el.classList.add('jdate');
      el.setAttribute('data-jdate-adapted', '1');
      el.setAttribute('autocomplete', 'off');
      el.setAttribute('placeholder', 'انتخاب تاریخ شمسی');
      if (min) el.setAttribute('data-min', isoToJalali(min));
      if (max) el.setAttribute('data-max', isoToJalali(max));
      el.removeAttribute('min');
      el.removeAttribute('max');

      var time = null;
      if (kind === 'datetime-local') {
        time = document.createElement('input');
        time.type = 'time';
        time.className = 'jd-native-time';
        time.setAttribute('aria-label', 'ساعت');
        time.value = initial.length >= 16 ? initial.slice(11, 16) : '';
        time.disabled = disabled;
        el.insertAdjacentElement('afterend', time);
      }
      if (time) time.insertAdjacentElement('afterend', hidden);
      else el.insertAdjacentElement('afterend', hidden);

      el.value = isoToJalali(initial);
      var sync = function () {
        var iso = jalaliToIso(el.value);
        hidden.value = iso ? iso + (time ? 'T' + (time.value || '00:00') : '') : '';
      };
      el.addEventListener('change', sync);
      if (time) {
        time.addEventListener('change', sync);
        time.addEventListener('input', sync);
      }
      if (el.form) {
        el.form.addEventListener('reset', function () {
          window.setTimeout(function () {
            el.value = isoToJalali(initial);
            if (time) time.value = initial.length >= 16 ? initial.slice(11, 16) : '';
            sync();
          }, 0);
        });
      }
      sync();
    });
  }

  function init() {
    adaptNativeDateInputs();
    [].forEach.call(document.querySelectorAll('input.jdate'), function (el) {
      if (!el.__jd) el.__jd = new Picker(el);
    });
  }
  if (typeof document !== 'undefined') {
    document.addEventListener('click', function (e) { if (openPicker && !openPicker.wrap.contains(e.target)) openPicker.close(); });
    document.addEventListener('keydown', function (e) { if (e.key === 'Escape' && openPicker) openPicker.close(); });
    if (document.readyState === 'loading') document.addEventListener('DOMContentLoaded', init); else init();
  }
  root.JDate = { toJalali: toJalali, toGregorian: toGregorian, monthLength: monthLength, isLeap: isLeap, parse: parse, format: format, init: init };
  if (typeof module !== 'undefined' && module.exports) module.exports = root.JDate;
})(typeof window !== 'undefined' ? window : globalThis);
