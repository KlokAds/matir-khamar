<script setup lang="ts">
import { Head, useForm } from '@inertiajs/vue3';
import { Paperclip, Plus, X } from '@lucide/vue';
import { computed, watch } from 'vue';
import Field from '@/components/khamar/Field.vue';
import NSelect from '@/components/khamar/NSelect.vue';
import PageTitle from '@/components/khamar/PageTitle.vue';
import { Button } from '@/components/ui/button';
import DateField from '@/components/khamar/DateField.vue';
import { Input } from '@/components/ui/input';
import { t, tAll } from '@/lib/i18n';
import { centerLabel, taka, today } from '@/lib/khamar';
import type { Center, Option } from '@/lib/khamar';

defineOptions({
    layout: {
        breadcrumbs: [{ title: 'নতুন এন্ট্রি', href: '/entries/create' }],
    },
});

const props = defineProps<{
    form: string;
    forms: Record<string, string>;
    centers: Center[];
    moneyAccounts: Option[];
    expenseAccounts: Option[];
    incomeAccounts: Option[];
    assetAccounts: Option[];
    budgetHeads: { id: number; code: string; name: string }[];
    parties: { id: number; name: string; type: string }[];
    partyTypes: Record<string, string>;
    animals: {
        id: number;
        tag: string;
        name: string | null;
        cost_center_id: number;
    }[];
    defaultCenterId: number | null;
    maxVoucherKb: number;
}>();

type Line = {
    account_id: number | null;
    cost_center_id: number | null;
    amount: string;
    budget_head_id: number | null;
    animal_id: number | null;
    memo: string;
};

const blankLine = (): Line => ({
    account_id: null,
    cost_center_id: props.defaultCenterId,
    amount: '',
    budget_head_id: null,
    animal_id: null,
    memo: '',
});

const f = useForm({
    form: props.form,
    date: today(),
    description: '',
    reference: '',
    party_id: null as number | null,
    settle: 'money',
    money_account_id: props.moneyAccounts[0]?.id ?? null,
    from_account_id: props.moneyAccounts[0]?.id ?? null,
    to_account_id: props.moneyAccounts[1]?.id ?? null,
    asset_account_id: props.assetAccounts[0]?.id ?? null,
    cost_center_id: props.defaultCenterId,
    budget_head_id: null as number | null,
    amount: '',
    principal: '',
    interest: '',
    months: 12,
    start_month: today().slice(0, 7) + '-01',
    lines: [blankLine()] as Line[],
    attachments: [] as File[],
    stay: false,
});

const isSplit = computed(() => f.form === 'expense' || f.form === 'income');
const lineAccounts = computed(() =>
    f.form === 'expense' ? props.expenseAccounts : props.incomeAccounts,
);
const total = computed(() =>
    f.lines.reduce(
        (sum, line) => sum + Math.round((parseFloat(line.amount) || 0) * 100),
        0,
    ),
);

const partyFilter = computed(() => {
    switch (f.form) {
        case 'investment':
        case 'withdrawal':
            return 'investor';
        case 'loan':
        case 'loan_repayment':
            return 'lender';
        case 'supplier_payment':
            return 'supplier';
        case 'customer_receipt':
            return 'customer';
        case 'expense':
            return f.settle === 'credit' ? 'supplier' : null;
        case 'income':
            return f.settle === 'credit' ? 'customer' : null;
        default:
            return null;
    }
});

const partyOptions = computed(() =>
    partyFilter.value
        ? props.parties.filter((p) => p.type === partyFilter.value)
        : props.parties,
);
const needsParty = computed(
    () =>
        [
            'investment',
            'withdrawal',
            'loan',
            'loan_repayment',
            'supplier_payment',
            'customer_receipt',
        ].includes(f.form) ||
        (isSplit.value && f.settle === 'credit'),
);

