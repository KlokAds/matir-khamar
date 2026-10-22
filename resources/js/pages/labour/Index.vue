<script setup lang="ts">
import { Head, router, useForm } from '@inertiajs/vue3';
import {
    Banknote,
    Check,
    ClipboardPen,
    History,
    PieChart,
    Pencil,
    Trash2,
    UserPlus,
    Users,
    X,
} from '@lucide/vue';
import { computed, ref, watch } from 'vue';
import DateField from '@/components/khamar/DateField.vue';
import Field from '@/components/khamar/Field.vue';
import NSelect from '@/components/khamar/NSelect.vue';
import PageTitle from '@/components/khamar/PageTitle.vue';
import StatTile from '@/components/khamar/StatTile.vue';
import Toggle from '@/components/khamar/Toggle.vue';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { ask } from '@/lib/dialog';
import { t, tAll } from '@/lib/i18n';
import {
    centerLabel,
    day,
    monthFull,
    qty,
    taka,
    today,
    useCan,
} from '@/lib/khamar';
import type { Center, Option } from '@/lib/khamar';

defineOptions({
    layout: { breadcrumbs: [{ title: 'মজুরির খাতা', href: '/labour' }] },
});

type UnpaidLog = {
    id: number;
    date: string;
    work_type: string;
    center: string;
    days: number;
    amount: number;
};

type WorkerRow = {
    id: number;
    name: string;
    phone: string | null;
    kind: 'daily' | 'monthly';
    kind_label: string;
    daily_rate: number | null;
    monthly_salary: number | null;
    day_rate: number;
    cost_center_id: number | null;
    center: string | null;
    is_active: boolean;
    notes: string | null;
    unpaid_days: number;
    owed: number;
    unpaid: UnpaidLog[];
};

type LogRow = {
    id: number;
    worker: string;
    worker_id: number;
    center: string;
    season: string | null;
    work_type: string;
    days: number;
    rate: number;
    amount: number;
    notes: string | null;
    paid: boolean;
    paid_number: string | null;
    deletable: boolean;
};

type DayGroup = { date: string; total: number; logs: LogRow[] };
type SumRow = { name: string; amount: number; days: number };
type Summary = {
    month: string;
    total: number;
    days: number;
    by_center: SumRow[];
    by_type: SumRow[];
};
type Season = { id: number; name: string; cost_center_id: number };

const props = defineProps<{
    workers: WorkerRow[];
    recent: DayGroup[];
    summary: Summary;
    workTypes: string[];
    kinds: Record<string, string>;
    centers: Center[];
    seasons: Season[];
    moneyAccounts: Option[];
}>();

const can = useCan();
const canLog = computed(() => !!can.value['post-daily']);
const canManage = computed(() => !!can.value['post-entries']);

const active = computed(() => props.workers.filter((w) => w.is_active));
const totalOwed = computed(() =>
    props.workers.reduce((sum, w) => sum + w.owed, 0),
);
const owingCount = computed(
    () => props.workers.filter((w) => w.owed > 0).length,
);

const daysText = (n: number) => qty(n);
const toTaka = (poisha: number | null | undefined) =>
    poisha ? String(Math.round(poisha) / 100) : '';

// ---------- Today's work ----------
const logForm = useForm({
    date: today(),
    cost_center_id: null as number | null,
    crop_season_id: null as number | null,
    work_type: '',
    notes: '',
    workers: [] as { worker_id: number; days: number; rate: string }[],
});

const picked = ref<Record<number, string>>({}); // worker id -> rate in taka
const dayMode = ref<'1' | '0.5' | 'custom'>('1');
const customDays = ref('1');
const days = computed(() =>
    dayMode.value === 'custom'
        ? Number(customDays.value) || 0
        : Number(dayMode.value),
);

function toggleWorker(w: WorkerRow) {
    const next = { ...picked.value };

    if (w.id in next) {
        delete next[w.id];
    } else {
        next[w.id] = toTaka(w.day_rate);

        if (logForm.cost_center_id === null && w.cost_center_id) {
            logForm.cost_center_id = w.cost_center_id;
        }
    }

    picked.value = next;
}

