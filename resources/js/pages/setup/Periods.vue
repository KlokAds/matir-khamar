<script setup lang="ts">
import { Head, router, useForm } from '@inertiajs/vue3';
import { LockKeyhole } from '@lucide/vue';
import Field from '@/components/khamar/Field.vue';
import PageTitle from '@/components/khamar/PageTitle.vue';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';

defineOptions({
    layout: { breadcrumbs: [{ title: 'মাস লক', href: '/setup/periods' }] },
});

defineProps<{
    locks: { id: number; month: string; label: string; by: string | null; at: string | null }[];
}>();

const lastMonth = (() => {
    const d = new Date();
    d.setDate(1);
    d.setMonth(d.getMonth() - 1);

    return d.toISOString().slice(0, 7);
})();

const f = useForm({ month: lastMonth });

function unlock(id: number) {
    const reason = window.prompt('কেন লক খুলছেন? (অডিট লগে থাকবে)');

    if (reason) {
        router.delete(`/setup/periods/${id}`, { data: { reason }, preserveScroll: true });
    }
}
</script>

<template>
    <Head title="মাস লক" />

    <div class="flex flex-1 flex-col p-4 md:p-6">
        <PageTitle title="মাসের হিসাব লক" subtitle="মাস শেষে হিসাব মিলিয়ে লক করুন। লক করা মাসে কেউ নতুন এন্ট্রি দিতে পারবে না — পুরনো হিসাব আর বদলায় না।" />

        <form class="mb-6 flex max-w-md items-end gap-3 rounded-xl border border-border bg-card p-4" @submit.prevent="f.post('/setup/periods')">
            <div class="flex-1"><Field label="কোন মাস" :error="f.errors.month"><Input v-model="f.month" type="month" /></Field></div>
            <Button type="submit" :disabled="f.processing"><LockKeyhole /> লক করুন</Button>
        </form>

        <div class="max-w-2xl rounded-xl border border-border bg-card">
            <div v-for="l in locks" :key="l.id" class="flex items-center justify-between border-b border-border/60 px-4 py-3 last:border-0">
                <div>
                    <p class="font-medium">{{ l.label }}</p>
                    <p class="text-xs text-muted-foreground">{{ l.by }} · {{ l.at }}</p>
                </div>
                <Button size="sm" variant="ghost" @click="unlock(l.id)">খুলুন</Button>
            </div>
            <p v-if="!locks.length" class="p-6 text-center text-sm text-muted-foreground">এখনো কোনো মাস লক করা হয়নি।</p>
        </div>
    </div>
</template>
