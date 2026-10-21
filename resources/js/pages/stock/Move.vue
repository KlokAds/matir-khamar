<script setup lang="ts">
import { Head, useForm } from '@inertiajs/vue3';
import { Paperclip, Plus, Sprout, Truck, Wheat, X } from '@lucide/vue';
import { computed, watch } from 'vue';
import Field from '@/components/khamar/Field.vue';
import SessionPicker from '@/components/khamar/SessionPicker.vue';
import NSelect from '@/components/khamar/NSelect.vue';
import PageTitle from '@/components/khamar/PageTitle.vue';
import { Button } from '@/components/ui/button';
import DateField from '@/components/khamar/DateField.vue';
import { Input } from '@/components/ui/input';
import { centerLabel, currentSession, qty, taka, today } from '@/lib/khamar';
import type { Center, Option } from '@/lib/khamar';

defineOptions({
    layout: {
        breadcrumbs: [
            { title: 'স্টক', href: '/stock' },
            { title: 'মাল আনা/দেওয়া', href: '/stock/move' },
        ],
    },
});

type StockItem = {
    id: number;
    name: string;
    unit: string;
    unit_label: string;
    kind: string;
    qty_on_hand: number;
    value_on_hand: number;
    average_cost: number;
};

const props = defineProps<{
    mode: 'purchase' | 'issue' | 'produce';
    sessions: Record<string, string>;
    items: StockItem[];
    centers: Center[];
    moneyAccounts: Option[];
    budgetHeads: { id: number; code: string; name: string }[];
    suppliers: { id: number; name: string }[];
    canPurchase: boolean;
    maxVoucherKb: number;
    grassCenterId: number | null;
    herd: Record<string, number>;
}>();

const tabs = computed(() =>
    [
        {
            key: 'issue',
            label: 'গরুকে খাওয়ানো / ব্যবহার',
            icon: Wheat,
            show: true,
        },
        {
            key: 'purchase',
            label: 'মাল কেনা (স্টকে)',
            icon: Truck,
            show: props.canPurchase,
        },
        {
            key: 'produce',
            label: 'নিজের উৎপাদন (ঘাস)',
            icon: Sprout,
            show: props.canPurchase,
        },
    ].filter((tab) => tab.show),
);

// For the dropdowns: project groups show as headings so the tree reads right.
const centerChoices = computed(() =>
    props.centers.filter((center) => center.kind !== 'overhead'),
);
const animalCenters = computed(() =>
    props.centers.filter(
        (center) => center.kind !== 'group' && center.kind !== 'overhead',
    ),
);

const f = useForm({
    mode: props.mode,
    item_id:
        (props.mode === 'issue'
            ? props.items.find((i) => i.qty_on_hand > 0)
            : undefined
        )?.id ??
        props.items[0]?.id ??
        null,
    date: today(),
    qty: '',
    amount: '',
    rate: '',
    settle: 'money',
    money_account_id: props.moneyAccounts[0]?.id ?? null,
    party_id: null as number | null,
    budget_head_id: null as number | null,
    cost_center_id: props.grassCenterId,
    description: '',
    note: '',
    splits: animalCenters.value
        .filter((c) => c.holds_animals)
        .map((c) => ({ cost_center_id: c.id, qty: '', land: '' })),
    attachments: [] as File[],
    stay: true,
    session: currentSession(),
    land_decimals: '',
});

const item = computed(() => props.items.find((i) => i.id === f.item_id));
const issueTotal = computed(() =>
    f.splits.reduce((sum, split) => sum + (parseFloat(split.qty) || 0), 0),
);
const overStock = computed(
    () =>
        item.value !== undefined &&
        issueTotal.value > item.value.qty_on_hand + 0.0005,
);
// Fertiliser and seed go on land, so each line also asks how many decimals (শতাংশ).
const onLand = computed(() => item.value?.kind === 'crop_input');
const herdOf = (id: number) => props.herd[String(id)] ?? 0;
const perHead = (split: { cost_center_id: number; qty: string }) => {
    const head = herdOf(split.cost_center_id);
    const amount = parseFloat(split.qty) || 0;

    return head > 0 && amount > 0
        ? `${head}টি গরু · প্রতিটি ${Math.round((amount / head) * 100) / 100} ${item.value?.unit_label ?? ''}`
        : head > 0
          ? `এখন ${head}টি গরু`
          : '';
};
const centerName = (id: number) =>
    props.centers.find((c) => c.id === id)?.name ?? '';

