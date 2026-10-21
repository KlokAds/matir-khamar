<script setup lang="ts">
import { Check, ChevronDown, Search } from '@lucide/vue';
import {
    PopoverContent,
    PopoverPortal,
    PopoverRoot,
    PopoverTrigger,
} from 'reka-ui';
import {
    Comment,
    Fragment,
    computed,
    nextTick,
    ref,
    useAttrs,
    useSlots,
} from 'vue';
import type { VNode } from 'vue';
import { t } from '@/lib/i18n';
import { cn } from '@/lib/utils';

/**
 * The app's own dropdown. Pages keep writing plain <option> children (with v-for, v-if and
 * <template> as usual); this reads them and shows a styled list that matches the farm look,
 * with a search box once the list is long. Number and null values come back as they were.
 */
defineOptions({ inheritAttrs: false });

const props = withDefaults(
    defineProps<{
        placeholder?: string;
        disabled?: boolean;
    }>(),
    { placeholder: '', disabled: false },
);

type Value = string | number | null | undefined;
type Choice = { key: number; value: Value; label: string; disabled: boolean };

const model = defineModel<Value>();
const slots = useSlots();
const attrs = useAttrs();
// class goes through cn() so a page's "w-auto" replaces the default width instead of fighting it.
const passThrough = computed(() => {
    const { class: _class, ...rest } = attrs;

    return rest;
});
const open = ref(false);
const query = ref('');
const active = ref(-1);
const list = ref<HTMLElement | null>(null);
const search = ref<HTMLInputElement | null>(null);
const trigger = ref<{ $el?: unknown } | null>(null);

function textOf(children: unknown): string {
    if (typeof children === 'string' || typeof children === 'number') {
        return String(children);
    }

    if (Array.isArray(children)) {
        return children
            .map((child) => textOf((child as VNode)?.children ?? child))
            .join('');
    }

    return '';
}

function collect(nodes: VNode[] | undefined, out: Choice[]): Choice[] {
    for (const node of nodes ?? []) {
        if (node.type === Comment) {
            continue;
        }

        if (node.type === Fragment || node.type === 'template') {
            collect(node.children as VNode[], out);
        } else if (node.type === 'option') {
            const attrs = (node.props ?? {}) as Record<string, unknown>;
            out.push({
                key: out.length,
                // :value="null" must stay null (the "none" choice), not fall back to the text.
                value:
                    'value' in attrs
                        ? (attrs.value as Value)
                        : textOf(node.children).trim(),
                // Only plain spaces are squeezed: centerLabel indents with no-break spaces.
                label: textOf(node.children)
                    .replace(/[ \t\r\n]+/g, ' ')
                    .replace(/^ | $/g, ''),
                disabled: attrs.disabled === '' || attrs.disabled === true,
            });
        }
    }

    return out;
}

// Read on each render, so options that arrive later (v-for over props) show up.
const choices = computed(() => collect(slots.default?.(), []));

const selected = computed(() =>
    choices.value.find(
        (choice) =>
            choice.value === (model.value ?? null) ||
            // A form may hold "5" where the option says 5 (or the other way round).
            (choice.value !== null &&
                model.value !== null &&
                model.value !== undefined &&
                String(choice.value) === String(model.value)),
    ),
);

const searchable = computed(() => choices.value.length > 8);

const shown = computed(() => {
    const needle = query.value.trim().toLowerCase();

    return needle
        ? choices.value.filter((choice) =>
              choice.label.toLowerCase().includes(needle),
          )
        : choices.value;
});

async function onOpen(value: boolean) {
    open.value = value;

    if (!value) {
        return;
    }

    // Bring the box to the middle first, so the list opens downward with room to breathe.
    const box = trigger.value?.$el as HTMLElement | undefined;

    if (box && window.innerHeight - box.getBoundingClientRect().bottom < 300) {
        box.scrollIntoView({ block: 'center' });
    }

    query.value = '';
    active.value = Math.max(
        0,
        shown.value.findIndex((choice) => choice === selected.value),
    );
    await nextTick();
    (search.value ?? list.value)?.focus();
    scrollToActive();
}

function pick(choice: Choice) {
    if (choice.disabled) {
        return;
    }

    model.value = choice.value;
    open.value = false;
}

function scrollToActive() {
    list.value
        ?.querySelector(`[data-index="${active.value}"]`)
        ?.scrollIntoView({ block: 'nearest' });
}

