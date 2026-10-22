<script setup lang="ts">
import { Head, Link, router } from '@inertiajs/vue3';
import {
    Beef,
    Calculator,
    CircleCheck,
    Coins,
    Droplets,
    HandCoins,
    Lightbulb,
    Milk,
    Scale,
    Sprout,
    Store,
    TrendingDown,
    TrendingUp,
    Wheat,
} from '@lucide/vue';
import { computed, reactive, ref } from 'vue';
import type { Component } from 'vue';
import CenterIcon from '@/components/khamar/CenterIcon.vue';
import PageTitle from '@/components/khamar/PageTitle.vue';
import { currentLocale, t } from '@/lib/i18n';
import { taka } from '@/lib/khamar';

defineOptions({
    layout: { breadcrumbs: [{ title: 'লাভের হিসাব', href: '/profit' }] },
});

type ResultKind = 'sold' | 'milk' | 'growing';

type ProjectRow = {
    id: number;
    name: string;
    kind: string;
    income: number;
    expense: number;
    profit: number;
    margin: number | null;
};

type AnimalRow = {
    id: number;
    tag: string;
    name: string | null;
    center: string | null;
    status: string;
    purchase: number;
    upkeep: number;
    spent: number;
    sale: number | null;
    milk_liters: number;
    milk_value: number;
    gain_per_day: number | null;
    result: number;
    result_kind: ResultKind;
};

const props = defineProps<{
    year: number;
    projects: ProjectRow[];
    animals: AnimalRow[];
    ratios: {
        milk_liters: number;
        milk_price: number | null;
        feed_per_liter: number | null;
        cost_per_liter: number | null;
        cost_per_kg_gain: number | null;
        profit_per_bigha: number | null;
    };
    defaults: {
        rent_per_bigha: number;
        cost_per_bigha: number;
        mon_per_bigha: number;
        paddy_price_per_mon: number;
        straw_kg_per_bigha: number;
        straw_price_per_kg: number;
        fattening_buy_price: number;
        fattening_feed_per_day: number;
        fattening_days: number;
        fattening_sale_price: number;
        cow_price: number;
        cow_liters_per_day: number;
        milk_price: number;
        cow_feed_per_day: number;
    };
}>();

/* ---------- helpers ---------- */
/** Amounts given in taka (not poisha). */
const tk = (value: number | null | undefined): string =>
    taka(Math.round((value ?? 0) * 100));
const num = (value: number | null | undefined, digits = 1): string =>
    (value ?? 0).toLocaleString('en-IN', { maximumFractionDigits: digits });
const n = (value: unknown): number => {
    const v = Number(value);

    return Number.isFinite(v) ? v : 0;
};
const BN_DIGITS = '০১২৩৪৫৬৭৮৯';
const localDigits = (value: number | string): string =>
    currentLocale() === 'bn'
        ? String(value).replace(/\d/g, (d) => BN_DIGITS[Number(d)])
        : String(value);

/* ---------- year ---------- */
const thisYear = new Date().getFullYear();
const years = computed(() => {
    const list = [thisYear, thisYear - 1, thisYear - 2];

    if (!list.includes(props.year)) {
        list.push(props.year);
    }

    return list;
});
const pickYear = (value: number) => {
    if (value !== props.year) {
        router.get('/profit', { year: value }, { preserveScroll: true });
    }
};

/* ---------- tabs ---------- */
type Tab = 'where' | 'plan' | 'tips';
const tab = ref<Tab>('where');
const tabs: { key: Tab; label: string; icon: Component }[] = [
    { key: 'where', label: 'কোথায় লাভ, কোথায় ক্ষতি', icon: Scale },
    { key: 'plan', label: 'যদি করি — পরিকল্পনা', icon: Calculator },
    { key: 'tips', label: 'লাভ বাড়ানোর উপায়', icon: Lightbulb },
];

/* ---------- key numbers ---------- */
type Tile = {
    label: string;
    value: number | null;
    unit: string;
    meaning: string;
    missing: string;
    icon: Component;
    tone: string;
};

const tiles = computed<Tile[]>(() => [
    {
        label: 'প্রতি লিটার দুধে খাবার খরচ',
        value: props.ratios.feed_per_liter,
        unit: '/লি.',
        meaning: 'গাভীর খাবারের খরচ ভাগ মোট দুধ',
        missing: 'গাভীর খাবার ও দুধের হিসাব লিখুন',
        icon: Wheat,
        tone: 'bg-paddy/12 text-paddy',
    },
    {
        label: 'দুধের যে দামে খরচ ওঠে',
        value: props.ratios.cost_per_liter,
        unit: '/লি.',
        meaning: 'এর নিচে বেচলে লোকসান',
        missing: 'গাভীর সব খরচ ও দুধের হিসাব লিখুন',
        icon: Droplets,
        tone: 'bg-clay/12 text-clay',
    },
    {
        label: 'দুধের গড় দাম',
        value: props.ratios.milk_price,
        unit: '/লি.',
        meaning: 'খাতার দুধ বিক্রি থেকে',
        missing: 'দুধ বিক্রির এন্ট্রি লিখুন',
        icon: Milk,
        tone: 'bg-pond/12 text-pond',
    },
    {
        label: 'মোটাতাজায় প্রতি কেজি বাড়াতে খরচ',
        value: props.ratios.cost_per_kg_gain,
        unit: '/কেজি',
        meaning: 'মোটাতাজার খরচ ভাগ মোট বাড়া ওজন',
        missing: 'মোটাতাজা গরুর ওজন অন্তত দুবার লিখুন',
        icon: Beef,
        tone: 'bg-turmeric/25 text-soil',
    },
    {
        label: 'প্রতি বিঘায় লাভ (ফসল)',
        value: props.ratios.profit_per_bigha,
        unit: '',
        meaning: 'এ বছরের ফসলের লাভ ভাগ মোট বিঘা',
        missing: 'জমি ও ফসলের মৌসুম লিখুন',
        icon: Sprout,
        tone: 'bg-paddy/12 text-paddy',
    },
]);