watch(
    () => f.mode,
    () => {
        f.clearErrors();
        window.history.replaceState({}, '', `/stock/move?mode=${f.mode}`);
    },
);

function pickFiles(event: Event) {
    f.attachments = Array.from(
        (event.target as HTMLInputElement).files ?? [],
    ).slice(0, 5);
}

function addSplit() {
    const unused = animalCenters.value.find(
        (c) => !f.splits.some((s) => s.cost_center_id === c.id),
    );

    if (unused) {
        f.splits.push({ cost_center_id: unused.id, qty: '', land: '' });
    }
}

function submit() {
    const url = {
        purchase: '/stock/purchase',
        issue: '/stock/issue',
        produce: '/stock/produce',
    }[f.mode];
    f.post(url, {
        forceFormData: f.mode === 'purchase',
        preserveScroll: true,
        onSuccess: () => {
            f.reset(
                'qty',
                'amount',
                'rate',
                'description',
                'note',
                'attachments',
                'land_decimals',
            );
            f.splits.forEach((split) => {
                split.qty = '';
                split.land = '';
            });
        },
    });
}
</script>

<template>
    <Head title="মাল আনা / দেওয়া" />

    <div class="flex flex-1 flex-col p-4 md:p-6">
        <PageTitle
            title="খাবার ও ঔষধের স্টক"
            subtitle="কেনা মাল আগে স্টকে ওঠে। যতটুকু যে ক্যাটাগরিতে খাওয়ানো হয়, ঠিক ততটুকুর দাম সেখানে খরচ হয়।"
        />

        <div class="mb-5 flex flex-wrap gap-1.5">
            <button
                v-for="tab in tabs"
                :key="tab.key"
                type="button"
                :class="[
                    'flex items-center gap-1.5 rounded-full border px-3.5 py-1.5 text-sm',
                    f.mode === tab.key
                        ? 'border-primary bg-primary text-primary-foreground'
                        : 'border-border bg-card hover:bg-accent',
                ]"
                @click="f.mode = tab.key as typeof f.mode"
            >
                <component :is="tab.icon" class="size-4" /> {{ tab.label }}
            </button>
        </div>

        <form class="grid gap-5 lg:grid-cols-3" @submit.prevent="submit">
            <section
                class="space-y-4 rounded-xl border border-border bg-card p-4 lg:col-span-2"
            >
                <div class="grid gap-4 sm:grid-cols-2">
                    <Field label="কোন মাল" :error="f.errors.item_id">
                        <NSelect v-model="f.item_id">
                            <option
                                v-for="i in items"
                                :key="i.id"
                                :value="i.id"
                            >
                                {{ i.name }} — আছে
                                {{ qty(i.qty_on_hand, i.unit_label) }}
                            </option>
                        </NSelect>
                    </Field>
                    <Field label="তারিখ" :error="f.errors.date">
                        <DateField v-model="f.date" :max="today()" />
                    </Field>
                </div>

                <template v-if="f.mode === 'issue'">
                    <SessionPicker v-model="f.session" :sessions="sessions" />
                    <div class="rounded-lg bg-muted/60 p-3 text-sm">
                        স্টকে আছে
                        <b class="num">{{
                            qty(item?.qty_on_hand, item?.unit_label)
                        }}</b
                        >, গড় দর
                        <b class="num">{{
                            taka(Math.round(item?.average_cost ?? 0))
                        }}</b>
                        / {{ item?.unit_label }}
                    </div>
                    <p v-if="f.errors.splits" class="text-sm text-destructive">
                        {{ f.errors.splits }}
                    </p>
                    <div class="space-y-2">
                        <div
                            v-for="(split, index) in f.splits"
                            :key="index"
                            class="grid grid-cols-[1fr_auto_auto] items-center gap-2 rounded-lg border border-border/70 bg-background/60 p-2 sm:grid-cols-[1fr_9rem_6.5rem_auto]"
                        >
                            <NSelect
                                v-model="split.cost_center_id"
                                class="col-span-3 sm:col-span-1"
                            >
                                <option
                                    v-for="c in centerChoices"
                                    :key="c.id"
                                    :value="c.id"
                                    :disabled="c.kind === 'group'"
                                >
                                    {{ centerLabel(c) }}
                                </option>
                            </NSelect>
                            <div class="relative w-36 sm:w-full">
                                <Input
                                    v-model="split.qty"
                                    type="number"
                                    inputmode="decimal"
                                    step="0.001"
                                    min="0"
                                    class="num h-12 pr-12 text-right text-lg"
                                    placeholder="0"
                                />
                                <span
                                    class="pointer-events-none absolute inset-y-0 right-3 flex items-center text-xs text-muted-foreground"
                                    >{{ item?.unit_label }}</span
                                >
                            </div>
                            <span
                                class="num text-right text-sm text-muted-foreground"
                                >{{
                                    taka(
                                        Math.round(
                                            (parseFloat(split.qty) || 0) *
                                                (item?.average_cost ?? 0),
                                        ),
                                    )
                                }}</span
                            >
                            <button
                                type="button"
                                class="rounded p-1.5 text-muted-foreground hover:bg-accent"
                                aria-label="সরান"
                                @click="f.splits.splice(index, 1)"
                            >
                                <X class="size-4" />
                            </button>
                            <div
                                v-if="onLand"
                                class="col-span-3 flex items-center gap-2 text-sm sm:col-span-4"
                            >
                                <span class="text-muted-foreground"
                                    >কত শতাংশ জমিতে</span
                                >
                                <div class="relative w-32">
                                    <Input
                                        v-model="split.land"
                                        type="number"
                                        inputmode="decimal"
                                        step="0.01"
                                        min="0"
                                        class="num pr-14 text-right"
                                        placeholder="0"
                                    />
                                    <span
                                        class="pointer-events-none absolute inset-y-0 right-3 flex items-center text-xs text-muted-foreground"
                                        >শতাংশ</span
                                    >
                                </div>
                                <span
                                    v-if="
                                        parseFloat(split.land) > 0 &&
                                        parseFloat(split.qty) > 0
                                    "
                                    class="num text-xs text-muted-foreground"
                                    >প্রতি শতাংশে
                                    {{
                                        Math.round(
                                            (parseFloat(split.qty) /
                                                parseFloat(split.land)) *
                                                100,
                                        ) / 100
                                    }}
                                    {{ item?.unit_label }}</span
                                >
                            </div>
                            <p
                                v-else-if="perHead(split)"
                                class="col-span-3 px-1 text-xs text-muted-foreground sm:col-span-4"
                            >
                                {{ perHead(split) }}
                            </p>
                        </div>
                    </div>
                    <Button
                        type="button"
                        size="sm"
                        variant="secondary"
                        @click="addSplit"
                        ><Plus /> আরেক ক্যাটাগরি</Button
                    >
                    <Field label="নোট (ঐচ্ছিক)">
                        <Input
                            v-model="f.note"
                            placeholder="যেমন: সকাল-বিকাল দুই বেলা"
                        />
                    </Field>
                </template>

                <template v-if="f.mode === 'purchase'">
                    <div class="grid gap-4 sm:grid-cols-2">
                        <Field
                            :label="`পরিমাণ (${item?.unit_label ?? ''})`"
                            :error="f.errors.qty"
                            hint="মণে কিনলে ৪০ দিয়ে গুণ করে কেজি লিখুন"
                        >
                            <Input
                                v-model="f.qty"
                                type="number"
                                inputmode="decimal"
                                step="0.001"
                                min="0"
                                class="num"
                                required
                            />
                        </Field>
                        <Field
                            label="মোট দাম (৳)"
                            :error="f.errors.amount"
                            :hint="
                                f.qty && f.amount
                                    ? `প্রতি ${item?.unit_label} ${taka(Math.round((parseFloat(f.amount) * 100) / parseFloat(f.qty)))}`
                                    : ''
                            "
                        >
                            <Input
                                v-model="f.amount"
                                type="number"
                                inputmode="decimal"
                                step="0.01"
                                min="0"
                                class="num"
                                required
                            />
                        </Field>
                        <Field label="টাকা কীভাবে">
                            <NSelect v-model="f.settle">
                                <option value="money">
                                    নগদ/ব্যাংক থেকে দেওয়া
                                </option>
                                <option value="credit">বাকিতে কেনা</option>
                            </NSelect>
                        </Field>
                        <Field
                            v-if="f.settle === 'money'"
                            label="কোথা থেকে"
                            :error="f.errors.money_account_id"
                        >
                            <NSelect v-model="f.money_account_id">
                                <option
                                    v-for="a in moneyAccounts"
                                    :key="a.id"
                                    :value="a.id"
                                >
                                    {{ a.name }}
                                </option>
                            </NSelect>
                        </Field>
                        <Field label="সরবরাহকারী" :error="f.errors.party_id">
                            <NSelect v-model="f.party_id">
                                <option :value="null">— বাছুন —</option>
                                <option
                                    v-for="p in suppliers"
                                    :key="p.id"
                                    :value="p.id"
                                >
                                    {{ p.name }}
                                </option>
                            </NSelect>
                        </Field>
                        <Field label="উদ্যোগ বাজেট খাত">
                            <NSelect v-model="f.budget_head_id">
                                <option :value="null">— নয় —</option>
                                <option
                                    v-for="b in budgetHeads"
                                    :key="b.id"
                                    :value="b.id"
                                >
                                    {{ b.code }} · {{ b.name }}
                                </option>
                            </NSelect>
                        </Field>
                        <Field label="বিবরণ">
                            <Input v-model="f.description" />
                        </Field>
                        <Field label="রসিদের ছবি" :error="f.errors.attachments">
                            <label
                                class="flex cursor-pointer items-center gap-2 rounded-md border border-dashed border-input px-3 py-2 text-sm hover:bg-accent"
                            >
                                <Paperclip
                                    class="size-4 text-muted-foreground"
                                />
                                <span class="truncate">{{
                                    f.attachments.length
                                        ? f.attachments
                                              .map((x) => x.name)
                                              .join(', ')
                                        : 'ছবি / PDF'
                                }}</span>
                                <input
                                    type="file"
                                    class="sr-only"
                                    accept="image/*,application/pdf"
                                    capture="environment"
                                    multiple
                                    @change="pickFiles"
                                />
                            </label>
                        </Field>
                    </div>
                </template>

                <template v-if="f.mode === 'produce'">
                    <p class="rounded-lg bg-paddy/10 p-3 text-sm">
                        নিজের জমির ঘাস কাটলে বাজারদরে স্টকে তুলুন। ঘাস চাষ
                        ক্যাটাগরি তখন দেখাবে — নিজে চাষ করা কেনার চেয়ে লাভজনক
                        কি না। কত শতাংশ জমি থেকে কাটলেন লিখলে রিপোর্টে দেখা যাবে
                        প্রতি শতাংশে কত কেজি হলো।
                    </p>
                    <div class="grid gap-4 sm:grid-cols-2 lg:grid-cols-4">
                        <Field
                            :label="`পরিমাণ (${item?.unit_label ?? ''})`"
                            :error="f.errors.qty"
                        >
                            <Input
                                v-model="f.qty"
                                type="number"
                                step="0.001"
                                min="0"
                                class="num"
                                required
                            />
                        </Field>
                        <Field
                            :label="`দর (৳ / ${item?.unit_label ?? ''})`"
                            :error="f.errors.rate"
                        >
                            <Input
                                v-model="f.rate"
                                type="number"
                                step="0.01"
                                min="0"
                                class="num"
                                required
                            />
                        </Field>
                        <Field
                            label="কোন ক্যাটাগরি উৎপাদন করলো"
                            :error="f.errors.cost_center_id"
                        >
                            <NSelect v-model="f.cost_center_id">
                                <option
                                    v-for="c in centerChoices"
                                    :key="c.id"
                                    :value="c.id"
                                    :disabled="c.kind === 'group'"
                                >
                                    {{ centerLabel(c) }}
                                </option>
                            </NSelect>
                        </Field>
                        <Field
                            label="কত শতাংশ জমি থেকে (ঐচ্ছিক)"
                            :error="f.errors.land_decimals"
                            :hint="
                                parseFloat(f.land_decimals) > 0 &&
                                parseFloat(f.qty) > 0
                                    ? `প্রতি শতাংশে ${Math.round((parseFloat(f.qty) / parseFloat(f.land_decimals)) * 10) / 10} ${item?.unit_label ?? ''}`
                                    : undefined
                            "
                        >
                            <Input
                                v-model="f.land_decimals"
                                type="number"
                                step="0.01"
                                min="0"
                                class="num"
                                placeholder="যেমন 10"
                            />
                        </Field>
                    </div>
                </template>
            </section>

            <aside class="space-y-4">
                <section
                    v-if="f.mode === 'issue'"
                    class="rounded-xl border border-primary/30 bg-primary/5 p-4"
                >
                    <p class="text-sm text-muted-foreground">
                        মোট দেওয়া হচ্ছে
                    </p>
                    <p class="num font-display text-3xl">
                        {{ qty(issueTotal, item?.unit_label) }}
                    </p>
                    <p class="num text-sm">
                        ≈
                        {{
                            taka(
                                Math.round(
                                    issueTotal * (item?.average_cost ?? 0),
                                ),
                            )
                        }}
                    </p>
                    <ul class="mt-3 space-y-1 text-sm">
                        <li
                            v-for="split in f.splits.filter(
                                (s) => parseFloat(s.qty) > 0,
                            )"
                            :key="split.cost_center_id"
                            class="flex justify-between"
                        >
                            <span>{{ centerName(split.cost_center_id) }}</span>
                            <span class="num">{{
                                taka(
                                    Math.round(
                                        parseFloat(split.qty) *
                                            (item?.average_cost ?? 0),
                                    ),
                                )
                            }}</span>
                        </li>
                    </ul>
                    <p v-if="overStock" class="mt-3 text-sm text-destructive">
                        স্টকে এত মাল নেই।
                    </p>
                    <Button
                        type="submit"
                        class="mt-4 w-full"
                        :disabled="f.processing || overStock || issueTotal <= 0"
                        >লিখে রাখুন</Button
                    >
                </section>
                <section
                    v-else
                    class="rounded-xl border border-primary/30 bg-primary/5 p-4"
                >
                    <p class="text-sm text-muted-foreground">
                        {{
                            f.mode === 'purchase'
                                ? 'স্টকে উঠবে'
                                : 'উৎপাদনের মূল্য'
                        }}
                    </p>
                    <p class="num font-display text-3xl">
                        {{
                            f.mode === 'purchase'
                                ? taka(
                                      Math.round(
                                          (parseFloat(f.amount) || 0) * 100,
                                      ),
                                  )
                                : taka(
                                      Math.round(
                                          (parseFloat(f.qty) || 0) *
                                              (parseFloat(f.rate) || 0) *
                                              100,
                                      ),
                                  )
                        }}
                    </p>
                    <Button
                        type="submit"
                        class="mt-4 w-full"
                        :disabled="f.processing"
                        >সংরক্ষণ করুন</Button
                    >
                </section>
            </aside>
        </form>
    </div>
</template>
