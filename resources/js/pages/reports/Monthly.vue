<script setup lang="ts">
import { Head, router } from '@inertiajs/vue3';
import {
    Beef,
    Coins,
    Milk,
    Printer,
    Send,
    TrendingDown,
    TrendingUp,
} from '@lucide/vue';
import { computed, ref, watch } from 'vue';
import CenterIcon from '@/components/khamar/CenterIcon.vue';
import DateField from '@/components/khamar/DateField.vue';
import PageTitle from '@/components/khamar/PageTitle.vue';
import StatTile from '@/components/khamar/StatTile.vue';
import { Button } from '@/components/ui/button';
import { currentLocale, t } from '@/lib/i18n';
import { day, monthFull, qty, taka } from '@/lib/khamar';

defineOptions({
    layout: {
        breadcrumbs: [{ title: 'মাসিক রিপোর্ট', href: '/reports/monthly' }],
    },
});

type Totals = { income: number; expense: number; profit: number };
type AnimalRow = {
    id: number;
    tag: string;
    name: string | null;
    date: string | null;
    amount: number;
};

const props = defineProps<{
    month: string;
    previousMonth: string;
    farm: Totals;
    previous: Totals;
    projects: {
        id: number;
        name: string;
        kind: string;
        depth: number;
        income: number;
        expense: number;
        profit: number;
    }[];
    expenses: { id: number; name: string; amount: number }[];
    money: {
        id: number;
        name: string;
        subtype: string | null;
        balance: number;
    }[];
    milk: { liters: number; previous: number };
    herd: { center: string; count: number }[];
    herdTotal: number;
    animals: { bought: AnimalRow[]; sold: AnimalRow[]; dead: AnimalRow[] };
    batches: {
        id: number;
        label: string;
        center: string;
        span: string;
        head: number;
        spent: number;
        avg_gain: number | null;
    }[];
    due: {
        id: number;
        tag: string | null;
        name: string | null;
        expected_on: string;
        days_left: number | null;
        result_label: string;
        months: string;
    }[];
    feed: {
        id: number;
        name: string;
        used_qty: number;
        unit: string;
        used_value: number;
    }[];
    notes: {
        id: number;
        date: string;
        kind: string;
        title: string;
        body: string | null;
    }[];
    goals: {
        id: number;
        title: string;
        unit: string | null;
        target_value: number;
        current_value: number;
        due_on: string | null;
        achieved: boolean;
        progress: number;
    }[];
}>();

const month = ref(props.month);
watch(month, (value) => {
    if (value && value !== props.month) {
        router.get('/reports/monthly', { month: value });
    }
});

const monthName = computed(() => monthFull(props.month));
const previousName = computed(() => monthFull(props.previousMonth));

/** "▲ 12%" / "▼ 8%" against last month; null when last month had nothing to compare. */
function change(now: number, before: number): string | null {
    if (before === 0) {
        return now === 0 ? null : t('নতুন');
    }

    const pct = Math.round(((now - before) / Math.abs(before)) * 100);

    if (pct === 0) {
        return t('— ০% পরিবর্তন নেই');
    }

    return `${pct > 0 ? '▲' : '▼'} ${Math.abs(pct)}%`;
}

const hint = (now: number, before: number, text: string) => {
    const c = change(now, before);

    return c
        ? t(':change · গত মাসে :text', { change: c, text })
        : t('গত মাসে :text', { text });
};

const moneyTotal = computed(() =>
    props.money.reduce((sum, row) => sum + row.balance, 0),
);
const feedTotal = computed(() =>
    props.feed.reduce((sum, row) => sum + row.used_value, 0),
);
const maxExpense = computed(() =>
    Math.max(1, ...props.expenses.map((row) => row.amount)),
);

const animalGroups = computed(() =>
    [
        { key: 'bought', title: t('কেনা হয়েছে'), rows: props.animals.bought },
        { key: 'sold', title: t('বিক্রি হয়েছে'), rows: props.animals.sold },
        {
            key: 'dead',
            title: t('মারা গেছে (খাতার মূল্য)'),
            rows: props.animals.dead,
        },
    ].filter((group) => group.rows.length > 0),
);
const groupTotal = (rows: AnimalRow[]) =>
    rows.reduce((sum, row) => sum + row.amount, 0);