/* ---------- projects ---------- */
const ranked = computed(() =>
    [...props.projects].sort((a, b) => b.profit - a.profit),
);
const maxProfit = computed(() =>
    Math.max(1, ...props.projects.map((p) => Math.abs(p.profit))),
);
const totalProfit = computed(() =>
    props.projects.reduce((sum, p) => sum + p.profit, 0),
);

/* ---------- animals ---------- */
type Filter = 'all' | ResultKind;
const filter = ref<Filter>('all');
const kindLabel: Record<ResultKind, string> = {
    sold: 'বিক্রি হয়েছে',
    milk: 'দুধে ফেরত',
    growing: 'বাড়ছে',
};
const kindTone: Record<ResultKind, string> = {
    sold: 'bg-paddy/12 text-paddy',
    milk: 'bg-pond/12 text-pond',
    growing: 'bg-turmeric/25 text-soil',
};
const filters = computed(() => [
    { key: 'all' as Filter, label: 'সব', count: props.animals.length },
    ...(['sold', 'milk', 'growing'] as ResultKind[]).map((key) => ({
        key: key as Filter,
        label: kindLabel[key],
        count: props.animals.filter((a) => a.result_kind === key).length,
    })),
]);
const shownAnimals = computed(() =>
    [...props.animals]
        .filter((a) => filter.value === 'all' || a.result_kind === filter.value)
        .sort((a, b) => b.result - a.result),
);

/* ---------- advice ---------- */
const TYPICAL_COST_PER_KG = 300;

const advice = computed(() => {
    const out: { text: string; tone: 'warn' | 'good' }[] = [];
    const r = props.ratios;

    if (r.milk_price !== null && r.cost_per_liter !== null) {
        if (r.milk_price < r.cost_per_liter) {
            out.push({
                tone: 'warn',
                text: t(
                    'দুধের দাম (:price/লি.) খরচের (:cost/লি.) চেয়ে কম — প্রতি লিটারে লোকসান। খাবারের খরচ কমান বা ভালো দামে বেচার পথ খুঁজুন।',
                    { price: tk(r.milk_price), cost: tk(r.cost_per_liter) },
                ),
            });
        } else if (r.milk_price < r.cost_per_liter * 1.1) {
            out.push({
                tone: 'warn',
                text: t(
                    'দুধে লাভ খুব কম — দাম :price/লি., খরচ :cost/লি.। একটু খরচ বাড়লেই লোকসান।',
                    { price: tk(r.milk_price), cost: tk(r.cost_per_liter) },
                ),
            });
        }
    }

    props.projects
        .filter((p) => p.profit < 0)
        .sort((a, b) => a.profit - b.profit)
        .slice(0, 2)
        .forEach((p) =>
            out.push({
                tone: 'warn',
                text: t(':name এ বছর :amount লোকসানে — বড় খরচগুলো দেখে নিন।', {
                    name: t(p.name),
                    amount: taka(Math.abs(p.profit)),
                }),
            }),
        );

    const slow = props.animals.filter(
        (a) =>
            a.result_kind === 'growing' &&
            a.gain_per_day !== null &&
            a.gain_per_day < 0.5,
    );

    if (slow.length > 0) {
        const tags = slow
            .slice(0, 4)
            .map((a) => a.tag)
            .join(', ');
        out.push({
            tone: 'warn',
            text: t(
                ':tags — ওজন বাড়ছে কম (দিনে আধা কেজির নিচে), অথচ খরচ চলছে। খাবার, কৃমিনাশক ও স্বাস্থ্য দেখুন।',
                { tags: slow.length > 4 ? `${tags} …` : tags },
            ),
        });
    }

    if (
        r.cost_per_kg_gain !== null &&
        r.cost_per_kg_gain > TYPICAL_COST_PER_KG
    ) {
        out.push({
            tone: 'warn',
            text: t(
                'মোটাতাজায় প্রতি কেজি বাড়াতে খরচ :cost — বেশি। সাধারণত :typical-এর নিচে থাকলে ভালো লাভ থাকে।',
                {
                    cost: tk(r.cost_per_kg_gain),
                    typical: tk(TYPICAL_COST_PER_KG),
                },
            ),
        });
    }

    if (r.profit_per_bigha !== null && r.profit_per_bigha < 0) {
        out.push({
            tone: 'warn',
            text: t(
                'ফসলে বিঘাপ্রতি :amount লোকসান — ফলন, খাজনা আর চাষের খরচ মিলিয়ে দেখুন।',
                { amount: tk(Math.abs(r.profit_per_bigha)) },
            ),
        });
    }

    if (out.length === 0) {
        out.push({
            tone: 'good',
            text: t('সব ঠিক চলছে — বড় কোনো সমস্যা দেখা যাচ্ছে না।'),
        });
    }

    return out.slice(0, 5);
});

/* ---------- planner ---------- */
const d = props.defaults;

const paddy = reactive({
    bigha: 2,
    rent: n(d.rent_per_bigha),
    cost: n(d.cost_per_bigha),
    mon: n(d.mon_per_bigha),
    price: n(d.paddy_price_per_mon),
    straw: n(d.straw_kg_per_bigha),
    strawPrice: n(d.straw_price_per_kg),
    seasons: 2,
});

