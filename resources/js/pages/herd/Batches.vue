<script setup lang="ts">
import { Head, Link, router, useForm } from '@inertiajs/vue3';
import {
    BookOpen,
    CalendarDays,
    Flag,
    Pencil,
    Plus,
    Scale,
    Sprout,
} from '@lucide/vue';
import { reactive, ref } from 'vue';
import Field from '@/components/khamar/Field.vue';
import NSelect from '@/components/khamar/NSelect.vue';
import PageTitle from '@/components/khamar/PageTitle.vue';
import { Button } from '@/components/ui/button';
import DateField from '@/components/khamar/DateField.vue';
import { Input } from '@/components/ui/input';
import { ask } from '@/lib/dialog';
import { t } from '@/lib/i18n';
import { centerLabel, day, taka, today, useCan } from '@/lib/khamar';
import type { Center } from '@/lib/khamar';

defineOptions({
    layout: {
        breadcrumbs: [{ title: 'মোটাতাজা ব্যাচ', href: '/batches' }],
    },
});

type Stage = { from: number; to: number; title: string; tasks: string[] };

type BatchAnimal = {
    id: number;
    tag: string;
    name: string | null;
    status: string;
    purchase_cost: number;
    first_kg: number | null;
    last_kg: number | null;
    last_on: string | null;
    per_day: number | null;
};

type BatchCard = {
    id: number;
    label: string;
    number: number;
    center: string;
    cost_center_id: number;
    started_on: string;
    closed_on: string | null;
    days: number;
    span: string;
    target_days: number;
    target_on: string;
    target_gain_kg: number | null;
    progress: number;
    notes: string | null;
    animals: BatchAnimal[];
    head: number;
    avg_gain: number | null;
    cost: { purchase: number; direct: number; shared: number; sale: number };
    spent: number;
    profit: number | null;
    stage: Stage | null;
};

const props = defineProps<{
    batches: BatchCard[];
    centers: Center[];
    defaultCenterId: number | null;
    guide: Stage[];
}>();

const can = useCan();

const statusLabels: Record<string, string> = {
    sold: t('বিক্রি হয়েছে'),
    dead: t('মৃত'),
};

const kg = (value: number | null | undefined): string =>
    value === null || value === undefined
        ? '—'
        : t(':n কেজি', {
              n: value.toLocaleString('en-IN', { maximumFractionDigits: 2 }),
          });

const perHead = (batch: BatchCard): number =>
    batch.head > 0 ? Math.round(batch.spent / batch.head) : 0;

const gainOk = (batch: BatchCard): boolean =>
    batch.avg_gain !== null &&
    batch.target_gain_kg !== null &&
    batch.avg_gain >= batch.target_gain_kg;

/** The saved custom name, or empty when the batch only carries its number. */
const customName = (batch: BatchCard): string =>
    batch.label === `ব্যাচ-${batch.number}` ? '' : batch.label;

/** What the batch is called on screen: its custom name, or "ব্যাচ-N" in the reader's language. */
const batchName = (batch: BatchCard): string =>
    customName(batch) || t('ব্যাচ-:n', { n: batch.number });

const toNumberOrNull = (value: string | number): number | null =>
    value === '' || value === null ? null : Number(value);

// "দিনে গড়ে X বাড়ছে" as one phrase; the amount keeps its own styled span.
const gainParts = t('দিনে গড়ে :kg বাড়ছে')
    .split(':kg')
    .map((part) => part.trim());

// New batch
const adding = ref(false);
const batch = useForm({
    cost_center_id: props.defaultCenterId ?? props.centers[0]?.id ?? null,
    name: '',
    started_on: today(),
    target_days: 120 as string | number,
    target_gain_kg: '' as string | number,
    notes: '',
});

