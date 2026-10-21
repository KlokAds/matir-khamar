<script setup lang="ts">
import { Head, Link, router, useForm } from '@inertiajs/vue3';
import {
    CalendarClock,
    Check,
    ClipboardList,
    History,
    Syringe,
    Trash2,
} from '@lucide/vue';
import { computed, ref, watch } from 'vue';
import DateField from '@/components/khamar/DateField.vue';
import Field from '@/components/khamar/Field.vue';
import NSelect from '@/components/khamar/NSelect.vue';
import PageTitle from '@/components/khamar/PageTitle.vue';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { ask } from '@/lib/dialog';
import { t, tAll } from '@/lib/i18n';
import { centerLabel, day, today, useCan } from '@/lib/khamar';
import type { Center } from '@/lib/khamar';

defineOptions({
    layout: { breadcrumbs: [{ title: 'টিকা ও কৃমি', href: '/health' }] },
});

type Due = {
    kind: string;
    title: string;
    detail: string;
    href: string;
    when: string;
    urgent: boolean;
};

type HistoryRow = {
    id: number;
    kind: string;
    kind_label: string;
    name: string;
    target: string;
    animal_id: number | null;
    given_on: string;
    next_due_on: string | null;
    dose: string | null;
    by_whom: string | null;
    notes: string | null;
    deletable: boolean;
};

type Preset = { kind: string; name: string; months: number | null };
type AnimalRow = {
    id: number;
    tag: string;
    name: string | null;
    cost_center_id: number;
};
type Target = 'animals' | 'center' | 'farm';

const props = defineProps<{
    due: Due[];
    history: HistoryRow[];
    kinds: Record<string, string>;
    presets: Preset[];
    centers: Center[];
    animals: AnimalRow[];
}>();

const can = useCan();
const canWrite = computed(() => !!can.value['post-daily']);

/** Adds whole months to a Y-m-d date on the local calendar; the day is clamped to the month's end. */
function addMonths(ymd: string, n: number): string {
    const [y, m, d] = ymd.slice(0, 10).split('-').map(Number);
    const total = y * 12 + (m - 1) + n;
    const ny = Math.floor(total / 12);
    const nm = (total % 12) + 1;
    const last = new Date(ny, nm, 0).getDate();
    const nd = Math.min(d, last);

    return `${ny}-${String(nm).padStart(2, '0')}-${String(nd).padStart(2, '0')}`;
}

const kindTone: Record<string, string> = {
    vaccine: 'bg-paddy/12 text-paddy',
    deworm: 'bg-turmeric/25 text-soil',
    treatment: 'bg-clay/15 text-clay',
    checkup: 'bg-pond/12 text-pond',
};
const toneFor = (kind: string) => kindTone[kind] ?? 'bg-muted text-foreground';

const form = useForm({
    target: 'animals' as Target,
    animal_ids: [] as number[],
    cost_center_id: null as number | null,
    kind: 'vaccine',
    name: '',
    given_on: today(),
    next_due_on: null as string | null,
    dose: '',
    by_whom: '',
    notes: '',
});

const activePreset = ref<Preset | null>(null);

function pickPreset(p: Preset) {
    activePreset.value = p;
    form.kind = p.kind;
    form.name = p.name;
    form.next_due_on = p.months ? addMonths(form.given_on, p.months) : null;
}

watch(
    () => form.given_on,
    (given) => {
        const p = activePreset.value;

        if (given && p?.months) {
            form.next_due_on = addMonths(given, p.months);
        }
    },
);

// Typing a different name drops the preset so its date rule stops applying.
watch(
    () => form.name,
    (name) => {
        if (activePreset.value && name !== activePreset.value.name) {
            activePreset.value = null;
        }
    },
);

const targets: { key: Target; label: string }[] = [
    { key: 'animals', label: t('কয়েকটি গরু') },
    { key: 'center', label: t('পুরো ক্যাটাগরি') },
    { key: 'farm', label: t('পুরো খামার') },
];

