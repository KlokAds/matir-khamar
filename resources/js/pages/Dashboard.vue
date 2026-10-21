<script setup lang="ts">
import { Head, Link } from '@inertiajs/vue3';
import {
    Beef,
    CircleAlert,
    HandCoins,
    Milk,
    Scale,
    TrendingDown,
    TrendingUp,
    Wallet,
    Wheat,
} from '@lucide/vue';
import { computed } from 'vue';
import CenterRows from '@/components/khamar/CenterRows.vue';
import PageTitle from '@/components/khamar/PageTitle.vue';
import StatTile from '@/components/khamar/StatTile.vue';
import TrendBars from '@/components/khamar/TrendBars.vue';
import { Button } from '@/components/ui/button';
import { day, monthFull, qty, taka, useCan } from '@/lib/khamar';
import type { TreeNode } from '@/lib/khamar';
import { dashboard } from '@/routes';

defineOptions({
    layout: { breadcrumbs: [{ title: 'আজকের খামার', href: dashboard() }] },
});

const props = defineProps<{
    canSeeMoney: boolean;
    month: string;
    farm: { income: number; expense: number; profit: number } | null;
    farmYear: { income: number; expense: number; profit: number } | null;
    tree: TreeNode[];
    trend: { month: string; income: number; expense: number }[];
    money: { id: number; name: string; balance: number }[];
    milkThisMonth: number;
    animals: { center: string; count: number }[];
    lowStock: { id: number; name: string; unit: string; qty_on_hand: number }[];
    recent: {
        id: number;
        number: string;
        date: string;
        type: string;
        description: string;
        amount: number;
        reversed: boolean;
    }[];
}>();

const can = useCan();
const cashTotal = computed(() =>
    props.money.reduce((sum, row) => sum + row.balance, 0),
);
const herd = computed(() =>
    props.animals.reduce((sum, row) => sum + row.count, 0),
);

const greeting = computed(() => {
    const hour = new Date().getHours();

    return hour < 12 ? 'শুভ সকাল' : hour < 17 ? 'শুভ দুপুর' : 'শুভ সন্ধ্যা';
});
</script>

