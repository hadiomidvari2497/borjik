(function (window, document) {
  'use strict';

  var Borjik = window.Borjik || {};

  function faDigits(s) {
    return String(s == null ? '' : s);
  }

  /** Bootstrap 5.3 Toast (local) */
  Borjik.toast = function (message, options) {
    options = options || {};
    var type = options.type || 'info';
    var delay = options.delay != null ? options.delay : 4500;

    var container = document.getElementById('borjik-toast-root');
    if (!container) {
      container = document.createElement('div');
      container.id = 'borjik-toast-root';
      container.className = 'toast-container position-fixed p-3 borjik-toast-container';
      container.setAttribute('aria-live', 'polite');
      container.setAttribute('aria-atomic', 'true');
      document.body.appendChild(container);
    }

    var el = document.createElement('div');
    el.className = 'toast borjik-toast borjik-toast-' + type;
    el.setAttribute('role', 'status');
    el.setAttribute('aria-live', 'polite');
    el.innerHTML =
      '<div class="d-flex">' +
        '<div class="toast-body">' + faDigits(message) + '</div>' +
        '<button type="button" class="btn-close me-2 m-auto" data-bs-dismiss="toast" aria-label="بستن"></button>' +
      '</div>';

    container.appendChild(el);

    if (window.bootstrap && bootstrap.Toast) {
      var t = new bootstrap.Toast(el, { autohide: true, delay: delay });
      el.addEventListener('hidden.bs.toast', function () { el.remove(); });
      t.show();
    } else {
      el.classList.add('show');
      setTimeout(function () { el.remove(); }, delay);
    }
  };

  Borjik.toastSuccess = function (msg, o) { Borjik.toast(msg, Object.assign({ type: 'success' }, o || {})); };
  Borjik.toastError = function (msg, o) { Borjik.toast(msg, Object.assign({ type: 'danger' }, o || {})); };
  Borjik.toastWarning = function (msg, o) { Borjik.toast(msg, Object.assign({ type: 'warning' }, o || {})); };
  Borjik.toastInfo = function (msg, o) { Borjik.toast(msg, Object.assign({ type: 'info' }, o || {})); };

  Borjik.confirm = function (opts) {
    opts = typeof opts === 'string' ? { text: opts } : (opts || {});
    if (!window.Swal) {
      return Promise.resolve(window.confirm(opts.text || opts.title || 'ادامه می‌دهید؟'));
    }
    return Swal.fire({
      title: opts.title || 'تأیید',
      text: opts.text || '',
      icon: opts.icon || 'question',
      showCancelButton: true,
      confirmButtonText: opts.confirmText || 'بله',
      cancelButtonText: opts.cancelText || 'خیر',
      reverseButtons: true,
      focusCancel: true,
      customClass: {
        popup: 'borjik-swal',
        confirmButton: 'borjik-swal-confirm',
        cancelButton: 'borjik-swal-cancel'
      },
      buttonsStyling: false
    }).then(function (r) { return !!r.isConfirmed; });
  };

  Borjik.prompt = function (opts) {
    opts = typeof opts === 'string' ? { text: opts } : (opts || {});
    if (!window.Swal) {
      var v = window.prompt(opts.text || opts.title || '');
      return Promise.resolve(v);
    }
    return Swal.fire({
      title: opts.title || 'ورود اطلاعات',
      text: opts.text || '',
      input: opts.input || 'text',
      inputPlaceholder: opts.placeholder || '',
      inputValue: opts.value || '',
      showCancelButton: true,
      confirmButtonText: opts.confirmText || 'تأیید',
      cancelButtonText: opts.cancelText || 'انصراف',
      reverseButtons: true,
      inputValidator: opts.required === false ? undefined : function (value) {
        if (!value || !String(value).trim()) return 'این فیلد الزامی است';
      },
      customClass: {
        popup: 'borjik-swal',
        confirmButton: 'borjik-swal-confirm',
        cancelButton: 'borjik-swal-cancel'
      },
      buttonsStyling: false
    }).then(function (r) {
      return r.isConfirmed ? r.value : null;
    });
  };

  function bindConfirms() {
    document.addEventListener('submit', function (e) {
      var form = e.target;
      if (!form || form.tagName !== 'FORM') return;

      var confirmMsg = form.getAttribute('data-confirm');
      var promptMsg = form.getAttribute('data-prompt');
      var promptName = form.getAttribute('data-prompt-name') || 'reason';

      if (!confirmMsg && !promptMsg) return;

      if (form.dataset.borjikConfirmed === '1') {
        delete form.dataset.borjikConfirmed;
        return;
      }

      e.preventDefault();
      e.stopPropagation();

      var chain = Promise.resolve(true);
      if (confirmMsg) {
        chain = Borjik.confirm({
          title: form.getAttribute('data-confirm-title') || 'تأیید عملیات',
          text: confirmMsg,
          icon: form.getAttribute('data-confirm-icon') || 'question',
          confirmText: form.getAttribute('data-confirm-ok') || 'بله',
          cancelText: form.getAttribute('data-confirm-cancel') || 'خیر'
        });
      }

      chain.then(function (ok) {
        if (!ok) return;
        if (!promptMsg) {
          form.dataset.borjikConfirmed = '1';
          form.requestSubmit ? form.requestSubmit() : form.submit();
          return;
        }
        return Borjik.prompt({
          title: form.getAttribute('data-prompt-title') || 'توضیح',
          text: promptMsg,
          placeholder: form.getAttribute('data-prompt-placeholder') || '',
          confirmText: form.getAttribute('data-prompt-ok') || 'تأیید',
          required: form.getAttribute('data-prompt-required') !== '0'
        }).then(function (value) {
          if (value == null) return;
          var input = form.querySelector('[name="' + promptName + '"]');
          if (!input) {
            input = document.createElement('input');
            input.type = 'hidden';
            input.name = promptName;
            form.appendChild(input);
          }
          input.value = value;
          form.dataset.borjikConfirmed = '1';
          form.requestSubmit ? form.requestSubmit() : form.submit();
        });
      });
    }, true);
  }

  Borjik.flashFromPage = function () {
    var nodes = document.querySelectorAll('[data-borjik-flash]');
    nodes.forEach(function (n) {
      var type = n.getAttribute('data-borjik-flash') || 'info';
      var msg = (n.textContent || '').trim();
      if (!msg) return;
      if (type === 'success' || type === 'status') Borjik.toastSuccess(msg);
      else if (type === 'danger' || type === 'error') Borjik.toastError(msg);
      else if (type === 'warning') Borjik.toastWarning(msg);
      else Borjik.toastInfo(msg);
      n.remove();
    });
  };

  document.addEventListener('DOMContentLoaded', function () {
    bindConfirms();
    Borjik.flashFromPage();
  });

  window.Borjik = Borjik;
})(window, document);
