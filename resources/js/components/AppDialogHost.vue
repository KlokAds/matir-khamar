<script setup lang="ts">
import { CircleAlert } from '@lucide/vue';
import { computed, nextTick, ref, watch } from 'vue';
import { Button } from '@/components/ui/button';
import {
    Dialog,
    DialogContent,
    DialogDescription,
    DialogFooter,
    DialogHeader,
    DialogTitle,
} from '@/components/ui/dialog';
import { Input } from '@/components/ui/input';
import { answer, dialogState } from '@/lib/dialog';
import { t } from '@/lib/i18n';

/** Shows the confirm / prompt boxes asked for through lib/dialog. */
const current = computed(() => dialogState.current);
const value = ref('');
const input = ref<{ $el?: HTMLInputElement } | null>(null);

watch(current, async (request) => {
    value.value = request?.initial ?? '';

    if (request?.kind === 'prompt') {
        await nextTick();
        input.value?.$el?.focus();
    }
});

const tooShort = computed(
    () =>
        current.value?.kind === 'prompt' &&
        value.value.trim().length < (current.value.minLength ?? 1),
);

function submit() {
    if (!current.value || tooShort.value) {
        return;
    }

    answer(current.value.kind === 'confirm' ? true : value.value.trim());
}

function cancel() {
    if (current.value) {
        answer(current.value.kind === 'confirm' ? false : null);
    }
}
</script>

<template>
    <Dialog
        :open="current !== null"
        @update:open="(open: boolean) => !open && cancel()"
    >
        <DialogContent v-if="current" class="sm:max-w-md">
            <form class="grid gap-4" @submit.prevent="submit">
                <DialogHeader>
                    <DialogTitle
                        class="font-display flex items-center gap-2 text-xl"
                    >
                        <CircleAlert
                            v-if="current.danger"
                            class="size-5 text-destructive"
                        />
                        {{ current.title }}
                    </DialogTitle>
                    <DialogDescription v-if="current.body">{{
                        current.body
                    }}</DialogDescription>
                </DialogHeader>

                <label v-if="current.kind === 'prompt'" class="grid gap-1.5">
                    <span v-if="current.label" class="text-sm font-medium">{{
                        current.label
                    }}</span>
                    <Input
                        ref="input"
                        v-model="value"
                        :type="current.inputType ?? 'text'"
                        :placeholder="current.placeholder"
                        :autocomplete="
                            current.inputType === 'password'
                                ? 'new-password'
                                : 'off'
                        "
                    />
                    <span
                        v-if="current.minLength && current.minLength > 1"
                        class="text-xs text-muted-foreground"
                        >{{
                            t('কমপক্ষে :n অক্ষর', { n: current.minLength })
                        }}</span
                    >
                </label>

                <DialogFooter class="gap-2">
                    <Button type="button" variant="secondary" @click="cancel">{{
                        current.cancelText ?? t('বাদ দিন')
                    }}</Button>
                    <Button
                        type="submit"
                        :variant="current.danger ? 'destructive' : 'default'"
                        :disabled="tooShort"
                        >{{ current.confirmText ?? t('ঠিক আছে') }}</Button
                    >
                </DialogFooter>
            </form>
        </DialogContent>
    </Dialog>
</template>
