<script setup lang="ts">
import { Head, Link, useForm } from '@inertiajs/vue3';
import {
    CalendarCheck,
    CalendarDays,
    Calculator,
    Check,
    CheckCircle2,
    Clock,
    Coins,
    Lightbulb,
    Moon,
    Pencil,
    Scale,
    ShoppingCart,
    Smartphone,
    Sprout,
    Syringe,
    Truck,
    TrendingDown,
    TrendingUp,
    Wheat,
} from '@lucide/vue';
import { computed, reactive, ref } from 'vue';
import DateField from '@/components/khamar/DateField.vue';
import Field from '@/components/khamar/Field.vue';
import PageTitle from '@/components/khamar/PageTitle.vue';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { t } from '@/lib/i18n';
import { day, taka, today, useCan } from '@/lib/khamar';

defineOptions({
    layout: {
        breadcrumbs: [{ title: 'কোরবানি পরিকল্পনা', href: '/kurbani' }],
    },
});

type AnimalRow = {
    id: number;
    tag: string;
    name: string | null;
    last_kg: number | null;
    last_on: string | null;
    gain: number;
    gain_source: 'own' | 'batch' | 'assumed';
    expected_kg: number | null;
    value: number | null;
    spent: number;
    future_cost: number;
    profit: number | null;
    profit_head: number | null;
};

type BatchPlan = {
    id: number;
    label: string;
    number: number;
    started_on: string;
    target_days: number;
    target_on: string;
    days: number;
    days_by_eid: number;
    days_left: number;
    ready: boolean;
    short_days: number;
    head: number;
    avg_gain: number | null;
    feed_per_day: number;
    feed_own: boolean;
    animals: AnimalRow[];
    weighed: number;
    expected_kg: number;
    spent: number;
    future_cost: number;
    cost_total: number;
    value: number;
    profit: number | null;
    value_head: number | null;
    profit_head: number | null;
};

type Plan = {
    eid: string;
    eid_saved: boolean;
    known_dates: string[];
    today: string;
    days_left: number;
    window: { from: string; to: string; late: string };
    phase: 'early' | 'buy' | 'late' | 'feed' | 'sell';
    price_per_kg: number;
    price_saved: boolean;
    haat: { price: number; date: string; place: string | null } | null;
    assumed_gain: number;
    batches: BatchPlan[];
};

const props = defineProps<{
    plan: Plan;
    calc: {
        animals: number;
        buy_kg: number;
        buy_price: number;
        gain: number;
        feed_per_day: number;
        sale_per_kg: number;
    };
}>();

const can = useCan();

const DAY = 86400000;
const toTime = (date: string): number =>
    Date.parse(`${date.slice(0, 10)}T00:00:00Z`);
const addDays = (date: string, n: number): string =>
    new Date(toTime(date) + n * DAY).toISOString().slice(0, 10);
const between = (from: string, to: string): number =>
    Math.round((toTime(to) - toTime(from)) / DAY);

const kg = (value: number | null | undefined): string =>
    value === null || value === undefined
        ? '—'
        : t(':n কেজি', {
              n: value.toLocaleString('en-IN', { maximumFractionDigits: 1 }),
          });

const phaseText = computed(
    () =>
        ({
            early: t('এখনো কেনার সময় হয়নি'),
            buy: t('এখন কেনার সময়'),
            late: t('দেরিতে কেনা — কম দিন খাওয়ানো যাবে'),
            feed: t('খাওয়ানোর সময় — ওজন বাড়ান'),
            sell: t('বিক্রির প্রস্তুতি নিন'),
        })[props.plan.phase],
);

const phaseTone = computed(
    () =>
        ({
            early: 'bg-soil/10 text-soil',
            buy: 'bg-paddy/12 text-paddy',
            late: 'bg-turmeric/25 text-soil',
            feed: 'bg-pond/12 text-pond',
            sell: 'bg-clay/12 text-clay',
        })[props.plan.phase],
);

// Edit the Eid date and the per-kg price.
const editing = ref(false);
const form = useForm({
    eid_date: props.plan.eid,
    price_per_kg: String(props.plan.price_per_kg / 100) as string | number,
});

function startEdit() {
    form.eid_date = props.plan.eid;
    form.price_per_kg = String(props.plan.price_per_kg / 100);
    form.clearErrors();
    editing.value = true;
}

const save = () =>
    form
        .transform((data) => ({
            eid_date: data.eid_date || null,
            price_per_kg:
                data.price_per_kg === '' ? null : Number(data.price_per_kg),
        }))
        .put('/kurbani/eid', {
            preserveScroll: true,
            onSuccess: () => {
                editing.value = false;
            },
        });

// Timeline: from 150 days before Eid to Eid itself.
const SPAN = 150;
const start = computed(() => addDays(props.plan.eid, -SPAN));
const pos = (date: string): number =>
    Math.min(100, Math.max(0, (between(start.value, date) / SPAN) * 100));
const todayPos = computed(() => pos(props.plan.today));
const segments = computed(() => [
    {
        key: 'buy',
        left: pos(props.plan.window.from),
        width: pos(props.plan.window.to) - pos(props.plan.window.from),
        cls: 'bg-paddy',
    },
    {
        key: 'late',
        left: pos(props.plan.window.to),
        width: pos(props.plan.window.late) - pos(props.plan.window.to),
        cls: 'bg-turmeric',
    },
    {
        key: 'feed',
        left: pos(props.plan.window.late),
        width: 100 - pos(props.plan.window.late),
        cls: 'bg-pond/50',
    },
]);
/* The ring fills over the year since the last Eid; the steps mark where today sits. */
const ring = { length: 2 * Math.PI * 52 };
const ringOffset = computed(
    () =>
        ring.length *
        (1 - Math.min(1, Math.max(0, (365 - props.plan.days_left) / 365))),
);

