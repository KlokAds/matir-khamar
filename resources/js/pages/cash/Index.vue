<script setup lang="ts">
import { Head, router } from '@inertiajs/vue3';
import {
    AlarmClock,
    ArrowDownLeft,
    ArrowUpRight,
    CircleAlert,
    HandCoins,
    MessageCircle,
    Plus,
    TrendingUp,
    Trash2,
    Wallet,
} from '@lucide/vue';
import type { Component } from 'vue';
import { computed, reactive, ref } from 'vue';
import Field from '@/components/khamar/Field.vue';
import NSelect from '@/components/khamar/NSelect.vue';
import PageTitle from '@/components/khamar/PageTitle.vue';
import StatTile from '@/components/khamar/StatTile.vue';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { t } from '@/lib/i18n';
import { day, monthFull, monthLabel, taka } from '@/lib/khamar';

defineOptions({
    layout: { breadcrumbs: [{ title: 'নগদ ও পাওনা', href: '/cash' }] },
});

type Kind = 'income' | 'expense';
type Part = {
    key: string;
    kind: Kind;
    label: string;
    amount: number;
    why: string;
    optional: boolean;
    counted: boolean;
};
type Month = {
    month: string;
    partial: boolean;
    days: number;
    fraction: number;
    opening: number;
    parts: Part[];
    income: number;
    expense: number;
    closing: number;
    flag: string;
};
type Line = { code: string; name: string; amount: number };
type Forecast = {
    today: string;
    start_cash: number;
    money: { name: string; balance: number }[];
    history: {
        from: string;
        to: string;
        months: number;
        income: Line[];
        expense: Line[];
        income_total: number;
        expense_total: number;
        animal_sales: number;
    };
    months: Month[];
    avg_income: number;
    avg_expense: number;
};
type Due = {
    party_id: number | null;
    name: string;
    phone: string | null;
    type: string | null;
    balance: number;
    oldest_on: string | null;
    age_days: number;
    last_paid_on: string | null;
    entries: number;
};
type Dues = {
    receivables: Due[];
    payables: Due[];
    total_receivable: number;
    total_payable: number;
};
type Tab = 'forecast' | 'dues';
type Extra = {
    id: number;
    month: string;
    kind: Kind;
    label: string;
    amount: number;
};

const props = defineProps<{
    tab: Tab;
    forecast: Forecast;
    dues: Dues;
    remindAfter: number;
}>();

/* ---------- tabs ---------- */
const tab = ref<Tab>(props.tab);
const tabs: { key: Tab; label: string; icon: Component }[] = [
    { key: 'forecast', label: 'পূর্বাভাস', icon: TrendingUp },
    { key: 'dues', label: 'পাওনা ও দেনা', icon: HandCoins },
];

function setTab(next: Tab) {
    tab.value = next;

    try {
        const url = new URL(window.location.href);
        url.searchParams.set('tab', next);
        window.history.replaceState(window.history.state, '', url);
    } catch {
        // The address bar is only a convenience.
    }
}

/* ---------- what-if: toggles and typed lines, client-side only ---------- */
const toggled = reactive<Record<string, boolean>>({});

function isCounted(part: Part): boolean {
    return toggled[part.key] ?? part.counted;
}

function toggle(part: Part) {
    toggled[part.key] = !isCounted(part);
}

const extras = ref<Extra[]>([]);
let nextId = 1;
const extraForm = reactive({
    month: props.forecast.months[0]?.month ?? '',
    kind: 'expense' as Kind,
    label: '',
    amount: '' as string | number,
});

function addExtra() {
    const amount = Math.round(Number(extraForm.amount) * 100);

    if (!extraForm.label.trim() || !(amount > 0)) {
        return;
    }

    extras.value.push({
        id: nextId++,
        month: extraForm.month,
        kind: extraForm.kind,
        label: extraForm.label.trim(),
        amount,
    });
    extraForm.label = '';
    extraForm.amount = '';
}

function removeExtra(id: number) {
    extras.value = extras.value.filter((e) => e.id !== id);
}

function flagOf(closing: number): string {
    if (closing < 0) {
        return 'danger';
    }

    return props.forecast.avg_expense > 0 &&
        closing < props.forecast.avg_expense
        ? 'thin'
        : 'ok';
}

