<script setup lang="ts">
import { Head, Link, router, useForm } from '@inertiajs/vue3';
import {
    CalendarClock,
    Check,
    FileText,
    Lightbulb,
    ListChecks,
    NotebookPen,
    Target,
    TriangleAlert,
    Trophy,
    X,
} from '@lucide/vue';
import {
    computed,
    onBeforeUnmount,
    onMounted,
    reactive,
    ref,
    watch,
} from 'vue';
import Field from '@/components/khamar/Field.vue';
import NSelect from '@/components/khamar/NSelect.vue';
import PageTitle from '@/components/khamar/PageTitle.vue';
import Toggle from '@/components/khamar/Toggle.vue';
import VoiceRecorder from '@/components/khamar/VoiceRecorder.vue';
import { Button } from '@/components/ui/button';
import DateField from '@/components/khamar/DateField.vue';
import { Input } from '@/components/ui/input';
import { t } from '@/lib/i18n';
import { centerLabel, day, qty, today, useCan } from '@/lib/khamar';
import type { Center } from '@/lib/khamar';

defineOptions({
    layout: { breadcrumbs: [{ title: 'খামার ডায়েরি', href: '/diary' }] },
});

type Note = {
    id: number;
    date: string;
    kind: string;
    title: string;
    body: string | null;
    center: string | null;
    animal: string | null;
    animal_id: number | null;
    follow_up_on: string | null;
    done: boolean;
    author: string | null;
    files: NoteFile[];
};

type NoteFile = {
    id: number;
    kind: 'image' | 'audio' | 'file';
    name: string;
    url: string;
};

type Goal = {
    id: number;
    title: string;
    unit: string | null;
    start_value: number;
    target_value: number;
    current_value: number;
    notes: string | null;
    cost_center_id: number | null;
    due_on: string | null;
    center: string | null;
    achieved: boolean;
    progress: number;
};

const props = defineProps<{
    kind: string | null;
    kinds: Record<string, string>;
    counts: Record<string, number>;
    notes: Note[];
    goals: Goal[];
    centers: Center[];
    animals: { id: number; tag: string; name: string | null }[];
    routine: { title: string; items: string[] }[];
}>();

const can = useCan();
const todayStr = today();

/* ---------- tabs ---------- */

type Tab = 'diary' | 'goals' | 'routine';
const tabs: { key: Tab; label: string; icon: typeof Target }[] = [
    { key: 'diary', label: t('ডায়েরি'), icon: NotebookPen },
    { key: 'goals', label: t('লক্ষ্য'), icon: Target },
    { key: 'routine', label: t('নিয়ম ও রুটিন'), icon: ListChecks },
];
const tab = ref<Tab>('diary');

onMounted(() => {
    const hash = window.location.hash.slice(1);

    if (tabs.some((item) => item.key === hash)) {
        tab.value = hash as Tab;
    }
});

watch(tab, (value) => {
    history.replaceState(history.state, '', `#${value}`);
});

/* ---------- diary ---------- */

const kindStyle: Record<string, { border: string; chip: string }> = {
    daily: {
        border: 'border-l-muted-foreground/40',
        chip: 'bg-muted text-muted-foreground',
    },
    mistake: {
        border: 'border-l-destructive',
        chip: 'bg-destructive/10 text-destructive',
    },
    lesson: { border: 'border-l-paddy', chip: 'bg-paddy/15 text-paddy' },
    idea: { border: 'border-l-pond', chip: 'bg-pond/15 text-pond' },
    health: { border: 'border-l-clay', chip: 'bg-clay/15 text-clay' },
};
const styleOf = (kind: string) => kindStyle[kind] ?? kindStyle.daily;

const note = useForm({
    date: todayStr,
    kind: 'daily',
    title: '',
    body: '',
    cost_center_id: null as number | null,
    animal_id: null as number | null,
    follow_up_on: '',
    files: [] as File[],
});

/* attachments: photos and voice notes */

const MAX_FILES = 5;
const tooMany = t('একটি লেখায় সর্বোচ্চ :maxটি ছবি বা ভয়েস রাখা যায়।', {
    max: MAX_FILES,
});
type Picked = { key: number; file: File; url: string; kind: 'image' | 'audio' };
const picked = ref<Picked[]>([]);
const fileMessage = ref('');
const recorderOpen = ref(false);
const photoInput = ref<HTMLInputElement | null>(null);
let pickKey = 0;

function syncFiles() {
    note.files = picked.value.map((p) => p.file);
}

function addFiles(files: File[]) {
    fileMessage.value = '';

    for (const file of files) {
        if (picked.value.length >= MAX_FILES) {
            fileMessage.value = tooMany;
            break;
        }

        picked.value.push({
            key: ++pickKey,
            file,
            url: URL.createObjectURL(file),
            kind: file.type.startsWith('image/') ? 'image' : 'audio',
        });
    }

    syncFiles();
}