const steps = computed(() => {
    const w = props.plan.window;
    const list = [
        {
            key: 'buy',
            title: t('কেনার সেরা সময়'),
            note: t(':from – :to (ঈদের ১২০–৯০ দিন আগে)', {
                from: day(w.from),
                to: day(w.to),
            }),
            dot: 'bg-paddy',
        },
        {
            key: 'late',
            title: t('দেরিতে কেনা'),
            note: t(':date পর্যন্ত (৬০ দিন আগে) — বড় গরু কিনুন', {
                date: day(w.late),
            }),
            dot: 'bg-turmeric',
        },
        {
            key: 'feed',
            title: t('খাওয়ানো ও বিক্রি'),
            note: t('শেষ ৬০ দিন — নতুন গরু না কিনে ওজন বাড়ান'),
            dot: 'bg-pond/50',
        },
        {
            key: 'eid',
            title: t('ঈদুল আজহা'),
            note: day(props.plan.eid),
            dot: 'bg-clay',
        },
    ];
    const at =
        props.plan.days_left <= 0
            ? 3
            : { early: 0, buy: 0, late: 1, feed: 2, sell: 2 }[props.plan.phase];

    return list.map((s, i) => ({
        ...s,
        done: i < at,
        now: i === at && props.plan.phase !== 'early',
        next: i === at && props.plan.phase === 'early',
    }));
});

const phaseTasks = computed(
    () =>
        ({
            early: [
                t('হাট ঘুরে গরুর দাম ও মান দেখে রাখুন'),
                t('গোয়ালঘর, পানি আর খাবারের জোগান ঠিক করুন'),
                t('নিচের হিসাব দিয়ে কয়টি গরু কিনবেন ঠিক করুন'),
            ],
            buy: [
                t('সুস্থ, ২–৩ বছরের গরু কিনুন'),
                t('কেনার পরপরই কৃমিনাশক ও টিকা দিন'),
                t('প্রতিটি গরুর কেনার ওজন লিখে রাখুন'),
            ],
            late: [
                t('বড় ও ভারী গরু কিনুন — কম দিনে তৈরি হবে'),
                t('কেনার আগে লাভ-ক্ষতির সমান দর মিলিয়ে নিন'),
                t('কৃমিনাশক দিয়ে দ্রুত খাওয়ানো শুরু করুন'),
            ],
            feed: [
                t('নতুন গরু না কিনে খাওয়ানোয় মন দিন'),
                t('২–৩ সপ্তাহ পরপর ওজন মেপে লিখুন'),
                t('খাবার হঠাৎ না বদলে নিয়মিত রাখুন'),
            ],
            sell: [
                t('হাটের ২–৩ দিন আগে ওজন মাপুন'),
                t('ছবি ও ওজন দিয়ে অনলাইনে বিক্রির পোস্ট দিন'),
                t('পরিবহন ও হাসিলের খরচ হিসাবে ধরুন'),
            ],
        })[props.plan.phase],
);

const batchName = (b: BatchPlan): string =>
    b.label === `ব্যাচ-${b.number}` ? t('ব্যাচ-:n', { n: b.number }) : b.label;

const fedPct = (b: BatchPlan): number =>
    b.target_days > 0 ? Math.min(100, (b.days / b.target_days) * 100) : 0;
const eidPct = (b: BatchPlan): number =>
    b.target_days > 0
        ? Math.min(100, (b.days_by_eid / b.target_days) * 100)
        : 0;

const todayInRange = computed(
    () =>
        between(start.value, props.plan.today) >= 0 &&
        props.plan.days_left >= 0,
);

// Price basis for the batch cards.
const basis = ref<'kg' | 'head'>('kg');
const valueOf = (b: BatchPlan): number | null =>
    basis.value === 'head' ? b.value_head : b.weighed > 0 ? b.value : null;
const profitOf = (b: BatchPlan): number | null =>
    basis.value === 'head' ? b.profit_head : b.profit;
const rowProfit = (a: AnimalRow): number | null =>
    basis.value === 'head' ? a.profit_head : a.profit;

const gainNote = (a: AnimalRow): string =>
    a.gain_source === 'own'
        ? t('নিজের ওজন থেকে')
        : a.gain_source === 'batch'
          ? t('ব্যাচের গড় ধরে')
          : t('জানা নেই — দিনে :kg কেজি ধরা হলো', {
                kg: props.plan.assumed_gain,
            });

// Client-side calculator for a new round.
const c = reactive({
    animals: props.calc.animals as number | string,
    buy_kg: props.calc.buy_kg as number | string,
    buy_price: props.calc.buy_price as number | string,
    gain: props.calc.gain as number | string,
    feed_per_day: props.calc.feed_per_day as number | string,
    sale_per_kg: props.calc.sale_per_kg as number | string,
    days: 120 as number | string,
});

const num = (v: number | string): number => {
    const n = Number(v);

    return Number.isFinite(n) && n > 0 ? n : 0;
};

