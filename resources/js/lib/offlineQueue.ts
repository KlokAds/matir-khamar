import { objectToFormData } from '@inertiajs/core';
import { ref } from 'vue';

/**
 * Entries made on the phone wait here (IndexedDB) until the server has
 * answered for them. Each carries an id made on the phone, so sending it
 * again after a dropped connection can never book it twice.
 */

/** milk/feed/expense: the offline page's own entries. form: any form sent with no internet. */
export type QueueKind = 'milk' | 'feed' | 'expense' | 'form';
export type QueueStatus = 'pending' | 'done' | 'failed';

export type QueueItem = {
    uuid: string;
    userId: number;
    kind: QueueKind;
    label: string;
    payload: Record<string, unknown>;
    recordedAt: string;
    status: QueueStatus;
    message: string | null;
    number: string | null;
    syncedAt: string | null;
};

const DB_NAME = 'matir-khamar';
const STORE = 'queue';
const KEEP_DONE_DAYS = 7;

function open(): Promise<IDBDatabase> {
    return new Promise((resolve, reject) => {
        const request = indexedDB.open(DB_NAME, 1);
        request.onupgradeneeded = () => {
            const store = request.result.createObjectStore(STORE, {
                keyPath: 'uuid',
            });
            store.createIndex('userId', 'userId');
        };
        request.onsuccess = () => resolve(request.result);
        request.onerror = () => reject(request.error);
    });
}

async function tx<T>(
    mode: IDBTransactionMode,
    run: (store: IDBObjectStore) => IDBRequest<T> | void,
): Promise<T | undefined> {
    const db = await open();

    return new Promise((resolve, reject) => {
        const transaction = db.transaction(STORE, mode);
        const request = run(transaction.objectStore(STORE));
        transaction.oncomplete = () =>
            resolve(request ? request.result : undefined);
        transaction.onerror = () => reject(transaction.error);
    });
}

export async function listFor(userId: number): Promise<QueueItem[]> {
    const items = (
        (await tx<QueueItem[]>('readonly', (store) =>
            store.index('userId').getAll(userId),
        )) ?? []
    ).sort((a, b) => b.recordedAt.localeCompare(a.recordedAt));
    const cutoff = Date.now() - KEEP_DONE_DAYS * 86400000;
    const stale = items.filter(
        (item) =>
            item.status === 'done' &&
            item.syncedAt &&
            Date.parse(item.syncedAt) < cutoff,
    );

    for (const item of stale) {
        await tx('readwrite', (store) => store.delete(item.uuid));
    }

    return items.filter((item) => !stale.includes(item));
}

/** A plain copy (no Vue proxies) that keeps photos and files as they are. */
function plain(value: unknown): unknown {
    if (value instanceof Blob || value instanceof Date) {
        return value;
    }

    if (Array.isArray(value)) {
        return value.map(plain);
    }

    if (value && typeof value === 'object') {
        return Object.fromEntries(
            Object.entries(value).map(([key, inner]) => [key, plain(inner)]),
        );
    }

    return value;
}

function put(item: QueueItem) {
    return tx('readwrite', (store) => store.put(plain(item)));
}

export function remove(uuid: string) {
    return tx('readwrite', (store) => store.delete(uuid));
}

function newId(): string {
    if (crypto.randomUUID) {
        return crypto.randomUUID();
    }

    // Older Android browsers: RFC 4122 v4 from getRandomValues.
    const bytes = crypto.getRandomValues(new Uint8Array(16));
    bytes[6] = (bytes[6] & 0x0f) | 0x40;
    bytes[8] = (bytes[8] & 0x3f) | 0x80;
    const hex = [...bytes].map((b) => b.toString(16).padStart(2, '0')).join('');

    return `${hex.slice(0, 8)}-${hex.slice(8, 12)}-${hex.slice(12, 16)}-${hex.slice(16, 20)}-${hex.slice(20)}`;
}

/** A form sent with no internet: kept to send as it was, in order, once the net is back. */
export function enqueueForm(
    userId: number,
    label: string,
    form: { method: string; url: string; data: unknown },
): Promise<QueueItem> {
    return enqueue(userId, 'form', label, form as Record<string, unknown>);
}

function hasFile(value: unknown): boolean {
    if (value instanceof Blob) {
        return true;
    }

    return !!value && typeof value === 'object'
        ? Object.values(value).some(hasFile)
        : false;
}

function inertiaVersion(): string {
    try {
        const raw = document.querySelector('script[data-page]')?.textContent;

        return raw ? String(JSON.parse(raw).version ?? '') : '';
    } catch {
        return '';
    }
}

/**
 * Sends one waiting form the way the page would have: as an Inertia visit, so the server
 * checks it exactly like a normal entry. Its phone-made id lets the server book it once only.
 */
