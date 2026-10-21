<script setup lang="ts">
import { Head, router, useForm } from '@inertiajs/vue3';
import { Plus } from '@lucide/vue';
import { reactive, ref } from 'vue';
import CenterRows from '@/components/khamar/CenterRows.vue';
import Field from '@/components/khamar/Field.vue';
import NSelect from '@/components/khamar/NSelect.vue';
import PageTitle from '@/components/khamar/PageTitle.vue';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { centerLabel, taka, useCan } from '@/lib/khamar';
import type { Center, TreeNode } from '@/lib/khamar';

defineOptions({
    layout: {
        breadcrumbs: [{ title: 'ক্যাটাগরি ও প্রজেক্ট', href: '/centers' }],
    },
});

const props = defineProps<{
    tree: TreeNode[];
    farm: { income: number; expense: number; profit: number };
    from: string;
    to: string;
    kinds: Record<string, string>;
    parents: Center[];
}>();

const can = useCan();
const range = reactive({ from: props.from, to: props.to });
const adding = ref(false);
const center = useForm({
    name: '',
    code: '',
    kind: 'livestock',
    parent_id: props.parents[0]?.id ?? null,
    holds_animals: false,
    is_active: true,
    sort: 100,
    description: '',
});

const applyRange = () =>
    router.get('/centers', { ...range }, { preserveState: true });
</script>

<template>
    <Head title="ক্যাটাগরি ও প্রজেক্ট" />

    <div class="flex flex-1 flex-col p-4 md:p-6">
        <PageTitle
            title="ক্যাটাগরি ও প্রজেক্ট"
            subtitle="প্রতিটি ক্যাটাগরির নিজের আয়-খরচ, উপরে উঠলে সব মিলিয়ে প্রজেক্ট আর পুরো খামার।"
        >
            <template #actions>
                <form
                    class="flex items-center gap-2"
                    @submit.prevent="applyRange"
                >
                    <Input v-model="range.from" type="date" class="w-36" />
                    <span class="text-muted-foreground">—</span>
                    <Input v-model="range.to" type="date" class="w-36" />
                    <Button type="submit" size="sm" variant="secondary"
                        >দেখুন</Button
                    >
                </form>
                <Button
                    v-if="can['manage-setup']"
                    size="sm"
                    @click="adding = !adding"
                    ><Plus /> নতুন ক্যাটাগরি</Button
                >
            </template>
        </PageTitle>

        <form
            v-if="adding"
            class="mb-5 grid gap-3 rounded-xl border border-border bg-card p-4 sm:grid-cols-2 lg:grid-cols-4"
            @submit.prevent="center.post('/setup/centers')"
        >
            <p
                class="text-sm text-muted-foreground sm:col-span-2 lg:col-span-4"
            >
                ভবিষ্যতে মাছ, হাঁস-মুরগি, দোকান, নতুন জমি — যা-ই আসুক, এখান থেকে
                নতুন ক্যাটাগরি বা প্রজেক্ট খুলুন। কোনো কোড বদলাতে হবে না।
            </p>
            <Field label="নাম" :error="center.errors.name"
                ><Input
                    v-model="center.name"
                    required
                    placeholder="যেমন: হাঁস পালন"
            /></Field>
            <Field
                label="ইংরেজি কোড"
                :error="center.errors.code"
                hint="যেমন DUCK"
                ><Input
                    v-model="center.code"
                    required
                    class="uppercase"
                    @input="center.code = center.code.toUpperCase()"
            /></Field>
            <Field label="ধরন" :error="center.errors.kind"
                ><NSelect v-model="center.kind"
                    ><option
                        v-for="(label, key) in kinds"
                        :key="key"
                        :value="key"
                    >
                        {{ label }}
                    </option></NSelect
                ></Field
            >
            <Field label="কোন প্রজেক্টের ভেতরে" :error="center.errors.parent_id"
                ><NSelect v-model="center.parent_id"
                    ><option v-for="p in parents" :key="p.id" :value="p.id">
                        {{ centerLabel(p) }}
                    </option></NSelect
                ></Field
            >
            <label class="flex items-center gap-2 text-sm"
                ><input
                    v-model="center.holds_animals"
                    type="checkbox"
                    class="size-4 accent-[var(--primary)]"
                />
                এখানে পশু থাকবে</label
            >
            <label class="flex items-center gap-2 text-sm"
                ><input
                    v-model="center.is_active"
                    type="checkbox"
                    class="size-4 accent-[var(--primary)]"
                />
                এখনই চালু</label
            >
            <div class="flex gap-2 lg:col-span-2 lg:justify-end">
                <Button type="submit" :disabled="center.processing"
                    >যোগ করুন</Button
                >
                <Button type="button" variant="ghost" @click="adding = false"
                    >বাদ</Button
                >
            </div>
        </form>

        <div
            class="overflow-x-auto rounded-xl border border-border bg-card p-4"
        >
            <table class="w-full min-w-[560px] text-sm">
                <thead>
                    <tr
                        class="border-b border-border text-left text-xs text-muted-foreground"
                    >
                        <th class="pb-2 font-medium">ক্যাটাগরি / প্রজেক্ট</th>
                        <th class="pb-2 text-right font-medium">আয়</th>
                        <th class="pb-2 text-right font-medium">খরচ</th>
                        <th class="pb-2 text-right font-medium">লাভ / ক্ষতি</th>
                    </tr>
                </thead>
                <tbody>
                    <CenterRows
                        :nodes="tree"
                        :query="`?from=${from}&to=${to}`"
                    />
                </tbody>
                <tfoot>
                    <tr class="font-semibold">
                        <td class="pt-3">পুরো খামার (নিজস্ব লেনদেন বাদে)</td>
                        <td class="num pt-3 text-right text-income">
                            {{ taka(farm.income) }}
                        </td>
                        <td class="num pt-3 text-right text-expense">
                            {{ taka(farm.expense) }}
                        </td>
                        <td
                            :class="[
                                'num pt-3 text-right',
                                farm.profit < 0 && 'text-destructive',
                            ]"
                        >
                            {{ taka(farm.profit) }}
                        </td>
                    </tr>
                </tfoot>
            </table>
        </div>
    </div>
</template>
