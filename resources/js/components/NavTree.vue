<script setup lang="ts">
import { Link, usePage } from '@inertiajs/vue3';
import { ChevronRight } from '@lucide/vue';
import { computed, reactive, watch } from 'vue';
import {
    SidebarGroup,
    SidebarGroupLabel,
    SidebarMenu,
    SidebarMenuButton,
    SidebarMenuItem,
    SidebarMenuSub,
    SidebarMenuSubButton,
    SidebarMenuSubItem,
} from '@/components/ui/sidebar';
import type { NavItem } from '@/types';

/**
 * The sidebar menu: top-level entries, some with a group of sub-entries that opens when one of
 * them is the current page. A sub-entry with a query (/stock/move?mode=issue) only matches that
 * exact query, so "give feed" and "buy feed" are told apart.
 */
const props = defineProps<{ items: NavItem[]; label?: string }>();

const page = usePage();
const current = computed(() => new URL(page.url, window.location.origin));

function matches(href: NavItem['href']): boolean {
    const target = new URL(
        typeof href === 'string' ? href : href.url,
        window.location.origin,
    );

    if (target.pathname !== current.value.pathname) {
        return false;
    }

    return [...target.searchParams].every(
        ([key, value]) => current.value.searchParams.get(key) === value,
    );
}

const containsCurrent = (item: NavItem) =>
    (item.children ?? []).some((child) => matches(child.href));

const open = reactive<Record<string, boolean>>({});
watch(
    () => [props.items, current.value.href],
    () => {
        for (const item of props.items) {
            if (item.children?.length && containsCurrent(item)) {
                open[item.title] = true;
            }
        }
    },
    { immediate: true },
);
</script>

<template>
    <SidebarGroup class="px-2 py-0">
        <SidebarGroupLabel v-if="label">{{ label }}</SidebarGroupLabel>
        <SidebarMenu>
            <SidebarMenuItem v-for="item in items" :key="item.title">
                <template v-if="item.children?.length">
                    <SidebarMenuButton
                        :tooltip="item.title"
                        :is-active="!open[item.title] && containsCurrent(item)"
                        @click="open[item.title] = !open[item.title]"
                    >
                        <component :is="item.icon" />
                        <span>{{ item.title }}</span>
                        <ChevronRight
                            :class="[
                                'ml-auto transition-transform',
                                open[item.title] && 'rotate-90',
                            ]"
                        />
                    </SidebarMenuButton>
                    <SidebarMenuSub v-if="open[item.title]">
                        <SidebarMenuSubItem
                            v-for="child in item.children"
                            :key="child.title"
                        >
                            <SidebarMenuSubButton
                                as-child
                                :is-active="matches(child.href)"
                            >
                                <a
                                    v-if="child.reload"
                                    :href="String(child.href)"
                                >
                                    <span>{{ child.title }}</span>
                                </a>
                                <Link v-else :href="child.href">
                                    <span>{{ child.title }}</span>
                                </Link>
                            </SidebarMenuSubButton>
                        </SidebarMenuSubItem>
                    </SidebarMenuSub>
                </template>

                <SidebarMenuButton
                    v-else
                    as-child
                    :is-active="matches(item.href)"
                    :tooltip="item.title"
                >
                    <a v-if="item.reload" :href="String(item.href)">
                        <component :is="item.icon" />
                        <span>{{ item.title }}</span>
                        <span
                            v-if="item.badge"
                            class="num ml-auto rounded-full bg-sidebar-primary px-1.5 text-[11px] font-semibold text-sidebar-primary-foreground"
                            >{{ item.badge }}</span
                        >
                    </a>
                    <Link v-else :href="item.href">
                        <component :is="item.icon" />
                        <span>{{ item.title }}</span>
                    </Link>
                </SidebarMenuButton>
            </SidebarMenuItem>
        </SidebarMenu>
    </SidebarGroup>
</template>
