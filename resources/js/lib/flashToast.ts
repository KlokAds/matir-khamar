import { router } from '@inertiajs/vue3';
import { toast } from 'vue-sonner';
import type { FlashToast } from '@/types/ui';

export function initializeFlashToast(): void {
    router.on('flash', (event) => {
        const flash = (event as CustomEvent).detail?.flash;
        const data = flash?.toast as FlashToast | undefined;

        if (!data) {
            return;
        }

        toast[data.type](data.message);
    });
}

/**
 * While the app is open, a phone notification also shows as a banner inside it, the way chat
 * apps do. The service worker tells every open window about each push it receives.
 */
export function initializePushBanner(): void {
    if (typeof navigator === 'undefined' || !('serviceWorker' in navigator)) {
        return;
    }

    navigator.serviceWorker.addEventListener('message', (event) => {
        const data = event.data as
            | { type?: string; title?: string; body?: string; tag?: string }
            | undefined;

        if (data?.type !== 'khamar-push' || data.tag === 'test') {
            return;
        }

        toast(data.title ?? '', { description: data.body, duration: 8000 });
    });
}
