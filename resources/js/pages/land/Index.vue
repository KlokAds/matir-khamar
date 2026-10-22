<script setup lang="ts">
import { Head, Link, router, useForm } from '@inertiajs/vue3';
import {
    CalendarClock,
    Check,
    ChevronRight,
    LandPlot,
    Lightbulb,
    Pencil,
    Phone,
    Plus,
    Ruler,
    Sprout,
    TrendingUp,
    TriangleAlert,
    Wheat,
    X,
} from '@lucide/vue';
import { computed, ref, watch } from 'vue';
import DateField from '@/components/khamar/DateField.vue';
import Field from '@/components/khamar/Field.vue';
import NSelect from '@/components/khamar/NSelect.vue';
import PageTitle from '@/components/khamar/PageTitle.vue';
import StatTile from '@/components/khamar/StatTile.vue';
import Toggle from '@/components/khamar/Toggle.vue';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { currentLocale, t, tAll } from '@/lib/i18n';
import { centerLabel, day, taka, today, useCan } from '@/lib/khamar';
import type { Center } from '@/lib/khamar';

defineOptions({
    layout: { breadcrumbs: [{ title: 'জমি ও ফসল', href: '/land' }] },
});

type Tenure = 'own' | 'lease' | 'share';

type Plot = {
    id: number;
    name: string;
    cost_center_id: number | null;
    center: string | null;
    area_decimals: number;
    bigha: number;
    tenure: Tenure;
    tenure_label: string;
    owner_name: string | null;
    owner_phone: string | null;
    lease_from: string | null;
    lease_to: string | null;
    lease_days_left: number | null;
    yearly_rent: number | null;
    rent_per_bigha: number | null;
    notes: string | null;
    is_active: boolean;
    year: {
        year: number;
        seasons: string[];
        profit: number;
        profit_per_bigha: number | null;
    };
};

type SeasonCard = {
    id: number;
    name: string;
    crop: string;
    variety: string | null;
    center: string;
    started_on: string;
    sown_on: string | null;
    harvested_on: string | null;
    closed_on: string | null;
    days: number;
    span: string;
    bigha: number;
    area_decimals: number;
    cost: number;
    value: number;
    profit: number;
    profit_per_bigha: number | null;
    cost_per_bigha: number | null;
    target_mon_per_bigha?: number | string | null;
    main: null | {
        name: string;
        qty: number;
        unit: string;
        mon: number | null;
        mon_per_bigha: number | null;
        cost_per_kg: number | null;
        cost_per_mon: number | null;
    };
    plots: { id: number; name: string; area_decimals: number }[];
};

type Crop = { crop: string; seasons: string[]; outputs: string[] };

const props = defineProps<{
    plots: Plot[];
    seasons: SeasonCard[];
    bighaDecimals: number;
    tenures: Record<string, string>;
    centers: Center[];
    defaultCenterId: number | null;
    crops: Crop[];
}>();

const can = useCan();
const canWrite = computed(() => !!can.value['post-entries']);
const canSetup = computed(() => !!can.value['manage-setup']);

/* ---------- small helpers ---------- */
const num = (value: number | null | undefined, digits = 2): string =>
    (value ?? 0).toLocaleString('en-IN', { maximumFractionDigits: digits });

const BN_DIGITS = '০১২৩৪৫৬৭৮৯';
const localDigits = (value: number | string): string =>
    currentLocale() === 'bn'
        ? String(value).replace(/\d/g, (d) => BN_DIGITS[Number(d)])
        : String(value);

const toBigha = (decimals: number): number =>
    props.bighaDecimals > 0 ? decimals / props.bighaDecimals : 0;

const tenureTone: Record<Tenure, string> = {
    own: 'bg-paddy/12 text-paddy',
    lease: 'bg-pond/12 text-pond',
    share: 'bg-clay/15 text-clay',
};

const centerOptions = computed(() =>
    props.centers.map((c) => ({
        ...c,
        label: centerLabel({ ...c, name: t(c.name) }),
        off: !c.is_active || c.kind === 'group',
    })),
);

/* ---------- summary ---------- */
const activePlots = computed(() => props.plots.filter((p) => p.is_active));
const totalBigha = computed(() =>
    activePlots.value.reduce((sum, p) => sum + p.bigha, 0),
);
const totalDecimals = computed(() =>
    activePlots.value.reduce((sum, p) => sum + p.area_decimals, 0),
);
const openSeasons = computed(() => props.seasons.filter((s) => !s.closed_on));
const yearProfit = computed(() =>
    props.plots.reduce((sum, p) => sum + (p.year?.profit ?? 0), 0),
);
const leaseEnding = computed(() =>
    props.plots.filter(
        (p) =>
            p.is_active &&
            p.lease_days_left !== null &&
            p.lease_days_left <= 30,
    ),
);

/* ---------- bigha size ---------- */
const bighaEditing = ref(false);
const bighaForm = useForm({ decimals: props.bighaDecimals as number | string });

function saveBigha() {
    bighaForm
        .transform((data) => ({ decimals: Number(data.decimals) }))
        .put('/setup/land/bigha', {
            preserveScroll: true,
            onSuccess: () => (bighaEditing.value = false),
        });
}

/* ---------- plot form (new = 0, or the plot's id) ---------- */
type AreaUnit = 'bigha' | 'decimal';

const units: { key: AreaUnit; label: string }[] = [
    { key: 'bigha', label: t('বিঘা') },
    { key: 'decimal', label: t('শতাংশ') },
];

const editingPlot = ref<number | null>(null);
const areaUnit = ref<AreaUnit>('bigha');
const areaInput = ref<string | number>('');

