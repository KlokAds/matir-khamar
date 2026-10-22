<script setup lang="ts">
import { Head, Link, router } from '@inertiajs/vue3';
import {
    CalendarDays,
    Coins,
    Flag,
    MapPin,
    PackagePlus,
    Pencil,
    Plus,
    Receipt,
    Scale,
    Sprout,
    TrendingDown,
    TrendingUp,
    Trash2,
    Wheat,
} from '@lucide/vue';
import { computed, reactive, ref } from 'vue';
import DateField from '@/components/khamar/DateField.vue';
import Field from '@/components/khamar/Field.vue';
import NSelect from '@/components/khamar/NSelect.vue';
import PageTitle from '@/components/khamar/PageTitle.vue';
import StatTile from '@/components/khamar/StatTile.vue';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { ask } from '@/lib/dialog';
import { t } from '@/lib/i18n';
import { centerLabel, day, taka, today, useCan } from '@/lib/khamar';
import type { Center } from '@/lib/khamar';

defineOptions({
    layout: {
        breadcrumbs: [
            { title: 'জমি ও ফসল', href: '/land' },
            { title: 'মৌসুম', href: '#' },
        ],
    },
});

type Output = {
    item_id: number;
    name: string;
    kind: string;
    unit: string;
    qty: number;
    value: number;
};

type MainCrop = Output & {
    mon: number | null;
    mon_per_bigha: number | null;
    /** Taka per kg (not poisha). */
    cost_per_kg: number | null;
    /** Taka per মণ (not poisha). */
    cost_per_mon: number | null;
};

type SeasonCard = {
    id: number;
    name: string;
    crop: string;
    variety: string | null;
    cost_center_id: number;
    center: string;
    started_on: string;
    sown_on: string | null;
    harvested_on: string | null;
    closed_on: string | null;
    days: number;
    span: string;
    notes: string | null;
    target_mon_per_bigha: number | null;
    plots: { id: number; name: string; area_decimals: number }[];
    area_decimals: number;
    bigha: number;
    costs: { name: string; amount: number }[];
    cost: number;
    outputs: Output[];
    value: number;
    sales: number;
    profit: number;
    profit_per_bigha: number | null;
    cost_per_bigha: number | null;
    main: MainCrop | null;
    by_products: number;
};

type HarvestItem = { id: number; name: string; unit: string; kind: string };
type PlotOption = {
    id: number;
    name: string;
    area_decimals: number;
    bigha: number;
};
type Crop = { crop: string; seasons: string[]; outputs: string[] };

const props = defineProps<{
    season: SeasonCard;
    bighaDecimals: number;
    harvestItems: HarvestItem[];
    plots: PlotOption[];
    crops: Crop[];
    centers: Center[];
}>();

const can = useCan();
const KG_PER_MON = 40;

const num = (value: number | null | undefined, digits = 2): string =>
    value === null || value === undefined
        ? '—'
        : Number(value).toLocaleString('en-IN', {
              maximumFractionDigits: digits,
          });

const toNumberOrNull = (value: string | number | null): number | null =>
    value === '' || value === null ? null : Number(value);

const kindLabels: Record<string, string> = {
    product: t('ফসল (বিক্রির পণ্য)'),
    roughage: t('খড় / শুকনো খাবার'),
    green: t('কাঁচা ঘাস / সবুজ খাবার'),
    crop_input: t('চাষের উপকরণ (সার, বীজ)'),
};

// Header and timeline
const status = computed(() =>
    props.season.closed_on
        ? { label: t('শেষ'), cls: 'bg-soil/10 text-soil' }
        : props.season.harvested_on
          ? { label: t('তোলা হয়েছে'), cls: 'bg-turmeric/25 text-soil' }
          : { label: t('চলছে'), cls: 'bg-paddy/12 text-paddy' },
);

const steps = computed(() => [
    { label: t('জমি তৈরি'), date: props.season.started_on },
    { label: t('রোপণ/বপন'), date: props.season.sown_on },
    { label: t('তোলা'), date: props.season.harvested_on },
    { label: t('শেষ'), date: props.season.closed_on },
]);

const areaText = (decimals: number): string =>
    t(':n শতাংশ', { n: num(decimals) });

// Results
const profitTone = computed(() => (props.season.profit < 0 ? 'clay' : 'paddy'));
const harvested = computed(() => props.season.outputs.length > 0);

const yieldPct = computed(() => {
    const main = props.season.main;
    const target = props.season.target_mon_per_bigha;

    if (!main || main.mon_per_bigha === null || !target) {
        return null;
    }

    return Math.round((main.mon_per_bigha / target) * 100);
});

const maxCost = computed(() =>
    Math.max(1, ...props.season.costs.map((c) => Math.abs(c.amount))),
);
const costShare = (amount: number): number =>
    props.season.cost > 0 ? Math.round((amount / props.season.cost) * 100) : 0;

const perKgTaka = (value: number | null): string =>
    value === null ? '—' : taka(Math.round(value * 100));

