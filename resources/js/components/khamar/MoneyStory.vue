<script setup lang="ts">
import { Link } from '@inertiajs/vue3';
import { ChevronRight, TrendingDown, TrendingUp } from '@lucide/vue';
import { computed } from 'vue';
import { t } from '@/lib/i18n';
import { day, taka } from '@/lib/khamar';

/**
 * The farm in plain words, from the start until today: what the owners put in, what the farm
 * holds now, and so the gain or loss so far — with why, so a loss on paper is not a surprise.
 */
export type Story = {
    capital: number;
    worth: number;
    result: number;
    money: number;
    animals: number;
    animals_count: number;
    things: number;
    receivable: number;
    owed: number;
    income: number;
    expense: number;
    top_costs: { name: string; amount: number }[];
    since: string | null;
};

const props = withDefaults(defineProps<{ story: Story; compact?: boolean }>(), {
    compact: false,
});

const gain = computed(() => props.story.result >= 0);
const pct = computed(() =>
    props.story.capital > 0
        ? Math.round((Math.abs(props.story.result) / props.story.capital) * 100)
        : 0,
);
const parts = computed(() =>
    [
        {
            label: t('হাতে ও ব্যাংকে টাকা'),
            amount: props.story.money,
            tone: 'bg-pond',
        },
        {
            label: t(':nটি পশু (কেনা দামে)', { n: props.story.animals_count }),
            amount: props.story.animals,
            tone: 'bg-clay',
        },
        {
            label: t('জিনিস, ঘর, মাল'),
            amount: props.story.things,
            tone: 'bg-turmeric',
        },
        {
            label: t('অন্যের কাছে পাওনা'),
            amount: props.story.receivable,
            tone: 'bg-paddy',
        },
    ].filter((p) => p.amount > 0),
);
const total = computed(() =>
    Math.max(
        1,
        parts.value.reduce((s, p) => s + p.amount, 0),
        props.story.capital,
    ),
);
const share = (n: number) => `${Math.max(1, (n / total.value) * 100)}%`;
</script>

