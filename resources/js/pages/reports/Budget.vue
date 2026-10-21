<script setup lang="ts">
import { Head, Link, useForm } from '@inertiajs/vue3';
import { CircleAlert, Pencil, Printer } from '@lucide/vue';
import { computed, ref } from 'vue';
import PageTitle from '@/components/khamar/PageTitle.vue';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { t } from '@/lib/i18n';
import { day, taka, useCan } from '@/lib/khamar';

defineOptions({
    layout: {
        breadcrumbs: [{ title: 'উদ্যোগ বাজেট', href: '/reports/budget' }],
    },
});

const props = defineProps<{
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
    missing: {
        entry_id: number;
        number: string;
        date: string;
        description: string;
    }[];
}>();

const can = useCan();
const print = () => window.print();
const editingId = ref<number | null>(null);
const edit = useForm({ name: '', details: '', approved_amount: '' });

const totals = computed(() => ({
    approved: props.heads.reduce((s, h) => s + h.approved, 0),
    spent: props.heads.reduce((s, h) => s + h.spent, 0),
}));

function start(head: (typeof props.heads)[number]) {
    editingId.value = head.id;
    edit.name = head.name;
    edit.details = head.details ?? '';
    edit.approved_amount = String(head.approved / 100);
}

const pct = (head: { approved: number; spent: number }) =>
    head.approved > 0
        ? Math.min(100, Math.round((head.spent / head.approved) * 100))
        : 0;
</script>

<template>
    <Head :title="t('উদ্যোগ বাজেট')" />

    <div class="flex flex-1 flex-col p-4 md:p-6">
        <PageTitle
            :title="t('উদ্যোগ কর্মসূচির বাজেট')"
            :subtitle="
                t(
                    'অনুমোদিত খাতে, ভাউচারসহ খরচ হলে অনুদান ফেরত দিতে হয় না। এই পাতা ব্যাংককে দেখানোর জন্য।',
                )
            "
        >
            <template #actions
                ><Button
                    size="sm"
                    variant="secondary"
                    class="print:hidden"
                    @click="print"
                    ><Printer /> {{ t('প্রিন্ট') }}</Button
                ></template
            >
        </PageTitle>

        <div
            v-if="missing.length"
            class="mb-5 rounded-xl border border-clay/40 bg-clay/8 p-4 text-sm"
        >
            <p class="flex items-center gap-2 font-medium">
                <CircleAlert class="size-4 text-clay" />
                {{ t(':nটি বাজেট-খরচে ভাউচার নেই', { n: missing.length }) }}
            </p>
            <ul class="mt-2 space-y-1">
                <li v-for="m in missing" :key="m.entry_id">
                    <Link :href="`/entries/${m.entry_id}`" class="underline">{{
                        m.number
                    }}</Link>
                    · {{ day(m.date) }} · {{ m.description }}
                </li>
            </ul>
        </div>

        <div class="overflow-x-auto rounded-xl border border-border bg-card">
            <table class="w-full min-w-[760px] text-sm">
                <thead
                    class="bg-muted/60 text-left text-xs text-muted-foreground"
                >
                    <tr>
                        <th class="px-4 py-2.5 font-medium">{{ t('খাত') }}</th>
                        <th class="px-4 py-2.5 text-right font-medium">
                            {{ t('অনুমোদিত') }}
                        </th>
                        <th class="px-4 py-2.5 text-right font-medium">
                            {{ t('খরচ হয়েছে') }}
                        </th>
                        <th class="px-4 py-2.5 text-right font-medium">
                            {{ t('বাকি') }}
                        </th>
                        <th class="w-40 px-4 py-2.5 font-medium">
                            {{ t('অগ্রগতি') }}
                        </th>
                        <th class="px-4 py-2.5" />
                    </tr>
                </thead>
                <tbody>
                    <template v-for="h in heads" :key="h.id">
                        <tr class="border-t border-border/60">
                            <td class="px-4 py-3">
                                <span class="num mr-1 text-muted-foreground">{{
                                    h.code
                                }}</span>
                                {{ t(h.name) }}
                                <p
                                    v-if="h.details"
                                    class="text-xs text-muted-foreground"
                                >
                                    {{ h.details }}
                                </p>
                                <p
                                    v-if="h.missing_vouchers"
                                    class="text-xs text-clay"
                                >
                                    {{
                                        t(':nটি এন্ট্রিতে ভাউচার নেই', {
                                            n: h.missing_vouchers,
                                        })
                                    }}
                                </p>
                            </td>
                            <td class="num px-4 py-3 text-right">
                                {{ taka(h.approved) }}
                            </td>
                            <td class="num px-4 py-3 text-right">
                                {{ taka(h.spent) }}
                            </td>
                            <td
                                :class="[
                                    'num px-4 py-3 text-right',
                                    h.remaining < 0 &&
                                        'font-semibold text-destructive',
                                ]"
                            >
                                {{ taka(h.remaining) }}
                            </td>
                            <td class="px-4 py-3">
                                <div class="h-2 rounded-full bg-muted">
                                    <div
                                        :class="[
                                            'h-2 rounded-full',
                                            h.remaining < 0
                                                ? 'bg-destructive'
                                                : 'bg-paddy',
                                        ]"
                                        :style="{ width: `${pct(h)}%` }"
                                    />
                                </div>
                            </td>
                            <td class="px-4 py-3 text-right">
                                <button
                                    v-if="can['manage-setup']"
                                    class="rounded p-1 text-muted-foreground hover:bg-accent print:hidden"
                                    @click="start(h)"
                                >
                                    <Pencil class="size-4" />
                                </button>
                            </td>
                        </tr>
                        <tr v-if="editingId === h.id">
                            <td colspan="6" class="px-4 pb-3">
                                <form
                                    class="grid gap-2 sm:grid-cols-[1fr_1fr_10rem_auto]"
                                    @submit.prevent="
                                        edit.put(`/setup/budget/${h.id}`, {
                                            preserveScroll: true,
                                            onSuccess: () => (editingId = null),
                                        })
                                    "
                                >
                                    <Input v-model="edit.name" required />
                                    <Input
                                        v-model="edit.details"
                                        :placeholder="t('বিবরণ')"
                                    />
                                    <Input
                                        v-model="edit.approved_amount"
                                        type="number"
                                        min="0"
                                        step="0.01"
                                        class="num"
                                        :placeholder="t('অনুমোদিত ৳')"
                                    />
                                    <Button
                                        type="submit"
                                        size="sm"
                                        :disabled="edit.processing"
                                        >{{ t('রাখুন') }}</Button
                                    >
                                </form>
                            </td>
                        </tr>
                    </template>
                </tbody>
                <tfoot class="font-semibold">
                    <tr class="border-t-2 border-border">
                        <td class="px-4 py-3">{{ t('মোট') }}</td>
                        <td class="num px-4 py-3 text-right">
                            {{ taka(totals.approved) }}
                        </td>
                        <td class="num px-4 py-3 text-right">
                            {{ taka(totals.spent) }}
                        </td>
                        <td class="num px-4 py-3 text-right">
                            {{ taka(totals.approved - totals.spent) }}
                        </td>
                        <td colspan="2" />
                    </tr>
                </tfoot>
            </table>
        </div>
        <p class="mt-3 text-xs text-muted-foreground">
            {{
                t(
                    'বাজেটের অঙ্কগুলো ১৪ অক্টোবরের আবেদন থেকে নেওয়া; ব্যাংকের অনুমোদনপত্র এলে সেই অনুযায়ী ✎ দিয়ে ঠিক করুন।',
                )
            }}
        </p>
    </div>
</template>
