/* اعلان‌ها و تأییدها — کاملاً لوکال
   • تاست‌ها: Bootstrap 5.3 Toast (vendor/bootstrap)
   • سؤال بله/خیر و دریافت متن: SweetAlert2 (vendor/sweetalert2)
   استفاده: <form data-confirm="..."> ، <form data-prompt="...">  یا  Borjik.toast / Borjik.confirm / Borjik.prompt */
(function (window, document) {
  'use strict';

  var Borjik = window.Borjik || {};

  /* ─── Toast ─── */
  var TITLES = { success: 'موفق', danger: 'خطا', warning: 'هشدار', info: 'اطلاع' };
  var DELAYS = { success: 4000, info: 5000, warning: 7000, danger: 9000 };

  function normType(t) {
    t = String(t || 'info').toLowerCase();
    if (t === 'error') return 'danger';
    if (t === 'status') return 'success';
    return TITLES[t] ? t : 'info';
  }

  function toastRoot() {
    var c = document.getElementById('borjik-toasts');
    if (!c) {
      c = document.createElement('div');
      c.id = 'borjik-toasts';
      c.className = 'toast-container borjik-toasts';
      document.body.appendChild(c);
    }
    return c;
  }

  Borjik.toast = function (message, options) {
    options = options || {};
    var type = normType(options.type);

    var el = document.createElement('div');
    el.className = 'toast borjik-toast borjik-toast-' + type;
    el.setAttribute('role', type === 'danger' ? 'alert' : 'status');
    el.setAttribute('aria-live', type === 'danger' ? 'assertive' : 'polite');
    el.setAttribute('aria-atomic', 'true');

    var head = document.createElement('div');
    head.className = 'toast-header';
    var title = document.createElement('strong');
    title.className = 'borjik-toast-title';
    title.textContent = options.title || TITLES[type];
    var close = document.createElement('button');
    close.type = 'button';
    close.className = 'btn-close';
    close.setAttribute('data-bs-dismiss', 'toast');
    close.setAttribute('aria-label', 'بستن');
    head.appendChild(title);
    head.appendChild(close);

    var body = document.createElement('div');
    body.className = 'toast-body';
    body.textContent = String(message == null ? '' : message);   // textContent: ضد XSS

    el.appendChild(head);
    el.appendChild(body);
    toastRoot().appendChild(el);

    var delay = options.delay != null ? options.delay : DELAYS[type];
    if (window.bootstrap && window.bootstrap.Toast) {
      var inst = new window.bootstrap.Toast(el, { autohide: true, delay: delay });
      el.addEventListener('hidden.bs.toast', function () { el.remove(); });
      inst.show();
      return inst;
    }
    // اگر فایل Bootstrap لود نشد، اعلان همچنان نمایش داده می‌شود
    el.classList.add('show');
    var rm = function () { el.remove(); };
    close.addEventListener('click', rm);
    setTimeout(rm, delay);
  };

  Borjik.toastSuccess = function (m, o) { return Borjik.toast(m, Object.assign({ type: 'success' }, o || {})); };
  Borjik.toastError   = function (m, o) { return Borjik.toast(m, Object.assign({ type: 'danger' }, o || {})); };
  Borjik.toastWarning = function (m, o) { return Borjik.toast(m, Object.assign({ type: 'warning' }, o || {})); };
  Borjik.toastInfo    = function (m, o) { return Borjik.toast(m, Object.assign({ type: 'info' }, o || {})); };

  /* ─── SweetAlert2 ─── */
  function swalOptions(extra, danger) {
    return Object.assign({
      heightAuto: false,          // چیدمان تمام‌صفحه‌ی اپ به‌هم نریزد
      scrollbarPadding: false,
      returnFocus: true,
      buttonsStyling: false,
      showCancelButton: true,
      customClass: {
        popup: 'borjik-swal',
        confirmButton: danger ? ['borjik-swal-confirm', 'is-danger'] : ['borjik-swal-confirm'],
        cancelButton: 'borjik-swal-cancel'
      }
    }, extra);
  }

  Borjik.confirm = function (opts) {
    opts = typeof opts === 'string' ? { text: opts } : (opts || {});
    if (!window.Swal) { return Promise.resolve(window.confirm(opts.text || opts.title || '')); }
    var danger = opts.icon === 'warning' || opts.danger === true;
    return window.Swal.fire(swalOptions({
      title: opts.title || 'تأیید عملیات',
      text: opts.text || '',
      icon: opts.icon || 'question',
      confirmButtonText: opts.confirmText || 'بله',
      cancelButtonText: opts.cancelText || 'خیر',
      focusCancel: danger
    }, danger)).then(function (r) { return !!r.isConfirmed; });
  };

  Borjik.prompt = function (opts) {
    opts = typeof opts === 'string' ? { text: opts } : (opts || {});
    if (!window.Swal) { var v = window.prompt(opts.text || opts.title || ''); return Promise.resolve(v ? v : null); }
    var required = opts.required !== false;
    return window.Swal.fire(swalOptions({
      title: opts.title || 'ورود اطلاعات',
      text: opts.text || '',
      input: 'textarea',
      inputPlaceholder: opts.placeholder || '',
      inputValue: opts.value || '',
      inputAttributes: { maxlength: String(opts.maxlength || 255), 'aria-label': opts.title || opts.text || '' },
      confirmButtonText: opts.confirmText || 'تأیید',
      cancelButtonText: opts.cancelText || 'انصراف',
      inputValidator: required ? function (value) {
        if (!value || !String(value).trim()) { return 'این فیلد الزامی است.'; }
      } : undefined
    }, false)).then(function (r) { return r.isConfirmed ? String(r.value || '').trim() : null; });
  };

  /* ─── فرم‌ها: data-confirm / data-prompt ─── */
  function bindForms() {
    if (Borjik._bound) { return; }
    Borjik._bound = true;

    document.addEventListener('submit', function (e) {
      var form = e.target;
      if (!form || form.tagName !== 'FORM') { return; }

      var confirmMsg = form.getAttribute('data-confirm');
      var promptMsg = form.getAttribute('data-prompt');
      if (!confirmMsg && !promptMsg) { return; }

      if (form.dataset.borjikConfirmed === '1') {   // ارسال دوم، بعد از تأیید کاربر
        delete form.dataset.borjikConfirmed;
        return;
      }

      e.preventDefault();
      e.stopPropagation();
      if (window.Swal && window.Swal.isVisible && window.Swal.isVisible()) { return; }

      var submitter = e.submitter || null;
      var promptName = form.getAttribute('data-prompt-name') || 'reason';

      function go() {
        form.dataset.borjikConfirmed = '1';
        try {
          if (submitter) { form.requestSubmit(submitter); } else { form.requestSubmit(); }
        } catch (err) {
          delete form.dataset.borjikConfirmed;
          form.submit();
        }
      }

      var step = Promise.resolve(true);
      if (confirmMsg) {
        step = Borjik.confirm({
          title: form.getAttribute('data-confirm-title') || 'تأیید عملیات',
          text: confirmMsg,
          icon: form.getAttribute('data-confirm-icon') || 'question',
          confirmText: form.getAttribute('data-confirm-ok') || 'بله',
          cancelText: form.getAttribute('data-confirm-cancel') || 'خیر'
        });
      }

      step.then(function (ok) {
        if (!ok) { return; }
        if (!promptMsg) { go(); return; }
        return Borjik.prompt({
          title: form.getAttribute('data-prompt-title') || 'توضیح',
          text: promptMsg,
          placeholder: form.getAttribute('data-prompt-placeholder') || '',
          confirmText: form.getAttribute('data-prompt-ok') || 'تأیید',
          maxlength: parseInt(form.getAttribute('data-prompt-maxlength'), 10) || 255,
          required: form.getAttribute('data-prompt-required') !== '0'
        }).then(function (value) {
          if (value == null) { return; }
          var input = form.querySelector('[name="' + promptName + '"]');
          if (!input) {
            input = document.createElement('input');
            input.type = 'hidden';
            input.name = promptName;
            form.appendChild(input);
          }
          input.value = value;
          go();
        });
      });
    }, true);
  }

  /* ─── پیام‌های flash سرور → تاست ─── */
  Borjik.flashFromPage = function () {
    document.querySelectorAll('[data-borjik-flash]').forEach(function (n) {
      var msg = (n.textContent || '').trim();
      var type = n.getAttribute('data-borjik-flash') || 'info';
      n.remove();
      if (msg) { Borjik.toast(msg, { type: type }); }
    });
  };

  function ready(fn) {
    if (document.readyState === 'loading') { document.addEventListener('DOMContentLoaded', fn); } else { fn(); }
  }
  ready(function () { bindForms(); Borjik.flashFromPage(); });

  window.Borjik = Borjik;
})(window, document);
