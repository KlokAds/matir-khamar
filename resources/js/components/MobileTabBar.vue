<script setup lang="ts">
import { Link, usePage } from '@inertiajs/vue3';
import { LayoutGrid, Menu, Milk, Plus, Wheat } from '@lucide/vue';
import { computed } from 'vue';
import QuickAddMenu from '@/components/QuickAddMenu.vue';
import { useSidebar } from '@/components/ui/sidebar';
import { t } from '@/lib/i18n';
import { useCan, useModules } from '@/lib/khamar';

/**
 * The phone's bottom bar, like an app: today, milk, the big ＋, feed and the full menu, all
 * within reach of a thumb while the other hand holds a bucket.
 */
const { setOpenMobile } = useSidebar();
const can = useCan();
const modules = useModules();
const page = usePage();
const path = computed(() => page.url.split('?')[0]);

const tabs = computed(() =>
    [
        { href: '/dashboard', label: 'আজ', icon: LayoutGrid, show: true },
        {
            href: '/milk',
            label: 'দুধ',
            icon: Milk,
            show: !!can.value['post-daily'] && modules.value.milk,
        },
        {
            href: '/stock/move?mode=issue',
            label: 'খাবার',
            icon: Wheat,
            show:
                !!can.value['post-daily'] &&
                (modules.value.cattle || modules.value.goat),
        },
    ].filter((tab) => tab.show),
);

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
            <span
                v-for="n in Math.max(0, 2 - tabs.slice(0, 2).length)"
                :key="`a${n}`"
            />

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
                v-for="tab in tabs.slice(2)"
                :key="tab.href"
                :href="tab.href"
                :class="['tab', isOn(tab.href) && 'is-on']"
            >
                <component :is="tab.icon" class="size-5" />
                <span>{{ t(tab.label) }}</span>
            </Link>
            <span v-if="tabs.length < 3" />

            <button type="button" class="tab" @click="setOpenMobile(true)">
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
    @apply flex flex-col items-center gap-0.5 rounded-lg py-1 text-[11px] font-medium text-muted-foreground;
}

.tab.is-on {
    @apply text-paddy;
}
</style>