// Harvest form
type Row = {
    key: number;
    item_id: number | null;
    qty: string | number;
    unit: 'kg' | 'mon';
    rate: string | number;
};

let nextKey = 1;

function prefillRows(): Row[] {
    const crop = props.crops.find((c) => c.crop === props.season.crop);
    const rows: Row[] = [];

    for (const name of crop?.outputs ?? [props.season.crop]) {
        const item = props.harvestItems.find((i) => i.name === name);

        if (item) {
            rows.push({
                key: nextKey++,
                item_id: item.id,
                qty: '',
                unit: item.kind === 'product' ? 'mon' : 'kg',
                rate: '',
            });
        }
    }

    if (!rows.length) {
        rows.push({
            key: nextKey++,
            item_id: null,
            qty: '',
            unit: 'mon',
            rate: '',
        });
    }

    return rows;
}

const harvestOpen = ref(!harvested.value);
const harvest = reactive({ date: today(), rows: prefillRows() });
const harvestErrors = ref<Record<string, string>>({});
const harvestBusy = ref(false);

const rowKg = (row: Row): number => {
    const qty = Number(row.qty) || 0;

    return row.unit === 'mon' ? qty * KG_PER_MON : qty;
};

/** The rate the farmer typed is per the chosen unit; the server wants taka per kg. */
const rowRatePerKg = (row: Row): number => {
    const rate = Number(row.rate) || 0;

    return row.unit === 'mon' ? rate / KG_PER_MON : rate;
};

const rowValue = (row: Row): number =>
    Math.round(rowKg(row) * rowRatePerKg(row) * 100);

const harvestTotal = computed(() =>
    harvest.rows.reduce((sum, row) => sum + rowValue(row), 0),
);

const itemOf = (id: number | null): HarvestItem | undefined =>
    props.harvestItems.find((i) => i.id === id);

function addRow() {
    harvest.rows.push({
        key: nextKey++,
        item_id: null,
        qty: '',
        unit: 'kg',
        rate: '',
    });
}

function removeRow(key: number) {
    harvest.rows = harvest.rows.filter((r) => r.key !== key);
}

function rowError(index: number): string | undefined {
    return (
        harvestErrors.value[`outputs.${index}.item_id`] ??
        harvestErrors.value[`outputs.${index}.qty`] ??
        harvestErrors.value[`outputs.${index}.rate`]
    );
}

function saveHarvest() {
    harvestBusy.value = true;
    harvestErrors.value = {};
    router.post(
        `/land/seasons/${props.season.id}/harvest`,
        {
            date: harvest.date,
            outputs: harvest.rows
                .filter((r) => r.item_id !== null)
                .map((r) => ({
                    item_id: r.item_id,
                    qty: Math.round(rowKg(r) * 100) / 100,
                    rate: Math.round(rowRatePerKg(r) * 10000) / 10000,
                })),
        } as never,
        {
            preserveScroll: true,
            onSuccess: () => {
                harvest.rows = prefillRows();
                harvestOpen.value = false;
            },
            onError: (errors) => {
                harvestErrors.value = errors;
            },
            onFinish: () => {
                harvestBusy.value = false;
            },
        },
    );
}

// New item (a new crop's produce)
const itemOpen = ref(false);
const newItem = reactive({ name: '', kind: 'product' });
const itemErrors = ref<Record<string, string>>({});
const itemBusy = ref(false);

function saveItem() {
    itemBusy.value = true;
    itemErrors.value = {};
    router.post(
        '/land/items',
        { name: newItem.name.trim(), kind: newItem.kind },
        {
            preserveScroll: true,
            onSuccess: () => {
                const name = newItem.name.trim();
                newItem.name = '';
                itemOpen.value = false;
                const item = props.harvestItems.find((i) => i.name === name);

                if (item) {
                    const empty = harvest.rows.find((r) => r.item_id === null);

                    if (empty) {
                        empty.item_id = item.id;
                    } else {
                        harvest.rows.push({
                            key: nextKey++,
                            item_id: item.id,
                            qty: '',
                            unit: item.kind === 'product' ? 'mon' : 'kg',
                            rate: '',
                        });
                    }
                }
            },
            onError: (errors) => {
                itemErrors.value = errors;
            },
            onFinish: () => {
                itemBusy.value = false;
            },
        },
    );
}

// Edit and close
const editing = ref(false);
const editBusy = ref(false);
const editErrors = ref<Record<string, string>>({});
const edit = reactive({
    cost_center_id: null as number | null,
    crop: '',
    name: '',
    variety: '',
    started_on: '',
    sown_on: '',
    harvested_on: '',
    closed_on: '',
    target_mon_per_bigha: '' as string | number,
    notes: '',
    plots: [] as {
        key: number;
        plot_id: number | null;
        area_decimals: string | number;
    }[],
});

const cropSeasons = computed(
    () => props.crops.find((c) => c.crop === edit.crop)?.seasons ?? [],
);

/** Plots the season already uses stay choosable even if they were turned off. */
const plotChoices = computed(() => {
    const list: { id: number; name: string; area_decimals: number }[] = [
        ...props.plots,
    ];

    for (const p of props.season.plots) {
        if (!list.some((x) => x.id === p.id)) {
            list.push(p);
        }
    }

    return list;
});