const print = () => window.print();

const periodLabel = computed(() => {
    const [y, m] = props.month.split('-').map(Number);
    const last = new Date(y, m, 0).getDate();

    return t('১–:last :month', { last, month: monthFull(`${y}-${m}`) });
});
const preparedOn = new Date().toLocaleDateString(
    currentLocale() === 'en' ? 'en-GB' : 'bn-BD',
    {
        day: 'numeric',
        month: 'long',
        year: 'numeric',
    },
);

/** One plain sentence a reader can take away without reading the tables. */
const summary = computed(() => {
    const f = props.farm;
    const p = props.previous;
    const parts = [
        t(
            f.profit >= 0
                ? ':month-এ আয় :income, খরচ :expense — লাভ :amount।'
                : ':month-এ আয় :income, খরচ :expense — ক্ষতি :amount।',
            {
                month: monthName.value,
                income: taka(f.income),
                expense: taka(f.expense),
                amount: taka(Math.abs(f.profit)),
            },
        ),
    ];

    if (p.expense > 0) {
        const pct = Math.round(((f.expense - p.expense) / p.expense) * 100);
        parts.push(
            pct === 0
                ? t('খরচ গত মাসের সমান।')
                : t(
                      pct > 0
                          ? 'খরচ গত মাসের চেয়ে :pct% বেশি।'
                          : 'খরচ গত মাসের চেয়ে :pct% কম।',
                      { pct: Math.abs(pct) },
                  ),
        );
    }

    parts.push(
        t('দুধ :milk, খামারে :herdটি গরু।', {
            milk: qty(props.milk.liters, t('লিটার')),
            herd: props.herdTotal,
        }),
    );

    if (props.expenses[0]) {
        parts.push(
            t('সবচেয়ে বড় খরচ: :name (:amount)।', {
                name: t(props.expenses[0].name),
                amount: taka(props.expenses[0].amount),
            }),
        );
    }

    return parts.join(' ');
});

function share() {
    const lines = [
        t('মাটির খামার — :month-এর হিসাব', { month: monthName.value }),
        t('আয়: :amount', { amount: taka(props.farm.income) }),
        t('খরচ: :amount', { amount: taka(props.farm.expense) }),
        t(props.farm.profit >= 0 ? 'লাভ: :amount' : 'ক্ষতি: :amount', {
            amount: taka(Math.abs(props.farm.profit)),
        }),
        t('দুধ: :amount', {
            amount: qty(props.milk.liters, t('লিটার')),
        }),
        t('গরু: :nটি', { n: props.herdTotal }),
    ];

    window.open(
        `https://wa.me/?text=${encodeURIComponent(lines.join('\n'))}`,
        '_blank',
        'noopener',
    );
}
</script>