const result = computed(() => {
    const n = num(c.animals);
    const days = Math.round(num(c.days));
    const weight = num(c.buy_kg) + num(c.gain) * days;
    const costHead = num(c.buy_price) + num(c.feed_per_day) * days;
    const saleHead = weight * num(c.sale_per_kg);
    const profit = (saleHead - costHead) * n;
    const buyBy = addDays(props.plan.eid, -days);

    return {
        buyBy,
        buyLate: between(today(), buyBy) < 0,
        weight,
        cost: costHead * n * 100,
        sale: saleHead * n * 100,
        profit: profit * 100,
        profitHead: (saleHead - costHead) * 100,
        returnPct: costHead > 0 ? ((saleHead - costHead) / costHead) * 100 : 0,
        breakEven: weight > 0 ? (costHead / weight) * 100 : 0,
    };
});

const tips = [
    {
        icon: Scale,
        tone: 'bg-pond/12 text-pond',
        title: 'হাটের আগে ওজন মাপা',
        body: 'হাটে নেওয়ার ২–৩ দিন আগে ওজন মেপে নিন (না পারলে ফিতা দিয়ে)। ওজন জানা থাকলে দরদামে ঠকবেন না।',
    },
    {
        icon: Syringe,
        tone: 'bg-clay/12 text-clay',
        title: 'কৃমি-টিকা আগে',
        body: 'কেনার পরপরই কৃমিনাশক আর তড়কা-ক্ষুরা রোগের টিকা দিন — শেষ দিকে ওষুধ দিলে মাংসে প্রভাব পড়ে।',
    },
    {
        icon: Wheat,
        tone: 'bg-turmeric/25 text-soil',
        title: 'শেষ ২ সপ্তাহে খাবার হঠাৎ না বাড়ানো',
        body: 'ঈদের আগে হঠাৎ দানাদার বাড়ালে পেট ফাঁপা ও অসুখ হতে পারে। খাবার যেমন চলছে তেমন রাখুন, পরিষ্কার পানি দিন।',
    },
    {
        icon: Truck,
        tone: 'bg-soil/10 text-soil',
        title: 'পরিবহন ও হাসিল খরচ',
        body: 'ট্রাক ভাড়া, হাসিল (হাটের খাজনা), রাখালের খরচ আগে থেকে হিসাবে ধরুন — গরুপ্রতি কয়েক হাজার টাকা হতে পারে।',
    },
    {
        icon: Smartphone,
        tone: 'bg-paddy/12 text-paddy',
        title: 'অনলাইন বিক্রি',
        body: 'গরুর ছবি, ওজন আর দাম দিয়ে ফেসবুক পেজ বা অনলাইন হাটে আগে থেকেই পোস্ট দিন — খামার থেকেই বিক্রি হলে হাটের খরচ বাঁচে।',
    },
];
</script>

