<script setup lang="ts">
import type { Component } from 'vue';

withDefaults(
    defineProps<{
        label: string;
        value: string;
        hint?: string;
        icon?: Component;
        tone?: 'paddy' | 'clay' | 'turmeric' | 'pond' | 'soil';
    }>(),
    { tone: 'paddy', hint: '', icon: undefined },
);

const tones = {
    paddy: 'bg-paddy/12 text-paddy',
    clay: 'bg-clay/12 text-clay',
    turmeric: 'bg-turmeric/25 text-soil',
    pond: 'bg-pond/12 text-pond',
    soil: 'bg-soil/10 text-soil',
};
</script>

<template>
    <div class="relative overflow-hidden rounded-xl border border-border bg-card p-4 shadow-[0_1px_0_0_var(--border)]">
        <div class="flex items-start justify-between gap-3">
            <div class="min-w-0">
                <p class="text-[13px] text-muted-foreground">{{ label }}</p>
                <p class="num mt-1 truncate text-2xl font-semibold tracking-tight">
                    {{ value }}
                </p>
                <p v-if="hint" class="mt-1 truncate text-xs text-muted-foreground">
                    {{ hint }}
                </p>
            </div>
            <span v-if="icon" :class="['rounded-lg p-2', tones[tone]]">
                <component :is="icon" class="size-5" />
            </span>
        </div>
    </div>
</template>