const paddyResult = computed(() => {
    const b = n(paddy.bigha);
    const seasons = Math.min(3, Math.max(1, Math.round(n(paddy.seasons))));
    const grain = b * n(paddy.mon) * n(paddy.price);
    const strawValue = b * n(paddy.straw) * n(paddy.strawPrice);
    const seasonIncome = grain + strawValue;
    const seasonCost = b * n(paddy.cost);
    const rent = b * n(paddy.rent);
    const yearProfit = (seasonIncome - seasonCost) * seasons - rent;
    const price = n(paddy.price);
    const breakEven =
        price > 0
            ? (n(paddy.cost) +
                  n(paddy.rent) / seasons -
                  n(paddy.straw) * n(paddy.strawPrice)) /
              price
            : null;

    return {
        seasons,
        grain,
        strawValue,
        seasonIncome,
        seasonCost,
        seasonProfit: seasonIncome - seasonCost,
        rent,
        yearProfit,
        perBigha: b > 0 ? yearProfit / b : 0,
        breakEven,
        strawKg: b * n(paddy.straw) * seasons,
        strawSaving: strawValue * seasons,
    };
});

const fat = reactive({
    head: 5,
    buy: n(d.fattening_buy_price),
    feed: n(d.fattening_feed_per_day),
    days: n(d.fattening_days) || 120,
    sale: n(d.fattening_sale_price),
});

const fatResult = computed(() => {
    const perCow = n(fat.buy) + n(fat.feed) * n(fat.days);
    const profitPerCow = n(fat.sale) - perCow;
    const head = Math.max(0, n(fat.head));
    const ret = perCow > 0 ? (profitPerCow / perCow) * 100 : 0;
    const months = n(fat.days) / 30;

    return {
        perCow,
        invest: perCow * head,
        profitPerCow,
        profit: profitPerCow * head,
        ret,
        perMonth: months > 0 ? ret / months : 0,
        breakEven: perCow,
    };
});

const cow = reactive({
    price: n(d.cow_price),
    liters: n(d.cow_liters_per_day),
    milk: n(d.milk_price),
    feed: n(d.cow_feed_per_day),
});

const MILKING_DAYS = 305;

const cowResult = computed(() => {
    const daily = n(cow.liters) * n(cow.milk) - n(cow.feed);
    const yearly =
        n(cow.liters) * n(cow.milk) * MILKING_DAYS - n(cow.feed) * 365;
    const monthlyAvg = yearly / 12;

    return {
        daily,
        monthly: daily * 30,
        yearly,
        monthlyAvg,
        payback: monthlyAvg > 0 ? n(cow.price) / monthlyAvg : null,
    };
});

const signTone = (value: number) =>
    value < 0 ? 'text-expense' : 'text-income';

/* ---------- tips ---------- */
const tipGroups: { title: string; icon: Component; items: string[] }[] = [
    {
        title: 'খাবার',
        icon: Wheat,
        items: [
            'সাইলেজ বানিয়ে রাখুন — বর্ষা ও শুকনো মৌসুমে কাঁচা ঘাসের অভাব মেটে, কেনা খাবার কম লাগে।',
            'নিজের জমির ঘাস (নেপিয়ার/পাকচং) আর ধানের খড়ই মূল খাবার রাখুন; দানাদার শুধু পরিপূরক।',
            'দানাদার মেপে দিন — দুধ বা ওজন অনুযায়ী ভাগ করুন; আন্দাজে দিলে খরচ বাড়ে, ফল বাড়ে না।',
        ],
    },
    {
        title: 'দুধ',
        icon: Milk,
        items: [
            'বাচ্চা হওয়ার আগে প্রায় ২ মাস শুষ্ক সময় দিন — পরের বেতে দুধ বেশি আসে।',
            'বাচ্চা হওয়ার ২–৩ মাসের মধ্যে গরম (হিট) দেখে সময়মতো বীজ দিন — লক্ষ্য বছরে একটি বাছুর।',
            'কম দুধের, বারবার বীজ না ধরা গাভী পালা লোকসান — সময়মতো বদলে নিন।',
        ],
    },
    {
        title: 'মোটাতাজা',
        icon: Beef,
        items: [
            'কেনার পরপরই কৃমিনাশক ও টিকা দিন, তারপর খাবার বাড়ান — নইলে খাবার নষ্ট হয়।',
            '১০০–১২০ দিনে বিক্রি করুন; এর বেশি রাখলে দিনে বাড়া কমে, খরচ চলতেই থাকে।',
            'কোরবানির ৪ মাস আগে ব্যাচ শুরু করুন — ঈদের হাটে দাম সবচেয়ে ভালো।',
            'নিয়মিত ওজন লিখুন — কোন গরু বাড়ছে না তা আগেই ধরা পড়ে।',
        ],
    },
    {
        title: 'ফসল',
        icon: Sprout,
        items: [
            'ধানের খড় গরুর খাবার — নিজের খড়ে কেনার খরচ বাঁচে।',
            'গোবর-সার জমিতে দিন — রাসায়নিক সারের খরচ কমে, মাটি ভালো থাকে।',
            'রবি মৌসুমে সরিষা বা ভুট্টা করুন — একই জমিতে বাড়তি আয়, ভুট্টার গাছ গরুর খাবারও।',
        ],
    },
    {
        title: 'বাজার',
        icon: Store,
        items: [
            'দুধ সরাসরি ক্রেতা, মিষ্টির দোকান বা দুধ সমিতিতে দিন — দালাল এড়ালে লিটারে বেশি দাম।',
            'হাটে গরু, ধান ও খড়ের দাম লিখে রাখুন — কখন বেচবেন, কখন কিনবেন বোঝা সহজ হয়।',
        ],
    },
    {
        title: 'টাকা',
        icon: HandCoins,
        items: [
            'বাকি টাকা নিয়মিত আদায় করুন — বাকিতে আটকে থাকা টাকা খামারের কাজে লাগে না।',
            'ঋণের সুদ হিসাবে ধরুন — সুদের চেয়ে কম লাভের কাজে ঋণ নেবেন না।',
            'প্রতি মাসে মাসিক রিপোর্ট দেখুন — কোন খাতে খরচ বাড়ছে তা আগেই চোখে পড়ে।',
        ],
    },
];
</script>

