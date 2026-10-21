<script setup lang="ts">
import { Head, router } from '@inertiajs/vue3';
import { Printer } from '@lucide/vue';
import { computed, reactive } from 'vue';
import CenterRows from '@/components/khamar/CenterRows.vue';
import PageTitle from '@/components/khamar/PageTitle.vue';
import TrendBars from '@/components/khamar/TrendBars.vue';
import { Button } from '@/components/ui/button';
import DateField from '@/components/khamar/DateField.vue';
import { Input } from '@/components/ui/input';
import { day, taka } from '@/lib/khamar';
import type { TreeNode } from '@/lib/khamar';

defineOptions({
    layout: { breadcrumbs: [{ title: 'রিপোর্ট', href: '/reports' }] },
});

const props = defineProps<{
    from: string;
    to: string;
    tree: TreeNode[];
    farm: { income: number; expense: number; profit: number };
    accounts: {
        id: number;
        code: string;
        name: string;
        type: string;
        internal: boolean;
        amount: number;
    }[];
    trend: { month: string; income: number; expense: number }[];
    balances: {
        id: number;
        code: string;
        name: string;
        type: string;
        subtype: string | null;
        balance: number;
    }[];
    retained: number;
}>();

const range = reactive({ from: props.from, to: props.to });
const apply = () =>
    router.get('/reports', { ...range }, { preserveState: true });
const print = () => window.print();

const realAccounts = computed(() => props.accounts.filter((a) => !a.internal));
const assets = computed(() => props.balances.filter((b) => b.type === 'asset'));
const liabilities = computed(() =>
    props.balances.filter((b) => b.type === 'liability'),
);
const equity = computed(() =>
    props.balances.filter((b) => b.type === 'equity'),
);
const sum = (rows: { balance: number }[]) =>
    rows.reduce((total, row) => total + row.balance, 0);
</script>

