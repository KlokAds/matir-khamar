<script setup lang="ts">
import { Head, Link, router, useForm } from '@inertiajs/vue3';
import {
    Baby,
    CalendarClock,
    Check,
    ChevronDown,
    ChevronRight,
    Eye,
    HeartPulse,
    Hourglass,
    Plus,
    X,
} from '@lucide/vue';
import { computed, ref } from 'vue';
import Field from '@/components/khamar/Field.vue';
import NSelect from '@/components/khamar/NSelect.vue';
import PageTitle from '@/components/khamar/PageTitle.vue';
import StatTile from '@/components/khamar/StatTile.vue';
import { Button } from '@/components/ui/button';
import DateField from '@/components/khamar/DateField.vue';
import { Input } from '@/components/ui/input';
import { ask } from '@/lib/dialog';
import { t } from '@/lib/i18n';
import { day, today } from '@/lib/khamar';

defineOptions({
    layout: { breadcrumbs: [{ title: 'গর্ভ ও বাছুর', href: '/breeding' }] },
});

type Stage = { from: number; to: number; title: string; tasks: string[] };

type BreedingCard = {
    id: number;
    animal_id: number;
    tag: string | null;
    name: string | null;
    served_on: string;
    estimated: boolean;
    method: string;
    sire: string | null;
    by_whom: string | null;
    result: 'pending' | 'pregnant' | 'empty';
    result_label: string;
    checked_on: string | null;
    expected_on: string;
    calved_on: string | null;
    outcome: string | null;
    calf_id: number | null;
    notes: string | null;
    open: boolean;
    day: number;
    months: string;
    days_left: number | null;
    progress: number;
    stage: Stage | null;
};

type WaitingCow = {
    id: number;
    tag: string;
    name: string | null;
    center: string;
    last_calved: string | null;
};

const props = defineProps<{
    open: BreedingCard[];
    recent: BreedingCard[];
    waiting: WaitingCow[];
    guide: Stage[];
    calfGuide: Stage[];
}>();

const GESTATION = 283;

const outcomes: Record<string, string> = {
    female: t('বকনা বাছুর'),
    male: t('এঁড়ে বাছুর'),
    twins: t('যমজ'),
    stillborn: t('মৃত বাচ্চা'),
    aborted: t('গর্ভপাত'),
};
const liveOutcomes = ['female', 'male', 'twins'];

const markers = [
    { at: 90, label: t('৩ মাস'), row: 0 },
    { at: 180, label: t('৬ মাস'), row: 1 },
    { at: 210, label: t('৭ মাস · দুধ বন্ধ'), row: 0, key: true },
    { at: 270, label: t('৯ মাস'), row: 1 },
].map((m) => ({ ...m, pct: (m.at / GESTATION) * 100 }));

const stats = computed(() => ({
    pregnant: props.open.filter((c) => c.result === 'pregnant' && c.open)
        .length,
    pending: props.open.filter((c) => c.result === 'pending').length,
    soon: props.open.filter((c) => c.days_left !== null && c.days_left <= 30)
        .length,
}));

const cowName = (c: { tag: string | null; name: string | null }) =>
    [c.tag, c.name].filter(Boolean).join(' · ') || t('নাম নেই');

const leftText = (n: number | null) =>
    n === null
        ? ''
        : n >= 0
          ? t('আর :n দিন', { n })
          : t(':n দিন পার', { n: Math.abs(n) });

const outcomeLabel = (key: string | null) =>
    key ? (outcomes[key] ?? key) : '—';

const range = (s: Stage) => t(':from–:to দিন', { from: s.from, to: s.to });

// One phrase so English can reorder it; the months value sits in its own styled span.
const monthsParts = t('গর্ভের :months চলছে')
    .split(':months')
    .map((part) => part.trim());

/* The ring counts down the 283 days; the steps are the dates that matter on the way. */
const ring = { length: 2 * Math.PI * 52 };
const ringOffset = (card: BreedingCard) =>
    ring.length * (1 - Math.min(100, Math.max(0, card.progress)) / 100);

