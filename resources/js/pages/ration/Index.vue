<script setup lang="ts">
import { Head, Link } from '@inertiajs/vue3';
import {
    Baby,
    BookOpen,
    ChevronDown,
    ChevronRight,
    Droplets,
    Info,
    Milk,
    Package,
    Pencil,
    RotateCcw,
    Scale,
    Settings2,
    Wheat,
    X,
} from '@lucide/vue';
import { computed, reactive, ref } from 'vue';
import PageTitle from '@/components/khamar/PageTitle.vue';
import StatTile from '@/components/khamar/StatTile.vue';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { t } from '@/lib/i18n';
import { day, taka } from '@/lib/khamar';

defineOptions({
    layout: {
        breadcrumbs: [{ title: 'খাবারের রেশন হিসাব', href: '/ration' }],
    },
});

type Category = 'gavi' | 'bokna' | 'motataja' | 'bachur';
type FeedKey = 'straw' | 'green' | 'bran' | 'concentrate' | 'oilcake';
type PriceKey = FeedKey | 'mineral' | 'salt';

type AnimalRow = {
    id: number;
    tag: string;
    name: string | null;
    category: Category;
    center: string;
    weight: number;
    weight_estimated: boolean;
    weight_date: string | null;
    gain_g: number | null;
    milk: number;
    milk_source: 'own' | 'center' | null;
    pregnant: boolean;
    expected_on: string | null;
    late_pregnancy: boolean;
};

type Assumptions = {
    dm_pct: Record<
        'gavi' | 'gavi_dry' | 'bokna' | 'motataja' | 'bachur',
        number
    >;
    roughage_min: Record<Category, number>;
    dm_green: number;
    dm_straw: number;
    dm_conc: number;
    green_pct_bw: number;
    green_share_max: number;
    cow_base_kg: number;
    liters_per_kg: number;
    pregnant_extra_kg: number;
    heifer_kg: number;
    fattening_gain_g: number;
    calf_conc_pct: number;
    mix: { bran: number; concentrate: number; oilcake: number };
    mineral_g: number;
    salt_g: number;
};

type Price = {
    price: number;
    source: 'stock' | 'market' | 'typical';
    stock_price: number | null;
    market_price: number | null;
};

type StockRow = {
    key: FeedKey;
    qty: number;
    actual_per_day: number | null;
    items: string[];
};

const props = defineProps<{
    animals: AnimalRow[];
    defaults: Assumptions;
    defaultWeights: Record<Category, number>;
    prices: Record<PriceKey, Price>;
    stock: StockRow[];
    milkDays: number;
    windowDays: number;
}>();

const DISCLAIMER = 'আনুমানিক — প্রাণিসম্পদ কর্মকর্তার সাথে মিলিয়ে নিন';

const categories: { key: Category; label: string; icon: typeof Milk }[] = [
    { key: 'gavi', label: 'গাভী (দুধ)', icon: Milk },
    { key: 'bokna', label: 'বকনা', icon: Wheat },
    { key: 'motataja', label: 'মোটাতাজাকরণ', icon: Scale },
    { key: 'bachur', label: 'বাছুর', icon: Baby },
];

const feedLabels: Record<PriceKey, string> = {
    straw: 'খড়',
    green: 'কাঁচা ঘাস',
    bran: 'ভুসি',
    concentrate: 'দানাদার খাদ্য',
    oilcake: 'খৈল',
    mineral: 'খনিজ মিশ্রণ',
    salt: 'লবণ',
};

const sourceLabels: Record<Price['source'], string> = {
    stock: 'স্টকের গড় দাম',
    market: 'বাজারদর',
    typical: 'সাধারণ দাম — বদলে নিন',
};

// ---- Editable assumptions (recalculated here, same arithmetic as App\Services\RationBook) ----

const clone = <T>(v: T): T => JSON.parse(JSON.stringify(v)) as T;
const a = reactive<Assumptions>(clone(props.defaults));

/** Prices in taka per kg while editing; the arithmetic below works in poisha. */
const priceTaka = reactive<Record<PriceKey, number>>(
    Object.fromEntries(
        Object.entries(props.prices).map(([k, p]) => [k, p.price / 100]),
    ) as Record<PriceKey, number>,
);

/** Per-animal weight and milk typed here to try a different figure; not saved. */
const overrides = reactive<Record<number, { weight: number; milk: number }>>(
    {},
);
const editing = ref<number | null>(null);
const showAssumptions = ref(false);
const showRules = ref(false);

function reset() {
    Object.assign(a, clone(props.defaults));

    for (const [k, p] of Object.entries(props.prices)) {
        priceTaka[k as PriceKey] = p.price / 100;
    }

    for (const id of Object.keys(overrides)) {
        delete overrides[Number(id)];
    }

    editing.value = null;
}

const num = (v: unknown, fallback = 0): number => {
    const n = Number(v);

    return Number.isFinite(n) ? n : fallback;
};

const round = (v: number, places = 2) => {
    const f = 10 ** places;

    return Math.round(v * f) / f;
};

function fatteningPct(gainG: number): number {
    return Math.max(1, Math.min(1.5, 1 + (num(gainG) - 500) / 1000));
}

type Ration = {
    dm: number;
    dmPct: number;
    green: number;
    straw: number;
    conc: number;
    bran: number;
    concentrate: number;
    oilcake: number;
    mineral: number;
    salt: number;
    water: number;
    offered: number;
    concShare: number;
    cost: number;
};