const pickedWorkers = computed(() =>
    active.value.filter((w) => w.id in picked.value),
);

const logTotal = computed(() =>
    pickedWorkers.value.reduce(
        (sum, w) =>
            sum +
            Math.round(days.value * (Number(picked.value[w.id]) || 0) * 100),
        0,
    ),
);

// Picking a season sets its project, so the cost lands on the right crop.
watch(
    () => logForm.crop_season_id,
    (id) => {
        const s = props.seasons.find((x) => x.id === id);

        if (s) {
            logForm.cost_center_id = s.cost_center_id;
        }
    },
);

const canSubmitLog = computed(
    () =>
        !logForm.processing &&
        pickedWorkers.value.length > 0 &&
        logForm.cost_center_id !== null &&
        logForm.work_type.trim() !== '' &&
        days.value > 0,
);

function submitLog() {
    logForm
        .transform((data) => ({
            ...data,
            workers: pickedWorkers.value.map((w) => ({
                worker_id: w.id,
                days: days.value,
                rate: picked.value[w.id] || '0',
            })),
        }))
        .post('/labour/logs', {
            preserveScroll: true,
            onSuccess: () => {
                picked.value = {};
                logForm.reset('notes');
            },
        });
}

// ---------- Delete a log ----------
const removing = ref<number | null>(null);

async function removeLog(row: LogRow, date: string) {
    if (
        !(await ask.confirm({
            title: t('লেখাটি মুছবেন?'),
            body: t(
                ':name — :work, :days দিন (:date)। শুধু আজকের, মজুরি না দেওয়া লেখা মোছা যায়।',
                {
                    name: row.worker,
                    work: t(row.work_type),
                    days: daysText(row.days),
                    date: day(date),
                },
            ),
            confirmText: t('হ্যাঁ, মুছুন'),
            danger: true,
        }))
    ) {
        return;
    }

    removing.value = row.id;
    router.delete(`/labour/logs/${row.id}`, {
        preserveScroll: true,
        onFinish: () => (removing.value = null),
    });
}

// ---------- Pay ----------
const payFor = ref<number | null>(null);
const payForm = useForm({
    date: today(),
    money_account_id: (props.moneyAccounts[0]?.id ?? null) as number | null,
    log_ids: [] as number[],
});

function openPay(w: WorkerRow) {
    payFor.value = payFor.value === w.id ? null : w.id;
    payForm.clearErrors();
    payForm.log_ids = w.unpaid.map((l) => l.id);
}

const payWorker = computed(
    () => props.workers.find((w) => w.id === payFor.value) ?? null,
);
const payLogs = computed(() =>
    (payWorker.value?.unpaid ?? []).filter(
        (l) => payForm.log_ids.includes(l.id) && l.date <= payForm.date,
    ),
);
const payTotal = computed(() =>
    payLogs.value.reduce((sum, l) => sum + l.amount, 0),
);

function togglePayLog(id: number) {
    payForm.log_ids = payForm.log_ids.includes(id)
        ? payForm.log_ids.filter((x) => x !== id)
        : [...payForm.log_ids, id];
}

async function pay() {
    const w = payWorker.value;

    if (!w || !payLogs.value.length) {
        return;
    }

    const account = props.moneyAccounts.find(
        (a) => a.id === payForm.money_account_id,
    );

    if (
        !(await ask.confirm({
            title: t(':name-কে :amount দেবেন?', {
                name: w.name,
                amount: taka(payTotal.value),
            }),
            body: t(':n টি লেখা, মোট :days দিন · :account থেকে · :date', {
                n: payLogs.value.length,
                days: daysText(payLogs.value.reduce((s, l) => s + l.days, 0)),
                account: account ? t(account.name) : '—',
                date: day(payForm.date),
            }),
            confirmText: t('হ্যাঁ, দিন'),
        }))
    ) {
        return;
    }

    payForm
        .transform((data) => ({
            ...data,
            log_ids: payLogs.value.map((l) => l.id),
        }))
        .post(`/labour/workers/${w.id}/pay`, {
            preserveScroll: true,
            onSuccess: () => (payFor.value = null),
        });
}

