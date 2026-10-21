<script setup lang="ts">
import { Head, Link, useForm } from '@inertiajs/vue3';
import { ArrowRightLeft, Coins, HeartCrack } from '@lucide/vue';
import { ref } from 'vue';
import Field from '@/components/khamar/Field.vue';
import NSelect from '@/components/khamar/NSelect.vue';
import PageTitle from '@/components/khamar/PageTitle.vue';
import StatTile from '@/components/khamar/StatTile.vue';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { day, qty, taka, today, useCan } from '@/lib/khamar';
import type { Center, Option } from '@/lib/khamar';

const props = defineProps<{
    animal: {
        id: number; tag: string; name: string | null; sex: string; breed: string | null; status: string; status_label: string; acquisition_label: string;
        purchase_cost: number; book_value: number; sale_amount: number | null; notes: string | null; cost_center_id: number;
        acquired_on: string; birth_date: string | null; disposed_on: string | null; center: string; mother: string | null;
    };
    placements: { center: string; from: string; to: string | null }[];
    cost: { direct: number; shared: number; purchase: number; sale: number | null; profit: number | null };
    milk: number;
    lines: { entry_id: number; number: string; date: string; account: string; description: string; debit: number; credit: number }[];
    centers: Center[];
    moneyAccounts: Option[];
    customers: { id: number; name: string }[];
}>();

defineOptions({
    layout: { breadcrumbs: [{ title: 'গরুর তালিকা', href: '/animals' }, { title: 'কার্ড', href: '#' }] },
});

const can = useCan();
const action = ref<'' | 'transfer' | 'sell' | 'death'>('');
const transfer = useForm({ cost_center_id: props.centers.find((c) => c.id !== props.animal.cost_center_id)?.id ?? null, date: today() });
const sell = useForm({ date: today(), amount: '', money_account_id: props.moneyAccounts[0]?.id ?? null, party_id: null as number | null });
const death = useForm({ date: today(), note: '' });
const done = () => (action.value = '');
const totalCost = props.cost.purchase + props.cost.direct + props.cost.shared;
</script>

