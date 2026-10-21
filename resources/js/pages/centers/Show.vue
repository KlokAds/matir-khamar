<script setup lang="ts">
import { Head, Link, router, useForm } from '@inertiajs/vue3';
import { Milk, Pencil, Scale, TrendingDown, TrendingUp } from '@lucide/vue';
import { computed, reactive, ref } from 'vue';
import CenterIcon from '@/components/khamar/CenterIcon.vue';
import CenterRows from '@/components/khamar/CenterRows.vue';
import Field from '@/components/khamar/Field.vue';
import NSelect from '@/components/khamar/NSelect.vue';
import PageTitle from '@/components/khamar/PageTitle.vue';
import StatTile from '@/components/khamar/StatTile.vue';
import TrendBars from '@/components/khamar/TrendBars.vue';
import { Button } from '@/components/ui/button';
import DateField from '@/components/khamar/DateField.vue';
import { Input } from '@/components/ui/input';
import { t, tAll } from '@/lib/i18n';
import { centerLabel, day, qty, taka, useCan } from '@/lib/khamar';
import type { Center, TreeNode } from '@/lib/khamar';

const props = defineProps<{
    center: {
        id: number;
        code: string;
        name: string;
        kind: string;
        holds_animals: boolean;
        is_active: boolean;
        description: string | null;
        parent_id: number | null;
        sort: number;
    };
    kindLabel: string;
    from: string;
    to: string;
    totals: {
        income: number;
        expense: number;
        accounts: {
            id: number;
            code: string;
            name: string;
            type: string;
            internal: boolean;
            amount: number;
        }[];
    };
    trend: { month: string; income: number; expense: number }[];
    node: TreeNode | null;
    lines: {
        id: number;
        entry_id: number;
        number: string;
        date: string;
        description: string;
        account: string;
        type: string;
        center: string | null;
        animal: string | null;
        amount: number;
    }[];
    milk: number;
    costPerLiter: number | null;
    animals: {
        id: number;
        tag: string;
        name: string | null;
        sex: string;
        breed: string | null;
        book_value: number;
        acquired_on: string;
    }[];
    kinds: Record<string, string>;
    parents: Center[];
}>();

defineOptions({
    layout: {
        breadcrumbs: [
            { title: 'ক্যাটাগরি ও প্রজেক্ট', href: '/centers' },
            { title: 'বিস্তারিত', href: '#' },
        ],
    },
});

const can = useCan();
const kindLabels = computed(() => tAll(props.kinds));
const tCenter = (c: Center) => centerLabel({ ...c, name: t(c.name) });
const range = reactive({ from: props.from, to: props.to });
const editing = ref(false);
const edit = useForm({
    ...props.center,
    description: props.center.description ?? '',
});
const applyRange = () =>
    router.get(
        `/centers/${props.center.id}`,
        { ...range },
        { preserveState: true },
    );
</script>

