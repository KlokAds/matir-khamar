<script setup lang="ts">
import { Head, Link } from '@inertiajs/vue3';
import {
    Beef,
    BellRing,
    ChevronRight,
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
import CenterIcon from '@/components/khamar/CenterIcon.vue';
import CenterRows from '@/components/khamar/CenterRows.vue';
import PageTitle from '@/components/khamar/PageTitle.vue';
import StatTile from '@/components/khamar/StatTile.vue';
import TrendBars from '@/components/khamar/TrendBars.vue';
import { Button } from '@/components/ui/button';
import { t } from '@/lib/i18n';
import {
    day,
    monthFull,
    qty,
    taka,
    useCan,
    useHerdWords,
    useModules,
} from '@/lib/khamar';
import type { TreeNode } from '@/lib/khamar';
import { dashboard } from '@/routes';

defineOptions({
    layout: { breadcrumbs: [{ title: 'আজকের খামার', href: dashboard() }] },
});

const props = defineProps<{
    canSeeMoney: boolean;
    reminders: {
        kind: string;
        title: string;
        detail: string;
        href: string;
        when: string;
        urgent: boolean;
    }[];
    month: string;
    farm: { income: number; expense: number; profit: number } | null;
    farmYear: { income: number; expense: number; profit: number } | null;
    tree: TreeNode[];
    projects: {
        id: number;
        name: string;
        kind: string;
        income: number;
        expense: number;
        profit: number;
        year_profit: number;
        animals: number | null;
        milk: number;
    }[];
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
const modules = useModules();
const herdWords = useHerdWords();
const cashTotal = computed(() =>
    props.money.reduce((sum, row) => sum + row.balance, 0),
);
const herd = computed(() =>
    props.animals.reduce((sum, row) => sum + row.count, 0),
);

const greeting = computed(() => {
    const hour = new Date().getHours();

    return t(hour < 12 ? 'শুভ সকাল' : hour < 17 ? 'শুভ দুপুর' : 'শুভ সন্ধ্যা');
});
</script>

<template>
    <Head :title="t('আজকের খামার')" />

    <div class="flex flex-1 flex-col p-4 md:p-6">
        <PageTitle
            :title="`${greeting}, ${t('মাটির খামার')}`"
            :subtitle="`${monthFull(month)} — ${t('গোপাই কাটখৈর, নওগাঁ সদর')}`"
        >
            <template #actions>
                <Button
                    v-if="can['post-daily'] && modules.milk"
                    as-child
                    variant="secondary"
                >
                    <Link href="/milk"><Milk /> {{ t('দুধ লিখুন') }}</Link>
                </Button>
                <Button
                    v-if="can['post-daily'] && (modules.cattle || modules.goat)"
                    as-child
                    variant="secondary"
                >
                    <Link href="/stock/move?mode=issue"
                        ><Wheat /> {{ t('খাবার দিন') }}</Link
                    >
                </Button>
                <Button v-if="can['post-entries']" as-child>
                    <Link href="/entries/create"
                        ><HandCoins /> {{ t('নতুন এন্ট্রি') }}</Link
                    >
                </Button>
            </template>
        </PageTitle>

        <div class="grid gap-3 sm:grid-cols-2 xl:grid-cols-4">
            <template v-if="canSeeMoney && farm">
                <StatTile
                    :label="t('এ মাসের আয়')"
                    :value="taka(farm.income)"
                    :icon="TrendingUp"
                    tone="paddy"
                    :hint="
                        t('বছরে :amount', { amount: taka(farmYear?.income) })
                    "
                />
                <StatTile
                    :label="t('এ মাসের খরচ')"
                    :value="taka(farm.expense)"
                    :icon="TrendingDown"
                    tone="clay"
                    :hint="
                        t('বছরে :amount', { amount: taka(farmYear?.expense) })
                    "
                />
                <StatTile
                    :label="t('এ মাসের লাভ / ক্ষতি')"
                    :value="taka(farm.profit)"
                    :icon="Scale"
                    :tone="farm.profit < 0 ? 'clay' : 'turmeric'"
                    :hint="
                        t('বছরে :amount', { amount: taka(farmYear?.profit) })
                    "
                />
                <StatTile
                    :label="t('হাতে ও ব্যাংকে টাকা')"
                    :value="taka(cashTotal)"
                    :icon="Wallet"
                    tone="pond"
                    :hint="
                        money
                            .filter((m) => m.balance !== 0)
                            .map(
                                (m) =>
                                    `${t(m.name).split(' ')[0]} ${taka(m.balance)}`,
                            )
                            .join(' · ')
                    "
                />
            </template>
            <StatTile
                v-if="modules.milk"
                :label="t('এ মাসে দুধ')"
                :value="qty(milkThisMonth, t('লিটার'))"
                :icon="Milk"
                tone="pond"
            />
            <StatTile
                v-if="modules.cattle || modules.goat"
                :label="t('খামারে :kind', { kind: t(herdWords.group) })"
                :value="t(':nটি', { n: herd })"
                :icon="Beef"
                tone="clay"
                :hint="
                    animals.map((a) => `${t(a.center)} ${a.count}`).join(' · ')
                "
            />
        </div>

        <Link
            v-if="canSeeMoney"
            href="/summary"
            class="mt-3 flex items-center justify-between gap-3 rounded-xl border border-primary/30 bg-primary/5 px-4 py-3 transition-colors hover:bg-primary/10 active:bg-primary/10"
        >
            <span class="min-w-0">
                <span class="block leading-normal font-medium">{{
                    t('পুরো হিসাবের সারাংশ')
                }}</span>
                <span
                    class="block text-xs leading-normal text-muted-foreground"
                    >{{
                        t('এ মাস, গত মাস, ৬ মাস, এ বছর বা নিজের বাছাই করা সময়')
                    }}</span
                >
            </span>
            <ChevronRight class="size-5 shrink-0 text-primary" />
        </Link>

        <section
            v-if="reminders.length"
            class="mt-4 rounded-xl border border-border bg-card p-3"
        >
            <h2 class="mb-2 flex items-center gap-2 text-sm font-semibold">
                <BellRing class="size-4 text-turmeric" />
                {{ t('মনে রাখার কাজ') }}
            </h2>
            <ul class="grid gap-1.5 sm:grid-cols-2">
                <li v-for="r in reminders.slice(0, 8)" :key="r.title + r.when">
                    <Link
                        :href="r.href"
                        class="flex items-start gap-2 rounded-lg bg-muted/40 p-2 text-sm hover:bg-muted/70"
                    >
                        <span
                            :class="[
                                'mt-1.5 size-2 shrink-0 rounded-full',
                                r.urgent ? 'bg-destructive' : 'bg-turmeric',
                            ]"
                        />
                        <span class="min-w-0">
                            <span class="block font-medium">{{ r.title }}</span>
                            <span class="block text-xs text-muted-foreground">{{
                                r.detail
                            }}</span>
                        </span>
                    </Link>
                </li>
            </ul>
        </section>

        <div
            v-if="lowStock.length"
            class="mt-4 flex items-start gap-3 rounded-xl border border-clay/40 bg-clay/8 p-3 text-sm"
        >
            <CircleAlert class="mt-0.5 size-4 shrink-0 text-clay" />
            <p>
                {{ t('স্টক কমে গেছে:') }}
                <span v-for="(item, index) in lowStock" :key="item.id">
                    <b>{{ t(item.name) }}</b> ({{ qty(item.qty_on_hand) }})<span
                        v-if="index < lowStock.length - 1"
                        >,
                    </span>
                </span>
            </p>
        </div>

        <section v-if="canSeeMoney && projects.length" class="mt-6">
            <div class="mb-3 flex flex-wrap items-center justify-between gap-2">
                <h2 class="font-display text-lg">
                    {{ t('প্রজেক্ট অনুযায়ী এ মাস') }}
                </h2>
                <Link href="/centers" class="btn-chip"
                    >{{ t('সব ক্যাটাগরি') }} <ChevronRight
                /></Link>
            </div>
            <div class="grid gap-3 sm:grid-cols-2 xl:grid-cols-4">
                <Link
                    v-for="p in projects"
                    :key="p.id"
                    :href="`/centers/${p.id}`"
                    class="rounded-xl border border-border bg-card p-4 transition-shadow hover:shadow-md active:bg-accent/40"
                >
                    <div class="flex items-center gap-2">
                        <CenterIcon :kind="p.kind" />
                        <p class="min-w-0 flex-1 truncate font-medium">
                            {{ t(p.name) }}
                        </p>
                        <span
                            v-if="p.animals !== null"
                            class="num rounded-full bg-accent px-2 py-0.5 text-xs"
                            >{{ t(':nটি', { n: p.animals }) }}</span
                        >
                    </div>
                    <p
                        :class="[
                            'num mt-3 text-2xl font-semibold',
                            p.profit < 0
                                ? 'text-destructive'
                                : 'text-foreground',
                        ]"
                    >
                        {{ taka(p.profit) }}
                    </p>
                    <p class="text-xs text-muted-foreground">
                        {{ t('এ মাসের লাভ / ক্ষতি') }}
                    </p>
                    <div
                        class="mt-3 grid grid-cols-2 gap-2 border-t border-dashed border-border pt-3 text-xs"
                    >
                        <span
                            >{{ t('আয়') }}
                            <b class="num block text-sm text-income">{{
                                taka(p.income)
                            }}</b></span
                        >
                        <span
                            >{{ t('খরচ') }}
                            <b class="num block text-sm text-expense">{{
                                taka(p.expense)
                            }}</b></span
                        >
                    </div>
                    <p class="mt-2 text-xs text-muted-foreground">
                        {{ t('বছরে :amount', { amount: taka(p.year_profit) }) }}
                        <span v-if="p.milk > 0">
                            · {{ t('দুধ :n লি.', { n: qty(p.milk) }) }}</span
                        >
                    </p>
                </Link>
            </div>
        </section>

        <div v-if="canSeeMoney" class="mt-6 grid gap-6 xl:grid-cols-5">
            <section
                class="min-w-0 rounded-xl border border-border bg-card p-4 xl:col-span-3"
            >
                <div
                    class="mb-3 flex flex-wrap items-center justify-between gap-2"
                >
                    <h2 class="font-display text-lg">
                        {{ t('ক্যাটাগরি অনুযায়ী এ মাস') }}
                    </h2>
                    <Link href="/centers" class="btn-chip"
                        >{{ t('সব দেখুন') }} <ChevronRight
                    /></Link>
                </div>
                <div class="overflow-x-auto">
                    <table class="w-full min-w-[520px] text-sm">
                        <thead>
                            <tr
                                class="border-b border-border text-left text-xs text-muted-foreground"
                            >
                                <th class="pb-2 font-medium">
                                    {{ t('ক্যাটাগরি / প্রজেক্ট') }}
                                </th>
                                <th class="pb-2 text-right font-medium">
                                    {{ t('আয়') }}
                                </th>
                                <th class="pb-2 text-right font-medium">
                                    {{ t('খরচ') }}
                                </th>
                                <th class="pb-2 text-right font-medium">
                                    {{ t('লাভ') }}
                                </th>
                            </tr>
                        </thead>
                        <tbody>
                            <CenterRows :nodes="tree" />
                        </tbody>
                    </table>
                </div>
                <p class="mt-3 text-xs text-muted-foreground">
                    {{
                        t(
                            'নিজের জমির ঘাস গরুকে দিলে সেটা ঘাস চাষে আয় আর গরুতে খরচ হিসেবে দেখায়; পুরো খামারের মোট হিসাবে এটা বাদ যায়, কারণ টাকা খামারের বাইরে যায়নি।',
                        )
                    }}
                </p>
            </section>

            <section
                class="min-w-0 rounded-xl border border-border bg-card p-4 xl:col-span-2"
            >
                <h2 class="mb-4 font-display text-lg">{{ t('গত ৬ মাস') }}</h2>
                <TrendBars :months="trend" />
            </section>
        </div>

        <section
            v-if="canSeeMoney && recent.length"
            class="mt-6 rounded-xl border border-border bg-card p-4"
        >
            <div class="mb-2 flex flex-wrap items-center justify-between gap-2">
                <h2 class="font-display text-lg">
                    {{ t('সাম্প্রতিক এন্ট্রি') }}
                </h2>
                <Link href="/entries" class="btn-chip"
                    >{{ t('খাতা খুলুন') }} <ChevronRight
                /></Link>
            </div>
            <ul class="divide-y divide-border/70">
                <li v-for="entry in recent" :key="entry.id">
                    <Link
                        :href="`/entries/${entry.id}`"
                        class="flex items-center gap-3 py-2.5 text-sm hover:bg-accent/40 active:bg-accent/60"
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
                                >· {{ t(entry.type) }}</span
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
            {{
                t(
                    'আপনার কাজ: প্রতিদিন সকাল-বিকাল দুধের হিসাব আর গরুকে কতটুকু খাবার দেওয়া হলো তা লিখুন। বাকি হিসাব অ্যাডমিন দেখবেন।',
                )
            }}
        </section>
    </div>
</template>