function ration(
    animal: Pick<
        AnimalRow,
        'category' | 'weight' | 'milk' | 'pregnant' | 'late_pregnancy'
    >,
): Ration {
    const c = animal.category;
    const weight = Math.max(0, num(animal.weight));
    const milk = Math.max(0, num(animal.milk));

    // Dry matter: 3% of body weight for milking/pregnant cows, fattening and calves; 2.5% heifers, dry cows.
    const dmPct =
        c === 'gavi'
            ? milk > 0 || animal.pregnant
                ? num(a.dm_pct.gavi)
                : num(a.dm_pct.gavi_dry)
            : c === 'bokna'
              ? animal.pregnant
                  ? num(a.dm_pct.gavi)
                  : num(a.dm_pct.bokna)
              : c === 'motataja'
                ? num(a.dm_pct.motataja)
                : num(a.dm_pct.bachur);
    const dm = (weight * dmPct) / 100;

    // Concentrate (fresh kg).
    let conc =
        c === 'gavi'
            ? num(a.cow_base_kg) +
              (num(a.liters_per_kg) > 0 ? milk / num(a.liters_per_kg) : 0)
            : c === 'bokna'
              ? num(a.heifer_kg)
              : c === 'motataja'
                ? (weight * fatteningPct(a.fattening_gain_g)) / 100
                : (weight * num(a.calf_conc_pct)) / 100;

    if (animal.late_pregnancy && (c === 'gavi' || c === 'bokna')) {
        conc += num(a.pregnant_extra_kg);
    }

    const concDm = (conc * num(a.dm_conc)) / 100;
    const roughageDm = Math.max(
        dm - concDm,
        (dm * num(a.roughage_min[c], 60)) / 100,
    );
    const greenDm = Math.max(
        0,
        Math.min(
            (((weight * num(a.green_pct_bw)) / 100) * num(a.dm_green)) / 100,
            (roughageDm * num(a.green_share_max)) / 100,
        ),
    );
    const strawDm = Math.max(0, roughageDm - greenDm);
    const green = num(a.dm_green) > 0 ? greenDm / (num(a.dm_green) / 100) : 0;
    const straw = num(a.dm_straw) > 0 ? strawDm / (num(a.dm_straw) / 100) : 0;

    const mixTotal = Math.max(
        1,
        num(a.mix.bran) + num(a.mix.concentrate) + num(a.mix.oilcake),
    );
    const bran = (conc * num(a.mix.bran)) / mixTotal;
    const concentrate = (conc * num(a.mix.concentrate)) / mixTotal;
    const oilcake = (conc * num(a.mix.oilcake)) / mixTotal;

    const small = c === 'bachur' || weight < 150;
    const mineral = num(a.mineral_g) * (small ? 0.5 : 1);
    const salt = num(a.salt_g) * (small ? 0.5 : 1);
    const water = small
        ? Math.max(8, weight * 0.12)
        : Math.min(60, 40 + 2 * milk);

    const p = (k: PriceKey) => num(priceTaka[k]) * 100;
    const cost =
        green * p('green') +
        straw * p('straw') +
        bran * p('bran') +
        concentrate * p('concentrate') +
        oilcake * p('oilcake') +
        (mineral / 1000) * p('mineral') +
        (salt / 1000) * p('salt');

    const offered = greenDm + strawDm + concDm;

    return {
        dm: round(dm),
        dmPct,
        green: round(green),
        straw: round(straw),
        conc: round(conc),
        bran: round(bran),
        concentrate: round(concentrate),
        oilcake: round(oilcake),
        mineral: Math.round(mineral),
        salt: Math.round(salt),
        water: Math.round(water),
        offered: round(offered),
        concShare: offered > 0 ? round((concDm / offered) * 100, 1) : 0,
        cost: Math.round(cost),
    };
}

type Row = AnimalRow & { r: Ration; edited: boolean };

const rows = computed<Row[]>(() =>
    props.animals.map((animal) => {
        const o = overrides[animal.id];
        const input = o
            ? { ...animal, weight: num(o.weight), milk: num(o.milk) }
            : animal;

        return { ...input, r: ration(input), edited: !!o };
    }),
);

type Sum = {
    count: number;
    green: number;
    straw: number;
    conc: number;
    bran: number;
    concentrate: number;
    oilcake: number;
    mineral: number;
    salt: number;
    water: number;
    cost: number;
};

function sum(list: Row[]): Sum {
    const s: Sum = {
        count: list.length,
        green: 0,
        straw: 0,
        conc: 0,
        bran: 0,
        concentrate: 0,
        oilcake: 0,
        mineral: 0,
        salt: 0,
        water: 0,
        cost: 0,
    };

    for (const { r } of list) {
        s.green += r.green;
        s.straw += r.straw;
        s.conc += r.conc;
        s.bran += r.bran;
        s.concentrate += r.concentrate;
        s.oilcake += r.oilcake;
        s.mineral += r.mineral;
        s.salt += r.salt;
        s.water += r.water;
        s.cost += r.cost;
    }

    return s;
}

const groups = computed(() =>
    categories
        .map((c) => {
            const list = rows.value.filter((r) => r.category === c.key);

            return { ...c, rows: list, total: sum(list) };
        })
        .filter((g) => g.rows.length > 0),
);

