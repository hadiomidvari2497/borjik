(function () {
  var root = document.documentElement;
  var $ = function (id) { return document.getElementById(id); };

  // ---- کشوی منو (موبایل)
  var side = $('side'), scrim = $('scrim');
  function drawer(open) { side.classList.toggle('open', open); scrim.classList.toggle('open', open); }
  ['burger', 'more'].forEach(function (id) { if ($(id)) $(id).addEventListener('click', function () { drawer(true); }); });
  if (scrim) scrim.addEventListener('click', function () { drawer(false); });
  document.addEventListener('keydown', function (e) { if (e.key === 'Escape') { drawer(false); var sw = $('sw'); if (sw) sw.classList.remove('open'); } });

  // ---- سوئیچ ساختمان
  var swb = $('swb'), sw = $('sw');
  if (swb) {
    swb.addEventListener('click', function () { sw.classList.toggle('open'); swb.setAttribute('aria-expanded', sw.classList.contains('open')); });
    document.addEventListener('click', function (e) { if (!sw.contains(e.target)) sw.classList.remove('open'); });
  }

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