const plotForm = useForm({
    name: '',
    cost_center_id: null as number | null,
    tenure: 'own' as Tenure,
    owner_name: '',
    owner_phone: '',
    lease_from: null as string | null,
    lease_to: null as string | null,
    yearly_rent: '' as string | number,
    notes: '',
    is_active: true,
});

const areaDecimals = computed((): number => {
    const value = Number(areaInput.value);

    if (!areaInput.value || Number.isNaN(value)) {
        return 0;
    }

    return areaUnit.value === 'bigha'
        ? Math.round(value * props.bighaDecimals * 100) / 100
        : value;
});

function setUnit(unit: AreaUnit) {
    if (unit === areaUnit.value) {
        return;
    }

    const decimals = areaDecimals.value;
    areaUnit.value = unit;

    if (decimals > 0) {
        areaInput.value =
            unit === 'bigha'
                ? Math.round(toBigha(decimals) * 100) / 100
                : decimals;
    }
}

function openNewPlot() {
    plotForm.reset();
    plotForm.clearErrors();
    areaUnit.value = 'bigha';
    areaInput.value = '';
    editingPlot.value = 0;
}

function startEditPlot(p: Plot) {
    plotForm.clearErrors();
    plotForm.name = p.name;
    plotForm.cost_center_id = p.cost_center_id;
    plotForm.tenure = p.tenure;
    plotForm.owner_name = p.owner_name ?? '';
    plotForm.owner_phone = p.owner_phone ?? '';
    plotForm.lease_from = p.lease_from;
    plotForm.lease_to = p.lease_to;
    plotForm.yearly_rent = p.yearly_rent !== null ? p.yearly_rent / 100 : '';
    plotForm.notes = p.notes ?? '';
    plotForm.is_active = p.is_active;
    areaUnit.value = 'decimal';
    areaInput.value = p.area_decimals;
    editingPlot.value = p.id;
}

function savePlot() {
    const id = editingPlot.value;
    const leased = plotForm.tenure !== 'own';

    plotForm.transform((data) => ({
        ...data,
        area_decimals: areaDecimals.value,
        owner_name: leased ? data.owner_name.trim() || null : null,
        owner_phone: leased ? data.owner_phone.trim() || null : null,
        lease_from: leased ? data.lease_from || null : null,
        lease_to: leased ? data.lease_to || null : null,
        yearly_rent:
            leased && data.yearly_rent !== '' ? Number(data.yearly_rent) : null,
        notes: data.notes.trim() || null,
    }));

    const options = {
        preserveScroll: true,
        onSuccess: () => {
            editingPlot.value = null;
        },
    };

    if (id) {
        plotForm.put(`/land/plots/${id}`, options);
    } else {
        plotForm.post('/land/plots', options);
    }
}

// The list shows the "new plot" form in the first slot (null) while it is open.
const plotRows = computed<(Plot | null)[]>(() =>
    editingPlot.value === 0 ? [null, ...props.plots] : props.plots,
);

const switching = ref<number | null>(null);

function setActive(p: Plot, value: boolean) {
    switching.value = p.id;
    router.put(
        `/land/plots/${p.id}`,
        {
            name: p.name,
            cost_center_id: p.cost_center_id,
            area_decimals: p.area_decimals,
            tenure: p.tenure,
            owner_name: p.owner_name,
            owner_phone: p.owner_phone,
            lease_from: p.lease_from,
            lease_to: p.lease_to,
            yearly_rent: p.yearly_rent !== null ? p.yearly_rent / 100 : null,
            notes: p.notes,
            is_active: value,
        },
        {
            preserveScroll: true,
            onFinish: () => (switching.value = null),
        },
    );
}

function leaseNote(p: Plot): { text: string; tone: string } | null {
    const left = p.lease_days_left;

    if (left === null) {
        return null;
    }

    if (left < 0) {
        return {
            text: t('লিজ শেষ হয়েছে :n দিন আগে', { n: -left }),
            tone: 'bg-destructive/10 text-destructive',
        };
    }

    if (left <= 30) {
        return {
            text: t('লিজ শেষ হতে :n দিন বাকি', { n: left }),
            tone: 'bg-turmeric/25 text-soil',
        };
    }

    return {
        text: t('আরও :n দিন', { n: left }),
        tone: 'bg-muted text-muted-foreground',
    };
}

/* ---------- new season ---------- */
const seasonOpen = ref(false);
const OTHER = '__other__';
const cropPick = ref<string>(props.crops[0]?.crop ?? OTHER);
const otherCrop = ref('');
const seasonPick = ref<string | null>(null);
const nameTouched = ref(false);
const plotAreas = ref<Record<number, string | number>>({});

const seasonForm = useForm({
    cost_center_id: props.defaultCenterId as number | null,
    name: '',
    variety: '',
    started_on: today(),
    sown_on: null as string | null,
    target_mon_per_bigha: '' as string | number,
    notes: '',
});

const cropName = computed(() =>
    cropPick.value === OTHER ? otherCrop.value.trim() : cropPick.value,
);
const cropSeasons = computed(
    () => props.crops.find((c) => c.crop === cropPick.value)?.seasons ?? [],
);

function pickCrop(crop: string) {
    cropPick.value = crop;
    const list = props.crops.find((c) => c.crop === crop)?.seasons ?? [];
    seasonPick.value = list.length === 1 ? list[0] : null;
}

const autoName = computed(() => {
    const year = localDigits((seasonForm.started_on || today()).slice(0, 4));
    const base = seasonPick.value ? t(seasonPick.value) : t(cropName.value);

    return base ? `${base} ${year}` : '';
});

watch(
    autoName,
    (name) => {
        if (!nameTouched.value) {
            seasonForm.name = name;
        }
    },
    { immediate: true },
);

