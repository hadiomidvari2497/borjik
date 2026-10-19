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


  /* ─── جست‌وجوپذیر کردن selectها در کل سامانه (بدون وابستگی خارجی) ─── */
  function initSearchableSelects(scope) {
    scope = scope || document;
    var selector = 'select:not([data-native-select]):not([data-search-select="off"]):not([size]):not([hidden])';
    var selects = [];
    if (scope.matches && scope.matches(selector)) selects.push(scope);
    if (scope.querySelectorAll) selects = selects.concat(Array.prototype.slice.call(scope.querySelectorAll(selector)));

    selects.forEach(function (select) {
      if (select.dataset.searchSelectReady === '1' || select.multiple || select.closest('.b-select')) return;
      select.dataset.searchSelectReady = '1';

      var wrap = document.createElement('div');
      wrap.className = 'b-select';
      wrap.dir = select.dir || document.documentElement.dir || 'rtl';
      var computed = window.getComputedStyle(select);
      wrap.style.width = computed.width === 'auto' ? '' : computed.width;
      if (computed.display === 'none') wrap.style.display = 'none';
      select.parentNode.insertBefore(wrap, select);
      wrap.appendChild(select);
      select.classList.add('b-select-native');
      select.setAttribute('aria-hidden', 'true');
      select.tabIndex = -1;

      var trigger = document.createElement('button');
      trigger.type = 'button';
      trigger.className = 'b-select-trigger';
      trigger.setAttribute('aria-haspopup', 'listbox');
      trigger.setAttribute('aria-expanded', 'false');
      trigger.setAttribute('aria-label', select.getAttribute('aria-label') || (select.labels && select.labels[0] ? select.labels[0].textContent.trim() : 'انتخاب گزینه'));
      var value = document.createElement('span');
      value.className = 'b-select-value';
      var arrow = document.createElement('span');
      arrow.className = 'b-select-arrow';
      arrow.setAttribute('aria-hidden', 'true');
      arrow.innerHTML = '<svg viewBox="0 0 24 24" focusable="false"><path d="m7 10 5 5 5-5"/></svg>';
      trigger.appendChild(value);
      trigger.appendChild(arrow);
      wrap.appendChild(trigger);

      var menu = document.createElement('div');
      menu.className = 'b-select-menu';
      menu.dir = wrap.dir;
      menu.hidden = true;
      menu.setAttribute('role', 'dialog');
      menu.setAttribute('aria-label', trigger.getAttribute('aria-label'));
      var searchWrap = document.createElement('div');
      searchWrap.className = 'b-select-search';
      var search = document.createElement('input');
      search.type = 'search';
      search.autocomplete = 'off';
      search.placeholder = 'جست‌وجو بین گزینه‌ها…';
      search.setAttribute('aria-label', 'جست‌وجو بین گزینه‌ها');
      searchWrap.appendChild(search);
      menu.appendChild(searchWrap);
      var list = document.createElement('div');
      list.className = 'b-select-options';
      list.setAttribute('role', 'listbox');
      list.id = 'b-select-list-' + Math.random().toString(36).slice(2, 10);
      trigger.setAttribute('aria-controls', list.id);
      menu.appendChild(list);
      var empty = document.createElement('div');
      empty.className = 'b-select-empty';
      empty.textContent = 'گزینه‌ای پیدا نشد.';
      empty.hidden = true;
      menu.appendChild(empty);
      document.body.appendChild(menu);

      var optionButtons = [];
      var opened = false;
      var placeholder = select.getAttribute('data-placeholder') || (select.options.length && select.options[0].value === '' ? select.options[0].text.trim() : 'انتخاب کنید…');

      function labelFor(option) { return (option.textContent || '').trim(); }
      function renderValue() {
        var selected = select.selectedOptions && select.selectedOptions.length ? select.selectedOptions[0] : null;
        var hasValue = !!selected && !(selected.value === '' && !selected.hasAttribute('data-allow-empty-label'));
        value.textContent = hasValue ? labelFor(selected) : placeholder;
        value.classList.toggle('is-placeholder', !hasValue);
        trigger.classList.toggle('has-value', hasValue);
        trigger.disabled = select.disabled;
        trigger.classList.toggle('is-disabled', select.disabled);
        trigger.setAttribute('aria-required', select.required ? 'true' : 'false');
        trigger.setAttribute('aria-invalid', select.getAttribute('aria-invalid') || 'false');
      }
      function syncOptions() {
        list.innerHTML = '';
        optionButtons = [];
        var opts = Array.prototype.slice.call(select.options);
        opts.forEach(function (option) {
          if (option.hidden) return;
          if (option.parentElement && option.parentElement.tagName === 'OPTGROUP') {
            var group = option.parentElement;
            if (group.dataset.bSelectLabelAdded !== '1') {
              var firstIndex = opts.indexOf(group.querySelector('option'));
              if (firstIndex === opts.indexOf(option)) {
                var heading = document.createElement('div');
                heading.className = 'b-select-group';
                heading.textContent = group.label;
                list.appendChild(heading);
              }
            }
          }
          var item = document.createElement('button');
          item.type = 'button';
          item.className = 'b-select-option';
          item.setAttribute('role', 'option');
          item.textContent = labelFor(option);
          item.disabled = option.disabled || !!(option.parentElement && option.parentElement.disabled);
          item.setAttribute('aria-selected', option.selected ? 'true' : 'false');
          if (option.selected) item.classList.add('is-selected');
          item._option = option;
          item.addEventListener('click', function () {
            if (item.disabled) return;
            select.value = option.value;
            select.dispatchEvent(new Event('input', { bubbles: true }));
            select.dispatchEvent(new Event('change', { bubbles: true }));
            renderValue();
            syncOptions();
            if (!select.multiple) closeMenu();
            else search.focus();
          });
          list.appendChild(item);
          optionButtons.push(item);
        });
        filterOptions();
      }
      function filterOptions() {
        var query = (search.value || '').trim().toLocaleLowerCase();
        var shown = 0;
        optionButtons.forEach(function (item) {
          var matches = !query || item.textContent.toLocaleLowerCase().indexOf(query) !== -1;
          item.hidden = !matches;
          if (matches) shown++;
        });
        Array.prototype.forEach.call(list.querySelectorAll('.b-select-group'), function (heading) {
          var next = heading.nextElementSibling, visible = false;
          while (next && !next.classList.contains('b-select-group')) {
            if (!next.hidden) visible = true;
            next = next.nextElementSibling;
          }
          heading.hidden = !visible;
        });
        empty.hidden = shown > 0;
      }
      function positionMenu() {
        var rect = trigger.getBoundingClientRect();
        var margin = 8;
        var maxWidth = Math.max(180, window.innerWidth - margin * 2);
        var width = Math.min(Math.max(rect.width, 240), maxWidth);
        var left = wrap.dir === 'rtl' ? rect.right - width : rect.left;
        left = Math.max(margin, Math.min(left, window.innerWidth - width - margin));
        menu.style.width = width + 'px';
        menu.style.left = left + 'px';
        menu.style.top = Math.min(rect.bottom + 6, window.innerHeight - 100) + 'px';
        var roomBelow = window.innerHeight - rect.bottom - 14;
        var roomAbove = rect.top - 14;
        if (roomBelow < 210 && roomAbove > roomBelow) {
          menu.style.top = Math.max(8, rect.top - Math.min(320, roomAbove) - 6) + 'px';
          menu.classList.add('opens-up');
        } else menu.classList.remove('opens-up');
        menu.style.maxHeight = Math.max(150, Math.min(340, Math.max(roomBelow, roomAbove) - 8)) + 'px';
      }
      function openMenu() {
        if (select.disabled || opened) return;
        document.querySelectorAll('.b-select-menu:not([hidden])').forEach(function (other) {
          if (other !== menu && other._close) other._close();
        });
        opened = true;
        menu.hidden = false;
        trigger.setAttribute('aria-expanded', 'true');
        search.value = '';
        filterOptions();
        positionMenu();
        window.requestAnimationFrame(function () { search.focus(); });
      }
      function closeMenu() {
        if (!opened) return;
        opened = false;
        menu.hidden = true;
        trigger.setAttribute('aria-expanded', 'false');
        trigger.focus({ preventScroll: true });
      }
      menu._close = closeMenu;
      trigger.addEventListener('click', function () { opened ? closeMenu() : openMenu(); });
      search.addEventListener('input', filterOptions);
      search.addEventListener('keydown', function (event) {
        if (event.key === 'Escape') { event.preventDefault(); closeMenu(); return; }
        if (event.key === 'ArrowDown' || event.key === 'ArrowUp') {
          event.preventDefault();
          var visible = optionButtons.filter(function (item) { return !item.hidden && !item.disabled; });
          if (!visible.length) return;
          var current = visible.indexOf(document.activeElement);
          var next = event.key === 'ArrowDown' ? Math.min(current + 1, visible.length - 1) : Math.max(current - 1, 0);
          visible[next].focus();
        }
      });
      menu.addEventListener('keydown', function (event) {
        if (event.key === 'Escape') { event.preventDefault(); closeMenu(); }
      });
      document.addEventListener('pointerdown', function (event) {
        if (opened && !wrap.contains(event.target) && !menu.contains(event.target)) {
          opened = false;
          menu.hidden = true;
          trigger.setAttribute('aria-expanded', 'false');
        }
      });
      window.addEventListener('resize', function () { if (opened) positionMenu(); });
      window.addEventListener('scroll', function () { if (opened) positionMenu(); }, true);
      select.addEventListener('change', function () { renderValue(); syncOptions(); });
      select.addEventListener('invalid', function () {
        trigger.classList.add('is-invalid');
        trigger.focus();
      });
      if (select.form) select.form.addEventListener('reset', function () {
        window.setTimeout(function () { renderValue(); syncOptions(); trigger.classList.remove('is-invalid'); }, 0);
      });
      renderValue();
      syncOptions();
    });
  }

  function bootSearchableSelects() {
    initSearchableSelects(document);
    if (window.__borjikSelectObserver) return;
    window.__borjikSelectObserver = new MutationObserver(function (mutations) {
      mutations.forEach(function (mutation) {
        Array.prototype.forEach.call(mutation.addedNodes || [], function (node) {
          if (node.nodeType === 1) initSearchableSelects(node);
        });
      });
    });
    window.__borjikSelectObserver.observe(document.body, { childList: true, subtree: true });
    window.addEventListener('borjik:content-updated', function () { initSearchableSelects(document); });
  }
  ready(bootSearchableSelects);

  window.Borjik = Borjik;
})(window, document);