<template>
    <section
        class="overflow-hidden rounded-2xl border border-border bg-card"
        :aria-label="t('সহজ কথায় খামারের হিসাব')"
    >
        <div
            class="grid gap-5 p-4 sm:p-5 lg:grid-cols-[minmax(0,1fr)_minmax(0,1.2fr)]"
        >
            <!-- The verdict -->
            <div>
                <p
                    class="text-xs leading-normal font-semibold text-muted-foreground"
                >
                    {{ t('সহজ কথায় — শুরু থেকে আজ পর্যন্ত') }}
                    <template v-if="story.since">
                        ·
                        {{
                            t(':date থেকে', { date: day(story.since) })
                        }}</template
                    >
                </p>
                <div class="mt-2 flex items-center gap-3">
                    <span
                        :class="[
                            'flex size-11 shrink-0 items-center justify-center rounded-2xl',
                            gain
                                ? 'bg-paddy/15 text-paddy'
                                : 'bg-clay/15 text-clay',
                        ]"
                    >
                        <component
                            :is="gain ? TrendingUp : TrendingDown"
                            class="size-6"
                        />
                    </span>
                    <div>
                        <p class="text-sm leading-normal text-muted-foreground">
                            {{
                                gain
                                    ? t('এখন পর্যন্ত লাভ')
                                    : t('এখন পর্যন্ত ক্ষতি')
                            }}
                        </p>
                        <p
                            :class="[
                                'num font-display text-3xl leading-tight',
                                gain ? 'text-paddy' : 'text-clay',
                            ]"
                        >
                            {{ taka(Math.abs(story.result)) }}
                        </p>
                    </div>
                </div>
                <p class="mt-3 text-sm leading-relaxed">
                    {{
                        t(
                            'মালিকরা দিয়েছেন :capital। খামারে এখন যা আছে তার দাম :worth। তাই :what :amount — দেওয়া টাকার :pct%।',
                            {
                                capital: taka(story.capital),
                                worth: taka(story.worth),
                                what: gain ? t('লাভ') : t('ক্ষতি'),
                                amount: taka(Math.abs(story.result)),
                                pct: pct,
                            },
                        )
                    }}
                </p>
                <p
                    v-if="!compact"
                    class="mt-2 rounded-lg bg-accent/60 px-3 py-2 text-xs leading-normal"
                >
                    {{
                        t(
                            'গরু এখানে কেনা দামে ধরা — বিক্রি না হওয়া পর্যন্ত বাড়তি দাম (লাভ) গোনা হয় না, অথচ খাবার-বেতনের খরচ রোজ গোনা হয়। তাই মোটাতাজা বা গাভী থেকে আয় না আসা পর্যন্ত হিসাবে ক্ষতি দেখানোই স্বাভাবিক।',
                        )
                    }}
                </p>
            </div>

            <!-- Where it stands, as one bar -->
            <div class="min-w-0">
                <div class="flex items-baseline justify-between gap-3 text-sm">
                    <span class="text-muted-foreground">{{
                        t('মালিকদের মূলধন')
                    }}</span>
                    <span class="num font-semibold">{{
                        taka(story.capital)
                    }}</span>
                </div>
                <div class="mt-1 h-3 rounded-full bg-muted">
                    <div
                        class="h-3 rounded-full bg-foreground/25"
                        :style="{ width: share(story.capital) }"
                    />
                </div>
                <div
                    class="mt-3 flex items-baseline justify-between gap-3 text-sm"
                >
                    <span class="text-muted-foreground">{{
                        t('খামারে এখন যা আছে')
                    }}</span>
                    <span class="num font-semibold">{{
                        taka(story.worth + story.owed)
                    }}</span>
                </div>
                <div
                    class="mt-1 flex h-3 overflow-hidden rounded-full bg-muted"
                >
                    <div
                        v-for="p in parts"
                        :key="p.label"
                        :class="p.tone"
                        :style="{ width: share(p.amount) }"
                    />
                </div>
                <ul
                    class="mt-2 grid gap-x-4 gap-y-1 text-xs leading-normal sm:grid-cols-2"
                >
                    <li
                        v-for="p in parts"
                        :key="p.label"
                        class="flex items-center justify-between gap-2"
                    >
                        <span
                            class="flex items-center gap-1.5 text-muted-foreground"
                            ><span :class="['size-2 rounded-full', p.tone]" />{{
                                p.label
                            }}</span
                        >
                        <span class="num whitespace-nowrap">{{
                            taka(p.amount)
                        }}</span>
                    </li>
                    <li
                        v-if="story.owed > 0"
                        class="flex items-center justify-between gap-2 text-clay"
                    >
                        <span>{{ t('অন্যকে দিতে হবে') }}</span>
                        <span class="num whitespace-nowrap"
                            >−{{ taka(story.owed) }}</span
                        >
                    </li>
                </ul>

                <div
                    v-if="!compact && story.top_costs.length"
                    class="mt-4 border-t border-dashed border-border pt-3"
                >
                    <p
                        class="text-xs leading-normal font-semibold text-muted-foreground"
                    >
                        {{
                            t(
                                'শুরু থেকে আয় :in, খরচ :out — সবচেয়ে বেশি খরচ:',
                                {
                                    in: taka(story.income),
                                    out: taka(story.expense),
                                },
                            )
                        }}
                    </p>
                    <ul class="mt-1.5 space-y-1 text-sm">
                        <li
                            v-for="c in story.top_costs"
                            :key="c.name"
                            class="flex justify-between gap-3 leading-normal"
                        >
                            <span>{{ t(c.name) }}</span>
                            <span class="num whitespace-nowrap">{{
                                taka(c.amount)
                            }}</span>
                        </li>
                    </ul>
                </div>
            </div>
        </div>
        <Link
            v-if="compact"
            href="/summary"
            class="flex items-center justify-between gap-3 border-t border-border bg-primary/5 px-4 py-2.5 text-sm font-medium text-primary hover:bg-primary/10"
        >
            {{ t('পুরো হিসাবের সারাংশ — মাস, ৬ মাস, বছর') }}
            <ChevronRight class="size-4" />
        </Link>
    </section>
</template>