<template>
    <Head :title="animal.tag" />

    <div class="flex flex-1 flex-col p-4 md:p-6">
        <PageTitle :title="`${animal.tag}${animal.name ? ' · ' + animal.name : ''}`" :subtitle="`${animal.sex === 'female' ? 'গাভী/বকনা' : 'ষাঁড়/এঁড়ে'}${animal.breed ? ' · ' + animal.breed : ''} · ${animal.center} · ${animal.status_label}`">
            <template v-if="animal.status === 'active' && can['post-entries']" #actions>
                <Button variant="secondary" @click="action = action === 'transfer' ? '' : 'transfer'"><ArrowRightLeft /> ক্যাটাগরি বদল</Button>
                <Button @click="action = action === 'sell' ? '' : 'sell'"><Coins /> বিক্রি</Button>
                <Button variant="ghost" @click="action = action === 'death' ? '' : 'death'"><HeartCrack /> মৃত্যু</Button>
            </template>
        </PageTitle>

        <form v-if="action === 'transfer'" class="mb-5 grid gap-3 rounded-xl border border-border bg-card p-4 sm:grid-cols-[1fr_180px_auto] sm:items-end" @submit.prevent="transfer.post(`/animals/${animal.id}/transfer`, { onSuccess: done })">
            <Field label="নতুন ক্যাটাগরি (যেমন বকনা বাচ্চা দিলে → গাভী)" :error="transfer.errors.cost_center_id">
                <NSelect v-model="transfer.cost_center_id"><option v-for="c in centers.filter((c) => c.id !== animal.cost_center_id)" :key="c.id" :value="c.id">{{ c.name }}</option></NSelect>
            </Field>
            <Field label="কবে থেকে" :error="transfer.errors.date"><Input v-model="transfer.date" type="date" :max="today()" /></Field>
            <Button type="submit" :disabled="transfer.processing">বদলান</Button>
        </form>

        <form v-if="action === 'sell'" class="mb-5 grid gap-3 rounded-xl border border-border bg-card p-4 sm:grid-cols-2 lg:grid-cols-5 lg:items-end" @submit.prevent="sell.post(`/animals/${animal.id}/sell`, { onSuccess: done })">
            <Field label="বিক্রির তারিখ" :error="sell.errors.date"><Input v-model="sell.date" type="date" :max="today()" /></Field>
            <Field label="বিক্রয় মূল্য (৳)" :error="sell.errors.amount"><Input v-model="sell.amount" type="number" step="0.01" min="0" class="num" required /></Field>
            <Field label="টাকা কোথায় এল"><NSelect v-model="sell.money_account_id"><option v-for="a in moneyAccounts" :key="a.id" :value="a.id">{{ a.name }}</option></NSelect></Field>
            <Field label="ক্রেতা"><NSelect v-model="sell.party_id"><option :value="null">—</option><option v-for="p in customers" :key="p.id" :value="p.id">{{ p.name }}</option></NSelect></Field>
            <Button type="submit" :disabled="sell.processing">বিক্রি লিখুন</Button>
        </form>

        <form v-if="action === 'death'" class="mb-5 grid gap-3 rounded-xl border border-destructive/40 bg-card p-4 sm:grid-cols-[180px_1fr_auto] sm:items-end" @submit.prevent="death.post(`/animals/${animal.id}/death`, { onSuccess: done })">
            <Field label="তারিখ" :error="death.errors.date"><Input v-model="death.date" type="date" :max="today()" /></Field>
            <Field label="কারণ"><Input v-model="death.note" /></Field>
            <Button type="submit" variant="destructive" :disabled="death.processing">লিখুন</Button>
        </form>

        <div class="grid gap-3 sm:grid-cols-2 xl:grid-cols-4">
            <StatTile label="কেনা দাম" :value="taka(cost.purchase)" :hint="`${animal.acquisition_label} · ${day(animal.acquired_on)}`" tone="soil" />
            <StatTile label="এর পেছনে খরচ" :value="taka(cost.direct + cost.shared)" :hint="`নিজস্ব ${taka(cost.direct)} + ক্যাটাগরির ভাগ ${taka(cost.shared)}`" tone="clay" />
            <StatTile label="মোট বিনিয়োগ" :value="taka(totalCost)" tone="pond" />
            <StatTile
                v-if="cost.profit !== null"
                label="বিক্রিতে আসল লাভ"
                :value="taka(cost.profit)"
                :hint="`বিক্রি ${taka(cost.sale)}`"
                :tone="cost.profit < 0 ? 'clay' : 'paddy'"
            />
            <StatTile v-else-if="milk > 0" label="মোট দুধ" :value="qty(milk, 'লিটার')" tone="paddy" />
        </div>
        <p class="mt-2 text-xs text-muted-foreground">
            "ক্যাটাগরির ভাগ" = গরুটি যে ক্যাটাগরিতে যতদিন ছিল, সেই ক্যাটাগরির খাবার-ঔষধ-মজুরির খরচ থেকে পশু-দিন অনুযায়ী তার অংশ।
        </p>

        <div class="mt-6 grid gap-6 lg:grid-cols-3">
            <section class="min-w-0 rounded-xl border border-border bg-card p-4">
                <h2 class="mb-3 font-display text-lg">কোথায় কতদিন</h2>
                <ol class="relative ml-2 border-l-2 border-dashed border-border pl-4">
                    <li v-for="(p, i) in placements" :key="i" class="mb-4 last:mb-0">
                        <span class="absolute -left-[7px] mt-1.5 size-3 rounded-full bg-paddy" />
                        <p class="font-medium">{{ p.center }}</p>
                        <p class="text-xs text-muted-foreground">{{ day(p.from) }} — {{ p.to ? day(p.to) : 'এখন' }}</p>
                    </li>
                </ol>
                <p v-if="animal.notes" class="mt-4 rounded-lg bg-muted/60 p-3 text-sm">{{ animal.notes }}</p>
                <p v-if="animal.mother" class="mt-2 text-sm text-muted-foreground">মা: {{ animal.mother }}</p>
            </section>

            <section class="min-w-0 rounded-xl border border-border bg-card lg:col-span-2">
                <h2 class="border-b border-border p-4 font-display text-lg">এই গরুর লেনদেন</h2>
                <table class="w-full text-sm">
                    <tbody>
                        <tr v-for="(l, i) in lines" :key="i" class="border-b border-border/60">
                            <td class="px-4 py-2.5 whitespace-nowrap text-muted-foreground">{{ day(l.date) }}</td>
                            <td class="px-4 py-2.5">{{ l.description }}<p class="text-xs text-muted-foreground">{{ l.account }}</p></td>
                            <td class="num px-4 py-2.5 text-right">{{ taka(l.debit || l.credit) }}</td>
                            <td class="px-4 py-2.5"><Link :href="`/entries/${l.entry_id}`" class="num text-primary hover:underline">{{ l.number }}</Link></td>
                        </tr>
                        <tr v-if="!lines.length"><td class="px-4 py-8 text-center text-muted-foreground">কিছু নেই।</td></tr>
                    </tbody>
                </table>
            </section>
        </div>
    </div>
</template>
