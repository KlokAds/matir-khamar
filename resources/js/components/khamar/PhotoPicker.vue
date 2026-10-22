<script setup lang="ts">
import { Camera, Paperclip, X } from '@lucide/vue';
import { ref } from 'vue';
import { t } from '@/lib/i18n';

/**
 * A receipt or photo, two ways: take it with the camera there and then, or pick a file or a
 * photo already on the phone. Phones differ — some open only the camera when asked for a
 * photo, some only the gallery — so both are always offered side by side.
 */
const props = withDefaults(
    defineProps<{
        accept?: string;
        multiple?: boolean;
        disabled?: boolean;
        /** Show the picked files with a remove button (off when the page uploads at once). */
        list?: boolean;
    }>(),
    {
        accept: 'image/*,application/pdf',
        multiple: true,
        disabled: false,
        list: true,
    },
);

const files = defineModel<File[]>('files', { default: () => [] });
const emit = defineEmits<{ picked: [files: File[]] }>();
const camera = ref<HTMLInputElement | null>(null);
const picker = ref<HTMLInputElement | null>(null);

function take(event: Event) {
    const input = event.target as HTMLInputElement;
    const added = Array.from(input.files ?? []);
    input.value = '';

    if (!added.length) {
        return;
    }

    files.value = props.multiple
        ? [...files.value, ...added]
        : added.slice(0, 1);
    emit('picked', added);
}

const remove = (index: number) =>
    (files.value = files.value.filter((_, i) => i !== index));
</script>

<template>
    <div class="space-y-2">
        <div class="grid grid-cols-2 gap-2">
            <button
                type="button"
                class="flex h-11 items-center justify-center gap-2 rounded-lg border border-primary/40 bg-primary/10 px-3 text-sm font-medium text-primary transition-colors hover:bg-primary/15 active:bg-primary/20 disabled:opacity-50"
                :disabled="disabled"
                @click="camera?.click()"
            >
                <Camera class="size-4" /> {{ t('ছবি তুলুন') }}
            </button>
            <button
                type="button"
                class="flex h-11 items-center justify-center gap-2 rounded-lg border border-dashed border-input bg-card px-3 text-sm font-medium transition-colors hover:bg-accent active:bg-accent disabled:opacity-50"
                :disabled="disabled"
                @click="picker?.click()"
            >
                <Paperclip class="size-4 text-muted-foreground" />
                {{ t('ফাইল / গ্যালারি') }}
            </button>
        </div>
        <input
            ref="camera"
            type="file"
            accept="image/*"
            capture="environment"
            class="hidden"
            @change="take"
        />
        <input
            ref="picker"
            type="file"
            :accept="accept"
            :multiple="multiple"
            class="hidden"
            @change="take"
        />

        <ul v-if="list && files.length" class="space-y-1">
            <li
                v-for="(file, i) in files"
                :key="`${file.name}-${i}`"
                class="flex items-center gap-2 rounded-md bg-muted/50 px-2.5 py-1.5 text-xs"
            >
                <Paperclip class="size-3.5 shrink-0 text-muted-foreground" />
                <span class="min-w-0 flex-1 truncate py-px leading-normal">{{
                    file.name
                }}</span>
                <button
                    type="button"
                    class="btn-icon size-7"
                    :aria-label="t('বাদ দিন')"
                    :title="t('বাদ দিন')"
                    @click="remove(i)"
                >
                    <X />
                </button>
            </li>
        </ul>
    </div>
</template>
