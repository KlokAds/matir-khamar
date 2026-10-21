<script setup lang="ts">
import { ask } from '@/lib/dialog';
import { t } from '@/lib/i18n';
import { Head, router, useForm } from '@inertiajs/vue3';
import { LockKeyhole } from '@lucide/vue';
import Field from '@/components/khamar/Field.vue';
import PageTitle from '@/components/khamar/PageTitle.vue';
import { Button } from '@/components/ui/button';
import DateField from '@/components/khamar/DateField.vue';
import { Input } from '@/components/ui/input';

defineOptions({
    layout: { breadcrumbs: [{ title: 'মাস লক', href: '/setup/periods' }] },
});

defineProps<{
    locks: {
        id: number;
        month: string;
        label: string;
        by: string | null;
        at: string | null;
    }[];
}>();

const lastMonth = (() => {
    const d = new Date();
    d.setDate(1);
    d.setMonth(d.getMonth() - 1);

    return d.toISOString().slice(0, 7);
})();

const f = useForm({ month: lastMonth });

async function unlock(id: number) {
    const reason = await ask.prompt({
        title: t('মাসের লক খুলবেন?'),
        body: t('কারণটি অডিট লগে লেখা থাকবে।'),
        label: t('কেন খুলছেন'),
        minLength: 5,
        confirmText: t('লক খুলুন'),
        danger: true,
    });

    if (reason) {
        router.delete(`/setup/periods/${id}`, {
            data: { reason },
            preserveScroll: true,
        });
    }
}
</script>

<template>
    <Head :title="t('মাস লক')" />

    <div class="flex flex-1 flex-col p-4 md:p-6">
        <PageTitle
            :title="t('মাসের হিসাব লক')"
            :subtitle="
                t(
                    'মাস শেষে হিসাব মিলিয়ে লক করুন। লক করা মাসে কেউ নতুন এন্ট্রি দিতে পারবে না — পুরনো হিসাব আর বদলায় না।',
                )
            "
        />

        <form
            class="mb-6 flex max-w-md items-end gap-3 rounded-xl border border-border bg-card p-4"
            @submit.prevent="f.post('/setup/periods')"
        >
            <div class="flex-1">
                <Field :label="t('কোন মাস')" :error="f.errors.month"
                    ><DateField mode="month" v-model="f.month"
                /></Field>
            </div>
            <Button type="submit" :disabled="f.processing"
                ><LockKeyhole /> {{ t('লক করুন') }}</Button
            >
        </form>

        <div class="max-w-2xl rounded-xl border border-border bg-card">
            <div
                v-for="l in locks"
                :key="l.id"
                class="flex items-center justify-between border-b border-border/60 px-4 py-3 last:border-0"
            >
                <div>
                    <p class="font-medium">{{ l.label }}</p>
                    <p class="text-xs text-muted-foreground">
                        {{ l.by }} · {{ l.at }}
                    </p>
                </div>
                <Button size="sm" variant="ghost" @click="unlock(l.id)">{{
                    t('লক খুলুন')
                }}</Button>
            </div>
            <p
                v-if="!locks.length"
                class="p-6 text-center text-sm text-muted-foreground"
            >
                {{ t('এখনো কোনো মাস লক করা হয়নি।') }}
            </p>
        </div>
    </div>
</template>
