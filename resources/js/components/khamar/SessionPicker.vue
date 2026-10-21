<script setup lang="ts">
import { Moon, Sun, Sunrise } from '@lucide/vue';

/** Morning / noon / afternoon buttons for feeding (and any other time-of-day entry). */
defineProps<{ sessions: Record<string, string>; label?: string }>();

const model = defineModel<string>({ required: true });
const icons: Record<string, typeof Sun> = {
    morning: Sunrise,
    noon: Sun,
    evening: Moon,
};
</script>

<template>
    <div class="grid gap-1.5">
        <span class="text-sm font-medium">{{ label ?? 'বেলা' }}</span>
        <div
            class="grid gap-1 rounded-md border border-input p-1"
            :style="{
                gridTemplateColumns: `repeat(${Object.keys(sessions).length}, minmax(0, 1fr))`,
            }"
        >
            <button
                v-for="(text, key) in sessions"
                :key="key"
                type="button"
                :class="[
                    'flex items-center justify-center gap-1 rounded py-2 text-sm',
                    model === key
                        ? 'bg-primary text-primary-foreground'
                        : 'hover:bg-accent',
                ]"
                @click="model = String(key)"
            >
                <component :is="icons[key]" class="size-4" /> {{ text }}
            </button>
        </div>
    </div>
</template>
