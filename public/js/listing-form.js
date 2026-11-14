/* فرم آگهی: نمایش فیلدهای مرتبط با نوع آگهی، قالب‌بندی مبلغ، مدیریت تصاویر و اعتبارسنجی سمت کاربر */
(function () {
  'use strict';
  var form = document.getElementById('listing-form');
  if (!form) { return; }

  var FA = '۰۱۲۳۴۵۶۷۸۹';
  function fa(s) { return String(s).replace(/\d/g, function (d) { return FA[d]; }); }
  function en(s) {
    return String(s).replace(/[۰-۹]/g, function (d) { return FA.indexOf(d); }).replace(/[٠-٩]/g, function (d) { return d.charCodeAt(0) - 1632; });
  }

  /* ───── نوع آگهی: فقط فیلدهای مرتبط ───── */
  var typeInputs = form.querySelectorAll('input[name="listing_type"]');
  function applyType() {
    var t = (form.querySelector('input[name="listing_type"]:checked') || {}).value;
    form.querySelectorAll('[data-show-for]').forEach(function (box) {
      var on = box.dataset.showFor.split(' ').indexOf(t) !== -1;
      box.hidden = !on;
      box.querySelectorAll('input').forEach(function (i) { i.disabled = !on; });
    });
  }
  typeInputs.forEach(function (i) { i.addEventListener('change', applyType); });
  applyType();

  /* ───── مبلغ: جداکننده‌ی هزارگان و خواندنی به حروف ───── */
  function words(n) {
    if (!n) { return ''; }
    var parts = [];
    var b = Math.floor(n / 1e9), m = Math.floor((n % 1e9) / 1e6), t = Math.floor((n % 1e6) / 1e3), r = n % 1e3;
    if (b) { parts.push(fa(b) + ' میلیارد'); }
    if (m) { parts.push(fa(m) + ' میلیون'); }
    if (t) { parts.push(fa(t) + ' هزار'); }
    if (r) { parts.push(fa(r)); }
    return parts.join(' و ') + ' تومان';
  }
  function fmtMoney(input) {
    var digits = en(input.value).replace(/\D/g, '');
    input.value = digits ? fa(digits.replace(/\B(?=(\d{3})+(?!\d))/g, '٬')) : '';
    var out = form.querySelector('[data-words-for="' + input.id + '"]');
    if (out) { out.textContent = words(Number(digits)); }
  }
  form.querySelectorAll('[data-money]').forEach(function (i) {
    if (i.value) { i.value = fa(i.value.replace(/,/g, '٬')); }
    var d = en(i.value).replace(/\D/g, '');
    var out = form.querySelector('[data-words-for="' + i.id + '"]');
    if (out) { out.textContent = words(Number(d)); }
    i.addEventListener('input', function () { fmtMoney(i); });
  });
  form.querySelectorAll('[data-digits]').forEach(function (i) {
    i.addEventListener('input', function () { i.value = en(i.value).replace(/[^\d.\-]/g, ''); });
  });

  /* ───── شمارنده‌ی توضیحات ───── */
  var desc = document.getElementById('f-description');
  var descCount = document.getElementById('desc-count');
  if (desc && descCount) {
    var upd = function () { descCount.textContent = fa(desc.value.length.toLocaleString('en-US').replace(/,/g, '٬')); };
    desc.addEventListener('input', upd); upd();
  }

  /* ───── تصاویر ───── */
  var input = document.getElementById('property-photos');
  var grid = document.getElementById('photo-grid');
  var drop = document.getElementById('photo-drop');
  var countEl = document.getElementById('photo-count');
  var errEl = document.getElementById('photo-error');
  var mainField = document.getElementById('main-photo');
  var MAX = Number(document.getElementById('photos').dataset.max) || 12;
  var MAX_SIZE = 5 * 1024 * 1024;
  var files = [];      // عکس‌های تازه
  var mainKey = null;  // مسیر عکس فعلی یا شیء File

  function existingTiles() { return Array.prototype.slice.call(grid.querySelectorAll('.ph[data-existing]:not([hidden])')); }
  function total() { return existingTiles().length + files.length; }

  function syncInput() {
    var dt = new DataTransfer();
    files.forEach(function (f) { dt.items.add(f); });
    input.files = dt.files;
  }

  function refresh() {
    // کاشی‌های تازه را از نو می‌سازیم
    grid.querySelectorAll('.ph[data-new]').forEach(function (n) { URL.revokeObjectURL(n.dataset.url); n.remove(); });
    files.forEach(function (f) {
      var tile = document.createElement('div');
      tile.className = 'ph'; tile.dataset.new = '1';
      var url = URL.createObjectURL(f); tile.dataset.url = url;
      var img = document.createElement('img'); img.src = url; img.alt = f.name;
      var star = document.createElement('button'); star.type = 'button'; star.className = 'ph-main'; star.textContent = '★';
      star.setAttribute('aria-label', 'انتخاب به‌عنوان تصویر اصلی');
      star.addEventListener('click', function () { mainKey = f; refresh(); });
      var del = document.createElement('button'); del.type = 'button'; del.className = 'ph-del'; del.setAttribute('aria-label', 'حذف تصویر');
      del.innerHTML = '<svg class="icon" aria-hidden="true"><use href="#i-x"/></svg>';
      del.addEventListener('click', function () { files.splice(files.indexOf(f), 1); if (mainKey === f) { mainKey = null; } syncInput(); refresh(); });
      tile.append(img, star, del);
      tile._file = f;
      grid.appendChild(tile);
    });
    // وضعیت «اصلی»: کلید انتخاب‌شده یا اولین تصویر
    var tiles = Array.prototype.slice.call(grid.querySelectorAll('.ph:not([hidden])'));
    var chosen = null;
    tiles.forEach(function (t) {
      if (t.dataset.existing && t.dataset.existing === mainKey) { chosen = t; }
      if (t._file && t._file === mainKey) { chosen = t; }
    });
    if (!chosen) { chosen = tiles[0] || null; mainKey = null; }
    tiles.forEach(function (t) { t.classList.toggle('is-main', t === chosen); });
    // مقدار ارسالی به سرور
    if (!chosen || chosen === tiles[0] && !mainKey) { mainField.value = ''; }
    else if (chosen.dataset.existing) { mainField.value = chosen.dataset.existing; }
    else { mainField.value = 'new:' + files.indexOf(chosen._file); }
    countEl.textContent = fa(total()) + ' از ' + fa(MAX);
    drop.disabled = total() >= MAX;
  }

  function addFiles(list) {
    errEl.textContent = '';
    Array.prototype.forEach.call(list, function (f) {
      if (total() >= MAX) { errEl.textContent = 'حداکثر ' + fa(MAX) + ' تصویر می‌توانید انتخاب کنید.'; return; }
      if (['image/jpeg', 'image/png', 'image/webp'].indexOf(f.type) === -1) { errEl.textContent = 'فقط تصویر JPG، PNG یا WebP مجاز است.'; return; }
      if (f.size > MAX_SIZE) { errEl.textContent = 'حجم هر تصویر باید کمتر از ۵ مگابایت باشد: ' + f.name; return; }
      if (files.some(function (x) { return x.name === f.name && x.size === f.size; })) { return; }
      files.push(f);
    });
    syncInput(); refresh();
  }

  drop.addEventListener('click', function () { input.click(); });
  input.addEventListener('change', function () {
    var picked = Array.prototype.slice.call(input.files);
    files = files.filter(function () { return true; });
    // مرورگر فهرست قبلی را عوض می‌کند؛ فقط موارد تازه را اضافه می‌کنیم
    var fresh = picked.filter(function (f) { return files.indexOf(f) === -1; });
    addFiles(fresh);
  });
  ['dragenter', 'dragover'].forEach(function (ev) { drop.addEventListener(ev, function (e) { e.preventDefault(); drop.classList.add('over'); }); });
  ['dragleave', 'drop'].forEach(function (ev) { drop.addEventListener(ev, function (e) { e.preventDefault(); drop.classList.remove('over'); }); });
  drop.addEventListener('drop', function (e) { if (e.dataTransfer) { addFiles(e.dataTransfer.files); } });

  grid.addEventListener('click', function (e) {
    var b = e.target.closest('button[data-act]');
    if (!b) { return; }
    var tile = b.closest('.ph');
    if (b.dataset.act === 'del') {
      tile.hidden = true; tile.querySelector('input').disabled = true;
      if (mainKey === tile.dataset.existing) { mainKey = null; }
    } else { mainKey = tile.dataset.existing; }
    refresh();
  });
  refresh();

  /* ───── اتصال به واحد: پر کردن خودکار ───── */
  var unitSel = document.getElementById('property-unit');
  if (unitSel && window.LISTING_UNITS) {
    unitSel.addEventListener('change', function () {
      var d = window.LISTING_UNITS[this.value];
      if (!d) { return; }
      Object.keys(d).forEach(function (name) {
        var f = form.querySelector('[name="' + name + '"]');
        if (f) { f.value = d[name] == null ? '' : d[name]; if (f.matches('[data-money]')) { fmtMoney(f); } }
      });
    });
  }

  /* ───── اعتبارسنجی سمت کاربر و ارسال ───── */
  function setError(fld, msg) {
    fld.classList.add('has-error');
    var e = fld.querySelector('.ferr');
    if (!e) { e = document.createElement('small'); e.className = 'ferr'; e.setAttribute('role', 'alert'); fld.appendChild(e); }
    e.textContent = msg;
  }
  function clearError(fld) {
    fld.classList.remove('has-error');
    var e = fld.querySelector('.ferr'); if (e) { e.remove(); }
  }
  form.addEventListener('input', function (ev) { var f = ev.target.closest('.fld'); if (f) { clearError(f); } });

  form.addEventListener('submit', function (ev) {
    var firstBad = null;
    form.querySelectorAll('.fld').forEach(function (f) {
      var c = f.querySelector('[required]');
      if (c && !c.disabled && !String(c.value).trim()) {
        setError(f, 'این فیلد الزامی است.');
        firstBad = firstBad || f;
      }
    });
    var t = (form.querySelector('input[name="listing_type"]:checked') || {}).value;
    var need = { sale: ['price'], rent: ['deposit', 'monthly_rent'], buy_request: [], rent_request: [] }[t] || [];
    if (need.length && need.every(function (n) { var el = form.querySelector('[name="' + n + '"]'); return el && !el.value.trim(); })) {
      var target = form.querySelector('[name="' + need[need.length - 1] + '"]').closest('.fld');
      setError(target, t === 'sale' ? 'برای آگهی فروش، قیمت را وارد کنید.' : 'ودیعه یا اجاره ماهانه را وارد کنید.');
      firstBad = firstBad || target;
    }
    if (firstBad) {
      ev.preventDefault();
      firstBad.scrollIntoView({ block: 'center', behavior: 'smooth' });
      var ctl = firstBad.querySelector('input,select,textarea'); if (ctl) { ctl.focus({ preventScroll: true }); }
      return;
    }
    var btn = document.getElementById('submit-btn');
    btn.setAttribute('aria-busy', 'true'); btn.textContent = 'در حال ارسال…';
  });

  var summary = document.getElementById('form-errors');
  if (summary) { summary.scrollIntoView({ block: 'center' }); summary.focus({ preventScroll: true }); }
})();
