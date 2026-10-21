<script setup lang="ts">
import { Head, useForm } from '@inertiajs/vue3';
import { Plus } from '@lucide/vue';
import { computed, ref } from 'vue';
import Field from '@/components/khamar/Field.vue';
import NSelect from '@/components/khamar/NSelect.vue';
import PageTitle from '@/components/khamar/PageTitle.vue';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { taka } from '@/lib/khamar';

defineOptions({
    layout: { breadcrumbs: [{ title: 'ক্রেতা, বিনিয়োগকারী…', href: '/setup/parties' }] },
});

const props = defineProps<{
    parties: { id: number; type: string; type_label: string; name: string; phone: string | null; address: string | null; notes: string | null; is_active: boolean; we_owe: number; they_owe: number; linked_balance: number | null; linked_label: string | null }[];
    types: Record<string, string>;
}>();

const adding = ref(false);
const f = useForm({ type: 'investor', name: '', phone: '', address: '', notes: '' });
const grouped = computed(() => Object.keys(props.types).map((type) => ({ type, rows: props.parties.filter((p) => p.type === type) })).filter((g) => g.rows.length));
</script>

<template>
    <Head title="ক্রেতা, বিনিয়োগকারী…" />

    <div class="flex flex-1 flex-col p-4 md:p-6">
        <PageTitle title="যাদের সাথে লেনদেন" subtitle="প্রত্যেক বিনিয়োগকারীর মূলধন, প্রত্যেক ঋণদাতার বাকি ঋণ, প্রত্যেক ক্রেতা-সরবরাহকারীর বাকি — আলাদা আলাদা।">
            <template #actions><Button @click="adding = !adding"><Plus /> নতুন যোগ</Button></template>
        </PageTitle>

        <form v-if="adding" class="mb-5 grid gap-3 rounded-xl border border-border bg-card p-4 sm:grid-cols-2 lg:grid-cols-6 lg:items-end" @submit.prevent="f.post('/setup/parties', { onSuccess: () => { adding = false; f.reset(); } })">
            <Field label="ধরন"><NSelect v-model="f.type"><option v-for="(label, key) in types" :key="key" :value="key">{{ label }}</option></NSelect></Field>
            <Field label="নাম" :error="f.errors.name"><Input v-model="f.name" required placeholder="যেমন: Klok Ads, সোনালী ব্যাংক" /></Field>
            <Field label="ফোন"><Input v-model="f.phone" /></Field>
            <Field label="ঠিকানা"><Input v-model="f.address" /></Field>
            <Field label="নোট"><Input v-model="f.notes" /></Field>
            <Button type="submit" :disabled="f.processing">যোগ করুন</Button>
        </form>

        <div class="space-y-5">
            <section v-for="g in grouped" :key="g.type" class="rounded-xl border border-border bg-card p-4">
                <h2 class="mb-2 font-display text-lg">{{ types[g.type] }}</h2>
                <table class="w-full text-sm">
                    <tbody>
                        <tr v-for="p in g.rows" :key="p.id" class="border-b border-border/60">
                            <td class="py-2.5">{{ p.name }}<p class="text-xs text-muted-foreground">{{ [p.phone, p.address].filter(Boolean).join(' · ') }}</p></td>
                            <td class="num py-2.5 text-right">
                                <span v-if="p.linked_label">{{ p.linked_label }}: {{ taka(p.linked_balance) }}</span>
                                <span v-if="p.we_owe" class="ml-3 text-clay">আমাদের দেনা {{ taka(p.we_owe) }}</span>
                                <span v-if="p.they_owe" class="ml-3 text-paddy">তাঁর কাছে পাওনা {{ taka(p.they_owe) }}</span>
                            </td>
                        </tr>
                    </tbody>
                </table>
            </section>
            <p v-if="!parties.length" class="rounded-xl border border-dashed border-border p-8 text-center text-muted-foreground">এখনো কেউ নেই। প্রথমে Klok Ads-কে বিনিয়োগকারী হিসেবে যোগ করুন।</p>
        </div>
    </div>
</template>
