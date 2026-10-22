<script setup lang="ts">
import { Head, useForm } from '@inertiajs/vue3';
import {
    ArrowDownLeft,
    ArrowUpRight,
    CalendarClock,
    Check,
    ChevronDown,
    HandCoins,
    History,
    Lightbulb,
    ListOrdered,
    MessageCircle,
    Pencil,
    Phone,
    Plus,
    Trash2,
    X,
} from '@lucide/vue';
import { createReusableTemplate } from '@vueuse/core';
import { computed, reactive, ref, watch } from 'vue';
import DateField from '@/components/khamar/DateField.vue';
import Field from '@/components/khamar/Field.vue';
import NSelect from '@/components/khamar/NSelect.vue';
import PageTitle from '@/components/khamar/PageTitle.vue';
import StatTile from '@/components/khamar/StatTile.vue';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { t } from '@/lib/i18n';
import { day, taka, today, useCan } from '@/lib/khamar';

defineOptions({
    layout: { breadcrumbs: [{ title: 'ধার ও জামানত', href: '/debts' }] },
});

type Direction = 'out' | 'in';
type Kind = 'more' | 'settle';

type Installment = {
    id: number;
    due_on: string;
    amount: number;
    paid: number;
    left: number;
    overdue: boolean;
};

type Card = {
    id: number;
    direction: Direction;
    person_name: string;
    phone: string | null;
    purpose: string | null;
    party_id: number | null;
    started_on: string;
    due_on: string | null;
    closed_on: string | null;
    notes: string | null;
    balance: number;
    next_due_on: string | null;
    next_amount: number | null;
    days_left: number | null;
    installments: Installment[];
    history: {
        id: number;
        number: string;
        date: string;
        description: string;
        amount: number;
    }[];
};

type Party = { id: number; name: string; phone: string | null; type: string };

const props = defineProps<{
    debts: Card[];
    totals: { out: number; in: number };
    directions: Record<string, string>;
    moneyAccounts: { id: number; name: string }[];
    parties: Party[];
}>();

const can = useCan();
const canWrite = computed(() => !!can.value['post-entries']);

const firstMoney = (): number | null => props.moneyAccounts[0]?.id ?? null;
const toPoisha = (value: string | number | null | undefined): number =>
    Math.round((Number(value) || 0) * 100);

/* ---------- dates ---------- */
function addMonths(ymd: string, months: number): string {
    const [y, m, d] = ymd.split('-').map(Number);
    const base = new Date(Date.UTC(y, m - 1 + months, 1));
    const last = new Date(
        Date.UTC(base.getUTCFullYear(), base.getUTCMonth() + 1, 0),
    ).getUTCDate();
    base.setUTCDate(Math.min(d, last));

    return base.toISOString().slice(0, 10);
}

function dueChip(c: Card): { text: string; tone: string } | null {
    if (c.days_left === null || c.balance <= 0) {
        return null;
    }

    const n = c.days_left;

    if (n < 0) {
        return {
            text: t(':n দিন পার হয়ে গেছে', { n: -n }),
            tone: 'bg-destructive/10 text-destructive',
        };
    }

    if (n === 0) {
        return { text: t('আজ'), tone: 'bg-destructive/10 text-destructive' };
    }

    if (n <= 3) {
        return {
            text: t(':n দিন পর', { n }),
            tone: 'bg-turmeric/25 text-soil',
        };
    }

    return {
        text: t(':n দিন বাকি', { n }),
        tone: 'bg-muted text-muted-foreground',
    };
}

/* ---------- lists & summary ---------- */
const tab = ref<Direction>('out');
const openOnes = computed(() =>
    props.debts.filter((d) => !d.closed_on && d.direction === tab.value),
);
const closedOnes = computed(() =>
    props.debts.filter((d) => !!d.closed_on && d.direction === tab.value),
);
const showClosed = ref(false);

const soonest = computed(
    () =>
        props.debts
            .filter((d) => !d.closed_on && d.next_due_on && d.balance > 0)
            .sort((a, b) =>
                (a.next_due_on ?? '').localeCompare(b.next_due_on ?? ''),
            )[0] ?? null,
);

const soonestHint = computed(() => {
    const s = soonest.value;

    if (!s) {
        return t('কোনো তারিখ ঠিক করা নেই');
    }

    return `${s.person_name} · ${day(s.next_due_on)} · ${dueChip(s)?.text ?? ''}`;
});

// Every entry on the debt's account is either "more" or "settle", so the sum of all of them is
// given + returned and the balance is given − returned.
function progress(c: Card): { settled: number; total: number; pct: number } {
    const moved = c.history.reduce((sum, h) => sum + h.amount, 0);
    const settled = Math.max(0, Math.round((moved - c.balance) / 2));
    const total = c.balance + settled;

    return {
        settled,
        total,
        pct: total > 0 ? Math.round((settled / total) * 100) : 0,
    };
}

/* ---------- phone & WhatsApp ---------- */
function waNumber(phone: string | null): string | null {
    const digits = (phone ?? '').replace(/\D/g, '');

    if (digits.startsWith('880') && digits.length === 13) {
        return digits;
    }

    if (digits.startsWith('01') && digits.length === 11) {
        return `88${digits}`;
    }

    if (digits.startsWith('1') && digits.length === 10) {
        return `880${digits}`;
    }

    return null;
}