const editArea = computed(() =>
    edit.plots.reduce((sum, p) => sum + (Number(p.area_decimals) || 0), 0),
);

function startEdit() {
    const s = props.season;
    editErrors.value = {};
    Object.assign(edit, {
        cost_center_id: s.cost_center_id,
        crop: s.crop,
        name: s.name,
        variety: s.variety ?? '',
        started_on: s.started_on,
        sown_on: s.sown_on ?? '',
        harvested_on: s.harvested_on ?? '',
        closed_on: s.closed_on ?? '',
        target_mon_per_bigha: s.target_mon_per_bigha ?? '',
        notes: s.notes ?? '',
        plots: s.plots.map((p) => ({
            key: nextKey++,
            plot_id: p.id,
            area_decimals: p.area_decimals,
        })),
    });
    editing.value = true;
}

function pickPlot(row: {
    plot_id: number | null;
    area_decimals: string | number;
}) {
    const plot = plotChoices.value.find((p) => p.id === row.plot_id);

    if (plot && (row.area_decimals === '' || row.area_decimals === null)) {
        row.area_decimals = plot.area_decimals;
    }
}

function addPlot() {
    edit.plots.push({ key: nextKey++, plot_id: null, area_decimals: '' });
}

function send(payload: Record<string, unknown>) {
    editBusy.value = true;
    router.put(`/land/seasons/${props.season.id}`, payload as never, {
        preserveScroll: true,
        onSuccess: () => {
            editing.value = false;
        },
        onError: (errors) => {
            editErrors.value = errors;
        },
        onFinish: () => {
            editBusy.value = false;
        },
    });
}

const saveEdit = () =>
    send({
        cost_center_id: edit.cost_center_id,
        crop: edit.crop.trim(),
        name: edit.name.trim(),
        variety: edit.variety.trim() || null,
        started_on: edit.started_on,
        sown_on: edit.sown_on || null,
        harvested_on: edit.harvested_on || null,
        closed_on: edit.closed_on || null,
        target_mon_per_bigha: toNumberOrNull(edit.target_mon_per_bigha),
        notes: edit.notes.trim() || null,
        plots: edit.plots
            .filter((p) => p.plot_id !== null)
            .map((p) => ({
                plot_id: p.plot_id,
                area_decimals: Number(p.area_decimals),
            })),
    });

async function closeSeason() {
    const s = props.season;
    const ok = await ask.confirm({
        title: t(':name শেষ করবেন?', { name: s.name }),
        body: t(
            'আজকের তারিখে মৌসুম বন্ধ হবে। এরপরের খরচ এই মৌসুমে ধরা হবে না। পরে সম্পাদনা থেকে বদলানো যাবে।',
        ),
        confirmText: t('হ্যাঁ, শেষ করুন'),
    });

    if (!ok) {
        return;
    }

    send({
        cost_center_id: s.cost_center_id,
        crop: s.crop,
        name: s.name,
        variety: s.variety,
        started_on: s.started_on,
        sown_on: s.sown_on,
        harvested_on: s.harvested_on,
        closed_on: today(),
        target_mon_per_bigha: s.target_mon_per_bigha,
        notes: s.notes,
        plots: s.plots.map((p) => ({
            plot_id: p.id,
            area_decimals: p.area_decimals,
        })),
    });
}
</script>