function onNameInput() {
    nameTouched.value = seasonForm.name.trim() !== '';
}

const pickablePlots = computed(() => props.plots.filter((p) => p.is_active));
const pickedIds = computed(() =>
    Object.keys(plotAreas.value).map((id) => Number(id)),
);

function togglePlot(p: Plot) {
    const next = { ...plotAreas.value };

    if (p.id in next) {
        delete next[p.id];
    } else {
        next[p.id] = p.area_decimals;
    }

    plotAreas.value = next;
}

const pickedDecimals = computed(() =>
    pickedIds.value.reduce(
        (sum, id) => sum + (Number(plotAreas.value[id]) || 0),
        0,
    ),
);

function openSeasonForm() {
    seasonOpen.value = !seasonOpen.value;

    if (seasonOpen.value && pickedIds.value.length === 0) {
        pickCrop(cropPick.value);
    }
}

const seasonErrors = computed(
    () => seasonForm.errors as Record<string, string | undefined>,
);
const plotsError = computed(() => {
    const errors = seasonErrors.value;
    const key = Object.keys(errors).find((k) => k.startsWith('plots'));

    return key ? errors[key] : undefined;
});

const canSaveSeason = computed(
    () =>
        !seasonForm.processing &&
        cropName.value !== '' &&
        seasonForm.name.trim() !== '' &&
        seasonForm.cost_center_id !== null &&
        pickedIds.value.length > 0,
);

function saveSeason() {
    seasonForm
        .transform((data) => ({
            ...data,
            crop: cropName.value,
            name: data.name.trim(),
            variety: data.variety.trim() || null,
            sown_on: data.sown_on || null,
            harvested_on: null,
            closed_on: null,
            target_mon_per_bigha:
                data.target_mon_per_bigha === ''
                    ? null
                    : Number(data.target_mon_per_bigha),
            notes: data.notes.trim() || null,
            plots: pickedIds.value.map((id) => ({
                plot_id: id,
                area_decimals: Number(plotAreas.value[id]) || 0,
            })),
        }))
        .post('/land/seasons', {
            preserveScroll: true,
            onSuccess: () => {
                seasonOpen.value = false;
                seasonForm.reset();
                plotAreas.value = {};
                nameTouched.value = false;
            },
        });
}

/* ---------- season cards ---------- */
const sortedSeasons = computed(() =>
    [...props.seasons].sort((a, b) => {
        const open = Number(!!a.closed_on) - Number(!!b.closed_on);

        return open !== 0 ? open : b.started_on.localeCompare(a.started_on);
    }),
);

function seasonStatus(s: SeasonCard): { label: string; tone: string } {
    if (s.closed_on) {
        return { label: t('শেষ'), tone: 'bg-muted text-muted-foreground' };
    }

    if (s.harvested_on) {
        return { label: t('তোলা হয়েছে'), tone: 'bg-turmeric/25 text-soil' };
    }

    return { label: t('চলছে'), tone: 'bg-paddy/12 text-paddy' };
}

const targetOf = (s: SeasonCard): number | null =>
    s.target_mon_per_bigha === null ||
    s.target_mon_per_bigha === undefined ||
    s.target_mon_per_bigha === ''
        ? null
        : Number(s.target_mon_per_bigha);

const perUnitTaka = (value: number | null): string =>
    value === null ? '—' : taka(Math.round(value * 100));
</script>

