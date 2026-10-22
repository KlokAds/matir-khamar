<script setup lang="ts">
import { Link, router, usePage } from '@inertiajs/vue3';
import { AlarmClock, Bell, BellOff, Trash2, X } from '@lucide/vue';
import {
    PopoverContent,
    PopoverPortal,
    PopoverRoot,
    PopoverTrigger,
} from 'reka-ui';
import { computed, ref } from 'vue';
import { t } from '@/lib/i18n';

/**
 * The bell, like a chat app's: every notification sent to this person's phones, newest first,
 * the unseen ones marked; opening it marks them seen. Today's reminders are a tap away below.
 */
type Item = {
    id: number;
    title: string;
    body: string | null;
    url: string | null;
    read: boolean;
    at: string | null;
};

const page = usePage();
const unread = computed(() => Number(page.props.notificationsUnread ?? 0));
const remindersDue = computed(() => Number(page.props.remindersDue ?? 0));
const badge = computed(() => unread.value + remindersDue.value);

const open = ref(false);
const items = ref<Item[]>([]);
const loading = ref(false);

function xsrf(): string {
    const match = document.cookie.match(/(?:^|;\s*)XSRF-TOKEN=([^;]+)/);

    return match ? decodeURIComponent(match[1]) : '';
}

async function load(value: boolean) {
    open.value = value;

    if (!value) {
        return;
    }

    loading.value = true;

    try {
        const response = await fetch('/notifications', {
            credentials: 'same-origin',
            headers: { Accept: 'application/json' },
        });
        items.value = response.ok ? (await response.json()).items : [];

        if (unread.value > 0) {
            await fetch('/notifications/read', {
                method: 'POST',
                credentials: 'same-origin',
                headers: {
                    Accept: 'application/json',
                    'X-XSRF-TOKEN': xsrf(),
                    'X-Requested-With': 'XMLHttpRequest',
                },
            });
            router.reload({ only: ['notificationsUnread'] });
        }
    } finally {
        loading.value = false;
    }
}

/** "5 মিনিট আগে", "2 ঘণ্টা আগে", or the date. */
function ago(iso: string | null): string {
    if (!iso) {
        return '';
    }

    const minutes = Math.round((Date.now() - new Date(iso).getTime()) / 60000);

    if (minutes < 1) {
        return t('এইমাত্র');
    }

    if (minutes < 60) {
        return t(':n মিনিট আগে', { n: minutes });
    }

    if (minutes < 60 * 24) {
        return t(':n ঘণ্টা আগে', { n: Math.round(minutes / 60) });
    }

    return new Date(iso).toLocaleDateString();
}

async function send(method: string, url: string) {
    await fetch(url, {
        method,
        credentials: 'same-origin',
        headers: {
            Accept: 'application/json',
            'X-XSRF-TOKEN': xsrf(),
            'X-Requested-With': 'XMLHttpRequest',
        },
    });
}

async function removeOne(item: Item) {
    items.value = items.value.filter((i) => i.id !== item.id);
    await send('DELETE', `/notifications/${item.id}`);
    router.reload({ only: ['notificationsUnread'] });
}

async function clearAll() {
    items.value = [];
    await send('DELETE', '/notifications');
    router.reload({ only: ['notificationsUnread'] });
}

function go(item: Item) {
    open.value = false;

    if (item.url) {
        router.visit(item.url);
    }
}
</script>