<template>
    <Head title="আজকের খামার" />

    <div class="flex flex-1 flex-col p-4 md:p-6">
        <PageTitle
            :title="`${greeting}, মাটির খামার`"
            :subtitle="`${monthFull(month)} — গোপাই কাটখৈর, নওগাঁ সদর`"
        >
            <template #actions>
                <Button v-if="can['post-daily']" as-child variant="secondary">
                    <Link href="/milk"><Milk /> দুধ লিখুন</Link>
                </Button>
                <Button v-if="can['post-daily']" as-child variant="secondary">
                    <Link href="/stock/move?mode=issue"
                        ><Wheat /> খাবার দিন</Link
                    >
                </Button>
                <Button v-if="can['post-entries']" as-child>
                    <Link href="/entries/create"
                        ><HandCoins /> নতুন এন্ট্রি</Link
                    >
                </Button>
            </template>
        </PageTitle>

        <div class="grid gap-3 sm:grid-cols-2 xl:grid-cols-4">
            <template v-if="canSeeMoney && farm">
                <StatTile
                    label="এ মাসের আয়"
                    :value="taka(farm.income)"
                    :icon="TrendingUp"
                    tone="paddy"
                    :hint="`বছরে ${taka(farmYear?.income)}`"
                />
                <StatTile
                    label="এ মাসের খরচ"
                    :value="taka(farm.expense)"
                    :icon="TrendingDown"
                    tone="clay"
                    :hint="`বছরে ${taka(farmYear?.expense)}`"
                />
                <StatTile
                    label="এ মাসের লাভ / ক্ষতি"
                    :value="taka(farm.profit)"
                    :icon="Scale"
                    :tone="farm.profit < 0 ? 'clay' : 'turmeric'"
                    :hint="`বছরে ${taka(farmYear?.profit)}`"
                />
                <StatTile
                    label="হাতে ও ব্যাংকে টাকা"
                    :value="taka(cashTotal)"
                    :icon="Wallet"
                    tone="pond"
                    :hint="
                        money
                            .filter((m) => m.balance !== 0)
                            .map(
                                (m) =>
                                    `${m.name.split(' ')[0]} ${taka(m.balance)}`,
                            )
                            .join(' · ')
                    "
                />
            </template>
            <StatTile
                label="এ মাসে দুধ"
                :value="qty(milkThisMonth, 'লিটার')"
                :icon="Milk"
                tone="pond"
            />
            <StatTile
                label="খামারে গরু"
                :value="`${herd}টি`"
                :icon="Beef"
                tone="clay"
                :hint="animals.map((a) => `${a.center} ${a.count}`).join(' · ')"
            />
        </div>

        <div
            v-if="lowStock.length"
            class="mt-4 flex items-start gap-3 rounded-xl border border-clay/40 bg-clay/8 p-3 text-sm"
        >
            <CircleAlert class="mt-0.5 size-4 shrink-0 text-clay" />
            <p>
                স্টক কমে গেছে:
                <span v-for="(item, index) in lowStock" :key="item.id">
                    <b>{{ item.name }}</b> ({{ qty(item.qty_on_hand) }})<span
                        v-if="index < lowStock.length - 1"
                        >,
                    </span>
                </span>
            </p>
        </div>

        <div v-if="canSeeMoney" class="mt-6 grid gap-6 xl:grid-cols-5">
            <section
                class="min-w-0 rounded-xl border border-border bg-card p-4 xl:col-span-3"
            >
                <div class="mb-3 flex items-center justify-between">
                    <h2 class="font-display text-lg">
                        ক্যাটাগরি অনুযায়ী এ মাস
                    </h2>
                    <Link
                        href="/centers"
                        class="text-sm text-primary hover:underline"
                        >সব দেখুন</Link
                    >
                </div>
                <div class="overflow-x-auto">
                    <table class="w-full min-w-[520px] text-sm">
                        <thead>
                            <tr
                                class="border-b border-border text-left text-xs text-muted-foreground"
                            >
                                <th class="pb-2 font-medium">
                                    ক্যাটাগরি / প্রজেক্ট
                                </th>
                                <th class="pb-2 text-right font-medium">আয়</th>
                                <th class="pb-2 text-right font-medium">খরচ</th>
                                <th class="pb-2 text-right font-medium">লাভ</th>
                            </tr>
                        </thead>
                        <tbody>
                            <CenterRows :nodes="tree" />
                        </tbody>
                    </table>
                </div>
                <p class="mt-3 text-xs text-muted-foreground">
                    নিজের জমির ঘাস গরুকে দিলে সেটা ঘাস চাষে আয় আর গরুতে খরচ
                    হিসেবে দেখায়; পুরো খামারের মোট হিসাবে এটা বাদ যায়, কারণ
                    টাকা খামারের বাইরে যায়নি।
                </p>
            </section>

            <section
                class="min-w-0 rounded-xl border border-border bg-card p-4 xl:col-span-2"
            >
                <h2 class="font-display mb-4 text-lg">গত ৬ মাস</h2>
                <TrendBars :months="trend" />
            </section>
        </div>

        <section
            v-if="canSeeMoney && recent.length"
            class="mt-6 rounded-xl border border-border bg-card p-4"
        >
            <div class="mb-2 flex items-center justify-between">
                <h2 class="font-display text-lg">সাম্প্রতিক এন্ট্রি</h2>
                <Link
                    href="/entries"
                    class="text-sm text-primary hover:underline"
                    >খাতা খুলুন</Link
                >
            </div>
            <ul class="divide-y divide-border/70">
                <li v-for="entry in recent" :key="entry.id">
                    <Link
                        :href="`/entries/${entry.id}`"
                        class="flex items-center gap-3 py-2.5 text-sm hover:bg-accent/40"
                    >
                        <span
                            class="w-24 shrink-0 text-xs text-muted-foreground"
                            >{{ day(entry.date) }}</span
                        >
                        <span
                            class="min-w-0 flex-1 truncate"
                            :class="entry.reversed && 'line-through opacity-60'"
                        >
                            {{ entry.description }}
                            <span class="ml-1 text-xs text-muted-foreground"
                                >· {{ entry.type }}</span
                            >
                        </span>
                        <span class="num shrink-0 font-medium">{{
                            taka(entry.amount)
                        }}</span>
                    </Link>
                </li>
            </ul>
        </section>

        <section
            v-if="!canSeeMoney"
            class="mt-6 rounded-xl border border-dashed border-border bg-card/60 p-6 text-center text-sm text-muted-foreground"
        >
            আপনার কাজ: প্রতিদিন সকাল-বিকাল দুধের হিসাব আর গরুকে কতটুকু খাবার
            দেওয়া হলো তা লিখুন। বাকি হিসাব অ্যাডমিন দেখবেন।
        </section>
    </div>
</template>
