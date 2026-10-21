<script setup lang="ts">
import { Head, Link, router, useForm } from '@inertiajs/vue3';
import {
    ArrowRightLeft,
    Banknote,
    Landmark,
    Plus,
    Smartphone,
    UserRound,
} from '@lucide/vue';
import { computed, ref } from 'vue';
import Field from '@/components/khamar/Field.vue';
import NSelect from '@/components/khamar/NSelect.vue';
import PageTitle from '@/components/khamar/PageTitle.vue';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { day, taka, today, useCan } from '@/lib/khamar';
import type { Option } from '@/lib/khamar';

defineOptions({
    layout: {
        breadcrumbs: [{ title: 'টাকা কোথায় আছে', href: '/money' }],
    },
});

type Place = {
    id: number;
    name: string;
    kind: string;
    kind_label: string;
    balance: number;
    is_active: boolean;
};

const props = defineProps<{
    places: Place[];
    kinds: Record<string, string>;
    selected: {
        id: number;
        name: string;
        kind_label: string;
        rows: {
            entry_id: number;
            number: string;
            date: string;
            description: string;
            in: number;
            out: number;
            balance: number;
        }[];
    } | null;
    moneyAccounts: Option[];
}>();

const can = useCan();
const icons: Record<string, typeof Banknote> = {
    cash: Banknote,
    bank: Landmark,
    wallet: Smartphone,
    person: UserRound,
};
const tones: Record<string, string> = {
    cash: 'bg-paddy/12 text-paddy',
    bank: 'bg-pond/12 text-pond',
    wallet: 'bg-clay/12 text-clay',
    person: 'bg-turmeric/25 text-soil',
};

const total = computed(() =>
    props.places.reduce((sum, place) => sum + place.balance, 0),
);
const byKind = computed(() =>
    Object.keys(props.kinds)
        .map((kind) => ({
            kind,
            label: props.kinds[kind],
            amount: props.places
                .filter((p) => p.kind === kind)
                .reduce((sum, p) => sum + p.balance, 0),
        }))
        .filter((group) => group.amount !== 0),
);

const show = (place: Place) =>
    router.get('/money', { place: place.id }, { preserveScroll: true });

const moving = ref(false);
const move = useForm({
    form: 'transfer',
    date: today(),
    from_account_id: props.moneyAccounts[0]?.id ?? null,
    to_account_id: props.moneyAccounts[1]?.id ?? null,
    amount: '',
    description: '',
    stay: true,
});
const sendMove = () =>
    move.post('/entries', {
        preserveScroll: true,
        onSuccess: () => {
            moving.value = false;
            move.reset('amount', 'description');
        },
    });

const adding = ref(false);
const place = useForm({
    name: '',
    kind: 'person',
    opening: '',
    opening_date: today(),
});
</script>

