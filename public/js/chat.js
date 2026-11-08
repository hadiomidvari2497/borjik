/* گفتگوی آگهی — پیام‌رسان تمام‌صفحه: ارسال بدون بارگذاری مجدد، پیوست (تصویر/PDF)، دریافت پیام جدید،
   جداکننده روز، گروه‌بندی و نمایشگر تصویر. متن‌ها فقط با textContent درج می‌شوند. */
(function () {
  'use strict';

  var root = document.getElementById('chat');
  if (!root) { return; }

  var thread = document.getElementById('chat-thread');
  var jump = document.getElementById('chat-jump');
  var jumpN = jump ? jump.querySelector('.jump-n') : null;
  var form = document.getElementById('chat-composer');
  var closedBar = document.getElementById('chat-closed');

  var me = Number(root.dataset.me) || 0;
  var pollUrl = root.dataset.pollUrl;
  var today = root.dataset.today || '';
  var yesterday = root.dataset.yesterday || '';
  var MAX = 2000, MAX_FILES = 5, MAX_SIZE = 10 * 1024 * 1024;
  var ALLOWED = ['image/jpeg', 'image/png', 'image/webp', 'application/pdf'];

  var lastId = 0, lastSender = null, lastDay = null, lastNode = null, pending = 0;

  function faDigits(n) { return String(n).replace(/\d/g, function (d) { return '۰۱۲۳۴۵۶۷۸۹'[d]; }); }
  function dayLabel(day) { return day === today ? 'امروز' : (day === yesterday ? 'دیروز' : day); }
  function nearBottom() { return thread.scrollHeight - thread.scrollTop - thread.clientHeight < 120; }
  function sizeLabel(b) { return b < 1048576 ? faDigits(Math.max(1, Math.round(b / 1024))) + ' کیلوبایت' : faDigits((b / 1048576).toFixed(1)) + ' مگابایت'; }

  function el(tag, cls, text) {
    var n = document.createElement(tag);
    if (cls) { n.className = cls; }
    if (text != null) { n.textContent = text; }
    return n;
  }
  function icon(id) {
    var ns = 'http://www.w3.org/2000/svg';
    var svg = document.createElementNS(ns, 'svg');
    svg.setAttribute('class', 'icon');
    svg.setAttribute('aria-hidden', 'true');
    var use = document.createElementNS(ns, 'use');
    use.setAttribute('href', '#' + id);
    svg.appendChild(use);
    return svg;
  }

  /* ───── ارتفاع واقعی صفحه (کیبورد موبایل) ───── */
  var vv = window.visualViewport;
  function fit() {
    if (!vv) { return; }
    var st = document.documentElement.style;
    st.setProperty('--app-h', Math.round(vv.height) + 'px');
    st.setProperty('--app-top', Math.round(vv.offsetTop) + 'px');
    if (window.scrollY) { window.scrollTo(0, 0); }
  }
  if (vv) {
    vv.addEventListener('resize', function () { var stick = nearBottom(); fit(); if (stick) { toBottom(false); } });
    vv.addEventListener('scroll', fit);
    fit();
  }

  function toBottom(smooth) {
    thread.style.scrollBehavior = smooth ? 'smooth' : 'auto';
    thread.scrollTop = thread.scrollHeight;
    thread.style.scrollBehavior = '';
    setPending(0);
  }

  function setPending(n) {
    pending = n;
    if (!jump) { return; }
    var away = !nearBottom();
    jump.classList.toggle('show', away);
    if (jumpN) {
      jumpN.textContent = faDigits(pending);
      jumpN.classList.toggle('show', pending > 0 && away);
    }
  }

  Array.prototype.forEach.call(thread.querySelectorAll('.msg'), function (node) {
    lastId = Math.max(lastId, Number(node.dataset.messageId) || 0);
    lastSender = Number(node.dataset.sender);
    lastDay = node.dataset.day;
    lastNode = node;
  });

  /* ───── افزودن پیام به گفتگو ───── */
  function buildAttachments(node, list) {
    var imgs = list.filter(function (a) { return a.is_image; });
    var files = list.filter(function (a) { return !a.is_image; });
    if (imgs.length) {
      node.classList.add('has-media');
      var grid = el('div', 'msg-media n' + Math.min(imgs.length, 5));
      imgs.forEach(function (a) {
        var link = el('a', 'media');
        link.href = a.url;
        link.setAttribute('data-lightbox', '');
        link.dataset.name = a.name;
        var img = document.createElement('img');
        img.src = a.url; img.alt = a.name; img.loading = 'lazy';
        link.appendChild(img);
        grid.appendChild(link);
      });
      node.appendChild(grid);
    }
    files.forEach(function (a) {
      var link = el('a', 'file');
      link.href = a.url;
      link.setAttribute('download', a.name);
      var ico = el('span', 'file-ico'); ico.appendChild(icon('i-file'));
      var t = el('span', 'file-t');
      var b = el('b', null, a.name); b.setAttribute('dir', 'auto');
      t.append(b, el('small', null, 'PDF · ' + a.size));
      var dl = icon('i-download'); dl.classList.add('file-dl');
      link.append(ico, t, dl);
      node.appendChild(link);
    });
  }

  function appendMessage(message) {
    if (thread.querySelector('.msg[data-message-id="' + message.id + '"]')) { return; }

    var parts = String(message.time || '').split(' ');
    var day = parts[0] || '', hm = parts[1] || '';
    var sender = Number(message.sender_user_id);
    var mine = sender === me;
    var stick = nearBottom() || mine;

    if (day && day !== lastDay) {
      thread.appendChild(el('div', 'day', dayLabel(day)));
      lastSender = null;
    }
    var starts = sender !== lastSender || day !== lastDay;
    if (lastNode && !starts) { lastNode.classList.remove('last'); }

    var node = el('article', 'msg ' + (mine ? 'mine' : 'theirs') + (starts ? ' first' : '') + ' last is-new');
    node.dataset.messageId = message.id;
    node.dataset.sender = sender;
    node.dataset.day = day;
    if (message.reply) {
      var q = el('button', 'reply-quote');
      q.type = 'button'; q.dataset.jumpTo = message.reply.id;
      q.setAttribute('aria-label', 'رفتن به پیام اصلی');
      q.append(el('b', null, message.reply.sender_name || 'کاربر'), el('span', null, (message.reply.body || 'پیوست').slice(0, 100)));
      node.appendChild(q);
    }
    buildAttachments(node, message.attachments || []);
    if (message.body) { node.appendChild(el('div', 'msg-body', message.body)); }
    var foot = el('div', 'msg-foot');
    var rb = el('button', 'msg-reply'); rb.type = 'button';
    rb.dataset.replyId = message.id;
    rb.dataset.replySender = mine ? 'شما' : (message.sender_name || 'کاربر');
    rb.dataset.replyBody = message.body || 'پیوست';
    rb.setAttribute('aria-label', 'پاسخ به این پیام');
    rb.appendChild(icon('i-reply'));
    foot.append(rb, el('time', 'msg-time', hm));
    if (mine) {
      var tick = el('span', 'tick' + (message.read_at ? ' is-read' : ''), message.read_at ? '✓✓' : '✓');
      tick.dataset.receiptId = message.id;
      foot.appendChild(tick);
    }
    node.appendChild(foot);
    thread.appendChild(node);

    lastId = Math.max(lastId, Number(message.id) || 0);
    lastSender = sender; lastDay = day; lastNode = node;

    if (stick) { toBottom(true); } else if (!mine) { setPending(pending + 1); }
  }

  function markRead(t) {
    if (!t || t.classList.contains('is-read')) { return; }
    t.textContent = '✓✓'; t.classList.add('is-read'); t.title = 'خوانده شده';
  }

  /* پرش به پیام اصلی هنگام کلیک روی نقل‌قول */
  thread.addEventListener('click', function (e) {
    var q = e.target.closest('[data-jump-to]');
    if (!q) { return; }
    var target = thread.querySelector('.msg[data-message-id="' + q.dataset.jumpTo + '"]');
    if (!target) { return; }
    target.scrollIntoView({ block: 'center', behavior: 'smooth' });
    target.classList.remove('flash'); void target.offsetWidth; target.classList.add('flash');
  });

  function lockAsClosed() {
    if (form) { form.hidden = true; }
    if (closedBar) { closedBar.hidden = false; }
  }

  /* ───── دریافت پیام‌های جدید ───── */
  var polling = false, timer = null;
  function poll() {
    if (polling || document.hidden) { return schedule(); }
    polling = true;
    fetch(pollUrl + '?after_id=' + encodeURIComponent(lastId), {
      headers: { 'Accept': 'application/json', 'X-Requested-With': 'XMLHttpRequest' },
      credentials: 'same-origin'
    }).then(function (r) { return r.ok ? r.json() : null; }).then(function (data) {
      if (!data) { return; }
      (data.messages || []).forEach(appendMessage);
      (data.read_message_ids || []).forEach(function (id) { markRead(thread.querySelector('[data-receipt-id="' + id + '"]')); });
      if (data.initial_message_read) { markRead(document.getElementById('initial-read-receipt')); }
      if (data.closed) { lockAsClosed(); }
    }).catch(function () {}).then(function () { polling = false; schedule(); });
  }
  function schedule() {
    window.clearTimeout(timer);
    if (closedBar && !closedBar.hidden) { return; }
    timer = window.setTimeout(poll, 4000);
  }
  document.addEventListener('visibilitychange', function () {
    if (!document.hidden) { window.clearTimeout(timer); poll(); }
  });

  thread.addEventListener('scroll', function () {
    if (nearBottom()) { pending = 0; }
    setPending(pending);
  }, { passive: true });
  if (jump) { jump.addEventListener('click', function () { toBottom(true); }); }

  var toast = document.getElementById('chat-toast');
  if (toast) {
    window.setTimeout(function () { toast.classList.add('out'); }, 3500);
    window.setTimeout(function () { toast.remove(); }, 4100);
  }

  /* ───── نمایشگر تصویر ───── */
  var lb = document.getElementById('chat-lightbox');
  if (lb) {
    var lbImg = document.getElementById('chat-lb-img');
    var lbName = document.getElementById('chat-lb-name');
    var lbDl = document.getElementById('chat-lb-dl');
    var prevBtn = lb.querySelector('[data-lb="prev"]');
    var nextBtn = lb.querySelector('[data-lb="next"]');
    var gallery = [], idx = 0;

    var show = function (i) {
      idx = (i + gallery.length) % gallery.length;
      var a = gallery[idx];
      lbImg.src = a.href; lbImg.alt = a.dataset.name || '';
      lbName.textContent = a.dataset.name || '';
      lbDl.href = a.href; lbDl.setAttribute('download', a.dataset.name || 'image');
      prevBtn.hidden = nextBtn.hidden = gallery.length < 2;
    };
    var close = function () { lb.hidden = true; lbImg.removeAttribute('src'); };

    thread.addEventListener('click', function (e) {
      var a = e.target.closest('a[data-lightbox]');
      if (!a) { return; }
      e.preventDefault();
      gallery = Array.prototype.slice.call(thread.querySelectorAll('a[data-lightbox]'));
      lb.hidden = false;
      show(gallery.indexOf(a));
    });
    lb.addEventListener('click', function (e) {
      var act = e.target.closest('[data-lb]');
      if (act) {
        var v = act.dataset.lb;
        if (v === 'close') { close(); } else { show(idx + (v === 'next' ? 1 : -1)); }
      } else if (e.target === lb) { close(); }
    });
    document.addEventListener('keydown', function (e) {
      if (lb.hidden) { return; }
      if (e.key === 'Escape') { close(); }
      // در RTL جهت‌ها برعکس است.
      if (e.key === 'ArrowLeft') { show(idx + 1); }
      if (e.key === 'ArrowRight') { show(idx - 1); }
    });
  }

  /* ───── نوشتن و ارسال ───── */
  if (form) {
    var textarea = form.querySelector('textarea[name="body"]');
    var send = form.querySelector('.send');
    var errBox = document.getElementById('chat-error');
    var counter = document.getElementById('chat-count');
    var csrf = form.querySelector('input[name="_token"]').value;
    var fileInput = document.getElementById('chat-file');
    var attachBtn = document.getElementById('chat-attach');
    var tray = document.getElementById('chat-tray');
    var staged = [];
    var sending = false;
    var replyInput = form.querySelector('input[name="reply_to_message_id"]');
    var replyBar = document.getElementById('chat-reply-bar');
    var replyLabel = document.getElementById('chat-reply-label');
    var clearReply = function () { replyInput.value = ''; replyBar.hidden = true; replyLabel.textContent = ''; };
    document.getElementById('chat-reply-cancel').addEventListener('click', clearReply);
    thread.addEventListener('click', function (e) {
      var b = e.target.closest('[data-reply-id]');
      if (!b || form.hidden) { return; }
      replyInput.value = b.dataset.replyId;
      replyLabel.textContent = '';
      replyLabel.append(el('b', null, b.dataset.replySender || 'کاربر'), document.createTextNode(' ' + String(b.dataset.replyBody || '').slice(0, 110)));
      replyBar.hidden = false;
      textarea.focus();
    });

    var showError = function (t) { if (errBox) { errBox.textContent = t || ''; } };

    var sync = function () {
      textarea.style.height = 'auto';
      textarea.style.height = Math.min(textarea.scrollHeight + 2, 130) + 'px';
      var len = textarea.value.length;
      send.disabled = sending || (textarea.value.trim() === '' && staged.length === 0);
      if (counter) {
        counter.textContent = len > MAX - 300 ? faDigits(len) + ' / ' + faDigits(MAX) : '';
        counter.classList.toggle('warn', len > MAX - 60);
      }
    };

    var renderTray = function () {
      tray.textContent = '';
      tray.hidden = staged.length === 0;
      staged.forEach(function (item, i) {
        var chip = el('div', 'chip');
        if (item.file.type.indexOf('image/') === 0) {
          if (!item.preview) { item.preview = URL.createObjectURL(item.file); }
          var im = document.createElement('img'); im.src = item.preview; im.alt = '';
          chip.appendChild(im);
        } else {
          var ci = el('span', 'ci'); ci.appendChild(icon('i-file')); chip.appendChild(ci);
        }
        var t = el('span');
        var b = el('b', null, item.file.name); b.setAttribute('dir', 'auto');
        t.append(b, el('small', null, sizeLabel(item.file.size)));
        chip.appendChild(t);
        var rm = el('button'); rm.type = 'button'; rm.setAttribute('aria-label', 'حذف فایل');
        rm.appendChild(icon('i-x'));
        rm.addEventListener('click', function () {
          if (sending) { return; }
          if (item.preview) { URL.revokeObjectURL(item.preview); }
          staged.splice(i, 1); renderTray(); sync();
        });
        chip.appendChild(rm);
        var bar = el('i', 'bar'); item.bar = bar; chip.appendChild(bar);
        tray.appendChild(chip);
      });
    };

    var addFiles = function (files) {
      showError('');
      Array.prototype.forEach.call(files, function (f) {
        if (staged.length >= MAX_FILES) { showError('در هر پیام حداکثر ۵ فایل می‌توانید بفرستید.'); return; }
        var ok = ALLOWED.indexOf(f.type) !== -1 || /\.(jpe?g|png|webp|pdf)$/i.test(f.name);
        if (!ok) { showError('فقط تصویر (JPG، PNG، WebP) و فایل PDF قابل ارسال است.'); return; }
        if (f.size > MAX_SIZE) { showError('حجم هر فایل باید کمتر از ۱۰ مگابایت باشد.'); return; }
        staged.push({ file: f });
      });
      renderTray(); sync();
    };

    attachBtn.addEventListener('click', function () { fileInput.click(); });
    fileInput.addEventListener('change', function () { addFiles(fileInput.files); fileInput.value = ''; });

    // کشیدن‌ورهاکردن و چسباندن (Ctrl+V) تصویر
    var dragDepth = 0;
    var hasFiles = function (e) { return e.dataTransfer && Array.prototype.indexOf.call(e.dataTransfer.types || [], 'Files') !== -1; };
    root.addEventListener('dragenter', function (e) { if (hasFiles(e) && !form.hidden) { dragDepth++; root.classList.add('dragging'); } });
    root.addEventListener('dragleave', function () { dragDepth = Math.max(0, dragDepth - 1); if (!dragDepth) { root.classList.remove('dragging'); } });
    root.addEventListener('dragover', function (e) { if (hasFiles(e)) { e.preventDefault(); } });
    root.addEventListener('drop', function (e) {
      if (!hasFiles(e)) { return; }
      e.preventDefault(); dragDepth = 0; root.classList.remove('dragging');
      if (!form.hidden) { addFiles(e.dataTransfer.files); }
    });
    textarea.addEventListener('paste', function (e) {
      var files = e.clipboardData && e.clipboardData.files;
      if (files && files.length) { e.preventDefault(); addFiles(files); }
    });

    textarea.addEventListener('input', function () { showError(''); sync(); });
    textarea.addEventListener('keydown', function (e) {
      if (e.key === 'Enter' && !e.shiftKey && !e.isComposing && !window.matchMedia('(hover: none)').matches) {
        e.preventDefault();
        if (!send.disabled) { form.requestSubmit(); }
      }
    });

    var setProgress = function (p) { staged.forEach(function (s) { if (s.bar) { s.bar.style.transform = 'scaleX(' + p + ')'; } }); };

    form.addEventListener('submit', function (event) {
      event.preventDefault();
      var body = textarea.value.trim();
      if ((!body && !staged.length) || sending) { return; }
      sending = true; send.disabled = true; send.setAttribute('aria-busy', 'true'); showError('');

      var data = new FormData();
      data.append('body', body);
      if (replyInput.value) { data.append('reply_to_message_id', replyInput.value); }
      staged.forEach(function (s) { data.append('attachments[]', s.file); });

      var xhr = new XMLHttpRequest();
      xhr.open('POST', form.action);
      xhr.setRequestHeader('Accept', 'application/json');
      xhr.setRequestHeader('X-CSRF-TOKEN', csrf);
      xhr.setRequestHeader('X-Requested-With', 'XMLHttpRequest');
      if (xhr.upload) {
        xhr.upload.onprogress = function (e) { if (e.lengthComputable) { setProgress(e.loaded / e.total); } };
      }
      var done = function () {
        sending = false; send.removeAttribute('aria-busy'); sync();
      };
      xhr.onload = function () {
        var res = {};
        try { res = JSON.parse(xhr.responseText); } catch (e) {}
        if (xhr.status >= 200 && xhr.status < 300 && res.message) {
          appendMessage(res.message);
          textarea.value = '';
          staged.forEach(function (s) { if (s.preview) { URL.revokeObjectURL(s.preview); } });
          staged = []; renderTray(); clearReply();
          if (!window.matchMedia('(hover: none)').matches) { textarea.focus(); }
        } else {
          var text = res.message;
          if (res.errors) {
            var first = Object.keys(res.errors)[0];
            if (first) { text = res.errors[first][0]; }
          }
          if (xhr.status === 419) { text = 'نشست شما منقضی شده است. صفحه را دوباره بارگذاری کنید.'; }
          if (xhr.status === 413) { text = 'حجم فایل‌ها بیش از حد مجاز است.'; }
          if (xhr.status === 403) { lockAsClosed(); text = 'این گفتگو بسته شده است.'; }
          showError(text || 'ارسال پیام انجام نشد. دوباره تلاش کنید.');
          setProgress(0);
        }
        done();
      };
      xhr.onerror = function () { showError('خطا در ارتباط با سرور.'); setProgress(0); done(); };
      xhr.send(data);
    });

    sync();
  }

  toBottom(false);
  schedule();
})();
