<script setup lang="ts">
import { Link, router, usePage } from '@inertiajs/vue3';
import { LogOut, Moon, Settings, Sun } from '@lucide/vue';
import { computed, onBeforeUnmount } from 'vue';
import AppLogoIcon from '@/components/AppLogoIcon.vue';
import {
    Sheet,
    SheetContent,
    SheetDescription,
    SheetTitle,
} from '@/components/ui/sheet';
import { useAppearance } from '@/composables/useAppearance';
import { t } from '@/lib/i18n';
import { mobileMenuOpen, useNav } from '@/lib/nav';
import type { NavGroup } from '@/lib/nav';
import { prepareLogout } from '@/lib/offline';
import { pendingCount } from '@/lib/offlineQueue';
import { logout } from '@/routes';
import { edit } from '@/routes/profile';

/**
 * The phone's menu: a sheet from the bottom with every page as a tile, grouped by part of the
 * farm, like the home screen of an app — instead of the desktop sidebar squeezed onto a phone.
 */
const nav = useNav();
const page = usePage();
const user = computed(() => page.props.auth.user);
const roleLabel = computed(
    () =>
        (page.props.auth as unknown as { roleLabel: string | null }).roleLabel,
);
const remindersDue = computed(() => Number(page.props.remindersDue ?? 0));
const path = computed(() => page.url.split('?')[0]);
const locale = computed(() => (page.props.locale as string) ?? 'bn');
const { resolvedAppearance, updateAppearance } = useAppearance();

const isOn = (href: string) => path.value === href.split('?')[0];

const tones: Record<NavGroup['tone'], string> = {
    paddy: 'bg-paddy/15 text-paddy',
    clay: 'bg-clay/15 text-clay',
    turmeric: 'bg-turmeric/15 text-turmeric',
    pond: 'bg-pond/15 text-pond',
    muted: 'bg-muted text-muted-foreground',
};

const close = () => (mobileMenuOpen.value = false);
const stop = router.on('navigate', close);
onBeforeUnmount(stop);

const switchTo = (next: 'bn' | 'en') => {
    if (next !== locale.value) {
        router.post('/locale', { locale: next }, { preserveScroll: true });
    }
};

async function signOut() {
    if (!(await prepareLogout(pendingCount.value))) {
        return;
    }

    close();
    router.flushAll();
    router.post(logout().url);
}
</script>

