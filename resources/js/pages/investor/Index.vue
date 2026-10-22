<script setup lang="ts">
import { Head } from '@inertiajs/vue3';
import {
    ArrowDownLeft,
    ArrowUpRight,
    Eye,
    HandCoins,
    PieChart,
    TrendingUp,
} from '@lucide/vue';
import StatTile from '@/components/khamar/StatTile.vue';
import { t } from '@/lib/i18n';
import { day, monthLabel, taka } from '@/lib/khamar';

defineOptions({
    layout: { breadcrumbs: [{ title: 'আমার বিনিয়োগ', href: '/investor' }] },
});

type Result = { income: number; expense: number; profit: number };

type Project = {
    id: number;
    center_id: number;
    name: string;
    description: string | null;
    share_percent: number | null;
    since: string | null;
    year_label: number;
    year: Result;
    all_time: Result;
    share_year: number | null;
    share_all_time: number | null;
    trend: { month: string; income: number; expense: number }[];
    recent: {
        date: string;
        description: string;
        kind: 'income' | 'expense';
        amount: number;
    }[];
};

defineProps<{
    farm: string;
    investor: string;
    asOf: string;
    projects: Project[];
    capital: {
        invested: number;
        withdrawn: number;
        net: number;
        parties: {
            id: number;
            name: string;
            invested: number;
            withdrawn: number;
            net: number;
        }[];
    };
}>();

const chart = { width: 360, height: 140, pad: 4 };

function bars(trend: Project['trend']) {
    const peak = Math.max(1, ...trend.flatMap((m) => [m.income, m.expense]));
    const slot = (chart.width - chart.pad * 2) / Math.max(1, trend.length);
    const bar = Math.max(2, slot / 2 - 2);
    const h = (v: number) =>
        Math.max(1, (Math.max(0, v) / peak) * (chart.height - 2));

    return trend.map((m, i) => {
        const x = chart.pad + i * slot + 1;
        const ih = h(m.income);
        const eh = h(m.expense);

        return {
            ...m,
            x,
            mid: x + bar,
            bar,
            income: { y: chart.height - ih, h: ih },
            expense: { y: chart.height - eh, h: eh },
            raw: m,
        };
    });
}

function tone(value: number): string {
    return value < 0 ? 'text-expense' : 'text-income';
}
</script>