<template>
    <Head :title="t('লাভের হিসাব')" />

    <div class="flex flex-1 flex-col p-4 md:p-6">
        <PageTitle
            :title="t('লাভের হিসাব')"
            :subtitle="
                t('কোন কাজে লাভ, কোথায় ক্ষতি — আর সামনে কী করলে লাভ বাড়বে।')
            "
        >
            <template #actions>
                <div
                    class="inline-flex rounded-full border border-border bg-card p-0.5"
                    role="group"
                    :aria-label="t('বছর')"
                >
                    <button
                        v-for="y in years"
                        :key="y"
                        type="button"
                        :class="[
                            'num inline-flex min-h-8 items-center gap-1.5 rounded-full px-3 py-1 text-sm transition-colors',
                            y === year
                                ? 'bg-primary text-primary-foreground'
                                : 'text-muted-foreground hover:bg-accent',
                        ]"
                        @click="pickYear(y)"
                    >
                        {{ localDigits(y) }}
                    </button>
                </div>
            </template>
        </PageTitle>

        <div class="mx-auto w-full max-w-5xl space-y-5">
            <!-- Tabs -->
            <div
                class="grid grid-cols-3 gap-1 rounded-xl border border-border bg-muted/60 p-1"
                role="tablist"
            >
                <button
                    v-for="item in tabs"
                    :key="item.key"
                    type="button"
                    role="tab"
                    :aria-selected="tab === item.key"
                    :class="[
                        'flex flex-col items-center justify-center gap-1 rounded-lg px-1.5 py-2 text-center text-xs leading-tight font-medium transition-colors sm:flex-row sm:text-sm',
                        tab === item.key
                            ? 'bg-card text-foreground shadow-sm'
                            : 'text-muted-foreground hover:text-foreground',
                    ]"
                    @click="tab = item.key"
                >
                    <component :is="item.icon" class="size-4 shrink-0" />
                    <span>{{ t(item.label) }}</span>
                </button>
            </div>

            <!-- ============ TAB 1: where ============ -->
            <template v-if="tab === 'where'">
                <!-- Advice -->
                <section class="card border-turmeric/60 bg-turmeric/8">
                    <h2 class="card-title flex items-center gap-2">
                        <Lightbulb class="size-5 text-soil" />
                        {{ t('পরামর্শ') }}
                    </h2>
                    <ul class="space-y-2 text-sm">
                        <li
                            v-for="(item, i) in advice"
                            :key="i"
                            class="flex gap-2"
                        >
                            <CircleCheck
                                v-if="item.tone === 'good'"
                                class="mt-0.5 size-4 shrink-0 text-income"
                            />
                            <TrendingDown
                                v-else
                                class="mt-0.5 size-4 shrink-0 text-clay"
                            />
                            <span>{{ item.text }}</span>
                        </li>
                    </ul>
                </section>

                <!-- Key numbers -->
                <section>
                    <h2 class="card-title">{{ t('মূল হিসাবগুলো') }}</h2>
                    <div class="grid gap-3 sm:grid-cols-2 lg:grid-cols-3">
                        <div
                            v-for="tile in tiles"
                            :key="tile.label"
                            class="rounded-xl border border-border bg-card p-4"
                        >
                            <div class="flex items-start justify-between gap-3">
                                <div class="min-w-0">
                                    <p
                                        class="text-[13px] text-muted-foreground"
                                    >
                                        {{ t(tile.label) }}
                                    </p>
                                    <p
                                        v-if="tile.value !== null"
                                        :class="[
                                            'num mt-1 text-2xl font-semibold tracking-tight',
                                            tile.value < 0 && 'text-expense',
                                        ]"
                                    >
                                        {{ tk(tile.value)
                                        }}<span
                                            v-if="tile.unit"
                                            class="text-sm font-normal text-muted-foreground"
                                            >{{ t(tile.unit) }}</span
                                        >
                                    </p>
                                    <p
                                        v-else
                                        class="mt-1 text-lg font-semibold text-muted-foreground"
                                    >
                                        —
                                    </p>
                                </div>
                                <span :class="['rounded-lg p-2', tile.tone]">
                                    <component :is="tile.icon" class="size-5" />
                                </span>
                            </div>
                            <p
                                v-if="tile.value !== null"
                                class="mt-1.5 text-xs text-muted-foreground"
                            >
                                {{ t(tile.meaning) }}
                            </p>
                            <p v-else class="mt-1.5 text-xs text-clay">
                                {{
                                    t('এখনো হিসাব নেই — :what', {
                                        what: t(tile.missing),
                                    })
                                }}
                            </p>
                        </div>
                    </div>
                    <p
                        v-if="ratios.milk_liters > 0"
                        class="mt-2 text-xs text-muted-foreground"
                    >
                        {{
                            t('এ বছরে মোট দুধ :liters লিটার।', {
                                liters: num(ratios.milk_liters),
                            })
                        }}
                    </p>
                </section>

                <!-- Projects -->
                <section class="card">
                    <div
                        class="mb-3 flex flex-wrap items-baseline justify-between gap-2"
                    >
                        <h2 class="font-display text-lg">
                            {{ t('কোন কাজে কত লাভ') }}
                        </h2>
                        <span
                            v-if="projects.length"
                            :class="[
                                'num text-sm font-semibold',
                                signTone(totalProfit),
                            ]"
                            >{{
                                t('মোট :amount', { amount: taka(totalProfit) })
                            }}</span
                        >
                    </div>
                    <p v-if="projects.length === 0" class="empty">
                        {{ t('এ বছরে কোনো প্রজেক্টে আয়-খরচ লেখা হয়নি।') }}
                    </p>
                    <ol v-else class="space-y-3">
                        <li v-for="(row, i) in ranked" :key="row.id">
                            <div
                                class="flex items-center justify-between gap-2 text-sm"
                            >
                                <span
                                    class="flex min-w-0 items-center gap-2 font-medium"
                                >
                                    <CenterIcon :kind="row.kind" />
                                    <span class="truncate">{{
                                        t(row.name)
                                    }}</span>
                                    <span
                                        v-if="
                                            i === 0 &&
                                            row.profit > 0 &&
                                            ranked.length > 1
                                        "
                                        class="shrink-0 rounded-full bg-paddy/12 px-2 py-0.5 text-[11px] text-paddy"
                                        >{{ t('সবচেয়ে লাভজনক') }}</span
                                    >
                                    <span
                                        v-else-if="
                                            i === ranked.length - 1 &&
                                            ranked.length > 1 &&
                                            row.profit < 0
                                        "
                                        class="shrink-0 rounded-full bg-clay/15 px-2 py-0.5 text-[11px] text-clay"
                                        >{{ t('নজর দিন') }}</span
                                    >
                                </span>
                                <span
                                    :class="[
                                        'num shrink-0 font-semibold',
                                        signTone(row.profit),
                                    ]"
                                    >{{ taka(row.profit) }}</span
                                >
                            </div>
                            <div
                                class="mt-1 h-2 overflow-hidden rounded-full bg-muted"
                            >
                                <div
                                    :class="[
                                        'h-full rounded-full',
                                        row.profit < 0
                                            ? 'bg-expense'
                                            : 'bg-income',
                                    ]"
                                    :style="{
                                        width: `${Math.max(2, Math.round((Math.abs(row.profit) / maxProfit) * 100))}%`,
                                    }"
                                />
                            </div>
                            <p
                                class="num mt-1 flex flex-wrap gap-x-3 text-xs text-muted-foreground"
                            >
                                <span>{{
                                    t('আয় :amount', {
                                        amount: taka(row.income),
                                    })
                                }}</span>
                                <span>{{
                                    t('খরচ :amount', {
                                        amount: taka(row.expense),
                                    })
                                }}</span>
                                <span v-if="row.margin !== null">{{
                                    t('মার্জিন :pct%', {
                                        pct: num(row.margin),
                                    })
                                }}</span>
                            </p>
                        </li>
                    </ol>
                </section>

                <!-- Animals -->
                <section class="card">
                    <h2 class="card-title">{{ t('কোন গরুতে কত লাভ') }}</h2>
                    <p
                        class="mb-3 text-xs leading-relaxed text-muted-foreground"
                    >
                        {{
                            t(
                                'বিক্রি হলে: বিক্রির দাম − কেনা ও পালনের সব খরচ। দুধের গাভী: দুধের দাম − পালনের খরচ। বাড়ছে: এখন পর্যন্ত পালনের খরচ (বিক্রি হলে ফেরত আসবে)।',
                            )
                        }}
                    </p>
                    <div class="mb-3 flex flex-wrap gap-1.5">
                        <button
                            v-for="f in filters"
                            :key="f.key"
                            type="button"
                            :class="[
                                'inline-flex min-h-8 items-center gap-1.5 rounded-full border px-3 py-1 text-xs transition-colors',
                                filter === f.key
                                    ? 'border-primary bg-primary text-primary-foreground'
                                    : 'border-input bg-card hover:bg-accent',
                            ]"
                            @click="filter = f.key"
                        >
                            {{ t(f.label) }}
                            <span class="num opacity-70">{{ f.count }}</span>
                        </button>
                    </div>
                    <p v-if="shownAnimals.length === 0" class="empty">
                        {{ t('এখানে কোনো গরু নেই।') }}
                    </p>
                    <ul v-else class="divide-y divide-border/60">
                        <li
                            v-for="a in shownAnimals"
                            :key="a.id"
                            class="py-2.5"
                        >
                            <div class="flex items-start justify-between gap-3">
                                <div class="min-w-0">
                                    <Link
                                        :href="`/animals/${a.id}`"
                                        class="font-medium text-primary underline underline-offset-2"
                                        >{{ a.tag
                                        }}<span
                                            v-if="a.name"
                                            class="font-normal text-muted-foreground"
                                        >
                                            ({{ a.name }})</span
                                        ></Link
                                    >
                                    <p class="text-xs text-muted-foreground">
                                        <span v-if="a.center">{{
                                            t(a.center)
                                        }}</span>
                                        <span
                                            :class="[
                                                'ml-1.5 inline-block rounded-full px-2 py-0.5 text-[11px] font-medium',
                                                kindTone[a.result_kind],
                                            ]"
                                            >{{
                                                t(kindLabel[a.result_kind])
                                            }}</span
                                        >
                                    </p>
                                </div>
                                <span
                                    :class="[
                                        'num shrink-0 font-semibold',
                                        signTone(a.result),
                                    ]"
                                    >{{ taka(a.result) }}</span
                                >
                            </div>
                            <p
                                class="num mt-1 flex flex-wrap gap-x-3 text-xs text-muted-foreground"
                            >
                                <span>{{
                                    t('খরচ :amount', {
                                        amount: taka(a.spent),
                                    })
                                }}</span>
                                <span v-if="a.sale !== null">{{
                                    t('বিক্রি :amount', {
                                        amount: taka(a.sale),
                                    })
                                }}</span>
                                <span v-if="a.milk_liters > 0">{{
                                    t('দুধ :liters লি. (:value)', {
                                        liters: num(a.milk_liters),
                                        value: taka(a.milk_value),
                                    })
                                }}</span>
                                <span
                                    v-if="a.gain_per_day !== null"
                                    :class="
                                        a.gain_per_day < 0.5 &&
                                        a.result_kind === 'growing'
                                            ? 'text-clay'
                                            : ''
                                    "
                                    >{{
                                        t('দিনে বাড়া :kg কেজি', {
                                            kg: num(a.gain_per_day, 2),
                                        })
                                    }}</span
                                >
                            </p>
                        </li>
                    </ul>
                </section>
            </template>

            <!-- ============ TAB 2: planner ============ -->
            <template v-else-if="tab === 'plan'">
                <p class="text-sm text-muted-foreground">
                    {{
                        t(
                            'সংখ্যা বদলালেই হিসাব বদলে যাবে। কিছুই সেভ হয় না — শুধু ভেবে দেখার জন্য।',
                        )
                    }}
                </p>

                <!-- a. Paddy -->
                <section class="card">
                    <h2 class="card-title flex items-center gap-2">
                        <Wheat class="size-5 text-paddy" />
                        {{ t('আরও জমি লিজ নিয়ে ধান') }}
                    </h2>
                    <div class="grid grid-cols-2 gap-3 sm:grid-cols-4">
                        <label class="plan-field">
                            <span>{{ t('কত বিঘা') }}</span>
                            <input
                                v-model.number="paddy.bigha"
                                type="number"
                                min="0"
                                step="0.5"
                                inputmode="decimal"
                                class="plan-input"
                            />
                        </label>
                        <label class="plan-field">
                            <span>{{ t('বিঘাপ্রতি বছরে খাজনা (৳)') }}</span>
                            <input
                                v-model.number="paddy.rent"
                                type="number"
                                min="0"
                                inputmode="decimal"
                                class="plan-input"
                            />
                        </label>
                        <label class="plan-field">
                            <span>{{
                                t('বিঘাপ্রতি চাষের খরচ, খাজনা ছাড়া (৳)')
                            }}</span>
                            <input
                                v-model.number="paddy.cost"
                                type="number"
                                min="0"
                                inputmode="decimal"
                                class="plan-input"
                            />
                        </label>
                        <label class="plan-field">
                            <span>{{ t('ফলন (মণ/বিঘা)') }}</span>
                            <input
                                v-model.number="paddy.mon"
                                type="number"
                                min="0"
                                step="0.5"
                                inputmode="decimal"
                                class="plan-input"
                            />
                        </label>
                        <label class="plan-field">
                            <span>{{ t('ধানের দাম (৳/মণ)') }}</span>
                            <input
                                v-model.number="paddy.price"
                                type="number"
                                min="0"
                                inputmode="decimal"
                                class="plan-input"
                            />
                        </label>
                        <label class="plan-field">
                            <span>{{ t('খড় (কেজি/বিঘা)') }}</span>
                            <input
                                v-model.number="paddy.straw"
                                type="number"
                                min="0"
                                inputmode="decimal"
                                class="plan-input"
                            />
                        </label>
                        <label class="plan-field">
                            <span>{{ t('খড়ের দাম (৳/কেজি)') }}</span>
                            <input
                                v-model.number="paddy.strawPrice"
                                type="number"
                                min="0"
                                step="0.5"
                                inputmode="decimal"
                                class="plan-input"
                            />
                        </label>
                        <label class="plan-field">
                            <span>{{ t('বছরে কয় মৌসুম (১–৩)') }}</span>
                            <input
                                v-model.number="paddy.seasons"
                                type="number"
                                min="1"
                                max="3"
                                step="1"
                                inputmode="numeric"
                                class="plan-input"
                            />
                        </label>
                    </div>

                    <div class="result-box">
                        <p class="result-head">{{ t('প্রতি মৌসুমে') }}</p>
                        <dl class="result-grid">
                            <dt>{{ t('আয় (ধান + খড়)') }}</dt>
                            <dd>{{ tk(paddyResult.seasonIncome) }}</dd>
                            <dt>{{ t('চাষের খরচ') }}</dt>
                            <dd>{{ tk(paddyResult.seasonCost) }}</dd>
                            <dt>{{ t('খাজনার আগে লাভ') }}</dt>
                            <dd :class="signTone(paddyResult.seasonProfit)">
                                {{ tk(paddyResult.seasonProfit) }}
                            </dd>
                        </dl>
                        <p class="result-head mt-3">{{ t('বছরে') }}</p>
                        <dl class="result-grid">
                            <dt>{{ t('খাজনা (বছরে একবার)') }}</dt>
                            <dd>{{ tk(paddyResult.rent) }}</dd>
                            <dt class="font-semibold text-foreground">
                                {{ t('বছরে লাভ') }}
                            </dt>
                            <dd
                                :class="[
                                    'text-base font-semibold',
                                    signTone(paddyResult.yearProfit),
                                ]"
                            >
                                {{ tk(paddyResult.yearProfit) }}
                            </dd>
                            <dt>{{ t('প্রতি বিঘায় লাভ') }}</dt>
                            <dd :class="signTone(paddyResult.perBigha)">
                                {{ tk(paddyResult.perBigha) }}
                            </dd>
                            <dt>{{ t('খরচ উঠতে ফলন লাগবে') }}</dt>
                            <dd>
                                {{
                                    paddyResult.breakEven === null
                                        ? '—'
                                        : t(':mon মণ/বিঘা', {
                                              mon: num(
                                                  Math.max(
                                                      0,
                                                      paddyResult.breakEven,
                                                  ),
                                              ),
                                          })
                                }}
                            </dd>
                        </dl>
                        <p
                            v-if="paddyResult.strawSaving > 0"
                            class="mt-3 rounded-lg bg-paddy/10 px-3 py-2 text-sm text-paddy"
                        >
                            {{
                                t(
                                    'খড় গরুকে দিলে কেনার খরচ বাঁচবে :amount (বছরে :kg কেজি খড়)।',
                                    {
                                        amount: tk(paddyResult.strawSaving),
                                        kg: num(paddyResult.strawKg, 0),
                                    },
                                )
                            }}
                        </p>
                    </div>
                    <p class="formula">
                        {{
                            t(
                                'হিসাব: মৌসুমে আয় = বিঘা × (ফলন × ধানের দাম + খড় × খড়ের দাম)। বছরে লাভ = (মৌসুমে আয় − চাষের খরচ) × মৌসুম − খাজনা। খরচ উঠতে ফলন = (চাষের খরচ + খাজনা ÷ মৌসুম − খড়ের আয়) ÷ ধানের দাম।',
                            )
                        }}
                    </p>
                    <p class="source-note">
                        {{
                            t(
                                'শুরুর সংখ্যাগুলো যেখানে পাওয়া গেছে খামারের নিজের হিসাব থেকে নেওয়া — নিজের মতো বদলান।',
                            )
                        }}
                    </p>
                </section>

                <!-- b. Fattening -->
                <section class="card">
                    <h2 class="card-title flex items-center gap-2">
                        <Beef class="size-5 text-clay" />
                        {{ t('মোটাতাজা বাড়ানো') }}
                    </h2>
                    <div class="grid grid-cols-2 gap-3 sm:grid-cols-5">
                        <label class="plan-field">
                            <span>{{ t('গরুর সংখ্যা') }}</span>
                            <input
                                v-model.number="fat.head"
                                type="number"
                                min="0"
                                step="1"
                                inputmode="numeric"
                                class="plan-input"
                            />
                        </label>
                        <label class="plan-field">
                            <span>{{ t('কেনা দাম / গরু (৳)') }}</span>
                            <input
                                v-model.number="fat.buy"
                                type="number"
                                min="0"
                                inputmode="decimal"
                                class="plan-input"
                            />
                        </label>
                        <label class="plan-field">
                            <span>{{ t('দিনে খাবার ও খরচ / গরু (৳)') }}</span>
                            <input
                                v-model.number="fat.feed"
                                type="number"
                                min="0"
                                inputmode="decimal"
                                class="plan-input"
                            />
                        </label>
                        <label class="plan-field">
                            <span>{{ t('কত দিন রাখবেন') }}</span>
                            <input
                                v-model.number="fat.days"
                                type="number"
                                min="1"
                                step="1"
                                inputmode="numeric"
                                class="plan-input"
                            />
                        </label>
                        <label class="plan-field col-span-2 sm:col-span-1">
                            <span>{{ t('বিক্রির আশা / গরু (৳)') }}</span>
                            <input
                                v-model.number="fat.sale"
                                type="number"
                                min="0"
                                inputmode="decimal"
                                class="plan-input"
                            />
                        </label>
                    </div>
                    <div class="result-box">
                        <dl class="result-grid">
                            <dt>{{ t('প্রতি গরুতে মোট খরচ') }}</dt>
                            <dd>{{ tk(fatResult.perCow) }}</dd>
                            <dt>{{ t('মোট বিনিয়োগ লাগবে') }}</dt>
                            <dd>{{ tk(fatResult.invest) }}</dd>
                            <dt>{{ t('প্রতি গরুতে লাভ') }}</dt>
                            <dd :class="signTone(fatResult.profitPerCow)">
                                {{ tk(fatResult.profitPerCow) }}
                            </dd>
                            <dt class="font-semibold text-foreground">
                                {{ t('মোট লাভ') }}
                            </dt>
                            <dd
                                :class="[
                                    'text-base font-semibold',
                                    signTone(fatResult.profit),
                                ]"
                            >
                                {{ tk(fatResult.profit) }}
                            </dd>
                            <dt>{{ t('টাকায় ফেরত') }}</dt>
                            <dd :class="signTone(fatResult.ret)">
                                {{
                                    t(':pct% (মাসে :month%)', {
                                        pct: num(fatResult.ret),
                                        month: num(fatResult.perMonth),
                                    })
                                }}
                            </dd>
                            <dt>{{ t('খরচ উঠতে বিক্রির দাম লাগবে') }}</dt>
                            <dd>{{ tk(fatResult.breakEven) }}</dd>
                        </dl>
                    </div>
                    <p class="formula">
                        {{
                            t(
                                'হিসাব: প্রতি গরুতে খরচ = কেনা দাম + দিনের খরচ × দিন। লাভ = বিক্রির দাম − খরচ। ফেরত % = লাভ ÷ খরচ × ১০০। মাসে % = ফেরত % ÷ (দিন ÷ ৩০)।',
                            )
                        }}
                    </p>
                    <p class="source-note">
                        {{
                            t(
                                'শুরুর সংখ্যাগুলো যেখানে পাওয়া গেছে খামারের নিজের হিসাব থেকে নেওয়া — নিজের মতো বদলান।',
                            )
                        }}
                    </p>
                </section>

                <!-- c. New cow -->
                <section class="card">
                    <h2 class="card-title flex items-center gap-2">
                        <Milk class="size-5 text-pond" />
                        {{ t('নতুন দুধের গাভী') }}
                    </h2>
                    <div class="grid grid-cols-2 gap-3 sm:grid-cols-4">
                        <label class="plan-field">
                            <span>{{ t('গাভীর দাম (৳)') }}</span>
                            <input
                                v-model.number="cow.price"
                                type="number"
                                min="0"
                                inputmode="decimal"
                                class="plan-input"
                            />
                        </label>
                        <label class="plan-field">
                            <span>{{ t('দিনে দুধ (লিটার)') }}</span>
                            <input
                                v-model.number="cow.liters"
                                type="number"
                                min="0"
                                step="0.5"
                                inputmode="decimal"
                                class="plan-input"
                            />
                        </label>
                        <label class="plan-field">
                            <span>{{ t('দুধের দাম (৳/লি.)') }}</span>
                            <input
                                v-model.number="cow.milk"
                                type="number"
                                min="0"
                                inputmode="decimal"
                                class="plan-input"
                            />
                        </label>
                        <label class="plan-field">
                            <span>{{ t('দিনে খাবার খরচ (৳)') }}</span>
                            <input
                                v-model.number="cow.feed"
                                type="number"
                                min="0"
                                inputmode="decimal"
                                class="plan-input"
                            />
                        </label>
                    </div>
                    <div class="result-box">
                        <dl class="result-grid">
                            <dt>{{ t('দুধ দেওয়ার দিনে লাভ') }}</dt>
                            <dd :class="signTone(cowResult.daily)">
                                {{ tk(cowResult.daily) }}
                            </dd>
                            <dt>{{ t('দুধ দেওয়ার মাসে লাভ') }}</dt>
                            <dd :class="signTone(cowResult.monthly)">
                                {{ tk(cowResult.monthly) }}
                            </dd>
                            <dt>{{ t('সারা বছরে লাভ (শুষ্ক সময় ধরে)') }}</dt>
                            <dd :class="signTone(cowResult.yearly)">
                                {{ tk(cowResult.yearly) }}
                            </dd>
                            <dt class="font-semibold text-foreground">
                                {{ t('গাভীর দাম উঠবে') }}
                            </dt>
                            <dd class="text-base font-semibold">
                                {{
                                    cowResult.payback === null
                                        ? t('উঠবে না')
                                        : t(':months মাসে', {
                                              months: num(cowResult.payback),
                                          })
                                }}
                            </dd>
                        </dl>
                    </div>
                    <p class="formula">
                        {{
                            t(
                                'হিসাব: দিনে লাভ = লিটার × দুধের দাম − খাবার খরচ। বছরে লাভ = লিটার × দাম × ৩০৫ দিন − খাবার × ৩৬৫ দিন (প্রায় ২ মাস শুষ্ক সময়েও খাবার লাগে)। দাম উঠবে = গাভীর দাম ÷ (বছরে লাভ ÷ ১২)।',
                            )
                        }}
                    </p>
                    <p class="source-note">
                        {{
                            t(
                                'শুরুর সংখ্যাগুলো যেখানে পাওয়া গেছে খামারের নিজের হিসাব থেকে নেওয়া — নিজের মতো বদলান।',
                            )
                        }}
                    </p>
                </section>
            </template>

            <!-- ============ TAB 3: tips ============ -->
            <template v-else>
                <p class="text-sm text-muted-foreground">
                    {{
                        t(
                            'ছোট খামারে লাভ বাড়ানোর পরীক্ষিত উপায় — একটা একটা করে চালু করুন, আর এই পাতায় ফল দেখুন।',
                        )
                    }}
                </p>
                <div class="grid gap-4 md:grid-cols-2">
                    <section
                        v-for="group in tipGroups"
                        :key="group.title"
                        class="card"
                    >
                        <h2 class="card-title flex items-center gap-2">
                            <span
                                class="rounded-lg bg-paddy/12 p-1.5 text-paddy"
                            >
                                <component :is="group.icon" class="size-4" />
                            </span>
                            {{ t(group.title) }}
                        </h2>
                        <ul class="space-y-2 text-sm">
                            <li
                                v-for="item in group.items"
                                :key="item"
                                class="flex gap-2"
                            >
                                <TrendingUp
                                    class="mt-0.5 size-4 shrink-0 text-income"
                                />
                                <span>{{ t(item) }}</span>
                            </li>
                        </ul>
                    </section>
                </div>
                <p class="flex items-start gap-2 text-xs text-muted-foreground">
                    <Coins class="mt-0.5 size-4 shrink-0" />
                    {{
                        t(
                            'ঔষধ বা টিকার মাত্রা নিজে ঠিক করবেন না — প্রাণিসম্পদ অফিস বা পশু ডাক্তারের পরামর্শ নিন।',
                        )
                    }}
                </p>
            </template>
        </div>
    </div>
