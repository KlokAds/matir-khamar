<script setup lang="ts">
import { Head, Link, router, useForm } from '@inertiajs/vue3';
import {
    Baby,
    CalendarClock,
    Check,
    ChevronDown,
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
import { Input } from '@/components/ui/input';
import { ask } from '@/lib/dialog';
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
    female: 'বকনা বাছুর',
    male: 'এঁড়ে বাছুর',
    twins: 'যমজ',
    stillborn: 'মৃত বাচ্চা',
    aborted: 'গর্ভপাত',
};
const liveOutcomes = ['female', 'male', 'twins'];

const markers = [
    { at: 90, label: '৩ মাস', row: 0 },
    { at: 180, label: '৬ মাস', row: 1 },
    { at: 210, label: '৭ মাস · দুধ বন্ধ', row: 0, key: true },
    { at: 270, label: '৯ মাস', row: 1 },
].map((m) => ({ ...m, pct: (m.at / GESTATION) * 100 }));

const stats = computed(() => ({
    pregnant: props.open.filter((c) => c.result === 'pregnant' && c.open)
        .length,
    pending: props.open.filter((c) => c.result === 'pending').length,
    soon: props.open.filter((c) => c.days_left !== null && c.days_left <= 30)
        .length,
}));

const cowName = (c: { tag: string | null; name: string | null }) =>
    [c.tag, c.name].filter(Boolean).join(' · ') || 'নাম নেই';

const leftText = (n: number | null) =>
    n === null ? '' : n >= 0 ? `আর ${n} দিন` : `${Math.abs(n)} দিন পার`;

const outcomeLabel = (key: string | null) =>
    key ? (outcomes[key] ?? key) : '—';

const range = (s: Stage) => `${s.from}–${s.to} দিন`;

const busy = ref<number | null>(null);

