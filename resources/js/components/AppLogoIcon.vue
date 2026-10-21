<script setup lang="ts">
import type { HTMLAttributes } from 'vue';

defineOptions({
    inheritAttrs: false,
});

type Props = {
    className?: HTMLAttributes['class'];
};

defineProps<Props>();
</script>

<template>
    <!-- Sun over ploughed furrows with a sprout: the Matir Khamar mark. -->
    <svg
        xmlns="http://www.w3.org/2000/svg"
        viewBox="0 0 48 48"
        fill="none"
        :class="className"
        v-bind="$attrs"
        aria-hidden="true"
    >
        <circle cx="31" cy="17" r="8" fill="var(--turmeric)" />
        <path
            d="M24 31c0-6 3-10 8-11-1 5-3 8-8 11Zm0 0c0-5-2.5-8-6.5-9 .8 4.2 2.6 6.8 6.5 9Z"
            fill="var(--paddy)"
        />
        <path
            d="M24 31v5"
            stroke="var(--paddy)"
            stroke-width="2"
            stroke-linecap="round"
        />
        <path
            d="M4 36c8-3 32-3 40 0M6 41c8-2.5 28-2.5 36 0"
            stroke="var(--clay)"
            stroke-width="2.6"
            stroke-linecap="round"
        />
    </svg>
</template>