<template>
    <Head :title="t(center.name)" />

    <div class="flex flex-1 flex-col p-4 md:p-6">
        <PageTitle
            :title="t(center.name)"
            :subtitle="`${t(kindLabel)}${center.is_active ? '' : ' · ' + t('এখনো চালু হয়নি')}${center.description ? ' · ' + center.description : ''}`"
        >
            <template #actions>
                <form
                    class="flex items-center gap-2"
                    @submit.prevent="applyRange"
                >
                    <DateField v-model="range.from" class="w-36" />
                    <span class="text-muted-foreground">—</span>
                    <DateField v-model="range.to" class="w-36" />
                    <Button type="submit" size="sm" variant="secondary">{{
                        t('দেখুন')
                    }}</Button>
                </form>
                <Button
                    v-if="can['manage-setup']"
                    size="sm"
                    variant="ghost"
                    @click="editing = !editing"
                    ><Pencil /> {{ t('বদলান') }}</Button
                >
            </template>
        </PageTitle>

        <form
            v-if="editing"
            class="mb-5 grid gap-3 rounded-xl border border-border bg-card p-4 sm:grid-cols-2 lg:grid-cols-4"
            @submit.prevent="
                edit.put(`/setup/centers/${center.id}`, {
                    onSuccess: () => (editing = false),
                })
            "
        >
            <Field :label="t('নাম')" :error="edit.errors.name"
                ><Input v-model="edit.name" required
            /></Field>
            <Field :label="t('কোড')" :error="edit.errors.code"
                ><Input v-model="edit.code" required
            /></Field>
            <Field :label="t('ধরন')"
                ><NSelect v-model="edit.kind"
                    ><option
                        v-for="(label, key) in kindLabels"
                        :key="key"
                        :value="key"
                    >
                        {{ label }}
                    </option></NSelect
                ></Field
            >
            <Field
                :label="t('কোন প্রজেক্টের ভেতরে')"
                :error="edit.errors.parent_id"
                ><NSelect v-model="edit.parent_id"
                    ><option :value="null">{{ t('— সবার উপরে —') }}</option>
                    <option
                        v-for="p in parents.filter((p) => p.id !== center.id)"
                        :key="p.id"
                        :value="p.id"
                    >
                        {{ tCenter(p) }}
                    </option></NSelect
                ></Field
            >
            <Field :label="t('বিবরণ')"
                ><Input v-model="edit.description"
            /></Field>
            <label class="flex items-center gap-2 text-sm"
                ><input
                    v-model="edit.holds_animals"
                    type="checkbox"
                    class="size-4"
                />
                {{ t('এখানে পশু থাকে') }}</label
            >
            <label class="flex items-center gap-2 text-sm"
                ><input
                    v-model="edit.is_active"
                    type="checkbox"
                    class="size-4"
                />
                {{ t('চালু') }}</label
            >
            <p
                v-if="edit.errors.holds_animals"
                class="text-sm text-destructive"
            >
                {{ edit.errors.holds_animals }}
            </p>
            <div class="flex gap-2">
                <Button type="submit" :disabled="edit.processing">{{
                    t('রাখুন')
                }}</Button>
            </div>
        </form>

        <div class="grid gap-3 sm:grid-cols-2 xl:grid-cols-4">
            <StatTile
                :label="t('আয়')"
                :value="taka(totals.income)"
                :icon="TrendingUp"
                tone="paddy"
            />
            <StatTile
                :label="t('খরচ')"
                :value="taka(totals.expense)"
                :icon="TrendingDown"
                tone="clay"
            />
            <StatTile
                :label="t('লাভ / ক্ষতি')"
                :value="taka(totals.income - totals.expense)"
                :icon="Scale"
                :tone="totals.income - totals.expense < 0 ? 'clay' : 'turmeric'"
            />
            <StatTile
                v-if="milk > 0"
                :label="t('দুধ / লিটারে খরচ')"
                :value="qty(milk, t('লি.'))"
                :hint="
                    costPerLiter
                        ? t('প্রতি লিটারে খরচ :amount', {
                              amount: taka(costPerLiter),
                          })
                        : ''
                "
                :icon="Milk"
                tone="pond"
            />
            <StatTile
                v-else-if="center.holds_animals"
                :label="t('এখানে গরু')"
                :value="t(':nটি', { n: animals.length })"
                tone="soil"
            />
        </div>

        <div class="mt-6 grid gap-6 xl:grid-cols-5">
            <section
                class="min-w-0 rounded-xl border border-border bg-card p-4 xl:col-span-3"
            >
                <h2 class="mb-3 font-display text-lg">
                    {{ t('খাত অনুযায়ী') }}
                </h2>
                <table class="w-full text-sm">
                    <tbody>
                        <tr
                            v-for="a in totals.accounts"
                            :key="a.id"
                            class="border-b border-border/60"
                        >
                            <td class="py-2">
                                {{ t(a.name) }}
                                <span
                                    v-if="a.internal"
                                    class="ml-1 rounded bg-turmeric/25 px-1.5 text-[11px]"
                                    >{{ t('নিজস্ব') }}</span
                                >
                            </td>
                            <td
                                :class="[
                                    'num py-2 text-right',
                                    a.type === 'income'
                                        ? 'text-income'
                                        : 'text-expense',
                                ]"
                            >
                                {{ a.type === 'income' ? '+' : '−' }}
                                {{ taka(a.amount) }}
                            </td>
                        </tr>
                        <tr v-if="!totals.accounts.length">
                            <td class="py-6 text-center text-muted-foreground">
                                {{ t('এই সময়ে কোনো লেনদেন নেই।') }}
                            </td>
                        </tr>
                    </tbody>
                </table>

                <template v-if="node && node.children.length">
                    <h2 class="mt-6 mb-2 font-display text-lg">
                        {{ t('ভেতরের ক্যাটাগরি') }}
                    </h2>
                    <table class="w-full text-sm">
                        <tbody>
                            <CenterRows
                                :nodes="node.children"
                                :query="`?from=${from}&to=${to}`"
                            />
                        </tbody>
                    </table>
                </template>
            </section>

            <section
                class="min-w-0 rounded-xl border border-border bg-card p-4 xl:col-span-2"
            >
                <h2 class="mb-4 font-display text-lg">{{ t('গত ৬ মাস') }}</h2>
                <TrendBars :months="trend" />
            </section>
        </div>

        <section
            v-if="animals.length"
            class="mt-6 rounded-xl border border-border bg-card p-4"
        >
            <h2 class="mb-3 font-display text-lg">{{ t('এখানকার গরু') }}</h2>
            <div class="flex flex-wrap gap-2">
                <Link
                    v-for="a in animals"
                    :key="a.id"
                    :href="`/animals/${a.id}`"
                    class="flex items-center gap-2 rounded-lg border border-border px-3 py-2 text-sm hover:bg-accent"
                >
                    <CenterIcon kind="livestock" />
                    <span class="font-medium">{{ a.tag }}</span>
                    <span class="text-muted-foreground">{{
                        a.name ?? a.breed ?? ''
                    }}</span>
                </Link>
            </div>
        </section>

        <section class="mt-6 rounded-xl border border-border bg-card">
            <h2 class="border-b border-border p-4 font-display text-lg">
                {{ t('লেনদেন') }}
            </h2>
            <div class="overflow-x-auto">
                <table class="w-full min-w-[640px] text-sm">
                    <tbody>
                        <tr
                            v-for="l in lines"
                            :key="l.id"
                            class="border-b border-border/60 hover:bg-accent/40"
                        >
                            <td
                                class="px-4 py-2.5 whitespace-nowrap text-muted-foreground"
                            >
                                {{ day(l.date) }}
                            </td>
                            <td class="px-4 py-2.5">
                                {{ l.description }}
                                <p class="text-xs text-muted-foreground">
                                    {{ t(l.account)
                                    }}<span
                                        v-if="
                                            l.center && l.center !== center.name
                                        "
                                    >
                                        · {{ t(l.center) }}</span
                                    ><span v-if="l.animal">
                                        · {{ l.animal }}</span
                                    >
                                </p>
                            </td>
                            <td
                                :class="[
                                    'num px-4 py-2.5 text-right',
                                    l.type === 'income'
                                        ? 'text-income'
                                        : 'text-expense',
                                ]"
                            >
                                {{ l.type === 'income' ? '+' : '−' }}
                                {{ taka(l.amount) }}
                            </td>
                            <td class="px-4 py-2.5">
                                <Link
                                    :href="`/entries/${l.entry_id}`"
                                    class="num text-primary hover:underline"
                                    >{{ l.number }}</Link
                                >
                            </td>
                        </tr>
                        <tr v-if="!lines.length">
                            <td
                                class="px-4 py-8 text-center text-muted-foreground"
                            >
                                {{ t('কিছু নেই।') }}
                            </td>
                        </tr>
                    </tbody>
                </table>
            </div>
        </section>
    </div>
</template>