const addDays = (iso: string, n: number) => {
    const d = new Date(`${iso}T00:00:00`);
    d.setDate(d.getDate() + n);

    return `${d.getFullYear()}-${String(d.getMonth() + 1).padStart(2, '0')}-${String(d.getDate()).padStart(2, '0')}`;
};

function milestones(card: BreedingCard) {
    const steps = [
        {
            key: 'served',
            title: t('বীজ দেওয়া'),
            date: card.served_on,
            done: true,
        },
        {
            key: 'check',
            title:
                card.result === 'pregnant'
                    ? t('গর্ভ নিশ্চিত')
                    : t('গর্ভ পরীক্ষা (২–৩ মাসে)'),
            date: card.checked_on ?? addDays(card.served_on, 60),
            done: card.result === 'pregnant',
        },
        {
            key: 'dry',
            title: t('দুধ বন্ধ / শুকানো (৭ মাস)'),
            date: addDays(card.served_on, 210),
            done: card.day >= 210,
        },
        {
            key: 'ready',
            title: t('প্রসবের প্রস্তুতি — আলাদা জায়গা'),
            date: addDays(card.expected_on, -14),
            done: card.day >= GESTATION - 14,
        },
        {
            key: 'calving',
            title: t('প্রসব'),
            date: card.expected_on,
            done: !!card.calved_on,
        },
    ];
    const next = steps.findIndex((s) => !s.done);

    return steps.map((s, i) => ({
        ...s,
        next: i === next,
        when: day(s.date),
    }));
}

const busy = ref<number | null>(null);

async function check(card: BreedingCard, result: 'pregnant' | 'empty') {
    if (
        result === 'empty' &&
        !(await ask.confirm({
            title: t('গর্ভ হয়নি?'),
            body: t(
                ':cow — এই বীজ দেওয়াটি "গর্ভ হয়নি" হিসেবে বন্ধ হবে। আবার গরম এলে নতুন করে বীজ দিন।',
                { cow: cowName(card) },
            ),
            confirmText: t('হ্যাঁ, গর্ভ হয়নি'),
            danger: true,
        }))
    ) {
        return;
    }

    busy.value = card.id;
    router.put(
        `/breedings/${card.id}`,
        { result, checked_on: today() },
        { preserveScroll: true, onFinish: () => (busy.value = null) },
    );
}

const calvingFor = ref<number | null>(null);
const calving = useForm({
    calved_on: today(),
    outcome: 'female' as string,
    notes: '',
});

function openCalving(card: BreedingCard) {
    calving.reset();
    calving.clearErrors();
    calving.calved_on = today();
    calvingFor.value = calvingFor.value === card.id ? null : card.id;
}

function saveCalving(card: BreedingCard) {
    calving.put(`/breedings/${card.id}`, {
        preserveScroll: true,
        onSuccess: () => {
            calvingFor.value = null;
            calving.reset();
        },
    });
}
</script>