<template>
    <PopoverRoot :open="open" @update:open="load">
        <PopoverTrigger as-child>
            <button
                type="button"
                class="bell relative"
                :aria-label="t('নোটিফিকেশন')"
            >
                <Bell class="size-4" />
                <span
                    v-if="badge > 0"
                    class="num absolute -top-1 -right-1 flex h-4 min-w-4 items-center justify-center rounded-full bg-destructive px-1 text-[10px] font-bold text-white"
                    >{{ badge }}</span
                >
            </button>
        </PopoverTrigger>
        <PopoverPortal>
            <PopoverContent
                align="end"
                :side-offset="8"
                :collision-padding="12"
                class="z-50 w-[min(22rem,calc(100vw-1.5rem))] overflow-hidden rounded-xl border border-border bg-popover text-popover-foreground shadow-lg"
            >
                <div
                    class="flex items-center justify-between border-b border-border px-4 py-2.5"
                >
                    <p class="font-display text-base leading-normal">
                        {{ t('নোটিফিকেশন') }}
                    </p>
                    <div class="flex items-center gap-2">
                        <span
                            v-if="unread"
                            class="num rounded-full bg-destructive/10 px-2 py-0.5 text-xs text-destructive"
                            >{{ t(':nটি নতুন', { n: unread }) }}</span
                        >
                        <button
                            v-if="items.length"
                            type="button"
                            class="btn-chip h-7 text-xs"
                            @click="clearAll"
                        >
                            <Trash2 /> {{ t('সব মুছুন') }}
                        </button>
                    </div>
                </div>
                <ul class="max-h-[60vh] overflow-y-auto">
                    <li
                        v-for="item in items"
                        :key="item.id"
                        :class="[
                            'border-b border-border/60 last:border-0',
                            !item.read && 'bg-primary/5',
                        ]"
                    >
                        <div class="flex items-start">
                            <button
                                type="button"
                                class="flex min-w-0 flex-1 gap-3 py-3 pl-4 text-left hover:bg-accent/50"
                                @click="go(item)"
                            >
                                <span
                                    :class="[
                                        'mt-1.5 size-2 shrink-0 rounded-full',
                                        item.read
                                            ? 'bg-transparent'
                                            : 'bg-primary',
                                    ]"
                                />
                                <span class="min-w-0 flex-1">
                                    <span
                                        class="block text-sm leading-normal font-medium"
                                        >{{ item.title }}</span
                                    >
                                    <span
                                        v-if="item.body"
                                        class="block text-xs leading-normal text-muted-foreground"
                                        >{{ item.body }}</span
                                    >
                                    <span
                                        class="mt-0.5 block text-[11px] text-muted-foreground"
                                        >{{ ago(item.at) }}</span
                                    >
                                </span>
                            </button>
                            <button
                                type="button"
                                class="m-2 flex size-8 shrink-0 items-center justify-center rounded-md text-muted-foreground hover:bg-accent hover:text-destructive"
                                :aria-label="t('মুছুন')"
                                :title="t('মুছুন')"
                                @click="removeOne(item)"
                            >
                                <X class="size-4" />
                            </button>
                        </div>
                    </li>
                    <li
                        v-if="!loading && !items.length"
                        class="flex flex-col items-center gap-2 px-4 py-8 text-center text-sm text-muted-foreground"
                    >
                        <BellOff class="size-5" />
                        {{ t('এখনো কোনো নোটিফিকেশন নেই।') }}
                    </li>
                    <li
                        v-if="loading && !items.length"
                        class="px-4 py-8 text-center text-sm text-muted-foreground"
                    >
                        {{ t('লোড হচ্ছে…') }}
                    </li>
                </ul>
                <Link
                    href="/reminders"
                    class="flex items-center justify-between gap-2 border-t border-border px-4 py-2.5 text-sm font-medium text-primary hover:bg-primary/5"
                    @click="open = false"
                >
                    <span class="flex items-center gap-2"
                        ><AlarmClock class="size-4" />
                        {{ t('মনে করিয়ে দিন') }}</span
                    >
                    <span
                        v-if="remindersDue"
                        class="num rounded-full bg-destructive px-1.5 text-[11px] text-white"
                        >{{ t('আজ :n', { n: remindersDue }) }}</span
                    >
                </Link>
            </PopoverContent>
        </PopoverPortal>
    </PopoverRoot>
</template>

<style scoped>
@reference '../../css/app.css';

.bell {
    @apply flex size-8 shrink-0 items-center justify-center rounded-full border border-border bg-card text-muted-foreground transition-colors hover:text-foreground sm:size-9;
}
</style>