const months = computed(() => {
    let balance = props.forecast.start_cash;

    return props.forecast.months.map((m) => {
        const mine = extras.value.filter((e) => e.month === m.month);
        let income = 0;
        let expense = 0;

        for (const part of m.parts) {
            if (isCounted(part)) {
                if (part.kind === 'income') {
                    income += part.amount;
                } else {
                    expense += part.amount;
                }
            }
        }

        for (const e of mine) {
            if (e.kind === 'income') {
                income += e.amount;
            } else {
                expense += e.amount;
            }
        }

        const opening = balance;
        balance = opening + income - expense;

        return {
            ...m,
            extras: mine,
            opening,
            income,
            expense,
            closing: balance,
            flag: flagOf(balance),
        };
    });
});

const lowest = computed(() =>
    months.value.reduce(
        (low, m) => (m.closing < low.closing ? m : low),
        months.value[0],
    ),
);
const worst = computed(
    () =>
        months.value.find((m) => m.flag === 'danger') ??
        months.value.find((m) => m.flag === 'thin'),
);
const changed = computed(
    () => extras.value.length > 0 || Object.keys(toggled).length > 0,
);

function resetWhatIf() {
    extras.value = [];

    for (const key of Object.keys(toggled)) {
        delete toggled[key];
    }
}

function monthTitle(m: { month: string; partial: boolean; days: number }) {
    return m.partial
        ? t(':month (বাকি :n দিন)', { month: monthFull(m.month), n: m.days })
        : monthFull(m.month);
}

const flagStyle: Record<string, string> = {
    danger: 'border-expense/50 bg-expense/6',
    thin: 'border-turmeric/70 bg-turmeric/10',
    ok: 'border-border bg-card',
};
const flagLabel: Record<string, string> = {
    danger: 'টাকা ফুরিয়ে যাবে',
    thin: 'টানাটানি — এক মাসের খরচও থাকবে না',
    ok: 'ঠিক আছে',
};

/* ---------- bar chart of month-end cash ---------- */
const chart = computed(() => {
    const values = [
        props.forecast.start_cash,
        ...months.value.map((m) => m.closing),
    ];
    const max = Math.max(0, ...values);
    const min = Math.min(0, ...values);
    const span = max - min || 1;
    const height = 140;
    const zero = (max / span) * height;
    const bars = [
        {
            key: 'now',
            label: t('আজ'),
            value: props.forecast.start_cash,
            flag: flagOf(props.forecast.start_cash),
        },
        ...months.value.map((m) => ({
            key: m.month,
            label: monthLabel(m.month),
            value: m.closing,
            flag: m.flag,
        })),
    ];

    return {
        height,
        zero,
        bars: bars.map((b, i) => {
            const h = (Math.abs(b.value) / span) * height;

            return {
                ...b,
                x: i,
                y: b.value >= 0 ? zero - h : zero,
                h: Math.max(h, 1),
            };
        }),
    };
});

const barFill: Record<string, string> = {
    danger: 'fill-expense',
    thin: 'fill-turmeric',
    ok: 'fill-paddy',
};

/* ---------- dues: WhatsApp and reminders ---------- */
function waPhone(phone: string | null): string | null {
    if (!phone) {
        return null;
    }

    const digits = phone.replace(/\D/g, '');

    if (/^01\d{9}$/.test(digits)) {
        return `88${digits}`;
    }

    if (/^8801\d{9}$/.test(digits)) {
        return digits;
    }

    if (/^1\d{9}$/.test(digits)) {
        return `880${digits}`;
    }

    return null;
}

function waLink(due: Due, side: 'in' | 'out'): string | null {
    const phone = waPhone(due.phone);

    if (!phone) {
        return null;
    }

    const amount = taka(due.balance);
    const message =
        side === 'in'
            ? t(
                  'আসসালামু আলাইকুম :name, খামার থেকে বলছি। :date থেকে আমাদের :amount বাকি আছে। আপনার সুবিধামতো সময়ে পরিশোধ করলে খুব উপকার হয়। ধন্যবাদ।',
                  {
                      name: due.name,
                      amount,
                      date: day(due.oldest_on),
                  },
              )
            : t(
                  'আসসালামু আলাইকুম :name, খামার থেকে বলছি। আপনার পাওনা :amount আমাদের মনে আছে, শীঘ্রই পরিশোধ করব। ধন্যবাদ।',
                  { name: due.name, amount },
              );

    return `https://wa.me/${phone}?text=${encodeURIComponent(message)}`;
}