const total = computed(() => sum(rows.value));

/** Suggested kg a day of each stocked feed for the whole farm. */
const suggestedPerDay = computed<Record<FeedKey, number>>(() => ({
    straw: total.value.straw,
    green: total.value.green,
    bran: total.value.bran,
    concentrate: total.value.concentrate,
    oilcake: total.value.oilcake,
}));

const stockRows = computed(() =>
    props.stock.map((s) => {
        const need = suggestedPerDay.value[s.key];
        const days = need > 0 ? Math.floor(s.qty / need) : null;
        const ratio =
            s.actual_per_day !== null && need > 0
                ? s.actual_per_day / need
                : null;
        let compare: { text: string; tone: string } | null = null;

        if (need > 0 && s.actual_per_day === null) {
            compare = {
                text: t('গত :n দিনে খাওয়ানোর হিসাব লেখা হয়নি', {
                    n: props.windowDays,
                }),
                tone: 'text-muted-foreground',
            };
        } else if (ratio !== null) {
            const pct = Math.round(Math.abs(ratio - 1) * 100);

            compare =
                ratio > 1.15
                    ? {
                          text: t('পরামর্শের চেয়ে :p% বেশি দিচ্ছেন', {
                              p: pct,
                          }),
                          tone: 'text-clay',
                      }
                    : ratio < 0.85
                      ? {
                            text: t('পরামর্শের চেয়ে :p% কম দিচ্ছেন', {
                                p: pct,
                            }),
                            tone: 'text-clay',
                        }
                      : {
                            text: t('প্রায় পরামর্শমতো দিচ্ছেন'),
                            tone: 'text-paddy',
                        };
        }

        const tone =
            days === null
                ? 'bg-muted text-muted-foreground'
                : days < 7
                  ? 'bg-destructive/10 text-destructive'
                  : days < 30
                    ? 'bg-turmeric/25 text-soil'
                    : 'bg-paddy/12 text-paddy';

        return { ...s, need, days, compare, tone };
    }),
);

// ---- Formatting ----

const kg = (v: number) =>
    v.toLocaleString('en-IN', { maximumFractionDigits: v < 10 ? 2 : 1 });
/** Taka rounded to whole taka, so tiles fit on a phone. */
const wholeTaka = (poisha: number) => taka(Math.round(poisha / 100) * 100);
const whole = (v: number) =>
    Math.round(v).toLocaleString('en-IN', { maximumFractionDigits: 0 });

function startEdit(row: Row) {
    if (!overrides[row.id]) {
        overrides[row.id] = { weight: row.weight, milk: row.milk };
    }

    editing.value = editing.value === row.id ? null : row.id;
}

function clearEdit(id: number) {
    delete overrides[id];
    editing.value = null;
}

// ---- Assumption fields, laid out as small number boxes ----

type FieldDef = {
    label: string;
    suffix: string;
    step: number;
    get: () => number;
    set: (v: number) => void;
};

const field = (
    label: string,
    suffix: string,
    step: number,
    obj: Record<string, number>,
    key: string,
): FieldDef => ({
    label,
    suffix,
    step,
    get: () => obj[key],
    set: (v) => {
        obj[key] = v;
    },
});

/** The plain-number assumptions, for the field helper. */
const top = a as unknown as Record<string, number>;

const sections = computed<
    { title: string; hint?: string; fields: FieldDef[] }[]
>(() => [
    {
        title: 'শুকনো খাদ্য (ওজনের %)',
        hint: 'দিনে কত শুকনো খাদ্য (DM) খায়',
        fields: [
            field('দুধের / গর্ভবতী গাভী', '%', 0.1, a.dm_pct, 'gavi'),
            field('শুকনো গাভী (দুধ নেই)', '%', 0.1, a.dm_pct, 'gavi_dry'),
            field('বকনা', '%', 0.1, a.dm_pct, 'bokna'),
            field('মোটাতাজা', '%', 0.1, a.dm_pct, 'motataja'),
            field('বাছুর', '%', 0.1, a.dm_pct, 'bachur'),
        ],
    },
    {
        title: 'খড়-ঘাসের ভাগ (কমপক্ষে)',
        hint: 'শুকনো খাদ্যের কত % খড় ও ঘাস থেকে',
        fields: [
            field('গাভী', '%', 1, a.roughage_min, 'gavi'),
            field('বকনা', '%', 1, a.roughage_min, 'bokna'),
            field('মোটাতাজা', '%', 1, a.roughage_min, 'motataja'),
            field('বাছুর', '%', 1, a.roughage_min, 'bachur'),
        ],
    },
    {
        title: 'কাঁচা ঘাস',
        fields: [
            field('সর্বোচ্চ (ওজনের %)', '%', 0.5, top, 'green_pct_bw'),
            field(
                'খড়-ঘাসের মধ্যে ঘাসের সর্বোচ্চ ভাগ',
                '%',
                5,
                top,
                'green_share_max',
            ),
        ],
    },
    {
        title: 'দানাদার খাদ্য',
        fields: [
            field('গাভীর মূল দানাদার', t('কেজি'), 0.25, top, 'cow_base_kg'),
            field(
                'প্রতি ১ কেজি দানাদারে দুধ',
                t('লিটার'),
                0.1,
                top,
                'liters_per_kg',
            ),
            field(
                'শেষ ২ মাসের গর্ভে বাড়তি',
                t('কেজি'),
                0.25,
                top,
                'pregnant_extra_kg',
            ),
            field('বকনা', t('কেজি'), 0.25, top, 'heifer_kg'),
            field(
                'মোটাতাজা — লক্ষ্য দৈনিক বাড়তি',
                t('গ্রাম'),
                50,
                top,
                'fattening_gain_g',
            ),
            field('বাছুর (ওজনের %)', '%', 0.1, top, 'calf_conc_pct'),
        ],
    },
    {
        title: 'দানাদার মিশ্রণ',
        hint: 'ভুসি / দানাদার / খৈল — মোট ১০০ ধরে ভাগ',
        fields: [
            field('ভুসি', '%', 5, a.mix, 'bran'),
            field('দানাদার খাদ্য', '%', 5, a.mix, 'concentrate'),
            field('খৈল', '%', 5, a.mix, 'oilcake'),
        ],
    },
    {
        title: 'খনিজ ও লবণ (বড় গরু, দিনে)',
        hint: 'বাছুর ও ছোট গরু অর্ধেক',
        fields: [
            field('খনিজ মিশ্রণ', t('গ্রাম'), 5, top, 'mineral_g'),
            field('লবণ', t('গ্রাম'), 5, top, 'salt_g'),
        ],
    },
    {
        title: 'শুকনো অংশ (খাদ্যের %)',
        fields: [
            field('কাঁচা ঘাস', '%', 1, top, 'dm_green'),
            field('খড়', '%', 1, top, 'dm_straw'),
            field('দানাদার', '%', 1, top, 'dm_conc'),
        ],
    },
]);

