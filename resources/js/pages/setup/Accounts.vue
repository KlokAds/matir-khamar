<script setup lang="ts">
import { Head, router, useForm } from '@inertiajs/vue3';
import { Plus } from '@lucide/vue';
import { computed, ref } from 'vue';
import Field from '@/components/khamar/Field.vue';
import NSelect from '@/components/khamar/NSelect.vue';
import PageTitle from '@/components/khamar/PageTitle.vue';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { taka } from '@/lib/khamar';

defineOptions({
    layout: { breadcrumbs: [{ title: 'হিসাব খাত', href: '/setup/accounts' }] },
});

const props = defineProps<{
    accounts: { id: number; code: string; name: string; type: string; subtype: string | null; parent_id: number | null; is_system: boolean; is_active: boolean; balance: number; used: boolean }[];
    types: Record<string, string>;
    parents: { id: number; code: string; name: string; type: string }[];
}>();

const adding = ref(false);
const f = useForm({ parent_id: props.parents[0]?.id ?? null, code: '', name: '', subtype: null as string | null });
const byType = computed(() => Object.keys(props.types).map((type) => ({ type, rows: props.accounts.filter((a) => a.type === type && a.parent_id) })));
const parentType = computed(() => props.parents.find((p) => p.id === f.parent_id)?.type);

const rename = (a: { id: number; name: string; is_active: boolean }) => {
    const name = window.prompt('নতুন নাম', a.name);

    if (name) {
        router.put(`/setup/accounts/${a.id}`, { name, is_active: a.is_active }, { preserveScroll: true });
    }
};
const toggle = (a: { id: number; name: string; is_active: boolean }) => router.put(`/setup/accounts/${a.id}`, { name: a.name, is_active: !a.is_active }, { preserveScroll: true });
</script>

<template>
    <Head title="হিসাব খাত" />

    <div class="flex flex-1 flex-col p-4 md:p-6">
        <PageTitle title="হিসাব খাত (চার্ট অব অ্যাকাউন্টস)" subtitle="নতুন খরচ বা আয়ের ধরন এলে এখানে খাত যোগ করুন। ব্যবহৃত খাত মোছা যায় না, বন্ধ করা যায়।">
            <template #actions><Button @click="adding = !adding"><Plus /> নতুন খাত</Button></template>
        </PageTitle>

        <form v-if="adding" class="mb-5 grid gap-3 rounded-xl border border-border bg-card p-4 sm:grid-cols-2 lg:grid-cols-5 lg:items-end" @submit.prevent="f.post('/setup/accounts', { onSuccess: () => { adding = false; f.reset(); } })">
            <Field label="কোন ধরনের ভেতরে"><NSelect v-model="f.parent_id"><option v-for="p in parents" :key="p.id" :value="p.id">{{ p.code }} · {{ p.name }}</option></NSelect></Field>
            <Field label="কোড" :error="f.errors.code" hint="যেমন 5130"><Input v-model="f.code" required /></Field>
            <Field label="নাম" :error="f.errors.name"><Input v-model="f.name" required placeholder="যেমন: লবণ ও ভিটামিন" /></Field>
            <Field v-if="parentType === 'asset'" label="এটা কী"><NSelect v-model="f.subtype"><option :value="null">সাধারণ</option><option value="cash">নগদ</option><option value="bank">ব্যাংক</option><option value="wallet">মোবাইল ওয়ালেট</option><option value="fixed_asset">স্থায়ী সম্পদ</option><option value="land">জমি</option><option value="inventory">স্টক</option></NSelect></Field>
            <Button type="submit" :disabled="f.processing">যোগ করুন</Button>
        </form>

        <div class="grid gap-5 xl:grid-cols-2">
            <section v-for="g in byType" :key="g.type" class="rounded-xl border border-border bg-card p-4">
                <h2 class="mb-2 font-display text-lg">{{ types[g.type] }}</h2>
                <table class="w-full text-sm">
                    <tbody>
                        <tr v-for="a in g.rows" :key="a.id" :class="['border-b border-border/60', !a.is_active && 'opacity-50']">
                            <td class="num w-16 py-2 text-muted-foreground">{{ a.code }}</td>
                            <td class="py-2">{{ a.name }}</td>
                            <td class="num py-2 text-right">{{ a.used ? taka(a.balance) : '' }}</td>
                            <td class="py-2 pl-2 text-right whitespace-nowrap">
                                <button class="text-xs text-primary hover:underline" @click="rename(a)">নাম</button>
                                <button class="ml-2 text-xs text-muted-foreground hover:underline" @click="toggle(a)">{{ a.is_active ? 'বন্ধ' : 'চালু' }}</button>
                            </td>
                        </tr>
                    </tbody>
                </table>
            </section>
        </div>
    </div>
</template>
