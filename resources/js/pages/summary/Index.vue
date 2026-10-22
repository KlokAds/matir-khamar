<script setup lang="ts">
import { Head, router } from '@inertiajs/vue3';
import {
    ArrowDownLeft,
    ArrowUpRight,
    Beef,
    Landmark,
    Scale,
    TrendingDown,
    TrendingUp,
    Wallet,
} from '@lucide/vue';
import { computed, ref } from 'vue';
import DateField from '@/components/khamar/DateField.vue';
import MoneyStory from '@/components/khamar/MoneyStory.vue';
import SalesMargin from '@/components/khamar/SalesMargin.vue';
import type { Sales } from '@/components/khamar/SalesMargin.vue';
import type { Story } from '@/components/khamar/MoneyStory.vue';
import PageTitle from '@/components/khamar/PageTitle.vue';
import StatTile from '@/components/khamar/StatTile.vue';
import TrendBars from '@/components/khamar/TrendBars.vue';
import { Button } from '@/components/ui/button';
import { t, tAll } from '@/lib/i18n';
import { day, taka } from '@/lib/khamar';

defineOptions({
    layout: { breadcrumbs: [{ title: 'হিসাবের সারাংশ', href: '/summary' }] },
});

type Money = { label: string; amount: number };
type Result = { income: number; expense: number; profit: number };

const props = defineProps<{
    period: string;
    periods: Record<string, string>;
    story: Story;
    sales: Sales;
    from: string;
    to: string;
    result: Result;
    previous: Result;
    flows: { in: Money[]; out: Money[]; in_total: number; out_total: number };
    projects: {
        id: number;
        name: string;
        kind: string;
        income: number;
        expense: number;
        profit: number;
        children: {
            id: number;
            name: string;
            income: number;
            expense: number;
            profit: number;
        }[];
    }[];
    spending: { name: string; amount: number }[];
    earning: { name: string; amount: number }[];
    months: { month: string; income: number; expense: number }[];
    position: {
        money: number;
        animals: number;
        things: number;
        receivable: number;
        assets: number;
        owed: number;
        net: number;
        capital: number;
        earned: number;
        money_places: { name: string; balance: number }[];
        receivable_list: { name: string; balance: number }[];
    };
    herd: {
        now: number;
        now_value: number;
        bought: number;
        bought_cost: number;
        sold: number;
        sold_amount: number;
        died: number;
        by_center: { name: string; count: number }[];
    };
    milk: number;
}>();

const custom = ref({ from: props.from, to: props.to });

function go(period: string) {
    if (period === 'custom') {
        router.get(
            '/summary',
            { period, from: custom.value.from, to: custom.value.to },
            { preserveScroll: true },
        );

        return;
    }

    router.get('/summary', { period }, { preserveScroll: true });
}

/** Change against the same length of time just before. */
function change(now: number, before: number): string {
    if (before === 0) {
        return '';
    }

    const pct = Math.round(((now - before) / Math.abs(before)) * 100);

    return t('আগের সময়ের চেয়ে :sign:pct%', {
        sign: pct >= 0 ? '+' : '−',
        pct: Math.abs(pct),
    });
}

const spendPeak = computed(() =>
    Math.max(1, ...props.spending.map((s) => s.amount)),
);
const projectPeak = computed(() =>
    Math.max(1, ...props.projects.flatMap((p) => [p.income, p.expense])),
);
const share = (value: number, peak: number) =>
    `${Math.max(2, Math.round((Math.max(0, value) / peak) * 100))}%`;
</script>

