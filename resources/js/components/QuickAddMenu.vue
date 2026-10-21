<script setup lang="ts">
import { Link } from '@inertiajs/vue3';
import {
    PopoverContent,
    PopoverPortal,
    PopoverRoot,
    PopoverTrigger,
} from 'reka-ui';
import { computed, ref } from 'vue';
import { t } from '@/lib/i18n';
import { useCan } from '@/lib/khamar';
import { quickActions } from '@/lib/quick';

/** The ＋ button: every everyday "write something" job, one tap away. */
withDefaults(defineProps<{ side?: 'top' | 'bottom' }>(), { side: 'bottom' });

const can = useCan();
const open = ref(false);
const actions = computed(() =>
    quickActions.filter((a) => !a.ability || can.value[a.ability]),
);
</script>

<template>
    <PopoverRoot v-model:open="open">
        <PopoverTrigger as-child>
            <slot :open="open" />
        </PopoverTrigger>
        <PopoverPortal>
            <PopoverContent
                :side="side"
                align="end"
                :side-offset="8"
                :collision-padding="12"
                class="z-50 w-[min(22rem,calc(100vw-24px))] rounded-2xl border border-border bg-popover p-2 text-popover-foreground shadow-xl data-[state=open]:animate-in data-[state=open]:fade-in-0 data-[state=open]:zoom-in-95"
            >
                <p
                    class="px-2 pt-1 pb-2 text-xs font-medium text-muted-foreground"
                >
                    {{ t('কী লিখবেন?') }}
                </p>
                <div class="grid grid-cols-2 gap-1.5">
                    <Link
                        v-for="a in actions"
                        :key="a.href"
                        :href="a.href"
                        class="flex items-start gap-2.5 rounded-xl border border-border/70 bg-card p-2.5 transition-colors hover:border-ring/50 hover:bg-accent"
                        @click="open = false"
                    >
                        <component
                            :is="a.icon"
                            :class="['mt-0.5 size-5 shrink-0', a.tone]"
                        />
                        <span class="min-w-0">
                            <span
                                class="block text-sm leading-tight font-medium"
                                >{{ t(a.title) }}</span
                            >
                            <span
                                class="mt-0.5 block text-[11px] leading-tight text-muted-foreground"
                                >{{ t(a.hint) }}</span
                            >
                        </span>
                    </Link>
                </div>
            </PopoverContent>
        </PopoverPortal>
    </PopoverRoot>
</template>