const groups = computed(() =>
    props.centers
        .map((c) => ({
            center: c,
            animals: props.animals.filter((a) => a.cost_center_id === c.id),
        }))
        .filter((g) => g.animals.length),
);

const others = computed(() => {
    const known = new Set(props.centers.map((c) => c.id));

    return props.animals.filter((a) => !known.has(a.cost_center_id));
});

const allGroups = computed(() =>
    others.value.length
        ? [
              ...groups.value,
              {
                  center: { id: 0, name: t('অন্যান্য') } as Center,
                  animals: others.value,
              },
          ]
        : groups.value,
);

const selected = computed(() => new Set(form.animal_ids));

function toggleAnimal(id: number) {
    form.animal_ids = selected.value.has(id)
        ? form.animal_ids.filter((x) => x !== id)
        : [...form.animal_ids, id];
}

const allPicked = (list: AnimalRow[]) =>
    list.length > 0 && list.every((a) => selected.value.has(a.id));

function toggleGroup(list: AnimalRow[]) {
    const ids = list.map((a) => a.id);

    form.animal_ids = allPicked(list)
        ? form.animal_ids.filter((x) => !ids.includes(x))
        : [...new Set([...form.animal_ids, ...ids])];
}

const animalLabel = (a: { tag: string; name: string | null }) =>
    [a.tag, a.name].filter(Boolean).join(' · ');

const canSubmit = computed(
    () =>
        !form.processing &&
        form.name.trim() !== '' &&
        (form.target !== 'animals' || form.animal_ids.length > 0) &&
        (form.target !== 'center' || form.cost_center_id !== null),
);

function submit() {
    form.transform((data) => ({
        ...data,
        animal_ids: data.target === 'animals' ? data.animal_ids : [],
        cost_center_id: data.target === 'center' ? data.cost_center_id : null,
        next_due_on: data.next_due_on || null,
    })).post('/health', {
        preserveScroll: true,
        onSuccess: () => {
            form.reset('name', 'dose', 'notes', 'animal_ids');
            activePreset.value = null;
        },
    });
}

// The server lets a row be removed only within a day of writing it, and says which ones.
const deletable = computed(
    () => new Set(props.history.filter((r) => r.deletable).map((r) => r.id)),
);

const removing = ref<number | null>(null);

async function remove(row: HistoryRow) {
    if (
        !(await ask.confirm({
            title: t('লেখাটি মুছবেন?'),
            body: t(':name — :target (:date)। শুধু আজকের লেখা মোছা যায়।', {
                name: t(row.name),
                target: t(row.target),
                date: day(row.given_on),
            }),
            confirmText: t('হ্যাঁ, মুছুন'),
            danger: true,
        }))
    ) {
        return;
    }

    removing.value = row.id;
    router.delete(`/health/${row.id}`, {
        preserveScroll: true,
        onError: () => {},
        onFinish: () => (removing.value = null),
    });
}
</script>

