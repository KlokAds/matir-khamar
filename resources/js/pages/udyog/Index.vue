<script setup lang="ts">
import { Head, Link, useForm } from '@inertiajs/vue3';
import {
    BadgeCheck,
    CalendarClock,
    FileClock,
    Gift,
    HandCoins,
    Landmark,
    Pencil,
    Percent,
} from '@lucide/vue';
import { computed, ref } from 'vue';
import DateField from '@/components/khamar/DateField.vue';
import Field from '@/components/khamar/Field.vue';
import NSelect from '@/components/khamar/NSelect.vue';
import PageTitle from '@/components/khamar/PageTitle.vue';
import StatTile from '@/components/khamar/StatTile.vue';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { t, tAll } from '@/lib/i18n';
import { day, taka, today, useCan } from '@/lib/khamar';
import type { Option } from '@/lib/khamar';

defineOptions({
    layout: { breadcrumbs: [{ title: 'উদ্যোগ ঋণ (সরকারি)', href: '/udyog' }] },
});

type Terms = {
    status: string;
    loan: number;
    rate: number;
    years: number;
    grant: number;
    applied_on: string | null;
    approved_on: string | null;
    debt_id: number | null;
    notes: string;
};

const props = defineProps<{
    terms: Terms;
    statuses: Record<string, string>;
    approved: boolean;
    schedule: {
        year: number;
        due_on: string | null;
        principal: number;
        interest: number;
        total: number;
        left: number;
    }[];
    heads: {
        id: number;
        code: string;
        name: string;
        details: string | null;
        approved: number;
        spent: number;
        remaining: number;
        missing_vouchers: number;
    }[];
    debt: {
        balance: number;
        next_due_on: string | null;
        next_amount: number | null;
    } | null;
    moneyAccounts: Option[];
}>();

const can = useCan();
const editing = ref(false);
const approving = ref(false);

const form = useForm({
    status: props.terms.status === 'approved' ? 'applied' : props.terms.status,
    loan: String(props.terms.loan / 100),
    rate: String(props.terms.rate),
    years: String(props.terms.years),
    grant: String(props.terms.grant / 100),
    applied_on: props.terms.applied_on ?? '',
    notes: props.terms.notes,
});

const approveForm = useForm({
    date: today(),
    amount: String(props.terms.loan / 100),
    money_account_id: props.moneyAccounts[0]?.id ?? null,
});

const totals = computed(() => ({
    interest: props.schedule.reduce((s, r) => s + r.interest, 0),
    repay: props.schedule.reduce((s, r) => s + r.total, 0),
    plan: props.heads.reduce((s, h) => s + h.approved, 0),
    spent: props.heads.reduce((s, h) => s + h.spent, 0),
}));

const statusTone = computed(
    () =>
        ({
            applied: 'bg-turmeric/20 text-soil',
            approved: 'bg-paddy/15 text-paddy',
            declined: 'bg-muted text-muted-foreground',
        })[props.terms.status] ?? 'bg-muted',
);

function save() {
    form.put('/udyog', {
        preserveScroll: true,
        onSuccess: () => (editing.value = false),
    });
}

function approve() {
    approveForm.post('/udyog/approve', {
        preserveScroll: true,
        onSuccess: () => (approving.value = false),
    });
}
</script>

