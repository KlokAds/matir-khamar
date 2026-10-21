<script setup lang="ts">
import { Head, router, useForm } from '@inertiajs/vue3';
import { Pencil, Plus } from '@lucide/vue';
import { computed, ref } from 'vue';
import Field from '@/components/khamar/Field.vue';
import NSelect from '@/components/khamar/NSelect.vue';
import PageTitle from '@/components/khamar/PageTitle.vue';
import Toggle from '@/components/khamar/Toggle.vue';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { ask } from '@/lib/dialog';
import { taka } from '@/lib/khamar';

defineOptions({
    layout: { breadcrumbs: [{ title: 'হিসাব খাত', href: '/setup/accounts' }] },
});

const props = defineProps<{
    accounts: {
        id: number;
        code: string;
        name: string;
        type: string;
        subtype: string | null;
        parent_id: number | null;
        is_system: boolean;
        is_active: boolean;
        balance: number;
        used: boolean;
    }[];
    types: Record<string, string>;
    parents: { id: number; code: string; name: string; type: string }[];
}>();

const adding = ref(false);
const f = useForm({
    parent_id: props.parents[0]?.id ?? null,
    code: '',
    name: '',
    subtype: null as string | null,
});
const byType = computed(() =>
    Object.keys(props.types).map((type) => ({
        type,
        rows: props.accounts.filter((a) => a.type === type && a.parent_id),
    })),
);
const parentType = computed(
    () => props.parents.find((p) => p.id === f.parent_id)?.type,
);

const rename = async (a: { id: number; name: string; is_active: boolean }) => {
    const name = await ask.prompt({
        title: 'খাতের নাম বদলান',
        label: 'নতুন নাম',
        initial: a.name,
        confirmText: 'রাখুন',
    });

    if (name && name !== a.name) {
        router.put(
            `/setup/accounts/${a.id}`,
            { name, is_active: a.is_active },
            { preserveScroll: true },
        );
    }
};
const toggle = async (a: { id: number; name: string; is_active: boolean }) => {
    // Turning off only hides the head from new entries; its history stays in the books.
    if (
        a.is_active &&
        !(await ask.confirm({
            title: `"${a.name}" বন্ধ করবেন?`,
            body: 'নতুন এন্ট্রিতে আর দেখাবে না। আগের হিসাব যেমন আছে তেমনই থাকবে; পরে আবার চালু করা যাবে।',
            confirmText: 'বন্ধ করুন',
        }))
    ) {
        return;
    }

    router.put(
        `/setup/accounts/${a.id}`,
        { name: a.name, is_active: !a.is_active },
        { preserveScroll: true },
    );
};
</script>

<template>
    <Head title="হিসাব খাত" />

    <div class="flex flex-1 flex-col p-4 md:p-6">
        <PageTitle
            title="হিসাব খাত (চার্ট অব অ্যাকাউন্টস)"
            subtitle="নতুন খরচ বা আয়ের ধরন এলে খাত যোগ করুন। ✎ দিয়ে নাম বদলান; সুইচ দিয়ে খাত চালু বা বন্ধ করুন — বন্ধ খাত নতুন এন্ট্রিতে আসে না, পুরনো হিসাব থাকে।"
        >
            <template #actions
                ><Button @click="adding = !adding"
                    ><Plus /> নতুন খাত</Button
                ></template
            >
        </PageTitle>

        <form
            v-if="adding"
            class="mb-5 grid gap-3 rounded-xl border border-border bg-card p-4 sm:grid-cols-2 lg:grid-cols-5 lg:items-end"
            @submit.prevent="
                f.post('/setup/accounts', {
                    onSuccess: () => {
                        adding = false;
                        f.reset();
                    },
                })
            "
        >
            <Field label="কোন ধরনের ভেতরে"
                ><NSelect v-model="f.parent_id"
                    ><option v-for="p in parents" :key="p.id" :value="p.id">
                        {{ p.code }} · {{ p.name }}
                    </option></NSelect
                ></Field
            >
            <Field label="কোড" :error="f.errors.code" hint="যেমন 5130"
                ><Input v-model="f.code" required
            /></Field>
            <Field label="নাম" :error="f.errors.name"
                ><Input
                    v-model="f.name"
                    required
                    placeholder="যেমন: লবণ ও ভিটামিন"
            /></Field>
            <Field v-if="parentType === 'asset'" label="এটা কী"
                ><NSelect v-model="f.subtype"
                    ><option :value="null">সাধারণ</option>
                    <option value="cash">নগদ</option>
                    <option value="bank">ব্যাংক</option>
                    <option value="wallet">মোবাইল ওয়ালেট</option>
                    <option value="person">কারো কাছে রাখা</option>
                    <option value="fixed_asset">স্থায়ী সম্পদ</option>
                    <option value="land">জমি</option>
                    <option value="inventory">স্টক</option></NSelect
                ></Field
            >
            <Button type="submit" :disabled="f.processing">যোগ করুন</Button>
        </form>

        <div class="grid gap-5 xl:grid-cols-2">
            <section
                v-for="g in byType"
                :key="g.type"
                class="rounded-xl border border-border bg-card p-4"
            >
                <h2 class="font-display mb-2 text-lg">{{ types[g.type] }}</h2>
                <ul class="divide-y divide-border/60">
                    <li
                        v-for="a in g.rows"
                        :key="a.id"
                        class="flex items-center gap-3 py-2.5"
                    >
                        <span
                            class="num w-12 shrink-0 rounded bg-muted px-1.5 py-0.5 text-center text-xs text-muted-foreground"
                            >{{ a.code }}</span
                        >
                        <span
                            :class="[
                                'min-w-0 flex-1 truncate text-sm',
                                !a.is_active &&
                                    'text-muted-foreground line-through',
                            ]"
                            >{{ a.name }}</span
                        >
                        <span
                            v-if="a.used"
                            class="num shrink-0 text-sm text-muted-foreground"
                            >{{ taka(a.balance) }}</span
                        >
                        <Button
                            variant="ghost"
                            size="icon-sm"
                            :title="'নাম বদলান'"
                            aria-label="নাম বদলান"
                            @click="rename(a)"
                        >
                            <Pencil />
                        </Button>
                        <Toggle
                            :model-value="a.is_active"
                            @update:model-value="toggle(a)"
                        />
                    </li>
                </ul>
            </section>
        </div>
    </div>
</template>