function move(step: number) {
    const count = shown.value.length;

    if (count === 0) {
        return;
    }

    let next = active.value;

    for (let tries = 0; tries < count; tries++) {
        next = (next + step + count) % count;

        if (!shown.value[next].disabled) {
            break;
        }
    }

    active.value = next;
    scrollToActive();
}

function onKey(event: KeyboardEvent) {
    if (event.key === 'ArrowDown') {
        event.preventDefault();
        move(1);
    } else if (event.key === 'ArrowUp') {
        event.preventDefault();
        move(-1);
    } else if (event.key === 'Enter') {
        event.preventDefault();
        const choice = shown.value[active.value];

        if (choice) {
            pick(choice);
        }
    }
}

function onTriggerKey(event: KeyboardEvent) {
    if (!open.value && ['ArrowDown', 'ArrowUp'].includes(event.key)) {
        event.preventDefault();
        void onOpen(true);
    }
}
</script>

<template>
    <PopoverRoot :open="open" @update:open="onOpen">
        <PopoverTrigger ref="trigger" as-child>
            <button
                type="button"
                v-bind="passThrough"
                :disabled="props.disabled"
                :data-state="open ? 'open' : 'closed'"
                :class="
                    cn(
                        'flex h-10 w-full min-w-0 items-center justify-between gap-2 rounded-md border border-input bg-card px-3 text-left text-[15px] shadow-xs transition-[color,box-shadow,border-color] outline-none hover:border-ring/60 focus-visible:border-ring focus-visible:ring-[3px] focus-visible:ring-ring/40 disabled:cursor-not-allowed disabled:opacity-50 data-[state=open]:border-ring data-[state=open]:ring-[3px] data-[state=open]:ring-ring/25',
                        attrs.class as string,
                    )
                "
                @keydown="onTriggerKey"
            >
                <span
                    :class="['truncate', !selected && 'text-muted-foreground']"
                    >{{ selected?.label || placeholder || t('বাছুন') }}</span
                >
                <ChevronDown
                    :class="[
                        'size-4 shrink-0 text-muted-foreground transition-transform',
                        open && 'rotate-180',
                    ]"
                />
            </button>
        </PopoverTrigger>

        <PopoverPortal>
            <PopoverContent
                align="start"
                :side-offset="6"
                :collision-padding="12"
                class="z-50 w-(--reka-popover-trigger-width) min-w-52 overflow-hidden rounded-lg border border-border bg-popover text-popover-foreground shadow-lg data-[state=closed]:animate-out data-[state=closed]:fade-out-0 data-[state=open]:animate-in data-[state=open]:fade-in-0 data-[state=open]:zoom-in-95"
                @keydown="onKey"
                @open-auto-focus.prevent
            >
                <div
                    v-if="searchable"
                    class="flex items-center gap-2 border-b border-border px-3"
                >
                    <Search class="size-4 shrink-0 text-muted-foreground" />
                    <input
                        ref="search"
                        v-model="query"
                        type="text"
                        autocomplete="off"
                        :placeholder="t('খুঁজুন…')"
                        class="h-10 w-full bg-transparent text-[15px] outline-none placeholder:text-muted-foreground"
                        @input="active = 0"
                    />
                </div>

                <div
                    ref="list"
                    role="listbox"
                    :tabindex="searchable ? -1 : 0"
                    class="max-h-72 overflow-y-auto p-1 outline-none"
                >
                    <button
                        v-for="(choice, index) in shown"
                        :key="choice.key"
                        type="button"
                        role="option"
                        :data-index="index"
                        :aria-selected="choice === selected"
                        :disabled="choice.disabled"
                        :class="[
                            'flex min-h-10 w-full items-center gap-2 rounded-md px-2.5 py-1.5 text-left text-[15px] leading-snug',
                            choice.disabled
                                ? 'cursor-default text-xs text-muted-foreground'
                                : 'cursor-pointer',
                            index === active && !choice.disabled && 'bg-accent',
                            choice === selected && 'font-medium text-primary',
                        ]"
                        @mousemove="!choice.disabled && (active = index)"
                        @click="pick(choice)"
                    >
                        <span class="min-w-0 flex-1 whitespace-pre-wrap">{{
                            choice.label || '—'
                        }}</span>
                        <Check
                            v-if="choice === selected"
                            class="size-4 shrink-0"
                        />
                    </button>
                    <p
                        v-if="shown.length === 0"
                        class="px-2.5 py-3 text-sm text-muted-foreground"
                    >
                        {{ t('কিছু মেলেনি') }}
                    </p>
                </div>
            </PopoverContent>
        </PopoverPortal>
    </PopoverRoot>
</template>
