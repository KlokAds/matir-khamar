<script setup lang="ts">
import { Head, Link } from '@inertiajs/vue3';
import { ArrowDown, HandCoins, Leaf, Sprout, Wheat } from '@lucide/vue';
import { computed, reactive, ref } from 'vue';
import PageTitle from '@/components/khamar/PageTitle.vue';
import { Input } from '@/components/ui/input';
import { taka } from '@/lib/khamar';

/**
 * A worked example of every split the app makes, with numbers the owner can change and see
 * the result move. Nothing here is saved; it only shows how the real pages will book it.
 */
defineOptions({
    layout: {
        breadcrumbs: [{ title: 'ভাগ কীভাবে হয়', href: '/guide/split' }],
    },
});

const tabs = [
    { key: 'overhead', label: 'সাধারণ খরচ', icon: HandCoins },
    { key: 'feed', label: 'খাবার (কেজিতে)', icon: Wheat },
    { key: 'grass', label: 'নিজের ঘাস', icon: Sprout },
    { key: 'fertiliser', label: 'সার (জমিতে)', icon: Leaf },
] as const;
const tab = ref<(typeof tabs)[number]['key']>('overhead');

const COLORS = ['bg-paddy', 'bg-turmeric', 'bg-clay', 'bg-pond', 'bg-soil'];
const n = (value: string | number) => Math.max(0, Number(value) || 0);
const tk = (value: number) => taka(Math.round(value * 100));
const round2 = (value: number) => Math.round(value * 100) / 100;

/** Shares a whole-taka amount by weight; the biggest share takes the leftover poisha. */
function splitBy(total: number, weights: number[]): number[] {
    const sum = weights.reduce((a, b) => a + b, 0);

    if (sum <= 0) {
        return weights.map(() => 0);
    }

    const poisha = Math.round(total * 100);
    const parts = weights.map((w) => Math.round((poisha * w) / sum));
    const biggest = weights.indexOf(Math.max(...weights));
    parts[biggest] += poisha - parts.reduce((a, b) => a + b, 0);

    return parts.map((p) => p / 100);
}

/* ---------- 1. সাধারণ খরচ ---------- */
const overhead = reactive({
    costs: [
        { name: 'কারেন্ট বিল', amount: '3000' },
        { name: 'রাখালের বেতন', amount: '9000' },
    ],
    centers: [
        { name: 'গাভী (দুধ)', cows: '4', days: '30' },
        { name: 'বকনা', cows: '3', days: '30' },
        { name: 'মোটাতাজাকরণ', cows: '5', days: '18' },
    ],
});
const overheadTotal = computed(() =>
    overhead.costs.reduce((sum, c) => sum + n(c.amount), 0),
);
const animalDays = computed(() =>
    overhead.centers.map((c) => n(c.cows) * n(c.days)),
);
const animalDaysTotal = computed(() =>
    animalDays.value.reduce((a, b) => a + b, 0),
);
const overheadShares = computed(() =>
    splitBy(overheadTotal.value, animalDays.value),
);
const overheadLines = computed(() =>
    overhead.costs.map((cost) => ({
        name: cost.name,
        parts: splitBy(n(cost.amount), animalDays.value),
    })),
);

/* ---------- 2. খাবার ---------- */
const feed = reactive({
    item: 'গমের ভুসি',
    kg: '50',
    price: '2500',
    centers: [
        { name: 'গাভী (দুধ)', kg: '20', cows: '4' },
        { name: 'বকনা', kg: '12', cows: '3' },
        { name: 'মোটাতাজাকরণ', kg: '18', cows: '5' },
    ],
});
const feedRate = computed(() =>
    n(feed.kg) > 0 ? n(feed.price) / n(feed.kg) : 0,
);
const feedUsed = computed(() =>
    feed.centers.reduce((sum, c) => sum + n(c.kg), 0),
);
const feedShares = computed(() =>
    feed.centers.map((c) => round2(n(c.kg) * feedRate.value)),
);

