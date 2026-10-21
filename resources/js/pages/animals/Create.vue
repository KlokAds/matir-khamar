<script setup lang="ts">
import { Head, useForm } from '@inertiajs/vue3';
import { computed, watch } from 'vue';
import { Paperclip } from '@lucide/vue';
import Field from '@/components/khamar/Field.vue';
import NSelect from '@/components/khamar/NSelect.vue';
import PageTitle from '@/components/khamar/PageTitle.vue';
import { Button } from '@/components/ui/button';
import DateField from '@/components/khamar/DateField.vue';
import { Input } from '@/components/ui/input';
import { t, tAll } from '@/lib/i18n';
import { centerLabel, today } from '@/lib/khamar';
import type { Center, Option } from '@/lib/khamar';

defineOptions({
    layout: {
        breadcrumbs: [
            { title: 'গরুর তালিকা', href: '/animals' },
            { title: 'নতুন গরু', href: '/animals/create' },
        ],
    },
});

const props = defineProps<{
    centers: Center[];
    moneyAccounts: Option[];
    budgetHeads: { id: number; code: string; name: string }[];
    mothers: { id: number; tag: string; name: string | null }[];
    acquisitions: Record<string, string>;
    maxVoucherKb: number;
    batches: {
        id: number;
        label: string;
        cost_center_id: number;
        started_on: string;
    }[];
    nextBatchNumbers: Record<string, number>;
    nextTags: Record<string, string>;
}>();

// Coming from a calving ("বাছুরটি তালিকায় যোগ করুন"): the mother is already known.
const motherFromLink = Number(
    new URLSearchParams(window.location.search).get('mother') ?? 0,
);

const f = useForm({
    tag: '',
    name: '',
    sex: 'female',
    breed: '',
    cost_center_id: props.centers[0]?.id ?? null,
    acquisition: motherFromLink ? 'born' : 'purchase',
    acquired_on: today(),
    birth_date: '',
    mother_id: (motherFromLink || null) as number | null,
    batch: '' as string,
    weight_kg: '',
    months_pregnant: '',
    notes: '',
    cost: '',
    paid_from_account_id: props.moneyAccounts[0]?.id ?? null,
    budget_head_id: null as number | null,
    attachments: [] as File[],
    count: '1',
});

const many = computed(() => (parseInt(f.count) || 1) > 1);
const nextTag = computed(() => props.nextTags[String(f.cost_center_id)] ?? '');
const perHead = computed(() => {
    const n = parseInt(f.count) || 1;
    const total = parseFloat(f.cost) || 0;

    return n > 1 && total > 0 ? Math.round(total / n) : 0;
});

const center = computed(() =>
    props.centers.find((c) => c.id === f.cost_center_id),
);
const centerBatches = computed(() =>
    props.batches.filter((b) => b.cost_center_id === f.cost_center_id),
);
// Fattening rounds are offered where they are used: মোটাতাজা, or any category that has one open.
const showBatch = computed(
    () => center.value?.code === 'MOTATAJA' || centerBatches.value.length > 0,
);

watch(
    () => f.cost_center_id,
    () => {
        f.batch = showBatch.value
            ? String(centerBatches.value[0]?.id ?? 'new')
            : '';
    },
    { immediate: true },
);
</script>

