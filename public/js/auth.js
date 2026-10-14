(function () {
  var $ = function (s, r) { return (r || document).querySelector(s); };
  var $$ = function (s, r) { return [].slice.call((r || document).querySelectorAll(s)); };
  var latin = function (s) { return String(s).replace(/[۰-۹]/g, function (c) { return c.charCodeAt(0) - 1776; }).replace(/[٠-٩]/g, function (c) { return c.charCodeAt(0) - 1632; }); };

  $$('.seg [data-tab]').forEach(function (b) {
    b.addEventListener('click', function () {
      $$('.seg [data-tab]').forEach(function (x) { x.classList.toggle('on', x === b); });
      $$('[data-pane]').forEach(function (p) { p.hidden = p.getAttribute('data-pane') !== b.getAttribute('data-tab'); });
      var f = $('[data-pane]:not([hidden]) input'); if (f) f.focus();
    });
  });

  $$('.pw button').forEach(function (b) {
    b.addEventListener('click', function () {
      var i = b.parentNode.querySelector('input'), show = i.type === 'password';
      i.type = show ? 'text' : 'password'; b.textContent = show ? 'پنهان' : 'نمایش';
    });
  });

  $$('input[data-digits]').forEach(function (i) {
    i.addEventListener('input', function () { var p = i.selectionStart; i.value = latin(i.value).replace(/\D/g, ''); try { i.setSelectionRange(p, p); } catch (e) {} });
  });

  var otp = $('input.otp');
  if (otp) {
    otp.focus();
    otp.addEventListener('input', function () { if (otp.value.length === 6) otp.form.submit(); });
  }
  var cd = $('[data-countdown]');
  if (cd) {
    var left = +cd.getAttribute('data-countdown'), btn = $('[data-resend]'), label = $('[data-count-label]');
    var fa = function (n) { return String(n).replace(/\d/g, function (d) { return '۰۱۲۳۴۵۶۷۸۹'[d]; }); };
    var tick = function () {
      if (left <= 0) { btn.disabled = false; label.textContent = ''; return; }
      label.textContent = ' (' + fa(left) + ' ثانیه)'; left -= 1; setTimeout(tick, 1000);
    };
    btn.disabled = true; tick();
  }

  var radios = $$('input[name="person_type"]');
  if (radios.length) {
    var sync = function () {
      var legal = ($('input[name="person_type"]:checked') || {}).value === 'legal';
      $$('[data-for]').forEach(function (e) { e.hidden = e.getAttribute('data-for') !== (legal ? 'legal' : 'real'); });
      $$('[data-label-real]').forEach(function (e) { e.textContent = legal ? e.getAttribute('data-label-legal') : e.getAttribute('data-label-real'); });
    };
    radios.forEach(function (r) { r.addEventListener('change', sync); }); sync();
  }
})();
