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

/** The sidebar tree: the day-to-day pages first, then one folding group per part of the farm. */
const items = computed<NavItem[]>(() => [
    ...nav.top.value.map((link) => ({
        title: t(link.title),
        href: link.href,
        icon: link.icon,
        reload: link.reload,
        badge: link.badge,
    })),
    ...nav.groups.value.map((g) => ({
        title: t(g.title),
        href: g.links[0].href,
        icon: g.icon,
        children: g.links.map((link) => ({
            title: t(link.title),
            href: link.href,
        })),
    })),
]);
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
            <NavTree :items="items" />
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