<template>
    <Head :title="t('মাসিক রিপোর্ট — :month', { month: monthName })" />

    <div class="monthly-report flex flex-1 flex-col p-4 md:p-6">
        <div class="no-print">
            <PageTitle
                :title="t('মাসিক রিপোর্ট')"
                :subtitle="
                    t(
                        'এক পাতায় মাসের পুরো হিসাব — প্রিন্ট করুন, PDF সেভ করুন বা WhatsApp-এ পাঠান।',
                    )
                "
            >
                <template #actions>
                    <!-- One row, one height: the month box and both buttons line up. -->
                    <DateField
                        v-model="month"
                        mode="month"
                        class="w-44"
                        :aria-label="t('মাস')"
                    />
                    <Button variant="secondary" @click="print"
                        ><Printer /> {{ t('প্রিন্ট / PDF সেভ') }}</Button
                    >
                    <Button @click="share"
                        ><Send /> {{ t('WhatsApp-এ পাঠান') }}</Button
                    >
                </template>
            </PageTitle>
        </div>

        <div class="mx-auto w-full max-w-5xl space-y-5">
            <!-- Letterhead -->
            <div
                class="report-card letterhead overflow-hidden rounded-xl border border-border bg-card"
            >
                <div
                    class="flex flex-wrap items-start justify-between gap-4 p-4 md:p-5"
                >
                    <div class="flex min-w-0 items-center gap-3">
                        <img
                            src="/favicon.svg"
                            alt=""
                            class="size-12 shrink-0 rounded-xl border border-border bg-background p-1.5"
                        />
                        <div class="min-w-0">
                            <p
                                class="font-display text-xl leading-tight md:text-2xl"
                            >
                                {{ t('মাটির খামার') }}
                            </p>
                            <p class="text-sm text-muted-foreground">
                                {{
                                    t(
                                        'Klok Ads কৃষি উদ্যোগ · গোপাই কাটখৈর, নওগাঁ সদর',
                                    )
                                }}
                            </p>
                        </div>
                    </div>
                    <div class="text-left sm:text-right">
                        <p
                            class="text-[11px] font-semibold tracking-wide text-paddy"
                        >
                            {{ t('মাসিক হিসাব বিবরণী') }}
                        </p>
                        <p class="font-display text-xl leading-tight">
                            {{ monthName }}
                        </p>
                        <p class="text-xs text-muted-foreground">
                            {{
                                t('সময়কাল :period · প্রস্তুত :date', {
                                    period: periodLabel,
                                    date: preparedOn,
                                })
                            }}
                        </p>
                    </div>
                </div>
                <div class="letterhead-rule" />
                <p class="px-4 py-3 text-sm leading-relaxed md:px-5">
                    <b>{{ t('সারসংক্ষেপ:') }}</b> {{ summary }}
                </p>
            </div>

            <!-- Summary -->
            <div
                class="report-card grid grid-cols-2 gap-3 lg:grid-cols-4 print:grid-cols-4"
            >
                <StatTile
                    :label="t('মোট আয়')"
                    :value="taka(farm.income)"
                    :hint="
                        hint(
                            farm.income,
                            previous.income,
                            taka(previous.income),
                        )
                    "
                    :icon="TrendingUp"
                    tone="paddy"
                />
                <StatTile
                    :label="t('মোট খরচ')"
                    :value="taka(farm.expense)"
                    :hint="
                        hint(
                            farm.expense,
                            previous.expense,
                            taka(previous.expense),
                        )
                    "
                    :icon="TrendingDown"
                    tone="clay"
                />
                <StatTile
                    :label="farm.profit >= 0 ? t('লাভ') : t('ক্ষতি')"
                    :value="taka(farm.profit)"
                    :hint="
                        hint(
                            farm.profit,
                            previous.profit,
                            taka(previous.profit),
                        )
                    "
                    :icon="Coins"
                    tone="turmeric"
                />
                <StatTile
                    :label="t('দুধ')"
                    :value="qty(milk.liters, t('লি.'))"
                    :hint="
                        hint(
                            milk.liters,
                            milk.previous,
                            qty(milk.previous, t('লি.')),
                        )
                    "
                    :icon="Milk"
                    tone="pond"
                />
            </div>
            <p class="text-xs text-muted-foreground">
                {{
                    t(
                        'তুলনা: :month-এর সাথে। নিজের ঘাস/খড় এক খাত থেকে আরেক খাতে দেওয়া খামারের মোট হিসাবে ধরা হয়নি।',
                        { month: previousName },
                    )
                }}
            </p>

            <div
                class="grid gap-5 lg:grid-cols-2 print:grid-cols-2 print:gap-3"
            >
                <!-- Projects -->
                <section
                    class="report-card card lg:col-span-2 print:col-span-2"
                >
                    <h2 class="card-title">{{ t('খাত অনুযায়ী আয়-খরচ') }}</h2>
                    <p v-if="projects.length === 0" class="empty">
                        {{ t('এই মাসে কোনো খাতে লেনদেন নেই।') }}
                    </p>
                    <div v-else class="-mx-1 overflow-x-auto">
                        <table class="w-full min-w-[22rem] text-sm">
                            <thead>
                                <tr class="text-xs text-muted-foreground">
                                    <th
                                        class="px-1 py-1.5 text-left font-medium"
                                    >
                                        {{ t('খাত') }}
                                    </th>
                                    <th
                                        class="px-1 py-1.5 text-right font-medium"
                                    >
                                        {{ t('আয়') }}
                                    </th>
                                    <th
                                        class="px-1 py-1.5 text-right font-medium"
                                    >
                                        {{ t('খরচ') }}
                                    </th>
                                    <th
                                        class="px-1 py-1.5 text-right font-medium"
                                    >
                                        {{ t('লাভ') }}
                                    </th>
                                </tr>
                            </thead>
                            <tbody>
                                <tr
                                    v-for="row in projects"
                                    :key="row.id"
                                    :class="[
                                        'border-t border-border/60',
                                        row.depth === 0 && 'font-semibold',
                                    ]"
                                >
                                    <td class="px-1 py-1.5">
                                        <span
                                            :class="[
                                                'flex items-center gap-2',
                                                row.depth === 1 &&
                                                    'pl-6 font-normal',
                                            ]"
                                        >
                                            <CenterIcon
                                                v-if="row.depth === 0"
                                                :kind="row.kind"
                                                class="no-print"
                                            />
                                            <span
                                                v-else
                                                class="text-muted-foreground"
                                                >└</span
                                            >
                                            {{ t(row.name) }}
                                        </span>
                                    </td>
                                    <td class="num px-1 py-1.5 text-right">
                                        {{ taka(row.income) }}
                                    </td>
                                    <td class="num px-1 py-1.5 text-right">
                                        {{ taka(row.expense) }}
                                    </td>
                                    <td
                                        :class="[
                                            'num px-1 py-1.5 text-right',
                                            row.profit < 0
                                                ? 'text-expense'
                                                : 'text-income',
                                        ]"
                                    >
                                        {{ taka(row.profit) }}
                                    </td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                </section>

                <!-- Top expenses -->
                <section class="report-card card">
                    <h2 class="card-title">{{ t('বড় খরচগুলো') }}</h2>
                    <p v-if="expenses.length === 0" class="empty">
                        {{ t('এই মাসে খরচ লেখা হয়নি।') }}
                    </p>
                    <ul v-else class="space-y-2.5">
                        <li v-for="row in expenses" :key="row.id">
                            <div
                                class="flex items-baseline justify-between gap-3 text-sm"
                            >
                                <span class="min-w-0 truncate">{{
                                    t(row.name)
                                }}</span>
                                <span class="num shrink-0 font-semibold">{{
                                    taka(row.amount)
                                }}</span>
                            </div>
                            <div
                                class="mt-1 h-1.5 overflow-hidden rounded-full bg-muted"
                            >
                                <div
                                    class="bar h-full rounded-full bg-clay"
                                    :style="{
                                        width: `${Math.round((row.amount / maxExpense) * 100)}%`,
                                    }"
                                />
                            </div>
                        </li>
                    </ul>
                </section>

                <!-- Money places -->
                <section class="report-card card">
                    <h2 class="card-title">{{ t('টাকা কোথায় আছে') }}</h2>
                    <p class="mb-2 text-xs text-muted-foreground">
                        {{ t('আজ পর্যন্ত খাতার হিসাবে') }}
                    </p>
                    <p v-if="money.length === 0" class="empty">
                        {{ t('কোনো নগদ/ব্যাংক হিসাব নেই।') }}
                    </p>
                    <table v-else class="w-full text-sm">
                        <tbody>
                            <tr
                                v-for="row in money"
                                :key="row.id"
                                class="border-b border-border/60"
                            >
                                <td class="py-1.5">{{ t(row.name) }}</td>
                                <td class="num py-1.5 text-right">
                                    {{ taka(row.balance) }}
                                </td>
                            </tr>
                            <tr class="font-semibold">
                                <td class="pt-2">{{ t('মোট') }}</td>
                                <td class="num pt-2 text-right">
                                    {{ taka(moneyTotal) }}
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </section>

                <!-- Herd -->
                <section class="report-card card">
                    <h2 class="card-title flex items-center gap-2">
                        <Beef class="no-print size-5 text-clay" />
                        {{ t('মাস শেষে গরু: :nটি', { n: herdTotal }) }}
                    </h2>
                    <p v-if="herd.length === 0" class="empty">
                        {{ t('খামারে কোনো গরু নেই।') }}
                    </p>
                    <ul v-else class="divide-y divide-border/60 text-sm">
                        <li
                            v-for="row in herd"
                            :key="row.center"
                            class="flex justify-between py-1.5"
                        >
                            <span>{{ t(row.center) }}</span>
                            <span class="num font-semibold">{{
                                t(':nটি', { n: row.count })
                            }}</span>
                        </li>
                    </ul>

                    <div
                        v-for="group in animalGroups"
                        :key="group.key"
                        class="mt-4"
                    >
                        <h3
                            class="mb-1 flex justify-between text-sm font-semibold"
                        >
                            <span
                                >{{ group.title }} ({{
                                    t(':nটি', { n: group.rows.length })
                                }})</span
                            >
                            <span class="num">{{
                                taka(groupTotal(group.rows))
                            }}</span>
                        </h3>
                        <ul class="text-sm text-muted-foreground">
                            <li
                                v-for="row in group.rows"
                                :key="row.id"
                                class="flex justify-between gap-3 py-0.5"
                            >
                                <span class="min-w-0 truncate"
                                    >{{ row.tag
                                    }}<template v-if="row.name">
                                        · {{ row.name }}</template
                                    >
                                    · {{ day(row.date) }}</span
                                >
                                <span class="num shrink-0">{{
                                    taka(row.amount)
                                }}</span>
                            </li>
                        </ul>
                    </div>
                    <p
                        v-if="animalGroups.length === 0"
                        class="mt-3 text-xs text-muted-foreground"
                    >
                        {{ t('এই মাসে কোনো গরু কেনা, বিক্রি বা মারা যায়নি।') }}
                    </p>
                </section>

                <!-- Pregnancies -->
                <section class="report-card card">
                    <h2 class="card-title">
                        {{ t('৬০ দিনের মধ্যে বাচ্চা হবে') }}
                    </h2>
                    <p v-if="due.length === 0" class="empty">
                        {{
                            t(
                                'সামনের ৬০ দিনে কোনো গাভীর বাচ্চা হওয়ার কথা নেই।',
                            )
                        }}
                    </p>
                    <ul v-else class="divide-y divide-border/60 text-sm">
                        <li
                            v-for="row in due"
                            :key="row.id"
                            class="flex justify-between gap-3 py-1.5"
                        >
                            <span class="min-w-0">
                                <span class="font-medium">{{ row.tag }}</span>
                                <span
                                    v-if="row.name"
                                    class="text-muted-foreground"
                                >
                                    · {{ row.name }}</span
                                >
                                <span
                                    class="block text-xs text-muted-foreground"
                                    >{{ t(row.result_label) }} ·
                                    {{
                                        t('গর্ভের :months', {
                                            months: row.months,
                                        })
                                    }}</span
                                >
                            </span>
                            <span class="shrink-0 text-right">
                                <span class="num block">{{
                                    day(row.expected_on)
                                }}</span>
                                <span
                                    v-if="row.days_left !== null"
                                    class="num block text-xs text-muted-foreground"
                                    >{{
                                        row.days_left >= 0
                                            ? t('আর :n দিন', {
                                                  n: row.days_left,
                                              })
                                            : t(':n দিন পার', {
                                                  n: Math.abs(row.days_left),
                                              })
                                    }}</span
                                >
                            </span>
                        </li>
                    </ul>
                </section>

                <!-- Batches -->
                <section
                    class="report-card card lg:col-span-2 print:col-span-2"
                >
                    <h2 class="card-title">{{ t('চলমান মোটাতাজা ব্যাচ') }}</h2>
                    <p v-if="batches.length === 0" class="empty">
                        {{ t('এখন কোনো ব্যাচ চলছে না।') }}
                    </p>
                    <div v-else class="grid gap-3 sm:grid-cols-2">
                        <div
                            v-for="row in batches"
                            :key="row.id"
                            class="rounded-lg border border-border/70 p-3 text-sm"
                        >
                            <p class="flex justify-between gap-2 font-semibold">
                                <span>{{ row.label }}</span>
                                <span
                                    class="font-normal text-muted-foreground"
                                    >{{ t(row.center) }}</span
                                >
                            </p>
                            <dl class="mt-2 grid grid-cols-2 gap-x-3 gap-y-1">
                                <dt class="text-muted-foreground">
                                    {{ t('চলছে') }}
                                </dt>
                                <dd class="text-right">{{ row.span }}</dd>
                                <dt class="text-muted-foreground">
                                    {{ t('গরু') }}
                                </dt>
                                <dd class="num text-right">
                                    {{ t(':nটি', { n: row.head }) }}
                                </dd>
                                <dt class="text-muted-foreground">
                                    {{ t('মোট খরচ') }}
                                </dt>
                                <dd class="num text-right">
                                    {{ taka(row.spent) }}
                                </dd>
                                <dt class="text-muted-foreground">
                                    {{ t('গড়ে দিনে বাড়ছে') }}
                                </dt>
                                <dd class="num text-right">
                                    {{
                                        row.avg_gain !== null
                                            ? t(':n কেজি', { n: row.avg_gain })
                                            : t('ওজন নেওয়া হয়নি')
                                    }}
                                </dd>
                            </dl>
                        </div>
                    </div>
                </section>

                <!-- Feed -->
                <section class="report-card card">
                    <h2 class="card-title">{{ t('খাবার ও উপকরণ খরচ হলো') }}</h2>
                    <p v-if="feed.length === 0" class="empty">
                        {{ t('এই মাসে স্টক থেকে কিছু দেওয়া হয়নি।') }}
                    </p>
                    <table v-else class="w-full text-sm">
                        <tbody>
                            <tr
                                v-for="row in feed"
                                :key="row.id"
                                class="border-b border-border/60"
                            >
                                <td class="py-1.5">{{ t(row.name) }}</td>
                                <td
                                    class="num py-1.5 text-right text-muted-foreground"
                                >
                                    {{ qty(row.used_qty, t(row.unit)) }}
                                </td>
                                <td class="num py-1.5 pl-3 text-right">
                                    {{ taka(row.used_value) }}
                                </td>
                            </tr>
                            <tr class="font-semibold">
                                <td class="pt-2" colspan="2">{{ t('মোট') }}</td>
                                <td class="num pt-2 text-right">
                                    {{ taka(feedTotal) }}
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </section>

                <!-- Goals -->
                <section class="report-card card">
                    <h2 class="card-title">{{ t('লক্ষ্য কতদূর') }}</h2>
                    <p v-if="goals.length === 0" class="empty">
                        {{ t('কোনো লক্ষ্য ঠিক করা হয়নি।') }}
                    </p>
                    <ul v-else class="space-y-3">
                        <li
                            v-for="goal in goals"
                            :key="goal.id"
                            class="text-sm"
                        >
                            <div class="flex justify-between gap-3">
                                <span class="min-w-0 font-medium"
                                    >{{ goal.title
                                    }}<span
                                        v-if="goal.achieved"
                                        class="ml-1 text-income"
                                        >{{ t('✓ পূরণ হয়েছে') }}</span
                                    ></span
                                >
                                <span class="num shrink-0"
                                    >{{ goal.progress }}%</span
                                >
                            </div>
                            <div
                                class="mt-1 h-1.5 overflow-hidden rounded-full bg-muted"
                            >
                                <div
                                    class="bar h-full rounded-full bg-paddy"
                                    :style="{ width: `${goal.progress}%` }"
                                />
                            </div>
                            <p class="num mt-0.5 text-xs text-muted-foreground">
                                {{
                                    t(
                                        'এখন :current :unit / লক্ষ্য :target :unit',
                                        {
                                            current: goal.current_value,
                                            target: goal.target_value,
                                            unit: goal.unit ?? '',
                                        },
                                    )
                                }}<template v-if="goal.due_on">
                                    {{
                                        t('· :date-এর মধ্যে', {
                                            date: day(goal.due_on),
                                        })
                                    }}</template
                                >
                            </p>
                        </li>
                    </ul>
                </section>

                <!-- Lessons -->
                <section
                    class="report-card card lg:col-span-2 print:col-span-2"
                >
                    <h2 class="card-title">{{ t('এই মাসের শিক্ষা ও ভুল') }}</h2>
                    <p v-if="notes.length === 0" class="empty">
                        {{
                            t(
                                'ডায়েরিতে এই মাসে কোনো শিক্ষা বা ভুল লেখা হয়নি।',
                            )
                        }}
                    </p>
                    <ul v-else class="space-y-2.5 text-sm">
                        <li
                            v-for="note in notes"
                            :key="note.id"
                            class="flex gap-3"
                        >
                            <span
                                :class="[
                                    'mt-0.5 h-fit shrink-0 rounded px-1.5 py-0.5 text-xs font-medium',
                                    note.kind === 'mistake'
                                        ? 'bg-clay/14 text-clay'
                                        : 'bg-paddy/14 text-paddy',
                                ]"
                                >{{
                                    note.kind === 'mistake'
                                        ? t('ভুল')
                                        : t('শিক্ষা')
                                }}</span
                            >
                            <span class="min-w-0">
                                <span class="font-medium">{{
                                    note.title
                                }}</span>
                                <span class="num text-xs text-muted-foreground">
                                    · {{ day(note.date) }}</span
                                >
                                <span
                                    v-if="note.body"
                                    class="block text-muted-foreground"
                                    >{{ note.body }}</span
                                >
                            </span>
                        </li>
                    </ul>
                </section>
            </div>

            <p class="pt-2 text-center text-xs text-muted-foreground">
                {{
                    t('মাটির খামার হিসাব থেকে তৈরি · :month', {
                        month: monthName,
                    })
                }}
            </p>
            <!-- Sign-off -->
            <div
                class="report-card grid grid-cols-2 gap-8 pt-10 text-center text-sm"
            >
                <div>
                    <div class="mb-1 border-t border-foreground/40" />
                    {{ t('প্রস্তুতকারী — হিসাব অ্যাডমিন') }}
                </div>
                <div>
                    <div class="mb-1 border-t border-foreground/40" />
                    {{ t('অনুমোদন — খামার মালিক') }}
                </div>
            </div>
            <p
                class="report-card text-center text-[11px] text-muted-foreground"
            >
                {{
                    t(
                        'সব অঙ্ক খামারের হিসাব খাতা থেকে স্বয়ংক্রিয়ভাবে তৈরি · মাটির খামার',
                    )
                }}
            </p>
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
</style>

