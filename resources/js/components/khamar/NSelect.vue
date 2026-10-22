<script setup lang="ts">
import { router } from '@inertiajs/vue3';
import {
    Check,
    ChevronDown,
    Pencil,
    Plus,
    Search,
    Trash2,
    X,
} from '@lucide/vue';
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
import { toast } from 'vue-sonner';
import { ask } from '@/lib/dialog';
import { t } from '@/lib/i18n';
import { useCan } from '@/lib/khamar';
import type { Abilities } from '@/lib/khamar';
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
        /**
         * What the choices are (account, money, center, party, item, worker): turns on rename
         * and remove beside each choice and "add new" at the bottom, for those allowed to.
         */
        manage?: 'account' | 'money' | 'center' | 'party' | 'item' | 'worker';
        /** Sent with a new one, e.g. { group: '5000' } for an expense head. */
        manageExtra?: Record<string, string>;
    }>(),
    {
        placeholder: '',
        disabled: false,
        manage: undefined,
        manageExtra: undefined,
    },
);

type Value = string | number | null | undefined;
type Choice = {
    key: number;
    value: Value;
    label: string;
    disabled: boolean;
    depth: number;
    parent: boolean;
    /** The app's own (data-locked on the option): it can be renamed, not removed. */
    locked: boolean;
    /** The plain name (data-name), when the label carries more, like stock on hand. */
    name: string;
};

const NBSP = '\u00a0';

/**
 * centerLabel() writes the tree as text ("   └ বকনা") so a plain <option> can carry it.
 * Here that text is turned back into a level, and drawn with guide lines instead.
 */
function splitTree(text: string): { depth: number; label: string } {
    let indent = 0;

    while (text[indent] === NBSP) {
        indent++;
    }

    let label = text.slice(indent);
    const branch = label.startsWith('└ ');

    if (branch) {
        label = label.slice(2);
    }

    return {
        depth: branch ? Math.max(1, Math.round(indent / 3)) : 0,
        label,
    };
}

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
            const tree = splitTree(
                textOf(node.children)
                    .replace(/[ \t\r\n]+/g, ' ')
                    .replace(/^ | $/g, ''),
            );
            out.push({
                key: out.length,
                // :value="null" must stay null (the "none" choice), not fall back to the text.
                value:
                    'value' in attrs
                        ? (attrs.value as Value)
                        : textOf(node.children).trim(),
                // Only plain spaces are squeezed: centerLabel indents with no-break spaces.
                label: tree.label,
                depth: tree.depth,
                parent: false,
                locked: !!attrs['data-locked'],
                name: String(attrs['data-name'] ?? tree.label),
                disabled: attrs.disabled === '' || attrs.disabled === true,
            });
        }
    }

    // A row is a parent when the next one sits a level deeper.
    out.forEach((choice, index) => {
        choice.parent = (out[index + 1]?.depth ?? 0) > choice.depth;
    });

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
const isTree = computed(() => choices.value.some((choice) => choice.depth > 0));

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

/* ---------- add, rename, remove from the list itself ---------- */
const NEEDS: Record<string, keyof Abilities> = {
    account: 'manage-setup',
    money: 'manage-setup',
    center: 'manage-setup',
    party: 'post-entries',
    item: 'post-entries',
    worker: 'post-entries',
};
const can = useCan();
const canManage = computed(
    () => !!props.manage && !!can.value[NEEDS[props.manage]],
);
const busy = ref(false);
const manageable = (choice: Choice) =>
    canManage.value &&
    !choice.disabled &&
    choice.value !== null &&
    choice.value !== undefined &&
    choice.value !== '' &&
    !Number.isNaN(Number(choice.value));

/** A refusal from the server (e.g. "already exists") comes back as a toast. */
const showError = (errors: Record<string, string>) =>
    toast.error(Object.values(errors)[0] ?? t('হয়নি, আবার চেষ্টা করুন।'));

/*
 * Names are typed in the app's own small dialog, not inside the open list: on a phone the
 * keyboard can close a list, and a tap meant for it would land on the page underneath.
 * A name already typed in the search box is added in one tap.
 */
async function startAdd() {
    let name = query.value.trim();
    open.value = false;

    if (!name) {
        name =
            (
                await ask.prompt({
                    title: t('নতুন যোগ করুন'),
                    label: t('নাম'),
                    confirmText: t('যোগ করুন'),
                })
            )?.trim() ?? '';
    }

    if (!name || busy.value) {
        return;
    }

    const before = new Set(choices.value.map((c) => String(c.value)));
    busy.value = true;
    router.post(
        `/manage/${props.manage}`,
        { name, extra: props.manageExtra ?? {} },
        {
            preserveScroll: true,
            preserveState: true,
            onSuccess: async () => {
                await nextTick();
                // The new choice is the one that was not there before: pick it.
                const added = choices.value.find(
                    (c) => !c.disabled && !before.has(String(c.value)),
                );

                if (added) {
                    model.value = added.value;
                }
            },
            onError: showError,
            onFinish: () => (busy.value = false),
        },
    );
}

