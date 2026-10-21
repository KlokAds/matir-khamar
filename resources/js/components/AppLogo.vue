<script setup lang="ts">
import AppLogoIcon from '@/components/AppLogoIcon.vue';
</script>

<template>
    <div
        class="flex aspect-square size-9 items-center justify-center rounded-lg bg-[oklch(0.97_0.02_85)] ring-1 ring-sidebar-border"
    >
        <AppLogoIcon class="size-7" />
    </div>
    <div class="ml-1 grid flex-1 text-left leading-tight">
        <span class="font-display truncate text-[15px] text-sidebar-foreground">
            মাটির খামার
        </span>
        <span
            class="truncate text-[11px] tracking-wide text-sidebar-foreground/60 uppercase"
        >
            Klok Agro · হিসাব
        </span>
    </div>
</template>