function tomorrowTen(): string {
    const d = new Date();
    d.setDate(d.getDate() + 1);
    const pad = (n: number) => String(n).padStart(2, '0');

    return `${d.getFullYear()}-${pad(d.getMonth() + 1)}-${pad(d.getDate())}T10:00`;
}

const reminding = ref<string | null>(null);
const reminded = reactive<Record<string, boolean>>({});

function remind(due: Due, side: 'in' | 'out') {
    const key = `${side}-${due.party_id ?? 'none'}`;
    const title =
        side === 'in'
            ? t(':name-এর কাছ থেকে :amount আদায় করুন', {
                  name: due.name,
                  amount: taka(due.balance),
              })
            : t(':name-কে :amount শোধ করুন', {
                  name: due.name,
                  amount: taka(due.balance),
              });

    reminding.value = key;
    router.post(
        '/reminders',
        {
            title: title.slice(0, 160),
            body: due.phone ? t('ফোন :phone', { phone: due.phone }) : null,
            due_at: tomorrowTen(),
            repeat: 'none',
        },
        {
            preserveScroll: true,
            preserveState: true,
            onSuccess: () => (reminded[key] = true),
            onFinish: () => (reminding.value = null),
        },
    );
}

function ageTone(days: number): string {
    if (days > 60) {
        return 'bg-expense/12 text-expense';
    }

    if (days > props.remindAfter) {
        return 'bg-turmeric/25 text-soil';
    }

    return 'bg-paddy/12 text-paddy';
}

const sides = computed(() => [
    {
        side: 'in' as const,
        title: 'কে কত দেবে',
        hint: 'বাকি বিক্রির টাকা — ক্রেতার কাছে পাওনা',
        rows: props.dues.receivables,
        total: props.dues.total_receivable,
        icon: ArrowDownLeft,
        tone: 'text-income',
        empty: 'কারো কাছে বাকি নেই।',
    },
    {
        side: 'out' as const,
        title: 'কাকে কত দিতে হবে',
        hint: 'বাকি কেনার টাকা — সরবরাহকারীর পাওনা',
        rows: props.dues.payables,
        total: props.dues.total_payable,
        icon: ArrowUpRight,
        tone: 'text-expense',
        empty: 'কারো কাছে দেনা নেই।',
    },
]);
</script>

