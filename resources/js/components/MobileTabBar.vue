<script setup lang="ts">
import { Link, usePage } from '@inertiajs/vue3';
import {
    AlarmClock,
    BookOpen,
    LayoutGrid,
    Menu,
    Milk,
    Plus,
    ReceiptText,
    Wallet,
    Wheat,
} from '@lucide/vue';
import { computed } from 'vue';
import QuickAddMenu from '@/components/QuickAddMenu.vue';
import { t } from '@/lib/i18n';
import { useCan, useModules } from '@/lib/khamar';
import { mobileMenuOpen } from '@/lib/nav';

/**
 * The phone's bottom bar, like an app: today, two daily jobs, the big ＋ and the full menu, all
 * within reach of a thumb. The two daily jobs are the first ones that apply: milk and feed while
 * those projects run, otherwise writing an expense, the money, the book or the reminders — so a
 * project turned off never leaves a hole in the bar.
 */
const can = useCan();
const modules = useModules();
const page = usePage();
const path = computed(() => page.url.split('?')[0]);

const tabs = computed(() => {
    const c = can.value;
    const m = modules.value;
    const daily = [
        {
            href: '/milk',
            label: 'দুধ',
            icon: Milk,
            show: !!c['post-daily'] && m.milk,
        },
        {
            href: '/stock/move?mode=issue',
            label: 'খাবার',
            icon: Wheat,
            show: !!c['post-daily'] && (m.cattle || m.goat),
        },
        {
            href: '/entries/create',
            label: 'খরচ',
            icon: ReceiptText,
            show: !!c['post-entries'],
        },
        {
            href: '/money',
            label: 'টাকা',
            icon: Wallet,
            show: !!c['view-reports'],
        },
        {
            href: '/entries',
            label: 'খাতা',
            icon: BookOpen,
            show: !!c['view-reports'],
        },
        { href: '/reminders', label: 'মনে রাখা', icon: AlarmClock, show: true },
    ].filter((tab) => tab.show);

    return [
        { href: '/dashboard', label: 'আজ', icon: LayoutGrid },
        ...daily.slice(0, 2),
    ];
});

const isOn = (href: string) => path.value === href.split('?')[0];
</script>

<template>
    <nav
        class="tabbar fixed inset-x-0 bottom-0 z-30 border-t border-border bg-background/95 backdrop-blur-md md:hidden"
        :aria-label="t('দ্রুত মেনু')"
    >
        <div class="mx-auto grid max-w-md grid-cols-5 items-end px-2 pt-1.5">
            <Link
                v-for="tab in tabs.slice(0, 2)"
                :key="tab.href"
                :href="tab.href"
                :class="['tab', isOn(tab.href) && 'is-on']"
            >
                <component :is="tab.icon" class="size-5" />
                <span>{{ t(tab.label) }}</span>
            </Link>

            <div class="flex justify-center">
                <QuickAddMenu
                    v-if="can['post-daily'] || can['post-entries']"
                    side="top"
                >
                    <button
                        type="button"
                        class="-mt-6 flex size-14 items-center justify-center rounded-full bg-primary text-primary-foreground shadow-lg ring-4 ring-background"
                        :aria-label="t('নতুন কিছু লিখুন')"
                    >
                        <Plus class="size-7" />
                    </button>
                </QuickAddMenu>
            </div>

            <Link
                v-if="tabs[2]"
                :href="tabs[2].href"
                :class="['tab', isOn(tabs[2].href) && 'is-on']"
            >
                <component :is="tabs[2].icon" class="size-5" />
                <span>{{ t(tabs[2].label) }}</span>
            </Link>
            <span v-else />

            <button
                type="button"
                :class="['tab', mobileMenuOpen && 'is-on']"
                @click="mobileMenuOpen = true"
            >
                <Menu class="size-5" />
                <span>{{ t('মেনু') }}</span>
            </button>
        </div>
    </nav>
</template>

<style scoped>
@reference '../../css/app.css';

.tabbar {
    padding-bottom: max(0.4rem, env(safe-area-inset-bottom));
}

.tab {
    @apply flex flex-col items-center gap-0.5 rounded-lg py-1 text-[11px] leading-normal font-medium text-muted-foreground;
}

.tab.is-on {
    @apply text-paddy;
}
</style>