// The message goes to the person, who reads Bangla, so it stays in Bangla in both languages.
function waLink(c: Card): string | null {
    const number = waNumber(c.phone);

    if (!number) {
        return null;
    }

    const amount = taka(c.next_amount ?? c.balance);
    const when = c.next_due_on ? day(c.next_due_on) : null;
    const text =
        c.direction === 'out'
            ? `আসসালামু আলাইকুম, ${c.person_name}। খামার থেকে বলছি। ${
                  when
                      ? `আপনার কাছে ${amount} ফেরতের তারিখ ${when}।`
                      : `আপনার কাছে ${amount} পাওনা আছে।`
              } সুবিধামতো সময়ে ফেরত দিলে খুব উপকার হয়। ধন্যবাদ।`
            : `আসসালামু আলাইকুম, ${c.person_name}। খামার থেকে বলছি। ${
                  when
                      ? `আপনার কাছ থেকে নেওয়া টাকার পরের কিস্তি ${amount} আমরা ${when} তারিখে দেবো, ইনশাআল্লাহ।`
                      : `আপনার কাছ থেকে নেওয়া ${amount} সময়মতো ফেরত দেবো, ইনশাআল্লাহ।`
              } ধন্যবাদ।`;

    return `https://wa.me/${number}?text=${encodeURIComponent(text)}`;
}

/* ---------- labels per direction ---------- */
const kindLabel = (dir: Direction, kind: Kind): string =>
    dir === 'out'
        ? kind === 'more'
            ? t('আরও দিলাম')
            : t('ফেরত পেলাম')
        : kind === 'more'
          ? t('আরও নিলাম')
          : t('কিস্তি দিলাম / ফেরত দিলাম');

// Money leaves us when we give (out+more) or pay back (in+settle).
const accountLabel = (dir: Direction, kind: Kind): string =>
    (dir === 'out') === (kind === 'more')
        ? t('কোন হিসাব থেকে দিলাম')
        : t('কোন হিসাবে টাকা এলো');

/* ---------- instalment planner (one at a time: the new form or a card's plan editor) ---------- */
type Row = { due_on: string | null; amount: string | number };

const planner = reactive({
    mode: 'once' as 'once' | 'split',
    due_on: null as string | null,
    count: 3 as string | number,
    first: null as string | null,
    every: 1 as string | number,
    rows: [] as Row[],
});
const plannerTarget = ref(0); // poisha to spread
const plannerMin = ref<string | undefined>(undefined);

const rowsTotal = computed(() =>
    planner.rows.reduce((sum, r) => sum + toPoisha(r.amount), 0),
);
const rowsMatch = computed(() => rowsTotal.value === plannerTarget.value);

function resetPlanner(target: number, min?: string) {
    planner.mode = 'once';
    planner.due_on = null;
    planner.count = 3;
    planner.first = null;
    planner.every = 1;
    planner.rows = [];
    plannerTarget.value = target;
    plannerMin.value = min;
}

function generateRows() {
    const count = Math.max(
        1,
        Math.min(120, Math.floor(Number(planner.count) || 1)),
    );
    const every = Math.max(1, Math.floor(Number(planner.every) || 1));
    const first = planner.first || addMonths(today(), 1);
    const total = plannerTarget.value;
    // Equal whole-taka rows; the last one takes what is left.
    const each = Math.floor(total / count / 100) * 100;
    const rows: Row[] = [];

    for (let i = 0; i < count; i++) {
        const amount = i === count - 1 ? total - each * (count - 1) : each;
        rows.push({
            due_on: addMonths(first, i * every),
            amount: amount / 100,
        });
    }

    planner.rows = rows;
}

const addRow = () => {
    const last = planner.rows[planner.rows.length - 1]?.due_on;
    planner.rows.push({
        due_on: last
            ? addMonths(last, Math.max(1, Number(planner.every) || 1))
            : null,
        amount: '',
    });
};

function plannerPayload(): {
    due_on: string | null;
    installments: { due_on: string; amount: number }[];
} {
    if (planner.mode === 'once') {
        return { due_on: planner.due_on || null, installments: [] };
    }

    const installments = planner.rows
        .filter((r) => r.due_on && toPoisha(r.amount) > 0)
        .map((r) => ({ due_on: r.due_on as string, amount: Number(r.amount) }))
        .sort((a, b) => a.due_on.localeCompare(b.due_on));

    return {
        due_on: installments[installments.length - 1]?.due_on ?? null,
        installments,
    };
}

const [DefinePlanner, ReusePlanner] = createReusableTemplate();
const [DefineCard, ReuseCard] = createReusableTemplate<{ c: Card }>();

/* ---------- new ---------- */
const newOpen = ref(false);
const partyPick = ref<number | null>(null);
const newForm = useForm({
    direction: 'out' as Direction,
    person_name: '',
    phone: '',
    purpose: '',
    party_id: null as number | null,
    date: today(),
    amount: '' as string | number,
    money_account_id: firstMoney(),
    notes: '',
});