/* ---------- 3. নিজের ঘাস ---------- */
const grass = reactive({
    land: '10',
    kg: '600',
    rate: '4',
    lease: '1500',
    centers: [
        { name: 'গাভী (দুধ)', kg: '300', cows: '4', days: '5' },
        { name: 'বকনা', kg: '150', cows: '3', days: '5' },
        { name: 'মোটাতাজাকরণ', kg: '150', cows: '5', days: '3' },
    ],
});
const grassValue = computed(() => n(grass.kg) * n(grass.rate));
const grassPerDecimal = computed(() =>
    n(grass.land) > 0 ? round2(n(grass.kg) / n(grass.land)) : 0,
);
const grassUsed = computed(() =>
    grass.centers.reduce((sum, c) => sum + n(c.kg), 0),
);

/* ---------- 4. সার ---------- */
const fert = reactive({
    item: 'ইউরিয়া সার',
    kg: '50',
    price: '1500',
    plots: [
        { name: 'ঘাস চাষ (জমি)', kg: '20', land: '10' },
        { name: 'সবজি বাগান', kg: '15', land: '5' },
        { name: 'ফল বাগান', kg: '15', land: '6' },
    ],
});
const fertRate = computed(() =>
    n(fert.kg) > 0 ? n(fert.price) / n(fert.kg) : 0,
);
const fertUsed = computed(() =>
    fert.plots.reduce((sum, p) => sum + n(p.kg), 0),
);
</script>