watch(
    () => f.form,
    () => {
        f.clearErrors();
        f.lines = [blankLine()];
        f.party_id = null;
        f.settle = 'money';

        if (f.form === 'lease') {
            f.cost_center_id =
                props.centers.find((c) => c.code === 'GHAS')?.id ??
                props.defaultCenterId;
        }

        window.history.replaceState({}, '', `/entries/create?form=${f.form}`);
    },
);

const animalsFor = (centerId: number | null) =>
    props.animals.filter((a) => a.cost_center_id === centerId);

function pickFiles(event: Event) {
    const input = event.target as HTMLInputElement;
    f.attachments = Array.from(input.files ?? []).slice(0, 5);
}

function submit(stay = false) {
    f.stay = stay;
    f.post('/entries', {
        forceFormData: true,
        preserveScroll: true,
        onSuccess: () => {
            if (stay) {
                f.reset(
                    'amount',
                    'principal',
                    'interest',
                    'description',
                    'reference',
                    'attachments',
                );
                f.lines = [blankLine()];
            }
        },
    });
}

const lineError = (index: number, key: string) =>
    (f.errors as Record<string, string>)[`lines.${index}.${key}`];

const formLabels = computed(() => tAll(props.forms));
const partyTypeLabels = computed(() => tAll(props.partyTypes));
const tCenter = (c: Center) => centerLabel({ ...c, name: t(c.name) });

const help = computed<Record<string, string>>(() => ({
    expense: t(
        'একটা বিল কয়েক ক্যাটাগরিতে ভাগ হলে আলাদা লাইনে লিখুন। যে খরচ সব গরুর জন্য একসাথে (যেমন কারেন্ট বিল, রাখালের বেতন), সেটা "সাধারণ খরচ"-এ দিন — মাস শেষে "খরচ ভাগ" পাতায় এক চাপে গরুর সংখ্যা অনুযায়ী গাভী, বকনা, মোটাতাজায় ভাগ হয়ে যাবে। খাবার/ঔষধ কিনলে এখানে নয়, স্টক → মাল ক্রয় দিন।',
    ),
    income: t(
        'দুধ, গোবর, সবজি বিক্রি — কোন ক্যাটাগরির আয় সেটা বাছুন। পশু বিক্রি হলে গরুর কার্ড থেকে করুন।',
    ),
    asset: t(
        'জমি কেনা, শেড বানানো, মেশিন কেনা খরচ নয়, খামারের সম্পদ। তাই লাভ-ক্ষতিতে আসে না, সম্পদে জমা থাকে।',
    ),
    lease: t(
        'পুরো লিজের টাকা একবারে দিলেও প্রতি মাসে তার ভাগ ওই জমির ক্যাটাগরিতে খরচ হিসেবে বসবে।',
    ),
    investment: t(
        'কে কত টাকা খামারে দিলেন — প্রত্যেক বিনিয়োগকারীর আলাদা মূলধন খাতা থাকে।',
    ),
    withdrawal: t('বিনিয়োগকারী খামার থেকে টাকা তুলে নিলে।'),
    loan: t(
        'ব্যাংক বা কারো কাছ থেকে ঋণ পেলে। ঋণদাতাকে আগে "ক্রেতা, বিনিয়োগকারী…" পাতায় যোগ করুন।',
    ),
    loan_repayment: t('কিস্তিতে আসল ঋণ কমায়, সুদ খরচ হিসেবে যায়।'),
    grant: t(
        'উদ্যোগ কর্মসূচির অনুদান। শর্ত পূরণ না হলে এটা ঋণে বদলে যেতে পারে, তাই আলাদা রাখা হয়।',
    ),
    transfer: t(
        'ক্যাশ থেকে ব্যাংকে, বিকাশ থেকে ক্যাশে — খামারের ভেতরেই টাকা সরানো।',
    ),
    supplier_payment: t('আগে বাকিতে কেনা মালের টাকা পরিশোধ।'),
    customer_receipt: t('আগে বাকিতে বিক্রির টাকা পাওয়া।'),
}));
</script>

