/*
 * Matir Khamar service worker.
 *
 * Keeps on the phone:
 *  - the app's built files (they never change once built, so cache-first);
 *  - the /offline page, refreshed every time it is opened with internet;
 *  - the last copy of every page opened (network first: with internet a page is
 *    always fresh; with none, the last copy shows, so the app works offline).
 * Forms sent with no internet wait in the page's own queue (offlineQueue.ts).
 * Signing out deletes the kept pages.
 */
const ASSETS = 'khamar-assets-v1';
const PAGES = 'khamar-pages-v1';
const DATA = 'khamar-data-v1';
const OFFLINE_URL = '/offline';
const KEEP = 80;

const FALLBACK = `<!doctype html><html lang="bn"><meta charset="utf-8"><meta name="viewport" content="width=device-width,initial-scale=1">
<title>নেট নেই</title><body style="font-family:system-ui;background:#f7f2e7;color:#3b2f22;display:grid;place-items:center;min-height:100vh;margin:0;text-align:center;padding:24px">
<div><h1 style="font-weight:600">নেট নেই</h1><p>অফলাইনে এন্ট্রি দিতে আগে একবার নেট থাকা অবস্থায় অ্যাপে লগইন করে "অফলাইন এন্ট্রি" পাতা খুলুন।</p></div></body></html>`;

self.addEventListener('install', () => self.skipWaiting());

self.addEventListener('activate', (event) => {
    event.waitUntil(
        caches
            .keys()
            .then((keys) =>
                Promise.all(
                    keys
                        .filter(
                            (key) =>
                                key.startsWith('khamar-') &&
                                ![ASSETS, PAGES, DATA].includes(key),
                        )
                        .map((key) => caches.delete(key)),
                ),
            )
            .then(() => self.clients.claim()),
    );
});

self.addEventListener('message', (event) => {
    if (event.data === 'clear-pages') {
        event.waitUntil(
            Promise.all([caches.delete(PAGES), caches.delete(DATA)]),
        );
    }

    if (event.data === 'precache-offline') {
        event.waitUntil(refreshOfflinePage().catch(() => undefined));
    }
});