<template>
    <Head :title="t('আমার বিনিয়োগ')" />

    <div class="mx-auto flex w-full max-w-4xl flex-1 flex-col p-4 md:p-6">
        <header
            class="furrows -mx-4 -mt-4 mb-6 border-b border-border/70 px-4 pt-5 pb-4 md:-mx-6 md:px-6"
        >
            <p
                class="text-xs font-medium tracking-wide text-muted-foreground uppercase"
            >
                {{ farm }}
            </p>
            <h1
                class="mt-1 font-display text-2xl leading-tight md:text-[1.7rem]"
            >
                {{ t('আমার বিনিয়োগ') }}
            </h1>
            <p class="mt-1 text-sm text-muted-foreground">
                {{ investor }} ·
                {{ t(':date পর্যন্ত হিসাব', { date: day(asOf) }) }}
            </p>
        </header>

        <section class="mb-6 grid gap-3 sm:grid-cols-3">
            <StatTile
                :label="t('আপনার দেওয়া মূলধন')"
                :value="taka(capital.invested)"
                :icon="HandCoins"
                tone="paddy"
            />
            <StatTile
                :label="t('তুলে নেওয়া')"
                :value="taka(capital.withdrawn)"
                :icon="ArrowDownLeft"
                tone="clay"
            />
            <StatTile
                :label="t('খামারে আপনার মূলধন এখন')"
                :value="taka(capital.net)"
                :icon="PieChart"
                tone="pond"
                :hint="
                    capital.parties.length > 1
                        ? capital.parties.map((p) => p.name).join(', ')
                        : ''
                "
            />
        </section>

        <div class="grid gap-5">
            <article
                v-for="p in projects"
                :key="p.id"
                class="rounded-xl border border-border bg-card p-4"
            >
                <div class="flex flex-wrap items-start justify-between gap-2">
                    <div class="min-w-0">
                        <h2 class="font-display text-xl leading-tight">
                            {{ p.name }}
                        </h2>
                        <p
                            v-if="p.description"
                            class="mt-0.5 text-sm text-muted-foreground"
                        >
                            {{ p.description }}
                        </p>
                    </div>
                    <span
                        v-if="p.share_percent !== null"
                        class="num rounded-full bg-turmeric/25 px-2.5 py-1 text-xs font-medium text-soil"
                        >{{ t('আপনার ভাগ :n%', { n: p.share_percent }) }}</span
                    >
                </div>

                <dl class="mt-4 grid grid-cols-2 gap-3 sm:grid-cols-4">
                    <div class="rounded-lg bg-muted/50 p-3">
                        <dt class="text-xs text-muted-foreground">
                            {{ t(':year সালে লাভ', { year: p.year_label }) }}
                        </dt>
                        <dd
                            :class="[
                                'num mt-1 text-lg font-semibold',
                                tone(p.year.profit),
                            ]"
                        >
                            {{ taka(p.year.profit) }}
                        </dd>
                        <dd
                            class="num mt-0.5 text-[11px] text-muted-foreground"
                        >
                            {{ t('আয়') }} {{ taka(p.year.income) }} ·
                            {{ t('খরচ') }} {{ taka(p.year.expense) }}
                        </dd>
                    </div>
                    <div class="rounded-lg bg-muted/50 p-3">
                        <dt class="text-xs text-muted-foreground">
                            {{
                                p.since
                                    ? t(':date থেকে মোট লাভ', {
                                          date: day(p.since),
                                      })
                                    : t('শুরু থেকে মোট লাভ')
                            }}
                        </dt>
                        <dd
                            :class="[
                                'num mt-1 text-lg font-semibold',
                                tone(p.all_time.profit),
                            ]"
                        >
                            {{ taka(p.all_time.profit) }}
                        </dd>
                        <dd
                            class="num mt-0.5 text-[11px] text-muted-foreground"
                        >
                            {{ t('আয়') }} {{ taka(p.all_time.income) }} ·
                            {{ t('খরচ') }} {{ taka(p.all_time.expense) }}
                        </dd>
                    </div>
                    <div class="rounded-lg bg-muted/50 p-3">
                        <dt class="text-xs text-muted-foreground">
                            {{ t('এ বছর আপনার ভাগ') }}
                        </dt>
                        <dd
                            v-if="p.share_year !== null"
                            :class="[
                                'num mt-1 text-lg font-semibold',
                                tone(p.share_year),
                            ]"
                        >
                            {{ taka(p.share_year) }}
                        </dd>
                        <dd v-else class="mt-1 text-sm text-muted-foreground">
                            {{ t('ভাগ ঠিক হয়নি') }}
                        </dd>
                    </div>
                    <div class="rounded-lg bg-muted/50 p-3">
                        <dt class="text-xs text-muted-foreground">
                            {{ t('মোট আপনার ভাগ') }}
                        </dt>
                        <dd
                            v-if="p.share_all_time !== null"
                            :class="[
                                'num mt-1 text-lg font-semibold',
                                tone(p.share_all_time),
                            ]"
                        >
                            {{ taka(p.share_all_time) }}
                        </dd>
                        <dd v-else class="mt-1 text-sm text-muted-foreground">
                            {{ t('ভাগ ঠিক হয়নি') }}
                        </dd>
                    </div>
                </dl>

                <div class="mt-5">
                    <h3
                        class="mb-2 flex items-center gap-1.5 text-sm font-medium"
                    >
                        <TrendingUp class="size-4 text-paddy" />
                        {{ t('গত ১২ মাসের আয় ও খরচ') }}
                    </h3>
                    <svg
                        :viewBox="`0 0 ${chart.width} ${chart.height + 16}`"
                        class="h-auto w-full"
                        role="img"
                        :aria-label="t('গত ১২ মাসের আয় ও খরচ')"
                    >
                        <line
                            x1="0"
                            :x2="chart.width"
                            :y1="chart.height + 0.5"
                            :y2="chart.height + 0.5"
                            class="stroke-border"
                        />
                        <g v-for="b in bars(p.trend)" :key="b.month">
                            <title>
                                {{ monthLabel(b.month) }} — {{ t('আয়') }}
                                {{ taka(b.raw.income) }}, {{ t('খরচ') }}
                                {{ taka(b.raw.expense) }}
                            </title>
                            <rect
                                :x="b.x"
                                :y="b.income.y"
                                :width="b.bar"
                                :height="b.income.h"
                                rx="1.5"
                                class="fill-income/85"
                            />
                            <rect
                                :x="b.x + b.bar + 1"
                                :y="b.expense.y"
                                :width="b.bar"
                                :height="b.expense.h"
                                rx="1.5"
                                class="fill-expense/75"
                            />
                            <text
                                :x="b.mid"
                                :y="chart.height + 12"
                                text-anchor="middle"
                                class="fill-muted-foreground text-[8px]"
                            >
                                {{ monthLabel(b.month) }}
                            </text>
                        </g>
                    </svg>
                    <div class="mt-2 flex gap-4 text-xs text-muted-foreground">
                        <span class="flex items-center gap-1.5"
                            ><i class="size-2.5 rounded-sm bg-income" />
                            {{ t('আয়') }}</span
                        >
                        <span class="flex items-center gap-1.5"
                            ><i class="size-2.5 rounded-sm bg-expense" />
                            {{ t('খরচ') }}</span
                        >
                    </div>
                </div>

                <div class="mt-5">
                    <h3 class="mb-2 text-sm font-medium">
                        {{ t('সাম্প্রতিক বড় লেনদেন') }}
                    </h3>
                    <ul
                        v-if="p.recent.length"
                        class="divide-y divide-border rounded-lg border border-border"
                    >
                        <li
                            v-for="(e, i) in p.recent"
                            :key="i"
                            class="flex items-center gap-3 px-3 py-2.5"
                        >
                            <span
                                :class="[
                                    'rounded-md p-1.5',
                                    e.kind === 'income'
                                        ? 'bg-income/12 text-income'
                                        : 'bg-expense/12 text-expense',
                                ]"
                            >
                                <ArrowUpRight
                                    v-if="e.kind === 'income'"
                                    class="size-4"
                                />
                                <ArrowDownLeft v-else class="size-4" />
                            </span>
                            <div class="min-w-0 flex-1">
                                <p class="truncate text-sm">
                                    {{ e.description }}
                                </p>
                                <p class="text-xs text-muted-foreground">
                                    {{ day(e.date) }}
                                </p>
                            </div>
                            <span
                                :class="[
                                    'num shrink-0 text-sm font-medium',
                                    e.kind === 'income'
                                        ? 'text-income'
                                        : 'text-expense',
                                ]"
                                >{{ e.kind === 'income' ? '+' : '−'
                                }}{{ taka(e.amount) }}</span
                            >
                        </li>
                    </ul>
                    <p v-else class="text-sm text-muted-foreground">
                        {{ t('এখনো কোনো লেনদেন নেই।') }}
                    </p>
                </div>
            </article>
        </div>

        <p
            class="mt-6 flex items-center justify-center gap-1.5 text-center text-xs text-muted-foreground"
        >
            <Eye class="size-3.5" />
            {{
                t(
                    'এই পাতা শুধু দেখার জন্য। হিসাবে কোনো প্রশ্ন থাকলে খামারের হিসাবরক্ষকের সাথে কথা বলুন।',
                )
            }}
        </p>
    </div>
</template>
