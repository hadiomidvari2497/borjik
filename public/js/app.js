(function () {
  var root = document.documentElement;
  var $ = function (id) { return document.getElementById(id); };

  // ---- کشوی منو (موبایل)
  var side = $('side'), scrim = $('scrim');
  function drawer(open) {
    if (side) side.classList.toggle('open', open);
    if (scrim) scrim.classList.toggle('open', open);
  }
  ['burger', 'more'].forEach(function (id) {
    if ($(id)) $(id).addEventListener('click', function () { drawer(true); });
  });
  if (scrim) scrim.addEventListener('click', function () { drawer(false); });

  // ---- سوئیچ ساختمان
  var swb = $('swb'), sw = $('sw');
  if (swb && sw) {
    swb.addEventListener('click', function () {
      var open = !sw.classList.contains('open');
      sw.classList.toggle('open', open);
      swb.setAttribute('aria-expanded', String(open));
    });

    document.addEventListener('click', function (e) {
      if (!sw.contains(e.target) && sw.classList.contains('open')) {
        sw.classList.remove('open');
        swb.setAttribute('aria-expanded', 'false');
      }
    });
  }

  // یک شنونده‌ی Escape برای هر دو منو؛ وضعیت ARIA و فوکوس همگام می‌ماند.
  document.addEventListener('keydown', function (e) {
    if (e.key !== 'Escape') return;

    drawer(false);

    if (swb && sw && sw.classList.contains('open')) {
      sw.classList.remove('open');
      swb.setAttribute('aria-expanded', 'false');
      swb.focus();
    }
  });

  // ---- حالت تیره و روشن
  try { var t = localStorage.getItem('borjik-theme'); if (t) root.setAttribute('data-theme', t); } catch (e) {}
  var tb = $('theme');
  if (tb) tb.addEventListener('click', function () {
    var cur = root.getAttribute('data-theme');
    var dark = cur === 'dark' || (!cur && matchMedia('(prefers-color-scheme: dark)').matches);
    var next = dark ? 'light' : 'dark';
    root.setAttribute('data-theme', next);
    try { localStorage.setItem('borjik-theme', next); } catch (e) {}
  });

  // ---- Service Worker (فقط روی https یا localhost)
  if ('serviceWorker' in navigator && (location.protocol === 'https:' || location.hostname === 'localhost')) {
    navigator.serviceWorker.register('/sw.js', { scope: '/' }).catch(function () {});
  }
})();