</template>

<style scoped>
@reference '../../../css/app.css';

.card {
    @apply min-w-0 rounded-xl border border-border bg-card p-4;
}

.card-title {
    @apply mb-3 font-display text-lg;
}

.empty {
    @apply text-sm text-muted-foreground;
}

.num {
    white-space: nowrap;
}

.plan-field {
    @apply grid content-end gap-1 text-xs font-medium text-muted-foreground;
}

.plan-input {
    @apply h-10 w-full min-w-0 rounded-md border border-input bg-background px-3 text-base whitespace-nowrap text-foreground shadow-xs outline-none focus-visible:border-ring focus-visible:ring-[3px] focus-visible:ring-ring/50 md:text-sm;
}

.result-box {
    @apply mt-4 rounded-lg border border-border/70 bg-muted/40 p-3;
}

.result-head {
    @apply mb-1 text-xs font-semibold tracking-wide text-paddy;
}

.result-grid {
    @apply grid grid-cols-[1fr_auto] gap-x-3 gap-y-1.5 text-sm;
}

.result-grid dt {
    @apply text-muted-foreground;
}

.result-grid dd {
    @apply text-right whitespace-nowrap;
}

.formula {
    @apply mt-3 text-xs leading-relaxed text-muted-foreground;
}

.source-note {
    @apply mt-1 text-[11px] text-muted-foreground/80 italic;
}
</style>