<template>
    <Head :title="t('কোরবানি পরিকল্পনা')" />

    <div class="flex flex-1 flex-col p-4 md:p-6">
        <PageTitle
            :title="t('কোরবানি পরিকল্পনা')"
            :subtitle="
                t(
                    'ঈদুল আজহা ধরে মোটাতাজার দফা সাজান — কবে কিনবেন, কত দিন খাওয়াবেন, ঈদে কত ওজন ও দাম হবে।',
                )
            "
        />

        <!-- Countdown, the road to Eid, and what to do now -->
        <section
            class="overflow-hidden rounded-2xl border border-border bg-card shadow-[0_1px_0_0_var(--border)]"
            aria-labelledby="eid-heading"
        >
            <div
                class="grid gap-5 p-4 sm:p-5 lg:grid-cols-[minmax(0,17rem)_minmax(0,1fr)_minmax(0,1fr)]"
            >
                <!-- Days to Eid -->
                <div class="flex items-center gap-4 lg:flex-col lg:items-start">
                    <div class="relative size-28 shrink-0 sm:size-32">
                        <svg
                            viewBox="0 0 120 120"
                            class="size-full -rotate-90"
                            aria-hidden="true"
                        >
                            <circle
                                cx="60"
                                cy="60"
                                r="52"
                                fill="none"
                                stroke="currentColor"
                                stroke-width="10"
                                class="text-muted"
                            />
                            <circle
                                cx="60"
                                cy="60"
                                r="52"
                                fill="none"
                                stroke="currentColor"
                                stroke-width="10"
                                stroke-linecap="round"
                                :stroke-dasharray="ring.length"
                                :stroke-dashoffset="ringOffset"
                                :class="
                                    plan.days_left <= 30
                                        ? 'text-clay'
                                        : 'text-paddy'
                                "
                            />
                        </svg>
                        <div
                            class="absolute inset-0 flex flex-col items-center justify-center text-center"
                        >
                            <span
                                class="num font-display text-4xl leading-none"
                                >{{ plan.days_left }}</span
                            >
                            <span
                                class="mt-1 text-xs leading-normal text-muted-foreground"
                                >{{ t('দিন বাকি') }}</span
                            >
                        </div>
                    </div>
                    <div class="min-w-0">
                        <p
                            id="eid-heading"
                            class="flex items-center gap-1.5 text-sm leading-normal text-muted-foreground"
                        >
                            <Moon class="size-4 shrink-0" />
                            {{ t('ঈদুল আজহা') }}
                        </p>
                        <p
                            class="num mt-0.5 font-display text-2xl leading-normal"
                        >
                            {{ day(plan.eid) }}
                        </p>
                        <p class="text-xs leading-normal text-muted-foreground">
                            {{
                                plan.eid_saved
                                    ? t('খামারের ঠিক করা তারিখ')
                                    : t('আনুমানিক — চাঁদ দেখার উপর নির্ভর করে')
                            }}
                        </p>
                        <span
                            :class="[
                                'mt-2 inline-block rounded-full px-2.5 py-1 text-xs leading-normal font-medium',
                                phaseTone,
                            ]"
                            >{{ phaseText }}</span
                        >
                    </div>
                </div>

                <!-- The buying window, step by step -->
                <div class="min-w-0 lg:border-l lg:border-border lg:pl-5">
                    <h2
                        class="mb-3 flex items-center gap-2 font-display text-lg leading-normal"
                    >
                        <ShoppingCart class="size-5 shrink-0 text-paddy" />
                        {{ t('কেনার সময়সূচি') }}
                    </h2>
                    <ol class="space-y-3">
                        <li
                            v-for="step in steps"
                            :key="step.key"
                            class="flex items-start gap-3"
                        >
                            <span
                                :class="[
                                    'mt-0.5 flex size-6 shrink-0 items-center justify-center rounded-full border',
                                    step.done
                                        ? 'border-paddy bg-paddy text-primary-foreground'
                                        : step.now || step.next
                                          ? 'border-clay bg-clay/10 text-clay'
                                          : 'border-border bg-card text-muted-foreground',
                                ]"
                            >
                                <Check v-if="step.done" class="size-3.5" />
                                <span
                                    v-else
                                    class="size-1.5 rounded-full bg-current"
                                />
                            </span>
                            <span class="min-w-0 flex-1">
                                <span
                                    class="flex flex-wrap items-center gap-x-2 gap-y-1"
                                >
                                    <span
                                        :class="[
                                            'text-sm leading-normal font-medium',
                                            (step.now || step.next) &&
                                                'text-clay',
                                            step.done &&
                                                'text-muted-foreground',
                                        ]"
                                        >{{ step.title }}</span
                                    >
                                    <span
                                        v-if="step.now || step.next"
                                        class="rounded-full bg-clay/12 px-2 py-0.5 text-[11px] leading-normal font-medium text-clay"
                                        >{{
                                            step.now
                                                ? t('আজ এখানে')
                                                : t('এর পরে')
                                        }}</span
                                    >
                                </span>
                                <span
                                    class="num block text-xs leading-normal text-muted-foreground"
                                    >{{ step.note }}</span
                                >
                            </span>
                        </li>
                    </ol>
                </div>

                <!-- What to do now -->
                <div class="min-w-0">
                    <div
                        class="rounded-xl border border-paddy/25 bg-paddy/5 p-3"
                    >
                        <p
                            class="text-sm leading-normal font-medium text-paddy"
                        >
                            {{ t('এখন যা করবেন') }}
                        </p>
                        <ul class="mt-2 space-y-1.5">
                            <li
                                v-for="(task, i) in phaseTasks"
                                :key="i"
                                class="flex gap-2 text-sm leading-normal"
                            >
                                <span
                                    class="mt-2 size-1.5 shrink-0 rounded-full bg-paddy"
                                />
                                <span>{{ task }}</span>
                            </li>
                        </ul>
                    </div>
                    <div
                        class="mt-3 flex items-center gap-3 rounded-xl bg-muted/50 p-3"
                    >
                        <span
                            class="flex size-9 shrink-0 items-center justify-center rounded-lg bg-turmeric/25 text-soil"
                        >
                            <Coins class="size-4" />
                        </span>
                        <span class="min-w-0 flex-1">
                            <span
                                class="block text-xs leading-normal text-muted-foreground"
                                >{{ t('জীবন্ত ওজনের দর (৳/কেজি)') }}</span
                            >
                            <span
                                class="num block text-lg leading-normal font-semibold"
                                >{{
                                    t(':p/কেজি', {
                                        p: taka(plan.price_per_kg),
                                    })
                                }}</span
                            >
                        </span>
                    </div>
                    <div
                        v-if="can['manage-setup'] && !editing"
                        class="mt-3 flex"
                    >
                        <button
                            type="button"
                            class="btn-chip"
                            @click="startEdit"
                        >
                            <Pencil /> {{ t('তারিখ ও দর বদলান') }}
                        </button>
                    </div>
                </div>
            </div>

            <!-- Where today sits on the 150-day run-up -->
            <div class="border-t border-border px-4 pt-7 pb-4 sm:px-5">
                <div class="relative">
                    <div
                        class="relative h-3 overflow-hidden rounded-full bg-muted"
                    >
                        <div
                            v-for="s in segments"
                            :key="s.key"
                            :class="['absolute inset-y-0', s.cls]"
                            :style="{
                                left: `${s.left}%`,
                                width: `${s.width}%`,
                            }"
                        />
                    </div>
                    <div
                        v-if="todayInRange"
                        class="absolute -top-6 flex -translate-x-1/2 flex-col items-center"
                        :style="{ left: `${todayPos}%` }"
                    >
                        <span
                            class="rounded bg-foreground px-1.5 text-[11px] leading-normal text-background"
                            >{{ t('আজ') }}</span
                        >
                        <span class="h-6 w-0.5 bg-foreground" />
                    </div>
                </div>
                <div
                    class="num mt-1.5 flex justify-between gap-3 text-[11px] leading-normal text-muted-foreground"
                >
                    <span>{{ day(start) }}</span>
                    <span class="text-right"
                        >{{ t('ঈদ') }} · {{ day(plan.eid) }}</span
                    >
                </div>
            </div>

            <form
                v-if="editing"
                class="grid gap-3 border-t border-border bg-muted/30 p-4 sm:grid-cols-2 sm:p-5"
                @submit.prevent="save"
            >
                <Field
                    :label="t('ঈদের তারিখ')"
                    :error="form.errors.eid_date"
                    :hint="t('ফাঁকা রাখলে আনুমানিক তারিখ ধরা হবে')"
                    ><DateField
                        v-model="form.eid_date"
                        :min="today()"
                        clearable
                /></Field>
                <Field
                    :label="t('জীবন্ত ওজনের দর (৳/কেজি)')"
                    :error="form.errors.price_per_kg"
                    ><Input
                        v-model="form.price_per_kg"
                        type="number"
                        min="50"
                        max="5000"
                        inputmode="decimal"
                /></Field>
                <div class="flex flex-wrap gap-2 sm:col-span-2">
                    <span
                        class="w-full text-xs leading-normal text-muted-foreground"
                        >{{ t('আনুমানিক তারিখ') }}</span
                    >
                    <button
                        v-for="d in plan.known_dates"
                        :key="d"
                        type="button"
                        :class="
                            form.eid_date === d
                                ? 'btn-chip-primary'
                                : 'btn-chip'
                        "
                        @click="form.eid_date = d"
                    >
                        <CalendarDays /> {{ day(d) }}
                    </button>
                </div>
                <div class="flex flex-wrap gap-2 sm:col-span-2 sm:justify-end">
                    <Button type="submit" :disabled="form.processing">{{
                        t('রাখুন')
                    }}</Button>
                    <Button
                        type="button"
                        variant="secondary"
                        @click="editing = false"
                        >{{ t('বাদ') }}</Button
                    >
                </div>
            </form>
        </section>

        <!-- Current batches -->
        <section class="mt-6">
            <div class="mb-3 flex flex-wrap items-center justify-between gap-2">
                <h2 class="font-display text-lg leading-normal">
                    {{ t('চলমান ব্যাচ ঈদে কেমন হবে') }}
                </h2>
                <div
                    v-if="plan.haat"
                    class="flex flex-wrap gap-2"
                    role="group"
                    :aria-label="t('দামের ভিত্তি')"
                >
                    <button
                        type="button"
                        :class="
                            basis === 'kg' ? 'btn-chip-primary' : 'btn-chip'
                        "
                        :aria-pressed="basis === 'kg'"
                        @click="basis = 'kg'"
                    >
                        <Scale /> {{ t('কেজি দরে') }}
                    </button>
                    <button
                        type="button"
                        :class="
                            basis === 'head' ? 'btn-chip-primary' : 'btn-chip'
                        "
                        :aria-pressed="basis === 'head'"
                        @click="basis = 'head'"
                    >
                        {{ t('হাটের দরে (প্রতি গরু)') }}
                    </button>
                </div>
            </div>
            <p
                v-if="plan.haat && basis === 'head'"
                class="mb-3 text-xs leading-normal text-muted-foreground"
            >
                {{
                    t('বাজারদর: :price প্রতি গরু (:date)', {
                        price: taka(plan.haat.price),
                        date: day(plan.haat.date),
                    })
                }}
            </p>

            <div
                v-if="!plan.batches.length"
                class="flex flex-col items-center rounded-xl border border-dashed border-border bg-card/60 px-4 py-10 text-center"
            >
                <span class="mb-3 rounded-full bg-paddy/12 p-3 text-paddy"
                    ><Sprout class="size-6"
                /></span>
                <p class="font-display text-lg leading-normal">
                    {{ t('কোনো চলমান মোটাতাজা ব্যাচ নেই') }}
                </p>
                <p
                    class="mt-1 max-w-md text-sm leading-normal text-muted-foreground"
                >
                    {{
                        t(
                            'কোরবানির জন্য নতুন দফা খুলুন — নিচের হিসাব দিয়ে আগে লাভ-ক্ষতি দেখে নিন।',
                        )
                    }}
                </p>
                <Link href="/batches" class="btn-chip-primary mt-4"
                    ><Sprout /> {{ t('মোটাতাজা ব্যাচ') }}</Link
                >
            </div>

            <div
                v-else
                :class="[
                    'grid gap-4',
                    plan.batches.length > 1 && 'xl:grid-cols-2',
                ]"
            >
                <article
                    v-for="b in plan.batches"
                    :key="b.id"
                    class="@container min-w-0 overflow-hidden rounded-2xl border border-border bg-card shadow-[0_1px_0_0_var(--border)]"
                >
                    <div class="p-4 sm:p-5">
                        <header
                            class="flex flex-wrap items-start justify-between gap-3"
                        >
                            <div class="min-w-0">
                                <h3
                                    class="truncate font-display text-xl leading-normal"
                                >
                                    {{ batchName(b) }}
                                </h3>
                                <p
                                    class="num text-sm leading-normal text-muted-foreground"
                                >
                                    {{
                                        t('শুরু :date · :n টি গরু', {
                                            date: day(b.started_on),
                                            n: b.head,
                                        })
                                    }}
                                </p>
                            </div>
                            <span
                                :class="[
                                    'inline-flex shrink-0 items-center gap-1 rounded-full px-2.5 py-1 text-xs leading-normal font-medium',
                                    b.ready
                                        ? 'bg-income/10 text-income'
                                        : 'bg-turmeric/25 text-soil',
                                ]"
                            >
                                <CheckCircle2 v-if="b.ready" class="size-3.5" />
                                <Clock v-else class="size-3.5" />
                                {{
                                    b.ready
                                        ? t('ঈদের আগে তৈরি')
                                        : t(':n দিন কম পড়বে', {
                                              n: b.short_days,
                                          })
                                }}
                            </span>
                        </header>

                        <!-- Feeding days: so far, and by Eid, against the target -->
                        <div class="mt-4">
                            <div
                                class="relative h-2.5 overflow-hidden rounded-full bg-muted"
                            >
                                <div
                                    :class="[
                                        'absolute inset-y-0 left-0 rounded-full',
                                        b.ready
                                            ? 'bg-paddy/30'
                                            : 'bg-turmeric/40',
                                    ]"
                                    :style="{ width: `${eidPct(b)}%` }"
                                />
                                <div
                                    class="absolute inset-y-0 left-0 rounded-full bg-paddy"
                                    :style="{ width: `${fedPct(b)}%` }"
                                />
                            </div>
                            <p
                                class="num mt-1.5 text-xs leading-normal text-muted-foreground"
                            >
                                {{
                                    t(
                                        'ঈদ পর্যন্ত :days দিন খাওয়ানো হবে (লক্ষ্য :target দিন)',
                                        {
                                            days: b.days_by_eid,
                                            target: b.target_days,
                                        },
                                    )
                                }}
                            </p>
                        </div>

                        <!-- The four numbers that matter -->
                        <div
                            class="mt-4 grid grid-cols-2 gap-2 @2xl:grid-cols-4"
                        >
                            <div class="rounded-xl bg-muted/50 p-3">
                                <p
                                    class="flex items-center gap-1.5 text-xs leading-normal text-muted-foreground"
                                >
                                    <Scale class="size-3.5 shrink-0" />
                                    {{ t('ঈদে মোট ওজন') }}
                                </p>
                                <p
                                    class="num mt-1 text-lg leading-normal font-semibold"
                                >
                                    {{ b.weighed ? kg(b.expected_kg) : '—' }}
                                </p>
                                <p
                                    v-if="b.weighed < b.head"
                                    class="num text-[11px] leading-normal text-muted-foreground"
                                >
                                    {{
                                        t(':n টির ওজন নেই', {
                                            n: b.head - b.weighed,
                                        })
                                    }}
                                </p>
                            </div>
                            <div class="rounded-xl bg-muted/50 p-3">
                                <p
                                    class="flex items-center gap-1.5 text-xs leading-normal text-muted-foreground"
                                >
                                    <Coins class="size-3.5 shrink-0" />
                                    {{ t('আনুমানিক বিক্রি') }}
                                </p>
                                <p
                                    class="num mt-1 text-lg leading-normal font-semibold text-income"
                                >
                                    {{
                                        valueOf(b) === null
                                            ? '—'
                                            : taka(valueOf(b))
                                    }}
                                </p>
                            </div>
                            <div class="rounded-xl bg-muted/50 p-3">
                                <p
                                    class="flex items-center gap-1.5 text-xs leading-normal text-muted-foreground"
                                >
                                    <ShoppingCart class="size-3.5 shrink-0" />
                                    {{ t('মোট খরচ') }}
                                </p>
                                <p
                                    class="num mt-1 text-lg leading-normal font-semibold text-expense"
                                >
                                    {{ taka(b.cost_total) }}
                                </p>
                            </div>
                            <div
                                :class="[
                                    'rounded-xl p-3',
                                    profitOf(b) === null
                                        ? 'bg-muted/50'
                                        : (profitOf(b) ?? 0) < 0
                                          ? 'bg-destructive/10'
                                          : 'bg-income/10',
                                ]"
                            >
                                <p
                                    class="flex items-center gap-1.5 text-xs leading-normal text-muted-foreground"
                                >
                                    <TrendingDown
                                        v-if="(profitOf(b) ?? 0) < 0"
                                        class="size-3.5 shrink-0"
                                    />
                                    <TrendingUp
                                        v-else
                                        class="size-3.5 shrink-0"
                                    />
                                    {{
                                        (profitOf(b) ?? 0) < 0
                                            ? t('আনুমানিক ক্ষতি')
                                            : t('আনুমানিক লাভ')
                                    }}
                                </p>
                                <p
                                    :class="[
                                        'num mt-1 text-lg leading-normal font-semibold',
                                        profitOf(b) === null
                                            ? ''
                                            : (profitOf(b) ?? 0) < 0
                                              ? 'text-destructive'
                                              : 'text-income',
                                    ]"
                                >
                                    {{
                                        profitOf(b) === null
                                            ? '—'
                                            : taka(profitOf(b))
                                    }}
                                </p>
                                <p
                                    v-if="
                                        basis === 'kg' &&
                                        b.weighed > 0 &&
                                        b.weighed < b.head
                                    "
                                    class="text-[11px] leading-normal text-muted-foreground"
                                >
                                    {{ t('লাভ শুধু ওজন মাপা গরুর হিসাবে') }}
                                </p>
                            </div>
                        </div>

                        <!-- Where the cost comes from -->
                        <dl
                            class="mt-3 grid gap-x-6 gap-y-2 text-sm @xl:grid-cols-2"
                        >
                            <div class="flex items-start justify-between gap-3">
                                <dt
                                    class="leading-normal text-muted-foreground"
                                >
                                    {{ t('এ পর্যন্ত খরচ') }}
                                </dt>
                                <dd class="num leading-normal font-medium">
                                    {{ taka(b.spent) }}
                                </dd>
                            </div>
                            <div class="flex items-start justify-between gap-3">
                                <dt
                                    class="leading-normal text-muted-foreground"
                                >
                                    {{ t('ঈদ পর্যন্ত খাবার খরচ') }}
                                    <span
                                        class="num block text-[11px] leading-normal"
                                        >{{
                                            t(
                                                ':rate/দিন প্রতি গরু × :days দিন',
                                                {
                                                    rate: taka(b.feed_per_day),
                                                    days: b.days_left,
                                                },
                                            )
                                        }}{{
                                            b.feed_own
                                                ? ''
                                                : ' · ' + t('আনুমানিক')
                                        }}</span
                                    >
                                </dt>
                                <dd class="num leading-normal font-medium">
                                    {{ taka(b.future_cost) }}
                                </dd>
                            </div>
                        </dl>
                    </div>

                    <!-- Each animal -->
                    <ul
                        v-if="b.animals.length"
                        class="grid gap-2 border-t border-border bg-muted/20 p-3 sm:p-4 @xl:grid-cols-2"
                    >
                        <li
                            v-for="a in b.animals"
                            :key="a.id"
                            class="flex min-w-0 items-center gap-3 rounded-xl border border-border bg-card p-3"
                        >
                            <div class="min-w-0 flex-1">
                                <p
                                    class="truncate text-sm leading-normal font-medium"
                                >
                                    <Link
                                        :href="`/animals/${a.id}`"
                                        class="text-primary underline decoration-primary/40 underline-offset-4 hover:decoration-primary"
                                        >{{ a.tag }}</Link
                                    >
                                    <span
                                        v-if="a.name"
                                        class="font-normal text-muted-foreground"
                                    >
                                        · {{ a.name }}</span
                                    >
                                </p>
                                <p
                                    v-if="a.expected_kg !== null"
                                    class="text-xs leading-normal text-muted-foreground"
                                >
                                    <span class="num">{{ kg(a.last_kg) }}</span>
                                    →
                                    <span
                                        class="num font-medium text-foreground"
                                        >{{ kg(a.expected_kg) }}</span
                                    >
                                </p>
                                <p
                                    v-if="a.expected_kg !== null"
                                    class="text-[11px] leading-normal text-muted-foreground"
                                >
                                    {{ t('দিনে :kg কেজি', { kg: a.gain }) }}
                                    ({{ gainNote(a) }})
                                </p>
                            </div>
                            <Link
                                v-if="a.expected_kg === null"
                                :href="`/animals/${a.id}`"
                                class="btn-chip-primary"
                                ><Scale /> {{ t('ওজন লিখুন') }}</Link
                            >
                            <span
                                v-else-if="rowProfit(a) !== null"
                                :class="[
                                    'num shrink-0 rounded-lg px-2 py-1 text-sm leading-normal font-semibold',
                                    (rowProfit(a) ?? 0) < 0
                                        ? 'bg-destructive/10 text-destructive'
                                        : 'bg-income/10 text-income',
                                ]"
                                >{{ taka(rowProfit(a)) }}</span
                            >
                        </li>
                    </ul>
                </article>
            </div>
        </section>

        <!-- Calculator -->
        <section
            class="mt-6 overflow-hidden rounded-2xl border border-border bg-card shadow-[0_1px_0_0_var(--border)]"
        >
            <div class="border-b border-border p-4 sm:px-5">
                <h2
                    class="flex items-center gap-2 font-display text-lg leading-normal"
                >
                    <Calculator class="size-5 shrink-0 text-paddy" />
                    {{ t('নতুন ব্যাচ পরিকল্পনা') }}
                </h2>
                <p class="mt-0.5 text-sm leading-normal text-muted-foreground">
                    {{
                        t(
                            'সংখ্যা বদলে দেখুন — কবে কিনতে হবে, ঈদে কত ওজন হবে, লাভ কত। কিছু সংরক্ষণ হয় না।',
                        )
                    }}
                </p>
            </div>
            <div
                class="grid gap-5 p-4 sm:p-5 lg:grid-cols-[minmax(0,1fr)_minmax(0,1fr)]"
            >
                <!-- Inputs -->
                <div class="grid content-start gap-3 sm:grid-cols-2">
                    <Field :label="t('কয়টি গরু')"
                        ><Input
                            v-model="c.animals"
                            type="number"
                            min="1"
                            inputmode="numeric"
                    /></Field>
                    <Field :label="t('কত দিন খাওয়াবেন')"
                        ><Input
                            v-model="c.days"
                            type="number"
                            min="1"
                            max="365"
                            inputmode="numeric"
                    /></Field>
                    <Field :label="t('কেনার ওজন (কেজি)')"
                        ><Input
                            v-model="c.buy_kg"
                            type="number"
                            min="0"
                            inputmode="decimal"
                    /></Field>
                    <Field :label="t('কেনার দাম (৳/গরু)')"
                        ><Input
                            v-model="c.buy_price"
                            type="number"
                            min="0"
                            inputmode="numeric"
                    /></Field>
                    <Field :label="t('দৈনিক ওজন বৃদ্ধি (কেজি)')"
                        ><Input
                            v-model="c.gain"
                            type="number"
                            min="0"
                            step="0.05"
                            inputmode="decimal"
                    /></Field>
                    <Field :label="t('খাবার খরচ (৳/দিন)')"
                        ><Input
                            v-model="c.feed_per_day"
                            type="number"
                            min="0"
                            inputmode="numeric"
                    /></Field>
                    <Field
                        :label="t('বিক্রির দর (৳/কেজি)')"
                        class="sm:col-span-2"
                        ><Input
                            v-model="c.sale_per_kg"
                            type="number"
                            min="0"
                            inputmode="numeric"
                    /></Field>
                </div>

                <!-- Results -->
                <div
                    class="grid content-start gap-2 sm:grid-cols-2 lg:border-l lg:border-border lg:pl-5"
                >
                    <div
                        :class="[
                            'flex items-center gap-3 rounded-xl border p-3 sm:col-span-2',
                            result.buyLate
                                ? 'border-destructive/30 bg-destructive/10'
                                : 'border-paddy/25 bg-paddy/5',
                        ]"
                    >
                        <span
                            :class="[
                                'flex size-10 shrink-0 items-center justify-center rounded-lg',
                                result.buyLate
                                    ? 'bg-destructive/15 text-destructive'
                                    : 'bg-paddy/12 text-paddy',
                            ]"
                        >
                            <CalendarCheck class="size-5" />
                        </span>
                        <span class="min-w-0">
                            <span
                                class="block text-xs leading-normal text-muted-foreground"
                                >{{ t('কিনতে হবে যেদিনের মধ্যে') }}</span
                            >
                            <span
                                class="num block font-display text-xl leading-normal"
                                >{{ day(result.buyBy) }}</span
                            >
                            <span
                                v-if="result.buyLate"
                                class="block text-xs leading-normal text-destructive"
                                >{{
                                    t('এই তারিখ পার হয়ে গেছে — দিন কমান')
                                }}</span
                            >
                        </span>
                    </div>
                    <div class="grid grid-cols-2 gap-2 sm:col-span-2">
                        <div class="rounded-xl bg-muted/50 p-3">
                            <p
                                class="text-xs leading-normal text-muted-foreground"
                            >
                                {{ t('ঈদে ওজন (প্রতি গরু)') }}
                            </p>
                            <p
                                class="num mt-1 text-lg leading-normal font-semibold"
                            >
                                {{ kg(result.weight) }}
                            </p>
                        </div>
                        <div class="rounded-xl bg-muted/50 p-3">
                            <p
                                class="text-xs leading-normal text-muted-foreground"
                            >
                                {{ t('লাভ-ক্ষতির সমান দর') }}
                            </p>
                            <p
                                class="num mt-1 text-lg leading-normal font-semibold"
                            >
                                {{
                                    t(':p/কেজি', {
                                        p: taka(Math.round(result.breakEven)),
                                    })
                                }}
                            </p>
                        </div>
                        <div class="rounded-xl bg-muted/50 p-3">
                            <p
                                class="text-xs leading-normal text-muted-foreground"
                            >
                                {{ t('মোট খরচ') }}
                            </p>
                            <p
                                class="num mt-1 text-lg leading-normal font-semibold text-expense"
                            >
                                {{ taka(Math.round(result.cost)) }}
                            </p>
                        </div>
                        <div class="rounded-xl bg-muted/50 p-3">
                            <p
                                class="text-xs leading-normal text-muted-foreground"
                            >
                                {{ t('আনুমানিক বিক্রি') }}
                            </p>
                            <p
                                class="num mt-1 text-lg leading-normal font-semibold text-income"
                            >
                                {{ taka(Math.round(result.sale)) }}
                            </p>
                        </div>
                    </div>
                    <div
                        :class="[
                            'flex items-center gap-3 rounded-xl p-3 sm:col-span-2',
                            result.profit < 0
                                ? 'bg-destructive/10'
                                : 'bg-income/10',
                        ]"
                    >
                        <span
                            :class="[
                                'flex size-10 shrink-0 items-center justify-center rounded-lg',
                                result.profit < 0
                                    ? 'bg-destructive/15 text-destructive'
                                    : 'bg-income/15 text-income',
                            ]"
                        >
                            <TrendingDown
                                v-if="result.profit < 0"
                                class="size-5"
                            />
                            <TrendingUp v-else class="size-5" />
                        </span>
                        <span class="min-w-0">
                            <span
                                class="block text-xs leading-normal text-muted-foreground"
                                >{{
                                    result.profit < 0 ? t('ক্ষতি') : t('লাভ')
                                }}</span
                            >
                            <span
                                :class="[
                                    'num block text-2xl leading-normal font-semibold',
                                    result.profit < 0
                                        ? 'text-destructive'
                                        : 'text-income',
                                ]"
                                >{{ taka(Math.round(result.profit)) }}</span
                            >
                            <span
                                class="num block text-xs leading-normal text-muted-foreground"
                                >{{
                                    t('প্রতি গরু :p · ফেরত :pct%', {
                                        // :pct first, or ":p" would eat its start.
                                        pct: result.returnPct.toFixed(1),
                                        p: taka(Math.round(result.profitHead)),
                                    })
                                }}</span
                            >
                        </span>
                    </div>
                </div>
            </div>
        </section>

        <!-- Tips -->
        <section
            class="mt-6 rounded-2xl border border-border bg-card p-4 shadow-[0_1px_0_0_var(--border)] sm:p-5"
        >
            <h2
                class="flex items-center gap-2 font-display text-lg leading-normal"
            >
                <Lightbulb class="size-5 shrink-0 text-turmeric" />
                {{ t('কাজের পরামর্শ') }}
            </h2>
            <ul class="mt-3 grid gap-x-6 gap-y-4 lg:grid-cols-2">
                <li
                    v-for="tip in tips"
                    :key="tip.title"
                    class="flex items-start gap-3 lg:odd:last:col-span-2"
                >
                    <span
                        :class="[
                            'flex size-10 shrink-0 items-center justify-center rounded-lg',
                            tip.tone,
                        ]"
                    >
                        <component :is="tip.icon" class="size-5" />
                    </span>
                    <span class="min-w-0">
                        <span
                            class="block text-sm leading-normal font-semibold"
                            >{{ t(tip.title) }}</span
                        >
                        <span
                            class="mt-0.5 block text-sm leading-normal text-muted-foreground"
                            >{{ t(tip.body) }}</span
                        >
                    </span>
                </li>
            </ul>
        </section>
    </div>
</template>