/** Fetches /offline, keeps it, and keeps every built file it needs. */
async function refreshOfflinePage(request = OFFLINE_URL) {
    const response = await fetch(request, {
        credentials: 'same-origin',
        redirect: 'follow',
    });
    const url = new URL(response.url);

    // Signed out (sent to /login) or refused: never keep that.
    if (!response.ok || url.pathname !== OFFLINE_URL) {
        return response;
    }

    const html = await response.clone().text();
    const pages = await caches.open(PAGES);
    await pages.put(
        OFFLINE_URL,
        new Response(html, {
            headers: { 'Content-Type': 'text/html; charset=utf-8' },
        }),
    );

    const assets = await caches.open(ASSETS);
    const files = [
        ...new Set(
            [...html.matchAll(/(?:href|src)="([^"]*\/build\/[^"]+)"/g)].map(
                (match) => new URL(match[1], self.location.origin).pathname,
            ),
        ),
    ];
    await Promise.all(
        files.map((file) =>
            assets
                .match(file)
                .then((hit) => hit || assets.add(file).catch(() => undefined)),
        ),
    );
    await assets
        .addAll([
            '/manifest.webmanifest',
            '/favicon.svg',
            '/icons/icon-192.png',
        ])
        .catch(() => undefined);

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

/** Pages that are never kept: signing in and out, and answers meant only for now. */
function keepable(url) {
    return !/^\/(login|logout|register|two-factor|forgot-password|reset-password|user\/confirm|push|notifications|offline\/sync|up)\b/.test(
        url.pathname,
    );
}

async function trim(cache) {
    const keys = await cache.keys();

    for (const key of keys.slice(0, Math.max(0, keys.length - KEEP))) {
        await cache.delete(key);
    }
}

/**
 * The network first; a good answer is kept under `key`. With no internet, the kept copy.
 * Only answers for the page asked for are kept (not one that sent the person to sign in).
 */
async function networkFirst(request, cacheName, key) {
    try {
        const response = await fetch(request);
        const landed = new URL(response.url || request.url);

        if (response.ok && !response.redirected && keepable(landed)) {
            const cache = await caches.open(cacheName);
            await cache.put(key, response.clone());
            trim(cache);
        }

        return response;
    } catch (error) {
        const hit = await caches.match(key, { cacheName });

        if (hit) {
            return hit;
        }

        throw error;
    }
}

/**
 * A page change with no internet whose data was never kept on its own: taken from the kept
 * copy of the whole page (opened by its address once), which carries the same data.
 */
async function dataFromKeptPage(key) {
    const html = await caches.match(key, { cacheName: PAGES });
    const match = html
        ? (await html.text()).match(
              /<script[^>]*data-page[^>]*>([\s\S]*?)<\/script>/,
          )
        : null;

    if (!match) {
        return null;
    }

    return new Response(match[1], {
        headers: {
            'Content-Type': 'application/json',
            'X-Inertia': 'true',
            Vary: 'X-Inertia',
        },
    });
}

async function offlinePage() {
    return (
        (await caches.match(OFFLINE_URL, { cacheName: PAGES })) ||
        new Response(FALLBACK, {
            headers: { 'Content-Type': 'text/html; charset=utf-8' },
        })
    );
}

self.addEventListener('fetch', (event) => {
    const { request } = event;

    if (request.method !== 'GET') {
        return;
    }

    const url = new URL(request.url);

    if (
        url.origin === self.location.origin &&
        url.pathname.startsWith('/build/')
    ) {
        event.respondWith(cacheFirst(request));

        return;
    }

    if (url.hostname === 'fonts.bunny.net') {
        event.respondWith(cacheFirst(request));

        return;
    }

    if (url.origin !== self.location.origin || !keepable(url)) {
        return;
    }

    // A page change inside the app (Inertia): its data, the last copy when offline. Partial
    // reloads ask for a few props only, so they are neither kept nor answered from a copy.
    if (
        request.mode !== 'navigate' &&
        request.headers.get('X-Inertia') &&
        !request.headers.get('X-Inertia-Partial-Data') &&
        !request.headers.get('X-Inertia-Partial-Except')
    ) {
        const key = `${url.pathname}${url.search}`;
        event.respondWith(
            networkFirst(request, DATA, key).catch(async (error) => {
                const kept = await dataFromKeptPage(key);

                if (kept) {
                    return kept;
                }

                throw error;
            }),
        );

        return;
    }

    if (request.mode !== 'navigate') {
        return;
    }

    if (url.pathname === OFFLINE_URL) {
        event.respondWith(
            refreshOfflinePage(request).catch(() => offlinePage()),
        );

        return;
    }

    // Any other page: the network; with none, the page's last copy, else the offline page.
    event.respondWith(
        networkFirst(request, PAGES, `${url.pathname}${url.search}`).catch(() =>
            offlinePage(),
        ),
    );
});

/*
 * Notifications. The server sends {title, body, url, tag, actions[{action, title, url}]};
 * the action buttons ("হয়ে গেছে", "১ ঘণ্টা পরে") post to their signed link, no app needed.
 */
/** Tells the app this phone received a push, so it knows what reaches the phone. */
async function reportReceived() {
    try {
        const subscription =
            await self.registration.pushManager.getSubscription();

        if (subscription) {
            await fetch('/push/received', {
                method: 'POST',
                credentials: 'include',
                headers: {
                    'Content-Type': 'application/json',
                    Accept: 'application/json',
                },
                body: JSON.stringify({ endpoint: subscription.endpoint }),
            });
        }
    } catch {
        // Offline or signed out: nothing to report.
    }
}

self.addEventListener('push', (event) => {
    event.waitUntil(reportReceived());

    // A push that comes without its words (or with words the phone cannot read) fetches the
    // newest unseen notifications from the app instead, so nothing is lost silently.
    if (!event.data) {
        event.waitUntil(showLatest());

        return;
    }

    let data = {};

    try {
        data = event.data.json();
    } catch {
        data = { title: 'মাটির খামার', body: event.data.text() };
    }

    const actions = (data.actions || []).slice(0, 2);
    const alarm = Boolean(data.alarm);
    // An alarm opens the app's full-screen ringing view when tapped.
    const url = alarm
        ? `/reminders?ring=${data.reminder_id}&title=${encodeURIComponent(data.title || '')}`
        : data.url || '/dashboard';

    event.waitUntil(
        Promise.all([
            self.registration.showNotification(data.title || 'মাটির খামার', {
                body: data.body || '',
                tag: data.tag,
                // Every notification makes its sound, even one that replaces an older one.
                renotify: Boolean(data.tag),
                silent: false,
                requireInteraction: Boolean(data.requireInteraction) || alarm,
                icon: '/icons/icon-192.png',
                badge: '/icons/icon-192.png',
                // An alarm shakes the phone long, like a ringing clock.
                vibrate: alarm
                    ? [800, 300, 800, 300, 800, 300, 800, 300, 1200]
                    : [300, 120, 300, 120, 600],
                lang: 'bn',
                data: {
                    url,
                    actions: Object.fromEntries(
                        actions.map((a) => [a.action, a.url]),
                    ),
                },
                actions: actions.map((a) => ({
                    action: a.action,
                    title: a.title,
                })),
            }),
            // An open app hears of every notification too: it shows a banner, and the
            // test on the reminders page learns that the phone really received it.
            self.clients
                .matchAll({ type: 'window', includeUncontrolled: true })
                .then((windows) =>
                    windows.forEach((w) =>
                        w.postMessage({
                            type: 'khamar-push',
                            title: data.title,
                            body: data.body,
                            tag: data.tag,
                        }),
                    ),
                ),
            // If the app is open, it rings on screen too.
            alarm
                ? self.clients
                      .matchAll({ type: 'window', includeUncontrolled: true })
                      .then((windows) =>
                          windows.forEach((w) =>
                              w.postMessage({
                                  type: 'khamar-alarm',
                                  id: data.reminder_id,
                                  title: data.title,
                                  body: data.body,
                              }),
                          ),
                      )
                : Promise.resolve(),
        ]),
    );
});

/** The newest unseen notifications, read from the app with the phone's own login. */
async function showLatest() {
    let items = [];

    try {
        const response = await fetch('/notifications/latest', {
            credentials: 'include',
            headers: { Accept: 'application/json' },
        });
        items = response.ok ? await response.json() : [];
    } catch {
        items = [];
    }

    if (!items.length) {
        items = [
            {
                title: 'মাটির খামার',
                body: 'নতুন নোটিফিকেশন — অ্যাপ খুলে দেখুন।',
                url: '/dashboard',
            },
        ];
    }

    await Promise.all(
        items.map((n) =>
            self.registration.showNotification(n.title || 'মাটির খামার', {
                body: n.body || '',
                tag: n.tag || `n-${n.id ?? Date.now()}`,
                renotify: true,
                silent: false,
                icon: '/icons/icon-192.png',
                badge: '/icons/icon-192.png',
                vibrate: [300, 120, 300, 120, 600],
                lang: 'bn',
                data: { url: n.url || '/dashboard', actions: {} },
            }),
        ),
    );
}

self.addEventListener('notificationclick', (event) => {
    const { url, actions } = event.notification.data || {};
    event.notification.close();

    if (event.action && actions && actions[event.action]) {
        event.waitUntil(
            fetch(actions[event.action], {
                method: 'POST',
                credentials: 'omit',
            }).catch(() => undefined),
        );

        return;
    }

    event.waitUntil(
        self.clients
            .matchAll({ type: 'window', includeUncontrolled: true })
            .then((windows) => {
                const open = windows.find(
                    (w) => new URL(w.url).origin === self.location.origin,
                );

                if (open) {
                    open.focus();

                    return open.navigate(url || '/dashboard');
                }

                return self.clients.openWindow(url || '/dashboard');
            }),
    );
});