function onPhotos(e: Event) {
    const input = e.target as HTMLInputElement;
    addFiles(Array.from(input.files ?? []));
    input.value = '';
}

function onRecorded(file: File) {
    addFiles([file]);
    recorderOpen.value = false;
}

function openRecorder() {
    if (picked.value.length >= MAX_FILES) {
        fileMessage.value = tooMany;

        return;
    }

    recorderOpen.value = true;
}

function openCamera() {
    if (picked.value.length >= MAX_FILES) {
        fileMessage.value = tooMany;

        return;
    }

    photoInput.value?.click();
}

function removePicked(key: number) {
    const item = picked.value.find((p) => p.key === key);

    if (item) {
        URL.revokeObjectURL(item.url);
    }

    picked.value = picked.value.filter((p) => p.key !== key);
    fileMessage.value = '';
    syncFiles();
}

function clearPicked() {
    picked.value.forEach((p) => URL.revokeObjectURL(p.url));
    picked.value = [];
    fileMessage.value = '';
    recorderOpen.value = false;
    syncFiles();
}

onBeforeUnmount(() => picked.value.forEach((p) => URL.revokeObjectURL(p.url)));

const fileErrors = computed(() =>
    Object.entries(note.errors as Record<string, string | undefined>)
        .filter(([key, msg]) => key.startsWith('files') && !!msg)
        .map(([, msg]) => msg as string),
);

const imagesOf = (n: Note) => (n.files ?? []).filter((f) => f.kind === 'image');
const othersOf = (n: Note) => (n.files ?? []).filter((f) => f.kind !== 'image');

function saveNote() {
    note.post('/diary/notes', {
        preserveScroll: true,
        forceFormData: true,
        onSuccess: () => {
            note.reset();
            note.date = today();
            clearPicked();
        },
    });
}

const totalCount = computed(() =>
    Object.values(props.counts).reduce((sum, n) => sum + n, 0),
);

function filter(kind: string | null) {
    router.get('/diary', kind ? { kind } : {}, {
        preserveState: true,
        preserveScroll: true,
    });
}

const grouped = computed(() => {
    const groups: { date: string; notes: Note[] }[] = [];

    for (const n of props.notes) {
        const last = groups[groups.length - 1];

        if (last && last.date === n.date.slice(0, 10)) {
            last.notes.push(n);
        } else {
            groups.push({ date: n.date.slice(0, 10), notes: [n] });
        }
    }

    return groups;
});

const isOverdue = (date: string | null, done = false) =>
    !!date && !done && date.slice(0, 10) < todayStr;

function toggleDone(n: Note) {
    router.put(
        `/diary/notes/${n.id}`,
        { done: !n.done },
        { preserveScroll: true },
    );
}

const animalLabel = (a: { tag: string; name: string | null }) =>
    `${a.tag}${a.name ? ' · ' + a.name : ''}`;

/* ---------- goals ---------- */

const suggestions = [
    { title: t('দিনে দুধ'), unit: t('লিটার'), start: '18', target: '30' },
    {
        title: t('মোটাতাজা দিনে বাড়া'),
        unit: t('কেজি'),
        start: '0.5',
        target: '0.8',
    },
    {
        title: t('খাবারের খরচ প্রতি লিটার দুধে'),
        unit: t('টাকা'),
        start: '45',
        target: '35',
    },
];

const goal = useForm({
    title: '',
    unit: '',
    start_value: '',
    target_value: '',
    current_value: '',
    due_on: '',
    cost_center_id: null as number | null,
    notes: '',
});

function prefill(s: (typeof suggestions)[number]) {
    goal.title = s.title;
    goal.unit = s.unit;
    goal.start_value = s.start;
    goal.target_value = s.target;
    goal.current_value = s.start;
}

function saveGoal() {
    goal.transform((data) => ({
        ...data,
        current_value:
            data.current_value === '' ? data.start_value : data.current_value,
    })).post('/diary/goals', {
        preserveScroll: true,
        onSuccess: () => goal.reset(),
    });
}

const nowValues = reactive<Record<number, string>>({});
const savingGoal = ref<number | null>(null);

watch(
    () => props.goals,
    (goals) => {
        for (const g of goals) {
            nowValues[g.id] = String(g.current_value);
        }
    },
    { immediate: true },
);

function updateCurrent(g: Goal) {
    const value = parseFloat(nowValues[g.id]);

    if (Number.isNaN(value)) {
        return;
    }

    savingGoal.value = g.id;
    router.put(
        `/diary/goals/${g.id}`,
        {
            title: g.title,
            unit: g.unit,
            start_value: g.start_value,
            target_value: g.target_value,
            current_value: value,
            due_on: g.due_on,
            cost_center_id: g.cost_center_id,
            notes: g.notes,
        },
        {
            preserveScroll: true,
            onFinish: () => (savingGoal.value = null),
        },
    );
}

const lessIsBetter = (g: Goal) => g.target_value < g.start_value;
const clampPct = (n: number) => Math.max(0, Math.min(100, Math.round(n)));

