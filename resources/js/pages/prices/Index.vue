<script setup lang="ts">
import { Head, router, useForm } from '@inertiajs/vue3';
import { Check, ChevronDown, Lightbulb, Store, Tag, Trash2 } from '@lucide/vue';
import { computed, ref } from 'vue';
import DateField from '@/components/khamar/DateField.vue';
import Field from '@/components/khamar/Field.vue';
import NSelect from '@/components/khamar/NSelect.vue';
import PageTitle from '@/components/khamar/PageTitle.vue';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { ask } from '@/lib/dialog';
import { t } from '@/lib/i18n';
import { day, taka, today, useCan } from '@/lib/khamar';

defineOptions({
    layout: { breadcrumbs: [{ title: 'বাজারদর', href: '/prices' }] },
});

type HistoryRow = {
    id: number;
    date: string;
    price: number;
    place: string | null;
    quality: string | null;
    notes: string | null;
    deletable: boolean;
};

type Card = {
    key: string;
    commodity: string;
    unit: string;
    unit_label: string;
    latest: number;
    latest_date: string;
    place: string | null;
    quality: string | null;
    previous: number | null;
    change_pct: number | null;
    avg30: number | null;
    avg90: number | null;
    min12: number | null;
    max12: number | null;
    per_kg: number | null;
    count: number;
    spark: { date: string; price: number }[];
    history: HistoryRow[];
};

type Hint = { tone: string; title: string; detail: string };
type Preset = { commodity: string; unit: string };

const props = defineProps<{
    cards: Card[];
    hints: Hint[];
    presets: Preset[];
    units: Record<string, string>;
    places: string[];
}>();

const can = useCan();
const canWrite = computed(() => !!can.value['post-daily']);

const form = useForm({
    date: today(),
    commodity: '',
    unit: 'kg',
    price: '' as string | number,
    place: '',
    quality: '',
    notes: '',
});

function pickPreset(p: Preset) {
    form.commodity = p.commodity;
    form.unit = p.unit;
}

const isPicked = (p: Preset) =>
    form.commodity === p.commodity && form.unit === p.unit;

const canSubmit = computed(
    () =>
        !form.processing &&
        form.commodity.trim() !== '' &&
        Number(form.price) > 0,
);

function submit() {
    form.post('/prices', {
        preserveScroll: true,
        onSuccess: () => form.reset('price', 'quality', 'notes'),
    });
}

// Filter by commodity.
const filter = ref<string | null>(null);
const commodities = computed(() => [
    ...new Set(props.cards.map((c) => c.commodity)),
]);
const shown = computed(() =>
    filter.value
        ? props.cards.filter((c) => c.commodity === filter.value)
        : props.cards,
);

const open = ref<Set<string>>(new Set());

function toggle(key: string) {
    const next = new Set(open.value);

    if (next.has(key)) {
        next.delete(key);
    } else {
        next.add(key);
    }

    open.value = next;
}

const hintTone: Record<string, string> = {
    good: 'border-paddy/30 bg-paddy/8',
    warn: 'border-clay/30 bg-clay/8',
    info: 'border-pond/30 bg-pond/8',
};

/** Points for a 120×32 sparkline. */
function sparkPath(points: { price: number }[]): string {
    if (points.length < 2) {
        return '';
    }

    const prices = points.map((p) => p.price);
    const min = Math.min(...prices);
    const max = Math.max(...prices);
    const span = max - min || 1;
    const step = 120 / (points.length - 1);

    return points
        .map((p, i) => {
            const x = (i * step).toFixed(1);
            const y = (28 - ((p.price - min) / span) * 24).toFixed(1);

            return `${i === 0 ? 'M' : 'L'}${x},${y}`;
        })
        .join(' ');
}

function lastPoint(points: { price: number }[]): { x: number; y: number } {
    const prices = points.map((p) => p.price);
    const min = Math.min(...prices);
    const span = Math.max(...prices) - min || 1;

    return { x: 120, y: 28 - ((prices[prices.length - 1] - min) / span) * 24 };
}

const removing = ref<number | null>(null);