<template>
    <Head :title="t('নগদ ও পাওনা')" />

    <div class="flex flex-1 flex-col p-4 md:p-6">
        <PageTitle
            :title="t('নগদ ও পাওনা')"
            :subtitle="
                t(
                    'সামনের মাসগুলোতে হাতে কত টাকা থাকবে, আর কে কত দেবে / কাকে কত দিতে হবে।',
                )
            "
        />

        <div class="mx-auto w-full max-w-5xl space-y-5">
            <div
                class="grid grid-cols-2 gap-1 rounded-xl border border-border bg-muted/60 p-1"
                role="tablist"
            >
                <button
                    v-for="item in tabs"
                    :key="item.key"
                    type="button"
                    role="tab"
                    :aria-selected="tab === item.key"
                    :class="[
                        'flex items-center justify-center gap-1.5 rounded-lg px-2 py-2 text-sm font-medium transition-colors',
                        tab === item.key
                            ? 'bg-card text-foreground shadow-sm'
                            : 'text-muted-foreground hover:text-foreground',
                    ]"
                    @click="setTab(item.key)"
                >
                    <component :is="item.icon" class="size-4 shrink-0" />
                    <span>{{ t(item.label) }}</span>
                </button>
            </div>

            <!-- ============ FORECAST ============ -->
            <template v-if="tab === 'forecast'">
                <div class="grid gap-3 sm:grid-cols-3">
                    <StatTile
                        :label="t('এখন হাতে ও ব্যাংকে')"
                        :value="taka(forecast.start_cash)"
                        :hint="
                            forecast.money
                                .filter((m) => m.balance !== 0)
                                .map((m) => `${t(m.name)} ${taka(m.balance)}`)
                                .join(' · ')
                        "
                        :icon="Wallet"
                        tone="pond"
                    />
                    <StatTile
                        :label="t('মাসে গড় আয় / খরচ')"
                        :value="`${taka(forecast.avg_income)} / ${taka(forecast.avg_expense)}`"
                        :hint="
                            t('গত :n মাসের হিসাবে', {
                                n: forecast.history.months,
                            })
                        "
                        :icon="TrendingUp"
                        tone="paddy"
                    />
                    <StatTile
                        v-if="lowest"
                        :label="t('সবচেয়ে কম থাকবে')"
                        :value="taka(lowest.closing)"
                        :hint="monthTitle(lowest)"
                        :icon="CircleAlert"
                        :tone="lowest.flag === 'ok' ? 'soil' : 'clay'"
                    />
                </div>

                <div
                    v-if="worst"
                    :class="[
                        'flex items-start gap-3 rounded-xl border p-4 text-sm',
                        flagStyle[worst.flag],
                    ]"
                >
                    <CircleAlert
                        :class="[
                            'mt-0.5 size-5 shrink-0',
                            worst.flag === 'danger'
                                ? 'text-expense'
                                : 'text-soil',
                        ]"
                    />
                    <p>
                        <span class="font-semibold">{{
                            monthTitle(worst)
                        }}</span>
                        —
                        {{
                            worst.flag === 'danger'
                                ? t(
                                      'মাস শেষে টাকা ঋণাত্মক হবে (:amount)। খরচ পিছিয়ে দিন, বাকি আদায় করুন বা টাকার ব্যবস্থা রাখুন।',
                                      { amount: taka(worst.closing) },
                                  )
                                : t(
                                      'মাস শেষে :amount থাকবে, যা এক মাসের খরচের (:expense) চেয়ে কম। হাতে একটু বাড়তি রাখুন।',
                                      {
                                          amount: taka(worst.closing),
                                          expense: taka(forecast.avg_expense),
                                      },
                                  )
                        }}
                    </p>
                </div>

                <!-- Bar chart -->
                <section class="rounded-xl border border-border bg-card p-4">
                    <h2 class="font-display text-lg">
                        {{ t('মাস শেষে হাতে কত থাকবে') }}
                    </h2>
                    <svg
                        :viewBox="`0 0 ${chart.bars.length * 60} ${chart.height + 40}`"
                        class="mt-3 h-48 w-full"
                        role="img"
                        :aria-label="t('মাস শেষে হাতে কত থাকবে')"
                    >
                        <line
                            x1="0"
                            :x2="chart.bars.length * 60"
                            :y1="chart.zero + 4"
                            :y2="chart.zero + 4"
                            class="stroke-border"
                            stroke-width="1"
                        />
                        <g v-for="bar in chart.bars" :key="bar.key">
                            <rect
                                :x="bar.x * 60 + 14"
                                :y="bar.y + 4"
                                width="32"
                                :height="bar.h"
                                rx="4"
                                :class="
                                    bar.key === 'now'
                                        ? 'fill-pond'
                                        : barFill[bar.flag]
                                "
                            >
                                <title>
                                    {{ bar.label }}: {{ taka(bar.value) }}
                                </title>
                            </rect>
                            <text
                                :x="bar.x * 60 + 30"
                                :y="chart.height + 22"
                                text-anchor="middle"
                                class="fill-muted-foreground text-[11px]"
                            >
                                {{ bar.label }}
                            </text>
                            <text
                                :x="bar.x * 60 + 30"
                                :y="chart.height + 36"
                                text-anchor="middle"
                                class="fill-foreground text-[10px]"
                            >
                                {{
                                    taka(
                                        Math.round(bar.value / 100000) * 100000,
                                    )
                                }}
                            </text>
                        </g>
                    </svg>
                </section>

                <!-- Month cards -->
                <div class="grid gap-3 md:grid-cols-2">
                    <section
                        v-for="m in months"
                        :key="m.month"
                        :class="['rounded-xl border p-4', flagStyle[m.flag]]"
                    >
                        <div
                            class="flex flex-wrap items-start justify-between gap-x-2 gap-y-1"
                        >
                            <div class="min-w-0">
                                <h3 class="font-display text-lg">
                                    {{ monthTitle(m) }}
                                </h3>
                                <p class="text-xs text-muted-foreground">
                                    {{ t('শুরুতে') }} {{ taka(m.opening) }}
                                </p>
                            </div>
                            <span
                                :class="[
                                    'rounded-full px-2 py-0.5 text-xs leading-normal font-medium',
                                    m.flag === 'danger'
                                        ? 'bg-expense/12 text-expense'
                                        : m.flag === 'thin'
                                          ? 'bg-turmeric/25 text-soil'
                                          : 'bg-paddy/12 text-paddy',
                                ]"
                                >{{ t(flagLabel[m.flag]) }}</span
                            >
                        </div>

                        <ul class="mt-3 space-y-2 text-sm">
                            <li
                                v-for="part in m.parts"
                                :key="part.key"
                                :class="[
                                    'flex items-start justify-between gap-3',
                                    !isCounted(part) && 'opacity-55',
                                ]"
                            >
                                <div class="min-w-0">
                                    <p class="font-medium">
                                        {{ t(part.label) }}
                                    </p>
                                    <p class="text-xs text-muted-foreground">
                                        {{ t(part.why) }}
                                    </p>
                                    <button
                                        v-if="part.optional"
                                        type="button"
                                        class="btn-chip mt-1.5 h-7 text-xs"
                                        @click="toggle(part)"
                                    >
                                        {{
                                            isCounted(part)
                                                ? t('হিসাব থেকে বাদ দিন')
                                                : t('হিসাবে ধরুন')
                                        }}
                                    </button>
                                </div>
                                <span
                                    :class="[
                                        'num shrink-0 font-medium',
                                        part.kind === 'income'
                                            ? 'text-income'
                                            : 'text-expense',
                                        !isCounted(part) && 'line-through',
                                    ]"
                                    >{{ part.kind === 'income' ? '+' : '−' }}
                                    {{ taka(part.amount) }}</span
                                >
                            </li>
                            <li
                                v-for="e in m.extras"
                                :key="`x${e.id}`"
                                class="flex items-start justify-between gap-3"
                            >
                                <div class="min-w-0">
                                    <p class="font-medium">{{ e.label }}</p>
                                    <p class="text-xs text-muted-foreground">
                                        {{
                                            t('আপনার যোগ করা (শুধু এই পাতায়)')
                                        }}
                                    </p>
                                </div>
                                <span class="flex shrink-0 items-center gap-1">
                                    <span
                                        :class="[
                                            'num font-medium',
                                            e.kind === 'income'
                                                ? 'text-income'
                                                : 'text-expense',
                                        ]"
                                        >{{ e.kind === 'income' ? '+' : '−' }}
                                        {{ taka(e.amount) }}</span
                                    >
                                    <button
                                        type="button"
                                        class="btn-icon size-8 hover:text-expense"
                                        :aria-label="t('মুছুন')"
                                        :title="t('মুছুন')"
                                        @click="removeExtra(e.id)"
                                    >
                                        <Trash2 class="size-3.5" />
                                    </button>
                                </span>
                            </li>
                            <li
                                v-if="m.parts.length === 0 && !m.extras.length"
                                class="text-muted-foreground"
                            >
                                {{
                                    t(
                                        'আগের মাসের হিসাব নেই, তাই আন্দাজ করা যায়নি।',
                                    )
                                }}
                            </li>
                        </ul>

                        <div
                            class="mt-3 flex items-center justify-between border-t border-border pt-3"
                        >
                            <span class="text-sm text-muted-foreground">{{
                                t('মাস শেষে')
                            }}</span>
                            <span
                                :class="[
                                    'num text-lg font-semibold',
                                    m.closing < 0 && 'text-expense',
                                ]"
                                >{{ taka(m.closing) }}</span
                            >
                        </div>
                    </section>
                </div>

                <!-- What-if -->
                <section class="rounded-xl border border-border bg-card p-4">
                    <div
                        class="flex flex-wrap items-center justify-between gap-2"
                    >
                        <h2 class="font-display text-lg">
                            {{ t('যদি এমন হয়…') }}
                        </h2>
                        <Button
                            v-if="changed"
                            variant="secondary"
                            size="sm"
                            @click="resetWhatIf"
                            >{{ t('আগের মতো করুন') }}</Button
                        >
                    </div>
                    <p class="mt-1 text-sm text-muted-foreground">
                        {{
                            t(
                                'একটি আয় বা খরচ যোগ করে দেখুন মাস শেষে কত থাকে — যেমন গরু বিক্রি, নতুন বাছুর কেনা বা শেড মেরামত। এটি কোথাও সংরক্ষিত হয় না।',
                            )
                        }}
                    </p>
                    <form
                        class="mt-3 grid gap-3 sm:grid-cols-2 lg:grid-cols-4"
                        @submit.prevent="addExtra"
                    >
                        <Field :label="t('মাস')">
                            <NSelect v-model="extraForm.month">
                                <option
                                    v-for="m in forecast.months"
                                    :key="m.month"
                                    :value="m.month"
                                >
                                    {{ monthTitle(m) }}
                                </option>
                            </NSelect>
                        </Field>
                        <Field :label="t('ধরন')">
                            <NSelect v-model="extraForm.kind">
                                <option value="income">
                                    {{ t('আয় (টাকা আসবে)') }}
                                </option>
                                <option value="expense">
                                    {{ t('খরচ (টাকা যাবে)') }}
                                </option>
                            </NSelect>
                        </Field>
                        <Field :label="t('কী')">
                            <Input
                                v-model="extraForm.label"
                                :placeholder="t('যেমন: ২টি ষাঁড় বিক্রি')"
                                maxlength="80"
                            />
                        </Field>
                        <Field :label="t('টাকা')">
                            <div class="flex gap-2">
                                <Input
                                    v-model="extraForm.amount"
                                    type="number"
                                    inputmode="decimal"
                                    min="0"
                                    step="any"
                                    placeholder="0"
                                />
                                <Button
                                    type="submit"
                                    :disabled="
                                        !extraForm.label.trim() ||
                                        !(Number(extraForm.amount) > 0)
                                    "
                                    ><Plus /> {{ t('যোগ') }}</Button
                                >
                            </div>
                        </Field>
                    </form>
                </section>

                <!-- How the averages were made -->
                <section class="rounded-xl border border-border bg-card p-4">
                    <h2 class="font-display text-lg">
                        {{ t('গড় কীভাবে হলো') }}
                    </h2>
                    <p class="mt-1 text-sm text-muted-foreground">
                        {{
                            t(
                                ':from থেকে :to — :n মাসের টাকার আয়-খরচ, মাসিক গড়। নিজের জমির ঘাস/খড়, লিজের মাসিক ভাগ, পশু কেনা-বিক্রি বাদ; স্টকে কেনা খাদ্য-ঔষধ কেনার মাসেই খরচ ধরা।',
                                {
                                    from: day(forecast.history.from),
                                    to: day(forecast.history.to),
                                    n: forecast.history.months,
                                },
                            )
                        }}
                    </p>
                    <div class="mt-3 grid gap-4 sm:grid-cols-2">
                        <div
                            v-for="side in [
                                {
                                    key: 'in',
                                    title: 'আয়',
                                    rows: forecast.history.income,
                                    total: forecast.history.income_total,
                                    tone: 'text-income',
                                },
                                {
                                    key: 'out',
                                    title: 'খরচ',
                                    rows: forecast.history.expense,
                                    total: forecast.history.expense_total,
                                    tone: 'text-expense',
                                },
                            ]"
                            :key="side.key"
                        >
                            <h3 class="text-sm font-semibold">
                                {{ t(side.title) }}
                            </h3>
                            <ul class="mt-1 divide-y divide-border text-sm">
                                <li
                                    v-for="row in side.rows"
                                    :key="row.code"
                                    class="flex justify-between gap-3 py-1.5"
                                >
                                    <span class="min-w-0 truncate">{{
                                        t(row.name)
                                    }}</span>
                                    <span class="num shrink-0">{{
                                        taka(row.amount)
                                    }}</span>
                                </li>
                                <li
                                    v-if="!side.rows.length"
                                    class="py-1.5 text-muted-foreground"
                                >
                                    {{ t('কিছু নেই') }}
                                </li>
                                <li
                                    class="flex justify-between gap-3 py-1.5 font-semibold"
                                >
                                    <span>{{ t('মাসে মোট') }}</span>
                                    <span :class="['num', side.tone]">{{
                                        taka(side.total)
                                    }}</span>
                                </li>
                            </ul>
                        </div>
                    </div>
                    <p
                        v-if="forecast.history.animal_sales > 0"
                        class="mt-3 text-sm text-muted-foreground"
                    >
                        {{
                            t(
                                'এই সময়ে পশু বিক্রি :amount — এককালীন, তাই গড়ে ধরা হয়নি। সামনে বিক্রি থাকলে ওপরের ঘরে যোগ করুন।',
                                { amount: taka(forecast.history.animal_sales) },
                            )
                        }}
                    </p>
                </section>
            </template>

            <!-- ============ DUES ============ -->
            <template v-else>
                <div class="grid grid-cols-2 gap-3">
                    <StatTile
                        :label="t('মোট পাওনা')"
                        :value="taka(dues.total_receivable)"
                        :hint="
                            t(':n জনের কাছে', { n: dues.receivables.length })
                        "
                        :icon="ArrowDownLeft"
                        tone="paddy"
                    />
                    <StatTile
                        :label="t('মোট দেনা')"
                        :value="taka(dues.total_payable)"
                        :hint="t(':n জনকে', { n: dues.payables.length })"
                        :icon="ArrowUpRight"
                        tone="clay"
                    />
                </div>

                <section
                    v-for="s in sides"
                    :key="s.side"
                    class="rounded-xl border border-border bg-card p-4"
                >
                    <div class="flex items-start justify-between gap-2">
                        <div>
                            <h2
                                class="flex items-center gap-2 font-display text-lg"
                            >
                                <component
                                    :is="s.icon"
                                    :class="['size-5', s.tone]"
                                />
                                {{ t(s.title) }}
                            </h2>
                            <p class="text-xs text-muted-foreground">
                                {{ t(s.hint) }}
                            </p>
                        </div>
                        <span :class="['num font-semibold', s.tone]">{{
                            taka(s.total)
                        }}</span>
                    </div>

                    <ul class="mt-3 space-y-3">
                        <li
                            v-for="due in s.rows"
                            :key="`${s.side}-${due.party_id ?? 'none'}`"
                            class="rounded-lg border border-border p-3"
                        >
                            <div class="flex items-start justify-between gap-3">
                                <div class="min-w-0">
                                    <p class="truncate font-medium">
                                        {{ due.name }}
                                    </p>
                                    <p class="text-xs text-muted-foreground">
                                        {{
                                            due.phone ??
                                            t('ফোন নম্বর দেওয়া নেই')
                                        }}
                                    </p>
                                </div>
                                <span class="num shrink-0 font-semibold">{{
                                    taka(due.balance)
                                }}</span>
                            </div>
                            <div
                                class="mt-2 flex flex-wrap items-center gap-1.5 text-xs"
                            >
                                <span
                                    :class="[
                                        'rounded-full px-2 py-0.5 font-medium',
                                        ageTone(due.age_days),
                                    ]"
                                    >{{
                                        t(':n দিন ধরে বাকি', {
                                            n: due.age_days,
                                        })
                                    }}</span
                                >
                                <span class="text-muted-foreground">{{
                                    t('প্রথম বাকি :date', {
                                        date: day(due.oldest_on),
                                    })
                                }}</span>
                                <span class="text-muted-foreground">·</span>
                                <span class="text-muted-foreground">{{
                                    due.last_paid_on
                                        ? t('শেষ লেনদেন :date', {
                                              date: day(due.last_paid_on),
                                          })
                                        : t('এখনো কিছু দেওয়া হয়নি')
                                }}</span>
                            </div>
                            <div class="mt-3 flex flex-wrap gap-2">
                                <a
                                    v-if="waLink(due, s.side)"
                                    :href="waLink(due, s.side) ?? undefined"
                                    target="_blank"
                                    rel="noopener"
                                    class="inline-flex items-center gap-1.5 rounded-md border border-border px-3 py-1.5 text-sm font-medium hover:bg-muted"
                                >
                                    <MessageCircle class="size-4 text-paddy" />
                                    {{ t('WhatsApp-এ মনে করান') }}
                                </a>
                                <Button
                                    variant="outline"
                                    size="sm"
                                    :disabled="
                                        reminding !== null ||
                                        reminded[
                                            `${s.side}-${due.party_id ?? 'none'}`
                                        ]
                                    "
                                    @click="remind(due, s.side)"
                                >
                                    <AlarmClock />
                                    {{
                                        reminded[
                                            `${s.side}-${due.party_id ?? 'none'}`
                                        ]
                                            ? t('কাল সকাল ১০টায় মনে করাবে')
                                            : t('রিমাইন্ডার রাখুন')
                                    }}
                                </Button>
                            </div>
                        </li>
                        <li
                            v-if="!s.rows.length"
                            class="text-sm text-muted-foreground"
                        >
                            {{ t(s.empty) }}
                        </li>
                    </ul>
                </section>
            </template>
        </div>
    </div>
</template>