watch(partyPick, (id) => {
    const party = props.parties.find((p) => p.id === id);
    newForm.party_id = party?.id ?? null;

    if (party) {
        newForm.person_name = party.name;
        newForm.phone = party.phone ?? '';
    }
});

watch(
    () => newForm.amount,
    (amount) => {
        if (newOpen.value) {
            plannerTarget.value = toPoisha(amount);
        }
    },
);
watch(
    () => newForm.date,
    (date) => {
        if (newOpen.value) {
            plannerMin.value = date;
        }
    },
);

function openNew() {
    planFor.value = null;
    newForm.reset();
    newForm.clearErrors();
    partyPick.value = null;
    resetPlanner(0, newForm.date);
    newOpen.value = !newOpen.value;
}

const errorStartingWith = (
    errors: Record<string, string | undefined>,
    prefix: string,
): string | undefined => {
    const key = Object.keys(errors).find((k) => k.startsWith(prefix));

    return key ? errors[key] : undefined;
};

const newErrors = computed(
    () => newForm.errors as Record<string, string | undefined>,
);

const canSaveNew = computed(
    () =>
        !newForm.processing &&
        newForm.person_name.trim() !== '' &&
        toPoisha(newForm.amount) > 0 &&
        newForm.money_account_id !== null &&
        (planner.mode === 'once' ||
            planner.rows.length === 0 ||
            rowsMatch.value),
);

function saveNew() {
    const plan = plannerPayload();

    newForm
        .transform((data) => ({
            ...data,
            person_name: data.person_name.trim(),
            phone: data.phone.trim() || null,
            purpose: data.purpose.trim() || null,
            notes: data.notes.trim() || null,
            amount: Number(data.amount),
            due_on: plan.due_on,
            installments: plan.installments,
        }))
        .post('/debts', {
            preserveScroll: true,
            onSuccess: () => {
                tab.value = newForm.direction;
                newOpen.value = false;
                newForm.reset();
                partyPick.value = null;
            },
        });
}

/* ---------- give more / settle ---------- */
const moveFor = ref<{ id: number; kind: Kind } | null>(null);
const moveForm = useForm({
    kind: 'settle' as Kind,
    amount: '' as string | number,
    money_account_id: firstMoney(),
    date: today(),
    note: '',
});

function openMove(c: Card, kind: Kind) {
    if (moveFor.value?.id === c.id && moveFor.value.kind === kind) {
        moveFor.value = null;

        return;
    }

    moveForm.clearErrors();
    moveForm.kind = kind;
    moveForm.amount =
        kind === 'settle' ? (c.next_amount ?? c.balance) / 100 : '';
    moveForm.date = today();
    moveForm.note = '';
    moveForm.money_account_id ??= firstMoney();
    moveFor.value = { id: c.id, kind };
}

function saveMove(c: Card) {
    moveForm
        .transform((data) => ({
            ...data,
            amount: Number(data.amount),
            note: data.note.trim() || null,
        }))
        .post(`/debts/${c.id}/move`, {
            preserveScroll: true,
            onSuccess: () => (moveFor.value = null),
        });
}

/* ---------- plan editor ---------- */
const planFor = ref<number | null>(null);
const planForm = useForm({ phone: '', notes: '' });
const planErrors = computed(
    () => planForm.errors as Record<string, string | undefined>,
);

// Rows already partly paid stay as they are; only untouched ones are replaced.
const keptLeft = (c: Card): number =>
    c.installments
        .filter((i) => i.paid > 0)
        .reduce((sum, i) => sum + i.left, 0);

function openPlan(c: Card) {
    if (planFor.value === c.id) {
        planFor.value = null;

        return;
    }

    newOpen.value = false;
    moveFor.value = null;
    planForm.clearErrors();
    planForm.phone = c.phone ?? '';
    planForm.notes = c.notes ?? '';
    resetPlanner(Math.max(0, c.balance - keptLeft(c)), today());

    const untouched = c.installments.filter((i) => i.paid === 0);

    if (untouched.length) {
        planner.mode = 'split';
        planner.rows = untouched.map((i) => ({
            due_on: i.due_on,
            amount: i.amount / 100,
        }));
        planner.count = untouched.length;
        planner.first = untouched[0].due_on;
    } else {
        planner.due_on = c.due_on;
    }

    planFor.value = c.id;
}

function savePlan(c: Card) {
    const plan = plannerPayload();

    planForm
        .transform((data) => ({
            phone: data.phone.trim() || null,
            notes: data.notes.trim() || null,
            due_on: plan.due_on,
            installments: plan.installments,
        }))
        .put(`/debts/${c.id}/plan`, {
            preserveScroll: true,
            onSuccess: () => (planFor.value = null),
        });
}

const historyOpen = ref<number | null>(null);

const tabs: { key: Direction; label: string; icon: typeof ArrowUpRight }[] = [
    { key: 'out', label: 'আমরা দিলাম — ফেরত পাবো', icon: ArrowUpRight },
    { key: 'in', label: 'আমরা নিলাম — ফেরত দিতে হবে', icon: ArrowDownLeft },
];
const countOf = (dir: Direction): number =>
    props.debts.filter((d) => d.direction === dir && !d.closed_on).length;
</script>

