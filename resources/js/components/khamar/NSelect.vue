<script setup lang="ts">
/**
 * Native select: fast on cheap phones, works with the keyboard, and shows
 * the category tree with indentation.
 */
defineOptions({ inheritAttrs: false });

withDefaults(
    defineProps<{
        placeholder?: string;
    }>(),
    { placeholder: '' },
);

const model = defineModel<string | number | null | undefined>();
</script>

<template>
    <select
        v-model="model"
        v-bind="$attrs"
        class="h-10 w-full min-w-0 rounded-md border border-input bg-card px-3 text-sm shadow-xs outline-none focus-visible:border-ring focus-visible:ring-[3px] focus-visible:ring-ring/40 disabled:opacity-50"
    >
        <option v-if="placeholder" :value="null" disabled>{{ placeholder }}</option>
        <slot />
    </select>
</template>
