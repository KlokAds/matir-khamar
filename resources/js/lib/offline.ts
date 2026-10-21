import { router } from '@inertiajs/vue3';
import { toast } from 'vue-sonner';
import { watchQueue } from '@/lib/offlineQueue';

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

    start(sharedProps());
    router.on('navigate', (event) =>
        start((event as CustomEvent).detail.page.props as Shared),
    );

    // A page change that fails for lack of internet goes to the saved offline page.
    router.on('networkError', () => {
        if (!navigator.onLine) {
            window.location.href = '/offline';
        }
    });

    window.addEventListener('offline', () => {
        if (location.pathname !== '/offline') {
            toast.warning(
                'নেট চলে গেছে। দুধ/খাবারের এন্ট্রি "অফলাইন এন্ট্রি" পাতায় দিন।',
                {
                    action: {
                        label: 'খুলুন',
                        onClick: () => (window.location.href = '/offline'),
                    },
                    duration: 10000,
                },
            );
        }
    });
}

/** Before signing out: warn about entries still on the phone, and drop the saved page. */
export async function prepareLogout(pending: number): Promise<boolean> {
    if (
        pending > 0 &&
        !window.confirm(
            `এই ফোনে ${pending}টি এন্ট্রি এখনো পাঠানো হয়নি। লগআউট করলেও এগুলো ফোনে থাকবে, আবার লগইন করলে যাবে। লগআউট করবেন?`,
        )
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
