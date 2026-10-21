<script setup lang="ts">
import { Link, router, usePage } from '@inertiajs/vue3';
import { ChevronRight, CloudOff, Plus, RefreshCw } from '@lucide/vue';
import { computed, onBeforeUnmount, onMounted, ref } from 'vue';
import { useSidebar } from '@/components/ui/sidebar';
import { currentLocale, t } from '@/lib/i18n';
import { useCan } from '@/lib/khamar';
import { pendingCount } from '@/lib/offlineQueue';
import type { BreadcrumbItem } from '@/types';

/**
 * The farm's own top bar: a "ঘর" (stall) button to open the menu, today's date the way the
 * farm says it, the page name in the display face, and on the right the things a worker
 * checks at a glance — is the phone online, is anything waiting to sync, which language.
 */
const props = withDefaults(
    defineProps<{
        breadcrumbs?: BreadcrumbItem[];
    }>(),
    {
        breadcrumbs: () => [],
    },
);

const { toggleSidebar } = useSidebar();
const can = useCan();
const page = usePage();

const title = computed(
    () => props.breadcrumbs[props.breadcrumbs.length - 1]?.title ?? '',
);
const trail = computed(() => props.breadcrumbs.slice(0, -1));

const WEEKDAYS = [
    'রবিবার',
    'সোমবার',
    'মঙ্গলবার',
    'বুধবার',
    'বৃহস্পতিবার',
    'শুক্রবার',
    'শনিবার',
];
const MONTHS = [
    'জানুয়ারি',
    'ফেব্রুয়ারি',
    'মার্চ',
    'এপ্রিল',
    'মে',
    'জুন',
    'জুলাই',
    'আগস্ট',
    'সেপ্টেম্বর',
    'অক্টোবর',
    'নভেম্বর',
    'ডিসেম্বর',
];
const BN_DIGITS = '০১২৩৪৫৬৭৮৯';

const todayLabel = computed(() => {
    const now = new Date();

    if (currentLocale() === 'en') {
        return now.toLocaleDateString('en-GB', {
            weekday: 'long',
            day: 'numeric',
            month: 'long',
        });
    }

    const dayNumber = String(now.getDate()).replace(
        /\d/g,
        (d) => BN_DIGITS[Number(d)],
    );

    return `${WEEKDAYS[now.getDay()]}, ${dayNumber} ${MONTHS[now.getMonth()]}`;
});

const online = ref(typeof navigator === 'undefined' ? true : navigator.onLine);
const setOnline = () => (online.value = navigator.onLine);

onMounted(() => {
    window.addEventListener('online', setOnline);
    window.addEventListener('offline', setOnline);
});
onBeforeUnmount(() => {
    window.removeEventListener('online', setOnline);
    window.removeEventListener('offline', setOnline);
});

const locale = computed(() => (page.props.locale as string) ?? 'bn');
const switchTo = (next: 'bn' | 'en') => {
    if (next !== locale.value) {
        router.post('/locale', { locale: next }, { preserveScroll: true });
    }
};
</script>

<template>
    <header
        class="farm-bar sticky top-0 z-20 flex h-16 shrink-0 items-center gap-3 bg-background/85 px-3 backdrop-blur-md md:px-5"
    >
        <button
            type="button"
            class="stall-btn group/stall"
            :aria-label="t('মেনু')"
            @click="toggleSidebar"
        >
            <svg viewBox="0 0 24 24" class="size-5" aria-hidden="true">
                <path
                    d="M3 11 12 4l9 7"
                    fill="none"
                    stroke="currentColor"
                    stroke-width="1.8"
                    stroke-linecap="round"
                    stroke-linejoin="round"
                />
                <path
                    d="M6 10v9h12v-9M10 19v-5h4v5"
                    fill="none"
                    stroke="currentColor"
                    stroke-width="1.8"
                    stroke-linecap="round"
                    stroke-linejoin="round"
                />
            </svg>
        </button>

        <div class="min-w-0 flex-1 leading-tight">
            <p
                class="flex min-w-0 items-center gap-1 text-[11px] font-medium tracking-wide text-paddy"
            >
                <span class="shrink-0">{{ todayLabel }}</span>
                <template v-for="crumb in trail" :key="crumb.title">
                    <ChevronRight
                        class="size-3 shrink-0 text-muted-foreground/60"
                    />
                    <Link
                        :href="crumb.href"
                        class="truncate text-muted-foreground hover:text-foreground"
                        >{{ t(crumb.title) }}</Link
                    >
                </template>
            </p>
            <p class="font-display truncate text-lg md:text-xl">
                {{ t(title) }}
            </p>
        </div>

        <div class="flex shrink-0 items-center gap-1.5">
            <Link
                v-if="!online || pendingCount > 0"
                href="/offline"
                :class="[
                    'pill',
                    online
                        ? 'border-turmeric/50 bg-turmeric/12 text-foreground'
                        : 'border-clay/50 bg-clay/12 text-foreground',
                ]"
                :title="
                    online
                        ? t('ফোনে এন্ট্রি জমে আছে')
                        : t('ইন্টারনেট নেই — এন্ট্রি ফোনে জমা হবে')
                "
            >
                <CloudOff v-if="!online" class="size-3.5 text-clay" />
                <RefreshCw v-else class="size-3.5 text-turmeric" />
                <span class="hidden sm:inline">{{
                    online ? t('বাকি') : t('অফলাইন')
                }}</span>
                <b v-if="pendingCount > 0" class="num">{{ pendingCount }}</b>
            </Link>

            <div
                class="lang flex items-center rounded-full border border-border bg-card p-0.5 text-xs font-semibold"
                role="group"
                :aria-label="t('ভাষা')"
            >
                <button
                    type="button"
                    :class="['lang-opt', locale === 'bn' && 'is-on']"
                    @click="switchTo('bn')"
                >
                    বাং
                </button>
                <button
                    type="button"
                    :class="['lang-opt', locale === 'en' && 'is-on']"
                    @click="switchTo('en')"
                >
                    EN
                </button>
            </div>

            <Link
                v-if="can['post-entries']"
                href="/entries/create"
                class="add-btn"
                :aria-label="t('নতুন এন্ট্রি')"
            >
                <Plus class="size-4" />
                <span class="hidden lg:inline">{{ t('এন্ট্রি') }}</span>
            </Link>
        </div>
    </header>
</template>

<style scoped>
@reference '../../css/app.css';

/* A thin field-row line under the bar: paddy, turmeric and clay, like crops in a row. */
.farm-bar::after {
    content: '';
    position: absolute;
    inset: auto 0 0 0;
    height: 3px;
    background: repeating-linear-gradient(
        90deg,
        var(--paddy) 0 28px,
        transparent 28px 34px,
        var(--turmeric) 34px 46px,
        transparent 46px 52px,
        var(--clay) 52px 60px,
        transparent 60px 66px
    );
    opacity: 0.55;
}

.stall-btn {
    @apply flex size-10 shrink-0 items-center justify-center rounded-xl border border-border bg-card text-paddy shadow-xs transition-colors hover:bg-paddy hover:text-primary-foreground;
}

.pill {
    @apply flex h-8 items-center gap-1.5 rounded-full border px-2.5 text-xs;
}

.lang-opt {
    @apply rounded-full px-2.5 py-1 text-muted-foreground transition-colors;
}

.lang-opt.is-on {
    @apply bg-paddy text-primary-foreground;
}

.add-btn {
    @apply flex h-9 items-center gap-1.5 rounded-full bg-primary px-3 text-sm font-medium text-primary-foreground shadow-xs transition-opacity hover:opacity-90;
}
</style>
