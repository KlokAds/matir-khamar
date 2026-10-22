<script setup lang="ts">
import { Link } from '@inertiajs/vue3';
import { computed } from 'vue';
import AppLogo from '@/components/AppLogo.vue';
import NavTree from '@/components/NavTree.vue';
import NavUser from '@/components/NavUser.vue';
import {
    Sidebar,
    SidebarContent,
    SidebarFooter,
    SidebarHeader,
    SidebarMenu,
    SidebarMenuButton,
    SidebarMenuItem,
} from '@/components/ui/sidebar';
import { t } from '@/lib/i18n';
import { useNav } from '@/lib/nav';
import { dashboard } from '@/routes';
import type { NavItem } from '@/types';

const nav = useNav();

/**
 * The sidebar in sections, each under a small heading: the day-to-day pages, then the farm's
 * own parts, then money and reports, then setup.
 */
const SECTION_OF: Record<string, 'farm' | 'money' | 'setup'> = {
    'টাকার হিসাব': 'money',
    রিপোর্ট: 'money',
    'সেটআপ ও নিরাপত্তা': 'setup',
};

const toItem = (g: (typeof nav.groups.value)[number]): NavItem => ({
    title: t(g.title),
    href: g.links[0].href,
    icon: g.icon,
    children: g.links.map((link) => ({
        title: t(link.title),
        href: link.href,
    })),
});

const sections = computed(() => {
    const groups = nav.groups.value;
    const of = (key: 'farm' | 'money' | 'setup') =>
        groups
            .filter((g) => (SECTION_OF[g.title] ?? 'farm') === key)
            .map(toItem);

    return [
        {
            label: t('প্রতিদিন'),
            items: nav.top.value.map((link) => ({
                title: t(link.title),
                href: link.href,
                icon: link.icon,
                reload: link.reload,
                badge: link.badge,
            })),
        },
        { label: t('খামার'), items: of('farm') },
        { label: t('টাকা ও রিপোর্ট'), items: of('money') },
        { label: t('সেটআপ'), items: of('setup') },
    ].filter((section) => section.items.length);
});
</script>

<template>
    <Sidebar collapsible="icon" variant="inset">
        <SidebarHeader>
            <SidebarMenu>
                <SidebarMenuItem>
                    <SidebarMenuButton size="lg" class="h-14" as-child>
                        <Link :href="dashboard()">
                            <AppLogo />
                        </Link>
                    </SidebarMenuButton>
                </SidebarMenuItem>
            </SidebarMenu>
        </SidebarHeader>

        <SidebarContent>
            <NavTree
                v-for="section in sections"
                :key="section.label"
                :items="section.items"
                :label="section.label"
            />
        </SidebarContent>

        <SidebarFooter>
            <svg
                viewBox="0 0 200 24"
                class="mx-2 h-5 text-sidebar-foreground/15 group-data-[collapsible=icon]:hidden"
                aria-hidden="true"
            >
                <path
                    d="M0 8c40-6 160-6 200 0M0 15c40-5 160-5 200 0M0 22c40-4 160-4 200 0"
                    stroke="currentColor"
                    stroke-width="2"
                    fill="none"
                />
            </svg>
            <NavUser />
        </SidebarFooter>
    </Sidebar>
    <slot />
</template>