// ---------- Add / edit worker ----------
const editing = ref<number | 'new' | null>(null);
const workerForm = useForm({
    name: '',
    phone: '',
    kind: 'daily' as 'daily' | 'monthly',
    daily_rate: '',
    monthly_salary: '',
    cost_center_id: null as number | null,
    is_active: true,
    notes: '',
});

function openWorker(w: WorkerRow | null) {
    workerForm.clearErrors();

    if (w) {
        editing.value = w.id;
        workerForm.name = w.name;
        workerForm.phone = w.phone ?? '';
        workerForm.kind = w.kind;
        workerForm.daily_rate = toTaka(w.daily_rate);
        workerForm.monthly_salary = toTaka(w.monthly_salary);
        workerForm.cost_center_id = w.cost_center_id;
        workerForm.is_active = w.is_active;
        workerForm.notes = w.notes ?? '';
    } else {
        editing.value = 'new';
        workerForm.reset();
    }
}

function saveWorker() {
    const options = {
        preserveScroll: true,
        onSuccess: () => (editing.value = null),
    };

    workerForm.transform((data) => ({
        ...data,
        daily_rate: data.kind === 'daily' ? data.daily_rate : null,
        monthly_salary: data.kind === 'monthly' ? data.monthly_salary : null,
    }));

    if (editing.value === 'new') {
        workerForm.post('/labour/workers', options);
    } else if (editing.value !== null) {
        workerForm.put(`/labour/workers/${editing.value}`, options);
    }
}

// ---------- Month summary ----------
const month = ref(props.summary.month);

watch(month, (m) => {
    if (m && m !== props.summary.month) {
        router.get(
            '/labour',
            { month: m.slice(0, 7) },
            { preserveState: true, preserveScroll: true, only: ['summary'] },
        );
    }
});

const share = (amount: number) =>
    props.summary.total > 0
        ? Math.max(2, Math.round((amount / props.summary.total) * 100))
        : 0;
</script>

