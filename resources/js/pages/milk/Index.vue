<script setup lang="ts">
import { Head, useForm } from '@inertiajs/vue3';
import { computed } from 'vue';
import Field from '@/components/khamar/Field.vue';
import SessionPicker from '@/components/khamar/SessionPicker.vue';
import PageTitle from '@/components/khamar/PageTitle.vue';
import { Button } from '@/components/ui/button';
import DateField from '@/components/khamar/DateField.vue';
import { Input } from '@/components/ui/input';
import { currentSession, day, qty, today } from '@/lib/khamar';

defineOptions({
    layout: { breadcrumbs: [{ title: 'দুধের হিসাব', href: '/milk' }] },
});

const props = defineProps<{
    sessions: Record<string, string>;
    cows: { id: number; tag: string; name: string | null }[];
    days: { date: string; by: Record<string, number>; total: number }[];
    month: number;
}>();

const f = useForm({
    date: today(),
    session: currentSession(),
    rows: props.cows.length
        ? props.cows.map((cow) => ({
              animal_id: cow.id as number | null,
              liters: '',
          }))
        : [{ animal_id: null, liters: '' }],
});

const total = computed(() =>
    f.rows.reduce((sum, row) => sum + (parseFloat(row.liters) || 0), 0),
);
const cowLabel = (id: number | null) => {
    const cow = props.cows.find((c) => c.id === id);

    return cow
        ? `${cow.tag}${cow.name ? ' · ' + cow.name : ''}`
        : 'সব গাভী মিলিয়ে';
};
</script>

<template>
    <Head title="দুধের হিসাব" />

    <div class="flex flex-1 flex-col p-4 md:p-6">
        <PageTitle
            title="দুধের হিসাব"
            :subtitle="`এ মাসে মোট ${qty(month, 'লিটার')}। বিক্রি হলে 'নতুন এন্ট্রি → আয়' দিন।`"
        />

        <div class="grid gap-6 lg:grid-cols-5">
            <form
                class="rounded-xl border border-border bg-card p-4 lg:col-span-2"
                @submit.prevent="
                    f.post('/milk', {
                        preserveScroll: true,
                        onSuccess: () => f.rows.forEach((r) => (r.liters = '')),
                    })
                "
            >
                <div class="grid gap-3">
                    <Field label="তারিখ" :error="f.errors.date"
                        ><DateField v-model="f.date" :max="today()"
                    /></Field>
                    <SessionPicker v-model="f.session" :sessions="sessions" />
                </div>

                <div class="mt-4 space-y-2">
                    <div
                        v-for="(row, index) in f.rows"
                        :key="index"
                        class="flex items-center gap-3 rounded-lg border border-border/70 bg-background/60 px-3 py-2"
                    >
                        <span class="min-w-0 flex-1 truncate text-sm">{{
                            cowLabel(row.animal_id)
                        }}</span>
                        <div class="relative w-32">
                            <Input
                                v-model="row.liters"
                                type="number"
                                inputmode="decimal"
                                step="0.1"
                                min="0"
                                max="80"
                                class="num h-12 pr-12 text-right text-lg"
                                placeholder="0"
                            />
                            <span
                                class="pointer-events-none absolute top-3 right-3 text-xs text-muted-foreground"
                                >লি.</span
                            >
                        </div>
                    </div>
                </div>
                <p v-if="f.errors.rows" class="mt-2 text-sm text-destructive">
                    {{ f.errors.rows }}
                </p>

                <div
                    class="mt-4 flex items-center justify-between rounded-lg bg-pond/10 px-4 py-3"
                >
                    <span class="text-sm">মোট</span>
                    <span class="num font-display text-2xl">{{
                        qty(total, 'লিটার')
                    }}</span>
                </div>
                <Button
                    type="submit"
                    class="mt-4 h-11 w-full text-base"
                    :disabled="f.processing || total <= 0"
                    >রাখুন</Button
                >
            </form>

            <section
                class="min-w-0 rounded-xl border border-border bg-card p-4 lg:col-span-3"
            >
                <h2 class="font-display mb-3 text-lg">গত ৩০ দিন</h2>
                <table class="w-full text-sm">
                    <thead class="text-left text-xs text-muted-foreground">
                        <tr class="border-b border-border">
                            <th class="pb-2 font-medium">তারিখ</th>
                            <th
                                v-for="(label, key) in sessions"
                                :key="key"
                                class="pb-2 text-right font-medium"
                            >
                                {{ label }}
                            </th>
                            <th class="pb-2 text-right font-medium">মোট</th>
                        </tr>
                    </thead>
                    <tbody>
                        <tr
                            v-for="d in days"
                            :key="d.date"
                            class="border-b border-border/60"
                        >
                            <td class="py-2">{{ day(d.date) }}</td>
                            <td
                                v-for="(label, key) in sessions"
                                :key="key"
                                :class="[
                                    'num py-2 text-right',
                                    !d.by[key] && 'text-muted-foreground/50',
                                ]"
                            >
                                {{ d.by[key] ? qty(d.by[key]) : '—' }}
                            </td>
                            <td class="num py-2 text-right font-medium">
                                {{ qty(d.total) }}
                            </td>
                        </tr>
                        <tr v-if="!days.length">
                            <td
                                :colspan="Object.keys(sessions).length + 2"
                                class="py-8 text-center text-muted-foreground"
                            >
                                এখনো দুধের হিসাব নেই।
                            </td>
                        </tr>
                    </tbody>
                </table>
            </section>
        </div>
    </div>
</template>