<template>
    <Head title="টাকা কোথায় আছে" />

    <div class="flex flex-1 flex-col p-4 md:p-6">
        <PageTitle
            title="টাকা কোথায় আছে"
            subtitle="হাতে, ব্যাংকে, বিকাশে, কারো কাছে — প্রতিটি জায়গার আলাদা হিসাব। এক জায়গা থেকে আরেক জায়গায় সরালে মোট ঠিক থাকে।"
        >
            <template #actions>
                <Button
                    v-if="can['post-entries']"
                    variant="secondary"
                    @click="moving = !moving"
                    ><ArrowRightLeft /> টাকা সরান</Button
                >
                <Button v-if="can['manage-setup']" @click="adding = !adding"
                    ><Plus /> নতুন জায়গা</Button
                >
            </template>
        </PageTitle>

        <section
            class="mb-5 rounded-xl border border-primary/30 bg-primary/5 p-4"
        >
            <p class="text-sm text-muted-foreground">খামারের মোট টাকা</p>
            <p class="num font-display text-3xl">{{ taka(total) }}</p>
            <p
                class="mt-1 flex flex-wrap gap-x-4 text-sm text-muted-foreground"
            >
                <span v-for="g in byKind" :key="g.kind"
                    >{{ g.label }}:
                    <b class="num text-foreground">{{
                        taka(g.amount)
                    }}</b></span
                >
            </p>
        </section>

        <form
            v-if="moving"
            class="mb-5 grid gap-3 rounded-xl border border-border bg-card p-4 sm:grid-cols-2 lg:grid-cols-5 lg:items-end"
            @submit.prevent="sendMove"
        >
            <Field label="কোথা থেকে" :error="move.errors.from_account_id">
                <NSelect v-model="move.from_account_id">
                    <option
                        v-for="a in moneyAccounts"
                        :key="a.id"
                        :value="a.id"
                    >
                        {{ a.name }}
                    </option>
                </NSelect>
            </Field>
            <Field label="কোথায়" :error="move.errors.to_account_id">
                <NSelect v-model="move.to_account_id">
                    <option
                        v-for="a in moneyAccounts"
                        :key="a.id"
                        :value="a.id"
                    >
                        {{ a.name }}
                    </option>
                </NSelect>
            </Field>
            <Field label="টাকা (৳)" :error="move.errors.amount">
                <Input
                    v-model="move.amount"
                    type="number"
                    step="0.01"
                    min="0"
                    class="num"
                    required
                />
            </Field>
            <Field label="তারিখ" :error="move.errors.date">
                <Input v-model="move.date" type="date" :max="today()" />
            </Field>
            <Button type="submit" :disabled="move.processing">সরান</Button>
            <div class="sm:col-span-2 lg:col-span-5">
                <Field label="কেন (ঐচ্ছিক)">
                    <Input
                        v-model="move.description"
                        placeholder="যেমন: ব্যাংক থেকে তুলে খরচের জন্য মিঠুকে দেওয়া"
                    />
                </Field>
            </div>
        </form>

        <form
            v-if="adding"
            class="mb-5 grid gap-3 rounded-xl border border-border bg-card p-4 sm:grid-cols-2 lg:grid-cols-5 lg:items-end"
            @submit.prevent="
                place.post('/setup/money', {
                    onSuccess: () => {
                        adding = false;
                        place.reset();
                    },
                })
            "
        >
            <Field label="নাম" :error="place.errors.name">
                <Input
                    v-model="place.name"
                    required
                    placeholder="যেমন: মিঠুর হাতে, সোনালী ব্যাংক"
                />
            </Field>
            <Field label="ধরন" :error="place.errors.kind">
                <NSelect v-model="place.kind">
                    <option
                        v-for="(label, key) in kinds"
                        :key="key"
                        :value="key"
                    >
                        {{ label }}
                    </option>
                </NSelect>
            </Field>
            <Field
                label="এখন কত আছে (৳)"
                :error="place.errors.opening"
                hint="খাতা শুরুর আগের টাকা; না থাকলে খালি"
            >
                <Input
                    v-model="place.opening"
                    type="number"
                    step="0.01"
                    min="0"
                    class="num"
                />
            </Field>
            <Field
                label="কোন তারিখের হিসাবে"
                :error="place.errors.opening_date"
            >
                <Input
                    v-model="place.opening_date"
                    type="date"
                    :max="today()"
                />
            </Field>
            <Button type="submit" :disabled="place.processing">যোগ করুন</Button>
        </form>

        <div class="grid gap-3 sm:grid-cols-2 xl:grid-cols-4">
            <button
                v-for="p in places"
                :key="p.id"
                type="button"
                :class="[
                    'rounded-xl border bg-card p-4 text-left transition-shadow hover:shadow-md',
                    selected?.id === p.id
                        ? 'border-primary ring-2 ring-primary/30'
                        : 'border-border',
                    !p.is_active && 'opacity-50',
                ]"
                @click="show(p)"
            >
                <div class="flex items-start justify-between gap-3">
                    <div class="min-w-0">
                        <p class="truncate font-medium">{{ p.name }}</p>
                        <p class="text-xs text-muted-foreground">
                            {{ p.kind_label }}
                        </p>
                    </div>
                    <span :class="['rounded-lg p-2', tones[p.kind]]">
                        <component :is="icons[p.kind]" class="size-5" />
                    </span>
                </div>
                <p
                    :class="[
                        'num mt-3 text-2xl font-semibold',
                        p.balance < 0 && 'text-destructive',
                    ]"
                >
                    {{ taka(p.balance) }}
                </p>
            </button>
        </div>

        <section
            v-if="selected"
            class="mt-6 min-w-0 rounded-xl border border-border bg-card"
        >
            <h2 class="font-display border-b border-border p-4 text-lg">
                {{ selected.name }} — আসা-যাওয়া
            </h2>
            <div class="overflow-x-auto">
                <table class="w-full min-w-[640px] text-sm">
                    <thead class="text-left text-xs text-muted-foreground">
                        <tr class="border-b border-border">
                            <th class="px-4 py-2 font-medium">তারিখ</th>
                            <th class="px-4 py-2 font-medium">বিবরণ</th>
                            <th class="px-4 py-2 text-right font-medium">এল</th>
                            <th class="px-4 py-2 text-right font-medium">
                                গেল
                            </th>
                            <th class="px-4 py-2 text-right font-medium">
                                জের
                            </th>
                        </tr>
                    </thead>
                    <tbody>
                        <tr
                            v-for="(r, i) in selected.rows"
                            :key="i"
                            class="border-b border-border/60"
                        >
                            <td
                                class="px-4 py-2.5 whitespace-nowrap text-muted-foreground"
                            >
                                {{ day(r.date) }}
                            </td>
                            <td class="px-4 py-2.5">
                                <Link
                                    :href="`/entries/${r.entry_id}`"
                                    class="hover:underline"
                                    >{{ r.description }}</Link
                                >
                            </td>
                            <td class="num px-4 py-2.5 text-right text-income">
                                {{ r.in ? taka(r.in) : '' }}
                            </td>
                            <td class="num px-4 py-2.5 text-right text-expense">
                                {{ r.out ? taka(r.out) : '' }}
                            </td>
                            <td class="num px-4 py-2.5 text-right font-medium">
                                {{ taka(r.balance) }}
                            </td>
                        </tr>
                        <tr v-if="!selected.rows.length">
                            <td
                                colspan="5"
                                class="px-4 py-8 text-center text-muted-foreground"
                            >
                                এখনো কোনো আসা-যাওয়া নেই।
                            </td>
                        </tr>
                    </tbody>
                </table>
            </div>
        </section>
    </div>
</template>
