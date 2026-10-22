<script setup lang="ts">
import { Link } from '@inertiajs/vue3';
import {
    AlarmClock,
    Beef,
    ChartColumnBig,
    CloudOff,
    HandCoins,
    LayoutGrid,
    Settings2,
    Sprout,
    Wheat,
} from '@lucide/vue';
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
import { useCan } from '@/lib/khamar';
import { pendingCount } from '@/lib/offlineQueue';
import { dashboard } from '@/routes';
import type { NavItem } from '@/types';

const can = useCan();

/** A group with only the entries this user may open; a group left empty is dropped. */
function group(
    title: string,
    icon: NavItem['icon'],
    entries: [string, string, boolean][],
): NavItem[] {
    const children = entries
        .filter(([, , allowed]) => allowed)
        .map(([childTitle, href]) => ({ title: t(childTitle), href }));

    return children.length
        ? [{ title: t(title), href: children[0].href, icon, children }]
        : [];
}

const items = computed<NavItem[]>(() => {
    const c = can.value;

    // An investor-only login sees one page: their own projects.
    if (c['investor-view'] && Object.keys(c).length === 1) {
        return [
            { title: t('আমার বিনিয়োগ'), href: '/investor', icon: HandCoins },
        ];
    }

    return [
        { title: t('আজকের খামার'), href: dashboard(), icon: LayoutGrid },
        ...(c['post-daily']
            ? [
                  {
                      title: t('অফলাইন এন্ট্রি'),
                      href: '/offline',
                      icon: CloudOff,
                      reload: true,
                      badge: pendingCount.value,
                  },
              ]
            : []),
        {
            title: t('মনে করিয়ে দিন'),
            href: '/reminders',
            icon: AlarmClock,
        },
        ...group('গরু', Beef, [
            ['গরুর তালিকা', '/animals', !!c['view-reports']],
            ['নতুন গরু', '/animals/create', !!c['post-entries']],
            ['দুধের হিসাব', '/milk', !!c['post-daily']],
            ['গর্ভ ও বাছুর', '/breeding', !!c['view-reports']],
            ['টিকা ও কৃমি', '/health', !!c['view-reports']],
            ['মোটাতাজা ব্যাচ', '/batches', !!c['view-reports']],
            ['খামার ডায়েরি ও লক্ষ্য', '/diary', !!c['view-reports']],
        ]),
        ...group('জমি ও ফসল', Sprout, [
            ['জমি ও মৌসুম', '/land', !!c['view-reports']],
            ['প্রজেক্ট সাজান', '/setup/centers', !!c['manage-setup']],
        ]),
        ...group('খাবার ও স্টক', Wheat, [
            ['গরুকে খাবার দেওয়া', '/stock/move?mode=issue', !!c['post-daily']],
            ['মাল কেনা', '/stock/move?mode=purchase', !!c['post-entries']],
            [
                'নিজের উৎপাদন (ঘাস)',
                '/stock/move?mode=produce',
                !!c['post-entries'],
            ],
            ['স্টকে কী আছে', '/stock', !!c['view-reports']],
            ['কোথায় কত কেজি গেল', '/reports/usage', !!c['view-reports']],
        ]),
        ...group('টাকার হিসাব', HandCoins, [
            ['নতুন এন্ট্রি', '/entries/create', !!c['post-entries']],
            ['মজুরির খাতা', '/labour', !!c['view-reports']],
            ['নগদ ও পাওনা', '/cash', !!c['view-reports']],
            ['টাকা কোথায় আছে', '/money', !!c['view-reports']],
            ['খাতা (সব এন্ট্রি)', '/entries', !!c['view-reports']],
            ['খরচ ভাগ (মাস শেষে)', '/allocation', !!c['post-entries']],
        ]),
        ...group('রিপোর্ট', ChartColumnBig, [
            ['লাভের হিসাব ও পরিকল্পনা', '/profit', !!c['view-reports']],
            ['বাজারদর', '/prices', !!c['view-reports']],
            ['ক্যাটাগরি ও প্রজেক্ট', '/centers', !!c['view-reports']],
            ['লাভ-ক্ষতি ও সম্পদ', '/reports', !!c['view-reports']],
            ['মাসিক রিপোর্ট', '/reports/monthly', !!c['view-reports']],
            ['উদ্যোগ বাজেট', '/reports/budget', !!c['view-reports']],
            ['ভাগ কীভাবে হয় (উদাহরণ)', '/guide/split', !!c['view-reports']],
            ['সাহায্য ও গাইড', '/guide', true],
        ]),
        ...group('সেটআপ ও নিরাপত্তা', Settings2, [
            ['প্রজেক্ট সাজান', '/setup/centers', !!c['manage-setup']],
            ['হিসাব খাত', '/setup/accounts', !!c['manage-setup']],
            ['ক্রেতা, বিনিয়োগকারী…', '/setup/parties', !!c['manage-setup']],
            ['বিলিং থেকে আনা', '/setup/import', !!c['manage-import']],
            ['ব্যবহারকারী', '/setup/users', !!c['manage-users']],
            ['বিনিয়োগকারী', '/setup/investors', !!c['manage-users']],
            ['রোল ও অনুমতি', '/setup/roles', !!c['manage-roles']],
            ['মাস লক', '/setup/periods', !!c['lock-periods']],
            ['অডিট লগ', '/setup/audit', !!c['view-audit']],
            ['অ্যাপ আপডেট', '/setup/update', !!c['manage-update']],
        ]),
    ];
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
