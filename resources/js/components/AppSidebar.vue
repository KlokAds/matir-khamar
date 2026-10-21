<script setup lang="ts">
import { Link } from '@inertiajs/vue3';
import {
    ArrowLeftRight,
    Beef,
    BookOpenText,
    ChartColumnBig,
    CloudDownload,
    FileClock,
    HandCoins,
    Landmark,
    LayoutGrid,
    LockKeyhole,
    Milk,
    Network,
    PackageOpen,
    Scale,
    Users,
    UsersRound,
    Wheat,
} from '@lucide/vue';
import { computed } from 'vue';
import AppLogo from '@/components/AppLogo.vue';
import NavMain from '@/components/NavMain.vue';
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
import { useCan } from '@/lib/khamar';
import { dashboard } from '@/routes';
import type { NavItem } from '@/types';

const can = useCan();

const groups = computed(() => {
    const daily: NavItem[] = [{ title: 'আজকের খামার', href: dashboard(), icon: LayoutGrid }];

    if (can.value['post-daily']) {
        daily.push(
            { title: 'দুধের হিসাব', href: '/milk', icon: Milk },
            { title: 'খাবার ব্যবহার', href: '/stock/move?mode=issue', icon: Wheat },
        );
    }

    const books: NavItem[] = [];

    if (can.value['post-entries']) {
        books.push(
            { title: 'নতুন এন্ট্রি', href: '/entries/create', icon: HandCoins },
            { title: 'খরচ ভাগ (মাস শেষে)', href: '/allocation', icon: Scale },
        );
    }

    if (can.value['view-reports']) {
        books.push(
            { title: 'ক্যাটাগরি ও প্রজেক্ট', href: '/centers', icon: Network },
            { title: 'গরুর তালিকা', href: '/animals', icon: Beef },
            { title: 'স্টক (খাবার/ঔষধ)', href: '/stock', icon: PackageOpen },
            { title: 'খাতা (সব এন্ট্রি)', href: '/entries', icon: BookOpenText },
            { title: 'রিপোর্ট', href: '/reports', icon: ChartColumnBig },
            { title: 'উদ্যোগ বাজেট', href: '/reports/budget', icon: Landmark },
        );
    }

    const setup: NavItem[] = [];

    if (can.value['manage-setup']) {
        setup.push(
            { title: 'হিসাব খাত', href: '/setup/accounts', icon: ArrowLeftRight },
            { title: 'ক্রেতা, বিনিয়োগকারী…', href: '/setup/parties', icon: UsersRound },
            { title: 'বিলিং থেকে আনা', href: '/setup/import', icon: CloudDownload },
        );
    }

    if (can.value['manage-users']) {
        setup.push({ title: 'ব্যবহারকারী', href: '/setup/users', icon: Users });
    }

    if (can.value['lock-periods']) {
        setup.push({ title: 'মাস লক', href: '/setup/periods', icon: LockKeyhole });
    }

    if (can.value['view-audit']) {
        setup.push({ title: 'অডিট লগ', href: '/setup/audit', icon: FileClock });
    }

    return [
        { label: 'প্রতিদিন', items: daily },
        { label: 'হিসাব', items: books },
        { label: 'সেটআপ ও নিরাপত্তা', items: setup },
    ].filter((group) => group.items.length > 0);
});
</script>

<template>
    <Sidebar collapsible="icon" variant="inset">
        <SidebarHeader>
            <SidebarMenu>
                <SidebarMenuItem>
                    <SidebarMenuButton size="lg" as-child>
                        <Link :href="dashboard()">
                            <AppLogo />
                        </Link>
                    </SidebarMenuButton>
                </SidebarMenuItem>
            </SidebarMenu>
        </SidebarHeader>

        <SidebarContent>
            <NavMain
                v-for="group in groups"
                :key="group.label"
                :label="group.label"
                :items="group.items"
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