<template>
    <Sheet v-model:open="mobileMenuOpen">
        <SheetContent
            side="bottom"
            class="max-h-[92dvh] gap-0 overflow-y-auto rounded-t-3xl p-0 md:hidden [&>button:last-child]:top-6 [&>button:last-child]:z-20 [&>button:last-child]:rounded-full [&>button:last-child]:border [&>button:last-child]:border-border [&>button:last-child]:bg-card [&>button:last-child]:p-2 [&>button:last-child]:opacity-100"
        >
            <div
                class="sticky top-0 z-10 border-b border-border bg-background/95 px-4 pt-2 pb-3 backdrop-blur"
            >
                <div class="mx-auto mb-3 h-1 w-10 rounded-full bg-border" />
                <div class="flex items-center gap-3 pr-8">
                    <span
                        class="flex size-11 shrink-0 items-center justify-center rounded-2xl bg-paddy/15"
                    >
                        <AppLogoIcon class="size-7" />
                    </span>
                    <div class="min-w-0">
                        <SheetTitle
                            class="truncate py-px font-display text-lg leading-normal"
                            >{{ t('মাটির খামার') }}</SheetTitle
                        >
                        <SheetDescription
                            class="truncate text-xs leading-normal text-muted-foreground"
                        >
                            {{ user?.name
                            }}<template v-if="roleLabel">
                                · {{ roleLabel }}</template
                            >
                        </SheetDescription>
                    </div>
                </div>
            </div>

            <div class="space-y-5 px-4 pt-4 pb-6">
                <!-- Day-to-day pages, a tap away -->
                <div class="grid grid-cols-4 gap-2">
                    <component
                        :is="link.reload ? 'a' : Link"
                        v-for="link in nav.top.value"
                        :key="link.href"
                        :href="link.href"
                        :class="['tile relative', isOn(link.href) && 'is-on']"
                        @click="close"
                    >
                        <span class="tile-icon bg-paddy/15 text-paddy">
                            <component :is="link.icon" class="size-5" />
                        </span>
                        <span class="tile-label">{{ t(link.title) }}</span>
                        <b
                            v-if="
                                (link.href === '/reminders' &&
                                    remindersDue > 0) ||
                                (link.badge ?? 0) > 0
                            "
                            class="num absolute top-1 right-2 flex h-4 min-w-4 items-center justify-center rounded-full bg-destructive px-1 text-[10px] text-white"
                            >{{
                                link.href === '/reminders'
                                    ? remindersDue
                                    : link.badge
                            }}</b
                        >
                    </component>
                </div>

                <!-- One section per part of the farm -->
                <section v-for="g in nav.groups.value" :key="g.title">
                    <h3
                        class="mb-2 flex items-center gap-2 px-1 text-xs font-semibold tracking-wide text-muted-foreground"
                    >
                        <component :is="g.icon" class="size-3.5" />
                        {{ t(g.title) }}
                    </h3>
                    <div
                        class="grid grid-cols-3 gap-2 rounded-2xl border border-border bg-card p-2"
                    >
                        <Link
                            v-for="link in g.links"
                            :key="link.href"
                            :href="link.href"
                            :class="['tile', isOn(link.href) && 'is-on']"
                            @click="close"
                        >
                            <span :class="['tile-icon', tones[g.tone]]">
                                <component :is="link.icon" class="size-5" />
                            </span>
                            <span class="tile-label">{{ t(link.title) }}</span>
                        </Link>
                    </div>
                </section>

                <!-- Account and display -->
                <div
                    class="flex flex-wrap items-center gap-2 rounded-2xl border border-border bg-card p-2"
                >
                    <Link :href="edit()" class="foot-btn" @click="close">
                        <Settings class="size-4" />
                        {{ t('সেটিংস') }}
                    </Link>
                    <button
                        type="button"
                        class="foot-btn"
                        @click="
                            updateAppearance(
                                resolvedAppearance === 'dark'
                                    ? 'light'
                                    : 'dark',
                            )
                        "
                    >
                        <Sun
                            v-if="resolvedAppearance === 'dark'"
                            class="size-4"
                        />
                        <Moon v-else class="size-4" />
                        {{
                            resolvedAppearance === 'dark'
                                ? t('আলো (লাইট) মোড')
                                : t('অন্ধকার (ডার্ক) মোড')
                        }}
                    </button>
                    <div
                        class="flex items-center rounded-full border border-border p-0.5 text-xs font-semibold"
                    >
                        <button
                            type="button"
                            :class="[
                                'rounded-full px-2.5 py-1',
                                locale === 'bn'
                                    ? 'bg-paddy text-primary-foreground'
                                    : 'text-muted-foreground',
                            ]"
                            @click="switchTo('bn')"
                        >
                            বাং
                        </button>
                        <button
                            type="button"
                            :class="[
                                'rounded-full px-2.5 py-1',
                                locale === 'en'
                                    ? 'bg-paddy text-primary-foreground'
                                    : 'text-muted-foreground',
                            ]"
                            @click="switchTo('en')"
                        >
                            EN
                        </button>
                    </div>
                    <button
                        type="button"
                        class="foot-btn ml-auto text-destructive"
                        @click="signOut"
                    >
                        <LogOut class="size-4" />
                        {{ t('লগআউট') }}
                    </button>
                </div>
            </div>
        </SheetContent>
    </Sheet>
</template>

<style scoped>
@reference '../../css/app.css';

.tile {
    @apply flex min-w-0 flex-col items-center gap-1.5 rounded-xl px-1 py-2.5 text-center transition-colors active:bg-accent;
}

.tile.is-on {
    @apply bg-accent;
}

.tile-icon {
    @apply flex size-11 items-center justify-center rounded-2xl;
}

.tile-label {
    @apply line-clamp-2 text-[12px] leading-snug font-medium break-words text-foreground;
}

.foot-btn {
    @apply flex h-9 items-center gap-1.5 rounded-full px-3 text-sm text-muted-foreground transition-colors hover:bg-accent hover:text-foreground;
}
</style>