const create = () =>
    batch
        .transform((data) => ({
            ...data,
            name: data.name.trim() || null,
            target_gain_kg: toNumberOrNull(data.target_gain_kg),
            notes: data.notes.trim() || null,
        }))
        .post('/batches', {
            preserveScroll: true,
            onSuccess: () => {
                adding.value = false;
                batch.reset();
            },
        });

// Edit and close
const editingId = ref<number | null>(null);
const busy = ref(false);
const edit = reactive({
    name: '',
    started_on: '',
    target_days: 120 as string | number,
    target_gain_kg: '' as string | number,
    notes: '',
});
const editErrors = ref<Record<string, string>>({});

function startEdit(card: BatchCard) {
    editingId.value = card.id;
    editErrors.value = {};
    Object.assign(edit, {
        name: customName(card),
        started_on: card.started_on.slice(0, 10),
        target_days: card.target_days,
        target_gain_kg: card.target_gain_kg ?? '',
        notes: card.notes ?? '',
    });
}

function send(card: BatchCard, payload: Record<string, unknown>) {
    busy.value = true;
    router.put(`/batches/${card.id}`, payload as never, {
        preserveScroll: true,
        onSuccess: () => {
            editingId.value = null;
        },
        onError: (errors) => {
            editErrors.value = errors;
        },
        onFinish: () => {
            busy.value = false;
        },
    });
}

const saveEdit = (card: BatchCard) =>
    send(card, {
        name: edit.name.trim() || null,
        started_on: edit.started_on,
        target_days: Number(edit.target_days),
        target_gain_kg: toNumberOrNull(edit.target_gain_kg),
        notes: edit.notes.trim() || null,
        closed_on: card.closed_on,
    });

async function closeBatch(card: BatchCard) {
    const ok = await ask.confirm({
        title: t(':batch শেষ করবেন?', { batch: batchName(card) }),
        body: t(
            'আজকের তারিখে এই দফা বন্ধ হবে। পরে সম্পাদনা থেকে আবার দেখা যাবে।',
        ),
        confirmText: t('হ্যাঁ, শেষ করুন'),
    });

    if (!ok) {
        return;
    }

    send(card, {
        name: customName(card) || null,
        started_on: card.started_on.slice(0, 10),
        target_days: card.target_days,
        target_gain_kg: card.target_gain_kg,
        notes: card.notes,
        closed_on: today(),
    });
}
</script>