<template>
    <Head :title="t('হিসাবের সারাংশ')" />

    <div class="flex flex-1 flex-col gap-6 p-4 pb-24 md:p-6 md:pb-6">
        <PageTitle
            :title="t('হিসাবের সারাংশ')"
            :subtitle="`${day(from)} — ${day(to)}`"
        />

        <!-- The whole farm in plain words, always from the start -->
        <MoneyStory :story="story" />

        <!-- What each sale really left -->
        <SalesMargin :sales="sales" />

        <h2 class="-mb-2 font-display text-lg leading-normal">
            {{ t('সময় ধরে দেখুন') }}
        </h2>

        <!-- Period -->
        <div class="flex flex-col gap-3">
            <div class="flex flex-wrap gap-2">
                <button
                    v-for="(label, key) in tAll(periods)"
                    :key="key"
                    type="button"
                    :class="[
                        'h-9 rounded-full border px-3.5 text-sm font-medium transition-colors',
                        period === key
                            ? 'border-primary bg-primary text-primary-foreground'
                            : 'border-border bg-card hover:bg-accent',
                    ]"
                    @click="
                        key === 'custom'
                            ? period !== 'custom' && go('custom')
                            : go(key)
                    "
                >
                    {{ label }}
                </button>
            </div>
            <form
                v-if="period === 'custom'"
                class="grid grid-cols-[1fr_auto_1fr] items-center gap-2 sm:flex sm:max-w-xl"
                @submit.prevent="go('custom')"
            >
                <DateField v-model="custom.from" />
                <span class="text-muted-foreground">—</span>
                <DateField v-model="custom.to" />
                <Button type="submit" class="col-span-3 sm:col-span-1">{{
                    t('দেখুন')
                }}</Button>
            </form>
        </div>

        <!-- Result -->
        <div class="grid gap-3 sm:grid-cols-3">
            <StatTile
                :label="t('আয়')"
                :value="taka(result.income)"
                :hint="change(result.income, previous.income)"
                :icon="TrendingUp"
                tone="paddy"
            />
            <StatTile
                :label="t('খরচ')"
                :value="taka(result.expense)"
                :hint="change(result.expense, previous.expense)"
                :icon="TrendingDown"
                tone="clay"
            />
            <StatTile
                :label="result.profit >= 0 ? t('লাভ') : t('ক্ষতি')"
                :value="taka(result.profit)"
                :hint="
                    t('আগের সমান সময়ে :amount', {
                        amount: taka(previous.profit),
                    })
                "
                :icon="Scale"
                :tone="result.profit >= 0 ? 'paddy' : 'clay'"
            />
        </div>
        <p class="-mt-3 text-xs leading-normal text-muted-foreground">
            {{
                t(
                    'আয়-খরচে শুধু খামারের আসল আয় ও খরচ: পশু বিক্রি, খাবার, ওষুধ, মজুরি, জমি… মূলধন, ধার, পশু বা জিনিস কেনা এখানে নয় — সেগুলো নিচে "টাকা এলো-গেল" আর "খামারের অবস্থা"-য়।',
                )
            }}
        </p>

        <!-- Money in and out -->
        <section class="grid gap-3 lg:grid-cols-2">
            <div
                v-for="side in ['in', 'out'] as const"
                :key="side"
                class="rounded-xl border border-border bg-card p-4"
            >
                <div class="mb-3 flex items-center justify-between gap-2">
                    <h2
                        class="flex items-center gap-2 font-display text-lg leading-normal"
                    >
                        <component
                            :is="side === 'in' ? ArrowDownLeft : ArrowUpRight"
                            :class="[
                                'size-5',
                                side === 'in' ? 'text-paddy' : 'text-clay',
                            ]"
                        />
                        {{ side === 'in' ? t('টাকা এলো') : t('টাকা গেল') }}
                    </h2>
                    <span class="num font-semibold whitespace-nowrap">{{
                        taka(side === 'in' ? flows.in_total : flows.out_total)
                    }}</span>
                </div>
                <ul class="space-y-2 text-sm">
                    <li
                        v-for="row in flows[side]"
                        :key="row.label"
                        class="flex items-center justify-between gap-3 border-b border-dashed border-border pb-2 last:border-0 last:pb-0"
                    >
                        <span class="leading-normal">{{ row.label }}</span>
                        <span class="num whitespace-nowrap">{{
                            taka(row.amount)
                        }}</span>
                    </li>
                    <li
                        v-if="!flows[side].length"
                        class="text-muted-foreground"
                    >
                        {{ t('এই সময়ে কিছু নেই।') }}
                    </li>
                </ul>
            </div>
        </section>

        <!-- Projects -->
        <section class="rounded-xl border border-border bg-card p-4">
            <h2 class="mb-3 font-display text-lg leading-normal">
                {{ t('প্রজেক্ট অনুযায়ী') }}
            </h2>
            <div class="space-y-4">
                <div v-for="p in projects" :key="p.id">
                    <div
                        class="flex flex-wrap items-baseline justify-between gap-x-3"
                    >
                        <p class="leading-normal font-medium">
                            {{ t(p.name) }}
                        </p>
                        <p
                            :class="[
                                'num text-sm font-semibold whitespace-nowrap',
                                p.profit >= 0 ? 'text-paddy' : 'text-clay',
                            ]"
                        >
                            {{ p.profit >= 0 ? t('লাভ') : t('ক্ষতি') }}
                            {{ taka(p.profit) }}
                        </p>
                    </div>
                    <div class="mt-1.5 space-y-1">
                        <div class="flex items-center gap-2 text-xs">
                            <span class="w-10 shrink-0 text-muted-foreground">{{
                                t('আয়')
                            }}</span>
                            <div class="h-2 flex-1 rounded-full bg-muted">
                                <div
                                    class="h-2 rounded-full bg-paddy"
                                    :style="{
                                        width: share(p.income, projectPeak),
                                    }"
                                />
                            </div>
                            <span class="num w-24 shrink-0 text-right">{{
                                taka(p.income)
                            }}</span>
                        </div>
                        <div class="flex items-center gap-2 text-xs">
                            <span class="w-10 shrink-0 text-muted-foreground">{{
                                t('খরচ')
                            }}</span>
                            <div class="h-2 flex-1 rounded-full bg-muted">
                                <div
                                    class="h-2 rounded-full bg-clay"
                                    :style="{
                                        width: share(p.expense, projectPeak),
                                    }"
                                />
                            </div>
                            <span class="num w-24 shrink-0 text-right">{{
                                taka(p.expense)
                            }}</span>
                        </div>
                    </div>
                    <p
                        v-if="p.children.length > 1"
                        class="mt-1.5 text-xs leading-normal text-muted-foreground"
                    >
                        <template v-for="(c, i) in p.children" :key="c.id"
                            ><span v-if="i"> · </span>{{ t(c.name) }}
                            <span
                                :class="
                                    c.profit >= 0 ? 'text-paddy' : 'text-clay'
                                "
                                >{{ taka(c.profit) }}</span
                            ></template
                        >
                    </p>
                </div>
                <p
                    v-if="!projects.length"
                    class="text-sm text-muted-foreground"
                >
                    {{ t('এই সময়ে কিছু নেই।') }}
                </p>
            </div>
        </section>

        <div class="grid gap-6 lg:grid-cols-2">
            <!-- Spending by kind -->
            <section class="rounded-xl border border-border bg-card p-4">
                <h2 class="mb-3 font-display text-lg leading-normal">
                    {{ t('কোন খাতে কত খরচ') }}
                </h2>
                <ul class="space-y-2.5 text-sm">
                    <li v-for="s in spending" :key="s.name">
                        <div class="flex justify-between gap-3">
                            <span class="leading-normal">{{ t(s.name) }}</span>
                            <span class="num whitespace-nowrap">{{
                                taka(s.amount)
                            }}</span>
                        </div>
                        <div class="mt-1 h-1.5 rounded-full bg-muted">
                            <div
                                class="h-1.5 rounded-full bg-clay/80"
                                :style="{ width: share(s.amount, spendPeak) }"
                            />
                        </div>
                    </li>
                    <li v-if="!spending.length" class="text-muted-foreground">
                        {{ t('এই সময়ে কিছু নেই।') }}
                    </li>
                </ul>
                <template v-if="earning.length">
                    <h3
                        class="mt-5 mb-2 text-sm font-semibold text-muted-foreground"
                    >
                        {{ t('আয় এলো কোথা থেকে') }}
                    </h3>
                    <ul class="space-y-1.5 text-sm">
                        <li
                            v-for="e in earning"
                            :key="e.name"
                            class="flex justify-between gap-3"
                        >
                            <span class="leading-normal">{{ t(e.name) }}</span>
                            <span class="num whitespace-nowrap text-paddy">{{
                                taka(e.amount)
                            }}</span>
                        </li>
                    </ul>
                </template>
            </section>

            <!-- Month by month -->
            <section
                class="min-w-0 rounded-xl border border-border bg-card p-4"
            >
                <h2 class="mb-4 font-display text-lg leading-normal">
                    {{ t('মাস অনুযায়ী আয়-খরচ') }}
                </h2>
                <TrendBars :months="months" />
            </section>
        </div>

        <!-- Where the farm stands -->
        <section class="rounded-xl border border-border bg-card p-4">
            <h2 class="font-display text-lg leading-normal">
                {{ t('খামারের অবস্থা') }}
            </h2>
            <p class="mb-3 text-xs leading-normal text-muted-foreground">
                {{ t(':date তারিখে', { date: day(to) }) }}
            </p>
            <div class="grid gap-4 md:grid-cols-2">
                <dl class="space-y-2 text-sm">
                    <p class="font-semibold">{{ t('খামারের যা আছে') }}</p>
                    <div class="row">
                        <dt>{{ t('হাতে ও ব্যাংকে টাকা') }}</dt>
                        <dd class="num">{{ taka(position.money) }}</dd>
                    </div>
                    <p
                        v-if="position.money_places.length > 1"
                        class="-mt-1 text-xs leading-normal text-muted-foreground"
                    >
                        <template
                            v-for="(m, i) in position.money_places"
                            :key="m.name"
                            ><span v-if="i"> · </span>{{ t(m.name) }}
                            {{ taka(m.balance) }}</template
                        >
                    </p>
                    <div class="row">
                        <dt>{{ t('পশু (কেনা দামে)') }}</dt>
                        <dd class="num">{{ taka(position.animals) }}</dd>
                    </div>
                    <div class="row">
                        <dt>{{ t('জিনিস, ঘর, মাল ও অগ্রিম') }}</dt>
                        <dd class="num">{{ taka(position.things) }}</dd>
                    </div>
                    <div class="row">
                        <dt>{{ t('অন্যের কাছে পাওনা / জামানত') }}</dt>
                        <dd class="num">{{ taka(position.receivable) }}</dd>
                    </div>
                    <ul
                        v-if="position.receivable_list.length"
                        class="-mt-1 space-y-0.5 text-xs leading-normal text-muted-foreground"
                    >
                        <li
                            v-for="r in position.receivable_list"
                            :key="r.name"
                            class="flex justify-between gap-3"
                        >
                            <span>{{ t(r.name) }}</span>
                            <span class="num whitespace-nowrap">{{
                                taka(r.balance)
                            }}</span>
                        </li>
                    </ul>
                    <div class="row font-semibold">
                        <dt>{{ t('মোট') }}</dt>
                        <dd class="num">{{ taka(position.assets) }}</dd>
                    </div>
                </dl>
                <dl class="space-y-2 text-sm">
                    <p class="font-semibold">{{ t('কার টাকা') }}</p>
                    <div class="row">
                        <dt>{{ t('মালিকদের মূলধন') }}</dt>
                        <dd class="num">{{ taka(position.capital) }}</dd>
                    </div>
                    <div class="row">
                        <dt>
                            {{
                                position.earned >= 0
                                    ? t('শুরু থেকে লাভ')
                                    : t('শুরু থেকে ক্ষতি')
                            }}
                        </dt>
                        <dd
                            :class="[
                                'num',
                                position.earned >= 0
                                    ? 'text-paddy'
                                    : 'text-clay',
                            ]"
                        >
                            {{ taka(position.earned) }}
                        </dd>
                    </div>
                    <div class="row">
                        <dt>{{ t('অন্যকে দিতে হবে (দেনা)') }}</dt>
                        <dd class="num">{{ taka(position.owed) }}</dd>
                    </div>
                    <div
                        class="mt-3 rounded-lg bg-accent/60 p-3 text-xs leading-normal"
                    >
                        <p class="mb-1 flex items-center gap-1.5 font-semibold">
                            <Landmark class="size-3.5" />
                            {{
                                t('খামারের নিট মূল্য :amount', {
                                    amount: taka(position.net),
                                })
                            }}
                        </p>
                        {{
                            t(
                                'যা আছে, তা থেকে দেনা বাদ দিলে। মালিকরা দিয়েছেন :capital; বাকিটা লাভ বা ক্ষতি।',
                                { capital: taka(position.capital) },
                            )
                        }}
                    </div>
                </dl>
            </div>
        </section>

        <!-- Herd -->
        <section class="grid gap-3 sm:grid-cols-3">
            <StatTile
                :label="t('খামারে পশু এখন')"
                :value="t(':nটি', { n: herd.now })"
                :hint="
                    herd.by_center
                        .map((c) => `${t(c.name)} ${c.count}`)
                        .join(' · ')
                "
                :icon="Beef"
                tone="clay"
            />
            <StatTile
                :label="t('এই সময়ে কেনা')"
                :value="t(':nটি', { n: herd.bought })"
                :hint="herd.bought ? taka(herd.bought_cost) : ''"
                :icon="Wallet"
                tone="turmeric"
            />
            <StatTile
                :label="t('এই সময়ে বিক্রি')"
                :value="t(':nটি', { n: herd.sold })"
                :hint="
                    herd.sold
                        ? taka(herd.sold_amount) +
                          (herd.died
                              ? ` · ${t('মৃত :n', { n: herd.died })}`
                              : '')
                        : herd.died
                          ? t('মৃত :n', { n: herd.died })
                          : ''
                "
                :icon="TrendingUp"
                tone="paddy"
            />
        </section>
    </div>
</template>

<style scoped>
@reference '../../../css/app.css';

.row {
    @apply flex items-baseline justify-between gap-3 border-b border-dashed border-border pb-2 leading-normal;
}

.row dt {
    @apply text-muted-foreground;
}

.row dd {
    @apply whitespace-nowrap;
}
</style>