<style>
.monthly-report .num {
    white-space: nowrap;
}

.letterhead-rule {
    height: 4px;
    background: repeating-linear-gradient(
        90deg,
        var(--paddy) 0 36px,
        var(--turmeric) 36px 52px,
        var(--clay) 52px 64px
    );
    -webkit-print-color-adjust: exact;
    print-color-adjust: exact;
}

@media print {
    @page {
        size: A4;
        margin: 12mm;
    }

    [data-slot='sidebar'],
    [data-sidebar],
    header,
    nav,
    .no-print {
        display: none !important;
    }

    html,
    body,
    [data-slot='sidebar-inset'],
    .monthly-report {
        background: #fff !important;
        color: #000 !important;
    }

    [data-slot='sidebar-inset'] {
        margin: 0 !important;
        border: 0 !important;
        border-radius: 0 !important;
        box-shadow: none !important;
    }

    .monthly-report {
        padding: 0 !important;
        font-size: 12px;
    }

    .monthly-report * {
        color: #000 !important;
        box-shadow: none !important;
    }

    .monthly-report .card,
    .monthly-report .report-card > *,
    .monthly-report .report-card {
        background: #fff !important;
        border-color: #999 !important;
        break-inside: avoid;
        page-break-inside: avoid;
    }

    .monthly-report .bar {
        background: #555 !important;
        print-color-adjust: exact;
        -webkit-print-color-adjust: exact;
    }

    .monthly-report tr {
        break-inside: avoid;
    }
}
</style>
