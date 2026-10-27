(function () {
  'use strict';
  function init() {
    var input = document.getElementById('side-menu-search');
    var nav = document.querySelector('#side .nav');
    if (!input || !nav) return;
    var empty = document.createElement('div');
    empty.className = 'nav-empty';
    empty.hidden = true;
    empty.textContent = 'موردی با این عبارت پیدا نشد.';
    nav.appendChild(empty);
    function filter() {
      var q = input.value.trim().toLocaleLowerCase('fa');
      var group = null, groupHas = false, total = 0;
      Array.prototype.slice.call(nav.children).forEach(function (item) {
        if (item === empty) return;
        if (item.classList.contains('grp')) {
          if (group) group.hidden = !groupHas;
          group = item; groupHas = false; return;
        }
        if (item.tagName !== 'A') return;
        var ok = !q || item.textContent.toLocaleLowerCase('fa').indexOf(q) !== -1;
        item.hidden = !ok;
        if (ok) { groupHas = true; total += 1; }
      });
      if (group) group.hidden = !groupHas;
      empty.hidden = total > 0;
    }
    input.addEventListener('input', filter);
    input.addEventListener('keydown', function (e) {
      if (e.key === 'Escape' && input.value) { input.value = ''; filter(); input.blur(); }
    });
    document.addEventListener('keydown', function (e) {
      if (e.key !== '/' || e.ctrlKey || e.metaKey || e.altKey) return;
      var t = e.target;
      if (t && (/INPUT|TEXTAREA|SELECT/.test(t.tagName) || t.isContentEditable)) return;
      e.preventDefault(); input.focus();
    });
  }
  if (document.readyState === 'loading') document.addEventListener('DOMContentLoaded', init); else init();
})();
