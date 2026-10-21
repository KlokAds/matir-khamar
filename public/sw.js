/*
 * Matir Khamar service worker.
 *
 * Keeps two things on the phone:
 *  - the app's built files (they never change once built, so cache-first);
 *  - the /offline page, refreshed every time it is opened with internet.
 * With no internet, any page opens the saved /offline page, where daily
 * entries are written into the phone's queue. Nothing else is cached:
 * reports and the books always come fresh from the server.
 */
const ASSETS = 'khamar-assets-v1';
const PAGES = 'khamar-pages-v1';
const OFFLINE_URL = '/offline';

const FALLBACK = `<!doctype html><html lang="bn"><meta charset="utf-8"><meta name="viewport" content="width=device-width,initial-scale=1">
<title>নেট নেই</title><body style="font-family:system-ui;background:#f7f2e7;color:#3b2f22;display:grid;place-items:center;min-height:100vh;margin:0;text-align:center;padding:24px">
<div><h1 style="font-weight:600">নেট নেই</h1><p>অফলাইনে এন্ট্রি দিতে আগে একবার নেট থাকা অবস্থায় অ্যাপে লগইন করে "অফলাইন এন্ট্রি" পাতা খুলুন।</p></div></body></html>`;

self.addEventListener('install', () => self.skipWaiting());

self.addEventListener('activate', (event) => {
    event.waitUntil(
        caches.keys()
            .then((keys) => Promise.all(keys.filter((key) => key.startsWith('khamar-') && ![ASSETS, PAGES].includes(key)).map((key) => caches.delete(key))))
            .then(() => self.clients.claim()),
    );
});

self.addEventListener('message', (event) => {
    if (event.data === 'clear-pages') {
        event.waitUntil(caches.delete(PAGES));
    }

    if (event.data === 'precache-offline') {
        event.waitUntil(refreshOfflinePage().catch(() => undefined));
    }
});

/** Fetches /offline, keeps it, and keeps every built file it needs. */
async function refreshOfflinePage(request = OFFLINE_URL) {
    const response = await fetch(request, { credentials: 'same-origin', redirect: 'follow' });
    const url = new URL(response.url);

    // Signed out (sent to /login) or refused: never keep that.
    if (!response.ok || url.pathname !== OFFLINE_URL) {
        return response;
    }

    const html = await response.clone().text();
    const pages = await caches.open(PAGES);
    await pages.put(OFFLINE_URL, new Response(html, { headers: { 'Content-Type': 'text/html; charset=utf-8' } }));

    const assets = await caches.open(ASSETS);
    const files = [...new Set([...html.matchAll(/(?:href|src)="([^"]*\/build\/[^"]+)"/g)].map((match) => new URL(match[1], self.location.origin).pathname))];
    await Promise.all(files.map((file) => assets.match(file).then((hit) => hit || assets.add(file).catch(() => undefined))));
    await assets.addAll(['/manifest.webmanifest', '/favicon.svg', '/icons/icon-192.png']).catch(() => undefined);

    return response;
}

async function cacheFirst(request) {
    const cache = await caches.open(ASSETS);
    const hit = await cache.match(request);

    if (hit) {
        return hit;
    }

    const response = await fetch(request);

    if (response.ok || response.type === 'opaque') {
        cache.put(request, response.clone());
    }

    return response;
}

async function offlinePage() {
    return (await caches.match(OFFLINE_URL, { cacheName: PAGES })) || new Response(FALLBACK, { headers: { 'Content-Type': 'text/html; charset=utf-8' } });
}

self.addEventListener('fetch', (event) => {
    const { request } = event;

    if (request.method !== 'GET') {
        return;
    }

    const url = new URL(request.url);

    if (url.origin === self.location.origin && url.pathname.startsWith('/build/')) {
        event.respondWith(cacheFirst(request));

        return;
    }

    if (url.hostname === 'fonts.bunny.net') {
        event.respondWith(cacheFirst(request));

        return;
    }

    if (url.origin !== self.location.origin || request.mode !== 'navigate') {
        return;
    }

    if (url.pathname === OFFLINE_URL) {
        event.respondWith(refreshOfflinePage(request).catch(() => offlinePage()));

        return;
    }

    // Any other page: the network, and the saved offline page when there is none.
    event.respondWith(fetch(request).catch(() => offlinePage()));
});