async function startEdit(choice: Choice) {
    open.value = false;
    const name = (
        await ask.prompt({
            title: t('নাম বদলান'),
            label: t('নতুন নাম'),
            initial: choice.name,
            confirmText: t('রাখুন'),
        })
    )?.trim();

    if (!name || name === choice.name || busy.value) {
        return;
    }

    busy.value = true;
    router.put(
        `/manage/${props.manage}/${choice.value}`,
        { name },
        {
            preserveScroll: true,
            preserveState: true,
            onError: showError,
            onFinish: () => (busy.value = false),
        },
    );
}

async function remove(choice: Choice) {
    open.value = false;
    const ok = await ask.confirm({
        title: t('":name" মুছবেন?', { name: choice.name }),
        body: t(
            'কোনো হিসাবে ব্যবহার না হলে পুরো মুছে যাবে; হিসাবে থাকলে শুধু তালিকা থেকে লুকানো হবে — পুরোনো হিসাব ঠিক থাকবে।',
        ),
        confirmText: t('মুছুন'),
        danger: true,
    });

    if (!ok) {
        return;
    }

    router.delete(`/manage/${props.manage}/${choice.value}`, {
        preserveScroll: true,
        preserveState: true,
        onSuccess: () => {
            if (String(model.value) === String(choice.value)) {
                model.value = null;
            }
        },
        onError: showError,
    });
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
                    <template
                        v-for="(choice, index) in shown"
                        :key="choice.key"
                    >
                        <div class="flex items-center gap-0.5">
                            <button
                                type="button"
                                role="option"
                                :data-index="index"
                                :aria-selected="choice === selected"
                                :disabled="choice.disabled"
                                :class="[
                                    'flex min-h-10 w-full min-w-0 flex-1 items-center gap-2 rounded-md px-2.5 py-1.5 text-left text-[15px] leading-snug',
                                    isTree && !query && 'min-h-9 py-1',
                                    choice.disabled
                                        ? isTree
                                            ? 'cursor-default text-[13px] font-semibold text-muted-foreground'
                                            : 'cursor-default text-xs text-muted-foreground'
                                        : 'cursor-pointer',
                                    index === active &&
                                        !choice.disabled &&
                                        'bg-accent',
                                    choice === selected &&
                                        'font-medium text-primary',
                                ]"
                                @mousemove="
                                    !choice.disabled && (active = index)
                                "
                                @click="pick(choice)"
                            >
                                <template v-if="isTree && !query">
                                    <span
                                        v-for="level in choice.depth"
                                        :key="level"
                                        aria-hidden="true"
                                        class="relative w-3 shrink-0 self-stretch before:absolute before:-inset-y-1.5 before:left-1 before:border-l before:border-muted-foreground/25"
                                    />
                                    <span
                                        v-if="!choice.disabled"
                                        aria-hidden="true"
                                        :class="[
                                            'shrink-0 rounded-full',
                                            choice.parent
                                                ? 'size-2 bg-paddy'
                                                : 'size-1.5 bg-muted-foreground/50',
                                        ]"
                                    />
                                </template>
                                <span
                                    :class="[
                                        'min-w-0 flex-1',
                                        isTree &&
                                            choice.parent &&
                                            'font-semibold',
                                        isTree &&
                                            choice.depth === 0 &&
                                            'text-foreground',
                                    ]"
                                    >{{ choice.label || '—' }}</span
                                >
                                <Check
                                    v-if="choice === selected"
                                    class="size-4 shrink-0"
                                />
                            </button>
                            <template v-if="manageable(choice)">
                                <button
                                    type="button"
                                    class="row-act"
                                    :aria-label="t('নাম বদলান')"
                                    :title="t('নাম বদলান')"
                                    @click.stop="startEdit(choice)"
                                >
                                    <Pencil />
                                </button>
                                <button
                                    v-if="!choice.locked"
                                    type="button"
                                    class="row-act hover:text-destructive"
                                    :aria-label="t('মুছুন')"
                                    :title="t('মুছুন')"
                                    @click.stop="remove(choice)"
                                >
                                    <Trash2 />
                                </button>
                            </template>
                        </div>
                    </template>
                    <p
                        v-if="shown.length === 0"
                        class="px-2.5 py-3 text-sm text-muted-foreground"
                    >
                        {{ t('কিছু মেলেনি') }}
                    </p>
                </div>

                <div v-if="canManage" class="border-t border-border p-1.5">
                    <button
                        type="button"
                        class="flex h-9 w-full items-center gap-2 rounded-md px-2.5 text-sm font-medium text-primary hover:bg-primary/10"
                        @click="startAdd"
                    >
                        <Plus class="size-4" />
                        {{
                            query.trim()
                                ? t('":name" যোগ করুন', { name: query.trim() })
                                : t('নতুন যোগ করুন')
                        }}
                    </button>
                </div>
            </PopoverContent>
        </PopoverPortal>
    </PopoverRoot>
</template>

<style scoped>
@reference '../../../css/app.css';

.row-act {
    @apply flex size-8 shrink-0 items-center justify-center rounded-md text-muted-foreground transition-colors hover:bg-accent hover:text-foreground [&_svg]:size-3.5;
}
</style>
