/**
 * Turning phone notifications on and off. The phone asks the person once; after that the
 * subscription is sent to the server, which uses it to deliver reminders even when the app
 * is closed. Works on Android browsers and, on iPhone, once the app is added to the home screen.
 */
export type PushState =
    | 'unsupported'
    | 'needs-install'
    | 'blocked'
    | 'off'
    | 'on';

function xsrfToken(): string {
    const match = document.cookie.match(/(?:^|;\s*)XSRF-TOKEN=([^;]+)/);

    return match ? decodeURIComponent(match[1]) : '';
}

async function post(url: string, body: unknown): Promise<boolean> {
    try {
        const response = await fetch(url, {
            method: 'POST',
            credentials: 'same-origin',
            headers: {
                'Content-Type': 'application/json',
                Accept: 'application/json',
                'X-Requested-With': 'XMLHttpRequest',
                'X-XSRF-TOKEN': xsrfToken(),
            },
            body: JSON.stringify(body),
        });

        return response.ok;
    } catch {
        return false;
    }
}

function keyBytes(base64: string): Uint8Array<ArrayBuffer> {
    const padded = (base64 + '='.repeat((4 - (base64.length % 4)) % 4))
        .replace(/-/g, '+')
        .replace(/_/g, '/');
    const raw = atob(padded);
    const bytes = new Uint8Array(new ArrayBuffer(raw.length));

    for (let i = 0; i < raw.length; i++) {
        bytes[i] = raw.charCodeAt(i);
    }

    return bytes;
}

const isIos = () => /iphone|ipad|ipod/i.test(navigator.userAgent);
const isStandalone = () =>
    window.matchMedia('(display-mode: standalone)').matches ||
    (navigator as unknown as { standalone?: boolean }).standalone === true;

export async function pushState(): Promise<PushState> {
    if (!('serviceWorker' in navigator) || !('Notification' in window)) {
        return isIos() && !isStandalone() ? 'needs-install' : 'unsupported';
    }

    if (!('PushManager' in window)) {
        return isIos() && !isStandalone() ? 'needs-install' : 'unsupported';
    }

    if (Notification.permission === 'denied') {
        return 'blocked';
    }

    const registration = await navigator.serviceWorker.getRegistration();
    const subscription = await registration?.pushManager.getSubscription();

    return subscription && Notification.permission === 'granted' ? 'on' : 'off';
}

export async function enablePush(vapidKey: string): Promise<PushState> {
    const permission = await Notification.requestPermission();

    if (permission !== 'granted') {
        return permission === 'denied' ? 'blocked' : 'off';
    }

    const registration =
        (await navigator.serviceWorker.getRegistration()) ??
        (await navigator.serviceWorker.register('/sw.js'));
    await navigator.serviceWorker.ready;

    const subscription =
        (await registration.pushManager.getSubscription()) ??
        (await registration.pushManager.subscribe({
            userVisibleOnly: true,
            applicationServerKey: keyBytes(vapidKey),
        }));

    const json = subscription.toJSON();
    const saved = await post('/push/subscribe', {
        endpoint: json.endpoint,
        keys: json.keys,
        contentEncoding:
            (PushManager as unknown as { supportedContentEncodings?: string[] })
                .supportedContentEncodings?.[0] ?? 'aes128gcm',
    });

    return saved ? 'on' : 'off';
}

export async function disablePush(): Promise<PushState> {
    const registration = await navigator.serviceWorker.getRegistration();
    const subscription = await registration?.pushManager.getSubscription();

    if (subscription) {
        await post('/push/unsubscribe', { endpoint: subscription.endpoint });
        await subscription.unsubscribe();
    }

    return 'off';
}
