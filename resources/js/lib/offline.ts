import { ask } from '@/lib/dialog';
import { t } from '@/lib/i18n';
import { router } from '@inertiajs/vue3';
import { toast } from 'vue-sonner';
import { enqueueForm, watchQueue } from '@/lib/offlineQueue';

type Shared = {
    auth?: { user?: { id: number } | null; can?: Record<string, boolean> };
};

const PRECACHE_EVERY_MS = 30 * 60 * 1000;

function sharedProps(): Shared {
    const raw = document.querySelector('script[data-page]')?.textContent;

    try {
        return raw ? (JSON.parse(raw).props as Shared) : {};
    } catch {
        return {};
    }
}

/** Asks the service worker to keep a fresh copy of /offline, at most twice an hour. */
function precacheOfflinePage() {
    try {
        const last = Number(
            localStorage.getItem('khamar:offline-precache') ?? 0,
        );

        if (Date.now() - last < PRECACHE_EVERY_MS) {
            return;
        }

        localStorage.setItem('khamar:offline-precache', String(Date.now()));
    } catch {
        // Private mode: just refresh every time.
    }

    navigator.serviceWorker.ready
        .then((registration) =>
            registration.active?.postMessage('precache-offline'),
        )
        .catch(() => undefined);
}

export function initializeOffline(): void {
    if (
        'serviceWorker' in navigator &&
        (location.protocol === 'https:' ||
            location.hostname.endsWith('.test') ||
            location.hostname === 'localhost')
    ) {
        navigator.serviceWorker.register('/sw.js').catch(() => undefined);
    }

    const start = (props: Shared) => {
        const user = props.auth?.user;

        if (
            !user ||
            !props.auth?.can?.['post-daily'] ||
            !('indexedDB' in window)
        ) {
            return;
        }

        watchQueue(user.id);

        if ('serviceWorker' in navigator) {
            precacheOfflinePage();
        }
    };

    let props = sharedProps();
    start(props);
    router.on('navigate', (event) => {
        props = (event as CustomEvent).detail.page.props as Shared;
        start(props);
    });

    /*
     * Any form sent with no internet is kept on the phone and goes to the server, in order,
     * once the net is back. The page then opens again from the phone's copy, empty, so the
     * same entry is not given twice.
     */
    type Visit = { method: string; url: URL; data: unknown };
    let lastSend: Visit | null = null;

    const keep = async (visit: Visit) => {
        const user = props.auth?.user;

        if (!user || !('indexedDB' in window)) {
            toast.error(t('নেট নেই — নেট এলে আবার পাঠান।'));

            return;
        }

        await enqueueForm(user.id, document.title.split(' - ')[0], {
            method: visit.method,
            url: visit.url.pathname + visit.url.search,
            data: visit.data,
        });

        try {
            sessionStorage.setItem('khamar:kept-offline', '1');
        } catch {
            // ignore
        }

        window.location.reload();
    };

    router.on('before', (event) => {
        const visit = (event as CustomEvent).detail.visit as Visit & {
            prefetch?: boolean;
        };

        if (visit.method === 'get' || visit.prefetch) {
            return;
        }

        lastSend = visit;

        if (!navigator.onLine) {
            event.preventDefault();
            void keep(visit);
        }
    });

    let lastOpen: URL | null = null;
    router.on('start', (event) => {
        const visit = (event as CustomEvent).detail.visit as Visit & {
            prefetch?: boolean;
            async?: boolean;
        };
        // Only a real page change (not a link fetched ahead, nor a quiet reload of a part).
        lastOpen =
            visit.method === 'get' && !visit.prefetch && !visit.async
                ? visit.url
                : null;
    });

    router.on('networkError', (event) => {
        if (lastSend) {
            // Sent, but the net dropped on the way: keep it (the server books it once only).
            event.preventDefault();
            const visit = lastSend;
            lastSend = null;
            void keep(visit);

            return;
        }

        // A page change that cannot reach the server: open it whole, which gives the phone's
        // last copy of it, or else the offline page.
        if (lastOpen) {
            event.preventDefault();
            window.location.href = lastOpen.href;
        }
    });
    router.on('finish', () => {
        lastSend = null;
    });

    try {
        if (sessionStorage.getItem('khamar:kept-offline')) {
            sessionStorage.removeItem('khamar:kept-offline');
            // After the page's toaster is on screen.
            window.setTimeout(
                () =>
                    toast.success(
                        t(
                            'নেট নেই — ফোনে রাখা হলো। নেট এলে নিজে থেকেই সার্ভারে যাবে।',
                        ),
                        { duration: 8000 },
                    ),
                600,
            );
        }
    } catch {
        // ignore
    }

    window.addEventListener('offline', () => {
        toast.warning(
            t(
                'নেট চলে গেছে। কাজ চালিয়ে যান — এন্ট্রি ফোনে থাকবে, নেট এলে যাবে। পাতায় শেষবারের তথ্য দেখাবে।',
            ),
            { duration: 8000 },
        );
    });
}

/** Before signing out: warn about entries still on the phone, and drop the saved page. */
export async function prepareLogout(pending: number): Promise<boolean> {
    if (
        pending > 0 &&
        !(await ask.confirm({
            title: t('লগআউট করবেন?'),
            body: t(
                'এই ফোনে :nটি এন্ট্রি এখনো পাঠানো হয়নি। লগআউট করলেও এগুলো ফোনে থাকবে, আবার লগইন করলে যাবে।',
                { n: pending },
            ),
            confirmText: t('লগআউট'),
        }))
    ) {
        return false;
    }

    const registration =
        'serviceWorker' in navigator
            ? await navigator.serviceWorker.getRegistration()
            : undefined;
    registration?.active?.postMessage('clear-pages');

    try {
        localStorage.removeItem('khamar:offline-precache');
    } catch {
        // ignore
    }

    return true;
}
