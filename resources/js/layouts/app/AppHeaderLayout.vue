<script setup lang="ts">
import AppContent from '@/components/AppContent.vue';
import AppHeader from '@/components/AppHeader.vue';
import AppShell from '@/components/AppShell.vue';
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
    <AppShell variant="header">
        <AppHeader :breadcrumbs="breadcrumbs" />
        <AppContent variant="header">
            <slot />
        </AppContent>
        <Toaster
            position="top-right"
            rich-colors
            close-button
            :offset="{ top: '4.5rem' }"
            :mobile-offset="{ top: '4.25rem', left: '2.5rem', right: '2.5rem' }"
            :toast-options="{ class: 'khamar-toast' }"
        />
    </AppShell>
</template>