<template>
    <Head :title="t('নতুন গরু')" />

    <div class="flex flex-1 flex-col p-4 md:p-6">
        <PageTitle
            :title="t('খামারে নতুন গরু')"
            :subtitle="
                t(
                    'যে গরু আগে থেকেই আছে সেগুলোও এখানে যোগ করুন — \'আগে থেকেই ছিল\' বেছে কেনা দাম দিন।',
                )
            "
        />

        <form
            class="grid max-w-4xl gap-5 rounded-xl border border-border bg-card p-4 sm:grid-cols-2 lg:grid-cols-3"
            @submit.prevent="f.post('/animals', { forceFormData: true })"
        >
            <Field
                v-if="f.acquisition !== 'born'"
                :label="t('কয়টি গরু')"
                :error="f.errors.count"
                :hint="
                    many
                        ? t(
                              'একসাথে কেনা — দাম সমান ভাগ হবে, নম্বর :tag থেকে শুরু',
                              { tag: nextTag },
                          )
                        : t('একসাথে কয়েকটি কিনলে সংখ্যা দিন')
                "
            >
                <Input
                    v-model="f.count"
                    type="number"
                    min="1"
                    max="50"
                    class="num"
                />
            </Field>
            <Field
                v-if="!many"
                :label="t('নম্বর (ঐচ্ছিক)')"
                :error="f.errors.tag"
                :hint="t('খালি রাখলে অ্যাপ নিজে পরের নম্বর দেবে')"
            >
                <Input v-model="f.tag" :placeholder="nextTag" />
            </Field>
            <Field
                v-if="!many"
                :label="t('নাম (ঐচ্ছিক)')"
                :error="f.errors.name"
                ><Input v-model="f.name" :placeholder="t('যেমন: লালী')"
            /></Field>
            <Field :label="t('লিঙ্গ')">
                <NSelect v-model="f.sex"
                    ><option value="female">
                        {{ t('স্ত্রী (গাভী/বকনা)') }}
                    </option>
                    <option value="male">
                        {{ t('পুরুষ (ষাঁড়/এঁড়ে)') }}
                    </option></NSelect
                >
            </Field>
            <Field :label="t('জাত')"
                ><Input
                    v-model="f.breed"
                    :placeholder="t('যেমন: ফ্রিজিয়ান ক্রস, দেশি')"
            /></Field>
            <Field
                :label="t('কোন ক্যাটাগরিতে')"
                :error="f.errors.cost_center_id"
            >
                <NSelect v-model="f.cost_center_id"
                    ><option v-for="c in centers" :key="c.id" :value="c.id">
                        {{ centerLabel({ ...c, name: t(c.name) }) }}
                    </option></NSelect
                >
            </Field>
            <Field :label="t('কীভাবে এল')">
                <NSelect v-model="f.acquisition"
                    ><option
                        v-for="(label, key) in tAll(acquisitions)"
                        :key="key"
                        :value="key"
                    >
                        {{ label }}
                    </option></NSelect
                >
            </Field>
            <Field
                :label="
                    f.acquisition === 'born'
                        ? t('জন্মের তারিখ')
                        : t('খামারে আসার তারিখ')
                "
                :error="f.errors.acquired_on"
                ><DateField v-model="f.acquired_on" :max="today()"
            /></Field>
            <Field
                v-if="f.acquisition !== 'born'"
                :label="t('জন্ম তারিখ (জানলে)')"
                ><DateField clearable v-model="f.birth_date" :max="today()"
            /></Field>
            <Field
                v-if="showBatch"
                :label="t('কোন ব্যাচে')"
                :error="f.errors.batch"
                :hint="
                    t(
                        'একসাথে কেনা গরু এক ব্যাচে — দিন, খরচ আর লাভ ব্যাচ ধরে দেখা যাবে',
                    )
                "
            >
                <NSelect v-model="f.batch">
                    <option
                        v-for="b in centerBatches"
                        :key="b.id"
                        :value="String(b.id)"
                    >
                        {{ b.label }}
                        {{ t('(শুরু :date)', { date: b.started_on }) }}
                    </option>
                    <option value="new">
                        {{
                            t('নতুন: ব্যাচ-:n', {
                                n:
                                    nextBatchNumbers[
                                        String(f.cost_center_id)
                                    ] ?? 1,
                            })
                        }}
                    </option>
                    <option value="">{{ t('কোনো ব্যাচে নয়') }}</option>
                </NSelect>
            </Field>
            <Field
                v-if="!many"
                :label="t('ওজন (কেজি, ঐচ্ছিক)')"
                :error="f.errors.weight_kg"
                :hint="t('শুরুর ওজন থাকলে পরে দিনে কত বাড়লো দেখা যাবে')"
            >
                <Input
                    v-model="f.weight_kg"
                    type="number"
                    step="0.1"
                    min="0"
                    class="num"
                />
            </Field>
            <Field
                v-if="f.sex === 'female' && f.acquisition !== 'born'"
                :label="t('গর্ভবতী? কত মাস চলছে')"
                :error="f.errors.months_pregnant"
                :hint="
                    t(
                        'জানা থাকলে লিখুন, যেমন 4 — বাচ্চা হওয়ার আনুমানিক দিন হিসাব হবে',
                    )
                "
            >
                <Input
                    v-model="f.months_pregnant"
                    type="number"
                    step="0.5"
                    min="0"
                    max="9.5"
                    class="num"
                    :placeholder="t('গর্ভবতী না হলে খালি রাখুন')"
                />
            </Field>
            <Field
                v-if="f.acquisition === 'born' && mothers.length"
                :label="t('মা')"
            >
                <NSelect v-model="f.mother_id"
                    ><option :value="null">—</option>
                    <option v-for="m in mothers" :key="m.id" :value="m.id">
                        {{ m.tag }} {{ m.name ?? '' }}
                    </option></NSelect
                >
            </Field>
            <template v-if="f.acquisition !== 'born'">
                <Field
                    :label="
                        f.acquisition === 'opening'
                            ? many
                                ? t('মোট কেনা দাম / আজকের মূল্য (৳)')
                                : t('কেনা দাম / আজকের মূল্য (৳)')
                            : many
                              ? t('মোট কেনা দাম (৳)')
                              : t('কেনা দাম (৳)')
                    "
                    :error="f.errors.cost"
                    :hint="
                        perHead
                            ? t('প্রতিটি ৳ :amount', {
                                  amount: perHead.toLocaleString('en-IN'),
                              })
                            : undefined
                    "
                >
                    <Input
                        v-model="f.cost"
                        type="number"
                        step="0.01"
                        min="0"
                        class="num"
                        required
                    />
                </Field>
                <Field
                    v-if="f.acquisition === 'purchase'"
                    :label="t('টাকা কোথা থেকে')"
                    :error="f.errors.paid_from_account_id"
                >
                    <NSelect v-model="f.paid_from_account_id"
                        ><option
                            v-for="a in moneyAccounts"
                            :key="a.id"
                            :value="a.id"
                        >
                            {{ t(a.name) }}
                        </option></NSelect
                    >
                </Field>
                <Field
                    v-if="f.acquisition === 'purchase'"
                    :label="t('উদ্যোগ বাজেট খাত')"
                >
                    <NSelect v-model="f.budget_head_id"
                        ><option :value="null">{{ t('— নয় —') }}</option>
                        <option
                            v-for="b in budgetHeads"
                            :key="b.id"
                            :value="b.id"
                        >
                            {{ b.code }} · {{ b.name }}
                        </option></NSelect
                    >
                </Field>
                <Field
                    v-if="f.acquisition === 'purchase'"
                    :label="t('হাটের রসিদ / ছবি')"
                >
                    <label
                        class="flex cursor-pointer items-center gap-2 rounded-md border border-dashed border-input px-3 py-2 text-sm hover:bg-accent"
                    >
                        <Paperclip class="size-4 text-muted-foreground" />
                        <span class="truncate">{{
                            f.attachments.length
                                ? f.attachments.map((x) => x.name).join(', ')
                                : t('ছবি / PDF')
                        }}</span>
                        <input
                            type="file"
                            class="sr-only"
                            accept="image/*,application/pdf"
                            capture="environment"
                            multiple
                            @change="
                                (e) =>
                                    (f.attachments = Array.from(
                                        (e.target as HTMLInputElement).files ??
                                            [],
                                    ))
                            "
                        />
                    </label>
                </Field>
            </template>
            <div class="sm:col-span-2 lg:col-span-3">
                <Field :label="t('নোট')"
                    ><Input
                        v-model="f.notes"
                        :placeholder="t('টিকা, রোগ, বৈশিষ্ট্য…')"
                /></Field>
            </div>
            <div class="sm:col-span-2 lg:col-span-3">
                <Button type="submit" :disabled="f.processing">{{
                    many
                        ? t(':nটি গরু যোগ করুন', { n: f.count })
                        : t('গরু যোগ করুন')
                }}</Button>
            </div>
        </form>
    </div>
</template>
