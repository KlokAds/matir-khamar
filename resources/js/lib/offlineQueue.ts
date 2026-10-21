import { ref } from 'vue';

/**
 * Entries made on the phone wait here (IndexedDB) until the server has
 * answered for them. Each carries an id made on the phone, so sending it
 * again after a dropped connection can never book it twice.
 */

export type QueueKind = 'milk' | 'feed' | 'expense';
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

function put(item: QueueItem) {
    return tx('readwrite', (store) =>
        store.put(JSON.parse(JSON.stringify(item))),
    );
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
        const pending = (await listFor(userId))
            .filter((item) => item.status === 'pending')
            .reverse();

        if (!pending.length) {
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
