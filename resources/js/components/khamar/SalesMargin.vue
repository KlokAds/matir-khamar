<script setup lang="ts">
import { Link } from '@inertiajs/vue3';
import { Coins } from '@lucide/vue';
import { t } from '@/lib/i18n';
import { day, taka } from '@/lib/khamar';

/**
 * What each sale really left after everything — sale less purchase and the animal's share of
 * feed, medicine and wages while it was kept — and the margin over all sales.
 */
export type Sales = {
    rows: {
        id: number;
        tag: string;
        center: string;
        sold_on: string | null;
        days: number;
        sale: number;
        purchase: number;
        upkeep: number;
        profit: number;
        margin: number;
    }[];
    sale: number;
    cost: number;
    profit: number;
    margin: number;
};

withDefaults(defineProps<{ sales: Sales; compact?: boolean }>(), {
    compact: false,
});
</script>

<template>
    <section class="rounded-2xl border border-border bg-card p-4 sm:p-5">
        <div class="flex flex-wrap items-start justify-between gap-3">
            <h2
                class="flex items-center gap-2 font-display text-lg leading-normal"
            >
                <Coins class="size-5 text-turmeric" />
                {{ t('বিক্রির পর আসল লাভ (মার্জিন)') }}
            </h2>
            <span
                v-if="sales.rows.length"
                :class="[
                    'num rounded-full px-3 py-1 text-sm font-semibold',
                    sales.profit >= 0
                        ? 'bg-paddy/15 text-paddy'
                        : 'bg-clay/15 text-clay',
                ]"
                >{{ t('মার্জিন :pct%', { pct: sales.margin }) }}</span
            >
        </div>

        <p
            v-if="!sales.rows.length"
            class="mt-3 rounded-lg bg-muted/50 px-3 py-3 text-sm leading-normal text-muted-foreground"
        >
            {{
                t(
                    'এখনো কিছু বিক্রি হয়নি। বিক্রি হলে এখানে দেখাবে: বিক্রি দাম থেকে কেনা দাম আর খাবার-ওষুধ-মজুরির ভাগ বাদ দিয়ে আসল লাভ কত থাকল।',
                )
            }}
        </p>

        <template v-else>
            <!-- All sales together -->
            <div class="mt-3 grid grid-cols-3 gap-2 text-center">
                <div class="rounded-xl bg-muted/50 p-2.5">
                    <p class="text-xs leading-normal text-muted-foreground">
                        {{ t('মোট বিক্রি') }}
                    </p>
                    <p class="num font-semibold">{{ taka(sales.sale) }}</p>
                </div>
                <div class="rounded-xl bg-muted/50 p-2.5">
                    <p class="text-xs leading-normal text-muted-foreground">
                        {{ t('মোট খরচ') }}
                    </p>
                    <p class="num font-semibold">{{ taka(sales.cost) }}</p>
                </div>
                <div
                    :class="[
                        'rounded-xl p-2.5',
                        sales.profit >= 0 ? 'bg-paddy/10' : 'bg-clay/10',
                    ]"
                >
                    <p class="text-xs leading-normal text-muted-foreground">
                        {{ sales.profit >= 0 ? t('আসল লাভ') : t('আসল ক্ষতি') }}
                    </p>
                    <p
                        :class="[
                            'num font-semibold',
                            sales.profit >= 0 ? 'text-paddy' : 'text-clay',
                        ]"
                    >
                        {{ taka(Math.abs(sales.profit)) }}
                    </p>
                </div>
            </div>

            <!-- Each sale -->
            <ul class="mt-3 space-y-2">
                <li
                    v-for="r in compact ? sales.rows.slice(0, 3) : sales.rows"
                    :key="r.id"
                >
                    <Link
                        :href="`/animals/${r.id}`"
                        class="block rounded-xl border border-border p-3 transition-colors hover:bg-accent/40 active:bg-accent"
                    >
                        <div class="flex items-baseline justify-between gap-3">
                            <span class="min-w-0 leading-normal">
                                <span class="font-semibold">{{ r.tag }}</span>
                                <span class="text-xs text-muted-foreground">
                                    · {{ t(r.center) }}
                                    <template v-if="r.sold_on"
                                        >· {{ day(r.sold_on) }}</template
                                    >
                                    ·
                                    {{ t(':n দিন রাখা', { n: r.days }) }}</span
                                >
                            </span>
                            <span
                                :class="[
                                    'num shrink-0 text-sm font-semibold',
                                    r.profit >= 0 ? 'text-paddy' : 'text-clay',
                                ]"
                                >{{ r.profit >= 0 ? '+' : '−'
                                }}{{ taka(Math.abs(r.profit)) }} ·
                                {{ r.margin }}%</span
                            >
                        </div>
                        <p
                            class="num mt-1 text-xs leading-normal text-muted-foreground"
                        >
                            {{
                                t(
                                    'বিক্রি :sale − কেনা :buy − খাবার/ওষুধ/মজুরি :upkeep',
                                    {
                                        sale: taka(r.sale),
                                        buy: taka(r.purchase),
                                        upkeep: taka(r.upkeep),
                                    },
                                )
                            }}
                        </p>
                    </Link>
                </li>
            </ul>
            <p class="mt-2 text-xs leading-normal text-muted-foreground">
                {{
                    t(
                        'খাবার-মজুরির ভাগ: পশুটি যে ক্যাটাগরিতে যতদিন ছিল, তার খরচের দিন অনুযায়ী অংশ। "সাধারণ" খরচ মাস শেষে ভাগ করলে তার অংশও যোগ হয়।',
                    )
                }}
            </p>
        </template>
    </section>
</template>