async function check(card: BreedingCard, result: 'pregnant' | 'empty') {
    if (
        result === 'empty' &&
        !(await ask.confirm({
            title: 'গর্ভ হয়নি?',
            body: `${cowName(card)} — এই বীজ দেওয়াটি "গর্ভ হয়নি" হিসেবে বন্ধ হবে। আবার গরম এলে নতুন করে বীজ দিন।`,
            confirmText: 'হ্যাঁ, গর্ভ হয়নি',
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
    <Head title="গর্ভ ও বাছুর" />

    <div class="flex flex-1 flex-col p-4 md:p-6">
        <PageTitle
            title="গর্ভ ও বাছুর"
            subtitle="কোন গাভী কতদিনের গর্ভবতী, কবে বাচ্চা হবে, আর এখন কী যত্ন দরকার — এক নজরে।"
        />

        <div class="grid grid-cols-1 gap-3 sm:grid-cols-3">
            <StatTile
                label="গর্ভবতী"
                :value="String(stats.pregnant)"
                hint="নিশ্চিত গর্ভ"
                :icon="HeartPulse"
                tone="paddy"
            />
            <StatTile
                label="পরীক্ষা বাকি"
                :value="String(stats.pending)"
                hint="বীজ দেওয়া হয়েছে, এখনো দেখা হয়নি"
                :icon="Hourglass"
                tone="turmeric"
            />
            <StatTile
                label="৩০ দিনের মধ্যে বাচ্চা"
                :value="String(stats.soon)"
                hint="প্রসবের প্রস্তুতি নিন"
                :icon="Baby"
                tone="clay"
            />
        </div>

        <!-- Open pregnancies -->
        <section class="mt-6">
            <h2 class="font-display mb-3 text-lg">চলমান গর্ভ</h2>

            <div
                v-if="!open.length"
                class="rounded-xl border border-dashed border-border bg-card/60 px-4 py-10 text-center text-sm text-muted-foreground"
            >
                এখন কোনো গাভী গর্ভবতী বা পরীক্ষার অপেক্ষায় নেই। গাভীর পাতায়
                গিয়ে বীজ দেওয়ার তারিখ লিখুন।
            </div>

            <div v-else class="grid gap-4 lg:grid-cols-2">
                <article
                    v-for="card in open"
                    :key="card.id"
                    class="rounded-xl border border-border bg-card p-4 shadow-[0_1px_0_0_var(--border)]"
                >
                    <header class="flex items-start justify-between gap-3">
                        <div class="min-w-0">
                            <Link
                                :href="`/animals/${card.animal_id}`"
                                class="font-display block truncate text-lg leading-tight hover:underline"
                                >{{ cowName(card) }}</Link
                            >
                            <p class="mt-0.5 text-sm text-foreground/80">
                                গর্ভের
                                <span class="num font-medium">{{
                                    card.months
                                }}</span>
                                চলছে
                                <span
                                    v-if="card.estimated"
                                    class="text-muted-foreground"
                                    >(আনুমানিক)</span
                                >
                            </p>
                        </div>
                        <span
                            :class="[
                                'shrink-0 rounded-full px-2.5 py-1 text-xs font-medium',
                                card.result === 'pregnant'
                                    ? 'bg-paddy/12 text-paddy'
                                    : 'bg-turmeric/25 text-soil',
                            ]"
                            >{{ card.result_label }}</span
                        >
                    </header>

                    <!-- Progress through 283 days -->
                    <div class="mt-4">
                        <div class="relative h-4 text-[10px]">
                            <span
                                v-for="m in markers.filter((x) => x.row === 1)"
                                :key="m.at"
                                class="absolute bottom-0 -translate-x-1/2 whitespace-nowrap text-muted-foreground"
                                :class="m.pct > 90 && '-translate-x-full!'"
                                :style="{ left: `${m.pct}%` }"
                                >{{ m.label }}</span
                            >
                        </div>
                        <div
                            class="relative mt-0.5 h-3 overflow-hidden rounded-full bg-muted"
                        >
                            <div
                                class="h-full rounded-full bg-linear-to-r from-paddy/70 to-paddy transition-[width]"
                                :style="{
                                    width: `${Math.min(100, Math.max(0, card.progress))}%`,
                                }"
                            />
                            <span
                                v-for="m in markers"
                                :key="m.at"
                                :class="[
                                    'absolute inset-y-0 w-px',
                                    m.key ? 'bg-clay' : 'bg-background/80',
                                ]"
                                :style="{ left: `${m.pct}%` }"
                            />
                        </div>
                        <div class="relative mt-0.5 h-4 text-[10px]">
                            <span
                                v-for="m in markers.filter((x) => x.row === 0)"
                                :key="m.at"
                                :class="[
                                    'absolute top-0 -translate-x-1/2 whitespace-nowrap',
                                    m.key
                                        ? 'font-medium text-clay'
                                        : 'text-muted-foreground',
                                ]"
                                :style="{ left: `${m.pct}%` }"
                                >{{ m.label }}</span
                            >
                        </div>
                    </div>

                    <div
                        class="mt-3 flex flex-wrap items-center justify-between gap-2 rounded-lg bg-pond/8 px-3 py-2"
                    >
                        <span class="flex items-center gap-1.5 text-sm">
                            <CalendarClock class="size-4 text-pond" />
                            সম্ভাব্য প্রসব:
                            <span class="num font-medium">{{
                                day(card.expected_on)
                            }}</span>
                        </span>
                        <span
                            v-if="card.days_left !== null"
                            :class="[
                                'num text-sm font-semibold',
                                card.days_left < 0
                                    ? 'text-expense'
                                    : card.days_left <= 30
                                      ? 'text-clay'
                                      : 'text-foreground',
                            ]"
                            >{{ leftText(card.days_left) }}</span
                        >
                    </div>

                    <p class="mt-2 text-xs text-muted-foreground">
                        বীজ:
                        <span class="num">{{ day(card.served_on) }}</span> ·
                        {{ card.method }}
                        <template v-if="card.sire">
                            · ষাঁড়/বীজ: {{ card.sire }}</template
                        >
                        <template v-if="card.by_whom">
                            · দিয়েছেন: {{ card.by_whom }}</template
                        >
                        <template v-if="card.checked_on">
                            · পরীক্ষা:
                            <span class="num">{{
                                day(card.checked_on)
                            }}</span></template
                        >
                    </p>

                    <div
                        v-if="card.stage"
                        class="mt-3 rounded-lg border border-paddy/25 bg-paddy/5 p-3"
                    >
                        <p class="text-sm font-medium text-paddy">
                            এখন যা দরকার — {{ card.stage.title }}
                        </p>
                        <ul class="mt-2 space-y-1.5">
                            <li
                                v-for="(task, i) in card.stage.tasks"
                                :key="i"
                                class="flex gap-2 text-sm leading-snug"
                            >
                                <span
                                    class="mt-0.5 flex size-4 shrink-0 items-center justify-center rounded border border-paddy/50 bg-card"
                                >
                                    <Check class="size-3 text-paddy/60" />
                                </span>
                                <span>{{ task }}</span>
                            </li>
                        </ul>
                    </div>

                    <div class="mt-4 flex flex-wrap gap-2">
                        <template v-if="card.result === 'pending'">
                            <Button
                                size="sm"
                                :disabled="busy === card.id"
                                @click="check(card, 'pregnant')"
                            >
                                <HeartPulse class="size-4" />
                                গর্ভ হয়েছে
                            </Button>
                            <Button
                                size="sm"
                                variant="outline"
                                :disabled="busy === card.id"
                                @click="check(card, 'empty')"
                            >
                                <X class="size-4" />
                                গর্ভ হয়নি
                            </Button>
                        </template>
                        <Button
                            size="sm"
                            :variant="
                                calvingFor === card.id ? 'secondary' : 'outline'
                            "
                            @click="openCalving(card)"
                        >
                            <Baby class="size-4" />
                            বাচ্চা হয়েছে
                        </Button>
                    </div>

                    <form
                        v-if="calvingFor === card.id"
                        class="mt-3 grid gap-3 rounded-lg border border-border bg-background/60 p-3"
                        @submit.prevent="saveCalving(card)"
                    >
                        <div class="grid grid-cols-2 gap-3">
                            <Field
                                label="তারিখ"
                                :error="calving.errors.calved_on"
                            >
                                <Input
                                    v-model="calving.calved_on"
                                    type="date"
                                    :max="today()"
                                    class="h-10"
                                />
                            </Field>
                            <Field
                                label="কী হয়েছে"
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
                            label="নোট (ইচ্ছামতো)"
                            :error="calving.errors.notes"
                        >
                            <Input
                                v-model="calving.notes"
                                class="h-10"
                                placeholder="যেমন: সহজে প্রসব, ফুল পড়েছে"
                            />
                        </Field>
                        <div class="flex gap-2">
                            <Button
                                type="submit"
                                size="sm"
                                :disabled="calving.processing"
                                >রাখুন</Button
                            >
                            <Button
                                type="button"
                                size="sm"
                                variant="ghost"
                                @click="calvingFor = null"
                                >বাদ দিন</Button
                            >
                        </div>
                    </form>
                </article>
            </div>
        </section>

        <div class="mt-6 grid gap-6 lg:grid-cols-2">
            <!-- Waiting for service -->
            <section class="rounded-xl border border-border bg-card p-4">
                <h2 class="font-display text-lg">বীজ দেওয়ার অপেক্ষায়</h2>
                <p class="mt-1 text-xs text-muted-foreground">
                    ২১ দিন পরপর গরম আসে; গরম দেখার ১২–১৮ ঘণ্টার মধ্যে বীজ দিলে
                    ভালো। গাভীর পাতায় বীজ দেওয়ার তারিখ লিখুন।
                </p>

                <ul
                    v-if="waiting.length"
                    class="mt-3 divide-y divide-border/70"
                >
                    <li v-for="cow in waiting" :key="cow.id">
                        <Link
                            :href="`/animals/${cow.id}`"
                            class="-mx-2 flex items-center justify-between gap-3 rounded-md px-2 py-2.5 hover:bg-accent"
                        >
                            <span class="min-w-0">
                                <span
                                    class="block truncate text-sm font-medium"
                                    >{{ cowName(cow) }}</span
                                >
                                <span
                                    class="block truncate text-xs text-muted-foreground"
                                    >{{ cow.center }}</span
                                >
                            </span>
                            <span
                                class="shrink-0 text-right text-xs text-muted-foreground"
                            >
                                <template v-if="cow.last_calved"
                                    >শেষ বাচ্চা<br /><span class="num">{{
                                        day(cow.last_calved)
                                    }}</span></template
                                >
                                <template v-else>বীজ দিন →</template>
                            </span>
                        </Link>
                    </li>
                </ul>
                <p
                    v-else
                    class="py-8 text-center text-sm text-muted-foreground"
                >
                    অপেক্ষায় কোনো গাভী নেই।
                </p>
            </section>

            <!-- Recent calvings -->
            <section class="rounded-xl border border-border bg-card p-4">
                <h2 class="font-display text-lg">সাম্প্রতিক বাচ্চা</h2>

                <ul v-if="recent.length" class="mt-3 divide-y divide-border/70">
                    <li
                        v-for="r in recent"
                        :key="r.id"
                        class="flex items-start justify-between gap-3 py-2.5"
                    >
                        <div class="min-w-0">
                            <Link
                                :href="`/animals/${r.animal_id}`"
                                class="block truncate text-sm font-medium hover:underline"
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
                            class="shrink-0 rounded-full bg-paddy/12 px-2.5 py-1 text-xs font-medium text-paddy hover:bg-paddy/20"
                            >বাছুর দেখুন</Link
                        >
                        <Link
                            v-else-if="
                                r.outcome && liveOutcomes.includes(r.outcome)
                            "
                            :href="`/animals/create?mother=${r.animal_id}`"
                            class="flex shrink-0 items-center gap-1 rounded-full bg-turmeric/25 px-2.5 py-1 text-xs font-medium text-soil hover:bg-turmeric/40"
                        >
                            <Plus class="size-3.5" />
                            বাছুরটি তালিকায় যোগ করুন
                        </Link>
                    </li>
                </ul>
                <p
                    v-else
                    class="py-8 text-center text-sm text-muted-foreground"
                >
                    এখনো কোনো বাচ্চা হওয়ার হিসাব নেই।
                </p>
            </section>
        </div>

        <!-- Guides -->
        <div class="mt-6 space-y-3">
            <details
                v-for="g in [
                    {
                        title: 'পুরো গাইড — গর্ভের ২৮৩ দিন',
                        stages: guide,
                        unit: 'বীজ দেওয়ার পর',
                    },
                    {
                        title: 'বাছুরের যত্ন — বয়স অনুযায়ী',
                        stages: calfGuide,
                        unit: 'বয়স',
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
                                <li v-for="(t, i) in s.tasks" :key="i">
                                    {{ t }}
                                </li>
                            </ul>
                        </li>
                    </ol>
                    <p v-else class="py-4 text-sm text-muted-foreground">
                        গাইড পাওয়া যায়নি।
                    </p>
                </div>
            </details>
            <p class="px-1 text-xs text-muted-foreground">
                ঔষধ, মাত্রা ও টিকার তারিখ অবশ্যই ডাক্তারের পরামর্শে ঠিক করুন।
            </p>
        </div>
    </div>
</template>