<template>
    <Head
        :title="t(':crop — :name', { crop: t(season.crop), name: season.name })"
    />

    <div class="flex flex-1 flex-col gap-5 p-4 md:p-6">
        <PageTitle
            :title="
                t(':crop — :name', { crop: t(season.crop), name: season.name })
            "
            :subtitle="
                season.variety
                    ? t('জাত: :variety · :center', {
                          variety: season.variety,
                          center: t(season.center),
                      })
                    : t(season.center)
            "
        >
            <template #actions>
                <template v-if="can['post-entries'] && !editing">
                    <Button
                        v-if="!season.closed_on"
                        variant="secondary"
                        :disabled="editBusy"
                        @click="closeSeason"
                        ><Flag /> {{ t('মৌসুম শেষ') }}</Button
                    >
                    <Button variant="secondary" @click="startEdit"
                        ><Pencil /> {{ t('সম্পাদনা') }}</Button
                    >
                </template>
            </template>
        </PageTitle>

        <!-- Header card: plots and timeline -->
        <section class="rounded-xl border border-paddy/30 bg-card p-4">
            <div class="flex flex-wrap items-center justify-between gap-2">
                <p class="flex items-center gap-1.5 text-sm font-medium">
                    <MapPin class="size-4 text-paddy" />
                    {{
                        t('মোট :area (:bigha বিঘা)', {
                            area: areaText(season.area_decimals),
                            bigha: num(season.bigha),
                        })
                    }}
                </p>
                <span
                    :class="[
                        'rounded-full px-2.5 py-0.5 text-xs font-medium',
                        status.cls,
                    ]"
                    >{{ status.label }}</span
                >
            </div>
            <ul class="mt-2 flex flex-wrap gap-1.5">
                <li
                    v-for="p in season.plots"
                    :key="p.id"
                    class="rounded-md bg-muted/60 px-2 py-1 text-xs"
                >
                    {{ p.name }}
                    <span class="num text-muted-foreground"
                        >· {{ areaText(p.area_decimals) }}</span
                    >
                </li>
            </ul>

            <ol class="mt-4 grid grid-cols-2 gap-2 sm:grid-cols-4">
                <li
                    v-for="(step, i) in steps"
                    :key="i"
                    :class="[
                        'rounded-lg border px-3 py-2',
                        step.date
                            ? 'border-paddy/35 bg-paddy/8'
                            : 'border-dashed border-border',
                    ]"
                >
                    <p
                        class="flex items-center gap-1 text-[11px] text-muted-foreground"
                    >
                        <span
                            :class="[
                                'inline-flex size-4 items-center justify-center rounded-full text-[10px] font-semibold',
                                step.date
                                    ? 'bg-paddy text-white'
                                    : 'bg-muted text-muted-foreground',
                            ]"
                            >{{ i + 1 }}</span
                        >
                        {{ step.label }}
                    </p>
                    <p class="num mt-0.5 text-sm font-medium">
                        {{ step.date ? day(step.date) : t('বাকি') }}
                    </p>
                </li>
            </ol>
            <p
                class="mt-3 flex items-center gap-1.5 text-xs text-muted-foreground"
            >
                <CalendarDays class="size-3.5" />
                {{
                    season.harvested_on || season.closed_on
                        ? t('জমি তৈরি থেকে তোলা পর্যন্ত :span', {
                              span: season.span,
                          })
                        : t('শুরু থেকে :span চলছে', { span: season.span })
                }}
            </p>
            <p v-if="season.notes" class="mt-2 text-sm text-muted-foreground">
                {{ season.notes }}
            </p>
        </section>

        <!-- Edit panel -->
        <form
            v-if="editing"
            class="grid gap-3 rounded-xl border border-border bg-card p-4 sm:grid-cols-2"
            @submit.prevent="saveEdit"
        >
            <h2 class="font-display text-lg sm:col-span-2">
                {{ t('মৌসুম সম্পাদনা') }}
            </h2>
            <Field :label="t('ফসল')" :error="editErrors.crop"
                ><Input
                    v-model="edit.crop"
                    list="season-crops"
                    required
                    :placeholder="t('যেমন: ধান')"
                />
                <datalist id="season-crops">
                    <option v-for="c in crops" :key="c.crop" :value="c.crop" />
                </datalist>
            </Field>
            <Field :label="t('মৌসুমের নাম')" :error="editErrors.name"
                ><Input
                    v-model="edit.name"
                    list="season-names"
                    required
                    :placeholder="t('যেমন: আমন ২০২৬')"
                />
                <datalist id="season-names">
                    <option v-for="s in cropSeasons" :key="s" :value="s" />
                </datalist>
            </Field>
            <Field :label="t('জাত (ইচ্ছা হলে)')" :error="editErrors.variety"
                ><Input
                    v-model="edit.variety"
                    :placeholder="t('যেমন: ব্রি ধান ৪৯')"
            /></Field>
            <Field :label="t('প্রজেক্ট')" :error="editErrors.cost_center_id"
                ><NSelect v-model="edit.cost_center_id"
                    ><option v-for="c in centers" :key="c.id" :value="c.id">
                        {{ centerLabel({ ...c, name: t(c.name) }) }}
                    </option></NSelect
                ></Field
            >
            <Field :label="t('জমি তৈরি শুরু')" :error="editErrors.started_on"
                ><DateField v-model="edit.started_on" :max="today()" required
            /></Field>
            <Field :label="t('রোপণ/বপনের তারিখ')" :error="editErrors.sown_on"
                ><DateField v-model="edit.sown_on" clearable
            /></Field>
            <Field :label="t('তোলার তারিখ')" :error="editErrors.harvested_on"
                ><DateField v-model="edit.harvested_on" clearable
            /></Field>
            <Field
                :label="t('শেষের তারিখ')"
                :error="editErrors.closed_on"
                :hint="t('খালি রাখলে মৌসুম চলছে ধরা হবে')"
                ><DateField v-model="edit.closed_on" :max="today()" clearable
            /></Field>
            <Field
                :label="t('লক্ষ্য ফলন (মণ/বিঘা)')"
                :error="editErrors.target_mon_per_bigha"
                :hint="t('যেমন: ২০ মণ')"
                ><Input
                    v-model="edit.target_mon_per_bigha"
                    type="number"
                    step="0.1"
                    min="0"
                    inputmode="decimal"
            /></Field>
            <Field :label="t('নোট')" :error="editErrors.notes"
                ><Input v-model="edit.notes"
            /></Field>

            <div class="grid gap-2 sm:col-span-2">
                <p class="text-sm font-medium">
                    {{ t('জমি') }}
                    <span class="num font-normal text-muted-foreground">
                        ·
                        {{
                            t('মোট :area (:bigha বিঘা)', {
                                area: areaText(editArea),
                                bigha: num(editArea / (bighaDecimals || 1)),
                            })
                        }}</span
                    >
                </p>
                <div
                    v-for="row in edit.plots"
                    :key="row.key"
                    class="grid grid-cols-[1fr_7rem_auto] items-start gap-2"
                >
                    <NSelect
                        v-model="row.plot_id"
                        :placeholder="t('জমি বেছে নিন')"
                        @update:model-value="pickPlot(row)"
                        ><option
                            v-for="p in plotChoices"
                            :key="p.id"
                            :value="p.id"
                        >
                            {{ p.name }} ({{ areaText(p.area_decimals) }})
                        </option></NSelect
                    >
                    <Input
                        v-model="row.area_decimals"
                        type="number"
                        step="0.01"
                        min="0.5"
                        inputmode="decimal"
                        :aria-label="t('শতাংশ')"
                        :placeholder="t('শতাংশ')"
                    />
                    <Button
                        type="button"
                        variant="secondary"
                        size="icon"
                        :aria-label="t('বাদ দিন')"
                        :disabled="edit.plots.length <= 1"
                        @click="
                            edit.plots = edit.plots.filter(
                                (p) => p.key !== row.key,
                            )
                        "
                        ><Trash2
                    /></Button>
                </div>
                <p
                    v-if="editErrors.plots || editErrors['plots.0.plot_id']"
                    class="text-xs text-destructive"
                >
                    {{ editErrors.plots ?? editErrors['plots.0.plot_id'] }}
                </p>
                <Button
                    type="button"
                    variant="outline"
                    size="sm"
                    class="justify-self-start"
                    @click="addPlot"
                    ><Plus /> {{ t('আরেকটি জমি') }}</Button
                >
            </div>

            <div class="flex gap-2 sm:col-span-2 sm:justify-end">
                <Button type="submit" :disabled="editBusy">{{
                    t('সংরক্ষণ')
                }}</Button>
                <Button
                    type="button"
                    variant="secondary"
                    @click="editing = false"
                    >{{ t('বাদ') }}</Button
                >
            </div>
        </form>

        <!-- Result tiles -->
        <section class="grid grid-cols-2 gap-3 lg:grid-cols-4">
            <StatTile
                :label="t('মোট খরচ')"
                :value="taka(season.cost)"
                :hint="
                    season.cost_per_bigha !== null
                        ? t('বিঘায় :amount', {
                              amount: taka(season.cost_per_bigha),
                          })
                        : ''
                "
                :icon="Receipt"
                tone="clay"
            />
            <StatTile
                :label="t('ফসলের দাম')"
                :value="harvested ? taka(season.value) : '—'"
                :hint="harvested ? t('বাজারদরে') : t('এখনো তোলা হয়নি')"
                :icon="Wheat"
                tone="turmeric"
            />
            <StatTile
                :label="season.profit < 0 ? t('ক্ষতি') : t('লাভ')"
                :value="harvested ? taka(season.profit) : '—'"
                :hint="harvested ? '' : t('ফসল তোলার পর জানা যাবে')"
                :icon="season.profit < 0 ? TrendingDown : TrendingUp"
                :tone="profitTone"
            />
            <StatTile
                :label="t('প্রতি বিঘায় লাভ')"
                :value="
                    season.profit_per_bigha !== null
                        ? taka(season.profit_per_bigha)
                        : '—'
                "
                :hint="t(':bigha বিঘা ধরে', { bigha: num(season.bigha) })"
                :icon="Coins"
                tone="pond"
            />
        </section>

        <!-- Main crop -->
        <section
            v-if="season.main"
            class="grid gap-3 rounded-xl border border-border bg-card p-4 sm:grid-cols-2"
        >
            <div>
                <p
                    class="flex items-center gap-1.5 text-[13px] text-muted-foreground"
                >
                    <Scale class="size-4 text-paddy" />
                    {{ t(':name ফলন', { name: t(season.main.name) }) }}
                </p>
                <p class="num mt-1 text-2xl font-semibold">
                    {{ t(':n মণ', { n: num(season.main.mon) }) }}
                    <span class="text-sm font-normal text-muted-foreground"
                        >({{ t(':n কেজি', { n: num(season.main.qty) }) }})</span
                    >
                </p>
                <p class="num mt-1 text-sm">
                    {{
                        t('বিঘায় :n মণ', {
                            n: num(season.main.mon_per_bigha),
                        })
                    }}
                    <span
                        v-if="season.target_mon_per_bigha"
                        class="text-muted-foreground"
                    >
                        ·
                        {{
                            t('লক্ষ্য :n মণ', {
                                n: num(season.target_mon_per_bigha),
                            })
                        }}</span
                    >
                </p>
                <template v-if="yieldPct !== null">
                    <div
                        class="mt-2 h-2 overflow-hidden rounded-full bg-muted"
                        role="progressbar"
                        :aria-valuenow="yieldPct"
                        aria-valuemin="0"
                        aria-valuemax="100"
                    >
                        <div
                            :class="[
                                'h-full rounded-full',
                                yieldPct >= 100 ? 'bg-paddy' : 'bg-turmeric',
                            ]"
                            :style="{ width: `${Math.min(100, yieldPct)}%` }"
                        />
                    </div>
                    <p class="num mt-1 text-xs text-muted-foreground">
                        {{ t('লক্ষ্যের :n%', { n: yieldPct }) }}
                    </p>
                </template>
            </div>
            <div class="grid grid-cols-2 gap-2">
                <div class="rounded-lg bg-muted/50 p-3">
                    <p class="text-xs text-muted-foreground">
                        {{ t('প্রতি কেজি আসল খরচ') }}
                    </p>
                    <p class="num text-xl font-semibold">
                        {{ perKgTaka(season.main.cost_per_kg) }}
                    </p>
                </div>
                <div class="rounded-lg bg-muted/50 p-3">
                    <p class="text-xs text-muted-foreground">
                        {{ t('প্রতি মণ আসল খরচ') }}
                    </p>
                    <p class="num text-xl font-semibold">
                        {{ perKgTaka(season.main.cost_per_mon) }}
                    </p>
                </div>
                <p class="col-span-2 text-xs text-muted-foreground">
                    {{
                        season.by_products > 0
                            ? t(
                                  'খড়ের দাম বাদে — উপজাত (খড় ইত্যাদি) :amount মোট খরচ থেকে বাদ দিয়ে হিসাব।',
                                  { amount: taka(season.by_products) },
                              )
                            : t(
                                  'খড়ের দাম বাদে — উপজাত তুললে তার দাম খরচ থেকে বাদ যাবে।',
                              )
                    }}
                </p>
            </div>
        </section>

        <!-- Cost breakdown -->
        <section class="rounded-xl border border-border bg-card p-4">
            <header class="mb-3 flex items-baseline justify-between gap-3">
                <h2 class="font-display text-lg">{{ t('খরচের হিসাব') }}</h2>
                <p class="num text-sm font-semibold text-expense">
                    {{ taka(season.cost) }}
                </p>
            </header>

            <ul v-if="season.costs.length" class="grid gap-2.5">
                <li v-for="c in season.costs" :key="c.name">
                    <div class="flex justify-between gap-3 text-sm">
                        <span class="min-w-0 truncate">{{ t(c.name) }}</span>
                        <span class="num shrink-0"
                            >{{ taka(c.amount) }}
                            <span class="text-xs text-muted-foreground"
                                >· {{ costShare(c.amount) }}%</span
                            ></span
                        >
                    </div>
                    <div
                        class="mt-1 h-1.5 overflow-hidden rounded-full bg-muted"
                    >
                        <div
                            class="h-full rounded-full bg-clay/70"
                            :style="{
                                width: `${Math.max(2, Math.round((Math.abs(c.amount) / maxCost) * 100))}%`,
                            }"
                        />
                    </div>
                </li>
            </ul>
            <div
                v-else
                class="flex flex-col items-center rounded-lg border border-dashed border-border px-4 py-6 text-center"
            >
                <span class="mb-2 rounded-full bg-clay/12 p-2.5 text-clay"
                    ><Receipt class="size-5"
                /></span>
                <p class="font-medium">{{ t('এখনো কোনো খরচ লেখা হয়নি') }}</p>
                <p class="mt-1 max-w-sm text-sm text-muted-foreground">
                    {{
                        t(
                            'চাষ, সার, বীজ, সেচ, মজুরি — মৌসুমের তারিখের মধ্যে এই প্রজেক্টে লেখা সব খরচ এখানে নিজে থেকেই জমবে।',
                        )
                    }}
                </p>
            </div>

            <dl
                v-if="season.costs.length"
                class="mt-3 grid gap-1 border-t border-border pt-2 text-sm"
            >
                <div class="flex justify-between gap-3 font-semibold">
                    <dt>{{ t('মোট খরচ') }}</dt>
                    <dd class="num text-expense">{{ taka(season.cost) }}</dd>
                </div>
                <div
                    v-if="season.cost_per_bigha !== null"
                    class="flex justify-between gap-3 text-muted-foreground"
                >
                    <dt>{{ t('প্রতি বিঘায় খরচ') }}</dt>
                    <dd class="num">{{ taka(season.cost_per_bigha) }}</dd>
                </div>
            </dl>

            <div
                v-if="can['post-entries'] && !season.closed_on"
                class="mt-4 grid gap-2 rounded-lg bg-muted/40 p-3 text-sm"
            >
                <p class="text-muted-foreground">
                    {{ t('খরচ যোগ করবেন কীভাবে:') }}
                </p>
                <div class="flex flex-wrap gap-2">
                    <Button
                        as-child
                        variant="secondary"
                        size="sm"
                        class="h-auto min-h-9 py-2 text-left leading-snug whitespace-normal"
                    >
                        <Link href="/entries/create"
                            ><Receipt />
                            {{
                                t('নতুন খরচ লিখুন — ক্যাটাগরি: :center', {
                                    center: t(season.center),
                                })
                            }}</Link
                        >
                    </Button>
                    <Button
                        as-child
                        variant="secondary"
                        size="sm"
                        class="h-auto min-h-9 py-2 text-left leading-snug whitespace-normal"
                    >
                        <Link href="/stock/move?mode=issue"
                            ><Wheat />
                            {{ t('স্টক থেকে সার/বীজ দিন (মাল খরচ)') }}</Link
                        >
                    </Button>
                </div>
            </div>
        </section>

        <!-- Harvest -->
        <section class="rounded-xl border border-border bg-card p-4">
            <header class="mb-3 flex items-center justify-between gap-3">
                <h2 class="font-display text-lg">{{ t('তোলা ফসল') }}</h2>
                <p
                    v-if="harvested"
                    class="num text-sm font-semibold text-income"
                >
                    {{ taka(season.value) }}
                </p>
            </header>

            <ul
                v-if="harvested"
                class="divide-y divide-border/70 rounded-lg border border-border/70"
            >
                <li
                    v-for="o in season.outputs"
                    :key="o.item_id"
                    class="flex items-center justify-between gap-3 px-3 py-2.5 text-sm"
                >
                    <div class="min-w-0">
                        <p class="truncate font-medium">{{ t(o.name) }}</p>
                        <p class="num text-xs text-muted-foreground">
                            {{ t(':n কেজি', { n: num(o.qty) }) }} ·
                            {{ t(':n মণ', { n: num(o.qty / KG_PER_MON) }) }}
                        </p>
                    </div>
                    <p class="num shrink-0 font-medium">{{ taka(o.value) }}</p>
                </li>
            </ul>
            <div
                v-else
                class="flex flex-col items-center rounded-lg border border-dashed border-border px-4 py-6 text-center"
            >
                <span class="mb-2 rounded-full bg-paddy/12 p-2.5 text-paddy"
                    ><Sprout class="size-5"
                /></span>
                <p class="font-medium">{{ t('এখনো ফসল তোলা হয়নি') }}</p>
                <p class="mt-1 max-w-sm text-sm text-muted-foreground">
                    {{
                        t(
                            'ফসল তুললে নিচের ফর্মে পরিমাণ আর বাজারদর লিখুন — স্টকে উঠবে আর লাভ-ক্ষতি বের হবে।',
                        )
                    }}
                </p>
            </div>

            <p
                v-if="season.sales > 0"
                class="mt-3 rounded-lg bg-pond/10 px-3 py-2 text-sm"
            >
                <span class="font-medium"
                    >{{ t('এ পর্যন্ত নগদ বিক্রি') }}:</span
                >
                <span class="num ml-1">{{ taka(season.sales) }}</span>
                <span class="mt-0.5 block text-xs text-muted-foreground">{{
                    t(
                        'শুধু জানার জন্য — লাভে আবার যোগ হয়নি, কারণ ফসল স্টকে ওঠার সময়ই তার দাম ধরা হয়েছে।',
                    )
                }}</span>
            </p>

            <template v-if="can['post-entries']">
                <Button
                    v-if="!harvestOpen"
                    class="mt-4"
                    variant="secondary"
                    @click="harvestOpen = true"
                    ><Wheat /> {{ t('ফসল তোলা') }}</Button
                >

                <form
                    v-else
                    class="mt-4 grid gap-3 rounded-lg border border-paddy/35 bg-paddy/5 p-3"
                    @submit.prevent="saveHarvest"
                >
                    <div class="flex flex-wrap items-end justify-between gap-3">
                        <h3 class="font-display text-base">
                            {{ t('ফসল তোলা') }}
                        </h3>
                        <Field
                            :label="t('তারিখ')"
                            :error="harvestErrors.date"
                            class="w-44"
                            ><DateField
                                v-model="harvest.date"
                                :min="season.started_on"
                                :max="today()"
                                required
                        /></Field>
                    </div>
                    <p class="text-xs text-muted-foreground">
                        {{
                            t(
                                'খড় বাজারদরে স্টকে উঠলে গরুকে খাওয়ালে গরুর খরচে যাবে, আর ধানের আসল খরচ থেকে বাদ যাবে।',
                            )
                        }}
                    </p>

                    <div
                        v-for="(row, i) in harvest.rows"
                        :key="row.key"
                        class="grid gap-2 rounded-lg border border-border bg-card p-3"
                    >
                        <div class="flex items-start gap-2">
                            <NSelect
                                v-model="row.item_id"
                                class="flex-1"
                                :placeholder="t('মাল বেছে নিন')"
                                ><option
                                    v-for="item in harvestItems"
                                    :key="item.id"
                                    :value="item.id"
                                >
                                    {{ t(item.name) }}
                                </option></NSelect
                            >
                            <Button
                                type="button"
                                variant="secondary"
                                size="icon"
                                :aria-label="t('বাদ দিন')"
                                :disabled="harvest.rows.length <= 1"
                                @click="removeRow(row.key)"
                                ><Trash2
                            /></Button>
                        </div>
                        <div class="grid grid-cols-2 gap-2">
                            <div class="grid gap-1">
                                <span class="text-xs text-muted-foreground">{{
                                    t('পরিমাণ')
                                }}</span>
                                <div class="flex">
                                    <Input
                                        v-model="row.qty"
                                        type="number"
                                        step="0.01"
                                        min="0"
                                        inputmode="decimal"
                                        class="rounded-r-none"
                                        :aria-label="t('পরিমাণ')"
                                    />
                                    <div
                                        class="flex h-10 shrink-0 overflow-hidden rounded-r-md border border-l-0 border-input text-xs"
                                        role="group"
                                    >
                                        <button
                                            v-for="u in ['kg', 'mon'] as const"
                                            :key="u"
                                            type="button"
                                            :class="[
                                                'px-2',
                                                row.unit === u
                                                    ? 'bg-paddy text-white'
                                                    : 'bg-card text-muted-foreground',
                                            ]"
                                            :aria-pressed="row.unit === u"
                                            @click="row.unit = u"
                                        >
                                            {{
                                                u === 'kg' ? t('কেজি') : t('মণ')
                                            }}
                                        </button>
                                    </div>
                                </div>
                            </div>
                            <div class="grid gap-1">
                                <span class="text-xs text-muted-foreground">{{
                                    row.unit === 'mon'
                                        ? t('বাজারদর (৳/মণ)')
                                        : t('বাজারদর (৳/কেজি)')
                                }}</span>
                                <Input
                                    v-model="row.rate"
                                    type="number"
                                    step="0.01"
                                    min="0"
                                    inputmode="decimal"
                                    :aria-label="t('বাজারদর')"
                                />
                            </div>
                        </div>
                        <p
                            class="num flex flex-wrap justify-between gap-x-3 text-xs text-muted-foreground"
                        >
                            <span
                                >{{ t(':n কেজি', { n: num(rowKg(row)) }) }}
                                <template v-if="itemOf(row.item_id)">
                                    ·
                                    {{
                                        kindLabels[itemOf(row.item_id)!.kind] ??
                                        ''
                                    }}
                                </template></span
                            >
                            <span class="font-medium text-foreground">{{
                                taka(rowValue(row))
                            }}</span>
                        </p>
                        <p v-if="rowError(i)" class="text-xs text-destructive">
                            {{ rowError(i) }}
                        </p>
                    </div>
                    <p
                        v-if="harvestErrors.outputs"
                        class="text-xs text-destructive"
                    >
                        {{ harvestErrors.outputs }}
                    </p>

                    <div class="flex flex-wrap gap-2">
                        <Button
                            type="button"
                            variant="outline"
                            size="sm"
                            @click="addRow"
                            ><Plus /> {{ t('আরেকটি সারি') }}</Button
                        >
                        <Button
                            type="button"
                            variant="secondary"
                            size="sm"
                            @click="itemOpen = !itemOpen"
                            ><PackagePlus /> {{ t('নতুন মাল') }}</Button
                        >
                    </div>

                    <div
                        v-if="itemOpen"
                        class="grid gap-2 rounded-lg border border-dashed border-border bg-card p-3 sm:grid-cols-[1fr_1fr_auto] sm:items-end"
                    >
                        <p class="text-xs text-muted-foreground sm:col-span-3">
                            {{
                                t(
                                    'তালিকায় নেই এমন ফসল (যেমন তিল) — নাম লিখে যোগ করুন, তারপর উপরের সারিতে বেছে নিন।',
                                )
                            }}
                        </p>
                        <Field :label="t('মালের নাম')" :error="itemErrors.name"
                            ><Input
                                v-model="newItem.name"
                                :placeholder="t('যেমন: তিল')"
                        /></Field>
                        <Field :label="t('ধরন')" :error="itemErrors.kind"
                            ><NSelect v-model="newItem.kind"
                                ><option
                                    v-for="(label, key) in kindLabels"
                                    :key="key"
                                    :value="key"
                                >
                                    {{ label }}
                                </option></NSelect
                            ></Field
                        >
                        <Button
                            type="button"
                            :disabled="itemBusy || !newItem.name.trim()"
                            @click="saveItem"
                            >{{ t('যোগ করুন') }}</Button
                        >
                    </div>

                    <div
                        class="flex flex-wrap items-center justify-between gap-3 border-t border-border pt-3"
                    >
                        <p class="text-sm">
                            {{ t('মোট দাম') }}:
                            <span class="num font-semibold text-income">{{
                                taka(harvestTotal)
                            }}</span>
                        </p>
                        <div class="flex gap-2">
                            <Button type="submit" :disabled="harvestBusy">{{
                                t('স্টকে তুলুন')
                            }}</Button>
                            <Button
                                v-if="harvested"
                                type="button"
                                variant="secondary"
                                @click="harvestOpen = false"
                                >{{ t('বাদ') }}</Button
                            >
                        </div>
                    </div>
                </form>
            </template>
        </section>
    </div>
</template>