<template>
    <Head :title="t('মোটাতাজা ব্যাচ')" />

    <div class="flex flex-1 flex-col p-4 md:p-6">
        <PageTitle
            :title="t('মোটাতাজা ব্যাচ')"
            :subtitle="
                t(
                    'প্রতিটি দফা আলাদা নম্বরে — কবে শুরু, কত দিন চলছে, কত খরচ, কত বাড়লো।',
                )
            "
        >
            <template #actions>
                <Button v-if="can['post-entries']" @click="adding = !adding"
                    ><Plus /> {{ t('নতুন ব্যাচ') }}</Button
                >
            </template>
        </PageTitle>

        <form
            v-if="adding"
            class="mb-5 grid gap-3 rounded-xl border border-border bg-card p-4 sm:grid-cols-2"
            @submit.prevent="create"
        >
            <p class="text-sm text-muted-foreground sm:col-span-2">
                {{
                    t(
                        'নতুন দফা খুলুন। গরু নিবন্ধনের সময় ব্যাচে যুক্ত হয় — নতুন গরু ফর্মে ব্যাচ বেছে নিন।',
                    )
                }}
            </p>
            <Field :label="t('ক্যাটাগরি')" :error="batch.errors.cost_center_id"
                ><NSelect v-model="batch.cost_center_id"
                    ><option v-for="c in centers" :key="c.id" :value="c.id">
                        {{ centerLabel({ ...c, name: t(c.name) }) }}
                    </option></NSelect
                ></Field
            >
            <Field
                :label="t('নাম (ইচ্ছা হলে)')"
                :error="batch.errors.name"
                :hint="t('না দিলে নম্বর দিয়ে চেনা যাবে')"
                ><Input
                    v-model="batch.name"
                    :placeholder="t('যেমন: কোরবানি ২০২৭')"
            /></Field>
            <Field :label="t('শুরুর তারিখ')" :error="batch.errors.started_on"
                ><DateField v-model="batch.started_on" required
            /></Field>
            <Field
                :label="t('লক্ষ্য (দিন)')"
                :error="batch.errors.target_days"
                :hint="t('কত দিনে বিক্রির উপযোগী হবে')"
                ><Input
                    v-model="batch.target_days"
                    type="number"
                    min="7"
                    max="730"
                    required
            /></Field>
            <Field
                :label="t('দৈনিক ওজন বৃদ্ধির লক্ষ্য (কেজি)')"
                :error="batch.errors.target_gain_kg"
                :hint="t('প্রতি গরু দিনে কত কেজি বাড়বে, যেমন 0.8')"
                ><Input
                    v-model="batch.target_gain_kg"
                    type="number"
                    step="0.01"
                    min="0"
                    inputmode="decimal"
            /></Field>
            <Field :label="t('নোট')" :error="batch.errors.notes"
                ><Input
                    v-model="batch.notes"
                    :placeholder="t('যেমন: হাট থেকে কেনা')"
            /></Field>
            <div class="flex gap-2 sm:col-span-2 sm:justify-end">
                <Button type="submit" :disabled="batch.processing">{{
                    t('ব্যাচ খুলুন')
                }}</Button>
                <Button type="button" variant="ghost" @click="adding = false">{{
                    t('বাদ')
                }}</Button>
            </div>
        </form>

        <div
            v-if="!batches.length"
            class="mb-6 flex flex-col items-center rounded-xl border border-dashed border-border bg-card px-4 py-10 text-center"
        >
            <span class="mb-3 rounded-full bg-paddy/12 p-3 text-paddy"
                ><Sprout class="size-6"
            /></span>
            <p class="font-display text-lg">{{ t('এখনো কোনো ব্যাচ নেই') }}</p>
            <p class="mt-1 max-w-sm text-sm text-muted-foreground">
                {{
                    t(
                        'প্রথম দফা খুলুন, তারপর নতুন গরু নিবন্ধনের সময় সেই ব্যাচ বেছে নিন। খরচ, ওজন আর লাভ এখানে নিজে থেকেই জমবে।',
                    )
                }}
            </p>
        </div>

        <div class="mb-6 grid gap-4 lg:grid-cols-2">
            <article
                v-for="b in batches"
                :key="b.id"
                :class="[
                    'min-w-0 rounded-xl border bg-card p-4',
                    b.closed_on ? 'border-border' : 'border-paddy/35',
                ]"
            >
                <header class="flex items-start justify-between gap-3">
                    <div class="min-w-0">
                        <h2 class="truncate font-display text-xl leading-tight">
                            {{ batchName(b) }}
                        </h2>
                        <p class="truncate text-sm text-muted-foreground">
                            {{ t(b.center) }}
                        </p>
                    </div>
                    <span
                        :class="[
                            'shrink-0 rounded-full px-2.5 py-0.5 text-xs font-medium',
                            b.closed_on
                                ? 'bg-soil/10 text-soil'
                                : 'bg-paddy/12 text-paddy',
                        ]"
                        >{{ b.closed_on ? t('শেষ') : t('চলছে') }}</span
                    >
                </header>

                <div class="mt-4">
                    <p class="font-display text-2xl">
                        {{
                            b.closed_on
                                ? t(':span ছিল', { span: b.span })
                                : t(':span চলছে', { span: b.span })
                        }}
                    </p>
                    <div
                        class="mt-2 h-2 overflow-hidden rounded-full bg-muted"
                        role="progressbar"
                        :aria-valuenow="b.progress"
                        aria-valuemin="0"
                        aria-valuemax="100"
                    >
                        <div
                            :class="[
                                'h-full rounded-full',
                                b.progress >= 100 ? 'bg-clay' : 'bg-paddy',
                            ]"
                            :style="{
                                width: `${Math.min(100, Math.max(0, b.progress))}%`,
                            }"
                        />
                    </div>
                    <p
                        class="mt-1.5 flex flex-wrap justify-between gap-x-3 text-xs text-muted-foreground"
                    >
                        <span class="inline-flex items-center gap-1"
                            ><CalendarDays class="size-3.5" />
                            {{ t('শুরু :date', { date: day(b.started_on) }) }}
                            <template v-if="b.closed_on">
                                ·
                                {{
                                    t('শেষ :date', { date: day(b.closed_on) })
                                }}</template
                            ></span
                        >
                        <span>{{
                            t('লক্ষ্য: :date (:n দিন)', {
                                date: day(b.target_on),
                                n: b.target_days,
                            })
                        }}</span>
                    </p>
                </div>

                <div class="mt-4 grid grid-cols-2 gap-2">
                    <div class="rounded-lg bg-muted/50 p-3">
                        <p class="text-xs text-muted-foreground">
                            {{ t('গরু') }}
                        </p>
                        <p class="num text-xl font-semibold">
                            {{ t(':nটি', { n: b.head }) }}
                        </p>
                    </div>
                    <div
                        :class="[
                            'rounded-lg p-3',
                            b.avg_gain === null || b.target_gain_kg === null
                                ? 'bg-muted/50'
                                : gainOk(b)
                                  ? 'bg-income/10'
                                  : 'bg-turmeric/25',
                        ]"
                    >
                        <p
                            class="flex items-center gap-1 text-xs text-muted-foreground"
                        >
                            <Scale class="size-3.5" /> {{ t('ওজন বৃদ্ধি') }}
                        </p>
                        <p
                            v-if="b.avg_gain !== null"
                            :class="[
                                'text-sm leading-snug font-medium',
                                b.target_gain_kg !== null &&
                                    (gainOk(b) ? 'text-income' : 'text-soil'),
                            ]"
                        >
                            {{ gainParts[0] }}
                            <span class="num">{{ kg(b.avg_gain) }}</span>
                            {{ gainParts[1] }}
                        </p>
                        <p v-else class="text-sm text-muted-foreground">
                            {{ t('ওজন মাপা হয়নি') }}
                        </p>
                        <p
                            v-if="b.target_gain_kg !== null"
                            class="text-xs text-muted-foreground"
                        >
                            {{ t('লক্ষ্য :kg', { kg: kg(b.target_gain_kg) }) }}
                        </p>
                    </div>
                </div>

                <dl class="mt-4 grid gap-1.5 text-sm">
                    <div class="flex justify-between gap-3">
                        <dt class="text-muted-foreground">{{ t('কেনা') }}</dt>
                        <dd class="num">{{ taka(b.cost.purchase) }}</dd>
                    </div>
                    <div class="flex justify-between gap-3">
                        <dt class="text-muted-foreground">
                            {{ t('সরাসরি খরচ') }}
                        </dt>
                        <dd class="num">{{ taka(b.cost.direct) }}</dd>
                    </div>
                    <div class="flex justify-between gap-3">
                        <dt class="text-muted-foreground">
                            {{ t('ক্যাটাগরির খরচের ভাগ') }}
                            <span class="block text-[11px] leading-tight">{{
                                t('খাবার-ঘাস ইত্যাদি পশু-দিন অনুযায়ী')
                            }}</span>
                        </dt>
                        <dd class="num">{{ taka(b.cost.shared) }}</dd>
                    </div>
                    <div
                        class="flex justify-between gap-3 border-t border-border pt-1.5 font-semibold"
                    >
                        <dt>{{ t('মোট খরচ') }}</dt>
                        <dd class="num text-expense">{{ taka(b.spent) }}</dd>
                    </div>
                    <div
                        v-if="b.head > 0"
                        class="flex justify-between gap-3 text-xs text-muted-foreground"
                    >
                        <dt>{{ t('প্রতি গরু খরচ') }}</dt>
                        <dd class="num">{{ taka(perHead(b)) }}</dd>
                    </div>
                    <div
                        v-if="b.cost.sale > 0"
                        class="flex justify-between gap-3"
                    >
                        <dt class="text-muted-foreground">{{ t('বিক্রি') }}</dt>
                        <dd class="num text-income">
                            {{ taka(b.cost.sale) }}
                        </dd>
                    </div>
                    <div
                        v-if="b.profit !== null"
                        class="flex justify-between gap-3 font-semibold"
                    >
                        <dt>{{ b.profit < 0 ? t('ক্ষতি') : t('লাভ') }}</dt>
                        <dd
                            :class="[
                                'num',
                                b.profit < 0
                                    ? 'text-destructive'
                                    : 'text-income',
                            ]"
                        >
                            {{ taka(b.profit) }}
                        </dd>
                    </div>
                </dl>

                <div v-if="b.animals.length" class="mt-4">
                    <h3 class="mb-1.5 text-sm font-medium">
                        {{ t('গরুগুলো') }}
                    </h3>
                    <ul
                        class="divide-y divide-border/70 rounded-lg border border-border/70"
                    >
                        <li
                            v-for="a in b.animals"
                            :key="a.id"
                            class="flex items-center justify-between gap-3 px-3 py-2 text-sm"
                        >
                            <div class="min-w-0">
                                <Link
                                    :href="`/animals/${a.id}`"
                                    class="font-medium text-primary underline-offset-4 hover:underline"
                                    >{{ a.tag
                                    }}<span
                                        v-if="a.name"
                                        class="font-normal text-muted-foreground"
                                    >
                                        ({{ a.name }})</span
                                    ></Link
                                >
                                <span
                                    v-if="a.status !== 'active'"
                                    class="ml-1.5 rounded-full bg-clay/12 px-2 py-0.5 text-[11px] text-clay"
                                    >{{
                                        statusLabels[a.status] ?? a.status
                                    }}</span
                                >
                            </div>
                            <div class="shrink-0 text-right">
                                <p class="num text-xs">
                                    <template v-if="a.first_kg !== null"
                                        >{{ kg(a.first_kg) }} →
                                        {{ kg(a.last_kg) }}</template
                                    >
                                    <span
                                        v-else
                                        class="text-muted-foreground"
                                        >{{ t('ওজন নেই') }}</span
                                    >
                                </p>
                                <p
                                    v-if="a.per_day !== null"
                                    class="num text-[11px] text-muted-foreground"
                                >
                                    {{ t('দিনে :kg', { kg: kg(a.per_day) }) }}
                                </p>
                            </div>
                        </li>
                    </ul>
                </div>
                <p v-else class="mt-4 text-sm text-muted-foreground">
                    {{
                        t(
                            'এই ব্যাচে এখনো গরু নেই — নতুন গরু ফর্মে ব্যাচ বেছে নিন।',
                        )
                    }}
                </p>

                <div
                    v-if="!b.closed_on && b.stage"
                    class="mt-4 rounded-lg border border-turmeric/50 bg-turmeric/12 p-3"
                >
                    <p class="text-xs text-muted-foreground">
                        {{
                            t('এখনকার ধাপ · দিন :from–:to', {
                                from: b.stage.from,
                                to: b.stage.to,
                            })
                        }}
                    </p>
                    <p class="font-display text-base">{{ b.stage.title }}</p>
                    <ul class="mt-1 list-disc space-y-0.5 pl-5 text-sm">
                        <li v-for="task in b.stage.tasks" :key="task">
                            {{ task }}
                        </li>
                    </ul>
                </div>

                <p
                    v-if="b.notes && editingId !== b.id"
                    class="mt-3 text-sm text-muted-foreground"
                >
                    {{ b.notes }}
                </p>

                <form
                    v-if="editingId === b.id"
                    class="mt-4 grid gap-3 rounded-lg border border-border bg-muted/30 p-3 sm:grid-cols-2"
                    @submit.prevent="saveEdit(b)"
                >
                    <Field :label="t('নাম')" :error="editErrors.name"
                        ><Input
                            v-model="edit.name"
                            :placeholder="t('ব্যাচ-:n', { n: b.number })"
                    /></Field>
                    <Field
                        :label="t('শুরুর তারিখ')"
                        :error="editErrors.started_on"
                        ><DateField v-model="edit.started_on" required
                    /></Field>
                    <Field
                        :label="t('লক্ষ্য (দিন)')"
                        :error="editErrors.target_days"
                        ><Input
                            v-model="edit.target_days"
                            type="number"
                            min="7"
                            max="730"
                            required
                    /></Field>
                    <Field
                        :label="t('দৈনিক বৃদ্ধির লক্ষ্য (কেজি)')"
                        :error="editErrors.target_gain_kg"
                        ><Input
                            v-model="edit.target_gain_kg"
                            type="number"
                            step="0.01"
                            min="0"
                            inputmode="decimal"
                    /></Field>
                    <Field
                        :label="t('নোট')"
                        :error="editErrors.notes"
                        class="sm:col-span-2"
                        ><Input v-model="edit.notes"
                    /></Field>
                    <div class="flex gap-2 sm:col-span-2 sm:justify-end">
                        <Button type="submit" size="sm" :disabled="busy">{{
                            t('সংরক্ষণ')
                        }}</Button>
                        <Button
                            type="button"
                            size="sm"
                            variant="ghost"
                            @click="editingId = null"
                            >{{ t('বাদ') }}</Button
                        >
                    </div>
                </form>

                <footer
                    v-if="can['post-entries'] && editingId !== b.id"
                    class="mt-4 flex flex-wrap gap-2"
                >
                    <Button
                        v-if="!b.closed_on"
                        size="sm"
                        variant="secondary"
                        :disabled="busy"
                        @click="closeBatch(b)"
                        ><Flag /> {{ t('ব্যাচ শেষ') }}</Button
                    >
                    <Button size="sm" variant="ghost" @click="startEdit(b)"
                        ><Pencil /> {{ t('সম্পাদনা') }}</Button
                    >
                </footer>
            </article>
        </div>

        <details
            v-if="guide.length"
            class="group rounded-xl border border-border bg-card p-4"
        >
            <summary
                class="flex cursor-pointer list-none items-center gap-2 font-display text-lg"
            >
                <BookOpen class="size-5 text-paddy" />
                {{ t('মোটাতাজার গাইড — দিন অনুযায়ী') }}
            </summary>
            <ol class="mt-3 grid gap-3">
                <li
                    v-for="stage in guide"
                    :key="stage.from"
                    class="border-l-2 border-paddy/40 pl-3"
                >
                    <p class="text-xs text-muted-foreground">
                        {{
                            t('দিন :from–:to', {
                                from: stage.from,
                                to: stage.to,
                            })
                        }}
                    </p>
                    <p class="font-medium">{{ stage.title }}</p>
                    <ul
                        class="mt-0.5 list-disc space-y-0.5 pl-5 text-sm text-muted-foreground"
                    >
                        <li v-for="task in stage.tasks" :key="task">
                            {{ task }}
                        </li>
                    </ul>
                </li>
            </ol>
            <p class="mt-4 text-xs text-muted-foreground">
                {{ t('ঔষধ ও টিকা ডাক্তারের পরামর্শে দিন।') }}
            </p>
        </details>
    </div>
</template>