const priceKeys: PriceKey[] = [
    'straw',
    'green',
    'bran',
    'concentrate',
    'oilcake',
    'mineral',
    'salt',
];

const mixSum = computed(
    () => num(a.mix.bran) + num(a.mix.concentrate) + num(a.mix.oilcake),
);

const rules = [
    'দিনে শুকনো খাদ্য (DM): দুধের/গর্ভবতী গাভী ও মোটাতাজা গরু ওজনের ~৩%, বকনা ~২.৫%।',
    'শুকনো খাদ্যের ৬০–৭০% খড়-ঘাস (গাভী ও বকনা), মোটাতাজায় ৪০–৫০%।',
    'শুকনো অংশ: কাঁচা ঘাস ~২০%, খড় ~৯০%, দানাদার ~৯০%।',
    'দুধের গাভী: ১ কেজি মূল দানাদার + প্রতি ২–২.৫ লিটার দুধে ১ কেজি।',
    'গর্ভের শেষ ২ মাসে দানাদার ১ কেজি বাড়তি।',
    'বকনা: দিনে ১–১.৫ কেজি দানাদার।',
    'মোটাতাজা: ওজনের ১–১.৫% দানাদার (লক্ষ্য বাড়তি যত বেশি, তত বেশি)।',
    'কাঁচা ঘাস ওজনের ~১০% পর্যন্ত (তাজা), বাকি খড়-ঘাস খড় দিয়ে।',
    'পানি দিনে ৪০–৬০ লিটার; খনিজ মিশ্রণ ~৫০ গ্রাম, লবণ ~৩০ গ্রাম।',
];
</script>

