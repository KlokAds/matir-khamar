<script setup lang="ts">
import { Head, router } from '@inertiajs/vue3';
import { Leaf, Pill, Sprout, Wheat } from '@lucide/vue';
import { computed, ref, watch } from 'vue';
import Field from '@/components/khamar/Field.vue';
import PageTitle from '@/components/khamar/PageTitle.vue';
import DateField from '@/components/khamar/DateField.vue';
import { Input } from '@/components/ui/input';
import { qty, taka } from '@/lib/khamar';

defineOptions({
    layout: {
        breadcrumbs: [{ title: 'কোথায় কত কেজি গেল', href: '/reports/usage' }],
    },
});

type Used = {
    center_id: number;
    center: string;
    qty: number;
    value: number;
    days: number;
    animals: number | null;
    per_animal_day: number | null;
    land: number | null;
    per_land: number | null;
};

type Row = {
    id: number;
    name: string;
    kind: string;
    unit: string;
    produced: {
        qty: number;
        value: number;
        land: number | null;
        per_land: number | null;
        from: string[];
    } | null;
    bought: { qty: number; value: number } | null;
    used: Used[];
    used_qty: number;
    used_value: number;
    on_hand: number;
};

const props = defineProps<{ month: string; items: Row[] }>();

const month = ref(props.month);
watch(month, (value) => {
    if (value) {
        router.get(
            '/reports/usage',
            { month: value },
            { preserveScroll: true },
        );
    }
});

const sections = computed(() =>
    [
        {
            key: 'grass',
            title: 'ঘাস — কোন জমি থেকে, কোন গরুকে',
            icon: Sprout,
            kinds: ['green'],
            land: false,
        },
        {
            key: 'feed',
            title: 'খাবার — খড়, ভুসি, দানাদার',
            icon: Wheat,
            kinds: ['roughage', 'concentrate'],
            land: false,
        },
        {
            key: 'field',
            title: 'সার ও বীজ — কোন জমিতে কত',
            icon: Leaf,
            kinds: ['crop_input'],
            land: true,
        },
        {
            key: 'other',
            title: 'ঔষধ ও অন্যান্য',
            icon: Pill,
            kinds: ['medicine', 'product', 'other'],
            land: false,
        },
    ]
        .map((section) => ({
            ...section,
            rows: props.items.filter((row) => section.kinds.includes(row.kind)),
        }))
        .filter((section) => section.rows.length > 0),
);

const share = (row: Row, used: Used) =>
    row.used_qty > 0 ? Math.round((used.qty / row.used_qty) * 100) : 0;
</script>