<template>
    <Head :title="t('গর্ভ ও বাছুর')" />

    <div class="flex flex-1 flex-col p-4 md:p-6">
        <PageTitle
            :title="t('গর্ভ ও বাছুর')"
            :subtitle="
                t(
                    'কোন গাভী কতদিনের গর্ভবতী, কবে বাচ্চা হবে, আর এখন কী যত্ন দরকার — এক নজরে।',
                )
            "
        />

        <div class="grid grid-cols-1 gap-3 sm:grid-cols-3">
            <StatTile
                :label="t('গর্ভবতী')"
                :value="String(stats.pregnant)"
                :hint="t('নিশ্চিত গর্ভ')"
                :icon="HeartPulse"
                tone="paddy"
            />
            <StatTile
                :label="t('পরীক্ষা বাকি')"
                :value="String(stats.pending)"
                :hint="t('বীজ দেওয়া হয়েছে, এখনো দেখা হয়নি')"
                :icon="Hourglass"
                tone="turmeric"
            />
            <StatTile
                :label="t('৩০ দিনের মধ্যে বাচ্চা')"
                :value="String(stats.soon)"
                :hint="t('প্রসবের প্রস্তুতি নিন')"
                :icon="Baby"
                tone="clay"
            />
        </div>

        <!-- Open pregnancies -->
        <section class="mt-6">
            <h2 class="mb-3 font-display text-lg">{{ t('চলমান গর্ভ') }}</h2>

            <div
                v-if="!open.length"
                class="rounded-xl border border-dashed border-border bg-card/60 px-4 py-10 text-center text-sm text-muted-foreground"
            >
                {{
                    t(
                        'এখন কোনো গাভী গর্ভবতী বা পরীক্ষার অপেক্ষায় নেই। গাভীর পাতায় গিয়ে বীজ দেওয়ার তারিখ লিখুন।',
                    )
                }}
            </div>

            <div v-else class="grid gap-4">
                <article
                    v-for="card in open"
                    :key="card.id"
                    class="overflow-hidden rounded-2xl border border-border bg-card shadow-[0_1px_0_0_var(--border)]"
                >
                    <div
                        class="grid gap-5 p-4 sm:p-5 lg:grid-cols-[minmax(0,17rem)_minmax(0,1fr)_minmax(0,1fr)]"
                    >
                        <!-- Who, and how long to go -->
                        <div
                            class="flex items-center gap-4 lg:flex-col lg:items-start"
                        >
                            <div class="relative size-28 shrink-0">
                                <svg
                                    viewBox="0 0 120 120"
                                    class="size-28 -rotate-90"
                                >
                                    <circle
                                        cx="60"
                                        cy="60"
                                        r="52"
                                        fill="none"
                                        stroke="currentColor"
                                        stroke-width="10"
                                        class="text-muted"
                                    />
                                    <circle
                                        cx="60"
                                        cy="60"
                                        r="52"
                                        fill="none"
                                        stroke="currentColor"
                                        stroke-width="10"
                                        stroke-linecap="round"
                                        :stroke-dasharray="ring.length"
                                        :stroke-dashoffset="ringOffset(card)"
                                        :class="
                                            card.days_left !== null &&
                                            card.days_left <= 30
                                                ? 'text-clay'
                                                : 'text-paddy'
                                        "
                                    />
                                </svg>
                                <div
                                    class="absolute inset-0 flex flex-col items-center justify-center text-center"
                                >
                                    <span
                                        class="num font-display text-3xl leading-none"
                                        >{{
                                            card.days_left !== null
                                                ? Math.abs(card.days_left)
                                                : '—'
                                        }}</span
                                    >
                                    <span
                                        class="mt-1 text-[11px] leading-normal text-muted-foreground"
                                        >{{
                                            card.days_left !== null &&
                                            card.days_left < 0
                                                ? t('দিন পার')
                                                : t('দিন বাকি')
                                        }}</span
                                    >
                                </div>
                            </div>
                            <div class="min-w-0">
                                <Link
                                    :href="`/animals/${card.animal_id}`"
                                    class="block truncate py-0.5 font-display text-xl leading-snug text-primary underline decoration-primary/40 underline-offset-4 hover:decoration-primary"
                                    >{{ cowName(card) }}</Link
                                >
                                <p
                                    class="mt-0.5 text-sm leading-normal text-foreground/80"
                                >
                                    {{ monthsParts[0] }}
                                    <span class="num font-medium">{{
                                        card.months
                                    }}</span>
                                    {{ monthsParts[1] }}
                                    <span
                                        v-if="card.estimated"
                                        class="text-muted-foreground"
                                        >{{ t('(আনুমানিক)') }}</span
                                    >
                                </p>
                                <span
                                    :class="[
                                        'mt-2 inline-block rounded-full px-2.5 py-1 text-xs font-medium',
                                        card.result === 'pregnant'
                                            ? 'bg-paddy/12 text-paddy'
                                            : 'bg-turmeric/25 text-soil',
                                    ]"
                                    >{{ t(card.result_label) }}</span
                                >
                                <p
                                    class="mt-2 flex items-center gap-1.5 text-sm leading-normal"
                                >
                                    <CalendarClock class="size-4 text-pond" />
                                    {{ t('সম্ভাব্য প্রসব:') }}
                                    <span class="num font-semibold">{{
                                        day(card.expected_on)
                                    }}</span>
                                </p>
                            </div>
                        </div>

                        <!-- The road to calving -->
                        <ol
                            class="relative space-y-3 lg:border-l lg:border-border lg:pl-5"
                        >
                            <li
                                v-for="step in milestones(card)"
                                :key="step.key"
                                class="flex items-start gap-3"
                            >
                                <span
                                    :class="[
                                        'mt-0.5 flex size-6 shrink-0 items-center justify-center rounded-full border',
                                        step.done
                                            ? 'border-paddy bg-paddy text-primary-foreground'
                                            : step.next
                                              ? 'border-clay bg-clay/10 text-clay'
                                              : 'border-border bg-card text-muted-foreground',
                                    ]"
                                >
                                    <Check v-if="step.done" class="size-3.5" />
                                    <span
                                        v-else
                                        class="size-1.5 rounded-full bg-current"
                                    />
                                </span>
                                <span class="min-w-0">
                                    <span
                                        :class="[
                                            'block text-sm leading-normal font-medium',
                                            step.next && 'text-clay',
                                        ]"
                                        >{{ step.title }}</span
                                    >
                                    <span
                                        class="num block text-xs leading-normal text-muted-foreground"
                                        >{{ step.when }}</span
                                    >
                                </span>
                            </li>
                        </ol>

                        <!-- What to do now -->
                        <div class="min-w-0">
                            <div
                                v-if="card.stage"
                                class="rounded-xl border border-paddy/25 bg-paddy/5 p-3"
                            >
                                <p
                                    class="text-sm leading-normal font-medium text-paddy"
                                >
                                    {{
                                        t('এখন যা দরকার — :stage', {
                                            stage: card.stage.title,
                                        })
                                    }}
                                </p>
                                <ul class="mt-2 space-y-1.5">
                                    <li
                                        v-for="(task, i) in card.stage.tasks"
                                        :key="i"
                                        class="flex gap-2 text-sm leading-normal"
                                    >
                                        <span
                                            class="mt-1 size-1.5 shrink-0 rounded-full bg-paddy"
                                        />
                                        <span>{{ task }}</span>
                                    </li>
                                </ul>
                            </div>
                            <p
                                class="mt-2 text-xs leading-normal text-muted-foreground"
                            >
                                {{ t(card.method)
                                }}<template v-if="card.sire">
                                    ·
                                    {{
                                        t('ষাঁড়/বীজ: :sire', {
                                            sire: card.sire,
                                        })
                                    }}</template
                                ><template v-if="card.by_whom">
                                    ·
                                    {{
                                        t('দিয়েছেন: :name', {
                                            name: card.by_whom,
                                        })
                                    }}</template
                                >
                            </p>
                            <div class="mt-4 flex flex-wrap gap-2">
                                <template v-if="card.result === 'pending'">
                                    <Button
                                        size="sm"
                                        :disabled="busy === card.id"
                                        @click="check(card, 'pregnant')"
                                    >
                                        <HeartPulse class="size-4" />
                                        {{ t('গর্ভ হয়েছে') }}
                                    </Button>
                                    <Button
                                        size="sm"
                                        variant="outline"
                                        :disabled="busy === card.id"
                                        @click="check(card, 'empty')"
                                    >
                                        <X class="size-4" />
                                        {{ t('গর্ভ হয়নি') }}
                                    </Button>
                                </template>
                                <Button
                                    size="sm"
                                    :variant="
                                        calvingFor === card.id
                                            ? 'secondary'
                                            : 'outline'
                                    "
                                    @click="openCalving(card)"
                                >
                                    <Baby class="size-4" />
                                    {{ t('বাচ্চা হয়েছে') }}
                                </Button>
                            </div>

                            <form
                                v-if="calvingFor === card.id"
                                class="mt-3 grid gap-3 rounded-lg border border-border bg-background/60 p-3"
                                @submit.prevent="saveCalving(card)"
                            >
                                <div class="grid grid-cols-2 gap-3">
                                    <Field
                                        :label="t('তারিখ')"
                                        :error="calving.errors.calved_on"
                                    >
                                        <DateField
                                            v-model="calving.calved_on"
                                            :max="today()"
                                            class="h-10"
                                        />
                                    </Field>
                                    <Field
                                        :label="t('কী হয়েছে')"
                                        :error="calving.errors.outcome"
                                    >
                                        <NSelect v-model="calving.outcome">
                                            <option
                                                v-for="(label, key) in outcomes"
                                                :key="key"
                                                :value="key"
                                            >
                                                {{ label }}
                                            </option>
                                        </NSelect>
                                    </Field>
                                </div>
                                <Field
                                    :label="t('নোট (ইচ্ছামতো)')"
                                    :error="calving.errors.notes"
                                >
                                    <Input
                                        v-model="calving.notes"
                                        class="h-10"
                                        :placeholder="
                                            t('যেমন: সহজে প্রসব, ফুল পড়েছে')
                                        "
                                    />
                                </Field>
                                <div class="flex gap-2">
                                    <Button
                                        type="submit"
                                        size="sm"
                                        :disabled="calving.processing"
                                        >{{ t('রাখুন') }}</Button
                                    >
                                    <Button
                                        type="button"
                                        size="sm"
                                        variant="secondary"
                                        @click="calvingFor = null"
                                        >{{ t('বাদ দিন') }}</Button
                                    >
                                </div>
                            </form>
                        </div>
                    </div>
                </article>
            </div>
        </section>

        <div class="mt-6 grid gap-6 lg:grid-cols-[minmax(0,3fr)_minmax(0,2fr)]">
            <!-- Waiting for service -->
            <section class="rounded-xl border border-border bg-card p-4">
                <h2 class="font-display text-lg">
                    {{ t('বীজ দেওয়ার অপেক্ষায়') }}
                </h2>
                <p class="mt-1 text-xs text-muted-foreground">
                    {{
                        t(
                            '২১ দিন পরপর গরম আসে; গরম দেখার ১২–১৮ ঘণ্টার মধ্যে বীজ দিলে ভালো। গাভীর পাতায় বীজ দেওয়ার তারিখ লিখুন।',
                        )
                    }}
                </p>

                <ul
                    v-if="waiting.length"
                    class="mt-3 grid gap-2 sm:grid-cols-2"
                >
                    <li v-for="cow in waiting" :key="cow.id">
                        <Link
                            :href="`/animals/${cow.id}`"
                            class="flex items-center gap-3 rounded-xl border border-border bg-background/60 p-3 transition-colors hover:border-primary/50 active:bg-accent"
                        >
                            <span
                                class="flex size-10 shrink-0 items-center justify-center rounded-full bg-clay/12 text-clay"
                            >
                                <HeartPulse class="size-5" />
                            </span>
                            <span class="min-w-0 flex-1">
                                <span
                                    class="block truncate py-px text-sm leading-normal font-semibold"
                                    >{{ cowName(cow) }}</span
                                >
                                <span
                                    class="block truncate py-px text-xs leading-normal text-muted-foreground"
                                    >{{
                                        cow.last_calved
                                            ? t('শেষ বাচ্চা :date', {
                                                  date: day(cow.last_calved),
                                              })
                                            : t(cow.center)
                                    }}</span
                                >
                            </span>
                            <span class="btn-chip-primary">{{
                                t('বীজ দিন')
                            }}</span>
                        </Link>
                    </li>
                </ul>
                <p
                    v-else
                    class="py-8 text-center text-sm text-muted-foreground"
                >
                    {{ t('অপেক্ষায় কোনো গাভী নেই।') }}
                </p>
            </section>

            <!-- Recent calvings -->
            <section class="rounded-xl border border-border bg-card p-4">
                <h2 class="font-display text-lg">
                    {{ t('সাম্প্রতিক বাচ্চা') }}
                </h2>

                <ul v-if="recent.length" class="mt-3 divide-y divide-border/70">
                    <li
                        v-for="r in recent"
                        :key="r.id"
                        class="flex flex-wrap items-center justify-between gap-x-3 gap-y-2 py-2.5"
                    >
                        <div class="min-w-0">
                            <Link
                                :href="`/animals/${r.animal_id}`"
                                class="block truncate py-0.5 text-sm font-medium text-primary underline decoration-primary/40 underline-offset-4 hover:decoration-primary"
                                >{{ cowName(r) }}</Link
                            >
                            <p class="text-xs text-muted-foreground">
                                <span class="num">{{ day(r.calved_on) }}</span>
                                · {{ outcomeLabel(r.outcome) }}
                            </p>
                        </div>
                        <Link
                            v-if="r.calf_id"
                            :href="`/animals/${r.calf_id}`"
                            class="btn-chip"
                            ><Eye /> {{ t('বাছুর দেখুন') }}</Link
                        >
                        <Link
                            v-else-if="
                                r.outcome && liveOutcomes.includes(r.outcome)
                            "
                            :href="`/animals/create?mother=${r.animal_id}`"
                            class="btn-chip-primary"
                        >
                            <Plus />
                            {{ t('বাছুরটি তালিকায় যোগ করুন') }}
                        </Link>
                    </li>
                </ul>
                <p
                    v-else
                    class="mt-3 flex items-center gap-2 rounded-lg bg-muted/50 px-3 py-3 text-sm leading-normal text-muted-foreground"
                >
                    <Baby class="size-4 shrink-0" />
                    {{
                        t(
                            'এখনো কোনো বাচ্চা হওয়ার হিসাব নেই। বাচ্চা হলে গর্ভের কার্ডে “বাচ্চা হয়েছে” চাপুন।',
                        )
                    }}
                </p>
            </section>
        </div>

        <!-- Guides -->
        <div class="mt-6 space-y-3">
            <details
                v-for="g in [
                    {
                        title: t('পুরো গাইড — গর্ভের ২৮৩ দিন'),
                        stages: guide,
                        unit: t('বীজ দেওয়ার পর'),
                    },
                    {
                        title: t('বাছুরের যত্ন — বয়স অনুযায়ী'),
                        stages: calfGuide,
                        unit: t('বয়স'),
                    },
                ]"
                :key="g.title"
                class="group rounded-xl border border-border bg-card"
            >
                <summary
                    class="flex cursor-pointer list-none items-center justify-between gap-3 px-4 py-3 [&::-webkit-details-marker]:hidden"
                >
                    <span class="font-display text-base">{{ g.title }}</span>
                    <ChevronDown
                        class="size-4 shrink-0 text-muted-foreground transition-transform group-open:rotate-180"
                    />
                </summary>
                <div class="border-t border-border px-4 py-3">
                    <ol v-if="g.stages.length" class="space-y-4">
                        <li
                            v-for="s in g.stages"
                            :key="`${s.from}-${s.to}`"
                            class="relative border-l-2 border-paddy/30 pl-4"
                        >
                            <span
                                class="absolute top-1 -left-[5px] size-2 rounded-full bg-paddy"
                            />
                            <p class="text-xs text-muted-foreground">
                                {{ g.unit }}
                                <span class="num">{{ range(s) }}</span>
                            </p>
                            <p class="text-sm font-medium">{{ s.title }}</p>
                            <ul
                                class="mt-1 list-disc space-y-0.5 pl-4 text-sm text-foreground/85 marker:text-paddy/60"
                            >
                                <li v-for="(task, i) in s.tasks" :key="i">
                                    {{ task }}
                                </li>
                            </ul>
                        </li>
                    </ol>
                    <p v-else class="py-4 text-sm text-muted-foreground">
                        {{ t('গাইড পাওয়া যায়নি।') }}
                    </p>
                </div>
            </details>
            <p class="px-1 text-xs text-muted-foreground">
                {{
                    t(
                        'ঔষধ, মাত্রা ও টিকার তারিখ অবশ্যই ডাক্তারের পরামর্শে ঠিক করুন।',
                    )
                }}
            </p>
        </div>
    </div>
</template>
