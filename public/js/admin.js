(function () {
  var root = document.documentElement;
  var $ = function (id) { return document.getElementById(id); };

  // کشوی منو (موبایل)
  var side = $('side'), scrim = $('scrim');
  function drawer(open) { if (!side) return; side.classList.toggle('open', open); scrim.classList.toggle('open', open); }
  ['burger', 'more'].forEach(function (id) { if ($(id)) $(id).addEventListener('click', function () { drawer(true); }); });
  if (scrim) scrim.addEventListener('click', function () { drawer(false); });
  document.addEventListener('keydown', function (e) { if (e.key === 'Escape') drawer(false); });

  // ---- حالت تیره و روشن (پیش‌فرض: روشن؛ از تنظیم سیستم‌عامل پیروی نمی‌کند)
  var themeMeta = document.querySelector('meta[name="theme-color"]');
  var lightColor = themeMeta ? themeMeta.getAttribute('content') : null;
  function applyTheme(t) {
    root.setAttribute('data-theme', t);
    if (themeMeta && lightColor) themeMeta.setAttribute('content', t === 'dark' ? '#0a1413' : lightColor);
  }
  try { var t = localStorage.getItem('borjik-theme'); applyTheme(t === 'dark' ? 'dark' : 'light'); } catch (e) { applyTheme('light'); }
  var tb = $('theme');
  if (tb) tb.addEventListener('click', function () {
    var next = root.getAttribute('data-theme') === 'dark' ? 'light' : 'dark';
    applyTheme(next);
    try { localStorage.setItem('borjik-theme', next); } catch (e) {}
  });

  // جست‌وجوی زنده در منوی مدیریت، با حفظ گروه‌بندی و دسترسی‌های مجاز
  var menuSearch = $('admin-menu-search');
  var nav = document.querySelector('#side .nav');
  if (menuSearch && nav) {
    var emptyResult = document.createElement('div');
    emptyResult.className = 'nav-empty';
    emptyResult.hidden = true;
    emptyResult.textContent = 'موردی با این عبارت پیدا نشد.';
    nav.appendChild(emptyResult);
    function filterMenu() {
      var query = menuSearch.value.trim().toLocaleLowerCase('fa');
      var children = Array.prototype.slice.call(nav.children);
      var group = null, groupHasMatch = false, total = 0;
      children.forEach(function (item) {
        if (item === emptyResult) return;
        if (item.classList.contains('grp')) {
          if (group) group.hidden = !groupHasMatch;
          group = item; groupHasMatch = false; return;
        }
        if (item.tagName !== 'A') return;
        var matches = !query || item.textContent.toLocaleLowerCase('fa').indexOf(query) !== -1;
        item.hidden = !matches;
        if (matches) { groupHasMatch = true; total += 1; }
      });
      if (group) group.hidden = !groupHasMatch;
      emptyResult.hidden = total > 0;
    }
    menuSearch.addEventListener('input', filterMenu);
    document.addEventListener('keydown', function (event) {
      if (event.key !== '/' || event.ctrlKey || event.metaKey || event.altKey) return;
      var target = event.target;
      if (target && (/INPUT|TEXTAREA|SELECT/.test(target.tagName) || target.isContentEditable)) return;
      event.preventDefault(); menuSearch.focus();
    });
    menuSearch.addEventListener('keydown', function (event) {
      if (event.key === 'Escape' && menuSearch.value) { menuSearch.value = ''; filterMenu(); menuSearch.blur(); }
    });
  }

  // حفظ موقع اسکرول منوی کناری پنل مدیریت سایت بین جابه‌جایی صفحات
  var adminNav = document.querySelector('body.adm #side .nav');
  if (adminNav) {
    var sidebarScrollKey = 'borjik-admin-sidebar-scroll';
    function saveAdminSidebarScroll() {
      try { sessionStorage.setItem(sidebarScrollKey, String(adminNav.scrollTop)); } catch (e) {}
    }
    function restoreAdminSidebarScroll() {
      try {
        var saved = sessionStorage.getItem(sidebarScrollKey);
        if (saved !== null && saved !== '') adminNav.scrollTop = Math.max(0, Number(saved) || 0);
      } catch (e) {}
    }
    restoreAdminSidebarScroll();
    window.addEventListener('pageshow', restoreAdminSidebarScroll);
    adminNav.addEventListener('scroll', saveAdminSidebarScroll, { passive: true });
    adminNav.addEventListener('click', function (event) {
      if (event.target.closest('a[href]')) saveAdminSidebarScroll();
    });
    window.addEventListener('pagehide', saveAdminSidebarScroll);
  }

})();