<template>
    <Head title="ভাগ কীভাবে হয়" />

    <div class="flex flex-1 flex-col p-4 md:p-6">
        <PageTitle
            title="ভাগ কীভাবে হয় — উদাহরণ"
            subtitle="সংখ্যাগুলো বদলে দেখুন, ফল সাথে সাথে বদলাবে। এখানে কিছু খাতায় ওঠে না — শুধু বোঝার জন্য।"
        />

        <div
            class="mb-5 grid grid-cols-2 gap-1 rounded-lg border border-border bg-card p-1 sm:flex"
        >
            <button
                v-for="t in tabs"
                :key="t.key"
                type="button"
                :class="[
                    'flex shrink-0 items-center justify-center gap-2 rounded-md px-3 py-2 text-sm font-medium transition-colors sm:justify-start',
                    tab === t.key
                        ? 'bg-primary text-primary-foreground'
                        : 'text-muted-foreground hover:bg-accent hover:text-foreground',
                ]"
                @click="tab = t.key"
            >
                <component :is="t.icon" class="size-4" />
                {{ t.label }}
            </button>
        </div>

        <!-- 1. সাধারণ খরচ -->
        <div v-if="tab === 'overhead'" class="demo">
            <div class="step">
                <h3><span class="badge">১</span> মাসজুড়ে যে খরচ সবার জন্য</h3>
                <p class="note">
                    এন্ট্রি দেওয়ার সময় ক্যাটাগরি "সাধারণ খরচ" বেছে নিন। কারও
                    একার নয় এমন খরচ এখানে জমে।
                </p>
                <div v-for="(cost, i) in overhead.costs" :key="i" class="row-2">
                    <span>{{ cost.name }}</span>
                    <Input
                        v-model="cost.amount"
                        type="number"
                        class="num text-right"
                    />
                </div>
                <p class="total">
                    সাধারণ খরচে জমা <b class="num">{{ tk(overheadTotal) }}</b>
                </p>
            </div>

            <ArrowDown class="arrow" />

            <div class="step">
                <h3>
                    <span class="badge">২</span> কোন ক্যাটাগরিতে কতটি গরু কতদিন
                </h3>
                <p class="note">
                    অ্যাপ নিজেই গরুর তালিকা থেকে গুনে নেয় (পশু-দিন = গরু ×
                    দিন)। মাসের মাঝে গরু এলে শুধু যতদিন ছিল ততদিন ধরা হয়।
                </p>
                <div class="grid gap-2">
                    <div
                        v-for="(c, i) in overhead.centers"
                        :key="i"
                        class="grid grid-cols-[1fr_4.5rem_4.5rem_5rem] items-center gap-2 text-sm"
                    >
                        <span class="font-medium">{{ c.name }}</span>
                        <label class="mini"
                            ><Input
                                v-model="c.cows"
                                type="number"
                                class="num text-right"
                            /><small>গরু</small></label
                        >
                        <label class="mini"
                            ><Input
                                v-model="c.days"
                                type="number"
                                class="num text-right"
                            /><small>দিন</small></label
                        >
                        <span class="num text-right"
                            >= {{ animalDays[i] }}
                            <small class="text-muted-foreground"
                                >পশু-দিন</small
                            ></span
                        >
                    </div>
                </div>
                <p class="total">
                    মোট <b class="num">{{ animalDaysTotal }}</b> পশু-দিন
                </p>
            </div>

            <ArrowDown class="arrow" />

            <div class="step">
                <h3><span class="badge">৩</span> অনুপাতে ভাগ</h3>
                <div class="bar">
                    <div
                        v-for="(c, i) in overhead.centers"
                        :key="i"
                        :class="COLORS[i]"
                        :style="{
                            width: `${animalDaysTotal ? (animalDays[i] / animalDaysTotal) * 100 : 0}%`,
                        }"
                    />
                </div>
                <div v-for="(c, i) in overhead.centers" :key="i" class="result">
                    <span class="flex items-center gap-2"
                        ><i :class="['dot', COLORS[i]]" />{{ c.name }}</span
                    >
                    <span class="num text-muted-foreground"
                        >{{ animalDays[i] }} ÷ {{ animalDaysTotal }} =
                        {{
                            animalDaysTotal
                                ? Math.round(
                                      (animalDays[i] / animalDaysTotal) * 1000,
                                  ) / 10
                                : 0
                        }}%</span
                    >
                    <b class="num">{{ tk(overheadShares[i]) }}</b>
                </div>
            </div>

            <ArrowDown class="arrow" />

            <div class="step">
                <h3>
                    <span class="badge">৪</span> খাতায় যা উঠবে ("খরচ ভাগ"
                    পাতায় এক চাপে)
                </h3>
                <p class="note">
                    প্রতিটি খরচ তার নিজের নামেই যায় — গাভীর হিসাবে দেখবেন
                    "কারেন্ট বিল", "রাখালের বেতন" আলাদা করে।
                </p>
                <table class="ledger">
                    <thead>
                        <tr>
                            <th>খাত</th>
                            <th v-for="c in overhead.centers" :key="c.name">
                                {{ c.name }}
                            </th>
                        </tr>
                    </thead>
                    <tbody>
                        <tr v-for="line in overheadLines" :key="line.name">
                            <td>{{ line.name }}</td>
                            <td
                                v-for="(part, i) in line.parts"
                                :key="i"
                                class="num"
                            >
                                {{ tk(part) }}
                            </td>
                        </tr>
                    </tbody>
                </table>
                <p class="total">
                    সাধারণ খরচ থেকে বের হলো {{ tk(overheadTotal) }} → এখন সেখানে
                    <b>৳ 0</b>। পুরো খামারের খরচ একই থাকে, শুধু সঠিক ঘরে গেলো।
                </p>
            </div>
        </div>

        <!-- 2. খাবার -->
        <div v-else-if="tab === 'feed'" class="demo">
            <div class="step">
                <h3><span class="badge">১</span> একবারে কেনা — স্টকে ওঠে</h3>
                <p class="note">
                    "মাল কেনা" দিয়ে কিনলে টাকা এখনো কোনো ক্যাটাগরির খরচ হয় না,
                    স্টকে থাকে।
                </p>
                <div class="grid grid-cols-2 gap-2 text-sm sm:grid-cols-3">
                    <label class="mini wide"
                        ><small>মাল</small><Input v-model="feed.item"
                    /></label>
                    <label class="mini wide"
                        ><small>কেজি</small
                        ><Input v-model="feed.kg" type="number" class="num"
                    /></label>
                    <label class="mini wide"
                        ><small>দাম (৳)</small
                        ><Input v-model="feed.price" type="number" class="num"
                    /></label>
                </div>
                <p class="total">
                    প্রতি কেজি <b class="num">{{ tk(feedRate) }}</b>
                </p>
            </div>

            <ArrowDown class="arrow" />

            <div class="step">
                <h3><span class="badge">২</span> যতটুকু যেখানে খাওয়ানো হলো</h3>
                <p class="note">
                    "গরুকে খাবার দেওয়া" পাতায় প্রতি ক্যাটাগরিতে কেজি লিখুন
                    (সকাল/দুপুর/বিকাল আলাদা করেও)। সেদিন কতটি গরু ছিল, অ্যাপ
                    নিজে লিখে রাখে।
                </p>
                <div
                    v-for="(c, i) in feed.centers"
                    :key="i"
                    class="grid grid-cols-[1fr_5.5rem_auto] items-center gap-2 py-1 text-sm"
                >
                    <span class="font-medium"
                        ><i :class="['dot mr-2', COLORS[i]]" />{{ c.name }}
                        <small class="block pl-4 text-muted-foreground"
                            >{{ c.cows }}টি গরু · প্রতিটি
                            {{ n(c.cows) ? round2(n(c.kg) / n(c.cows)) : 0 }}
                            কেজি</small
                        ></span
                    >
                    <label class="mini"
                        ><Input
                            v-model="c.kg"
                            type="number"
                            class="num text-right"
                        /><small>কেজি</small></label
                    >
                    <b class="num w-24 text-right">{{ tk(feedShares[i]) }}</b>
                </div>
                <p
                    :class="[
                        'total',
                        feedUsed > n(feed.kg) && 'text-destructive',
                    ]"
                >
                    দেওয়া হলো {{ feedUsed }} কেজি · স্টকে বাকি
                    <b class="num">{{ round2(n(feed.kg) - feedUsed) }} কেজি</b>
                    <template v-if="feedUsed > n(feed.kg)">
                        — স্টকের বেশি দেওয়া যায় না, অ্যাপ আটকে দেবে</template
                    >
                </p>
            </div>

            <ArrowDown class="arrow" />

            <div class="step">
                <h3><span class="badge">৩</span> ফল</h3>
                <div class="bar">
                    <div
                        v-for="(c, i) in feed.centers"
                        :key="i"
                        :class="COLORS[i]"
                        :style="{
                            width: `${n(feed.kg) ? (n(c.kg) / n(feed.kg)) * 100 : 0}%`,
                        }"
                    />
                    <div
                        class="bg-muted"
                        :style="{
                            width: `${n(feed.kg) ? Math.max(0, (n(feed.kg) - feedUsed) / n(feed.kg)) * 100 : 0}%`,
                        }"
                    />
                </div>
                <p class="note">
                    প্রতিটি ক্যাটাগরির খরচে উঠলো শুধু তার খাওয়া কেজির দাম।
                    বাকিটা স্টকে থাকে, পরে যে খাবে তার ঘরে যাবে। "কোথায় কত কেজি
                    গেল" রিপোর্টে মাস শেষে প্রতি গরু দিনে কত কেজি খেল তাও
                    দেখাবে।
                </p>
            </div>
        </div>

        <!-- 3. নিজের ঘাস -->
        <div v-else-if="tab === 'grass'" class="demo">
            <div class="step">
                <h3><span class="badge">১</span> নিজের জমি থেকে ঘাস কাটা</h3>
                <p class="note">
                    "নিজের উৎপাদন (ঘাস)" পাতায় কত কেজি, বাজারদর, আর কত শতাংশ
                    জমি থেকে — লিখুন।
                </p>
                <div class="grid grid-cols-2 gap-2 text-sm sm:grid-cols-4">
                    <label class="mini wide"
                        ><small>জমি (শতাংশ)</small
                        ><Input v-model="grass.land" type="number" class="num"
                    /></label>
                    <label class="mini wide"
                        ><small>ঘাস (কেজি)</small
                        ><Input v-model="grass.kg" type="number" class="num"
                    /></label>
                    <label class="mini wide"
                        ><small>বাজারদর (৳/কেজি)</small
                        ><Input v-model="grass.rate" type="number" class="num"
                    /></label>
                    <label class="mini wide"
                        ><small>এ মাসের লিজ খরচ (৳)</small
                        ><Input v-model="grass.lease" type="number" class="num"
                    /></label>
                </div>
                <p class="total">
                    প্রতি শতাংশে <b class="num">{{ grassPerDecimal }} কেজি</b> ·
                    ঘাসের দাম <b class="num">{{ tk(grassValue) }}</b>
                </p>
            </div>

            <ArrowDown class="arrow" />

            <div class="step">
                <h3><span class="badge">২</span> কোন গরুকে কত</h3>
                <div
                    v-for="(c, i) in grass.centers"
                    :key="i"
                    class="grid grid-cols-[1fr_5rem_4rem_4rem] items-center gap-2 py-1 text-sm"
                >
                    <span class="font-medium"
                        ><i :class="['dot mr-2', COLORS[i]]" />{{ c.name }}
                        <small class="block pl-4 text-muted-foreground"
                            >প্রতি গরু দিনে
                            {{
                                n(c.cows) * n(c.days)
                                    ? round2(n(c.kg) / (n(c.cows) * n(c.days)))
                                    : 0
                            }}
                            কেজি · {{ tk(n(c.kg) * n(grass.rate)) }}</small
                        ></span
                    >
                    <label class="mini"
                        ><Input
                            v-model="c.kg"
                            type="number"
                            class="num text-right"
                        /><small>কেজি</small></label
                    >
                    <label class="mini"
                        ><Input
                            v-model="c.cows"
                            type="number"
                            class="num text-right"
                        /><small>গরু</small></label
                    >
                    <label class="mini"
                        ><Input
                            v-model="c.days"
                            type="number"
                            class="num text-right"
                        /><small>দিন</small></label
                    >
                </div>
                <p class="total">
                    {{ grass.land }} শতাংশ জমির {{ grassUsed }} কেজি ঘাসে
                    <b
                        >{{
                            grass.centers.reduce((s, c) => s + n(c.cows), 0)
                        }}টি গরু</b
                    >
                    খেলো · স্টকে বাকি {{ round2(n(grass.kg) - grassUsed) }} কেজি
                </p>
            </div>

            <ArrowDown class="arrow" />

            <div class="step">
                <h3><span class="badge">৩</span> কে কী দেখে</h3>
                <div class="result">
                    <span>ঘাস চাষ (জমি) — ঘাস বিক্রি হিসেবে আয়</span><span /><b
                        class="num text-income"
                        >{{ tk(grassValue) }}</b
                    >
                </div>
                <div class="result">
                    <span>ঘাস চাষ (জমি) — লিজ খরচ</span><span /><b
                        class="num text-expense"
                        >−{{ tk(n(grass.lease)) }}</b
                    >
                </div>
                <div class="result font-semibold">
                    <span>ঘাস চাষে লাভ (নিজে চাষ করা কি সস্তা?)</span><span /><b
                        class="num"
                        >{{ tk(grassValue - n(grass.lease)) }}</b
                    >
                </div>
                <p class="note mt-3">
                    গরুর ক্যাটাগরিগুলোর খরচে ঘাস ওঠে বাজারদরে, তাই কোন গরু কত
                    খরচ খায় তা সঠিক থাকে। কিন্তু পুরো খামারের হিসাবে এই আয়-খরচ
                    কাটাকাটি হয়ে যায় — টাকা খামারের বাইরে যায়নি, তাই খামারের
                    মোট লাভ বাড়িয়ে দেখায় না। আসল খরচ শুধু লিজের
                    {{ tk(n(grass.lease)) }}।
                </p>
            </div>
        </div>

        <!-- 4. সার -->
        <div v-else class="demo">
            <div class="step">
                <h3><span class="badge">১</span> সার কেনা — স্টকে ওঠে</h3>
                <div class="grid grid-cols-2 gap-2 text-sm sm:grid-cols-3">
                    <label class="mini wide"
                        ><small>মাল</small><Input v-model="fert.item"
                    /></label>
                    <label class="mini wide"
                        ><small>কেজি</small
                        ><Input v-model="fert.kg" type="number" class="num"
                    /></label>
                    <label class="mini wide"
                        ><small>দাম (৳)</small
                        ><Input v-model="fert.price" type="number" class="num"
                    /></label>
                </div>
                <p class="total">
                    প্রতি কেজি <b class="num">{{ tk(fertRate) }}</b>
                </p>
            </div>

            <ArrowDown class="arrow" />

            <div class="step">
                <h3>
                    <span class="badge">২</span> কোন জমিতে কত কেজি, কত শতাংশে
                </h3>
                <p class="note">
                    "গরুকে খাবার দেওয়া / ব্যবহার" পাতায় সার বাছলে প্রতি লাইনে
                    জমির শতাংশ চাইবে।
                </p>
                <div
                    v-for="(p, i) in fert.plots"
                    :key="i"
                    class="grid grid-cols-[1fr_5rem_5rem_6rem] items-center gap-2 py-1 text-sm"
                >
                    <span class="font-medium"
                        ><i :class="['dot mr-2', COLORS[i]]" />{{ p.name }}
                        <small class="block pl-4 text-muted-foreground"
                            >প্রতি শতাংশে
                            {{ n(p.land) ? round2(n(p.kg) / n(p.land)) : 0 }}
                            কেজি</small
                        ></span
                    >
                    <label class="mini"
                        ><Input
                            v-model="p.kg"
                            type="number"
                            class="num text-right"
                        /><small>কেজি</small></label
                    >
                    <label class="mini"
                        ><Input
                            v-model="p.land"
                            type="number"
                            class="num text-right"
                        /><small>শতাংশ</small></label
                    >
                    <b class="num text-right">{{ tk(n(p.kg) * fertRate) }}</b>
                </div>
                <p class="total">
                    দেওয়া হলো {{ fertUsed }} কেজি · স্টকে বাকি
                    <b class="num">{{ round2(n(fert.kg) - fertUsed) }} কেজি</b>
                </p>
            </div>

            <ArrowDown class="arrow" />

            <div class="step">
                <h3><span class="badge">৩</span> ফল</h3>
                <div class="bar">
                    <div
                        v-for="(p, i) in fert.plots"
                        :key="i"
                        :class="COLORS[i]"
                        :style="{
                            width: `${n(fert.kg) ? (n(p.kg) / n(fert.kg)) * 100 : 0}%`,
                        }"
                    />
                </div>
                <p class="note">
                    সবজি বাগানের খরচে উঠলো শুধু তার জমিতে পড়া সারের দাম, ফল
                    বাগানের খরচে তারটুকু। ঘাসের জমির সার ঘাস চাষের খরচ — তাই
                    ঘাসের প্রতি কেজির আসল খরচও বোঝা যায়।
                </p>
            </div>
        </div>

        <p class="mt-6 text-sm text-muted-foreground">
            আসল কাজ করতে:
            <Link href="/allocation" class="underline">খরচ ভাগ</Link> ·
            <Link href="/stock/move?mode=issue" class="underline"
                >গরুকে খাবার দেওয়া</Link
            >
            ·
            <Link href="/stock/move?mode=produce" class="underline"
                >নিজের উৎপাদন</Link
            >
            ·
            <Link href="/reports/usage" class="underline"
                >কোথায় কত কেজি গেল</Link
            >
        </p>
    </div>
