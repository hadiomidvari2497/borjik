/* رفتارهای مشترک بازار: پنجره‌ی تأیید برای فرم‌های data-confirm و بستن منوهای سه‌نقطه */
(function () {
  'use strict';

  // بستن منوی سه‌نقطه با کلیک بیرون یا Esc
  document.addEventListener('click', function (e) {
    document.querySelectorAll('details.kebab[open]').forEach(function (d) { if (!d.contains(e.target)) { d.open = false; } });
  });
  document.addEventListener('keydown', function (e) {
    if (e.key === 'Escape') { document.querySelectorAll('details.kebab[open]').forEach(function (d) { d.open = false; }); }
  });

  function ask(message, okLabel, danger) {
    return new Promise(function (resolve) {
      var back = document.createElement('div');
      back.className = 'cdlg'; back.setAttribute('role', 'alertdialog'); back.setAttribute('aria-modal', 'true');
      var box = document.createElement('div'); box.className = 'cdlg-box';
      var p = document.createElement('p'); p.textContent = message;
      var row = document.createElement('div'); row.className = 'cdlg-row';
      var no = document.createElement('button'); no.type = 'button'; no.className = 'btn light'; no.textContent = 'انصراف';
      var yes = document.createElement('button'); yes.type = 'button'; yes.className = 'btn' + (danger ? ' danger' : ''); yes.textContent = okLabel || 'تأیید';
      row.append(yes, no); box.append(p, row); back.appendChild(box);
      var prev = document.activeElement;
      function done(v) { document.removeEventListener('keydown', onKey); back.remove(); if (prev && prev.focus) { prev.focus(); } resolve(v); }
      function onKey(ev) { if (ev.key === 'Escape') { done(false); } }
      yes.addEventListener('click', function () { done(true); });
      no.addEventListener('click', function () { done(false); });
      back.addEventListener('click', function (ev) { if (ev.target === back) { done(false); } });
      document.addEventListener('keydown', onKey);
      document.body.appendChild(back);
      no.focus();
    });
  }

  document.addEventListener('submit', function (e) {
    var form = e.target;
    var msg = form.getAttribute && form.getAttribute('data-confirm');
    if (!msg || form.dataset.confirmed) { return; }
    e.preventDefault();
    ask(msg, form.getAttribute('data-confirm-ok'), form.hasAttribute('data-danger')).then(function (ok) {
      if (ok) { form.dataset.confirmed = '1'; form.submit(); }
    });
  });
})();
