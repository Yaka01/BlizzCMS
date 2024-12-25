importScripts('https://storage.googleapis.com/workbox-cdn/releases/7.0.0/workbox-sw.js');

const cacheName = 'blizzcms-cache-v1';

workbox.setConfig({
    debug: false, // Set to true if you need more debug details
});

// Exclude admin paths
const adminPath = '/admin/';

workbox.routing.registerRoute(
    ({ url }) => url.pathname.startsWith(adminPath),
    new workbox.strategies.NetworkOnly() // No caching for anything related to admin
);

// Strategy for static files, images, and documents
const cacheFirstStrategy = new workbox.strategies.StaleWhileRevalidate({
    cacheName: 'blizzcms-asset',
    plugins: [
        new workbox.cacheableResponse.CacheableResponsePlugin({
            statuses: [0, 200],
        }),
        new workbox.expiration.ExpirationPlugin({
            maxEntries: 100,
            maxAgeSeconds: 30 * 24 * 60 * 60, // 30 days
        }),
    ],
});

workbox.routing.registerRoute(
    ({ url }) => !url.pathname.startsWith(adminPath) && /\.(?:js|css|woff2|woff|ttf|otf|eot)$/.test(url.pathname),
    cacheFirstStrategy
);

workbox.routing.registerRoute(
    ({ url }) => !url.pathname.startsWith(adminPath) && /\.(?:png|jpg|jpeg|svg|gif|webp)$/.test(url.pathname),
    new workbox.strategies.StaleWhileRevalidate({
        cacheName: 'blizzcms-image',
        plugins: [
            new workbox.expiration.ExpirationPlugin({
                maxEntries: 60,
                maxAgeSeconds: 30 * 24 * 60 * 60, // 30 days
            }),
        ],
    })
);

// Installation event
self.addEventListener('install', (event) => {
    const urlsToCache = [
        '/'
    ];

    event.waitUntil(
        caches.open(cacheName)
            .then((cache) => {
                return Promise.all(
                    urlsToCache.map((url) => {
                        return cache.add(url).catch((error) => {
                            console.error(`Failed to cache ${url}:`, error);
                        });
                    })
                );
            })
            .then(() => self.skipWaiting())
    );
});

// Activation event
self.addEventListener('activate', (event) => {
    const currentCaches = [
        'blizzcms-asset',
        'blizzcms-image',
        'blizzcms-cache-v1'
    ];

    event.waitUntil(
        caches.keys()
            .then((cacheNames) =>
                Promise.all(
                    cacheNames
                        .filter((cacheName) => !currentCaches.includes(cacheName))
                        .map((cacheToDelete) => caches.delete(cacheToDelete))
                )
            )
            .then(() => self.clients.claim())
    );
});

// Offline document handler
workbox.routing.setCatchHandler(async ({ event }) => {
    if (event.request.destination === 'document') {
        return await caches.match('/offline.html');
    }
    return Response.error();
});