<template>
    <Head :title="t('ধার ও জামানত')" />

    <!-- Shared "when does it come back" editor: one date, or instalments. -->
    <DefinePlanner>
        <div
            class="grid gap-3 rounded-lg border border-border bg-background/60 p-3"
        >
            <div class="grid grid-cols-2 gap-1 rounded-lg bg-muted p-1">
                <button
                    v-for="m in ['once', 'split'] as const"
                    :key="m"
                    type="button"
                    :class="[
                        'h-9 rounded-md px-2 text-sm font-medium transition-colors',
                        planner.mode === m
                            ? 'bg-card text-foreground shadow-sm'
                            : 'text-muted-foreground hover:text-foreground',
                    ]"
                    @click="planner.mode = m"
                >
                    {{ m === 'once' ? t('একবারে — তারিখ') : t('কিস্তিতে') }}
                </button>
            </div>

            <Field
                v-if="planner.mode === 'once'"
                :label="t('কবে ফেরত')"
                :hint="t('ফাঁকা রাখলে কোনো তারিখ ধরা হবে না')"
            >
                <DateField
                    v-model="planner.due_on"
                    :min="plannerMin"
                    class="h-10"
                    clearable
                />
            </Field>

            <template v-else>
                <div class="grid grid-cols-3 gap-2">
                    <Field :label="t('কয়টি কিস্তি')">
                        <Input
                            v-model="planner.count"
                            type="number"
                            min="1"
                            max="120"
                            inputmode="numeric"
                            class="h-10"
                        />
                    </Field>
                    <Field :label="t('প্রথম কিস্তি')">
                        <DateField
                            v-model="planner.first"
                            :min="plannerMin"
                            class="h-10"
                        />
                    </Field>
                    <Field :label="t('প্রতি কত মাসে')">
                        <Input
                            v-model="planner.every"
                            type="number"
                            min="1"
                            max="24"
                            inputmode="numeric"
                            class="h-10"
                        />
                    </Field>
                </div>
                <Button
                    type="button"
                    variant="outline"
                    :disabled="plannerTarget <= 0"
                    @click="generateRows"
                >
                    <ListOrdered class="size-4" />
                    {{ t('সমান ভাগে কিস্তি বানান') }}
                </Button>

                <ul v-if="planner.rows.length" class="grid gap-2">
                    <li
                        v-for="(row, i) in planner.rows"
                        :key="i"
                        class="flex items-center gap-2"
                    >
                        <span
                            class="num w-6 shrink-0 text-center text-xs text-muted-foreground"
                            >{{ i + 1 }}</span
                        >
                        <DateField
                            v-model="row.due_on"
                            :min="plannerMin"
                            class="h-10 min-w-0 flex-1"
                        />
                        <Input
                            v-model="row.amount"
                            type="number"
                            min="0"
                            step="1"
                            inputmode="decimal"
                            class="h-10 w-28 shrink-0 text-right"
                            :aria-label="t('টাকা')"
                        />
                        <button
                            type="button"
                            class="btn-icon hover:text-destructive"
                            :aria-label="t('মুছুন')"
                            :title="t('মুছুন')"
                            @click="planner.rows.splice(i, 1)"
                        >
                            <Trash2 class="size-4" />
                        </button>
                    </li>
                </ul>
                <button
                    type="button"
                    class="btn-chip justify-self-start"
                    @click="addRow"
                >
                    <Plus /> {{ t('আরেকটি কিস্তি') }}
                </button>

                <p
                    v-if="planner.rows.length"
                    :class="[
                        'num rounded-md px-2.5 py-1.5 text-xs',
                        rowsMatch
                            ? 'bg-paddy/10 text-paddy'
                            : 'bg-destructive/10 text-destructive',
                    ]"
                >
                    {{
                        t('কিস্তির মোট :sum, হওয়ার কথা :target', {
                            sum: taka(rowsTotal),
                            target: taka(plannerTarget),
                        })
                    }}
                    <template v-if="!rowsMatch">
                        ·
                        {{
                            t('পার্থক্য :diff', {
                                diff: taka(plannerTarget - rowsTotal),
                            })
                        }}</template
                    >
                </p>
            </template>
        </div>
    </DefinePlanner>

    <div class="flex flex-1 flex-col p-4 md:p-6">
        <PageTitle
            :title="t('ধার ও জামানত')"
            :subtitle="
                t(
                    'কাকে দিলাম আর কবে ফেরত পাবো, কার কাছ থেকে নিলাম আর কবে ফেরত দিতে হবে — সময়মতো মনে করিয়ে দেবে।',
                )
            "
        >
            <template v-if="canWrite" #actions>
                <Button @click="openNew"><Plus /> {{ t('নতুন লিখুন') }}</Button>
            </template>
        </PageTitle>

        <!-- Summary -->
        <div class="mb-6 grid grid-cols-2 gap-3 lg:grid-cols-3">
            <StatTile
                :label="t('ফেরত পাবো')"
                :value="taka(totals.out)"
                :hint="t(':nটি চলছে', { n: countOf('out') })"
                :icon="ArrowUpRight"
                tone="paddy"
            />
            <StatTile
                :label="t('ফেরত দিতে হবে')"
                :value="taka(totals.in)"
                :hint="t(':nটি চলছে', { n: countOf('in') })"
                :icon="ArrowDownLeft"
                tone="clay"
            />
            <div class="col-span-2 lg:col-span-1">
                <StatTile
                    :label="
                        soonest
                            ? soonest.direction === 'out'
                                ? t('সামনের কিস্তি — ফেরত পাবো')
                                : t('সামনের কিস্তি — দিতে হবে')
                            : t('সামনের কিস্তি')
                    "
                    :value="
                        soonest
                            ? taka(soonest.next_amount ?? soonest.balance)
                            : '—'
                    "
                    :hint="soonestHint"
                    :icon="CalendarClock"
                    :tone="
                        soonest && (soonest.days_left ?? 99) <= 3
                            ? 'turmeric'
                            : 'pond'
                    "
                />
            </div>
        </div>

        <!-- New -->
        <form
            v-if="newOpen && canWrite"
            class="mb-6 rounded-xl border border-border bg-card p-4"
            @submit.prevent="saveNew"
        >
            <div class="mb-3 flex items-center justify-between gap-2">
                <h2 class="flex items-center gap-2 font-display text-lg">
                    <HandCoins class="size-5 text-paddy" />
                    {{ t('নতুন লিখুন') }}
                </h2>
                <button
                    type="button"
                    class="btn-icon"
                    :aria-label="t('বন্ধ করুন')"
                    :title="t('বন্ধ করুন')"
                    @click="newOpen = false"
                >
                    <X class="size-4" />
                </button>
            </div>

            <div class="grid gap-4 md:grid-cols-2">
                <div class="grid content-start gap-3">
                    <div class="grid grid-cols-2 gap-1 rounded-lg bg-muted p-1">
                        <button
                            v-for="d in tabs"
                            :key="d.key"
                            type="button"
                            :class="[
                                'flex flex-col items-start rounded-md px-3 py-2 text-left transition-colors',
                                newForm.direction === d.key
                                    ? 'bg-card shadow-sm'
                                    : 'text-muted-foreground hover:text-foreground',
                            ]"
                            @click="newForm.direction = d.key"
                        >
                            <span
                                :class="[
                                    'flex items-center gap-1 text-sm font-semibold',
                                    newForm.direction === d.key &&
                                        (d.key === 'out'
                                            ? 'text-paddy'
                                            : 'text-clay'),
                                ]"
                                ><component :is="d.icon" class="size-4" />{{
                                    d.key === 'out'
                                        ? t('আমরা দিলাম')
                                        : t('আমরা নিলাম')
                                }}</span
                            >
                            <span class="text-[11px]">{{
                                d.key === 'out'
                                    ? t('পরে ফেরত পাবো')
                                    : t('পরে ফেরত দিতে হবে')
                            }}</span>
                        </button>
                    </div>

                    <Field
                        v-if="parties.length"
                        :label="t('তালিকা থেকে বাছুন (ইচ্ছামতো)')"
                    >
                        <NSelect
                            v-model="partyPick"
                            :placeholder="t('তালিকায় নেই — নিচে লিখুন')"
                        >
                            <option :value="null">
                                {{ t('তালিকায় নেই — নিচে লিখুন') }}
                            </option>
                            <option
                                v-for="p in parties"
                                :key="p.id"
                                :value="p.id"
                            >
                                {{ p.name }}{{ p.phone ? ` · ${p.phone}` : '' }}
                            </option>
                        </NSelect>
                    </Field>

                    <div class="grid grid-cols-2 gap-3">
                        <Field
                            :label="
                                newForm.direction === 'out'
                                    ? t('কাকে দিলাম')
                                    : t('কার কাছ থেকে নিলাম')
                            "
                            :error="newErrors.person_name"
                        >
                            <Input
                                v-model="newForm.person_name"
                                class="h-10"
                                required
                            />
                        </Field>
                        <Field :label="t('ফোন')" :error="newErrors.phone">
                            <Input
                                v-model="newForm.phone"
                                type="tel"
                                inputmode="tel"
                                class="h-10"
                                :placeholder="t('০১৭…')"
                            />
                        </Field>
                    </div>

                    <Field :label="t('কী কারণে')" :error="newErrors.purpose">
                        <Input
                            v-model="newForm.purpose"
                            class="h-10"
                            :placeholder="
                                t('যেমন: জমির জামানত, বিনিয়োগকারীর ধার, আগাম')
                            "
                        />
                    </Field>

                    <div class="grid grid-cols-2 gap-3">
                        <Field :label="t('টাকা (৳)')" :error="newErrors.amount">
                            <Input
                                v-model="newForm.amount"
                                type="number"
                                min="0"
                                step="1"
                                inputmode="decimal"
                                class="h-10"
                                required
                            />
                        </Field>
                        <Field :label="t('তারিখ')" :error="newErrors.date">
                            <DateField
                                v-model="newForm.date"
                                :max="today()"
                                class="h-10"
                                required
                            />
                        </Field>
                    </div>

                    <Field
                        :label="accountLabel(newForm.direction, 'more')"
                        :error="newErrors.money_account_id"
                    >
                        <NSelect
                            v-model="newForm.money_account_id"
                            :placeholder="t('হিসাব বাছুন')"
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

                <div class="grid content-start gap-3">
                    <Field
                        :label="t('কবে ফেরত')"
                        :error="
                            newErrors.due_on ??
                            errorStartingWith(newErrors, 'installments')
                        "
                    >
                        <ReusePlanner />
                    </Field>

                    <Field
                        :label="t('নোট (ইচ্ছামতো)')"
                        :error="newErrors.notes"
                    >
                        <Input v-model="newForm.notes" class="h-10" />
                    </Field>

                    <Button type="submit" :disabled="!canSaveNew">
                        <Check class="size-4" /> {{ t('সংরক্ষণ') }}
                    </Button>
                </div>
            </div>
        </form>

        <!-- Tabs -->
        <div class="mb-4 grid grid-cols-2 gap-1 rounded-xl bg-muted p-1">
            <button
                v-for="d in tabs"
                :key="d.key"
                type="button"
                :class="[
                    'flex items-center justify-center gap-1.5 rounded-lg px-2 py-2 text-center text-xs font-medium transition-colors sm:text-sm',
                    tab === d.key
                        ? 'bg-card text-foreground shadow-sm'
                        : 'text-muted-foreground hover:text-foreground',
                ]"
                @click="tab = d.key"
            >
                <component
                    :is="d.icon"
                    :class="[
                        'size-4 shrink-0',
                        d.key === 'out' ? 'text-paddy' : 'text-clay',
                    ]"
                />
                <span>{{ t(d.label) }}</span>
                <span class="num rounded-full bg-muted px-1.5 text-[11px]">{{
                    countOf(d.key)
                }}</span>
            </button>
        </div>

        <DefineCard v-slot="{ c }">
            <article
                :class="[
                    'min-w-0 rounded-xl border border-border bg-card p-4',
                    c.closed_on && 'opacity-75',
                ]"
            >
                <!-- Head -->
                <div class="flex items-start justify-between gap-3">
                    <div class="min-w-0">
                        <h3
                            class="truncate text-base leading-snug font-semibold"
                        >
                            {{ c.person_name }}
                        </h3>
                        <p
                            v-if="c.purpose"
                            class="truncate text-xs text-muted-foreground"
                        >
                            {{ c.purpose }}
                        </p>
                        <p class="num mt-0.5 text-[11px] text-muted-foreground">
                            {{ t('শুরু :date', { date: day(c.started_on) }) }}
                            <template v-if="c.closed_on">
                                ·
                                {{
                                    t('শোধ :date', { date: day(c.closed_on) })
                                }}</template
                            >
                        </p>
                    </div>
                    <div class="shrink-0 text-right">
                        <p class="text-[11px] text-muted-foreground">
                            {{
                                c.direction === 'out'
                                    ? t('ফেরত পাবো')
                                    : t('ফেরত দিতে হবে')
                            }}
                        </p>
                        <p
                            :class="[
                                'num text-xl font-semibold tracking-tight',
                                c.direction === 'out'
                                    ? 'text-paddy'
                                    : 'text-clay',
                            ]"
                        >
                            {{ taka(c.balance) }}
                        </p>
                    </div>
                </div>

                <!-- Contact -->
                <div v-if="c.phone" class="mt-2 flex flex-wrap gap-1.5 text-xs">
                    <a
                        :href="`tel:${c.phone}`"
                        class="num inline-flex items-center gap-1 rounded-full bg-pond/10 px-2 py-0.5 text-pond hover:bg-pond/20"
                        ><Phone class="size-3" />{{ c.phone }}</a
                    >
                    <a
                        v-if="waLink(c) && !c.closed_on"
                        :href="waLink(c)!"
                        target="_blank"
                        rel="noopener"
                        class="inline-flex items-center gap-1 rounded-full bg-paddy/10 px-2 py-0.5 text-paddy hover:bg-paddy/20"
                        ><MessageCircle class="size-3" />{{
                            c.direction === 'out'
                                ? t('হোয়াটসঅ্যাপে মনে করিয়ে দিন')
                                : t('হোয়াটসঅ্যাপে জানান')
                        }}</a
                    >
                </div>

                <!-- Next due -->
                <div
                    v-if="c.next_due_on && dueChip(c)"
                    class="mt-3 flex flex-wrap items-center gap-1.5 text-xs"
                >
                    <span class="text-muted-foreground">{{
                        t('পরের তারিখ:')
                    }}</span>
                    <span class="num font-medium">{{
                        day(c.next_due_on)
                    }}</span>
                    <span v-if="c.next_amount" class="num font-semibold"
                        >· {{ taka(c.next_amount) }}</span
                    >
                    <span
                        :class="[
                            'num rounded-full px-2 py-0.5 font-medium',
                            dueChip(c)!.tone,
                        ]"
                        >{{ dueChip(c)!.text }}</span
                    >
                </div>
                <p
                    v-else-if="!c.closed_on"
                    class="mt-3 text-xs text-muted-foreground"
                >
                    {{ t('ফেরতের কোনো তারিখ ঠিক করা নেই') }}
                </p>

                <!-- Progress -->
                <div v-if="progress(c).total > 0" class="mt-3">
                    <div class="h-2 overflow-hidden rounded-full bg-muted">
                        <div
                            :class="[
                                'h-full rounded-full',
                                c.direction === 'out' ? 'bg-paddy' : 'bg-clay',
                            ]"
                            :style="{ width: `${progress(c).pct}%` }"
                        />
                    </div>
                    <p
                        class="num mt-1 flex justify-between text-[11px] text-muted-foreground"
                    >
                        <span>{{
                            t('ফেরত হয়েছে :amount', {
                                amount: taka(progress(c).settled),
                            })
                        }}</span>
                        <span>{{
                            t('মোট :amount', {
                                amount: taka(progress(c).total),
                            })
                        }}</span>
                    </p>
                </div>

                <!-- Instalments -->
                <ul
                    v-if="c.installments.length"
                    class="mt-3 divide-y divide-border/60 rounded-lg border border-border text-xs"
                >
                    <li
                        v-for="i in c.installments"
                        :key="i.id"
                        :class="[
                            'flex items-center justify-between gap-2 px-3 py-1.5',
                            i.overdue && 'bg-destructive/5',
                        ]"
                    >
                        <span
                            :class="[
                                'num',
                                i.overdue && 'font-medium text-destructive',
                            ]"
                            >{{ day(i.due_on) }}</span
                        >
                        <span class="num text-right">
                            <span class="font-medium">{{
                                taka(i.amount)
                            }}</span>
                            <span v-if="i.left === 0" class="ml-1.5 text-paddy">
                                <Check class="inline size-3" />
                                {{ t('শোধ') }}</span
                            >
                            <span
                                v-else-if="i.paid > 0"
                                class="ml-1.5 text-muted-foreground"
                                >{{
                                    t('বাকি :amount', { amount: taka(i.left) })
                                }}</span
                            >
                        </span>
                    </li>
                </ul>

                <p v-if="c.notes" class="mt-2 text-xs text-muted-foreground">
                    {{ c.notes }}
                </p>

                <!-- Actions -->
                <div
                    v-if="canWrite"
                    class="mt-3 flex flex-wrap gap-2 border-t border-border/70 pt-3"
                >
                    <Button
                        v-if="!c.closed_on"
                        size="sm"
                        :variant="
                            moveFor?.id === c.id && moveFor.kind === 'settle'
                                ? 'default'
                                : 'outline'
                        "
                        @click="openMove(c, 'settle')"
                    >
                        <Check class="size-4" />
                        {{ kindLabel(c.direction, 'settle') }}
                    </Button>
                    <Button
                        size="sm"
                        :variant="
                            moveFor?.id === c.id && moveFor.kind === 'more'
                                ? 'default'
                                : 'outline'
                        "
                        @click="openMove(c, 'more')"
                    >
                        <Plus class="size-4" />
                        {{ kindLabel(c.direction, 'more') }}
                    </Button>
                    <Button
                        v-if="!c.closed_on"
                        size="sm"
                        :variant="planFor === c.id ? 'default' : 'outline'"
                        @click="openPlan(c)"
                    >
                        <Pencil class="size-4" /> {{ t('পরিকল্পনা বদলান') }}
                    </Button>
                </div>

                <!-- Move form -->
                <form
                    v-if="moveFor?.id === c.id"
                    class="mt-3 grid gap-3 rounded-lg border border-primary/40 bg-background/60 p-3"
                    @submit.prevent="saveMove(c)"
                >
                    <h4 class="text-sm font-semibold">
                        {{ kindLabel(c.direction, moveFor.kind) }}
                    </h4>
                    <div class="grid grid-cols-2 gap-3">
                        <Field
                            :label="t('টাকা (৳)')"
                            :error="moveForm.errors.amount"
                            :hint="
                                moveFor.kind === 'settle'
                                    ? t('বাকি :amount', {
                                          amount: taka(c.balance),
                                      })
                                    : undefined
                            "
                        >
                            <Input
                                v-model="moveForm.amount"
                                type="number"
                                min="0"
                                step="1"
                                inputmode="decimal"
                                class="h-10"
                                required
                            />
                        </Field>
                        <Field
                            :label="t('তারিখ')"
                            :error="moveForm.errors.date"
                        >
                            <DateField
                                v-model="moveForm.date"
                                :min="c.started_on"
                                :max="today()"
                                class="h-10"
                                required
                            />
                        </Field>
                    </div>
                    <Field
                        :label="accountLabel(c.direction, moveFor.kind)"
                        :error="moveForm.errors.money_account_id"
                    >
                        <NSelect
                            v-model="moveForm.money_account_id"
                            :placeholder="t('হিসাব বাছুন')"
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
                    <Field
                        :label="t('নোট (ইচ্ছামতো)')"
                        :error="moveForm.errors.note"
                    >
                        <Input v-model="moveForm.note" class="h-10" />
                    </Field>
                    <div class="flex gap-2">
                        <Button
                            type="submit"
                            class="flex-1"
                            :disabled="
                                moveForm.processing ||
                                toPoisha(moveForm.amount) <= 0 ||
                                !moveForm.money_account_id
                            "
                        >
                            <Check class="size-4" /> {{ t('লিখুন') }}
                        </Button>
                        <Button
                            type="button"
                            variant="outline"
                            @click="moveFor = null"
                            >{{ t('বাতিল') }}</Button
                        >
                    </div>
                </form>

                <!-- Plan editor -->
                <form
                    v-if="planFor === c.id"
                    class="mt-3 grid gap-3 rounded-lg border border-primary/40 bg-background/60 p-3"
                    @submit.prevent="savePlan(c)"
                >
                    <h4 class="text-sm font-semibold">
                        {{ t('ফেরতের পরিকল্পনা') }}
                    </h4>
                    <p
                        v-if="keptLeft(c) > 0"
                        class="text-xs text-muted-foreground"
                    >
                        {{
                            t(
                                'কিছুটা শোধ হওয়া কিস্তি (:amount) যেমন আছে থাকবে।',
                                {
                                    amount: taka(keptLeft(c)),
                                },
                            )
                        }}
                    </p>
                    <Field
                        :label="t('কবে ফেরত')"
                        :error="
                            planErrors.due_on ??
                            errorStartingWith(planErrors, 'installments')
                        "
                    >
                        <ReusePlanner />
                    </Field>
                    <div class="grid grid-cols-2 gap-3">
                        <Field :label="t('ফোন')" :error="planErrors.phone">
                            <Input
                                v-model="planForm.phone"
                                type="tel"
                                inputmode="tel"
                                class="h-10"
                            />
                        </Field>
                        <Field
                            :label="t('নোট (ইচ্ছামতো)')"
                            :error="planErrors.notes"
                        >
                            <Input v-model="planForm.notes" class="h-10" />
                        </Field>
                    </div>
                    <div class="flex gap-2">
                        <Button
                            type="submit"
                            class="flex-1"
                            :disabled="
                                planForm.processing ||
                                (planner.mode === 'split' &&
                                    planner.rows.length > 0 &&
                                    !rowsMatch)
                            "
                        >
                            <Check class="size-4" /> {{ t('সংরক্ষণ') }}
                        </Button>
                        <Button
                            type="button"
                            variant="outline"
                            @click="planFor = null"
                            >{{ t('বাতিল') }}</Button
                        >
                    </div>
                </form>

                <!-- History -->
                <button
                    v-if="c.history.length"
                    type="button"
                    class="btn-chip mt-3"
                    :aria-expanded="historyOpen === c.id"
                    @click="historyOpen = historyOpen === c.id ? null : c.id"
                >
                    <History class="size-3.5" />
                    {{ t('লেনদেন (:n)', { n: c.history.length }) }}
                    <ChevronDown
                        :class="[
                            'size-3.5 transition-transform',
                            historyOpen === c.id && 'rotate-180',
                        ]"
                    />
                </button>
                <ul v-if="historyOpen === c.id" class="mt-2 grid gap-1 text-xs">
                    <li
                        v-for="h in c.history"
                        :key="h.id"
                        class="flex justify-between gap-2"
                    >
                        <span class="min-w-0">
                            <span class="num text-muted-foreground">{{
                                day(h.date)
                            }}</span>
                            ·
                            <span class="break-words">{{ h.description }}</span>
                        </span>
                        <span class="num shrink-0 font-medium">{{
                            taka(h.amount)
                        }}</span>
                    </li>
                </ul>
            </article>
        </DefineCard>

        <div class="grid gap-3 lg:grid-cols-2">
            <ReuseCard v-for="c in openOnes" :key="c.id" :c="c" />
        </div>

        <p
            v-if="!openOnes.length"
            class="flex flex-col items-center gap-2 rounded-xl border border-dashed border-border py-10 text-center text-sm text-muted-foreground"
        >
            <HandCoins class="size-6 opacity-60" />
            {{
                tab === 'out'
                    ? t('কাউকে দেওয়া টাকা বাকি নেই।')
                    : t('কারও কাছে ফেরত দেওয়ার কিছু বাকি নেই।')
            }}
        </p>

        <button
            v-if="closedOnes.length"
            type="button"
            class="btn-chip mt-4 self-start"
            :aria-expanded="showClosed"
            @click="showClosed = !showClosed"
        >
            <ChevronDown
                :class="[
                    'size-4 transition-transform',
                    showClosed && 'rotate-180',
                ]"
            />
            {{ t('শোধ হয়ে গেছে (:n)', { n: closedOnes.length }) }}
        </button>
        <div
            v-if="showClosed && closedOnes.length"
            class="mt-3 grid gap-3 lg:grid-cols-2"
        >
            <ReuseCard v-for="c in closedOnes" :key="c.id" :c="c" />
        </div>

        <p
            class="mt-6 flex items-start gap-2 rounded-lg bg-turmeric/15 px-3 py-2.5 text-xs text-soil"
        >
            <Lightbulb class="mt-0.5 size-4 shrink-0" />
            {{
                t(
                    "এগুলো হাতের টাকার সাথে যোগ হয় না — 'ফেরত পাবো' খামারের সম্পদ, 'ফেরত দিতে হবে' খামারের দেনা। সময় হলে সকালের তালিকা আর নোটিফিকেশনে আসবে।",
                )
            }}
        </p>
    </div>
</template>
