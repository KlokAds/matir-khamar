<script setup lang="ts">
import { Head, useForm } from '@inertiajs/vue3';
import { Paperclip } from '@lucide/vue';
import Field from '@/components/khamar/Field.vue';
import NSelect from '@/components/khamar/NSelect.vue';
import PageTitle from '@/components/khamar/PageTitle.vue';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
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
}>();

const f = useForm({
    tag: '',
    name: '',
    sex: 'female',
    breed: '',
    cost_center_id: props.centers[0]?.id ?? null,
    acquisition: 'purchase',
    acquired_on: today(),
    birth_date: '',
    mother_id: null as number | null,
    notes: '',
    cost: '',
    paid_from_account_id: props.moneyAccounts[0]?.id ?? null,
    budget_head_id: null as number | null,
    attachments: [] as File[],
});
</script>

<template>
    <Head title="নতুন গরু" />

    <div class="flex flex-1 flex-col p-4 md:p-6">
        <PageTitle
            title="খামারে নতুন গরু"
            subtitle="যে গরু আগে থেকেই আছে সেগুলোও এখানে যোগ করুন — 'আগে থেকেই ছিল' বেছে কেনা দাম দিন।"
        />

        <form
            class="grid max-w-4xl gap-5 rounded-xl border border-border bg-card p-4 sm:grid-cols-2 lg:grid-cols-3"
            @submit.prevent="f.post('/animals', { forceFormData: true })"
        >
            <Field
                label="ট্যাগ / নম্বর"
                :error="f.errors.tag"
                hint="কানের ট্যাগ বা নিজের দেওয়া নম্বর, যেমন B-01"
            >
                <Input v-model="f.tag" required />
            </Field>
            <Field label="নাম (ঐচ্ছিক)" :error="f.errors.name"
                ><Input v-model="f.name" placeholder="যেমন: লালী"
            /></Field>
            <Field label="লিঙ্গ">
                <NSelect v-model="f.sex"
                    ><option value="female">স্ত্রী (গাভী/বকনা)</option>
                    <option value="male">পুরুষ (ষাঁড়/এঁড়ে)</option></NSelect
                >
            </Field>
            <Field label="জাত"
                ><Input
                    v-model="f.breed"
                    placeholder="যেমন: ফ্রিজিয়ান ক্রস, দেশি"
            /></Field>
            <Field label="কোন ক্যাটাগরিতে" :error="f.errors.cost_center_id">
                <NSelect v-model="f.cost_center_id"
                    ><option v-for="c in centers" :key="c.id" :value="c.id">
                        {{ centerLabel(c) }}
                    </option></NSelect
                >
            </Field>
            <Field label="কীভাবে এল">
                <NSelect v-model="f.acquisition"
                    ><option
                        v-for="(label, key) in acquisitions"
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
                        ? 'জন্মের তারিখ'
                        : 'খামারে আসার তারিখ'
                "
                :error="f.errors.acquired_on"
                ><Input v-model="f.acquired_on" type="date" :max="today()"
            /></Field>
            <Field v-if="f.acquisition !== 'born'" label="জন্ম তারিখ (জানলে)"
                ><Input v-model="f.birth_date" type="date" :max="today()"
            /></Field>
            <Field v-if="f.acquisition === 'born' && mothers.length" label="মা">
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
                            ? 'কেনা দাম / আজকের মূল্য (৳)'
                            : 'কেনা দাম (৳)'
                    "
                    :error="f.errors.cost"
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
                    label="টাকা কোথা থেকে"
                    :error="f.errors.paid_from_account_id"
                >
                    <NSelect v-model="f.paid_from_account_id"
                        ><option
                            v-for="a in moneyAccounts"
                            :key="a.id"
                            :value="a.id"
                        >
                            {{ a.name }}
                        </option></NSelect
                    >
                </Field>
                <Field
                    v-if="f.acquisition === 'purchase'"
                    label="উদ্যোগ বাজেট খাত"
                >
                    <NSelect v-model="f.budget_head_id"
                        ><option :value="null">— নয় —</option>
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
                    label="হাটের রসিদ / ছবি"
                >
                    <label
                        class="flex cursor-pointer items-center gap-2 rounded-md border border-dashed border-input px-3 py-2 text-sm hover:bg-accent"
                    >
                        <Paperclip class="size-4 text-muted-foreground" />
                        <span class="truncate">{{
                            f.attachments.length
                                ? f.attachments.map((x) => x.name).join(', ')
                                : 'ছবি / PDF'
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
                <Field label="নোট"
                    ><Input
                        v-model="f.notes"
                        placeholder="টিকা, রোগ, বৈশিষ্ট্য…"
                /></Field>
            </div>
            <div class="sm:col-span-2 lg:col-span-3">
                <Button type="submit" :disabled="f.processing"
                    >গরু যোগ করুন</Button
                >
            </div>
        </form>
    </div>
</template>
