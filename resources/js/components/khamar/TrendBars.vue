<script setup lang="ts">
import { computed } from 'vue';
import { monthLabel, taka } from '@/lib/khamar';

const props = defineProps<{
    months: { month: string; income: number; expense: number }[];
}>();

const peak = computed(() =>
    Math.max(1, ...props.months.flatMap((m) => [m.income, m.expense])),
);
const height = (value: number) =>
    `${Math.max(2, Math.round((Math.max(0, value) / peak.value) * 100))}%`;
</script>

<template>
    <div>
        <div
            class="flex h-44 items-end gap-2 border-b border-border pb-px sm:gap-3"
        >
            <div
                v-for="m in months"
                :key="m.month"
                class="group relative flex h-full flex-1 items-end justify-center gap-0.5"
            >
                <div
                    class="w-full max-w-5 rounded-t-sm bg-income/85 transition-opacity group-hover:opacity-100"
                    :style="{ height: height(m.income) }"
                />
                <div
                    class="w-full max-w-5 rounded-t-sm bg-expense/75"
                    :style="{ height: height(m.expense) }"
                />
                <div
                    class="pointer-events-none absolute bottom-full z-10 mb-2 hidden w-max rounded-md border border-border bg-popover px-2.5 py-1.5 text-xs shadow-md group-hover:block"
                >
                    <p class="font-medium">{{ monthLabel(m.month) }}</p>
                    <p class="num text-income">আয় {{ taka(m.income) }}</p>
                    <p class="num text-expense">খরচ {{ taka(m.expense) }}</p>
                    <p class="num">লাভ {{ taka(m.income - m.expense) }}</p>
                </div>
            </div>
        </div>
        <div class="mt-1.5 flex gap-2 sm:gap-3">
            <span
                v-for="m in months"
                :key="m.month"
                class="flex-1 text-center text-[11px] text-muted-foreground"
                >{{ monthLabel(m.month) }}</span
            >
        </div>
        <div class="mt-3 flex gap-4 text-xs text-muted-foreground">
            <span class="flex items-center gap-1.5"
                ><i class="size-2.5 rounded-sm bg-income" /> আয়</span
            >
            <span class="flex items-center gap-1.5"
                ><i class="size-2.5 rounded-sm bg-expense" /> খরচ</span
            >
        </div>
    </div>
</template>
