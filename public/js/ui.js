(function (window, document) {
  'use strict';

  var Borjik = window.Borjik || {};

  /* ─── Bootstrap 5.3–style toasts (local, no CDN) ─── */
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
    el.className = 'toast borjik-toast borjik-toast-' + type + ' show';
    el.setAttribute('role', 'status');
    el.innerHTML =
      '<div class="d-flex">' +
        '<div class="toast-body"></div>' +
        '<button type="button" class="btn-close me-2 m-auto" aria-label="بستن"></button>' +
      '</div>';
    el.querySelector('.toast-body').textContent = String(message == null ? '' : message);

    var closeBtn = el.querySelector('.btn-close');
    var hide = function () {
      el.classList.remove('show');
      el.classList.add('hiding');
      setTimeout(function () { el.remove(); }, 200);
    };
    closeBtn.addEventListener('click', hide);
    container.appendChild(el);
    setTimeout(hide, delay);
  };

  Borjik.toastSuccess = function (m, o) { Borjik.toast(m, Object.assign({ type: 'success' }, o || {})); };
  Borjik.toastError = function (m, o) { Borjik.toast(m, Object.assign({ type: 'danger' }, o || {})); };
  Borjik.toastWarning = function (m, o) { Borjik.toast(m, Object.assign({ type: 'warning' }, o || {})); };
  Borjik.toastInfo = function (m, o) { Borjik.toast(m, Object.assign({ type: 'info' }, o || {})); };

  /* ─── SweetAlert2-compatible confirm (uses Swal if present, else local modal) ─── */
  function ensureLocalModal() {
    if (document.getElementById('borjik-swal-root')) return;
    var root = document.createElement('div');
    root.id = 'borjik-swal-root';
    root.innerHTML =
      '<div class="borjik-swal-backdrop" data-swal-cancel></div>' +
      '<div class="borjik-swal-panel" role="dialog" aria-modal="true">' +
        '<div class="borjik-swal-icon" data-swal-icon></div>' +
        '<h3 class="borjik-swal-title" data-swal-title></h3>' +
        '<p class="borjik-swal-text" data-swal-text></p>' +
        '<div class="borjik-swal-input-wrap" data-swal-input-wrap hidden>' +
          '<input class="borjik-swal-input" data-swal-input type="text">' +
        '</div>' +
        '<div class="borjik-swal-actions">' +
          '<button type="button" class="borjik-swal-cancel" data-swal-cancel>خیر</button>' +
          '<button type="button" class="borjik-swal-confirm" data-swal-ok>بله</button>' +
        '</div>' +
      '</div>';
    document.body.appendChild(root);
  }

  function localFire(opts) {
    ensureLocalModal();
    var root = document.getElementById('borjik-swal-root');
    var title = root.querySelector('[data-swal-title]');
    var text = root.querySelector('[data-swal-text]');
    var icon = root.querySelector('[data-swal-icon]');
    var wrap = root.querySelector('[data-swal-input-wrap]');
    var input = root.querySelector('[data-swal-input]');
    var okBtn = root.querySelector('[data-swal-ok]');
    var cancelBtns = root.querySelectorAll('[data-swal-cancel]');

    title.textContent = opts.title || 'تأیید';
    text.textContent = opts.text || '';
    icon.setAttribute('data-icon', opts.icon || 'question');
    icon.textContent = ({ question: '?', warning: '!', success: '✓', error: '×', info: 'i' })[opts.icon || 'question'] || '?';
    okBtn.textContent = opts.confirmButtonText || 'بله';
    cancelBtns.forEach(function (b) {
      if (b.tagName === 'BUTTON') b.textContent = opts.cancelButtonText || 'خیر';
    });

    var wantsInput = !!opts.input;
    wrap.hidden = !wantsInput;
    if (wantsInput) {
      input.value = opts.inputValue || '';
      input.placeholder = opts.inputPlaceholder || '';
      setTimeout(function () { input.focus(); }, 50);
    }

    root.classList.add('open');

    return new Promise(function (resolve) {
      function cleanup(result) {
        root.classList.remove('open');
        okBtn.onclick = null;
        cancelBtns.forEach(function (b) { b.onclick = null; });
        resolve(result);
      }
      okBtn.onclick = function () {
        if (wantsInput) {
          var v = input.value;
          if (opts.inputValidator) {
            var err = opts.inputValidator(v);
            if (err) { Borjik.toastWarning(err); return; }
          }
          cleanup({ isConfirmed: true, value: v });
        } else {
          cleanup({ isConfirmed: true });
        }
      };
      cancelBtns.forEach(function (b) {
        b.onclick = function () { cleanup({ isConfirmed: false }); };
      });
    });
  }

  Borjik.confirm = function (opts) {
    opts = typeof opts === 'string' ? { text: opts } : (opts || {});
    var fire = (window.Swal && Swal.fire)
      ? function () {
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
        }
      : function () {
          return localFire({
            title: opts.title || 'تأیید',
            text: opts.text || '',
            icon: opts.icon || 'question',
            confirmButtonText: opts.confirmText || 'بله',
            cancelButtonText: opts.cancelText || 'خیر'
          }).then(function (r) { return !!r.isConfirmed; });
        };
    return fire();
  };

  Borjik.prompt = function (opts) {
    opts = typeof opts === 'string' ? { text: opts } : (opts || {});
    var fire = (window.Swal && Swal.fire)
      ? function () {
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
          }).then(function (r) { return r.isConfirmed ? r.value : null; });
        }
      : function () {
          return localFire({
            title: opts.title || 'ورود اطلاعات',
            text: opts.text || '',
            icon: 'info',
            input: true,
            inputPlaceholder: opts.placeholder || '',
            inputValue: opts.value || '',
            confirmButtonText: opts.confirmText || 'تأیید',
            cancelButtonText: opts.cancelText || 'انصراف',
            inputValidator: opts.required === false ? undefined : function (value) {
              if (!value || !String(value).trim()) return 'این فیلد الزامی است';
            }
          }).then(function (r) { return r.isConfirmed ? r.value : null; });
        };
    return fire();
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
          if (form.requestSubmit) form.requestSubmit();
          else form.submit();
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
          if (form.requestSubmit) form.requestSubmit();
          else form.submit();
        });
      });
    }, true);
  }

  Borjik.flashFromPage = function () {
    document.querySelectorAll('[data-borjik-flash]').forEach(function (n) {
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