<template>
    <Head :title="t('মজুরির খাতা')" />

    <div class="flex flex-1 flex-col p-4 md:p-6">
        <PageTitle
            :title="t('মজুরির খাতা')"
            :subtitle="
                t(
                    'কে কোন কাজে কত দিন খাটলেন, কত বাকি — আর একবারে মজুরি দেওয়া।',
                )
            "
        >
            <template #actions>
                <Button
                    v-if="canManage"
                    variant="outline"
                    @click="openWorker(null)"
                >
                    <UserPlus class="size-4" /> {{ t('শ্রমিক যোগ') }}
                </Button>
            </template>
        </PageTitle>

        <div class="mb-6 grid grid-cols-2 gap-3">
            <StatTile
                :label="t('মোট বাকি মজুরি')"
                :value="taka(totalOwed)"
                :hint="t(':n জনের পাওনা', { n: owingCount })"
                :icon="Banknote"
                tone="clay"
            />
            <StatTile
                :label="t('এ মাসের মজুরি')"
                :value="taka(summary.total)"
                :hint="t(':n দিন কাজ', { n: daysText(summary.days) })"
                :icon="Users"
                tone="paddy"
            />
        </div>

        <!-- Add / edit worker -->
        <form
            v-if="canManage && editing !== null"
            class="mb-6 rounded-xl border border-border bg-card p-4"
            @submit.prevent="saveWorker"
        >
            <div class="mb-3 flex items-center justify-between gap-2">
                <h2 class="flex items-center gap-2 font-display text-lg">
                    <UserPlus class="size-5 text-paddy" />
                    {{
                        editing === 'new'
                            ? t('নতুন শ্রমিক')
                            : t('শ্রমিকের তথ্য')
                    }}
                </h2>
                <button
                    type="button"
                    class="btn-icon"
                    :aria-label="t('বন্ধ')"
                    :title="t('বন্ধ')"
                    @click="editing = null"
                >
                    <X class="size-4" />
                </button>
            </div>

            <div class="grid gap-3 sm:grid-cols-2">
                <Field :label="t('নাম')" :error="workerForm.errors.name">
                    <Input v-model="workerForm.name" class="h-10" required />
                </Field>
                <Field :label="t('মোবাইল')" :error="workerForm.errors.phone">
                    <Input
                        v-model="workerForm.phone"
                        class="h-10"
                        inputmode="tel"
                    />
                </Field>
                <Field :label="t('ধরন')" :error="workerForm.errors.kind">
                    <div class="grid grid-cols-2 gap-1 rounded-lg bg-muted p-1">
                        <button
                            v-for="(label, key) in tAll(kinds)"
                            :key="key"
                            type="button"
                            :class="[
                                'h-9 rounded-md text-sm font-medium transition-colors',
                                workerForm.kind === key
                                    ? 'bg-card text-foreground shadow-sm'
                                    : 'text-muted-foreground hover:text-foreground',
                            ]"
                            @click="
                                workerForm.kind = key as 'daily' | 'monthly'
                            "
                        >
                            {{ label }}
                        </button>
                    </div>
                </Field>
                <Field
                    v-if="workerForm.kind === 'daily'"
                    :label="t('দৈনিক মজুরি (টাকা)')"
                    :error="workerForm.errors.daily_rate"
                >
                    <Input
                        v-model="workerForm.daily_rate"
                        class="num h-10"
                        inputmode="decimal"
                        :placeholder="t('যেমন: ৫০০')"
                    />
                </Field>
                <Field
                    v-else
                    :label="t('মাসিক বেতন (টাকা)')"
                    :error="workerForm.errors.monthly_salary"
                    :hint="t('দিনের হার = বেতন ÷ ৩০')"
                >
                    <Input
                        v-model="workerForm.monthly_salary"
                        class="num h-10"
                        inputmode="decimal"
                    />
                </Field>
                <Field
                    :label="t('সাধারণত কোন প্রজেক্টে')"
                    :error="workerForm.errors.cost_center_id"
                >
                    <NSelect
                        v-model="workerForm.cost_center_id"
                        :placeholder="t('ঠিক নেই')"
                    >
                        <option :value="null">{{ t('ঠিক নেই') }}</option>
                        <option v-for="c in centers" :key="c.id" :value="c.id">
                            {{ centerLabel({ ...c, name: t(c.name) }) }}
                        </option>
                    </NSelect>
                </Field>
                <Field :label="t('কাজে আছেন')">
                    <Toggle
                        v-model="workerForm.is_active"
                        :on-text="t('হ্যাঁ')"
                        :off-text="t('না')"
                    />
                </Field>
                <Field
                    :label="t('নোট (ইচ্ছামতো)')"
                    :error="workerForm.errors.notes"
                    class="sm:col-span-2"
                >
                    <Input v-model="workerForm.notes" class="h-10" />
                </Field>
            </div>

            <Button
                type="submit"
                class="mt-4"
                :disabled="workerForm.processing || !workerForm.name.trim()"
            >
                <Check class="size-4" /> {{ t('রাখুন') }}
            </Button>
        </form>

        <div class="grid gap-6 lg:grid-cols-5">
            <div class="min-w-0 space-y-6 lg:col-span-2">
                <!-- Today's work -->
                <form
                    v-if="canLog"
                    class="rounded-xl border border-border bg-card p-4"
                    @submit.prevent="submitLog"
                >
                    <h2
                        class="mb-3 flex items-center gap-2 font-display text-lg"
                    >
                        <ClipboardPen class="size-5 text-paddy" />
                        {{ t('আজকের কাজ লিখুন') }}
                    </h2>

                    <div class="grid gap-3">
                        <Field :label="t('তারিখ')" :error="logForm.errors.date">
                            <DateField
                                v-model="logForm.date"
                                :max="today()"
                                class="h-10"
                                required
                            />
                        </Field>

                        <Field
                            :label="t('কারা কাজ করলেন')"
                            :error="logForm.errors.workers"
                        >
                            <div
                                v-if="active.length"
                                class="grid grid-cols-2 gap-1"
                            >
                                <label
                                    v-for="w in active"
                                    :key="w.id"
                                    :class="[
                                        'flex min-w-0 cursor-pointer items-center gap-2 rounded-md border px-2 py-1.5 text-sm',
                                        w.id in picked
                                            ? 'border-paddy/50 bg-paddy/8'
                                            : 'border-border hover:bg-accent',
                                    ]"
                                >
                                    <input
                                        type="checkbox"
                                        class="size-4 shrink-0 accent-paddy"
                                        :checked="w.id in picked"
                                        @change="toggleWorker(w)"
                                    />
                                    <span class="truncate">{{ w.name }}</span>
                                </label>
                            </div>
                            <p
                                v-else
                                class="rounded-lg bg-muted px-3 py-4 text-center text-sm text-muted-foreground"
                            >
                                {{ t('আগে শ্রমিক যোগ করুন।') }}
                            </p>
                        </Field>

                        <Field
                            :label="t('কী কাজ')"
                            :error="logForm.errors.work_type"
                        >
                            <div class="flex flex-wrap gap-1.5">
                                <button
                                    v-for="wt in workTypes"
                                    :key="wt"
                                    type="button"
                                    :class="[
                                        'inline-flex min-h-8 items-center gap-1.5 rounded-full border px-3 py-1 text-xs transition-colors',
                                        logForm.work_type === wt
                                            ? 'border-primary bg-primary text-primary-foreground'
                                            : 'border-input bg-card hover:bg-accent',
                                    ]"
                                    @click="logForm.work_type = wt"
                                >
                                    {{ t(wt) }}
                                </button>
                            </div>
                            <Input
                                v-model="logForm.work_type"
                                class="mt-1.5 h-10"
                                :placeholder="t('অথবা লিখুন')"
                            />
                        </Field>

                        <Field
                            :label="t('কোন প্রজেক্টের কাজ')"
                            :error="logForm.errors.cost_center_id"
                        >
                            <NSelect
                                v-model="logForm.cost_center_id"
                                :placeholder="t('প্রজেক্ট বাছুন')"
                            >
                                <option
                                    v-for="c in centers"
                                    :key="c.id"
                                    :value="c.id"
                                >
                                    {{ centerLabel({ ...c, name: t(c.name) }) }}
                                </option>
                            </NSelect>
                        </Field>

                        <Field
                            v-if="seasons.length"
                            :label="t('মৌসুম (ইচ্ছামতো)')"
                            :error="logForm.errors.crop_season_id"
                        >
                            <NSelect
                                v-model="logForm.crop_season_id"
                                :placeholder="t('কোনো মৌসুম নয়')"
                            >
                                <option :value="null">
                                    {{ t('কোনো মৌসুম নয়') }}
                                </option>
                                <option
                                    v-for="s in seasons"
                                    :key="s.id"
                                    :value="s.id"
                                >
                                    {{ s.name }}
                                </option>
                            </NSelect>
                        </Field>

                        <Field :label="t('কত দিন')">
                            <div
                                class="grid grid-cols-3 gap-1 rounded-lg bg-muted p-1"
                            >
                                <button
                                    v-for="opt in [
                                        { key: '1', label: t('পুরো দিন') },
                                        { key: '0.5', label: t('আধা দিন') },
                                        { key: 'custom', label: t('অন্য') },
                                    ]"
                                    :key="opt.key"
                                    type="button"
                                    :class="[
                                        'h-9 rounded-md text-sm font-medium transition-colors',
                                        dayMode === opt.key
                                            ? 'bg-card text-foreground shadow-sm'
                                            : 'text-muted-foreground hover:text-foreground',
                                    ]"
                                    @click="
                                        dayMode = opt.key as
                                            '1' | '0.5' | 'custom'
                                    "
                                >
                                    {{ opt.label }}
                                </button>
                            </div>
                            <Input
                                v-if="dayMode === 'custom'"
                                v-model="customDays"
                                class="num mt-1.5 h-10"
                                inputmode="decimal"
                                :placeholder="t('যেমন: ৩ বা ১.৫')"
                            />
                        </Field>

                        <div
                            v-if="pickedWorkers.length"
                            class="rounded-lg border border-border bg-background/60"
                        >
                            <p
                                class="border-b border-border px-3 py-2 text-xs text-muted-foreground"
                            >
                                {{ t('দিনের হার (টাকা)') }}
                            </p>
                            <div
                                v-for="w in pickedWorkers"
                                :key="w.id"
                                class="flex items-center justify-between gap-3 px-3 py-1.5"
                            >
                                <span class="min-w-0 truncate text-sm">{{
                                    w.name
                                }}</span>
                                <Input
                                    v-model="picked[w.id]"
                                    class="num h-9 w-28 text-right"
                                    inputmode="decimal"
                                />
                            </div>
                            <p
                                class="flex justify-between border-t border-border px-3 py-2 text-sm"
                            >
                                <span class="text-muted-foreground">{{
                                    t('মোট')
                                }}</span>
                                <span class="num font-semibold">{{
                                    taka(logTotal)
                                }}</span>
                            </p>
                        </div>

                        <Field
                            :label="t('নোট (ইচ্ছামতো)')"
                            :error="logForm.errors.notes"
                        >
                            <Input v-model="logForm.notes" class="h-10" />
                        </Field>

                        <Button type="submit" :disabled="!canSubmitLog">
                            <Check class="size-4" />
                            {{
                                t('লিখুন (:n জন)', {
                                    n: pickedWorkers.length,
                                })
                            }}
                        </Button>
                    </div>
                </form>

                <!-- Month summary -->
                <section class="rounded-xl border border-border bg-card p-4">
                    <div class="mb-3 flex items-center justify-between gap-2">
                        <h2
                            class="flex items-center gap-2 font-display text-lg"
                        >
                            <PieChart class="size-5 text-turmeric" />
                            {{ t('মাসের হিসাব') }}
                        </h2>
                        <DateField
                            v-model="month"
                            mode="month"
                            :max="today().slice(0, 7)"
                            class="h-9 w-40"
                        />
                    </div>

                    <p class="mb-3 text-sm text-muted-foreground">
                        {{ monthFull(summary.month) }} ·
                        <span class="num font-medium text-foreground">{{
                            taka(summary.total)
                        }}</span>
                        · {{ t(':n দিন কাজ', { n: daysText(summary.days) }) }}
                    </p>

                    <template v-if="summary.total > 0">
                        <div
                            v-for="block in [
                                {
                                    title: t('প্রজেক্ট অনুযায়ী'),
                                    rows: summary.by_center,
                                    tone: 'bg-paddy',
                                },
                                {
                                    title: t('কাজ অনুযায়ী'),
                                    rows: summary.by_type,
                                    tone: 'bg-pond',
                                },
                            ]"
                            :key="block.title"
                            class="mb-4 last:mb-0"
                        >
                            <h3
                                class="mb-2 text-xs font-semibold text-muted-foreground"
                            >
                                {{ block.title }}
                            </h3>
                            <ul class="space-y-2">
                                <li v-for="r in block.rows" :key="r.name">
                                    <div
                                        class="flex items-baseline justify-between gap-2 text-sm"
                                    >
                                        <span class="min-w-0 truncate">{{
                                            t(r.name)
                                        }}</span>
                                        <span class="num shrink-0"
                                            >{{ taka(r.amount) }}
                                            <span
                                                class="text-xs text-muted-foreground"
                                                >·
                                                {{
                                                    t(':n দিন', {
                                                        n: daysText(r.days),
                                                    })
                                                }}</span
                                            ></span
                                        >
                                    </div>
                                    <div
                                        class="mt-1 h-1.5 overflow-hidden rounded-full bg-muted"
                                    >
                                        <div
                                            :class="[
                                                'h-full rounded-full',
                                                block.tone,
                                            ]"
                                            :style="{
                                                width: `${share(r.amount)}%`,
                                            }"
                                        />
                                    </div>
                                </li>
                            </ul>
                        </div>
                    </template>
                    <p
                        v-else
                        class="py-6 text-center text-sm text-muted-foreground"
                    >
                        {{ t('এ মাসে কোনো কাজ লেখা হয়নি।') }}
                    </p>
                </section>
            </div>

            <div class="min-w-0 space-y-6 lg:col-span-3">
                <!-- Workers -->
                <section class="rounded-xl border border-border bg-card p-4">
                    <h2
                        class="mb-3 flex items-center gap-2 font-display text-lg"
                    >
                        <Users class="size-5 text-clay" />
                        {{ t('শ্রমিক ও পাওনা') }}
                    </h2>

                    <ul v-if="workers.length" class="divide-y divide-border/70">
                        <li v-for="w in workers" :key="w.id" class="py-3">
                            <div class="flex items-start justify-between gap-3">
                                <div class="min-w-0">
                                    <p
                                        :class="[
                                            'truncate font-medium',
                                            !w.is_active &&
                                                'text-muted-foreground line-through',
                                        ]"
                                    >
                                        {{ w.name }}
                                    </p>
                                    <p
                                        class="truncate text-xs text-muted-foreground"
                                    >
                                        {{ t(w.kind_label) }} ·
                                        <span class="num">{{
                                            t(':amount/দিন', {
                                                amount: taka(w.day_rate),
                                            })
                                        }}</span>
                                        <template v-if="w.center">
                                            · {{ t(w.center) }}</template
                                        >
                                    </p>
                                </div>
                                <div class="shrink-0 text-right">
                                    <p
                                        :class="[
                                            'num font-semibold',
                                            w.owed > 0
                                                ? 'text-expense'
                                                : 'text-muted-foreground',
                                        ]"
                                    >
                                        {{ taka(w.owed) }}
                                    </p>
                                    <p
                                        class="num text-xs text-muted-foreground"
                                    >
                                        {{
                                            t(':n দিন বাকি', {
                                                n: daysText(w.unpaid_days),
                                            })
                                        }}
                                    </p>
                                </div>
                            </div>

                            <div
                                v-if="canManage"
                                class="mt-2 flex flex-wrap gap-2"
                            >
                                <Button
                                    v-if="w.owed > 0"
                                    size="sm"
                                    :variant="
                                        payFor === w.id ? 'default' : 'outline'
                                    "
                                    @click="openPay(w)"
                                >
                                    <Banknote class="size-4" />
                                    {{ t('মজুরি দিন') }}
                                </Button>
                                <Button
                                    size="sm"
                                    variant="secondary"
                                    @click="openWorker(w)"
                                >
                                    <Pencil class="size-4" /> {{ t('বদলান') }}
                                </Button>
                            </div>

                            <!-- Pay panel -->
                            <div
                                v-if="canManage && payFor === w.id"
                                class="mt-3 rounded-lg border border-border bg-background/60 p-3"
                            >
                                <div class="grid grid-cols-2 gap-3">
                                    <Field
                                        :label="t('তারিখ')"
                                        :error="payForm.errors.date"
                                    >
                                        <DateField
                                            v-model="payForm.date"
                                            :max="today()"
                                            class="h-10"
                                            required
                                        />
                                    </Field>
                                    <Field
                                        :label="t('কোথা থেকে দিলেন')"
                                        :error="payForm.errors.money_account_id"
                                    >
                                        <NSelect
                                            v-model="payForm.money_account_id"
                                        >
                                            <option
                                                v-for="a in moneyAccounts"
                                                :key="a.id"
                                                :value="a.id"
                                            >
                                                {{ t(a.name) }}
                                            </option>
                                        </NSelect>
                                    </Field>
                                </div>

                                <ul class="mt-3 divide-y divide-border/60">
                                    <li
                                        v-for="l in w.unpaid"
                                        :key="l.id"
                                        class="flex items-center gap-2 py-1.5 text-sm"
                                    >
                                        <input
                                            type="checkbox"
                                            class="size-4 shrink-0 accent-paddy"
                                            :checked="
                                                payForm.log_ids.includes(l.id)
                                            "
                                            :disabled="l.date > payForm.date"
                                            @change="togglePayLog(l.id)"
                                        />
                                        <span class="min-w-0 flex-1 truncate"
                                            ><span class="num">{{
                                                day(l.date)
                                            }}</span>
                                            · {{ t(l.work_type) }} ·
                                            <span
                                                class="text-xs text-muted-foreground"
                                                >{{ t(l.center) }}</span
                                            ></span
                                        >
                                        <span class="num shrink-0 text-xs"
                                            >{{
                                                t(':n দিন', {
                                                    n: daysText(l.days),
                                                })
                                            }}
                                            · {{ taka(l.amount) }}</span
                                        >
                                    </li>
                                </ul>

                                <p
                                    v-if="payForm.errors.log_ids"
                                    class="mt-2 text-xs text-destructive"
                                >
                                    {{ payForm.errors.log_ids }}
                                </p>

                                <Button
                                    class="mt-3 w-full"
                                    :disabled="
                                        payForm.processing ||
                                        !payLogs.length ||
                                        !payForm.money_account_id
                                    "
                                    @click="pay"
                                >
                                    <Banknote class="size-4" />
                                    {{
                                        t(':amount দিন', {
                                            amount: taka(payTotal),
                                        })
                                    }}
                                </Button>
                            </div>
                        </li>
                    </ul>
                    <p
                        v-else
                        class="py-8 text-center text-sm text-muted-foreground"
                    >
                        {{ t('এখনো কোনো শ্রমিক যোগ হয়নি।') }}
                    </p>
                </section>

                <!-- Recent logs -->
                <section class="rounded-xl border border-border bg-card p-4">
                    <h2
                        class="mb-3 flex items-center gap-2 font-display text-lg"
                    >
                        <History class="size-5 text-pond" />
                        {{ t('সাম্প্রতিক কাজ') }}
                    </h2>

                    <div v-if="recent.length" class="space-y-4">
                        <div v-for="g in recent" :key="g.date">
                            <p
                                class="mb-1 flex justify-between text-xs font-semibold text-muted-foreground"
                            >
                                <span class="num">{{ day(g.date) }}</span>
                                <span class="num">{{ taka(g.total) }}</span>
                            </p>
                            <ul class="divide-y divide-border/60">
                                <li
                                    v-for="l in g.logs"
                                    :key="l.id"
                                    class="flex items-center gap-3 py-2"
                                >
                                    <div class="min-w-0 flex-1">
                                        <p class="truncate text-sm">
                                            <span class="font-medium">{{
                                                l.worker
                                            }}</span>
                                            · {{ t(l.work_type) }}
                                        </p>
                                        <p
                                            class="truncate text-xs text-muted-foreground"
                                        >
                                            {{ t(l.center)
                                            }}<template v-if="l.season">
                                                · {{ l.season }}</template
                                            >
                                            ·
                                            <span class="num">{{
                                                t(':n দিন × :rate', {
                                                    n: daysText(l.days),
                                                    rate: taka(l.rate),
                                                })
                                            }}</span>
                                        </p>
                                    </div>
                                    <div class="shrink-0 text-right">
                                        <p class="num text-sm">
                                            {{ taka(l.amount) }}
                                        </p>
                                        <p
                                            :class="[
                                                'text-[11px]',
                                                l.paid
                                                    ? 'text-paddy'
                                                    : 'text-clay',
                                            ]"
                                        >
                                            {{
                                                l.paid
                                                    ? t('দেওয়া হয়েছে')
                                                    : t('বাকি')
                                            }}
                                        </p>
                                    </div>
                                    <button
                                        v-if="canLog && l.deletable"
                                        type="button"
                                        class="btn-icon hover:text-destructive"
                                        :disabled="removing === l.id"
                                        :aria-label="t('মুছুন')"
                                        :title="t('মুছুন')"
                                        @click="removeLog(l, g.date)"
                                    >
                                        <Trash2 class="size-4" />
                                    </button>
                                </li>
                            </ul>
                        </div>
                    </div>
                    <p
                        v-else
                        class="py-8 text-center text-sm text-muted-foreground"
                    >
                        {{ t('গত ৩০ দিনে কোনো কাজ লেখা হয়নি।') }}
                    </p>
                </section>
            </div>
        </div>
    </div>
</template>
