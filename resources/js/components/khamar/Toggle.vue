<script setup lang="ts">
/** An on/off switch with its state written next to it, so nobody has to guess. */
const props = withDefaults(
    defineProps<{
        modelValue: boolean;
        onText?: string;
        offText?: string;
        disabled?: boolean;
    }>(),
    { onText: 'চালু', offText: 'বন্ধ', disabled: false },
);

const emit = defineEmits<{ 'update:modelValue': [value: boolean] }>();
</script>

<template>
    <button
        type="button"
        role="switch"
        :aria-checked="props.modelValue"
        :disabled="disabled"
        class="inline-flex items-center gap-2 rounded-full py-1 pr-1 text-xs font-medium disabled:opacity-50"
        @click="emit('update:modelValue', !props.modelValue)"
    >
        <span
            :class="[
                'relative inline-flex h-5 w-9 shrink-0 items-center rounded-full transition-colors',
                props.modelValue ? 'bg-primary' : 'bg-input',
            ]"
        >
            <span
                :class="[
                    'absolute size-4 rounded-full bg-card shadow transition-transform',
                    props.modelValue ? 'translate-x-4.5' : 'translate-x-0.5',
                ]"
            />
        </span>
        <span
            :class="
                props.modelValue ? 'text-foreground' : 'text-muted-foreground'
            "
            >{{ props.modelValue ? onText : offText }}</span
        >
    </button>
</template>
