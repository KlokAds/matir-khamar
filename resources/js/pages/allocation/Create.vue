<script setup lang="ts">
import { Head, Link, router, useForm } from '@inertiajs/vue3';
import { computed, reactive, watch } from 'vue';
import Field from '@/components/khamar/Field.vue';
import NSelect from '@/components/khamar/NSelect.vue';
import PageTitle from '@/components/khamar/PageTitle.vue';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { centerLabel, taka } from '@/lib/khamar';
import type { Center } from '@/lib/khamar';

defineOptions({
    layout: { breadcrumbs: [{ title: 'খরচ ভাগ', href: '/allocation' }] },
});

const props = defineProps<{
    sourceId: number | null;
    month: string;
    sources: { id: number; name: string }[];
    centers: Center[];
    pool: { account: string; amount: number }[];
    animalDays: Record<string, number>;
    done: { id: number; number: string; description: string }[];
}>();

const pick = reactive({ source: props.sourceId, month: props.month });
watch(pick, () =>
    router.get(
        '/allocation',
        { source: pick.source, month: pick.month },
        { preserveState: false },
    ),
);

const hasAnimalDays = Object.keys(props.animalDays).length > 0;
const f = useForm({
    source_id: props.sourceId,
    month: props.month,
    method: hasAnimalDays ? 'animal_days' : 'percent',
    weights: {} as Record<string, string>,
});

function fillWeights() {
    f.weights = Object.fromEntries(
        props.centers.map((c) => [
            String(c.id),
            f.method === 'animal_days'
                ? String(props.animalDays[c.id] ?? '')
                : '',
        ]),
    );
}

fillWeights();
watch(() => f.method, fillWeights);

const poolTotal = computed(() =>
    props.pool.reduce((sum, row) => sum + row.amount, 0),
);
const weightTotal = computed(() =>
    Object.values(f.weights).reduce((sum, w) => sum + (parseFloat(w) || 0), 0),
);
const share = (id: number) =>
    weightTotal.value > 0
        ? Math.round(
              (poolTotal.value * (parseFloat(f.weights[String(id)]) || 0)) /
                  weightTotal.value,
          )
        : 0;
</script>

<template>
    <Head title="খরচ ভাগ" />

    <div class="flex flex-1 flex-col p-4 md:p-6">
        <PageTitle
            title="মাস শেষে সাধারণ খরচ ভাগ"
            subtitle="বিদ্যুৎ, সবার জন্য রাখা শ্রমিক — যা কোনো এক ক্যাটাগরির নয়, তা এখান থেকে সঠিক অনুপাতে ভাগ হয়ে যায়।"
        />

        <div class="mb-5 grid max-w-xl grid-cols-2 gap-3">
            <Field label="কোন সাধারণ খাত">
                <NSelect v-model="pick.source"
                    ><option v-for="s in sources" :key="s.id" :value="s.id">
                        {{ s.name }}
                    </option></NSelect
                >
            </Field>
            <Field label="মাস"
                ><Input v-model="pick.month" type="month"
            /></Field>
        </div>

        <div
            v-if="done.length"
            class="mb-5 rounded-xl border border-paddy/40 bg-paddy/8 p-3 text-sm"
        >
            এই মাস ইতিমধ্যে ভাগ করা হয়েছে:
            <Link
                v-for="d in done"
                :key="d.id"
                :href="`/entries/${d.id}`"
                class="ml-1 font-medium underline"
                >{{ d.number }}</Link
            >. পরে আরও সাধারণ খরচ এলে আবার ভাগ করলে শুধু নতুনটুকুই ভাগ হবে।
        </div>

        <div class="grid gap-6 lg:grid-cols-5">
            <section
                class="min-w-0 rounded-xl border border-border bg-card p-4 lg:col-span-2"
            >
                <h2 class="font-display mb-3 text-lg">ভাগ করার মতো খরচ</h2>
                <table class="w-full text-sm">
                    <tbody>
                        <tr
                            v-for="row in pool"
                            :key="row.account"
                            class="border-b border-border/60"
                        >
                            <td class="py-2">{{ row.account }}</td>
                            <td class="num py-2 text-right">
                                {{ taka(row.amount) }}
                            </td>
                        </tr>
                        <tr v-if="!pool.length">
                            <td class="py-6 text-center text-muted-foreground">
                                এই মাসে ভাগ করার কিছু নেই।
                            </td>
                        </tr>
                    </tbody>
                    <tfoot>
                        <tr class="font-semibold">
                            <td class="pt-2">মোট</td>
                            <td class="num pt-2 text-right">
                                {{ taka(poolTotal) }}
                            </td>
                        </tr>
                    </tfoot>
                </table>
            </section>

            <form
                class="rounded-xl border border-border bg-card p-4 lg:col-span-3"
                @submit.prevent="f.post('/allocation')"
            >
                <div
                    class="mb-4 grid grid-cols-2 gap-1 rounded-md border border-input p-1 text-sm"
                >
                    <button
                        type="button"
                        :class="[
                            'rounded py-1.5',
                            f.method === 'animal_days'
                                ? 'bg-primary text-primary-foreground'
                                : 'hover:bg-accent',
                        ]"
                        @click="f.method = 'animal_days'"
                    >
                        পশু-দিন অনুযায়ী
                    </button>
                    <button
                        type="button"
                        :class="[
                            'rounded py-1.5',
                            f.method === 'percent'
                                ? 'bg-primary text-primary-foreground'
                                : 'hover:bg-accent',
                        ]"
                        @click="f.method = 'percent'"
                    >
                        নিজে অংশ দিন (%)
                    </button>
                </div>
                <p class="mb-3 text-xs text-muted-foreground">
                    {{
                        f.method === 'animal_days'
                            ? 'যে ক্যাটাগরিতে যত গরু যতদিন ছিল, সে তত বেশি ভাগ পাবে। সংখ্যাগুলো চাইলে বদলাতে পারেন।'
                            : 'যেমন বাগান ২০, গরু ৮০ — মোট যত হোক, অনুপাতে ভাগ হবে।'
                    }}
                </p>
                <p
                    v-if="Object.keys(f.errors).length"
                    class="mb-3 text-sm text-destructive"
                >
                    {{ Object.values(f.errors)[0] }}
                </p>
                <div class="space-y-2">
                    <div
                        v-for="c in centers"
                        :key="c.id"
                        class="grid grid-cols-[1fr_7rem_8rem] items-center gap-2 text-sm"
                    >
                        <span>{{ centerLabel(c) }}</span>
                        <Input
                            v-model="f.weights[String(c.id)]"
                            type="number"
                            min="0"
                            step="0.01"
                            class="num text-right"
                        />
                        <span class="num text-right text-muted-foreground">{{
                            taka(share(c.id))
                        }}</span>
                    </div>
                </div>
                <Button
                    type="submit"
                    class="mt-5 w-full"
                    :disabled="
                        f.processing || poolTotal === 0 || weightTotal === 0
                    "
                    >ভাগ করে খাতায় লিখুন</Button
                >
            </form>
        </div>
    </div>
</template>