</template>

<style scoped>
@reference '../../../css/app.css';

.demo {
    @apply mx-auto grid w-full max-w-3xl gap-1;
}
.step {
    @apply rounded-xl border border-border bg-card p-4;
}
.step h3 {
    @apply mb-2 flex items-center gap-2 text-lg;
    font-family: var(--font-display);
}
.badge {
    @apply inline-flex size-7 shrink-0 items-center justify-center rounded-full bg-primary text-sm text-primary-foreground;
}
.note {
    @apply mb-3 text-sm text-muted-foreground;
}
.total {
    @apply mt-3 border-t border-border/70 pt-3 text-sm;
}
.arrow {
    @apply mx-auto my-1 size-5 text-muted-foreground;
}
.row-2 {
    @apply grid grid-cols-[1fr_8rem] items-center gap-2 py-1 text-sm;
}
.mini {
    @apply relative block;
}
.mini small {
    @apply mt-0.5 block text-center text-[11px] text-muted-foreground;
}
.mini.wide small {
    @apply mt-0 mb-1 text-left;
}
.bar {
    @apply mb-3 flex h-4 overflow-hidden rounded-full bg-muted;
}
.bar > div {
    @apply h-full transition-[width] duration-300;
}
.dot {
    @apply inline-block size-2.5 shrink-0 rounded-full;
}
.result {
    @apply grid grid-cols-[1fr_auto_6.5rem] items-center gap-3 border-b border-border/50 py-2 text-sm last:border-0;
}
.result b {
    @apply text-right;
}
.ledger {
    @apply w-full text-sm;
}
.ledger th {
    @apply border-b border-border pb-2 text-right text-xs font-medium text-muted-foreground first:text-left;
}
.ledger td {
    @apply border-b border-border/50 py-2 text-right first:text-left;
}
</style>
