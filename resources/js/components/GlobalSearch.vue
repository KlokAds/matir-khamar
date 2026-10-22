<script setup lang="ts">
import { router } from '@inertiajs/vue3';
import {
    AlarmClock,
    Beef,
    CornerDownLeft,
    Folder,
    NotebookPen,
    Package,
    Receipt,
    Search,
    User,
    X,
} from '@lucide/vue';
import type { LucideIcon } from '@lucide/vue';
import {
    DialogContent,
    DialogOverlay,
    DialogPortal,
    DialogRoot,
    DialogTitle,
} from 'reka-ui';
import {
    computed,
    nextTick,
    onBeforeUnmount,
    onMounted,
    ref,
    watch,
} from 'vue';
import { t } from '@/lib/i18n';
import { useCan, useModules } from '@/lib/khamar';
import { moduleAllows, pageLinks } from '@/lib/quick';

/**
 * One box to find anything: a page or feature by name, an animal by its number, a person,
 * a stock item, an entry number, a diary note or a reminder. Opens with the 🔍 button or
 * Ctrl/⌘ + K; arrows and Enter work as expected.
 */
const open = defineModel<boolean>('open', { default: false });
const can = useCan();
const modules = useModules();
const query = ref('');
const active = ref(0);
const loading = ref(false);
const remote = ref<
    { group: string; title: string; hint: string; href: string; icon: string }[]
>([]);
const input = ref<HTMLInputElement | null>(null);
const list = ref<HTMLElement | null>(null);

const icons: Record<string, LucideIcon> = {
    cow: Beef,
    folder: Folder,
    user: User,
    package: Package,
    receipt: Receipt,
    note: NotebookPen,
    alarm: AlarmClock,
};

type Hit = {
    group: string;
    title: string;
    hint: string;
    href: string;
    icon: LucideIcon;
};

const pages = computed<Hit[]>(() => {
    const needle = query.value.trim().toLowerCase();
    const allowed = pageLinks.filter(
        (p) =>
            (!p.ability || can.value[p.ability]) &&
            moduleAllows(p.href, modules.value),
    );
    const matched = needle
        ? allowed.filter((p) =>
              `${p.title} ${t(p.title)} ${p.words}`
                  .toLowerCase()
                  .includes(needle),
          )
        : allowed.slice(0, 8);

    return matched.slice(0, 8).map((p) => ({
        group: t('পাতা ও কাজ'),
        title: t(p.title),
        hint: p.href,
        href: p.href,
        icon: p.icon,
    }));
});

const hits = computed<Hit[]>(() => [
    ...pages.value,
    ...remote.value.map((r) => ({
        ...r,
        group: t(r.group),
        hint: t(r.hint),
        icon: icons[r.icon] ?? Search,
    })),
]);

const grouped = computed(() => {
    const groups: { name: string; items: (Hit & { index: number })[] }[] = [];

    hits.value.forEach((hit, index) => {
        let group = groups.find((g) => g.name === hit.group);

        if (!group) {
            group = { name: hit.group, items: [] };
            groups.push(group);
        }

        group.items.push({ ...hit, index });
    });

    return groups;
});

let timer: ReturnType<typeof setTimeout> | undefined;
let controller: AbortController | undefined;

watch(query, (value) => {
    active.value = 0;
    clearTimeout(timer);
    controller?.abort();

    if (!value.trim()) {
        remote.value = [];
        loading.value = false;

        return;
    }

    loading.value = true;
    timer = setTimeout(async () => {
        controller = new AbortController();

        try {
            const response = await fetch(
                `/search?q=${encodeURIComponent(value.trim())}`,
                {
                    headers: {
                        Accept: 'application/json',
                        'X-Requested-With': 'XMLHttpRequest',
                    },
                    credentials: 'same-origin',
                    signal: controller.signal,
                },
            );
            remote.value = response.ok ? (await response.json()).results : [];
        } catch {
            // A newer search replaced this one, or the phone is offline: keep the page matches.
        } finally {
            loading.value = false;
        }
    }, 220);
});

watch(open, async (value) => {
    if (value) {
        query.value = '';
        remote.value = [];
        await nextTick();
        input.value?.focus();
    }
});

function go(hit: Hit | undefined) {
    if (!hit) {
        return;
    }

    open.value = false;
    router.visit(hit.href);
}