<template>
    <Head :title="t('জমি ও ফসল')" />

    <div class="flex flex-1 flex-col p-4 md:p-6">
        <PageTitle
            :title="t('জমি ও ফসল')"
            :subtitle="
                t(
                    'কোন জমিতে কী ফসল, কত খরচ, কত ফলন — প্রতি বিঘায় লাভ আর প্রতি কেজির আসল খরচ।',
                )
            "
        >
            <template v-if="canWrite" #actions>
                <Button variant="outline" @click="openNewPlot"
                    ><LandPlot /> {{ t('নতুন জমি') }}</Button
                >
                <Button @click="openSeasonForm"
                    ><Plus /> {{ t('নতুন মৌসুম') }}</Button
                >
            </template>
        </PageTitle>

        <!-- Summary -->
        <div class="mb-6 grid grid-cols-2 gap-3 lg:grid-cols-4">
            <StatTile
                :label="t('মোট জমি')"
                :value="t(':n বিঘা', { n: num(totalBigha) })"
                :hint="t(':n শতাংশ', { n: num(totalDecimals) })"
                :icon="LandPlot"
                tone="soil"
            />
            <StatTile
                :label="t('চলতি মৌসুম')"
                :value="t(':nটি', { n: openSeasons.length })"
                :hint="t('মোট :n মৌসুম', { n: seasons.length })"
                :icon="Sprout"
                tone="paddy"
            />
            <StatTile
                :label="t('এ বছরের ফসলে লাভ')"
                :value="taka(yearProfit)"
                :icon="TrendingUp"
                :tone="yearProfit < 0 ? 'clay' : 'pond'"
            />
            <StatTile
                :label="t('লিজ শেষ হচ্ছে')"
                :value="t(':nটি', { n: leaseEnding.length })"
                :hint="t('৩০ দিনের মধ্যে')"
                :icon="CalendarClock"
                :tone="leaseEnding.length ? 'turmeric' : 'soil'"
            />
        </div>

        <!-- New season -->
        <form
            v-if="seasonOpen && canWrite"
            class="mb-6 rounded-xl border border-border bg-card p-4"
            @submit.prevent="saveSeason"
        >
            <div class="mb-3 flex items-center justify-between gap-2">
                <h2 class="flex items-center gap-2 font-display text-lg">
                    <Sprout class="size-5 text-paddy" />
                    {{ t('নতুন মৌসুম') }}
                </h2>
                <button
                    type="button"
                    class="rounded-md p-1.5 text-muted-foreground hover:bg-accent"
                    :aria-label="t('বন্ধ করুন')"
                    @click="seasonOpen = false"
                >
                    <X class="size-4" />
                </button>
            </div>

            <div class="grid gap-4 md:grid-cols-2">
                <div class="grid content-start gap-3">
                    <Field :label="t('ফসল')" :error="seasonErrors.crop">
                        <div class="flex flex-wrap gap-1.5">
                            <button
                                v-for="c in crops"
                                :key="c.crop"
                                type="button"
                                :class="[
                                    'rounded-full border px-3 py-1.5 text-sm transition-colors',
                                    cropPick === c.crop
                                        ? 'border-primary bg-primary text-primary-foreground'
                                        : 'border-input bg-card hover:bg-accent',
                                ]"
                                @click="pickCrop(c.crop)"
                            >
                                {{ t(c.crop) }}
                            </button>
                            <button
                                type="button"
                                :class="[
                                    'rounded-full border px-3 py-1.5 text-sm transition-colors',
                                    cropPick === OTHER
                                        ? 'border-primary bg-primary text-primary-foreground'
                                        : 'border-dashed border-input bg-card hover:bg-accent',
                                ]"
                                @click="pickCrop(OTHER)"
                            >
                                {{ t('অন্য ফসল') }}
                            </button>
                        </div>
                    </Field>

                    <Field v-if="cropPick === OTHER" :label="t('ফসলের নাম')">
                        <Input
                            v-model="otherCrop"
                            class="h-10"
                            :placeholder="t('যেমন: পেঁয়াজ')"
                        />
                    </Field>

                    <Field v-if="cropSeasons.length" :label="t('মৌসুম')">
                        <div class="flex flex-wrap gap-1.5">
                            <button
                                v-for="s in cropSeasons"
                                :key="s"
                                type="button"
                                :class="[
                                    'rounded-full border px-3 py-1.5 text-xs transition-colors',
                                    seasonPick === s
                                        ? 'border-paddy bg-paddy/12 text-paddy'
                                        : 'border-input bg-card hover:bg-accent',
                                ]"
                                @click="
                                    seasonPick = seasonPick === s ? null : s
                                "
                            >
                                {{ t(s) }}
                            </button>
                        </div>
                    </Field>

                    <div class="grid grid-cols-2 gap-3">
                        <Field
                            :label="t('মৌসুমের নাম')"
                            :error="seasonErrors.name"
                        >
                            <Input
                                v-model="seasonForm.name"
                                class="h-10"
                                required
                                @input="onNameInput"
                            />
                        </Field>
                        <Field :label="t('জাত')" :error="seasonErrors.variety">
                            <Input
                                v-model="seasonForm.variety"
                                class="h-10"
                                :placeholder="t('যেমন: ব্রি ধান ৪৯')"
                            />
                        </Field>
                    </div>

                    <Field
                        :label="t('প্রজেক্ট')"
                        :error="seasonErrors.cost_center_id"
                        :hint="
                            t(
                                'আলুর মতো নতুন ফসলের জন্য আলাদা প্রজেক্ট খুলতে পারেন — প্রজেক্ট সাজান থেকে।',
                            )
                        "
                    >
                        <NSelect
                            v-model="seasonForm.cost_center_id"
                            :placeholder="t('প্রজেক্ট বাছুন')"
                        >
                            <option
                                v-for="c in centerOptions"
                                :key="c.id"
                                :value="c.id"
                                :disabled="c.off"
                            >
                                {{ c.label }}
                            </option>
                        </NSelect>
                    </Field>
                    <Link
                        v-if="canSetup"
                        href="/setup/centers"
                        class="-mt-1 text-xs font-medium text-paddy hover:underline"
                        >{{ t('প্রজেক্ট সাজান') }} →</Link
                    >

                    <div class="grid grid-cols-2 gap-3">
                        <Field
                            :label="t('জমি তৈরি শুরু')"
                            :error="seasonErrors.started_on"
                        >
                            <DateField
                                v-model="seasonForm.started_on"
                                :max="today()"
                                class="h-10"
                                required
                            />
                        </Field>
                        <Field
                            :label="t('বোনা / রোপণ')"
                            :error="seasonErrors.sown_on"
                        >
                            <DateField
                                v-model="seasonForm.sown_on"
                                :min="seasonForm.started_on"
                                class="h-10"
                                clearable
                            />
                        </Field>
                    </div>

                    <Field
                        :label="t('লক্ষ্য (মণ/বিঘা)')"
                        :error="seasonErrors.target_mon_per_bigha"
                        :hint="t('ফাঁকা রাখলে তুলনা দেখাবে না')"
                    >
                        <Input
                            v-model="seasonForm.target_mon_per_bigha"
                            type="number"
                            min="0"
                            step="0.1"
                            inputmode="decimal"
                            class="h-10"
                            :placeholder="t('যেমন: ২০')"
                        />
                    </Field>
                </div>

                <div class="grid content-start gap-3">
                    <Field :label="t('কোন জমিতে')" :error="plotsError">
                        <div
                            class="rounded-lg border border-border bg-background/60"
                        >
                            <p
                                class="flex items-center justify-between border-b border-border px-3 py-2 text-xs text-muted-foreground"
                            >
                                <span>{{ t('জমি (শতাংশে)') }}</span>
                                <span class="num font-medium text-foreground">{{
                                    t(':d শতাংশ · :b বিঘা', {
                                        d: num(pickedDecimals),
                                        b: num(toBigha(pickedDecimals)),
                                    })
                                }}</span>
                            </p>
                            <ul
                                v-if="pickablePlots.length"
                                class="max-h-80 divide-y divide-border/60 overflow-y-auto"
                            >
                                <li
                                    v-for="p in pickablePlots"
                                    :key="p.id"
                                    :class="[
                                        'flex items-center gap-2 px-3 py-2',
                                        p.id in plotAreas && 'bg-paddy/8',
                                    ]"
                                >
                                    <label
                                        class="flex min-w-0 flex-1 cursor-pointer items-center gap-2 text-sm"
                                    >
                                        <input
                                            type="checkbox"
                                            class="size-4 shrink-0 accent-paddy"
                                            :checked="p.id in plotAreas"
                                            @change="togglePlot(p)"
                                        />
                                        <span class="min-w-0">
                                            <span class="block truncate">{{
                                                p.name
                                            }}</span>
                                            <span
                                                class="num block text-[11px] text-muted-foreground"
                                                >{{
                                                    t(':n বিঘা', {
                                                        n: num(p.bigha),
                                                    })
                                                }}</span
                                            >
                                        </span>
                                    </label>
                                    <Input
                                        v-if="p.id in plotAreas"
                                        v-model="plotAreas[p.id]"
                                        type="number"
                                        min="0.5"
                                        step="0.01"
                                        inputmode="decimal"
                                        class="h-10 w-24 shrink-0 text-right"
                                        :aria-label="t('শতাংশ')"
                                    />
                                </li>
                            </ul>
                            <p
                                v-else
                                class="px-3 py-6 text-center text-sm text-muted-foreground"
                            >
                                {{ t('আগে একটি জমি যোগ করুন।') }}
                            </p>
                        </div>
                    </Field>

                    <Field
                        :label="t('নোট (ইচ্ছামতো)')"
                        :error="seasonErrors.notes"
                    >
                        <Input v-model="seasonForm.notes" class="h-10" />
                    </Field>

                    <Button type="submit" :disabled="!canSaveSeason">
                        <Check class="size-4" />
                        {{ t('মৌসুম শুরু করুন') }}
                    </Button>
                </div>
            </div>
        </form>

        <div class="grid gap-6 lg:grid-cols-5">
            <!-- Plots -->
            <section class="min-w-0 space-y-3 lg:col-span-2">
                <div class="flex flex-wrap items-center justify-between gap-2">
                    <h2 class="flex items-center gap-2 font-display text-lg">
                        <LandPlot class="size-5 text-soil" />
                        {{ t('জমির তালিকা') }}
                    </h2>

                    <!-- Bigha size -->
                    <div
                        v-if="!bighaEditing"
                        class="flex items-center gap-1 text-xs text-muted-foreground"
                    >
                        <Ruler class="size-3.5" />
                        <span class="num">{{
                            t('১ বিঘা = :n শতাংশ', { n: num(bighaDecimals) })
                        }}</span>
                        <button
                            v-if="canSetup"
                            type="button"
                            class="rounded p-1 hover:bg-accent hover:text-foreground"
                            :aria-label="t('বিঘার মাপ বদলান')"
                            @click="bighaEditing = true"
                        >
                            <Pencil class="size-3.5" />
                        </button>
                    </div>
                    <form
                        v-else
                        class="flex items-center gap-1.5 text-xs"
                        @submit.prevent="saveBigha"
                    >
                        <span>{{ t('১ বিঘা =') }}</span>
                        <Input
                            v-model="bighaForm.decimals"
                            type="number"
                            min="10"
                            max="200"
                            step="0.01"
                            class="h-10 w-20"
                        />
                        <span>{{ t('শতাংশ') }}</span>
                        <Button
                            type="submit"
                            size="sm"
                            :disabled="bighaForm.processing"
                            ><Check class="size-4"
                        /></Button>
                        <Button
                            type="button"
                            size="sm"
                            variant="ghost"
                            :aria-label="t('বাতিল')"
                            @click="bighaEditing = false"
                            ><X class="size-4"
                        /></Button>
                    </form>
                </div>
                <p
                    v-if="bighaForm.errors.decimals"
                    class="text-xs text-destructive"
                >
                    {{ bighaForm.errors.decimals }}
                </p>

                <template v-for="p in plotRows" :key="p?.id ?? 0">
                    <!-- Plot form (new or edit) -->
                    <form
                        v-if="
                            editingPlot !== null && (p?.id ?? 0) === editingPlot
                        "
                        class="grid gap-3 rounded-xl border border-primary/40 bg-card p-4"
                        @submit.prevent="savePlot"
                    >
                        <h3 class="font-display text-base">
                            {{ p ? t('জমির তথ্য বদলান') : t('নতুন জমি') }}
                        </h3>

                        <Field
                            :label="t('জমির নাম')"
                            :error="plotForm.errors.name"
                        >
                            <Input
                                v-model="plotForm.name"
                                class="h-10"
                                :placeholder="t('যেমন: পুকুর পাড়ের জমি')"
                                required
                            />
                        </Field>

                        <Field
                            :label="t('পরিমাণ')"
                            :error="
                                (plotForm.errors as Record<string, string>)
                                    .area_decimals
                            "
                            :hint="
                                areaDecimals > 0
                                    ? areaUnit === 'bigha'
                                        ? t('= :n শতাংশ', {
                                              n: num(areaDecimals),
                                          })
                                        : t('= :n বিঘা', {
                                              n: num(toBigha(areaDecimals)),
                                          })
                                    : undefined
                            "
                        >
                            <div class="flex gap-2">
                                <Input
                                    v-model="areaInput"
                                    type="number"
                                    min="0"
                                    step="0.01"
                                    inputmode="decimal"
                                    class="h-10 min-w-0 flex-1"
                                    required
                                />
                                <div
                                    class="grid shrink-0 grid-cols-2 gap-1 rounded-lg bg-muted p-1"
                                >
                                    <button
                                        v-for="u in units"
                                        :key="u.key"
                                        type="button"
                                        :class="[
                                            'h-8 rounded-md px-2.5 text-xs font-medium transition-colors',
                                            areaUnit === u.key
                                                ? 'bg-card text-foreground shadow-sm'
                                                : 'text-muted-foreground hover:text-foreground',
                                        ]"
                                        @click="setUnit(u.key)"
                                    >
                                        {{ u.label }}
                                    </button>
                                </div>
                            </div>
                        </Field>

                        <div class="grid grid-cols-2 gap-3">
                            <Field
                                :label="t('মালিকানা')"
                                :error="plotForm.errors.tenure"
                            >
                                <NSelect v-model="plotForm.tenure">
                                    <option
                                        v-for="(label, key) in tAll(tenures)"
                                        :key="key"
                                        :value="key"
                                    >
                                        {{ label }}
                                    </option>
                                </NSelect>
                            </Field>
                            <Field
                                :label="t('প্রজেক্ট')"
                                :error="plotForm.errors.cost_center_id"
                            >
                                <NSelect v-model="plotForm.cost_center_id">
                                    <option :value="null">
                                        {{ t('কোনোটি নয়') }}
                                    </option>
                                    <option
                                        v-for="c in centerOptions"
                                        :key="c.id"
                                        :value="c.id"
                                        :disabled="c.off"
                                    >
                                        {{ c.label }}
                                    </option>
                                </NSelect>
                            </Field>
                        </div>

                        <template v-if="plotForm.tenure !== 'own'">
                            <div class="grid grid-cols-2 gap-3">
                                <Field
                                    :label="t('জমির মালিক')"
                                    :error="plotForm.errors.owner_name"
                                >
                                    <Input
                                        v-model="plotForm.owner_name"
                                        class="h-10"
                                    />
                                </Field>
                                <Field
                                    :label="t('মালিকের ফোন')"
                                    :error="plotForm.errors.owner_phone"
                                >
                                    <Input
                                        v-model="plotForm.owner_phone"
                                        type="tel"
                                        inputmode="tel"
                                        class="h-10"
                                        :placeholder="t('০১৭…')"
                                    />
                                </Field>
                            </div>
                            <div class="grid grid-cols-2 gap-3">
                                <Field
                                    :label="t('লিজ শুরু')"
                                    :error="plotForm.errors.lease_from"
                                >
                                    <DateField
                                        v-model="plotForm.lease_from"
                                        class="h-10"
                                        clearable
                                    />
                                </Field>
                                <Field
                                    :label="t('লিজ শেষ')"
                                    :error="plotForm.errors.lease_to"
                                >
                                    <DateField
                                        v-model="plotForm.lease_to"
                                        :min="plotForm.lease_from ?? undefined"
                                        class="h-10"
                                        clearable
                                    />
                                </Field>
                            </div>
                            <Field
                                :label="t('বছরে খাজনা / লিজের টাকা (৳)')"
                                :error="plotForm.errors.yearly_rent"
                            >
                                <Input
                                    v-model="plotForm.yearly_rent"
                                    type="number"
                                    min="0"
                                    step="1"
                                    inputmode="decimal"
                                    class="h-10"
                                />
                            </Field>
                        </template>

                        <Field
                            :label="t('নোট (ইচ্ছামতো)')"
                            :error="plotForm.errors.notes"
                        >
                            <Input v-model="plotForm.notes" class="h-10" />
                        </Field>

                        <div class="flex gap-2">
                            <Button
                                type="submit"
                                class="flex-1"
                                :disabled="
                                    plotForm.processing ||
                                    !plotForm.name.trim() ||
                                    areaDecimals <= 0
                                "
                            >
                                <Check class="size-4" /> {{ t('সংরক্ষণ') }}
                            </Button>
                            <Button
                                type="button"
                                variant="outline"
                                @click="editingPlot = null"
                                >{{ t('বাতিল') }}</Button
                            >
                        </div>
                    </form>

                    <!-- Plot card -->
                    <article
                        v-else-if="p"
                        :class="[
                            'rounded-xl border border-border bg-card p-4 transition-opacity',
                            !p.is_active && 'opacity-60',
                        ]"
                    >
                        <div class="flex items-start justify-between gap-3">
                            <div class="min-w-0">
                                <h3
                                    class="truncate text-base leading-snug font-semibold"
                                >
                                    {{ p.name }}
                                </h3>
                                <p class="num mt-0.5 text-sm">
                                    <span class="font-semibold">{{
                                        t(':n বিঘা', { n: num(p.bigha) })
                                    }}</span>
                                    <span
                                        class="ml-1 text-xs text-muted-foreground"
                                        >({{
                                            t(':n শতাংশ', {
                                                n: num(p.area_decimals),
                                            })
                                        }})</span
                                    >
                                </p>
                            </div>
                            <div class="flex shrink-0 items-center gap-1">
                                <span
                                    :class="[
                                        'rounded-full px-2 py-0.5 text-[11px] font-medium',
                                        tenureTone[p.tenure] ??
                                            'bg-muted text-foreground',
                                    ]"
                                    >{{ t(p.tenure_label) }}</span
                                >
                                <button
                                    v-if="canWrite"
                                    type="button"
                                    class="rounded-md p-1.5 text-muted-foreground hover:bg-accent hover:text-foreground"
                                    :aria-label="t('সম্পাদনা')"
                                    @click="startEditPlot(p)"
                                >
                                    <Pencil class="size-4" />
                                </button>
                            </div>
                        </div>

                        <dl class="mt-3 grid gap-1.5 text-xs">
                            <div v-if="p.center" class="flex gap-1.5">
                                <dt class="text-muted-foreground">
                                    {{ t('প্রজেক্ট:') }}
                                </dt>
                                <dd class="truncate">{{ t(p.center) }}</dd>
                            </div>
                            <div
                                v-if="p.owner_name || p.owner_phone"
                                class="flex flex-wrap items-center gap-1.5"
                            >
                                <dt class="text-muted-foreground">
                                    {{ t('মালিক:') }}
                                </dt>
                                <dd>{{ p.owner_name }}</dd>
                                <a
                                    v-if="p.owner_phone"
                                    :href="`tel:${p.owner_phone}`"
                                    class="num inline-flex items-center gap-1 rounded-full bg-pond/10 px-2 py-0.5 text-pond hover:bg-pond/20"
                                    ><Phone class="size-3" />{{
                                        p.owner_phone
                                    }}</a
                                >
                            </div>
                            <div
                                v-if="p.lease_from || p.lease_to"
                                class="flex flex-wrap items-center gap-1.5"
                            >
                                <dt class="text-muted-foreground">
                                    {{ t('লিজ:') }}
                                </dt>
                                <dd class="num">
                                    {{ day(p.lease_from) }} –
                                    {{ day(p.lease_to) }}
                                </dd>
                                <span
                                    v-if="leaseNote(p)"
                                    :class="[
                                        'num inline-flex items-center gap-1 rounded-full px-2 py-0.5 font-medium',
                                        leaseNote(p)!.tone,
                                    ]"
                                    ><TriangleAlert
                                        v-if="(p.lease_days_left ?? 99) <= 30"
                                        class="size-3"
                                    />{{ leaseNote(p)!.text }}</span
                                >
                            </div>
                            <div
                                v-if="p.yearly_rent !== null"
                                class="flex flex-wrap gap-1.5"
                            >
                                <dt class="text-muted-foreground">
                                    {{ t('বছরে খাজনা:') }}
                                </dt>
                                <dd class="num">
                                    {{ taka(p.yearly_rent) }}
                                    <span
                                        v-if="p.rent_per_bigha !== null"
                                        class="text-muted-foreground"
                                        >·
                                        {{
                                            t(':amount / বিঘা', {
                                                amount: taka(p.rent_per_bigha),
                                            })
                                        }}</span
                                    >
                                </dd>
                            </div>
                        </dl>

                        <div
                            class="mt-3 flex items-end justify-between gap-3 border-t border-border/70 pt-3"
                        >
                            <div class="min-w-0 text-xs">
                                <p class="text-muted-foreground">
                                    {{
                                        t(':year সালে', {
                                            year: localDigits(p.year.year),
                                        })
                                    }}
                                </p>
                                <p
                                    v-if="p.year.seasons.length"
                                    class="mt-0.5 font-medium"
                                >
                                    {{
                                        p.year.seasons
                                            .map((s) => t(s))
                                            .join(' → ')
                                    }}
                                </p>
                                <p v-else class="mt-0.5 text-muted-foreground">
                                    {{ t('এখনো কোনো ফসল নেই') }}
                                </p>
                                <p
                                    v-if="p.year.profit_per_bigha !== null"
                                    :class="[
                                        'num mt-0.5 font-semibold',
                                        p.year.profit_per_bigha < 0
                                            ? 'text-destructive'
                                            : 'text-paddy',
                                    ]"
                                >
                                    {{
                                        t('বিঘায় লাভ :amount', {
                                            amount: taka(
                                                p.year.profit_per_bigha,
                                            ),
                                        })
                                    }}
                                </p>
                            </div>
                            <Toggle
                                v-if="canWrite"
                                :model-value="p.is_active"
                                :disabled="switching === p.id"
                                class="shrink-0"
                                @update:model-value="setActive(p, $event)"
                            />
                        </div>
                    </article>
                </template>

                <p
                    v-if="!plots.length && editingPlot !== 0"
                    class="flex flex-col items-center gap-2 rounded-xl border border-dashed border-border py-10 text-center text-sm text-muted-foreground"
                >
                    <LandPlot class="size-6 opacity-60" />
                    {{ t('এখনো কোনো জমি যোগ করা হয়নি।') }}
                </p>
            </section>

            <!-- Seasons -->
            <section class="min-w-0 space-y-3 lg:col-span-3">
                <h2 class="flex items-center gap-2 font-display text-lg">
                    <Wheat class="size-5 text-turmeric" />
                    {{ t('মৌসুম') }}
                </h2>

                <div v-if="sortedSeasons.length" class="grid gap-3">
                    <Link
                        v-for="s in sortedSeasons"
                        :key="s.id"
                        :href="`/land/seasons/${s.id}`"
                        :class="[
                            'group block rounded-xl border border-border bg-card p-4 transition-colors hover:border-primary/40 hover:bg-accent/40',
                            s.closed_on && 'opacity-85',
                        ]"
                    >
                        <div class="flex items-start justify-between gap-3">
                            <div class="min-w-0">
                                <p class="text-xs text-muted-foreground">
                                    {{ t(s.crop)
                                    }}<template v-if="s.variety">
                                        · {{ s.variety }}</template
                                    >
                                </p>
                                <h3
                                    class="truncate text-base leading-snug font-semibold"
                                >
                                    {{ s.name }}
                                </h3>
                                <p
                                    class="num mt-0.5 text-xs text-muted-foreground"
                                >
                                    {{ day(s.started_on) }} · {{ t(s.span) }} ·
                                    {{ t(':n বিঘা', { n: num(s.bigha) }) }}
                                </p>
                            </div>
                            <div class="flex shrink-0 items-center gap-1">
                                <span
                                    :class="[
                                        'rounded-full px-2 py-0.5 text-[11px] font-medium',
                                        seasonStatus(s).tone,
                                    ]"
                                    >{{ seasonStatus(s).label }}</span
                                >
                                <ChevronRight
                                    class="size-4 text-muted-foreground transition-transform group-hover:translate-x-0.5"
                                />
                            </div>
                        </div>

                        <div
                            class="mt-3 grid grid-cols-3 gap-2 rounded-lg bg-muted/50 p-2.5 text-center"
                        >
                            <div class="min-w-0">
                                <p class="text-[11px] text-muted-foreground">
                                    {{ t('খরচ') }}
                                </p>
                                <p class="num truncate text-sm font-semibold">
                                    {{ taka(s.cost) }}
                                </p>
                            </div>
                            <div class="min-w-0">
                                <p class="text-[11px] text-muted-foreground">
                                    {{ t('ফসলের দাম') }}
                                </p>
                                <p class="num truncate text-sm font-semibold">
                                    {{ taka(s.value) }}
                                </p>
                            </div>
                            <div class="min-w-0">
                                <p class="text-[11px] text-muted-foreground">
                                    {{ s.profit < 0 ? t('ক্ষতি') : t('লাভ') }}
                                </p>
                                <p
                                    :class="[
                                        'num truncate text-sm font-semibold',
                                        s.profit < 0
                                            ? 'text-destructive'
                                            : 'text-paddy',
                                    ]"
                                >
                                    {{ taka(s.profit) }}
                                </p>
                            </div>
                        </div>

                        <div
                            class="mt-2.5 flex flex-wrap gap-x-4 gap-y-1 text-xs"
                        >
                            <span
                                v-if="s.profit_per_bigha !== null"
                                class="num"
                            >
                                <span class="text-muted-foreground">{{
                                    t('বিঘায় লাভ:')
                                }}</span>
                                <span
                                    :class="[
                                        'ml-1 font-semibold',
                                        s.profit_per_bigha < 0
                                            ? 'text-destructive'
                                            : 'text-foreground',
                                    ]"
                                    >{{ taka(s.profit_per_bigha) }}</span
                                >
                            </span>
                            <span
                                v-else-if="s.cost_per_bigha !== null"
                                class="num"
                            >
                                <span class="text-muted-foreground">{{
                                    t('বিঘায় খরচ:')
                                }}</span>
                                <span class="ml-1 font-semibold">{{
                                    taka(s.cost_per_bigha)
                                }}</span>
                            </span>
                            <span
                                v-if="
                                    s.main?.mon_per_bigha != null ||
                                    targetOf(s) !== null
                                "
                                class="num"
                            >
                                <span class="text-muted-foreground">{{
                                    t('ফলন:')
                                }}</span>
                                <span
                                    :class="[
                                        'ml-1 font-semibold',
                                        s.main?.mon_per_bigha != null &&
                                            targetOf(s) !== null &&
                                            (s.main.mon_per_bigha >=
                                            targetOf(s)!
                                                ? 'text-paddy'
                                                : 'text-clay'),
                                    ]"
                                    >{{
                                        s.main?.mon_per_bigha != null
                                            ? t(':n মণ/বিঘা', {
                                                  n: num(s.main.mon_per_bigha),
                                              })
                                            : '—'
                                    }}</span
                                >
                                <span
                                    v-if="targetOf(s) !== null"
                                    class="text-muted-foreground"
                                >
                                    ·
                                    {{
                                        t('লক্ষ্য :n', {
                                            n: num(targetOf(s)),
                                        })
                                    }}</span
                                >
                            </span>
                            <span
                                v-if="s.main?.cost_per_kg != null"
                                class="num"
                            >
                                <span class="text-muted-foreground">{{
                                    t(':name আসল খরচ:', {
                                        name: t(s.main.name),
                                    })
                                }}</span>
                                <span class="ml-1 font-semibold">{{
                                    t(':amount/কেজি', {
                                        amount: perUnitTaka(s.main.cost_per_kg),
                                    })
                                }}</span>
                                <span
                                    v-if="s.main.cost_per_mon != null"
                                    class="text-muted-foreground"
                                >
                                    ·
                                    {{
                                        t(':amount/মণ', {
                                            amount: perUnitTaka(
                                                s.main.cost_per_mon,
                                            ),
                                        })
                                    }}</span
                                >
                            </span>
                        </div>

                        <p
                            v-if="s.plots.length"
                            class="mt-2 truncate text-[11px] text-muted-foreground"
                        >
                            {{ s.plots.map((pl) => pl.name).join(', ') }}
                        </p>
                    </Link>
                </div>
                <p
                    v-else
                    class="flex flex-col items-center gap-2 rounded-xl border border-dashed border-border py-10 text-center text-sm text-muted-foreground"
                >
                    <Sprout class="size-6 opacity-60" />
                    {{ t('এখনো কোনো মৌসুম শুরু হয়নি।') }}
                </p>
            </section>
        </div>

        <p
            class="mt-6 flex items-start gap-2 rounded-lg bg-turmeric/15 px-3 py-2.5 text-xs text-soil"
        >
            <Lightbulb class="mt-0.5 size-4 shrink-0" />
            {{
                t(
                    'ধান তোলার পর খড়ও স্টকে তুলুন — গরুর খাবারে বাজারদরে যাবে, আর ধানের আসল খরচ কমবে।',
                )
            }}
        </p>
    </div>
</template>