<template>
    <Head title="কোথায় কত কেজি গেল" />

    <div class="flex flex-1 flex-col p-4 md:p-6">
        <PageTitle
            title="কোথায় কত কেজি গেল"
            subtitle="ঘাস কত শতাংশ জমি থেকে এলো, খাবার কোন ক্যাটাগরির কতটি গরু খেলো, সার কোন জমিতে কত পড়লো — টাকার হিসাব খাতার সাথে মিলিয়ে।"
        >
            <template #actions>
                <Field label="মাস" class="w-44">
                    <DateField mode="month" v-model="month" />
                </Field>
            </template>
        </PageTitle>

        <p
            v-if="sections.length === 0"
            class="rounded-xl border border-dashed border-border p-8 text-center text-muted-foreground"
        >
            এই মাসে স্টক থেকে কিছু দেওয়া বা কাটা হয়নি।
        </p>

        <section v-for="section in sections" :key="section.key" class="mb-8">
            <h2 class="mb-3 flex items-center gap-2 font-display text-xl">
                <component :is="section.icon" class="size-5 text-paddy" />
                {{ section.title }}
            </h2>

            <div class="grid gap-4 xl:grid-cols-2">
                <article
                    v-for="row in section.rows"
                    :key="row.id"
                    class="rounded-xl border border-border bg-card p-4"
                >
                    <header
                        class="mb-3 flex items-baseline justify-between gap-3"
                    >
                        <h3 class="text-lg font-semibold">{{ row.name }}</h3>
                        <span class="num text-sm text-muted-foreground"
                            >স্টকে বাকি {{ qty(row.on_hand, row.unit) }}</span
                        >
                    </header>

                    <div class="mb-3 grid gap-2 text-sm sm:grid-cols-2">
                        <div
                            v-if="row.produced"
                            class="rounded-lg bg-paddy/10 p-3"
                        >
                            <p class="text-xs text-muted-foreground">
                                নিজের জমি থেকে কাটা ({{
                                    row.produced.from.join(', ')
                                }})
                            </p>
                            <p class="num text-base font-semibold">
                                {{ qty(row.produced.qty, row.unit) }}
                            </p>
                            <p
                                v-if="row.produced.land"
                                class="num text-xs text-muted-foreground"
                            >
                                {{ row.produced.land }} শতাংশ জমি থেকে · প্রতি
                                শতাংশে {{ row.produced.per_land }}
                                {{ row.unit }}
                            </p>
                            <p v-else class="text-xs text-muted-foreground">
                                জমির মাপ লেখা হয়নি
                            </p>
                        </div>
                        <div
                            v-if="row.bought"
                            class="rounded-lg bg-muted/60 p-3"
                        >
                            <p class="text-xs text-muted-foreground">কেনা</p>
                            <p class="num text-base font-semibold">
                                {{ qty(row.bought.qty, row.unit) }}
                            </p>
                            <p class="num text-xs text-muted-foreground">
                                {{ taka(row.bought.value) }}
                            </p>
                        </div>
                    </div>

                    <p
                        v-if="row.used.length === 0"
                        class="text-sm text-muted-foreground"
                    >
                        এই মাসে কোথাও দেওয়া হয়নি।
                    </p>

                    <ul v-else class="divide-y divide-border/60">
                        <li
                            v-for="used in row.used"
                            :key="used.center_id"
                            class="py-2.5"
                        >
                            <div
                                class="flex items-baseline justify-between gap-3"
                            >
                                <span class="font-medium">{{
                                    used.center
                                }}</span>
                                <span class="num font-semibold">{{
                                    qty(used.qty, row.unit)
                                }}</span>
                            </div>
                            <div
                                class="mt-1.5 h-1.5 overflow-hidden rounded-full bg-muted"
                            >
                                <div
                                    class="h-full rounded-full bg-paddy"
                                    :style="{ width: `${share(row, used)}%` }"
                                />
                            </div>
                            <div
                                class="num mt-1.5 flex flex-wrap justify-between gap-x-4 gap-y-1 text-xs text-muted-foreground"
                            >
                                <span v-if="section.land">
                                    <template v-if="used.land"
                                        >{{ used.land }} শতাংশ জমিতে · প্রতি
                                        শতাংশে {{ used.per_land }}
                                        {{ row.unit }}</template
                                    >
                                    <template v-else
                                        >জমির মাপ লেখা হয়নি</template
                                    >
                                </span>
                                <span v-else-if="used.animals">
                                    গড়ে {{ used.animals }}টি গরু ·
                                    {{ used.days }} দিন · প্রতি গরু দিনে
                                    {{ used.per_animal_day }} {{ row.unit }}
                                </span>
                                <span v-else>{{ used.days }} দিন</span>
                                <span
                                    >{{ share(row, used) }}% ·
                                    {{ taka(used.value) }}</span
                                >
                            </div>
                        </li>
                        <li
                            class="num flex justify-between pt-2.5 text-sm font-semibold"
                        >
                            <span>মোট দেওয়া</span>
                            <span
                                >{{ qty(row.used_qty, row.unit) }} ·
                                {{ taka(row.used_value) }}</span
                            >
                        </li>
                    </ul>
                </article>
            </div>
        </section>
    </div>
</template>