<template>
    <Head :title="t('খাবারের রেশন হিসাব')" />

    <div class="flex flex-1 flex-col p-4 pb-24 md:p-6">
        <PageTitle
            :title="t('খাবারের রেশন হিসাব')"
            :subtitle="
                t(
                    'কোন গরুকে দিনে কত খড়, কাঁচা ঘাস ও দানাদার — খরচ কত, স্টক কত দিন চলবে',
                )
            "
        >
            <template #actions>
                <Button
                    variant="secondary"
                    :aria-expanded="showAssumptions"
                    @click="showAssumptions = !showAssumptions"
                >
                    <Settings2 /> {{ t('হিসাবের নিয়ম বদলান') }}
                </Button>
            </template>
        </PageTitle>

        <div
            class="mb-4 flex items-start gap-3 rounded-xl border border-turmeric/50 bg-turmeric/15 p-3 text-sm leading-snug text-soil"
            role="note"
        >
            <Info class="mt-0.5 size-4 shrink-0" />
            <p>
                <span class="font-semibold">{{ t(DISCLAIMER) }}</span>
                <span class="block text-[13px] opacity-90">{{
                    t(
                        'এগুলো খামারিদের প্রচলিত হিসাব, বৈজ্ঞানিক রেশন নয়। গরুর স্বাস্থ্য, জাত ও ঘাসের মান অনুযায়ী কমবেশি হবে।',
                    )
                }}</span>
            </p>
        </div>

        <!-- Assumptions -->
        <section
            v-if="showAssumptions"
            class="mb-4 rounded-xl border border-border bg-card p-4"
        >
            <div class="mb-3 flex flex-wrap items-center justify-between gap-2">
                <div>
                    <h2 class="font-display text-lg leading-snug">
                        {{ t('হিসাবের নিয়ম') }}
                    </h2>
                    <p class="text-[13px] text-muted-foreground">
                        {{
                            t(
                                'বদলালে সঙ্গে সঙ্গে হিসাব বদলায়; এখানে কিছু সেভ হয় না।',
                            )
                        }}
                    </p>
                </div>
                <div class="flex flex-wrap gap-2">
                    <button type="button" class="btn-chip" @click="reset">
                        <RotateCcw /> {{ t('আগের মতো') }}
                    </button>
                    <button
                        type="button"
                        class="btn-icon"
                        :aria-label="t('লুকান')"
                        :title="t('লুকান')"
                        @click="showAssumptions = false"
                    >
                        <X />
                    </button>
                </div>
            </div>

            <div class="grid grid-cols-[minmax(0,1fr)] gap-4 lg:grid-cols-2">
                <div
                    v-for="s in sections"
                    :key="s.title"
                    class="rounded-lg border border-border/70 p-3"
                >
                    <p class="text-sm font-semibold">{{ t(s.title) }}</p>
                    <p
                        v-if="s.hint"
                        class="mb-1 text-xs leading-snug text-muted-foreground"
                    >
                        {{ t(s.hint) }}
                    </p>
                    <div class="mt-2 grid grid-cols-2 gap-x-3 gap-y-2.5">
                        <label
                            v-for="f in s.fields"
                            :key="f.label"
                            class="grid content-end gap-1"
                        >
                            <span
                                class="text-xs leading-snug text-muted-foreground"
                                >{{ t(f.label) }}</span
                            >
                            <span class="relative">
                                <Input
                                    type="number"
                                    inputmode="decimal"
                                    min="0"
                                    :step="f.step"
                                    class="num h-9 pr-12"
                                    :model-value="f.get()"
                                    @update:model-value="f.set(num($event))"
                                />
                                <span
                                    class="pointer-events-none absolute inset-y-0 right-2.5 flex items-center text-xs text-muted-foreground"
                                    >{{ f.suffix }}</span
                                >
                            </span>
                        </label>
                    </div>
                    <p
                        v-if="s.title === 'দানাদার মিশ্রণ' && mixSum !== 100"
                        class="mt-2 text-xs text-clay"
                    >
                        {{ t('মোট :n — ১০০ ধরে ভাগ করা হচ্ছে', { n: mixSum }) }}
                    </p>
                    <p
                        v-if="s.title === 'দানাদার খাদ্য'"
                        class="mt-2 text-xs leading-snug text-muted-foreground"
                    >
                        {{
                            t('মোটাতাজা: ওজনের :p% দানাদার', {
                                p: round(fatteningPct(a.fattening_gain_g), 2),
                            })
                        }}
                    </p>
                </div>

                <div
                    class="rounded-lg border border-border/70 p-3 lg:col-span-2"
                >
                    <p class="text-sm font-semibold">
                        {{ t('দাম (৳ / কেজি)') }}
                    </p>
                    <div
                        class="mt-2 grid grid-cols-2 gap-x-3 gap-y-2.5 sm:grid-cols-4 lg:grid-cols-7"
                    >
                        <label
                            v-for="k in priceKeys"
                            :key="k"
                            class="grid content-end gap-1"
                        >
                            <span
                                class="text-xs leading-snug text-muted-foreground"
                                >{{ t(feedLabels[k]) }}</span
                            >
                            <Input
                                v-model.number="priceTaka[k]"
                                type="number"
                                inputmode="decimal"
                                min="0"
                                step="0.5"
                                class="num h-9"
                            />
                            <span
                                class="text-[11px] leading-snug text-muted-foreground"
                            >
                                {{ t(sourceLabels[prices[k].source]) }}
                                <template
                                    v-if="
                                        prices[k].market_price &&
                                        prices[k].source === 'stock'
                                    "
                                >
                                    ·
                                    {{
                                        t('হাটে :p', {
                                            p: taka(prices[k].market_price),
                                        })
                                    }}
                                </template>
                            </span>
                        </label>
                    </div>
                </div>
            </div>
        </section>

        <div
            v-if="animals.length === 0"
            class="rounded-xl border border-dashed border-border bg-card p-6 text-center"
        >
            <p class="font-medium">
                {{ t('চালু ক্যাটাগরিতে কোনো গরু নেই।') }}
            </p>
            <p class="mt-1 text-sm text-muted-foreground">
                {{ t('গরু যোগ করলে এখানে প্রতিটির রেশন দেখাবে।') }}
            </p>
            <Button as-child variant="secondary" class="mt-4">
                <Link href="/animals"
                    >{{ t('গরুর তালিকা') }} <ChevronRight
                /></Link>
            </Button>
        </div>

        <template v-else>
            <!-- Farm totals -->
            <div class="grid grid-cols-2 gap-3">
                <StatTile
                    :label="t('দিনে খরচ')"
                    :value="wholeTaka(total.cost)"
                    :hint="t(':n টি গরু', { n: total.count })"
                />
                <StatTile
                    :label="t('মাসে খরচ (৩০ দিন)')"
                    :value="wholeTaka(total.cost * 30)"
                    :hint="t('আনুমানিক')"
                />
            </div>

            <section class="mt-4 rounded-xl border border-border bg-card p-4">
                <h2 class="font-display text-lg leading-snug">
                    {{ t('পুরো খামারে দিনে') }}
                </h2>
                <dl class="mt-3 grid grid-cols-2 gap-3 sm:grid-cols-4">
                    <div class="rounded-lg bg-paddy/10 p-3">
                        <dt class="text-xs text-muted-foreground">
                            {{ t('কাঁচা ঘাস') }}
                        </dt>
                        <dd class="num text-xl font-semibold">
                            {{ kg(total.green) }}
                            <span class="text-sm font-normal">{{
                                t('কেজি')
                            }}</span>
                        </dd>
                    </div>
                    <div class="rounded-lg bg-turmeric/20 p-3">
                        <dt class="text-xs text-muted-foreground">
                            {{ t('খড়') }}
                        </dt>
                        <dd class="num text-xl font-semibold">
                            {{ kg(total.straw) }}
                            <span class="text-sm font-normal">{{
                                t('কেজি')
                            }}</span>
                        </dd>
                    </div>
                    <div class="rounded-lg bg-clay/10 p-3">
                        <dt class="text-xs text-muted-foreground">
                            {{ t('দানাদার') }}
                        </dt>
                        <dd class="num text-xl font-semibold">
                            {{ kg(total.conc) }}
                            <span class="text-sm font-normal">{{
                                t('কেজি')
                            }}</span>
                        </dd>
                    </div>
                    <div class="rounded-lg bg-pond/10 p-3">
                        <dt class="text-xs text-muted-foreground">
                            {{ t('পানি') }}
                        </dt>
                        <dd class="num text-xl font-semibold">
                            {{ whole(total.water) }}
                            <span class="text-sm font-normal">{{
                                t('লিটার')
                            }}</span>
                        </dd>
                    </div>
                </dl>
                <p
                    class="num mt-3 text-sm leading-relaxed text-muted-foreground"
                >
                    {{
                        t(
                            'দানাদারের মধ্যে ভুসি :b কেজি, দানাদার খাদ্য :c কেজি, খৈল :o কেজি · খনিজ :m গ্রাম · লবণ :s গ্রাম',
                            {
                                b: kg(total.bran),
                                c: kg(total.concentrate),
                                o: kg(total.oilcake),
                                m: whole(total.mineral),
                                s: whole(total.salt),
                            },
                        )
                    }}
                </p>
            </section>

            <!-- Stock -->
            <section class="mt-4 rounded-xl border border-border bg-card p-4">
                <div class="flex flex-wrap items-center justify-between gap-2">
                    <div class="min-w-0">
                        <h2 class="font-display text-lg leading-snug">
                            {{ t('স্টক কত দিন চলবে') }}
                        </h2>
                        <p
                            class="text-[13px] leading-snug text-muted-foreground"
                        >
                            {{
                                t(
                                    'এই রেশনে; পাশে গত :n দিনে আসলে দিনে কত গেছে',
                                    { n: windowDays },
                                )
                            }}
                        </p>
                    </div>
                    <Link href="/stock" class="btn-chip"
                        ><Package /> {{ t('স্টক দেখুন') }}</Link
                    >
                </div>

                <ul
                    class="mt-3 grid grid-cols-[minmax(0,1fr)] gap-2 sm:grid-cols-2 xl:grid-cols-3"
                >
                    <li
                        v-for="s in stockRows"
                        :key="s.key"
                        class="rounded-lg border border-border/70 p-3"
                    >
                        <div class="flex items-start justify-between gap-2">
                            <p class="leading-snug font-medium">
                                {{ t(feedLabels[s.key]) }}
                            </p>
                            <span
                                :class="[
                                    'num shrink-0 rounded-full px-2 py-0.5 text-xs font-medium',
                                    s.tone,
                                ]"
                                >{{
                                    s.days === null
                                        ? '—'
                                        : t(':n দিন', { n: whole(s.days) })
                                }}</span
                            >
                        </div>
                        <p
                            class="num mt-1 text-sm leading-snug text-muted-foreground"
                        >
                            {{
                                t('আছে :q কেজি · দরকার :n কেজি/দিন', {
                                    q: kg(s.qty),
                                    n: kg(s.need),
                                })
                            }}
                        </p>
                        <p
                            v-if="s.actual_per_day !== null"
                            class="num text-sm leading-snug text-muted-foreground"
                        >
                            {{
                                t('আসলে গেছে :a কেজি/দিন', {
                                    a: kg(s.actual_per_day),
                                })
                            }}
                        </p>
                        <p
                            v-if="s.compare"
                            :class="[
                                'mt-1 text-[13px] leading-snug',
                                s.compare.tone,
                            ]"
                        >
                            {{ s.compare.text }}
                        </p>
                        <p
                            v-if="
                                s.key === 'green' &&
                                s.days !== null &&
                                s.days < 7
                            "
                            class="mt-1 text-[13px] leading-snug text-clay"
                        >
                            {{
                                t(
                                    'নিজের জমির ঘাস কাটা হলে "নিজের উৎপাদন" এ লিখুন, নইলে স্টকে দেখাবে না।',
                                )
                            }}
                        </p>
                        <p
                            v-if="s.items.length"
                            class="mt-1 truncate text-[11px] text-muted-foreground"
                        >
                            {{ s.items.map((i) => t(i)).join(' · ') }}
                        </p>
                    </li>
                </ul>
            </section>

            <!-- Groups -->
            <section
                v-for="g in groups"
                :key="g.key"
                class="mt-4 rounded-xl border border-border bg-card p-4"
            >
                <div class="flex flex-wrap items-start justify-between gap-2">
                    <div class="flex min-w-0 items-center gap-2.5">
                        <span class="rounded-lg bg-paddy/12 p-2 text-paddy">
                            <component :is="g.icon" class="size-5" />
                        </span>
                        <div class="min-w-0">
                            <h2 class="font-display text-lg leading-snug">
                                {{ t(g.label) }}
                            </h2>
                            <p class="text-[13px] text-muted-foreground">
                                {{ t(':n টি গরু', { n: g.total.count }) }}
                            </p>
                        </div>
                    </div>
                    <div class="text-right">
                        <p class="num font-semibold">
                            {{ t(':c/দিন', { c: taka(g.total.cost) }) }}
                        </p>
                        <p class="num text-xs text-muted-foreground">
                            {{ t(':c/মাস', { c: taka(g.total.cost * 30) }) }}
                        </p>
                    </div>
                </div>

                <p
                    class="num mt-2 text-sm leading-relaxed text-muted-foreground"
                >
                    {{
                        t(
                            'মোট দিনে: ঘাস :g কেজি · খড় :s কেজি · দানাদার :c কেজি',
                            {
                                g: kg(g.total.green),
                                s: kg(g.total.straw),
                                c: kg(g.total.conc),
                            },
                        )
                    }}
                </p>

                <ul
                    class="mt-3 grid grid-cols-[minmax(0,1fr)] gap-2 md:grid-cols-2 xl:grid-cols-3"
                >
                    <li
                        v-for="row in g.rows"
                        :key="row.id"
                        class="rounded-lg border border-border/70 p-3"
                    >
                        <div class="flex items-start justify-between gap-2">
                            <div class="min-w-0">
                                <Link
                                    :href="`/animals/${row.id}`"
                                    class="font-medium text-primary underline underline-offset-2"
                                    >{{ row.tag
                                    }}<template v-if="row.name">
                                        · {{ row.name }}</template
                                    ></Link
                                >
                                <p
                                    class="num mt-0.5 text-[13px] leading-snug text-muted-foreground"
                                >
                                    {{ t(':w কেজি', { w: kg(row.weight) }) }}
                                    <template v-if="row.category === 'gavi'">
                                        ·
                                        {{
                                            t(':l লি. দুধ/দিন', {
                                                l: kg(row.milk),
                                            })
                                        }}
                                    </template>
                                    <template
                                        v-if="
                                            row.category === 'motataja' &&
                                            row.gain_g !== null
                                        "
                                    >
                                        ·
                                        {{
                                            t('বাড়ছে :g গ্রাম/দিন', {
                                                g: row.gain_g,
                                            })
                                        }}
                                    </template>
                                </p>
                            </div>
                            <button
                                type="button"
                                :class="[
                                    'btn-icon size-8',
                                    editing === row.id
                                        ? 'border-primary/40 bg-primary/10 text-primary'
                                        : '',
                                ]"
                                :aria-label="t('ওজন / দুধ বদলে দেখুন')"
                                :title="t('ওজন / দুধ বদলে দেখুন')"
                                @click="startEdit(row)"
                            >
                                <Pencil />
                            </button>
                        </div>

                        <div class="mt-1.5 flex flex-wrap gap-1.5">
                            <span
                                v-if="row.weight_estimated && !row.edited"
                                class="rounded bg-turmeric/25 px-1.5 py-0.5 text-[11px] leading-snug text-soil"
                                >{{ t('ওজন লেখা নেই — আনুমানিক') }}</span
                            >
                            <span
                                v-if="row.edited"
                                class="rounded bg-pond/12 px-1.5 py-0.5 text-[11px] leading-snug text-pond"
                                >{{ t('বদলানো হিসাব') }}</span
                            >
                            <span
                                v-if="row.pregnant"
                                class="rounded bg-paddy/12 px-1.5 py-0.5 text-[11px] leading-snug text-paddy"
                                >{{
                                    row.expected_on
                                        ? t('গর্ভবতী · বাচ্চা :d', {
                                              d: day(row.expected_on),
                                          })
                                        : t('গর্ভবতী')
                                }}{{
                                    row.late_pregnancy
                                        ? ` · ${t('+দানাদার')}`
                                        : ''
                                }}</span
                            >
                            <span
                                v-if="
                                    row.milk_source === 'center' && !row.edited
                                "
                                class="rounded bg-muted px-1.5 py-0.5 text-[11px] leading-snug text-muted-foreground"
                                >{{ t('দুধ: ক্যাটাগরির গড়') }}</span
                            >
                            <span
                                v-if="
                                    row.category === 'gavi' &&
                                    row.milk_source === null &&
                                    !row.edited
                                "
                                class="rounded bg-muted px-1.5 py-0.5 text-[11px] leading-snug text-muted-foreground"
                                >{{
                                    t('গত :n দিনে দুধের হিসাব নেই', {
                                        n: milkDays,
                                    })
                                }}</span
                            >
                        </div>

                        <div
                            v-if="editing === row.id && overrides[row.id]"
                            class="mt-2 grid grid-cols-2 gap-2 rounded-md bg-muted/50 p-2"
                        >
                            <label class="grid gap-1">
                                <span class="text-xs text-muted-foreground">{{
                                    t('ওজন (কেজি)')
                                }}</span>
                                <Input
                                    v-model.number="overrides[row.id].weight"
                                    type="number"
                                    inputmode="decimal"
                                    min="0"
                                    class="num h-9"
                                />
                            </label>
                            <label
                                v-if="row.category === 'gavi'"
                                class="grid gap-1"
                            >
                                <span class="text-xs text-muted-foreground">{{
                                    t('দুধ (লিটার/দিন)')
                                }}</span>
                                <Input
                                    v-model.number="overrides[row.id].milk"
                                    type="number"
                                    inputmode="decimal"
                                    min="0"
                                    step="0.5"
                                    class="num h-9"
                                />
                            </label>
                            <div class="col-span-2 flex flex-wrap gap-2">
                                <button
                                    type="button"
                                    class="btn-chip"
                                    @click="clearEdit(row.id)"
                                >
                                    <RotateCcw /> {{ t('আসল হিসাবে ফেরত') }}
                                </button>
                            </div>
                        </div>

                        <dl class="mt-2 grid grid-cols-3 gap-1.5 text-center">
                            <div class="rounded-md bg-paddy/10 px-1 py-1.5">
                                <dt class="text-[11px] text-muted-foreground">
                                    {{ t('কাঁচা ঘাস') }}
                                </dt>
                                <dd class="num font-semibold">
                                    {{ kg(row.r.green) }}
                                </dd>
                            </div>
                            <div class="rounded-md bg-turmeric/20 px-1 py-1.5">
                                <dt class="text-[11px] text-muted-foreground">
                                    {{ t('খড়') }}
                                </dt>
                                <dd class="num font-semibold">
                                    {{ kg(row.r.straw) }}
                                </dd>
                            </div>
                            <div class="rounded-md bg-clay/10 px-1 py-1.5">
                                <dt class="text-[11px] text-muted-foreground">
                                    {{ t('দানাদার') }}
                                </dt>
                                <dd class="num font-semibold">
                                    {{ kg(row.r.conc) }}
                                </dd>
                            </div>
                        </dl>
                        <p
                            class="mt-1 text-center text-[11px] text-muted-foreground"
                        >
                            {{ t('কেজি / দিন') }}
                        </p>

                        <p
                            class="num mt-1.5 text-[13px] leading-snug text-muted-foreground"
                        >
                            {{
                                t('ভুসি :b · দানাদার :c · খৈল :o কেজি', {
                                    b: kg(row.r.bran),
                                    c: kg(row.r.concentrate),
                                    o: kg(row.r.oilcake),
                                })
                            }}
                        </p>
                        <p
                            class="num flex flex-wrap items-center gap-x-1 text-[13px] leading-snug text-muted-foreground"
                        >
                            <Droplets class="size-3.5 text-pond" />
                            {{
                                t(
                                    'পানি :w লি. · খনিজ :m গ্রাম · লবণ :s গ্রাম',
                                    {
                                        w: row.r.water,
                                        m: row.r.mineral,
                                        s: row.r.salt,
                                    },
                                )
                            }}
                        </p>
                        <p
                            v-if="row.r.concShare > 50"
                            class="mt-1 text-[13px] leading-snug text-clay"
                        >
                            {{
                                t(
                                    'দানাদার শুকনো খাদ্যের :p% — খড়-ঘাস বাড়ান, হঠাৎ দানাদার বাড়াবেন না',
                                    { p: Math.round(row.r.concShare) },
                                )
                            }}
                        </p>

                        <div
                            class="mt-2 flex items-baseline justify-between gap-2 border-t border-border/70 pt-2"
                        >
                            <span class="num text-xs text-muted-foreground">{{
                                t('শুকনো খাদ্য :d কেজি (ওজনের :p%)', {
                                    d: kg(row.r.dm),
                                    p: row.r.dmPct,
                                })
                            }}</span>
                            <span class="num text-sm font-semibold">{{
                                t(':c/দিন', { c: taka(row.r.cost) })
                            }}</span>
                        </div>
                    </li>
                </ul>
            </section>
        </template>

        <!-- Rules of thumb -->
        <section class="mt-4 rounded-xl border border-border bg-card p-4">
            <button
                type="button"
                class="flex w-full items-center justify-between gap-2 text-left"
                :aria-expanded="showRules"
                @click="showRules = !showRules"
            >
                <span class="flex items-center gap-2">
                    <BookOpen class="size-5 text-soil" />
                    <span class="font-display text-lg leading-snug">{{
                        t('যে নিয়মে হিসাব')
                    }}</span>
                </span>
                <span class="btn-icon size-8">
                    <ChevronDown
                        :class="[
                            'transition-transform',
                            showRules ? 'rotate-180' : '',
                        ]"
                    />
                </span>
            </button>
            <ul
                v-if="showRules"
                class="mt-3 list-disc space-y-1.5 pl-5 text-sm leading-relaxed"
            >
                <li v-for="r in rules" :key="r">{{ t(r) }}</li>
                <li>
                    {{
                        t(
                            'ওজন লেখা না থাকলে ধরা হয়েছে: গাভী :g, বকনা :b, মোটাতাজা :m, বাছুর :c কেজি।',
                            {
                                g: defaultWeights.gavi,
                                b: defaultWeights.bokna,
                                m: defaultWeights.motataja,
                                c: defaultWeights.bachur,
                            },
                        )
                    }}
                </li>
                <li>
                    {{
                        t(
                            'দুধ: গত :n দিনের দুধের হিসাব থেকে গাভীপ্রতি দিনের গড়।',
                            {
                                n: milkDays,
                            },
                        )
                    }}
                </li>
            </ul>
            <p class="mt-3 text-[13px] font-medium text-soil">
                {{ t(DISCLAIMER) }}
            </p>
        </section>
    </div>
</template>
