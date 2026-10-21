<script setup lang="ts">
defineProps<{
    label: string;
    error?: string;
    hint?: string;
    for?: string;
}>();
</script>

<template>
    <div class="grid gap-1.5">
        <label :for="$props.for" class="text-sm font-medium">{{ label }}</label>
        <slot />
        <p v-if="hint && !error" class="text-xs text-muted-foreground">
            {{ hint }}
        </p>
        <p v-if="error" class="text-xs text-destructive">{{ error }}</p>
    </div>
</template>