/* ---------- routine ---------- */

const tickKey = `khamar-routine-${todayStr}`;
const ticks = ref<Record<string, boolean>>({});

onMounted(() => {
    try {
        const saved = window.localStorage.getItem(tickKey);
        ticks.value = saved ? JSON.parse(saved) : {};
    } catch {
        ticks.value = {};
    }
});

function tick(id: string) {
    ticks.value = { ...ticks.value, [id]: !ticks.value[id] };

    try {
        window.localStorage.setItem(tickKey, JSON.stringify(ticks.value));
    } catch {
        // Ticks are only a convenience; nothing is lost if storage is off.
    }
}

const doneIn = (i: number, items: string[]) =>
    items.filter((_, j) => ticks.value[`${i}-${j}`]).length;

const rules = [
    t('সব লেনদেন সেদিনই, রসিদসহ লিখুন।'),
    t('প্রতিটি গরুর আলাদা কার্ড আর নিয়মিত ওজন।'),
    t('খাবার কেজিতে মেপে দিন, আন্দাজে নয়।'),
    t('মাসে একবার লাভ-ক্ষতি দেখে অন্তত একটি সিদ্ধান্ত নিন।'),
    t('ভুল লুকাবেন না — ডায়েরিতে লিখে রাখুন।'),
];
</script>

