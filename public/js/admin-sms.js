(function () {
  var form = document.getElementById('prov-form');
  if (!form) return;

  function q(id) { return document.getElementById(id); }

  // افزودن/حذف ردیف هدر و پارامتر
  function addRow(kind, data) {
    var box = q('rows-' + kind), tpl = q('tpl-' + kind);
    var i = parseInt(box.dataset.next, 10); box.dataset.next = i + 1;
    var holder = document.createElement('div');
    holder.innerHTML = tpl.innerHTML.replace(/__i__/g, i);
    var row = holder.firstElementChild;
    box.appendChild(row);
    if (data) {
      var inputs = row.querySelectorAll('input');
      if (kind === 'headers') { inputs[0].value = data.name || ''; inputs[1].value = data.value || ''; }
      else {
        inputs[0].value = data.name || ''; inputs[1].value = data.value || '';
        inputs[2].checked = !!data.secret; inputs[1].type = data.secret ? 'password' : 'text';
      }
    }
  }
  form.addEventListener('click', function (e) {
    var add = e.target.closest('[data-add]');
    if (add) { addRow(add.dataset.add); return; }
    var rm = e.target.closest('[data-remove]');
    if (rm) rm.closest('.row-pair').remove();
  });
  // محرمانه ← مقدار به‌صورت نقطه نمایش داده شود
  form.addEventListener('change', function (e) {
    if (e.target.matches('[data-secret]')) {
      e.target.closest('.row-param').querySelector('[data-value]').type = e.target.checked ? 'password' : 'text';
    }
  });

  // نمونه‌های آماده
  var sel = q('preset'), raw = q('presets-data');
  if (sel && raw) {
    var presets = JSON.parse(raw.textContent);
    sel.addEventListener('change', function () {
      var p = presets[sel.value];
      q('preset-note').textContent = p ? p.note : 'با انتخاب نمونه، فرم زیر پر می‌شود؛ فقط کلید و شمارهٔ خط خودتان را وارد کنید. مشخصات همهٔ اپراتورها را می‌توانید خودتان هم بنویسید.';
      if (!p) return;
      var d = p.data;
      ['name', 'method', 'url', 'number_format', 'body_type', 'body', 'success_json_path', 'success_expected', 'success_contains', 'ref_path'].forEach(function (k) {
        if (q(k) && d[k] !== undefined) q(k).value = d[k];
      });
      ['headers', 'params'].forEach(function (kind) {
        q('rows-' + kind).innerHTML = '';
        (d[kind] || []).forEach(function (r) { addRow(kind, r); });
      });
    });
  }
})();