<template>
    <Head title="রিপোর্ট" />

    <div class="flex flex-1 flex-col p-4 md:p-6">
        <PageTitle
            title="খামারের রিপোর্ট"
            :subtitle="`${day(from)} থেকে ${day(to)}`"
        >
            <template #actions>
                <form
                    class="flex items-center gap-2 print:hidden"
                    @submit.prevent="apply"
                >
                    <DateField v-model="range.from" class="w-36" />
                    <span class="text-muted-foreground">—</span>
                    <DateField v-model="range.to" class="w-36" />
                    <Button type="submit" size="sm" variant="secondary"
                        >দেখুন</Button
                    >
                </form>
                <Button
                    size="sm"
                    variant="secondary"
                    class="print:hidden"
                    @click="print"
                    ><Printer /> প্রিন্ট / PDF</Button
                >
            </template>
        </PageTitle>

        <div class="grid gap-6 xl:grid-cols-2">
            <section
                class="min-w-0 rounded-xl border border-border bg-card p-4"
            >
                <h2 class="font-display mb-3 text-lg">
                    লাভ-ক্ষতি — খাত অনুযায়ী
                </h2>
                <table class="w-full text-sm">
                    <tbody>
                        <tr class="bg-muted/50">
                            <td class="px-2 py-1.5 font-medium" colspan="2">
                                আয়
                            </td>
                        </tr>
                        <tr
                            v-for="a in realAccounts.filter(
                                (a) => a.type === 'income',
                            )"
                            :key="a.id"
                            class="border-b border-border/50"
                        >
                            <td class="px-2 py-1.5">{{ a.name }}</td>
                            <td class="num px-2 py-1.5 text-right">
                                {{ taka(a.amount) }}
                            </td>
                        </tr>
                        <tr class="font-semibold">
                            <td class="px-2 py-2">মোট আয়</td>
                            <td class="num px-2 py-2 text-right text-income">
                                {{ taka(farm.income) }}
                            </td>
                        </tr>
                        <tr class="bg-muted/50">
                            <td class="px-2 py-1.5 font-medium" colspan="2">
                                খরচ
                            </td>
                        </tr>
                        <tr
                            v-for="a in realAccounts.filter(
                                (a) => a.type === 'expense',
                            )"
                            :key="a.id"
                            class="border-b border-border/50"
                        >
                            <td class="px-2 py-1.5">{{ a.name }}</td>
                            <td class="num px-2 py-1.5 text-right">
                                {{ taka(a.amount) }}
                            </td>
                        </tr>
                        <tr class="font-semibold">
                            <td class="px-2 py-2">মোট খরচ</td>
                            <td class="num px-2 py-2 text-right text-expense">
                                {{ taka(farm.expense) }}
                            </td>
                        </tr>
                        <tr
                            class="border-t-2 border-foreground/70 text-base font-semibold"
                        >
                            <td class="px-2 py-2.5">নিট লাভ / ক্ষতি</td>
                            <td
                                :class="[
                                    'num px-2 py-2.5 text-right',
                                    farm.profit < 0 && 'text-destructive',
                                ]"
                            >
                                {{ taka(farm.profit) }}
                            </td>
                        </tr>
                    </tbody>
                </table>
            </section>

            <section
                class="min-w-0 rounded-xl border border-border bg-card p-4"
            >
                <h2 class="font-display mb-3 text-lg">
                    ক্যাটাগরি / প্রজেক্ট অনুযায়ী
                </h2>
                <table class="w-full text-sm">
                    <thead>
                        <tr
                            class="border-b border-border text-left text-xs text-muted-foreground"
                        >
                            <th class="pb-2 font-medium">ক্যাটাগরি</th>
                            <th class="pb-2 text-right font-medium">আয়</th>
                            <th class="pb-2 text-right font-medium">খরচ</th>
                            <th class="pb-2 text-right font-medium">লাভ</th>
                        </tr>
                    </thead>
                    <tbody>
                        <CenterRows
                            :nodes="tree"
                            :query="`?from=${from}&to=${to}`"
                        />
                    </tbody>
                </table>
            </section>

            <section
                class="min-w-0 rounded-xl border border-border bg-card p-4"
            >
                <h2 class="font-display mb-3 text-lg">
                    খামারের সম্পদ ও দায় (আজ পর্যন্ত)
                </h2>
                <div class="grid gap-4 sm:grid-cols-2">
                    <div>
                        <p
                            class="mb-1 text-xs font-medium text-muted-foreground"
                        >
                            সম্পদ
                        </p>
                        <p
                            v-for="b in assets"
                            :key="b.id"
                            class="flex justify-between border-b border-border/50 py-1.5 text-sm"
                        >
                            <span>{{ b.name }}</span
                            ><span class="num">{{ taka(b.balance) }}</span>
                        </p>
                        <p class="flex justify-between py-2 font-semibold">
                            <span>মোট</span
                            ><span class="num">{{ taka(sum(assets)) }}</span>
                        </p>
                    </div>
                    <div>
                        <p
                            class="mb-1 text-xs font-medium text-muted-foreground"
                        >
                            দায় ও মূলধন
                        </p>
                        <p
                            v-for="b in [...liabilities, ...equity]"
                            :key="b.id"
                            class="flex justify-between border-b border-border/50 py-1.5 text-sm"
                        >
                            <span>{{ b.name }}</span
                            ><span class="num">{{ taka(b.balance) }}</span>
                        </p>
                        <p
                            class="flex justify-between border-b border-border/50 py-1.5 text-sm"
                        >
                            <span>এ পর্যন্ত জমা লাভ / ক্ষতি</span
                            ><span class="num">{{ taka(retained) }}</span>
                        </p>
                        <p class="flex justify-between py-2 font-semibold">
                            <span>মোট</span
                            ><span class="num">{{
                                taka(sum(liabilities) + sum(equity) + retained)
                            }}</span>
                        </p>
                    </div>
                </div>
                <p class="mt-2 text-xs text-muted-foreground">
                    দুই পাশ সবসময় সমান থাকে — না মিললে কোথাও ভুল আছে।
                </p>
            </section>

            <section
                class="min-w-0 rounded-xl border border-border bg-card p-4"
            >
                <h2 class="font-display mb-4 text-lg">গত ১২ মাস</h2>
                <TrendBars :months="trend" />
            </section>
        </div>
    </div>
</template>
