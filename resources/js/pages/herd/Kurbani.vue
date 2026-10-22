<script setup lang="ts">
import { Head, Link, useForm } from '@inertiajs/vue3';
import {
    CalendarDays,
    Calculator,
    CheckCircle2,
    Clock,
    Lightbulb,
    Moon,
    Pencil,
    Scale,
    ShoppingCart,
    Sprout,
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
        title: 'হাটের আগে ওজন মাপা',
        body: 'হাটে নেওয়ার ২–৩ দিন আগে ওজন মেপে নিন (না পারলে ফিতা দিয়ে)। ওজন জানা থাকলে দরদামে ঠকবেন না।',
    },
    {
        title: 'কৃমি-টিকা আগে',
        body: 'কেনার পরপরই কৃমিনাশক আর তড়কা-ক্ষুরা রোগের টিকা দিন — শেষ দিকে ওষুধ দিলে মাংসে প্রভাব পড়ে।',
    },
    {
        title: 'শেষ ২ সপ্তাহে খাবার হঠাৎ না বাড়ানো',
        body: 'ঈদের আগে হঠাৎ দানাদার বাড়ালে পেট ফাঁপা ও অসুখ হতে পারে। খাবার যেমন চলছে তেমন রাখুন, পরিষ্কার পানি দিন।',
    },
    {
        title: 'পরিবহন ও হাসিল খরচ',
        body: 'ট্রাক ভাড়া, হাসিল (হাটের খাজনা), রাখালের খরচ আগে থেকে হিসাবে ধরুন — গরুপ্রতি কয়েক হাজার টাকা হতে পারে।',
    },
    {
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

        <!-- Countdown -->
        <section
            class="mb-5 rounded-xl border border-paddy/35 bg-card p-4"
            aria-labelledby="eid-heading"
        >
            <div class="flex flex-wrap items-start justify-between gap-3">
                <div class="min-w-0">
                    <p
                        id="eid-heading"
                        class="flex items-center gap-1.5 text-sm text-muted-foreground"
                    >
                        <Moon class="size-4" /> {{ t('ঈদুল আজহা') }}
                    </p>
                    <p class="mt-1 font-display text-2xl leading-snug">
                        {{ day(plan.eid) }}
                    </p>
                    <p class="text-xs text-muted-foreground">
                        {{
                            plan.eid_saved
                                ? t('খামারের ঠিক করা তারিখ')
                                : t('আনুমানিক — চাঁদ দেখার উপর নির্ভর করে')
                        }}
                    </p>
                </div>
                <div class="text-right">
                    <p
                        class="num font-display text-4xl leading-none text-paddy"
                    >
                        {{ plan.days_left }}
                    </p>
                    <p class="mt-1 text-sm text-muted-foreground">
                        {{ t('দিন বাকি') }}
                    </p>
                </div>
            </div>

            <div class="mt-3 flex flex-wrap items-center gap-2">
                <span
                    :class="[
                        'rounded-full px-2.5 py-1 text-xs leading-snug font-medium',
                        phaseTone,
                    ]"
                    >{{ phaseText }}</span
                >
                <span class="text-xs text-muted-foreground">{{
                    t('জীবন্ত ওজনের দর ৳:p/কেজি', {
                        p: (plan.price_per_kg / 100).toLocaleString('en-IN'),
                    })
                }}</span>
                <button
                    v-if="can['manage-setup'] && !editing"
                    type="button"
                    class="btn-chip ml-auto"
                    @click="startEdit"
                >
                    <Pencil /> {{ t('তারিখ ও দর বদলান') }}
                </button>
            </div>

            <form
                v-if="editing"
                class="mt-4 grid gap-3 border-t border-border pt-4 sm:grid-cols-2"
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
                    <span class="w-full text-xs text-muted-foreground">{{
                        t('আনুমানিক তারিখ')
                    }}</span>
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

        <!-- Buying window -->
        <section class="mb-6 rounded-xl border border-border bg-card p-4">
            <h2
                class="flex items-center gap-2 font-display text-lg leading-snug"
            >
                <ShoppingCart class="size-5 text-paddy" />
                {{ t('কেনার সময়সূচি') }}
            </h2>
            <div class="relative mt-6 mb-2">
                <div class="relative h-3 overflow-hidden rounded-full bg-muted">
                    <div
                        v-for="s in segments"
                        :key="s.key"
                        :class="['absolute inset-y-0', s.cls]"
                        :style="{ left: `${s.left}%`, width: `${s.width}%` }"
                    />
                </div>
                <div
                    v-if="todayInRange"
                    class="absolute -top-5 flex -translate-x-1/2 flex-col items-center"
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
                class="flex justify-between text-[11px] leading-snug text-muted-foreground"
            >
                <span>{{ day(start) }}</span>
                <span>{{ t('ঈদ') }} · {{ day(plan.eid) }}</span>
            </div>
            <ul class="mt-4 grid gap-2 text-sm sm:grid-cols-3">
                <li class="flex items-start gap-2">
                    <span class="mt-1 size-3 shrink-0 rounded-full bg-paddy" />
                    <span class="leading-snug"
                        ><span class="font-medium">{{
                            t('কেনার সেরা সময়')
                        }}</span>
                        <span class="block text-xs text-muted-foreground">{{
                            t(':from – :to (ঈদের ১২০–৯০ দিন আগে)', {
                                from: day(plan.window.from),
                                to: day(plan.window.to),
                            })
                        }}</span></span
                    >
                </li>
                <li class="flex items-start gap-2">
                    <span
                        class="mt-1 size-3 shrink-0 rounded-full bg-turmeric"
                    />
                    <span class="leading-snug"
                        ><span class="font-medium">{{ t('দেরিতে কেনা') }}</span>
                        <span class="block text-xs text-muted-foreground">{{
                            t(':date পর্যন্ত (৬০ দিন আগে) — বড় গরু কিনুন', {
                                date: day(plan.window.late),
                            })
                        }}</span></span
                    >
                </li>
                <li class="flex items-start gap-2">
                    <span
                        class="mt-1 size-3 shrink-0 rounded-full bg-pond/50"
                    />
                    <span class="leading-snug"
                        ><span class="font-medium">{{
                            t('খাওয়ানো ও বিক্রি')
                        }}</span>
                        <span class="block text-xs text-muted-foreground">{{
                            t('শেষ ৬০ দিন — নতুন গরু না কিনে ওজন বাড়ান')
                        }}</span></span
                    >
                </li>
            </ul>
        </section>

        <!-- Current batches -->
        <section class="mb-6">
            <div class="mb-3 flex flex-wrap items-center justify-between gap-2">
                <h2 class="font-display text-xl leading-snug">
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
                class="mb-3 text-xs text-muted-foreground"
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
                class="flex flex-col items-center rounded-xl border border-dashed border-border bg-card px-4 py-8 text-center"
            >
                <span class="mb-3 rounded-full bg-paddy/12 p-3 text-paddy"
                    ><Sprout class="size-6"
                /></span>
                <p class="font-display text-lg">
                    {{ t('কোনো চলমান মোটাতাজা ব্যাচ নেই') }}
                </p>
                <p class="mt-1 max-w-sm text-sm text-muted-foreground">
                    {{
                        t(
                            'কোরবানির জন্য নতুন দফা খুলুন — নিচের হিসাব দিয়ে আগে লাভ-ক্ষতি দেখে নিন।',
                        )
                    }}
                </p>
                <Link href="/batches" class="btn-chip-primary mt-4">{{
                    t('মোটাতাজা ব্যাচ')
                }}</Link>
            </div>

            <div class="grid gap-4 lg:grid-cols-2">
                <article
                    v-for="b in plan.batches"
                    :key="b.id"
                    class="min-w-0 rounded-xl border border-border bg-card p-4"
                >
                    <header class="flex items-start justify-between gap-3">
                        <div class="min-w-0">
                            <h3
                                class="truncate font-display text-lg leading-snug"
                            >
                                {{
                                    b.label === `ব্যাচ-${b.number}`
                                        ? t('ব্যাচ-:n', { n: b.number })
                                        : b.label
                                }}
                            </h3>
                            <p class="text-xs text-muted-foreground">
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
                                'inline-flex shrink-0 items-center gap-1 rounded-full px-2.5 py-1 text-xs leading-snug font-medium',
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
                                    : t(':n দিন কম পড়বে', { n: b.short_days })
                            }}
                        </span>
                    </header>

                    <p class="mt-2 text-sm text-muted-foreground">
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

                    <div class="mt-3 grid grid-cols-2 gap-2">
                        <div class="rounded-lg bg-muted/50 p-3">
                            <p class="text-xs text-muted-foreground">
                                {{ t('ঈদে মোট ওজন') }}
                            </p>
                            <p class="num text-lg font-semibold">
                                {{ b.weighed ? kg(b.expected_kg) : '—' }}
                            </p>
                            <p
                                v-if="b.weighed < b.head"
                                class="text-[11px] leading-snug text-muted-foreground"
                            >
                                {{
                                    t(':n টির ওজন নেই', {
                                        n: b.head - b.weighed,
                                    })
                                }}
                            </p>
                        </div>
                        <div class="rounded-lg bg-muted/50 p-3">
                            <p class="text-xs text-muted-foreground">
                                {{ t('আনুমানিক বিক্রি') }}
                            </p>
                            <p class="num text-lg font-semibold text-income">
                                {{
                                    valueOf(b) === null ? '—' : taka(valueOf(b))
                                }}
                            </p>
                        </div>
                    </div>

                    <dl class="mt-3 grid gap-1.5 text-sm">
                        <div class="flex justify-between gap-3">
                            <dt class="text-muted-foreground">
                                {{ t('এ পর্যন্ত খরচ') }}
                            </dt>
                            <dd class="num">{{ taka(b.spent) }}</dd>
                        </div>
                        <div class="flex justify-between gap-3">
                            <dt class="text-muted-foreground">
                                {{ t('ঈদ পর্যন্ত খাবার খরচ') }}
                                <span class="block text-[11px] leading-snug"
                                    >{{
                                        t(':rate/দিন প্রতি গরু × :days দিন', {
                                            rate: taka(b.feed_per_day),
                                            days: b.days_left,
                                        })
                                    }}{{
                                        b.feed_own ? '' : ' · ' + t('আনুমানিক')
                                    }}</span
                                >
                            </dt>
                            <dd class="num">{{ taka(b.future_cost) }}</dd>
                        </div>
                        <div
                            v-if="profitOf(b) !== null"
                            class="flex justify-between gap-3 border-t border-border pt-1.5 font-semibold"
                        >
                            <dt>
                                {{
                                    (profitOf(b) ?? 0) < 0
                                        ? t('আনুমানিক ক্ষতি')
                                        : t('আনুমানিক লাভ')
                                }}
                            </dt>
                            <dd
                                :class="[
                                    'num',
                                    (profitOf(b) ?? 0) < 0
                                        ? 'text-destructive'
                                        : 'text-income',
                                ]"
                            >
                                {{ taka(profitOf(b)) }}
                            </dd>
                        </div>
                        <p
                            v-if="
                                basis === 'kg' &&
                                b.weighed > 0 &&
                                b.weighed < b.head
                            "
                            class="text-[11px] leading-snug text-muted-foreground"
                        >
                            {{ t('লাভ শুধু ওজন মাপা গরুর হিসাবে') }}
                        </p>
                    </dl>

                    <ul
                        v-if="b.animals.length"
                        class="mt-4 divide-y divide-border/70 rounded-lg border border-border/70"
                    >
                        <li
                            v-for="a in b.animals"
                            :key="a.id"
                            class="flex flex-wrap items-center justify-between gap-x-3 gap-y-2 px-3 py-2.5 text-sm"
                        >
                            <div class="min-w-0">
                                <Link
                                    :href="`/animals/${a.id}`"
                                    class="font-medium text-primary underline underline-offset-2"
                                    >{{ a.tag }}</Link
                                >
                                <span
                                    v-if="a.name"
                                    class="text-muted-foreground"
                                >
                                    · {{ a.name }}</span
                                >
                                <p
                                    v-if="a.expected_kg !== null"
                                    class="text-xs leading-snug text-muted-foreground"
                                >
                                    <span class="num">{{ kg(a.last_kg) }}</span>
                                    →
                                    <span
                                        class="num font-medium text-foreground"
                                        >{{ kg(a.expected_kg) }}</span
                                    >
                                    ·
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
                                    'num shrink-0 text-sm font-medium',
                                    (rowProfit(a) ?? 0) < 0
                                        ? 'text-destructive'
                                        : 'text-income',
                                ]"
                                >{{ taka(rowProfit(a)) }}</span
                            >
                        </li>
                    </ul>
                </article>
            </div>
        </section>

        <!-- Calculator -->
        <section class="mb-6 rounded-xl border border-border bg-card p-4">
            <h2
                class="flex items-center gap-2 font-display text-xl leading-snug"
            >
                <Calculator class="size-5 text-paddy" />
                {{ t('নতুন ব্যাচ পরিকল্পনা') }}
            </h2>
            <p class="mt-1 text-sm text-muted-foreground">
                {{
                    t(
                        'সংখ্যা বদলে দেখুন — কবে কিনতে হবে, ঈদে কত ওজন হবে, লাভ কত। কিছু সংরক্ষণ হয় না।',
                    )
                }}
            </p>
            <div class="mt-4 grid grid-cols-2 gap-3 lg:grid-cols-4">
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
                <Field :label="t('বিক্রির দর (৳/কেজি)')"
                    ><Input
                        v-model="c.sale_per_kg"
                        type="number"
                        min="0"
                        inputmode="numeric"
                /></Field>
            </div>

            <div class="mt-4 grid grid-cols-2 gap-2 lg:grid-cols-4">
                <div
                    :class="[
                        'col-span-2 rounded-lg p-3 lg:col-span-1',
                        result.buyLate ? 'bg-destructive/10' : 'bg-paddy/10',
                    ]"
                >
                    <p class="text-xs text-muted-foreground">
                        {{ t('কিনতে হবে যেদিনের মধ্যে') }}
                    </p>
                    <p class="font-display text-lg leading-snug">
                        {{ day(result.buyBy) }}
                    </p>
                    <p
                        v-if="result.buyLate"
                        class="text-[11px] leading-snug text-destructive"
                    >
                        {{ t('এই তারিখ পার হয়ে গেছে — দিন কমান') }}
                    </p>
                </div>
                <div class="rounded-lg bg-muted/50 p-3">
                    <p class="text-xs text-muted-foreground">
                        {{ t('ঈদে ওজন (প্রতি গরু)') }}
                    </p>
                    <p class="num text-lg font-semibold">
                        {{ kg(result.weight) }}
                    </p>
                </div>
                <div class="rounded-lg bg-muted/50 p-3">
                    <p class="text-xs text-muted-foreground">
                        {{ t('লাভ-ক্ষতির সমান দর') }}
                    </p>
                    <p class="num text-lg font-semibold">
                        {{
                            t(':p/কেজি', {
                                p: taka(Math.round(result.breakEven)),
                            })
                        }}
                    </p>
                </div>
                <div class="rounded-lg bg-muted/50 p-3">
                    <p class="text-xs text-muted-foreground">
                        {{ t('মোট খরচ') }}
                    </p>
                    <p class="num text-lg font-semibold text-expense">
                        {{ taka(Math.round(result.cost)) }}
                    </p>
                </div>
                <div class="rounded-lg bg-muted/50 p-3">
                    <p class="text-xs text-muted-foreground">
                        {{ t('আনুমানিক বিক্রি') }}
                    </p>
                    <p class="num text-lg font-semibold text-income">
                        {{ taka(Math.round(result.sale)) }}
                    </p>
                </div>
                <div
                    :class="[
                        'col-span-2 rounded-lg p-3',
                        result.profit < 0
                            ? 'bg-destructive/10'
                            : 'bg-income/10',
                    ]"
                >
                    <p class="text-xs text-muted-foreground">
                        {{ result.profit < 0 ? t('ক্ষতি') : t('লাভ') }}
                    </p>
                    <p
                        :class="[
                            'num text-xl font-semibold',
                            result.profit < 0
                                ? 'text-destructive'
                                : 'text-income',
                        ]"
                    >
                        {{ taka(Math.round(result.profit)) }}
                    </p>
                    <p class="text-xs text-muted-foreground">
                        {{
                            t('প্রতি গরু :p · ফেরত :pct%', {
                                p: taka(Math.round(result.profitHead)),
                                pct: result.returnPct.toFixed(1),
                            })
                        }}
                    </p>
                </div>
            </div>
        </section>

        <!-- Tips -->
        <section class="rounded-xl border border-border bg-card p-4">
            <h2
                class="flex items-center gap-2 font-display text-xl leading-snug"
            >
                <Lightbulb class="size-5 text-turmeric" />
                {{ t('কাজের পরামর্শ') }}
            </h2>
            <ul class="mt-3 grid gap-3 sm:grid-cols-2">
                <li
                    v-for="tip in tips"
                    :key="tip.title"
                    class="rounded-lg bg-muted/50 p-3"
                >
                    <p class="leading-snug font-medium">{{ t(tip.title) }}</p>
                    <p
                        class="mt-1 text-sm leading-normal text-muted-foreground"
                    >
                        {{ t(tip.body) }}
                    </p>
                </li>
            </ul>
        </section>
    </div>
</template>
