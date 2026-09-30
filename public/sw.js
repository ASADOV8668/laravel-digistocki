const CACHE_NAME = 'digistocki-assets-v5';
const STATIC_ASSETS = [
    './manifest.json',
    './images/logo.png',
    './images/logo-192.png',
    './images/logo-512.png',
    './favicon.ico',
    './offline.html',
];

self.addEventListener('install', (event) => {
    event.waitUntil(
        caches.open(CACHE_NAME).then((cache) => cache.addAll(
            STATIC_ASSETS.map((asset) => new URL(asset, self.registration.scope).toString()),
        )),
    );
    self.skipWaiting();
});

self.addEventListener('activate', (event) => {
    event.waitUntil(
        caches.keys().then((keys) => Promise.all(
            keys.filter((key) => key.startsWith('digistocki-assets-') && key !== CACHE_NAME)
                .map((key) => caches.delete(key)),
        )).then(() => self.clients.claim()),
    );
});

self.addEventListener('fetch', (event) => {
    if (event.request.method !== 'GET') return;

    const requestUrl = new URL(event.request.url);
    if (requestUrl.origin !== self.location.origin) return;

    if (event.request.mode === 'navigate') {
        event.respondWith(
            fetch(event.request).catch(() => caches.match(
                new URL('./offline.html', self.registration.scope),
            )),
        );
        return;
    }

    if (!['font', 'image', 'script', 'style'].includes(event.request.destination)) return;

    event.respondWith(
        caches.match(event.request).then((cached) => cached || fetch(event.request).then((response) => {
            if (response.ok) {
                const copy = response.clone();
                caches.open(CACHE_NAME).then((cache) => cache.put(event.request, copy));
            }

            return response;
        })),
    );
});
