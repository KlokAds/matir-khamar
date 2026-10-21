<script setup lang="ts">
import { Head, Link, useForm } from '@inertiajs/vue3';
import { Plus, Sprout, Truck, Wheat } from '@lucide/vue';
import { ref } from 'vue';
import Field from '@/components/khamar/Field.vue';
import NSelect from '@/components/khamar/NSelect.vue';
import PageTitle from '@/components/khamar/PageTitle.vue';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { day, qty, taka, useCan } from '@/lib/khamar';
import type { Option } from '@/lib/khamar';

defineOptions({
    layout: { breadcrumbs: [{ title: 'স্টক', href: '/stock' }] },
});

defineProps<{
    items: {
        id: number;
        name: string;
        unit: string;
        unit_label: string;
        kind: string;
        qty_on_hand: number;
        value_on_hand: number;
        average_cost: number;
    }[];
    kinds: Record<string, string>;
    units: Record<string, string>;
    inventoryAccounts: Option[];
    expenseAccounts: Option[];
    recent: {
        id: number;
        date: string;
        item: string;
        unit: string;
        direction: string;
        session: string | null;
        qty: number;
        value: number;
        center: string | null;
        entry_id: number;
        number: string;
    }[];
}>();

const can = useCan();
const adding = ref(false);
const item = useForm({
    name: '',
    unit: 'kg',
    kind: 'roughage',
    inventory_account_id: null as number | null,
    expense_account_id: null as number | null,
    reorder_level: '',
});
</script>