<template>
    <Head :title="t('নতুন এন্ট্রি')" />

    <div class="flex flex-1 flex-col p-4 md:p-6">
        <PageTitle
            :title="t('নতুন এন্ট্রি')"
            :subtitle="
                t(
                    'যা লিখবেন তা এক টাকাও না হারিয়ে খাতায় উঠবে — ভুল হলে মুছে নয়, উল্টো এন্ট্রি দিয়ে ঠিক হয়।',
                )
            "
        />

        <div class="mb-5 flex flex-wrap gap-1.5">
            <button
                v-for="(label, key) in formLabels"
                :key="key"
                type="button"
                :class="[
                    'inline-flex min-h-8 items-center gap-1.5 rounded-full border px-3 py-1 text-sm transition-colors',
                    f.form === key
                        ? 'border-primary bg-primary text-primary-foreground'
                        : 'border-border bg-card hover:bg-accent',
                ]"
                @click="f.form = key"
            >
                {{ label }}
            </button>
        </div>

        <form class="grid gap-5 lg:grid-cols-3" @submit.prevent="submit(false)">
            <div class="space-y-5 lg:col-span-2">
                <section
                    class="min-w-0 rounded-xl border border-border bg-card p-4"
                >
                    <p
                        class="mb-4 rounded-lg bg-muted/70 p-3 text-sm text-muted-foreground"
                    >
                        {{ help[f.form] }}
                    </p>

                    <div class="grid gap-4 sm:grid-cols-2">
                        <Field :label="t('তারিখ')" :error="f.errors.date">
                            <DateField
                                v-model="f.date"
                                :max="today()"
                                required
                            />
                        </Field>

                        <Field
                            v-if="isSplit"
                            :label="t('টাকা কীভাবে')"
                            :error="f.errors.settle"
                        >
                            <NSelect v-model="f.settle">
                                <option value="money">
                                    {{
                                        f.form === 'expense'
                                            ? t('নগদ/ব্যাংক থেকে দেওয়া হয়েছে')
                                            : t('নগদ/ব্যাংকে পাওয়া গেছে')
                                    }}
                                </option>
                                <option value="credit">
                                    {{
                                        f.form === 'expense'
                                            ? t('বাকিতে কেনা')
                                            : t('বাকিতে বিক্রি')
                                    }}
                                </option>
                            </NSelect>
                        </Field>

                        <Field
                            v-if="
                                (isSplit && f.settle === 'money') ||
                                [
                                    'asset',
                                    'lease',
                                    'investment',
                                    'withdrawal',
                                    'loan',
                                    'loan_repayment',
                                    'grant',
                                    'supplier_payment',
                                    'customer_receipt',
                                ].includes(f.form)
                            "
                            :label="
                                [
                                    'income',
                                    'investment',
                                    'loan',
                                    'grant',
                                    'customer_receipt',
                                ].includes(f.form)
                                    ? t('টাকা কোথায় এল')
                                    : t('টাকা কোথা থেকে গেল')
                            "
                            :error="f.errors.money_account_id"
                        >
                            <NSelect
                                v-model="f.money_account_id"
                                manage="money"
                            >
                                <option
                                    v-for="a in moneyAccounts"
                                    :key="a.id"
                                    :value="a.id"
                                >
                                    {{ t(a.name) }}
                                </option>
                            </NSelect>
                        </Field>

                        <template v-if="f.form === 'transfer'">
                            <Field
                                :label="t('কোথা থেকে')"
                                :error="f.errors.from_account_id"
                            >
                                <NSelect
                                    v-model="f.from_account_id"
                                    manage="money"
                                >
                                    <option
                                        v-for="a in moneyAccounts"
                                        :key="a.id"
                                        :value="a.id"
                                    >
                                        {{ t(a.name) }}
                                    </option>
                                </NSelect>
                            </Field>
                            <Field
                                :label="t('কোথায়')"
                                :error="f.errors.to_account_id"
                            >
                                <NSelect
                                    v-model="f.to_account_id"
                                    manage="money"
                                >
                                    <option
                                        v-for="a in moneyAccounts"
                                        :key="a.id"
                                        :value="a.id"
                                    >
                                        {{ t(a.name) }}
                                    </option>
                                </NSelect>
                            </Field>
                        </template>

                        <Field
                            v-if="needsParty || isSplit"
                            :label="
                                partyFilter
                                    ? partyTypeLabels[partyFilter]
                                    : t('কার সাথে (ঐচ্ছিক)')
                            "
                            :error="f.errors.party_id"
                            :hint="
                                t(
                                    'তালিকায় না থাকলে তালিকার নিচে “নতুন যোগ করুন” চাপুন',
                                )
                            "
                        >
                            <NSelect
                                v-model="f.party_id"
                                manage="party"
                                :manage-extra="{
                                    party_type: partyFilter ?? 'other',
                                }"
                            >
                                <option :value="null">
                                    {{ t('— বাছুন —') }}
                                </option>
                                <option
                                    v-for="p in partyOptions"
                                    :key="p.id"
                                    :value="p.id"
                                >
                                    {{ p.name }}
                                </option>
                            </NSelect>
                        </Field>

                        <Field
                            v-if="f.form === 'asset'"
                            :label="t('কী কেনা হলো')"
                            :error="f.errors.asset_account_id"
                        >
                            <NSelect v-model="f.asset_account_id">
                                <option
                                    v-for="a in assetAccounts"
                                    :key="a.id"
                                    :value="a.id"
                                >
                                    {{ t(a.name) }}
                                </option>
                            </NSelect>
                        </Field>

                        <Field
                            v-if="
                                ['asset', 'lease', 'loan_repayment'].includes(
                                    f.form,
                                )
                            "
                            :label="
                                f.form === 'loan_repayment'
                                    ? t('সুদ কোন ক্যাটাগরিতে')
                                    : t('কোন ক্যাটাগরি / প্রজেক্ট')
                            "
                            :error="f.errors.cost_center_id"
                        >
                            <NSelect v-model="f.cost_center_id" manage="center">
                                <option
                                    v-for="c in centers"
                                    :key="c.id"
                                    :value="c.id"
                                    :disabled="c.kind === 'group'"
                                >
                                    {{ tCenter(c) }}
                                </option>
                            </NSelect>
                        </Field>

                        <Field
                            v-if="!isSplit && f.form !== 'loan_repayment'"
                            :label="t('টাকা (৳)')"
                            :error="f.errors.amount"
                        >
                            <Input
                                v-model="f.amount"
                                type="number"
                                inputmode="decimal"
                                step="0.01"
                                min="0"
                                placeholder="0"
                                class="num text-lg"
                                required
                            />
                        </Field>

                        <template v-if="f.form === 'loan_repayment'">
                            <Field
                                :label="t('আসল (৳)')"
                                :error="f.errors.principal"
                            >
                                <Input
                                    v-model="f.principal"
                                    type="number"
                                    step="0.01"
                                    min="0"
                                    class="num"
                                />
                            </Field>
                            <Field
                                :label="t('সুদ (৳)')"
                                :error="f.errors.interest"
                            >
                                <Input
                                    v-model="f.interest"
                                    type="number"
                                    step="0.01"
                                    min="0"
                                    class="num"
                                />
                            </Field>
                        </template>

                        <template v-if="f.form === 'lease'">
                            <Field
                                :label="t('কত মাসের লিজ')"
                                :error="f.errors.months"
                            >
                                <Input
                                    v-model="f.months"
                                    type="number"
                                    min="1"
                                    max="120"
                                />
                            </Field>
                            <Field
                                :label="t('লিজ শুরুর মাস')"
                                :error="f.errors.start_month"
                            >
                                <DateField v-model="f.start_month" />
                            </Field>
                        </template>

                        <Field
                            v-if="['asset', 'lease'].includes(f.form)"
                            :label="t('উদ্যোগ বাজেটের খাত (থাকলে)')"
                            :error="f.errors.budget_head_id"
                        >
                            <NSelect v-model="f.budget_head_id">
                                <option :value="null">
                                    {{ t('— প্রযোজ্য নয় —') }}
                                </option>
                                <option
                                    v-for="b in budgetHeads"
                                    :key="b.id"
                                    :value="b.id"
                                >
                                    {{ b.code }} · {{ t(b.name) }}
                                </option>
                            </NSelect>
                        </Field>
                    </div>
                </section>

                <section
                    v-if="isSplit"
                    class="rounded-xl border border-border bg-card p-4"
                >
                    <div class="mb-3 flex items-center justify-between">
                        <h2 class="font-display text-lg">
                            {{
                                f.form === 'expense'
                                    ? t('কোন খাতে, কোন ক্যাটাগরিতে খরচ')
                                    : t('কোন খাতে, কোন ক্যাটাগরিতে আয়')
                            }}
                        </h2>
                        <Button
                            type="button"
                            size="sm"
                            variant="secondary"
                            @click="f.lines.push(blankLine())"
                            ><Plus /> {{ t('লাইন') }}</Button
                        >
                    </div>
                    <p
                        v-if="f.errors.lines"
                        class="mb-3 text-sm text-destructive"
                    >
                        {{ f.errors.lines }}
                    </p>

                    <div class="space-y-3">
                        <div
                            v-for="(line, index) in f.lines"
                            :key="index"
                            class="khata relative rounded-lg border border-border/80 bg-background/60 py-3 pr-3 pl-12"
                        >
                            <span
                                class="num absolute top-3 left-3 text-sm text-muted-foreground"
                                >{{ index + 1 }}.</span
                            >
                            <button
                                v-if="f.lines.length > 1"
                                type="button"
                                class="btn-icon absolute top-2 right-2 size-8 hover:text-destructive"
                                :aria-label="t('মুছুন')"
                                :title="t('মুছুন')"
                                @click="f.lines.splice(index, 1)"
                            >
                                <X class="size-4" />
                            </button>
                            <div
                                class="grid gap-3 sm:grid-cols-2 md:grid-cols-4"
                            >
                                <Field
                                    :label="t('খাত')"
                                    :error="lineError(index, 'account_id')"
                                >
                                    <NSelect
                                        v-model="line.account_id"
                                        :placeholder="t('বাছুন')"
                                        manage="account"
                                        :manage-extra="{
                                            group:
                                                f.form === 'expense'
                                                    ? '5000'
                                                    : '4000',
                                        }"
                                    >
                                        <option
                                            v-for="a in lineAccounts"
                                            :key="a.id"
                                            :value="a.id"
                                        >
                                            {{ t(a.name) }}
                                        </option>
                                    </NSelect>
                                </Field>
                                <Field
                                    :label="t('ক্যাটাগরি')"
                                    :error="lineError(index, 'cost_center_id')"
                                >
                                    <NSelect
                                        v-model="line.cost_center_id"
                                        manage="center"
                                    >
                                        <option
                                            v-for="c in centers"
                                            :key="c.id"
                                            :value="c.id"
                                            :disabled="c.kind === 'group'"
                                        >
                                            {{ tCenter(c) }}
                                        </option>
                                    </NSelect>
                                </Field>
                                <Field
                                    :label="t('টাকা (৳)')"
                                    :error="lineError(index, 'amount')"
                                >
                                    <Input
                                        v-model="line.amount"
                                        type="number"
                                        inputmode="decimal"
                                        step="0.01"
                                        min="0"
                                        class="num"
                                        required
                                    />
                                </Field>
                                <Field
                                    v-if="f.form === 'expense'"
                                    :label="t('উদ্যোগ বাজেট খাত')"
                                    :error="lineError(index, 'budget_head_id')"
                                >
                                    <NSelect v-model="line.budget_head_id">
                                        <option :value="null">
                                            {{ t('— নয় —') }}
                                        </option>
                                        <option
                                            v-for="b in budgetHeads"
                                            :key="b.id"
                                            :value="b.id"
                                        >
                                            {{ b.code }} · {{ t(b.name) }}
                                        </option>
                                    </NSelect>
                                </Field>
                                <Field
                                    v-if="
                                        animalsFor(line.cost_center_id).length
                                    "
                                    :label="t('নির্দিষ্ট গরু (ঐচ্ছিক)')"
                                >
                                    <NSelect v-model="line.animal_id">
                                        <option :value="null">
                                            {{ t('— সব —') }}
                                        </option>
                                        <option
                                            v-for="a in animalsFor(
                                                line.cost_center_id,
                                            )"
                                            :key="a.id"
                                            :value="a.id"
                                        >
                                            {{ a.tag }} {{ a.name ?? '' }}
                                        </option>
                                    </NSelect>
                                </Field>
                                <div class="sm:col-span-2 md:col-span-3">
                                    <Field :label="t('নোট')">
                                        <Input
                                            v-model="line.memo"
                                            :placeholder="
                                                t(
                                                    'যেমন: ডাক্তার ভিজিট, বকনা ২টি',
                                                )
                                            "
                                        />
                                    </Field>
                                </div>
                            </div>
                        </div>
                    </div>
                </section>
            </div>

            <aside class="space-y-5">
                <section
                    class="min-w-0 rounded-xl border border-border bg-card p-4"
                >
                    <div class="grid gap-4">
                        <Field
                            :label="t('বিবরণ')"
                            :error="f.errors.description"
                        >
                            <Input
                                v-model="f.description"
                                :placeholder="formLabels[f.form]"
                            />
                        </Field>
                        <Field
                            :label="t('রসিদ / মেমো নম্বর')"
                            :error="f.errors.reference"
                        >
                            <Input v-model="f.reference" />
                        </Field>
                        <Field
                            :label="t('ভাউচার / রসিদের ছবি')"
                            :error="
                                f.errors.attachments ||
                                (f.errors as Record<string, string>)[
                                    'attachments.0'
                                ]
                            "
                            :hint="
                                t(
                                    'JPG, PNG বা PDF, প্রতিটি :mb MB পর্যন্ত। উদ্যোগের টাকার খরচে ভাউচার বাধ্যতামূলক।',
                                    { mb: Math.round(maxVoucherKb / 1024) },
                                )
                            "
                        >
                            <label
                                class="flex cursor-pointer items-center gap-2 rounded-md border border-dashed border-input bg-background px-3 py-2.5 text-sm hover:bg-accent"
                            >
                                <Paperclip
                                    class="size-4 text-muted-foreground"
                                />
                                <span class="truncate">{{
                                    f.attachments.length
                                        ? f.attachments
                                              .map((file) => file.name)
                                              .join(', ')
                                        : t('ছবি তুলুন বা ফাইল বাছুন')
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
                </section>

                <section
                    class="sticky top-4 rounded-xl border border-primary/30 bg-primary/5 p-4"
                >
                    <p class="text-sm text-muted-foreground">{{ t('মোট') }}</p>
                    <p class="num font-display text-3xl">
                        {{
                            isSplit
                                ? taka(total)
                                : f.form === 'loan_repayment'
                                  ? taka(
                                        Math.round(
                                            ((parseFloat(f.principal) || 0) +
                                                (parseFloat(f.interest) || 0)) *
                                                100,
                                        ),
                                    )
                                  : taka(
                                        Math.round(
                                            (parseFloat(f.amount) || 0) * 100,
                                        ),
                                    )
                        }}
                    </p>
                    <div class="mt-4 grid gap-2">
                        <Button type="submit" :disabled="f.processing">{{
                            t('সংরক্ষণ করুন')
                        }}</Button>
                        <Button
                            type="button"
                            variant="secondary"
                            :disabled="f.processing"
                            @click="submit(true)"
                            >{{ t('সংরক্ষণ করে আরেকটা') }}</Button
                        >
                    </div>
                </section>
            </aside>
        </form>
    </div>
</template>
