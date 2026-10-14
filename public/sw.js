const CACHE = 'borjik-v2';
const SHELL = ['/offline.html', '/css/app.css', '/js/app.js', '/pwa/icon-192.png', '/pwa/icon-512.png'];

self.addEventListener('install', e => { e.waitUntil(caches.open(CACHE).then(c => c.addAll(SHELL))); self.skipWaiting(); });
self.addEventListener('activate', e => {
  e.waitUntil(caches.keys().then(keys => Promise.all(keys.filter(k => k !== CACHE).map(k => caches.delete(k)))));
  self.clients.claim();
});

self.addEventListener('fetch', e => {
  const r = e.request;
  if (r.method !== 'GET') return;                       // فرم‌ها و API هرگز کش نمی‌شوند
  if (r.mode === 'navigate') {                          // صفحه‌ها: همیشه از شبکه؛ بدون اینترنت صفحه‌ی آفلاین
    e.respondWith(fetch(r).catch(() => caches.match('/offline.html')));
    return;
  }
  const u = new URL(r.url);
  if (u.origin === location.origin && /\.(css|js|png|svg|woff2?)$/.test(u.pathname)) {   // فایل‌های استاتیک: اول شبکه، بعد کش
    e.respondWith(fetch(r).then(res => { const c = res.clone(); caches.open(CACHE).then(x => x.put(r, c)); return res; }).catch(() => caches.match(r)));
  }
});