<template>
    <Head title="স্টক" />

    <div class="flex flex-1 flex-col p-4 md:p-6">
        <PageTitle
            title="গুদাম / স্টক"
            subtitle="কোন মাল কতটুকু আছে, গড়ে কত দামে কেনা"
        >
            <template #actions>
                <Button as-child variant="secondary"
                    ><Link href="/stock/move?mode=issue"
                        ><Wheat /> খাওয়ানো</Link
                    ></Button
                >
                <Button v-if="can['post-entries']" as-child variant="secondary"
                    ><Link href="/stock/move?mode=produce"
                        ><Sprout /> উৎপাদন</Link
                    ></Button
                >
                <Button v-if="can['post-entries']" as-child
                    ><Link href="/stock/move?mode=purchase"
                        ><Truck /> মাল কেনা</Link
                    ></Button
                >
            </template>
        </PageTitle>

        <div class="grid gap-3 sm:grid-cols-2 xl:grid-cols-4">
            <div
                v-for="i in items"
                :key="i.id"
                class="rounded-xl border border-border bg-card p-4"
            >
                <div class="flex items-start justify-between gap-2">
                    <p class="font-medium">{{ i.name }}</p>
                    <span
                        class="rounded bg-muted px-1.5 py-0.5 text-[11px] text-muted-foreground"
                        >{{ kinds[i.kind] }}</span
                    >
                </div>
                <p class="num mt-2 font-display text-2xl">
                    {{ qty(i.qty_on_hand, i.unit_label) }}
                </p>
                <p class="num text-sm text-muted-foreground">
                    মূল্য {{ taka(i.value_on_hand) }} · গড়
                    {{ taka(Math.round(i.average_cost)) }}/{{ i.unit_label }}
                </p>
            </div>
        </div>

        <div v-if="can['manage-setup']" class="mt-4">
            <Button
                v-if="!adding"
                variant="ghost"
                size="sm"
                @click="adding = true"
                ><Plus /> নতুন মাল (যেমন: খৈল, লবণ, ভিটামিন)</Button
            >
            <form
                v-else
                class="grid gap-3 rounded-xl border border-border bg-card p-4 sm:grid-cols-3 lg:grid-cols-6"
                @submit.prevent="
                    item.post('/setup/items', {
                        preserveScroll: true,
                        onSuccess: () => {
                            adding = false;
                            item.reset();
                        },
                    })
                "
            >
                <Field label="নাম" :error="item.errors.name"
                    ><Input v-model="item.name" required
                /></Field>
                <Field label="একক"
                    ><NSelect v-model="item.unit"
                        ><option
                            v-for="(label, key) in units"
                            :key="key"
                            :value="key"
                        >
                            {{ label }}
                        </option></NSelect
                    ></Field
                >
                <Field label="ধরন"
                    ><NSelect v-model="item.kind"
                        ><option
                            v-for="(label, key) in kinds"
                            :key="key"
                            :value="key"
                        >
                            {{ label }}
                        </option></NSelect
                    ></Field
                >
                <Field
                    label="কোন স্টক খাতে"
                    :error="item.errors.inventory_account_id"
                    ><NSelect
                        v-model="item.inventory_account_id"
                        placeholder="বাছুন"
                        ><option
                            v-for="a in inventoryAccounts"
                            :key="a.id"
                            :value="a.id"
                        >
                            {{ a.name }}
                        </option></NSelect
                    ></Field
                >
                <Field
                    label="ব্যবহারে কোন খরচ খাত"
                    :error="item.errors.expense_account_id"
                    ><NSelect
                        v-model="item.expense_account_id"
                        placeholder="বাছুন"
                        ><option
                            v-for="a in expenseAccounts"
                            :key="a.id"
                            :value="a.id"
                        >
                            {{ a.name }}
                        </option></NSelect
                    ></Field
                >
                <Field label="কমলে সতর্ক করুন (পরিমাণ)"
                    ><Input v-model="item.reorder_level" type="number" min="0"
                /></Field>
                <div class="flex gap-2 sm:col-span-3 lg:col-span-6">
                    <Button type="submit" :disabled="item.processing"
                        >যোগ করুন</Button
                    >
                    <Button
                        type="button"
                        variant="ghost"
                        @click="adding = false"
                        >বাদ</Button
                    >
                </div>
            </form>
        </div>

        <section class="mt-6 rounded-xl border border-border bg-card">
            <h2 class="border-b border-border p-4 font-display text-lg">
                সাম্প্রতিক আনা-নেওয়া
            </h2>
            <div class="overflow-x-auto">
                <table class="w-full min-w-[640px] text-sm">
                    <tbody>
                        <tr
                            v-for="m in recent"
                            :key="m.id"
                            class="border-b border-border/60"
                        >
                            <td
                                class="px-4 py-2.5 whitespace-nowrap text-muted-foreground"
                            >
                                {{ day(m.date) }}
                            </td>
                            <td class="px-4 py-2.5">
                                <span
                                    :class="[
                                        'mr-2 rounded px-1.5 py-0.5 text-[11px]',
                                        m.direction === 'in'
                                            ? 'bg-paddy/15 text-paddy'
                                            : 'bg-clay/15 text-clay',
                                    ]"
                                    >{{
                                        m.direction === 'in' ? 'এল' : 'গেল'
                                    }}</span
                                >
                                {{ m.item }}
                                <span
                                    v-if="m.session"
                                    class="ml-1 text-xs text-muted-foreground"
                                    >· {{ m.session }}</span
                                >
                            </td>
                            <td class="num px-4 py-2.5 text-right">
                                {{ qty(m.qty, m.unit) }}
                            </td>
                            <td class="px-4 py-2.5 text-muted-foreground">
                                {{ m.center ?? 'স্টকে' }}
                            </td>
                            <td class="num px-4 py-2.5 text-right">
                                {{ taka(m.value) }}
                            </td>
                            <td class="px-4 py-2.5">
                                <Link
                                    :href="`/entries/${m.entry_id}`"
                                    class="num text-primary hover:underline"
                                    >{{ m.number }}</Link
                                >
                            </td>
                        </tr>
                        <tr v-if="!recent.length">
                            <td
                                class="px-4 py-8 text-center text-muted-foreground"
                            >
                                এখনো কিছু নেই।
                            </td>
                        </tr>
                    </tbody>
                </table>
            </div>
        </section>
    </div>
</template>