<template>
    <Head :title="t('উদ্যোগ ঋণ (সরকারি)')" />

    <div class="flex flex-1 flex-col gap-5 p-4 pb-24 md:p-6 md:pb-6">
        <PageTitle
            :title="t('উদ্যোগ ঋণ (সরকারি)')"
            :subtitle="
                t(
                    'সরকারি উদ্যোগ কর্মসূচির ঋণ — আবেদন থেকে অনুমোদন, কিস্তি আর খরচের পরিকল্পনা এক জায়গায়।',
                )
            "
        />

        <!-- Where the application stands -->
        <section class="rounded-2xl border border-border bg-card p-4 sm:p-5">
            <div class="flex flex-wrap items-start justify-between gap-3">
                <div class="flex items-center gap-3">
                    <span
                        class="flex size-12 shrink-0 items-center justify-center rounded-2xl bg-pond/12 text-pond"
                    >
                        <component
                            :is="approved ? BadgeCheck : FileClock"
                            class="size-6"
                        />
                    </span>
                    <div>
                        <span
                            :class="[
                                'inline-block rounded-full px-2.5 py-1 text-xs font-semibold',
                                statusTone,
                            ]"
                            >{{ tAll(statuses)[terms.status] }}</span
                        >
                        <p
                            class="mt-1 text-sm leading-normal text-muted-foreground"
                        >
                            <template v-if="approved && terms.approved_on">{{
                                t('অনুমোদন :date', {
                                    date: day(terms.approved_on),
                                })
                            }}</template>
                            <template v-else-if="terms.applied_on">{{
                                t('আবেদন :date', {
                                    date: day(terms.applied_on),
                                })
                            }}</template>
                            <template v-else>{{
                                t('আবেদনের তারিখ লেখা নেই')
                            }}</template>
                        </p>
                    </div>
                </div>
                <div v-if="can['manage-setup']" class="flex flex-wrap gap-2">
                    <Button
                        v-if="!approved"
                        variant="secondary"
                        @click="editing = !editing"
                        ><Pencil /> {{ t('শর্ত বদলান') }}</Button
                    >
                    <Button
                        v-if="!approved && terms.status !== 'declined'"
                        @click="approving = !approving"
                        ><BadgeCheck /> {{ t('অনুমোদন হয়েছে') }}</Button
                    >
                </div>
            </div>

            <p
                v-if="!approved"
                class="mt-4 rounded-xl border border-turmeric/40 bg-turmeric/10 p-3 text-sm leading-normal"
            >
                {{
                    t(
                        'এখনো অনুমোদন হয়নি — তাই এটা শুধু পরিকল্পনা। কোনো খরচ এই ঋণ থেকে ধরা হচ্ছে না, আর এন্ট্রিতে উদ্যোগের খাত দেখানো হচ্ছে না। অনুমোদন হলে "অনুমোদন হয়েছে" চাপুন।',
                    )
                }}
            </p>

            <!-- Edit the terms -->
            <form
                v-if="editing"
                class="mt-4 grid gap-3 rounded-xl border border-border bg-background/60 p-3 sm:grid-cols-2 lg:grid-cols-3"
                @submit.prevent="save"
            >
                <Field :label="t('অবস্থা')" :error="form.errors.status">
                    <NSelect v-model="form.status">
                        <option
                            v-for="(label, key) in tAll(statuses)"
                            v-show="key !== 'approved'"
                            :key="key"
                            :value="key"
                            :disabled="key === 'approved'"
                        >
                            {{ label }}
                        </option>
                    </NSelect>
                </Field>
                <Field
                    :label="t('আবেদনের তারিখ')"
                    :error="form.errors.applied_on"
                >
                    <DateField v-model="form.applied_on" clearable />
                </Field>
                <Field :label="t('ঋণ (৳)')" :error="form.errors.loan">
                    <Input v-model="form.loan" inputmode="decimal" />
                </Field>
                <Field :label="t('সুদ (% বছরে)')" :error="form.errors.rate">
                    <Input v-model="form.rate" inputmode="decimal" />
                </Field>
                <Field :label="t('কত বছরে শোধ')" :error="form.errors.years">
                    <Input v-model="form.years" inputmode="numeric" />
                </Field>
                <Field
                    :label="t('সম্ভাব্য অনুদান (৳, নিশ্চিত নয়)')"
                    :error="form.errors.grant"
                >
                    <Input v-model="form.grant" inputmode="decimal" />
                </Field>
                <Field
                    :label="t('নোট')"
                    :error="form.errors.notes"
                    class="sm:col-span-2 lg:col-span-3"
                >
                    <Input
                        v-model="form.notes"
                        :placeholder="t('যেমন: কোন অফিসে, কার মাধ্যমে আবেদন')"
                    />
                </Field>
                <div class="flex gap-2 sm:col-span-2 lg:col-span-3">
                    <Button type="submit" :disabled="form.processing">{{
                        t('রাখুন')
                    }}</Button>
                    <Button
                        type="button"
                        variant="secondary"
                        @click="editing = false"
                        >{{ t('বাদ') }}</Button
                    >
                </div>
            </form>

            <!-- Record the approval -->
            <form
                v-if="approving"
                class="mt-4 grid gap-3 rounded-xl border border-paddy/40 bg-paddy/5 p-3 sm:grid-cols-3"
                @submit.prevent="approve"
            >
                <p class="text-sm leading-normal sm:col-span-3">
                    {{
                        t(
                            'টাকা হাতে এলে লিখুন: "ধার ও জামানত"-এ ঋণটি খুলবে, প্রতি বছরের কিস্তিসহ, আর তখন থেকে খরচে উদ্যোগের খাত বাছাই করা যাবে।',
                        )
                    }}
                </p>
                <Field
                    :label="t('টাকা পাওয়ার তারিখ')"
                    :error="approveForm.errors.date"
                >
                    <DateField v-model="approveForm.date" :max="today()" />
                </Field>
                <Field
                    :label="t('কত টাকা এলো (৳)')"
                    :error="approveForm.errors.amount"
                >
                    <Input v-model="approveForm.amount" inputmode="decimal" />
                </Field>
                <Field
                    :label="t('কোথায় এলো')"
                    :error="approveForm.errors.money_account_id"
                >
                    <NSelect v-model="approveForm.money_account_id">
                        <option
                            v-for="a in moneyAccounts"
                            :key="a.id"
                            :value="a.id"
                        >
                            {{ t(a.name) }}
                        </option>
                    </NSelect>
                </Field>
                <div class="flex gap-2 sm:col-span-3">
                    <Button type="submit" :disabled="approveForm.processing"
                        ><BadgeCheck /> {{ t('অনুমোদন লিখুন') }}</Button
                    >
                    <Button
                        type="button"
                        variant="secondary"
                        @click="approving = false"
                        >{{ t('বাদ') }}</Button
                    >
                </div>
            </form>
        </section>

        <!-- The terms at a glance -->
        <div class="grid gap-3 sm:grid-cols-2 lg:grid-cols-4">
            <StatTile
                :label="t('ঋণ')"
                :value="taka(terms.loan)"
                :hint="
                    t(':years বছরে শোধ', {
                        years: terms.years,
                    })
                "
                :icon="Landmark"
                tone="pond"
            />
            <StatTile
                :label="t('সুদ')"
                :value="`${terms.rate}%`"
                :hint="
                    t('মোট সুদ প্রায় :amount', {
                        amount: taka(totals.interest),
                    })
                "
                :icon="Percent"
                tone="clay"
            />
            <StatTile
                :label="t('মোট শোধ করতে হবে')"
                :value="taka(totals.repay)"
                :hint="t('আসল + সুদ')"
                :icon="HandCoins"
                tone="turmeric"
            />
            <StatTile
                :label="t('সম্ভাব্য অনুদান')"
                :value="taka(terms.grant)"
                :hint="t('রিপোর্ট ভালো থাকলে — নিশ্চিত নয়, হিসাবে ধরা হয়নি')"
                :icon="Gift"
                tone="paddy"
            />
        </div>

        <div class="grid gap-5 lg:grid-cols-2">
            <!-- Repayment by year -->
            <section
                class="min-w-0 rounded-2xl border border-border bg-card p-4 sm:p-5"
            >
                <h2
                    class="flex items-center gap-2 font-display text-lg leading-normal"
                >
                    <CalendarClock class="size-5 text-pond" />
                    {{ t('বছরে বছরে শোধ') }}
                </h2>
                <p class="mt-1 text-xs leading-normal text-muted-foreground">
                    {{
                        t(
                            'প্রতি বছর আসলের সমান ভাগ, আর বাকি ঋণের ওপর সুদ। আনুমানিক — ব্যাংকের কিস্তির তালিকার সাথে মিলিয়ে নিন।',
                        )
                    }}
                </p>
                <div class="mt-3 overflow-x-auto">
                    <table class="w-full min-w-[420px] text-sm">
                        <thead>
                            <tr
                                class="border-b border-border text-left text-xs text-muted-foreground"
                            >
                                <th class="py-2 font-medium">{{ t('বছর') }}</th>
                                <th class="py-2 text-right font-medium">
                                    {{ t('আসল') }}
                                </th>
                                <th class="py-2 text-right font-medium">
                                    {{ t('সুদ') }}
                                </th>
                                <th class="py-2 text-right font-medium">
                                    {{ t('মোট কিস্তি') }}
                                </th>
                                <th class="py-2 text-right font-medium">
                                    {{ t('বাকি থাকবে') }}
                                </th>
                            </tr>
                        </thead>
                        <tbody>
                            <tr
                                v-for="row in schedule"
                                :key="row.year"
                                class="border-b border-dashed border-border last:border-0"
                            >
                                <td class="py-2 leading-normal">
                                    {{ t(':nম বছর', { n: row.year }) }}
                                    <span
                                        v-if="row.due_on"
                                        class="num block text-xs text-muted-foreground"
                                        >{{ day(row.due_on) }}</span
                                    >
                                </td>
                                <td
                                    class="num py-2 text-right whitespace-nowrap"
                                >
                                    {{ taka(row.principal) }}
                                </td>
                                <td
                                    class="num py-2 text-right whitespace-nowrap text-clay"
                                >
                                    {{ taka(row.interest) }}
                                </td>
                                <td
                                    class="num py-2 text-right font-semibold whitespace-nowrap"
                                >
                                    {{ taka(row.total) }}
                                </td>
                                <td
                                    class="num py-2 text-right whitespace-nowrap text-muted-foreground"
                                >
                                    {{ taka(row.left) }}
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>
                <Link v-if="debt" href="/debts" class="btn-chip-primary mt-3"
                    ><HandCoins />
                    {{
                        t('ঋণ বাকি :amount — ধার ও জামানতে দেখুন', {
                            amount: taka(debt.balance),
                        })
                    }}</Link
                >
            </section>

            <!-- Spending plan -->
            <section
                class="min-w-0 rounded-2xl border border-border bg-card p-4 sm:p-5"
            >
                <h2 class="font-display text-lg leading-normal">
                    {{
                        approved
                            ? t('বাজেট আর আসল খরচ')
                            : t('খরচের পরিকল্পনা (আবেদনে যা ছিল)')
                    }}
                </h2>
                <p class="mt-1 text-xs leading-normal text-muted-foreground">
                    {{
                        approved
                            ? t('উদ্যোগের খাত বেছে লেখা খরচ এখানে জমা হয়।')
                            : t(
                                  'শুধু পরিকল্পনা — এখনো কোনো খরচ এই খাতগুলো থেকে ধরা হয়নি।',
                              )
                    }}
                </p>
                <ul class="mt-3 space-y-3">
                    <li v-for="h in heads" :key="h.id">
                        <div class="flex items-baseline justify-between gap-3">
                            <span class="min-w-0 text-sm leading-normal">
                                <span class="font-medium">{{ t(h.name) }}</span>
                                <span
                                    v-if="h.details"
                                    class="block text-xs text-muted-foreground"
                                    >{{ h.details }}</span
                                >
                            </span>
                            <span
                                class="num shrink-0 text-sm whitespace-nowrap"
                            >
                                <template v-if="approved"
                                    >{{ taka(h.spent) }} /
                                </template>
                                {{ taka(h.approved) }}
                            </span>
                        </div>
                        <div
                            v-if="approved"
                            class="mt-1 h-1.5 rounded-full bg-muted"
                        >
                            <div
                                class="h-1.5 rounded-full bg-paddy"
                                :style="{
                                    width: `${h.approved ? Math.min(100, Math.round((h.spent / h.approved) * 100)) : 0}%`,
                                }"
                            />
                        </div>
                    </li>
                    <li
                        class="flex justify-between gap-3 border-t border-border pt-3 text-sm font-semibold"
                    >
                        <span>{{ t('মোট') }}</span>
                        <span class="num whitespace-nowrap"
                            ><template v-if="approved"
                                >{{ taka(totals.spent) }} /
                            </template>
                            {{ taka(totals.plan) }}</span
                        >
                    </li>
                </ul>
            </section>
        </div>
    </div>
</template>
