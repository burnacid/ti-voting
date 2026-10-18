// Service worker for the installed web app. The game needs a live connection, so nothing is served
// from the cache except a friendly page when a page cannot be loaded because the phone is offline.
const CACHE = 'ti4-offline-v1';
const OFFLINE_FILES = ['/offline.html', '/logo.svg'];

self.addEventListener('install', (event) => {
    event.waitUntil(caches.open(CACHE).then((cache) => cache.addAll(OFFLINE_FILES)));
    self.skipWaiting();
});

self.addEventListener('activate', (event) => {
    event.waitUntil(
        caches.keys().then((keys) => Promise.all(keys.filter((key) => key !== CACHE).map((key) => caches.delete(key))))
    );
    self.clients.claim();
});

self.addEventListener('fetch', (event) => {
    if (event.request.mode === 'navigate') {
        event.respondWith(fetch(event.request).catch(() => caches.match('/offline.html')));
    } else if (OFFLINE_FILES.includes(new URL(event.request.url).pathname)) {
        // The logo on the offline page
        event.respondWith(fetch(event.request).catch(() => caches.match(event.request)));
    }
});
