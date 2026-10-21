<script setup lang="ts">
defineProps<{
    title: string;
    subtitle?: string;
}>();
</script>

<template>
    <div class="furrows -mx-4 -mt-4 mb-6 border-b border-border/70 px-4 pt-5 pb-4 md:-mx-6 md:px-6">
        <div class="flex flex-wrap items-end justify-between gap-3">
            <div class="min-w-0">
                <h1 class="font-display text-2xl leading-tight text-foreground md:text-[1.7rem]">
                    {{ title }}
                </h1>
                <p v-if="subtitle" class="mt-1 text-sm text-muted-foreground">
                    {{ subtitle }}
                </p>
            </div>
            <div class="flex flex-wrap items-center gap-2">
                <slot name="actions" />
            </div>
        </div>
        <slot />
    </div>
</template>
