<script setup lang="ts">
import AppContent from '@/components/AppContent.vue';
import AppShell from '@/components/AppShell.vue';
import AppSidebar from '@/components/AppSidebar.vue';
import AppSidebarHeader from '@/components/AppSidebarHeader.vue';
import AlarmHost from '@/components/AlarmHost.vue';
import AppDialogHost from '@/components/AppDialogHost.vue';
import MobileMenu from '@/components/MobileMenu.vue';
import MobileTabBar from '@/components/MobileTabBar.vue';
import { Toaster } from '@/components/ui/sonner';
import type { BreadcrumbItem } from '@/types';

type Props = {
    breadcrumbs?: BreadcrumbItem[];
};

withDefaults(defineProps<Props>(), {
    breadcrumbs: () => [],
});
</script>

<template>
    <AppShell variant="sidebar">
        <AppSidebar />
        <AppContent
            variant="sidebar"
            class="min-w-0 overflow-x-clip pb-24 md:pb-0"
        >
            <AppSidebarHeader :breadcrumbs="breadcrumbs" />
            <slot />
        </AppContent>
        <MobileTabBar />
        <MobileMenu />
        <Toaster
            position="top-right"
            rich-colors
            close-button
            :offset="{ top: '4.5rem' }"
            :mobile-offset="{ top: '4.25rem', left: '2.5rem', right: '2.5rem' }"
            :toast-options="{ class: 'khamar-toast' }"
        />
        <AppDialogHost />
        <AlarmHost />
    </AppShell>
</template>