async function remove(card: Card, row: HistoryRow) {
    if (
        !(await ask.confirm({
            title: t('লেখাটি মুছবেন?'),
            body: t(':name — :price (:date)। শুধু আজকের লেখা মোছা যায়।', {
                name: t(card.commodity),
                price: `${taka(row.price)}/${card.unit_label}`,
                date: day(row.date),
            }),
            confirmText: t('হ্যাঁ, মুছুন'),
            danger: true,
        }))
    ) {
        return;
    }

    removing.value = row.id;
    router.delete(`/prices/${row.id}`, {
        preserveScroll: true,
        onError: () => {},
        onFinish: () => (removing.value = null),
    });
}
</script>

<template>
    <Head :title="t('বাজারদর')" />

    <div class="flex flex-1 flex-col p-4 md:p-6">
        <PageTitle
            :title="t('বাজারদর')"
            :subtitle="
                t(
                    'হাট-বাজারের দাম লিখে রাখুন — কখন বেচবেন, কী কিনবেন, তা আসল হিসাবে ঠিক করুন।',
                )
            "
        />

        <!-- Hints -->
        <section v-if="hints.length" class="mb-6 grid gap-2 sm:grid-cols-2">
            <div
                v-for="(h, i) in hints"
                :key="i"
                :class="[
                    'flex items-start gap-3 rounded-xl border p-3',
                    hintTone[h.tone] ?? 'border-border bg-card',
                ]"
            >
                <Lightbulb class="mt-0.5 size-5 shrink-0 text-turmeric" />
                <div class="min-w-0">
                    <p class="text-sm font-medium">{{ t(h.title) }}</p>
                    <p class="text-xs text-muted-foreground">
                        {{ t(h.detail) }}
                    </p>
                </div>
            </div>
        </section>

        <div class="grid gap-6 lg:grid-cols-5">
            <!-- Quick add -->
            <form
                v-if="canWrite"
                class="h-fit min-w-0 rounded-xl border border-border bg-card p-4 lg:col-span-2"
                @submit.prevent="submit"
            >
                <h2 class="mb-3 flex items-center gap-2 font-display text-lg">
                    <Tag class="size-5 text-paddy" />
                    {{ t('দাম লিখুন') }}
                </h2>

                <div class="grid gap-3">
                    <div class="flex flex-wrap gap-1.5">
                        <button
                            v-for="p in presets"
                            :key="`${p.commodity}-${p.unit}`"
                            type="button"
                            :class="[
                                'rounded-full border px-3 py-1.5 text-xs transition-colors',
                                isPicked(p)
                                    ? 'border-primary bg-primary text-primary-foreground'
                                    : 'border-input bg-card hover:bg-accent',
                            ]"
                            @click="pickPreset(p)"
                        >
                            {{ t(p.commodity) }}
                            <span class="opacity-70"
                                >· {{ units[p.unit] ?? p.unit }}</span
                            >
                        </button>
                    </div>

                    <div class="grid grid-cols-2 gap-3">
                        <Field
                            :label="t('পণ্য')"
                            :error="form.errors.commodity"
                        >
                            <Input
                                v-model="form.commodity"
                                class="h-10"
                                :placeholder="t('যেমন: ধান')"
                                required
                            />
                        </Field>
                        <Field :label="t('একক')" :error="form.errors.unit">
                            <NSelect v-model="form.unit">
                                <option
                                    v-for="(label, key) in units"
                                    :key="key"
                                    :value="key"
                                >
                                    {{ label }}
                                </option>
                            </NSelect>
                        </Field>
                    </div>

                    <div class="grid grid-cols-2 gap-3">
                        <Field
                            :label="
                                t('দাম (৳ / :unit)', {
                                    unit: units[form.unit] ?? form.unit,
                                })
                            "
                            :error="form.errors.price"
                            :hint="
                                form.unit === 'mon'
                                    ? t('১ মণ = ৪০ কেজি')
                                    : undefined
                            "
                        >
                            <Input
                                v-model="form.price"
                                type="number"
                                inputmode="decimal"
                                min="0"
                                step="0.01"
                                class="num h-10"
                                required
                            />
                        </Field>
                        <Field :label="t('তারিখ')" :error="form.errors.date">
                            <DateField
                                v-model="form.date"
                                :max="today()"
                                class="h-10"
                                required
                            />
                        </Field>
                    </div>

                    <Field :label="t('হাট / বাজার')" :error="form.errors.place">
                        <Input
                            v-model="form.place"
                            class="h-10"
                            list="price-places"
                            :placeholder="t('যেমন: নওগাঁ হাট')"
                        />
                        <datalist id="price-places">
                            <option v-for="p in places" :key="p" :value="p" />
                        </datalist>
                    </Field>

                    <Field :label="t('মান / ধরন')" :error="form.errors.quality">
                        <Input
                            v-model="form.quality"
                            class="h-10"
                            :placeholder="t('যেমন: মাঝারি গরু ~২ মণ মাংস')"
                        />
                    </Field>

                    <Field
                        :label="t('নোট (ইচ্ছামতো)')"
                        :error="form.errors.notes"
                    >
                        <Input v-model="form.notes" class="h-10" />
                    </Field>

                    <Button type="submit" :disabled="!canSubmit">
                        <Check class="size-4" />
                        {{ t('লিখুন') }}
                    </Button>
                </div>
            </form>

            <!-- Cards -->
            <section
                :class="[
                    'min-w-0 space-y-3',
                    canWrite ? 'lg:col-span-3' : 'lg:col-span-5',
                ]"
            >
                <div
                    v-if="commodities.length > 1"
                    class="flex flex-wrap gap-1.5"
                >
                    <button
                        type="button"
                        :class="[
                            'rounded-full border px-3 py-1.5 text-xs transition-colors',
                            filter === null
                                ? 'border-primary bg-primary text-primary-foreground'
                                : 'border-input bg-card hover:bg-accent',
                        ]"
                        @click="filter = null"
                    >
                        {{ t('সব') }}
                    </button>
                    <button
                        v-for="c in commodities"
                        :key="c"
                        type="button"
                        :class="[
                            'rounded-full border px-3 py-1.5 text-xs transition-colors',
                            filter === c
                                ? 'border-primary bg-primary text-primary-foreground'
                                : 'border-input bg-card hover:bg-accent',
                        ]"
                        @click="filter = filter === c ? null : c"
                    >
                        {{ t(c) }}
                    </button>
                </div>

                <div
                    v-if="!cards.length"
                    class="flex flex-col items-center gap-2 rounded-xl border border-dashed border-border bg-card px-4 py-12 text-center text-sm text-muted-foreground"
                >
                    <Store class="size-8 text-soil/60" />
                    {{ t('এখনো কোনো দাম লেখা হয়নি।') }}
                </div>

                <article
                    v-for="c in shown"
                    :key="c.key"
                    class="rounded-xl border border-border bg-card p-4"
                >
                    <div class="flex items-start justify-between gap-3">
                        <div class="min-w-0">
                            <h3 class="truncate font-display text-base">
                                {{ t(c.commodity) }}
                                <span
                                    class="text-xs font-normal text-muted-foreground"
                                    >· {{ c.unit_label }}</span
                                >
                            </h3>
                            <p class="mt-1 flex flex-wrap items-baseline gap-2">
                                <span class="num text-xl font-semibold">{{
                                    taka(c.latest)
                                }}</span>
                                <span
                                    v-if="c.change_pct !== null"
                                    :class="[
                                        'num text-xs font-medium',
                                        c.change_pct > 0
                                            ? 'text-income'
                                            : c.change_pct < 0
                                              ? 'text-expense'
                                              : 'text-muted-foreground',
                                    ]"
                                    >{{
                                        c.change_pct > 0
                                            ? '▲'
                                            : c.change_pct < 0
                                              ? '▼'
                                              : '='
                                    }}
                                    {{ Math.abs(c.change_pct) }}%</span
                                >
                            </p>
                            <p
                                class="mt-0.5 truncate text-xs text-muted-foreground"
                            >
                                <span class="num">{{
                                    day(c.latest_date)
                                }}</span>
                                <template v-if="c.place">
                                    · {{ c.place }}</template
                                >
                                <template v-if="c.quality">
                                    · {{ c.quality }}</template
                                >
                            </p>
                        </div>

                        <svg
                            v-if="c.spark.length > 1"
                            viewBox="-2 0 124 32"
                            class="h-8 w-28 shrink-0 text-paddy"
                            aria-hidden="true"
                        >
                            <path
                                :d="sparkPath(c.spark)"
                                fill="none"
                                stroke="currentColor"
                                stroke-width="1.75"
                                stroke-linejoin="round"
                                stroke-linecap="round"
                            />
                            <circle
                                :cx="lastPoint(c.spark).x"
                                :cy="lastPoint(c.spark).y"
                                r="2.5"
                                fill="currentColor"
                            />
                        </svg>
                    </div>

                    <dl
                        class="mt-3 grid grid-cols-3 gap-2 border-t border-border/70 pt-3 text-xs"
                    >
                        <div>
                            <dt class="text-muted-foreground">
                                {{ t('৩০ দিনের গড়') }}
                            </dt>
                            <dd class="num font-medium">
                                {{ c.avg30 !== null ? taka(c.avg30) : '—' }}
                            </dd>
                        </div>
                        <div>
                            <dt class="text-muted-foreground">
                                {{ t('৯০ দিনের গড়') }}
                            </dt>
                            <dd class="num font-medium">
                                {{ c.avg90 !== null ? taka(c.avg90) : '—' }}
                            </dd>
                        </div>
                        <div>
                            <dt class="text-muted-foreground">
                                {{ t('১২ মাসে কম–বেশি') }}
                            </dt>
                            <dd class="num font-medium">
                                <template
                                    v-if="c.min12 !== null && c.max12 !== null"
                                    >{{ taka(c.min12, false) }}–{{
                                        taka(c.max12, false)
                                    }}</template
                                >
                                <template v-else>—</template>
                            </dd>
                        </div>
                    </dl>

                    <p
                        v-if="c.per_kg !== null && c.unit !== 'kg'"
                        class="mt-2 text-xs text-muted-foreground"
                    >
                        {{
                            t('= :amount/কেজি', {
                                amount: taka(Math.round(c.per_kg)),
                            })
                        }}
                    </p>

                    <button
                        type="button"
                        class="mt-2 flex w-full items-center justify-between rounded-md px-1 py-1.5 text-xs font-medium text-pond hover:bg-accent"
                        @click="toggle(c.key)"
                    >
                        {{ t('আগের দাম (:nটি)', { n: c.count }) }}
                        <ChevronDown
                            :class="[
                                'size-4 transition-transform',
                                open.has(c.key) && 'rotate-180',
                            ]"
                        />
                    </button>

                    <ul
                        v-if="open.has(c.key)"
                        class="divide-y divide-border/70 border-t border-border/70"
                    >
                        <li
                            v-for="r in c.history"
                            :key="r.id"
                            class="flex items-start gap-3 py-2"
                        >
                            <div class="min-w-0 flex-1">
                                <p class="flex items-baseline gap-2 text-sm">
                                    <span class="num font-medium">{{
                                        taka(r.price)
                                    }}</span>
                                    <span
                                        class="num text-xs text-muted-foreground"
                                        >{{ day(r.date) }}</span
                                    >
                                </p>
                                <p
                                    v-if="r.place || r.quality || r.notes"
                                    class="truncate text-xs text-muted-foreground"
                                >
                                    {{
                                        [r.place, r.quality, r.notes]
                                            .filter(Boolean)
                                            .join(' · ')
                                    }}
                                </p>
                            </div>
                            <button
                                v-if="canWrite && r.deletable"
                                type="button"
                                class="shrink-0 rounded-md p-1.5 text-muted-foreground hover:bg-destructive/10 hover:text-destructive disabled:opacity-50"
                                :disabled="removing === r.id"
                                :aria-label="t('মুছুন')"
                                @click="remove(c, r)"
                            >
                                <Trash2 class="size-4" />
                            </button>
                        </li>
                    </ul>
                </article>
            </section>
        </div>
    </div>
</template>