async function sendForm(
    item: QueueItem,
): Promise<'done' | 'failed' | 'offline' | 'signed-out' | 'error'> {
    const form = item.payload as { method: string; url: string; data: unknown };
    const method = form.method.toUpperCase();
    const files = hasFile(form.data);
    const body = files
        ? objectToFormData(
              (method === 'POST'
                  ? form.data
                  : { ...(form.data as object), _method: method }) as never,
          )
        : JSON.stringify(form.data ?? {});
    let response: Response;

    try {
        response = await fetch(form.url, {
            method: files ? 'POST' : method,
            credentials: 'same-origin',
            headers: {
                ...(files ? {} : { 'Content-Type': 'application/json' }),
                Accept: 'text/html, application/xhtml+xml',
                'X-Requested-With': 'XMLHttpRequest',
                'X-Inertia': 'true',
                'X-Inertia-Version': inertiaVersion(),
                'X-XSRF-TOKEN': xsrfToken(),
                'X-Offline-Key': item.uuid,
                'X-Offline-At': item.recordedAt,
            },
            body,
        });
    } catch {
        return 'offline';
    }

    if (
        response.status === 401 ||
        response.status === 419 ||
        new URL(response.url).pathname.startsWith('/login')
    ) {
        return 'signed-out';
    }

    if (response.status === 403 || response.status === 404) {
        await put({
            ...item,
            status: 'failed',
            message: response.status === 403 ? 'অনুমতি নেই' : 'পাতাটি আর নেই',
            syncedAt: new Date().toISOString(),
        });

        return 'failed';
    }

    if (!response.ok) {
        return 'error';
    }

    // The page the server answered with: its errors say why the entry was refused.
    let errors: Record<string, string> = {};

    try {
        const page = (await response.json()) as {
            props?: { errors?: Record<string, string> };
        };
        errors = page.props?.errors ?? {};
    } catch {
        // Not a page (a file or a plain answer): it went through.
    }

    const failed = Object.keys(errors).length > 0;
    await put({
        ...item,
        status: failed ? 'failed' : 'done',
        message: failed ? Object.values(errors).join(' · ') : null,
        syncedAt: new Date().toISOString(),
    });

    return failed ? 'failed' : 'done';
}

export async function enqueue(
    userId: number,
    kind: QueueKind,
    label: string,
    payload: Record<string, unknown>,
): Promise<QueueItem> {
    const item: QueueItem = {
        uuid: newId(),
        userId,
        kind,
        label,
        payload,
        recordedAt: new Date().toISOString(),
        status: 'pending',
        message: null,
        number: null,
        syncedAt: null,
    };
    await put(item);
    notify();

    return item;
}

function xsrfToken(): string {
    const match = document.cookie.match(/(?:^|;\s*)XSRF-TOKEN=([^;]+)/);

    return match ? decodeURIComponent(match[1]) : '';
}

export type SyncOutcome = 'ok' | 'offline' | 'signed-out' | 'error' | 'nothing';

let running: Promise<SyncOutcome> | null = null;

/** Sends every waiting entry of this user. Safe to call often: one run at a time. */
export function syncNow(userId: number): Promise<SyncOutcome> {
    running ??= (async (): Promise<SyncOutcome> => {
        const waiting = (await listFor(userId))
            .filter((item) => item.status === 'pending')
            .reverse();
        const pending = waiting.filter((item) => item.kind !== 'form');
        const forms = waiting.filter((item) => item.kind === 'form');

        if (!waiting.length) {
            return 'nothing';
        }

        if (!navigator.onLine) {
            return 'offline';
        }

        for (let i = 0; i < pending.length; i += 25) {
            const batch = pending.slice(i, i + 25);
            let response: Response;

            try {
                response = await fetch('/offline/sync', {
                    method: 'POST',
                    credentials: 'same-origin',
                    headers: {
                        'Content-Type': 'application/json',
                        Accept: 'application/json',
                        'X-Requested-With': 'XMLHttpRequest',
                        'X-XSRF-TOKEN': xsrfToken(),
                    },
                    body: JSON.stringify({
                        items: batch.map((item) => ({
                            uuid: item.uuid,
                            kind: item.kind,
                            recorded_at: item.recordedAt,
                            payload: item.payload,
                        })),
                    }),
                });
            } catch {
                return 'offline';
            }

            if (response.status === 401 || response.status === 419) {
                return 'signed-out';
            }

            if (!response.ok) {
                return 'error';
            }

            const { results } = (await response.json()) as {
                results: {
                    uuid: string;
                    status: 'done' | 'failed' | 'retry';
                    message: string | null;
                    number: string | null;
                }[];
            };

            for (const result of results) {
                const item = batch.find(
                    (candidate) => candidate.uuid === result.uuid,
                );

                if (!item || result.status === 'retry') {
                    continue;
                }

                await put({
                    ...item,
                    status: result.status,
                    message: result.message,
                    number: result.number,
                    syncedAt: new Date().toISOString(),
                });
            }
        }

        // Forms one by one, oldest first: a later one may need an earlier one booked.
        for (const item of forms) {
            const outcome = await sendForm(item);

            if (outcome === 'offline' || outcome === 'signed-out') {
                notify();

                return outcome;
            }

            if (outcome === 'error') {
                break;
            }
        }

        notify();

        return 'ok';
    })().finally(() => {
        running = null;
    });

    return running;
}

/** Live count of waiting entries for the badge, shared across tabs. */
export const pendingCount = ref(0);
const channel =
    typeof BroadcastChannel !== 'undefined'
        ? new BroadcastChannel('matir-khamar-queue')
        : null;
let watchedUser: number | null = null;

async function refreshCount() {
    if (watchedUser !== null) {
        pendingCount.value = (await listFor(watchedUser)).filter(
            (item) => item.status === 'pending',
        ).length;
    }
}

function notify() {
    void refreshCount();
    channel?.postMessage('changed');
}

channel?.addEventListener('message', () => void refreshCount());

/**
 * Starts background sending for a signed-in user: on load, when the net
 * returns, when the app is brought back to the screen, and every minute.
 */
export function watchQueue(userId: number) {
    if (watchedUser === userId) {
        return;
    }

    watchedUser = userId;
    void refreshCount();
    const attempt = () => void syncNow(userId).then(refreshCount);
    attempt();
    window.addEventListener('online', attempt);
    window.addEventListener('focus', attempt);
    document.addEventListener(
        'visibilitychange',
        () => document.visibilityState === 'visible' && attempt(),
    );
    window.setInterval(attempt, 60 * 1000);
}