<template>
    <Head :title="t('টিকা ও কৃমি')" />

    <div class="flex flex-1 flex-col p-4 md:p-6">
        <PageTitle
            :title="t('টিকা ও কৃমি')"
            :subtitle="
                t(
                    'কোন গরুকে কবে কী দেওয়া হলো, পরেরটা কবে — সময় হলে নিজেই মনে করিয়ে দেবে।',
                )
            "
        />

        <div class="grid gap-6 lg:grid-cols-5">
            <div class="min-w-0 space-y-6 lg:col-span-2">
                <!-- Coming up -->
                <section class="rounded-xl border border-border bg-card p-4">
                    <h2
                        class="mb-3 flex items-center gap-2 font-display text-lg"
                    >
                        <CalendarClock class="size-5 text-clay" />
                        {{ t('সামনে যা আছে') }}
                    </h2>

                    <ul v-if="due.length" class="divide-y divide-border/70">
                        <li
                            v-for="(d, i) in due"
                            :key="`${d.href}-${d.when}-${i}`"
                        >
                            <Link
                                :href="d.href"
                                class="-mx-2 flex items-start justify-between gap-3 rounded-md px-2 py-2.5 hover:bg-accent"
                            >
                                <span class="min-w-0">
                                    <span
                                        :class="[
                                            'block truncate text-sm font-medium',
                                            d.urgent && 'text-destructive',
                                        ]"
                                        >{{ t(d.title) }}</span
                                    >
                                    <span
                                        class="block truncate text-xs text-muted-foreground"
                                        >{{ t(d.detail) }}</span
                                    >
                                </span>
                                <span
                                    :class="[
                                        'num shrink-0 rounded-full px-2.5 py-1 text-xs font-medium',
                                        d.urgent
                                            ? 'bg-destructive/10 text-destructive'
                                            : 'bg-pond/10 text-pond',
                                    ]"
                                    >{{ day(d.when) }}</span
                                >
                            </Link>
                        </li>
                    </ul>
                    <p
                        v-else
                        class="flex items-center justify-center gap-2 py-8 text-center text-sm text-muted-foreground"
                    >
                        <Check class="size-4 text-paddy" />
                        {{ t('৩০ দিনের মধ্যে কিছু বাকি নেই') }}
                    </p>
                </section>

                <!-- New entry -->
                <form
                    v-if="canWrite"
                    class="rounded-xl border border-border bg-card p-4"
                    @submit.prevent="submit"
                >
                    <h2
                        class="mb-3 flex items-center gap-2 font-display text-lg"
                    >
                        <Syringe class="size-5 text-paddy" />
                        {{ t('নতুন লিখুন') }}
                    </h2>

                    <div class="grid gap-3">
                        <div class="flex flex-wrap gap-1.5">
                            <button
                                v-for="p in presets"
                                :key="`${p.kind}-${p.name}`"
                                type="button"
                                :class="[
                                    'rounded-full border px-3 py-1.5 text-xs transition-colors',
                                    activePreset?.name === p.name &&
                                    activePreset?.kind === p.kind
                                        ? 'border-primary bg-primary text-primary-foreground'
                                        : 'border-input bg-card hover:bg-accent',
                                ]"
                                @click="pickPreset(p)"
                            >
                                {{ t(p.name) }}
                                <span v-if="p.months" class="num opacity-70"
                                    >· {{ t(':n মাস', { n: p.months }) }}</span
                                >
                            </button>
                        </div>

                        <div class="grid grid-cols-2 gap-3">
                            <Field :label="t('ধরন')" :error="form.errors.kind">
                                <NSelect v-model="form.kind">
                                    <option
                                        v-for="(label, key) in tAll(kinds)"
                                        :key="key"
                                        :value="key"
                                    >
                                        {{ label }}
                                    </option>
                                </NSelect>
                            </Field>
                            <Field :label="t('নাম')" :error="form.errors.name">
                                <Input
                                    v-model="form.name"
                                    class="h-10"
                                    :placeholder="t('যেমন: ক্ষুরা রোগ')"
                                    required
                                />
                            </Field>
                        </div>

                        <Field
                            :label="t('কাকে')"
                            :error="
                                form.errors.target ||
                                form.errors.animal_ids ||
                                form.errors.cost_center_id
                            "
                        >
                            <div
                                class="grid grid-cols-3 gap-1 rounded-lg bg-muted p-1"
                            >
                                <button
                                    v-for="tg in targets"
                                    :key="tg.key"
                                    type="button"
                                    :class="[
                                        'h-9 rounded-md text-xs font-medium transition-colors sm:text-sm',
                                        form.target === tg.key
                                            ? 'bg-card text-foreground shadow-sm'
                                            : 'text-muted-foreground hover:text-foreground',
                                    ]"
                                    @click="form.target = tg.key"
                                >
                                    {{ tg.label }}
                                </button>
                            </div>
                        </Field>

                        <div
                            v-if="form.target === 'animals'"
                            class="rounded-lg border border-border bg-background/60"
                        >
                            <p
                                class="flex items-center justify-between border-b border-border px-3 py-2 text-xs text-muted-foreground"
                            >
                                <span
                                    >{{ t('বাছাই:') }}
                                    <span
                                        class="num font-medium text-foreground"
                                        >{{
                                            t(':nটি', {
                                                n: form.animal_ids.length,
                                            })
                                        }}</span
                                    ></span
                                >
                                <button
                                    v-if="form.animal_ids.length"
                                    type="button"
                                    class="underline-offset-4 hover:underline"
                                    @click="form.animal_ids = []"
                                >
                                    {{ t('সব বাদ') }}
                                </button>
                            </p>
                            <div
                                v-if="allGroups.length"
                                class="max-h-72 overflow-y-auto px-3 py-2"
                            >
                                <div
                                    v-for="g in allGroups"
                                    :key="g.center.id"
                                    class="py-1.5"
                                >
                                    <div
                                        class="mb-1 flex items-center justify-between gap-2"
                                    >
                                        <span
                                            class="truncate text-xs font-semibold text-muted-foreground"
                                            >{{ t(g.center.name) }}
                                            <span class="num"
                                                >({{ g.animals.length }})</span
                                            ></span
                                        >
                                        <button
                                            type="button"
                                            class="shrink-0 text-xs font-medium text-paddy hover:underline"
                                            @click="toggleGroup(g.animals)"
                                        >
                                            {{
                                                allPicked(g.animals)
                                                    ? t('সব বাদ দিন')
                                                    : t('সব বাছুন')
                                            }}
                                        </button>
                                    </div>
                                    <div class="grid grid-cols-2 gap-1">
                                        <label
                                            v-for="a in g.animals"
                                            :key="a.id"
                                            :class="[
                                                'flex min-w-0 cursor-pointer items-center gap-2 rounded-md border px-2 py-1.5 text-sm',
                                                selected.has(a.id)
                                                    ? 'border-paddy/50 bg-paddy/8'
                                                    : 'border-transparent hover:bg-accent',
                                            ]"
                                        >
                                            <input
                                                type="checkbox"
                                                class="size-4 shrink-0 accent-paddy"
                                                :checked="selected.has(a.id)"
                                                @change="toggleAnimal(a.id)"
                                            />
                                            <span class="truncate">{{
                                                animalLabel(a)
                                            }}</span>
                                        </label>
                                    </div>
                                </div>
                            </div>
                            <p
                                v-else
                                class="px-3 py-6 text-center text-sm text-muted-foreground"
                            >
                                {{ t('কোনো সক্রিয় গরু নেই।') }}
                            </p>
                        </div>

                        <Field
                            v-else-if="form.target === 'center'"
                            :label="t('ক্যাটাগরি')"
                        >
                            <NSelect
                                v-model="form.cost_center_id"
                                :placeholder="t('ক্যাটাগরি বাছুন')"
                            >
                                <option
                                    v-for="c in centers"
                                    :key="c.id"
                                    :value="c.id"
                                    :disabled="!c.is_active"
                                >
                                    {{ centerLabel({ ...c, name: t(c.name) }) }}
                                </option>
                            </NSelect>
                        </Field>

                        <p
                            v-else
                            class="rounded-lg bg-pond/8 px-3 py-2 text-xs text-muted-foreground"
                        >
                            {{ t('খামারের সব গরুর জন্য একটি লেখা হবে।') }}
                        </p>

                        <div class="grid grid-cols-2 gap-3">
                            <Field
                                :label="t('দেওয়ার তারিখ')"
                                :error="form.errors.given_on"
                            >
                                <DateField
                                    v-model="form.given_on"
                                    :max="today()"
                                    class="h-10"
                                    required
                                />
                            </Field>
                            <Field
                                :label="t('পরেরটা কবে')"
                                :error="form.errors.next_due_on"
                                :hint="t('ফাঁকা রাখলে মনে করাবে না')"
                            >
                                <DateField
                                    v-model="form.next_due_on"
                                    :min="form.given_on"
                                    class="h-10"
                                    clearable
                                />
                            </Field>
                        </div>

                        <div class="grid grid-cols-2 gap-3">
                            <Field
                                :label="t('মাত্রা')"
                                :error="form.errors.dose"
                            >
                                <Input
                                    v-model="form.dose"
                                    class="h-10"
                                    :placeholder="t('যেমন: ২ মিলি')"
                                />
                            </Field>
                            <Field
                                :label="t('কে দিয়েছেন')"
                                :error="form.errors.by_whom"
                            >
                                <Input
                                    v-model="form.by_whom"
                                    class="h-10"
                                    :placeholder="
                                        t('যেমন: উপজেলা প্রাণিসম্পদ অফিস')
                                    "
                                />
                            </Field>
                        </div>

                        <Field
                            :label="t('নোট (ইচ্ছামতো)')"
                            :error="form.errors.notes"
                        >
                            <Input v-model="form.notes" class="h-10" />
                        </Field>

                        <Button type="submit" :disabled="!canSubmit">
                            <Check class="size-4" />
                            {{ t('লিখুন') }}
                        </Button>
                    </div>
                </form>
            </div>

            <!-- History -->
            <section
                class="min-w-0 rounded-xl border border-border bg-card p-4 lg:col-span-3"
            >
                <h2 class="mb-3 flex items-center gap-2 font-display text-lg">
                    <History class="size-5 text-pond" /> {{ t('আগের লেখা') }}
                </h2>

                <ul v-if="history.length" class="divide-y divide-border/70">
                    <li
                        v-for="r in history"
                        :key="r.id"
                        class="flex items-start gap-3 py-3"
                    >
                        <span
                            :class="[
                                'mt-0.5 shrink-0 rounded-full px-2 py-0.5 text-[11px] font-medium',
                                toneFor(r.kind),
                            ]"
                            >{{ t(r.kind_label) }}</span
                        >
                        <div class="min-w-0 flex-1">
                            <p class="text-sm leading-snug font-medium">
                                {{ t(r.name) }}
                            </p>
                            <p class="text-xs text-foreground/80">
                                <Link
                                    v-if="r.animal_id"
                                    :href="`/animals/${r.animal_id}`"
                                    class="hover:underline"
                                    >{{ t(r.target) }}</Link
                                >
                                <template v-else>{{ t(r.target) }}</template>
                                · <span class="num">{{ day(r.given_on) }}</span>
                            </p>
                            <p
                                v-if="r.next_due_on"
                                class="mt-0.5 text-xs text-pond"
                            >
                                {{ t('পরেরটা:') }}
                                <span class="num font-medium">{{
                                    day(r.next_due_on)
                                }}</span>
                            </p>
                            <p
                                v-if="r.dose || r.by_whom || r.notes"
                                class="mt-0.5 text-xs text-muted-foreground"
                            >
                                <template v-if="r.dose">{{
                                    t('মাত্রা: :dose', { dose: r.dose })
                                }}</template>
                                <template v-if="r.dose && r.by_whom">
                                    ·
                                </template>
                                <template v-if="r.by_whom">{{
                                    r.by_whom
                                }}</template>
                                <template v-if="r.notes">
                                    <template v-if="r.dose || r.by_whom">
                                        ·
                                    </template>
                                    {{ r.notes }}</template
                                >
                            </p>
                        </div>
                        <button
                            v-if="canWrite && deletable.has(r.id)"
                            type="button"
                            class="shrink-0 rounded-md p-1.5 text-muted-foreground hover:bg-destructive/10 hover:text-destructive disabled:opacity-50"
                            :disabled="removing === r.id"
                            :aria-label="t('মুছুন')"
                            @click="remove(r)"
                        >
                            <Trash2 class="size-4" />
                        </button>
                    </li>
                </ul>
                <p
                    v-else
                    class="flex flex-col items-center gap-2 py-10 text-center text-sm text-muted-foreground"
                >
                    <ClipboardList class="size-6 opacity-60" />
                    {{ t('এখনো কিছু লেখা হয়নি।') }}
                </p>
            </section>
        </div>

        <p class="mt-6 px-1 text-xs text-muted-foreground">
            {{
                t(
                    'ঔষধ, মাত্রা ও টিকার তারিখ অবশ্যই প্রাণিসম্পদ ডাক্তারের পরামর্শে।',
                )
            }}
        </p>
    </div>
</template>
