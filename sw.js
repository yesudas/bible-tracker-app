// Minimal service worker: network-first for pages, cached static assets as an offline fallback.
const CACHE = 'bible-tracker-v2';
const SHELL = ['./assets/css/style.css', './assets/js/app.js', './assets/images/icon-192.png'];

self.addEventListener('install', (e) => {
    e.waitUntil(caches.open(CACHE).then((c) => c.addAll(SHELL)).catch(() => {}));
    self.skipWaiting();
});
self.addEventListener('activate', (e) => {
    e.waitUntil(
        caches.keys().then((keys) => Promise.all(keys.filter((k) => k !== CACHE).map((k) => caches.delete(k))))
            .then(() => self.clients.claim())
    );
});
self.addEventListener('fetch', (e) => {
    const req = e.request;
    if (req.method !== 'GET' || new URL(req.url).origin !== location.origin) return;
    if (req.url.includes('/api/')) return; // never cache API calls
    e.respondWith(
        fetch(req).then((res) => {
            if (req.url.includes('/assets/')) {
                const copy = res.clone();
                caches.open(CACHE).then((c) => c.put(req, copy));
            }
            return res;
        }).catch(() => caches.match(req).then((hit) => hit || Response.error()))
    );
});