<template>
    <Head :title="t('খামার ডায়েরি')" />

    <div class="flex flex-1 flex-col p-4 md:p-6">
        <PageTitle
            :title="t('খামার ডায়েরি')"
            :subtitle="
                t('প্রতিদিনের কথা, ভুল, শিক্ষা আর লক্ষ্য — এক জায়গায়।')
            "
        />

        <!-- segmented tabs -->
        <div
            role="tablist"
            class="mb-5 grid grid-cols-3 gap-1 rounded-lg border border-border bg-card p-1 md:max-w-lg"
        >
            <button
                v-for="item in tabs"
                :key="item.key"
                type="button"
                role="tab"
                :aria-selected="tab === item.key"
                :class="[
                    'flex h-10 items-center justify-center gap-1.5 rounded-md px-2 text-sm font-medium transition-colors',
                    tab === item.key
                        ? 'bg-primary text-primary-foreground shadow-xs'
                        : 'text-muted-foreground hover:bg-accent',
                ]"
                @click="tab = item.key"
            >
                <component :is="item.icon" class="size-4 shrink-0" />
                <span class="truncate">{{ item.label }}</span>
            </button>
        </div>

        <!-- ================= ডায়েরি ================= -->
        <div v-if="tab === 'diary'" class="grid gap-6 lg:grid-cols-5">
            <div class="space-y-4 lg:col-span-2">
                <div
                    class="flex gap-3 rounded-xl border border-border bg-card p-4 text-sm leading-relaxed"
                >
                    <Lightbulb class="mt-0.5 size-5 shrink-0 text-turmeric" />
                    <div>
                        <p class="font-display text-base">
                            {{ t('কেন লিখবেন?') }}
                        </p>
                        <p class="mt-1 text-muted-foreground">
                            {{
                                t(
                                    'প্রতিদিনের ছোট কথা, ভুল আর শিক্ষা লিখে রাখলে প্রতি মাসে খামার একটু করে ভালো হয়। একই ভুল দুবার হয় না, আর ভালো চিন্তাগুলো হারিয়ে যায় না।',
                                )
                            }}
                        </p>
                    </div>
                </div>

                <form
                    v-if="can['post-daily']"
                    class="rounded-xl border border-border bg-card p-4"
                    @submit.prevent="saveNote"
                >
                    <h2 class="mb-3 font-display text-lg">
                        {{ t('আজ কী লিখবেন?') }}
                    </h2>

                    <Field
                        :label="t('কী ধরনের কথা')"
                        :error="note.errors.kind"
                        class="mb-3"
                    >
                        <NSelect v-model="note.kind">
                            <option
                                v-for="(label, key) in kinds"
                                :key="key"
                                :value="key"
                            >
                                {{ t(label) }}
                            </option>
                        </NSelect>
                    </Field>

                    <div class="grid gap-3">
                        <Field :label="t('তারিখ')" :error="note.errors.date">
                            <DateField
                                v-model="note.date"
                                :max="todayStr"
                                class="h-10"
                            />
                        </Field>
                        <Field :label="t('শিরোনাম')" :error="note.errors.title">
                            <Input
                                v-model="note.title"
                                required
                                maxlength="200"
                                class="h-10"
                                :placeholder="t('যেমন: লাল গাভীর খাওয়া কম')"
                            />
                        </Field>
                        <Field
                            :label="t('বিস্তারিত')"
                            :error="note.errors.body"
                        >
                            <textarea
                                v-model="note.body"
                                rows="4"
                                class="w-full rounded-md border border-input bg-card px-3 py-2 text-[15px] shadow-xs outline-none placeholder:text-muted-foreground focus-visible:border-ring focus-visible:ring-[3px] focus-visible:ring-ring/40"
                                :placeholder="
                                    t('কী হলো, কেন হলো, এরপর কী করবেন…')
                                "
                            />
                        </Field>
                        <div class="grid grid-cols-2 gap-3">
                            <Field
                                :label="t('বিভাগ')"
                                :error="note.errors.cost_center_id"
                            >
                                <NSelect
                                    v-model="note.cost_center_id"
                                    :placeholder="t('ঐচ্ছিক')"
                                >
                                    <option :value="null">—</option>
                                    <option
                                        v-for="c in centers"
                                        :key="c.id"
                                        :value="c.id"
                                        :disabled="c.kind === 'group'"
                                    >
                                        {{
                                            centerLabel({
                                                ...c,
                                                name: t(c.name),
                                            })
                                        }}
                                    </option>
                                </NSelect>
                            </Field>
                            <Field
                                :label="t('পশু')"
                                :error="note.errors.animal_id"
                            >
                                <NSelect
                                    v-model="note.animal_id"
                                    :placeholder="t('ঐচ্ছিক')"
                                >
                                    <option :value="null">—</option>
                                    <option
                                        v-for="a in animals"
                                        :key="a.id"
                                        :value="a.id"
                                    >
                                        {{ animalLabel(a) }}
                                    </option>
                                </NSelect>
                            </Field>
                        </div>
                        <Field
                            :label="t('কবে আবার দেখবেন')"
                            :hint="
                                t(
                                    'ঐচ্ছিক — দিন পার হলে লাল হয়ে মনে করিয়ে দেবে।',
                                )
                            "
                            :error="note.errors.follow_up_on"
                        >
                            <DateField
                                clearable
                                v-model="note.follow_up_on"
                                :min="note.date"
                                class="h-10"
                            />
                        </Field>

                        <div class="grid gap-2">
                            <div class="grid grid-cols-2 gap-2">
                                <Button
                                    type="button"
                                    variant="outline"
                                    class="h-11 text-base"
                                    @click="openCamera"
                                >
                                    📷 {{ t('ছবি') }}
                                </Button>
                                <Button
                                    type="button"
                                    variant="outline"
                                    class="h-11 text-base"
                                    :disabled="recorderOpen"
                                    @click="openRecorder"
                                >
                                    🎙 {{ t('ভয়েস নোট') }}
                                </Button>
                            </div>
                            <input
                                ref="photoInput"
                                type="file"
                                accept="image/*"
                                capture="environment"
                                multiple
                                class="hidden"
                                @change="onPhotos"
                            />

                            <VoiceRecorder
                                v-if="recorderOpen"
                                @recorded="onRecorded"
                                @close="recorderOpen = false"
                            />

                            <ul v-if="picked.length" class="grid gap-2">
                                <li
                                    v-for="p in picked"
                                    :key="p.key"
                                    class="flex items-center gap-2 rounded-lg border border-border bg-muted/40 p-1.5"
                                >
                                    <template v-if="p.kind === 'image'">
                                        <img
                                            :src="p.url"
                                            alt=""
                                            class="size-12 shrink-0 rounded-md object-cover"
                                        />
                                        <span
                                            class="min-w-0 flex-1 truncate text-xs text-muted-foreground"
                                            >{{ p.file.name }}</span
                                        >
                                    </template>
                                    <audio
                                        v-else
                                        controls
                                        :src="p.url"
                                        class="h-10 min-w-0 flex-1"
                                    />
                                    <button
                                        type="button"
                                        class="shrink-0 rounded-md p-2 text-muted-foreground hover:bg-accent hover:text-destructive"
                                        :aria-label="t('সরিয়ে দিন')"
                                        @click="removePicked(p.key)"
                                    >
                                        <X class="size-4" />
                                    </button>
                                </li>
                            </ul>

                            <p
                                v-if="fileMessage"
                                class="text-xs text-destructive"
                            >
                                {{ fileMessage }}
                            </p>
                            <p
                                v-for="(msg, i) in fileErrors"
                                :key="i"
                                class="text-xs text-destructive"
                            >
                                {{ msg }}
                            </p>
                        </div>
                    </div>

                    <Button
                        type="submit"
                        class="mt-4 h-11 w-full text-base"
                        :disabled="note.processing || !note.title.trim()"
                        >{{ t('লিখে রাখুন') }}</Button
                    >
                </form>
            </div>

            <section class="min-w-0 lg:col-span-3">
                <!-- Phone: one tidy dropdown. Wider screens: the chips fit in a row. -->
                <Field
                    :label="t('কোন ধরনের লেখা দেখবেন')"
                    class="mb-4 md:hidden"
                >
                    <NSelect
                        :model-value="kind ?? ''"
                        @update:model-value="
                            (v) => filter(v ? String(v) : null)
                        "
                    >
                        <option value="">
                            {{ t('সব (:n)', { n: totalCount }) }}
                        </option>
                        <option
                            v-for="(label, key) in kinds"
                            :key="key"
                            :value="key"
                        >
                            {{ t(label) }} ({{ counts[key] ?? 0 }})
                        </option>
                    </NSelect>
                </Field>
                <div class="mb-4 hidden md:block">
                    <div class="flex flex-wrap gap-1.5">
                        <button
                            type="button"
                            :class="[
                                'rounded-full border px-3 py-1.5 text-xs font-medium whitespace-nowrap',
                                !kind
                                    ? 'border-primary bg-primary text-primary-foreground'
                                    : 'border-border bg-card hover:bg-accent',
                            ]"
                            @click="filter(null)"
                        >
                            {{ t('সব') }}
                            <span class="num opacity-75">{{ totalCount }}</span>
                        </button>
                        <button
                            v-for="(label, key) in kinds"
                            :key="key"
                            type="button"
                            :class="[
                                'rounded-full border px-3 py-1.5 text-xs font-medium whitespace-nowrap',
                                kind === key
                                    ? 'border-primary bg-primary text-primary-foreground'
                                    : 'border-border bg-card hover:bg-accent',
                            ]"
                            @click="filter(key)"
                        >
                            {{ t(label) }}
                            <span class="num opacity-75">{{
                                counts[key] ?? 0
                            }}</span>
                        </button>
                    </div>
                </div>

                <div
                    v-if="!notes.length"
                    class="rounded-xl border border-dashed border-border bg-card/60 px-4 py-12 text-center"
                >
                    <NotebookPen
                        class="mx-auto mb-2 size-8 text-muted-foreground/60"
                    />
                    <p class="font-display text-lg">
                        {{ t('ডায়েরির পাতা এখনো খালি') }}
                    </p>
                    <p class="mt-1 text-sm text-muted-foreground">
                        {{
                            kind
                                ? t('এই ধরনের কোনো লেখা নেই।')
                                : t('আজকের একটি ছোট কথা দিয়ে শুরু করুন।')
                        }}
                    </p>
                </div>

                <div v-else class="space-y-6">
                    <div v-for="g in grouped" :key="g.date">
                        <h3
                            class="mb-2 flex items-center gap-2 font-display text-sm text-muted-foreground"
                        >
                            <span>{{ day(g.date) }}</span>
                            <span
                                v-if="g.date === todayStr"
                                class="rounded-full bg-paddy/15 px-2 py-0.5 text-[11px] text-paddy"
                                >{{ t('আজ') }}</span
                            >
                            <span class="h-px flex-1 bg-border" />
                        </h3>
                        <div class="space-y-2.5">
                            <article
                                v-for="n in g.notes"
                                :key="n.id"
                                :class="[
                                    'rounded-lg border border-l-4 border-border bg-card p-3.5',
                                    styleOf(n.kind).border,
                                    n.done && 'opacity-70',
                                ]"
                            >
                                <div
                                    class="flex flex-wrap items-center gap-1.5 text-[11px]"
                                >
                                    <span
                                        :class="[
                                            'rounded-full px-2 py-0.5 font-medium',
                                            styleOf(n.kind).chip,
                                        ]"
                                        >{{ t(kinds[n.kind] ?? n.kind) }}</span
                                    >
                                    <span
                                        v-if="n.center"
                                        class="rounded-full border border-border px-2 py-0.5 text-muted-foreground"
                                        >{{ t(n.center) }}</span
                                    >
                                    <Link
                                        v-if="n.animal && n.animal_id"
                                        :href="`/animals/${n.animal_id}`"
                                        class="rounded-full border border-pond/40 px-2 py-0.5 text-pond hover:bg-pond/10"
                                        >{{ n.animal }}</Link
                                    >
                                    <span
                                        v-else-if="n.animal"
                                        class="rounded-full border border-border px-2 py-0.5 text-muted-foreground"
                                        >{{ n.animal }}</span
                                    >
                                </div>
                                <h4
                                    :class="[
                                        'mt-2 leading-snug font-medium',
                                        n.done && 'line-through',
                                    ]"
                                >
                                    {{ n.title }}
                                </h4>
                                <p
                                    v-if="n.body"
                                    class="mt-1 text-sm leading-relaxed whitespace-pre-line text-muted-foreground"
                                >
                                    {{ n.body }}
                                </p>
                                <div
                                    v-if="imagesOf(n).length"
                                    class="mt-2 flex flex-wrap gap-2"
                                >
                                    <a
                                        v-for="f in imagesOf(n)"
                                        :key="f.id"
                                        :href="f.url"
                                        target="_blank"
                                        rel="noopener"
                                        class="block overflow-hidden rounded-lg border border-border"
                                    >
                                        <img
                                            :src="f.url"
                                            :alt="f.name"
                                            loading="lazy"
                                            class="size-20 object-cover"
                                        />
                                    </a>
                                </div>
                                <div
                                    v-if="othersOf(n).length"
                                    class="mt-2 grid gap-2"
                                >
                                    <template
                                        v-for="f in othersOf(n)"
                                        :key="f.id"
                                    >
                                        <audio
                                            v-if="f.kind === 'audio'"
                                            controls
                                            preload="none"
                                            :src="f.url"
                                            class="w-full"
                                        />
                                        <a
                                            v-else
                                            :href="f.url"
                                            target="_blank"
                                            rel="noopener"
                                            class="inline-flex items-center gap-1.5 text-sm text-pond hover:underline"
                                        >
                                            <FileText class="size-4" />
                                            {{ f.name }}
                                        </a>
                                    </template>
                                </div>
                                <div
                                    class="mt-3 flex flex-wrap items-center justify-between gap-2 text-xs text-muted-foreground"
                                >
                                    <span v-if="n.author"
                                        >— {{ n.author }}</span
                                    >
                                    <span v-else />
                                    <div
                                        v-if="n.follow_up_on"
                                        class="flex flex-wrap items-center gap-2"
                                    >
                                        <span
                                            :class="[
                                                'inline-flex items-center gap-1 rounded-full px-2 py-0.5',
                                                n.done
                                                    ? 'bg-income/10 text-income'
                                                    : isOverdue(n.follow_up_on)
                                                      ? 'bg-destructive/10 text-destructive'
                                                      : 'bg-turmeric/15 text-foreground',
                                            ]"
                                        >
                                            <CalendarClock class="size-3.5" />
                                            {{
                                                isOverdue(
                                                    n.follow_up_on,
                                                    n.done,
                                                )
                                                    ? t('দেরি হয়েছে · ')
                                                    : t('দেখবেন · ')
                                            }}{{ day(n.follow_up_on) }}
                                        </span>
                                        <Toggle
                                            :model-value="n.done"
                                            :on-text="t('দেখা হয়েছে')"
                                            :off-text="t('বাকি')"
                                            :disabled="!can['post-daily']"
                                            @update:model-value="toggleDone(n)"
                                        />
                                    </div>
                                </div>
                            </article>
                        </div>
                    </div>
                </div>
            </section>
        </div>

        <!-- ================= লক্ষ্য ================= -->
        <div v-else-if="tab === 'goals'" class="grid gap-6 lg:grid-cols-5">
            <section class="min-w-0 space-y-3 lg:order-1 lg:col-span-3">
                <div
                    v-if="!goals.length"
                    class="rounded-xl border border-dashed border-border bg-card/60 px-4 py-12 text-center"
                >
                    <Target
                        class="mx-auto mb-2 size-8 text-muted-foreground/60"
                    />
                    <p class="font-display text-lg">
                        {{ t('এখনো কোনো লক্ষ্য নেই') }}
                    </p>
                    <p class="mt-1 text-sm text-muted-foreground">
                        {{
                            t(
                                'একটি মাপা যায় এমন লক্ষ্য ঠিক করুন — যেমন দিনে দুধ ১৮ থেকে ৩০ লিটার।',
                            )
                        }}
                    </p>
                </div>

                <article
                    v-for="g in goals"
                    :key="g.id"
                    class="rounded-xl border border-border bg-card p-4"
                >
                    <div class="flex items-start justify-between gap-3">
                        <div class="min-w-0">
                            <h3 class="font-display text-lg leading-snug">
                                {{ g.title }}
                            </h3>
                            <p
                                v-if="g.center || lessIsBetter(g)"
                                class="mt-0.5 text-xs text-muted-foreground"
                            >
                                <span v-if="g.center">{{ t(g.center) }}</span>
                                <span v-if="g.center && lessIsBetter(g)">
                                    ·
                                </span>
                                <span v-if="lessIsBetter(g)">{{
                                    t('কম হলেই ভালো')
                                }}</span>
                            </p>
                        </div>
                        <span
                            v-if="g.achieved"
                            class="inline-flex shrink-0 items-center gap-1 rounded-full bg-income/15 px-2.5 py-1 text-xs font-medium text-income"
                        >
                            <Trophy class="size-3.5" /> {{ t('অর্জিত') }}
                        </span>
                        <span
                            v-else
                            class="num shrink-0 font-display text-xl text-paddy"
                            >{{ clampPct(g.progress) }}%</span
                        >
                    </div>

                    <div
                        class="mt-3 h-2.5 overflow-hidden rounded-full bg-muted"
                        role="progressbar"
                        :aria-valuenow="clampPct(g.progress)"
                        aria-valuemin="0"
                        aria-valuemax="100"
                    >
                        <div
                            :class="[
                                'h-full rounded-full transition-[width]',
                                g.achieved ? 'bg-income' : 'bg-paddy',
                            ]"
                            :style="{ width: `${clampPct(g.progress)}%` }"
                        />
                    </div>

                    <div
                        class="mt-3 grid grid-cols-3 gap-2 text-center text-xs text-muted-foreground"
                    >
                        <div>
                            <div>{{ t('শুরু') }}</div>
                            <div class="num text-sm text-foreground">
                                {{ qty(g.start_value, t(g.unit ?? '')) }}
                            </div>
                        </div>
                        <div>
                            <div>{{ t('এখন') }}</div>
                            <div
                                class="num text-sm font-semibold text-foreground"
                            >
                                {{ qty(g.current_value, t(g.unit ?? '')) }}
                            </div>
                        </div>
                        <div>
                            <div>{{ t('লক্ষ্য') }}</div>
                            <div class="num text-sm text-foreground">
                                {{ qty(g.target_value, t(g.unit ?? '')) }}
                            </div>
                        </div>
                    </div>

                    <p
                        v-if="g.due_on"
                        :class="[
                            'mt-3 inline-flex items-center gap-1 text-xs',
                            isOverdue(g.due_on, g.achieved)
                                ? 'text-destructive'
                                : 'text-muted-foreground',
                        ]"
                    >
                        <TriangleAlert
                            v-if="isOverdue(g.due_on, g.achieved)"
                            class="size-3.5"
                        />
                        <CalendarClock v-else class="size-3.5" />
                        {{
                            isOverdue(g.due_on, g.achieved)
                                ? t('সময় পার হয়ে গেছে · ')
                                : t('শেষ তারিখ · ')
                        }}{{ day(g.due_on) }}
                    </p>
                    <p
                        v-if="g.notes"
                        class="mt-2 text-sm whitespace-pre-line text-muted-foreground"
                    >
                        {{ g.notes }}
                    </p>

                    <form
                        v-if="can['post-entries']"
                        class="pt3 mt-3 flex items-end gap-2 border-t border-border/70 pt-3"
                        @submit.prevent="updateCurrent(g)"
                    >
                        <label class="grid min-w-0 flex-1 gap-1">
                            <span class="text-xs font-medium">{{
                                t('আজকের অবস্থা')
                            }}</span>
                            <div class="relative">
                                <Input
                                    v-model="nowValues[g.id]"
                                    type="number"
                                    inputmode="decimal"
                                    step="any"
                                    class="num h-10 pr-14 text-right"
                                />
                                <span
                                    v-if="g.unit"
                                    class="pointer-events-none absolute top-2.5 right-3 text-xs text-muted-foreground"
                                    >{{ t(g.unit) }}</span
                                >
                            </div>
                        </label>
                        <Button
                            type="submit"
                            variant="outline"
                            class="h-10"
                            :disabled="
                                savingGoal === g.id ||
                                nowValues[g.id] === '' ||
                                parseFloat(nowValues[g.id]) === g.current_value
                            "
                        >
                            <Check class="size-4" /> {{ t('রাখুন') }}
                        </Button>
                    </form>
                </article>
            </section>

            <form
                v-if="can['post-entries']"
                class="h-fit rounded-xl border border-border bg-card p-4 lg:col-span-2"
                @submit.prevent="saveGoal"
            >
                <h2 class="font-display text-lg">{{ t('নতুন লক্ষ্য') }}</h2>
                <p class="mt-1 text-xs text-muted-foreground">
                    {{
                        t(
                            'যেটা কম হলে ভালো (যেমন খরচ), সেখানে লক্ষ্য শুরুর চেয়ে কম দিন।',
                        )
                    }}
                </p>

                <div class="mt-3 flex flex-wrap gap-1.5">
                    <button
                        v-for="s in suggestions"
                        :key="s.title"
                        type="button"
                        class="rounded-full border border-dashed border-paddy/50 bg-paddy/5 px-3 py-1.5 text-xs text-paddy hover:bg-paddy/15"
                        @click="prefill(s)"
                    >
                        + {{ s.title }}
                    </button>
                </div>

                <div class="mt-4 grid gap-3">
                    <Field :label="t('লক্ষ্য')" :error="goal.errors.title">
                        <Input
                            v-model="goal.title"
                            required
                            class="h-10"
                            :placeholder="t('যেমন: দিনে দুধ')"
                        />
                    </Field>
                    <div class="grid grid-cols-2 gap-3">
                        <Field :label="t('একক')" :error="goal.errors.unit">
                            <Input
                                v-model="goal.unit"
                                class="h-10"
                                :placeholder="t('লিটার')"
                            />
                        </Field>
                        <Field
                            :label="t('শেষ তারিখ')"
                            :error="goal.errors.due_on"
                        >
                            <DateField
                                clearable
                                v-model="goal.due_on"
                                :min="todayStr"
                                class="h-10"
                            />
                        </Field>
                    </div>
                    <div class="grid grid-cols-3 gap-2">
                        <Field
                            :label="t('শুরু')"
                            :error="goal.errors.start_value"
                        >
                            <Input
                                v-model="goal.start_value"
                                type="number"
                                inputmode="decimal"
                                step="any"
                                required
                                class="num h-10"
                                placeholder="18"
                            />
                        </Field>
                        <Field
                            :label="t('এখন')"
                            :error="goal.errors.current_value"
                        >
                            <Input
                                v-model="goal.current_value"
                                type="number"
                                inputmode="decimal"
                                step="any"
                                class="num h-10"
                                placeholder="18"
                            />
                        </Field>
                        <Field
                            :label="t('লক্ষ্য')"
                            :error="goal.errors.target_value"
                        >
                            <Input
                                v-model="goal.target_value"
                                type="number"
                                inputmode="decimal"
                                step="any"
                                required
                                class="num h-10"
                                placeholder="30"
                            />
                        </Field>
                    </div>
                    <Field
                        :label="t('বিভাগ')"
                        :error="goal.errors.cost_center_id"
                    >
                        <NSelect
                            v-model="goal.cost_center_id"
                            :placeholder="t('ঐচ্ছিক')"
                        >
                            <option :value="null">—</option>
                            <option
                                v-for="c in centers"
                                :key="c.id"
                                :value="c.id"
                                :disabled="c.kind === 'group'"
                            >
                                {{ centerLabel({ ...c, name: t(c.name) }) }}
                            </option>
                        </NSelect>
                    </Field>
                    <Field :label="t('নোট')" :error="goal.errors.notes">
                        <textarea
                            v-model="goal.notes"
                            rows="2"
                            class="w-full rounded-md border border-input bg-card px-3 py-2 text-[15px] shadow-xs outline-none placeholder:text-muted-foreground focus-visible:border-ring focus-visible:ring-[3px] focus-visible:ring-ring/40"
                            :placeholder="
                                t('কীভাবে পৌঁছাবেন — যেমন সবুজ ঘাস বাড়ানো')
                            "
                        />
                    </Field>
                </div>

                <Button
                    type="submit"
                    class="mt-4 h-11 w-full text-base"
                    :disabled="goal.processing || !goal.title.trim()"
                    >{{ t('লক্ষ্য রাখুন') }}</Button
                >
            </form>
        </div>

        <!-- ================= নিয়ম ও রুটিন ================= -->
        <div v-else class="grid gap-6 lg:grid-cols-5">
            <section class="grid min-w-0 gap-4 sm:grid-cols-2 lg:col-span-3">
                <p
                    v-if="!routine.length"
                    class="rounded-xl border border-dashed border-border bg-card/60 px-4 py-12 text-center text-sm text-muted-foreground sm:col-span-2"
                >
                    {{ t('রুটিন এখনো ঠিক করা হয়নি।') }}
                </p>
                <div
                    v-for="(block, i) in routine"
                    :key="block.title"
                    class="rounded-xl border border-border bg-card p-4"
                >
                    <div class="mb-2 flex items-center justify-between gap-2">
                        <h3 class="font-display text-lg">
                            {{ t(block.title) }}
                        </h3>
                        <span class="num text-xs text-muted-foreground"
                            >{{ doneIn(i, block.items) }}/{{
                                block.items.length
                            }}</span
                        >
                    </div>
                    <ul class="space-y-0.5">
                        <li v-for="(item, j) in block.items" :key="j">
                            <button
                                type="button"
                                class="flex min-h-10 w-full items-start gap-3 rounded-md px-1 py-2 text-left text-sm hover:bg-accent/60"
                                :aria-pressed="!!ticks[`${i}-${j}`]"
                                @click="tick(`${i}-${j}`)"
                            >
                                <span
                                    :class="[
                                        'mt-0.5 flex size-5 shrink-0 items-center justify-center rounded border-2 transition-colors',
                                        ticks[`${i}-${j}`]
                                            ? 'border-paddy bg-paddy text-primary-foreground'
                                            : 'border-muted-foreground/40 bg-card',
                                    ]"
                                >
                                    <Check
                                        v-if="ticks[`${i}-${j}`]"
                                        class="size-3.5"
                                        :stroke-width="3"
                                    />
                                </span>
                                <span
                                    :class="
                                        ticks[`${i}-${j}`] &&
                                        'text-muted-foreground line-through'
                                    "
                                    >{{ t(item) }}</span
                                >
                            </button>
                        </li>
                    </ul>
                </div>
                <p class="text-xs text-muted-foreground sm:col-span-2">
                    {{ t('টিক শুধু এই ফোনে, আজকের জন্য মনে থাকে।') }}
                </p>
            </section>

            <aside
                class="h-fit rounded-xl border border-paddy/30 bg-paddy/5 p-4 lg:col-span-2"
            >
                <h2 class="font-display text-lg">
                    {{ t('ভালো খামারের ৫টি নিয়ম') }}
                </h2>
                <ol class="mt-3 space-y-3">
                    <li
                        v-for="(rule, i) in rules"
                        :key="i"
                        class="flex gap-3 text-sm leading-relaxed"
                    >
                        <span
                            class="num flex size-6 shrink-0 items-center justify-center rounded-full bg-paddy text-xs font-semibold text-primary-foreground"
                            >{{ i + 1 }}</span
                        >
                        <span>{{ rule }}</span>
                    </li>
                </ol>
            </aside>
        </div>
    </div>
</template>