function onKey(event: KeyboardEvent) {
    const count = hits.value.length;

    if (event.key === 'ArrowDown' && count) {
        event.preventDefault();
        active.value = (active.value + 1) % count;
    } else if (event.key === 'ArrowUp' && count) {
        event.preventDefault();
        active.value = (active.value - 1 + count) % count;
    } else if (event.key === 'Enter') {
        event.preventDefault();
        go(hits.value[active.value]);
    } else {
        return;
    }

    nextTick(() =>
        list.value
            ?.querySelector(`[data-index="${active.value}"]`)
            ?.scrollIntoView({ block: 'nearest' }),
    );
}

function shortcut(event: KeyboardEvent) {
    if ((event.ctrlKey || event.metaKey) && event.key.toLowerCase() === 'k') {
        event.preventDefault();
        open.value = !open.value;
    }
}

onMounted(() => window.addEventListener('keydown', shortcut));
onBeforeUnmount(() => window.removeEventListener('keydown', shortcut));
</script>

<template>
    <DialogRoot v-model:open="open">
        <DialogPortal>
            <DialogOverlay
                class="fixed inset-0 z-50 bg-black/50 backdrop-blur-[2px] data-[state=open]:animate-in data-[state=open]:fade-in-0"
            />
            <DialogContent
                class="fixed inset-x-0 top-0 z-50 mx-auto flex max-h-[100dvh] w-full flex-col overflow-hidden border-border bg-popover text-popover-foreground shadow-2xl sm:top-[12vh] sm:max-h-[70vh] sm:max-w-xl sm:rounded-2xl sm:border"
                @keydown="onKey"
            >
                <DialogTitle class="sr-only">{{ t('খুঁজুন') }}</DialogTitle>
                <div
                    class="flex items-center gap-2 border-b border-border px-3"
                >
                    <Search class="size-5 shrink-0 text-paddy" />
                    <input
                        ref="input"
                        v-model="query"
                        type="search"
                        autocomplete="off"
                        :placeholder="
                            t('গরুর নম্বর, মানুষ, মাল, এন্ট্রি নম্বর, পাতা…')
                        "
                        class="h-14 w-full bg-transparent text-base outline-none placeholder:text-muted-foreground"
                    />
                    <button
                        type="button"
                        class="rounded-md p-1.5 text-muted-foreground hover:bg-accent"
                        :aria-label="t('বন্ধ')"
                        @click="open = false"
                    >
                        <X class="size-5" />
                    </button>
                </div>

                <div ref="list" class="min-h-0 flex-1 overflow-y-auto p-2">
                    <section
                        v-for="group in grouped"
                        :key="group.name"
                        class="mb-2"
                    >
                        <p
                            class="px-2 py-1 text-[11px] font-semibold tracking-wide text-muted-foreground"
                        >
                            {{ group.name }}
                        </p>
                        <button
                            v-for="hit in group.items"
                            :key="hit.group + hit.href + hit.title"
                            type="button"
                            :data-index="hit.index"
                            :class="[
                                'flex w-full items-center gap-3 rounded-lg px-2.5 py-2 text-left',
                                hit.index === active
                                    ? 'bg-accent'
                                    : 'hover:bg-accent/60',
                            ]"
                            @mousemove="active = hit.index"
                            @click="go(hit)"
                        >
                            <component
                                :is="hit.icon"
                                class="size-4 shrink-0 text-paddy"
                            />
                            <span class="min-w-0 flex-1">
                                <span class="block truncate text-[15px]">{{
                                    hit.title
                                }}</span>
                                <span
                                    v-if="hit.hint"
                                    class="block truncate text-xs text-muted-foreground"
                                    >{{ hit.hint }}</span
                                >
                            </span>
                            <CornerDownLeft
                                v-if="hit.index === active"
                                class="size-4 shrink-0 text-muted-foreground"
                            />
                        </button>
                    </section>

                    <p
                        v-if="query && !loading && hits.length === 0"
                        class="px-3 py-8 text-center text-sm text-muted-foreground"
                    >
                        {{ t('কিছু মেলেনি') }}
                    </p>
                    <p
                        v-if="loading"
                        class="px-3 py-2 text-xs text-muted-foreground"
                    >
                        {{ t('খুঁজছি…') }}
                    </p>
                </div>

                <p
                    class="hidden border-t border-border px-3 py-2 text-[11px] text-muted-foreground sm:block"
                >
                    ↑ ↓ {{ t('বাছুন') }} · Enter {{ t('খুলুন') }} · Ctrl + K
                    {{ t('যেকোনো পাতা থেকে') }}
                </p>
            </DialogContent>
        </DialogPortal>
    </DialogRoot>
</template>
