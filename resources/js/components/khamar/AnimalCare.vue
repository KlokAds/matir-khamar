<script setup lang="ts">
import { Link, router, useForm } from '@inertiajs/vue3';
import { Baby, CalendarHeart, Layers, Scale } from '@lucide/vue';
import { computed, ref } from 'vue';
import Field from '@/components/khamar/Field.vue';
import NSelect from '@/components/khamar/NSelect.vue';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { ask } from '@/lib/dialog';
import { day, today, useCan } from '@/lib/khamar';

/**
 * The care side of one animal's card: its pregnancy and what to do this month, its weights
 * and daily gain, its fattening batch, and the calf guide while it is young.
 */
type Stage = { from: number; to: number; title: string; tasks: string[] };
type BreedingCard = {
    id: number;
    served_on: string;
    estimated: boolean;
    method: string;
    sire: string | null;
    result: 'pending' | 'pregnant' | 'empty';
    result_label: string;
    expected_on: string;
    calved_on: string | null;
    outcome: string | null;
    outcome_label: string | null;
    calf_id: number | null;
    open: boolean;
    day: number;
    months: string;
    days_left: number | null;
    progress: number;
    stage: Stage | null;
};

const props = defineProps<{
    animalId: number;
    sex: string;
    active: boolean;
    batch: {
        id: number;
        label: string;
        days: number;
        target_days: number;
    } | null;
    weights: { id: number; date: string; kg: number; note: string | null }[];
    growth: {
        first_kg: number | null;
        last_kg: number | null;
        last_on: string | null;
        per_day: number | null;
    };
    breedings: BreedingCard[];
    options: {
        methods: Record<string, string>;
        results: Record<string, string>;
        outcomes: Record<string, string>;
    };
    ageDays: number | null;
    calfStage: Stage | null;
}>();

const can = useCan();
const current = computed(() => props.breedings.find((b) => b.open) ?? null);
const past = computed(() => props.breedings.filter((b) => !b.open));
const canBreed = computed(
    () =>
        props.sex === 'female' &&
        props.active &&
        !current.value &&
        can.value['post-daily'],
);

const serving = ref(false);
const service = useForm({
    mode: 'served' as 'served' | 'months',
    served_on: today(),
    months_pregnant: '',
    method: 'ai',
    sire: '',
    by_whom: '',
    notes: '',
});
const saveService = () =>
    service.post(`/animals/${props.animalId}/breedings`, {
        preserveScroll: true,
        onSuccess: () => {
            serving.value = false;
            service.reset();
        },
    });

const calving = ref(false);
const calf = useForm({ calved_on: today(), outcome: 'female', notes: '' });
const saveCalving = () =>
    calf.put(`/breedings/${current.value?.id}`, {
        preserveScroll: true,
        onSuccess: () => (calving.value = false),
    });

async function setResult(result: 'pregnant' | 'empty') {
    if (
        result === 'empty' &&
        !(await ask.confirm({
            title: 'গর্ভ হয়নি লিখবেন?',
            body: 'এই হিসাব বন্ধ হবে; আবার গরম এলে নতুন করে বীজের তারিখ দিন।',
            confirmText: 'হ্যাঁ, হয়নি',
        }))
    ) {
        return;
    }

    router.put(
        `/breedings/${current.value?.id}`,
        { result, checked_on: today() },
        { preserveScroll: true },
    );
}

const weighing = ref(false);
const weight = useForm({ date: today(), kg: '', note: '' });
const saveWeight = () =>
    weight.post(`/animals/${props.animalId}/weights`, {
        preserveScroll: true,
        onSuccess: () => {
            weighing.value = false;
            weight.reset('kg', 'note');
        },
    });

const marks = [
    { day: 90, label: '৩ মাস' },
    { day: 190, label: '৬ মাস' },
    { day: 223, label: 'দুধ বন্ধ' },
    { day: 270, label: '৯ মাস' },
];
</script>

<template>
    <div class="mt-6 grid gap-6 lg:grid-cols-2">
        <!-- গর্ভ -->
        <section
            v-if="sex === 'female'"
            class="min-w-0 rounded-xl border border-border bg-card p-4"
        >
            <div class="mb-3 flex items-center justify-between gap-2">
                <h2 class="font-display flex items-center gap-2 text-lg">
                    <CalendarHeart class="size-5 text-clay" /> গর্ভ ও বাচ্চা
                </h2>
                <Button
                    v-if="canBreed"
                    size="sm"
                    variant="secondary"
                    @click="serving = !serving"
                    >বীজ / গর্ভ লিখুন</Button
                >
            </div>

            <form
                v-if="serving"
                class="mb-4 grid gap-3 rounded-lg bg-muted/50 p-3 sm:grid-cols-2"
                @submit.prevent="saveService"
            >
                <div
                    class="grid grid-cols-2 gap-1 rounded-md border border-input bg-card p-1 text-sm sm:col-span-2"
                >
                    <button
                        v-for="m in [
                            { key: 'served', label: 'বীজের তারিখ জানি' },
                            { key: 'months', label: 'শুধু কত মাস জানি' },
                        ] as const"
                        :key="m.key"
                        type="button"
                        :class="[
                            'rounded py-1.5',
                            service.mode === m.key
                                ? 'bg-primary text-primary-foreground'
                                : 'hover:bg-accent',
                        ]"
                        @click="service.mode = m.key"
                    >
                        {{ m.label }}
                    </button>
                </div>
                <Field
                    v-if="service.mode === 'served'"
                    label="বীজ দেওয়ার তারিখ"
                    :error="service.errors.served_on"
                    ><Input
                        v-model="service.served_on"
                        type="date"
                        :max="today()"
                /></Field>
                <Field
                    v-else
                    label="কত মাস চলছে"
                    :error="service.errors.months_pregnant"
                    hint="যেমন 4 বা 4.5"
                    ><Input
                        v-model="service.months_pregnant"
                        type="number"
                        step="0.5"
                        min="0.5"
                        max="9.5"
                        class="num"
                /></Field>
                <Field label="কীভাবে">
                    <NSelect v-model="service.method">
                        <option
                            v-for="(label, key) in options.methods"
                            :key="key"
                            :value="key"
                        >
                            {{ label }}
                        </option>
                    </NSelect>
                </Field>
                <Field label="ষাঁড় / সিমেনের জাত (ঐচ্ছিক)"
                    ><Input
                        v-model="service.sire"
                        placeholder="যেমন: ফ্রিজিয়ান ৭৫%"
                /></Field>
                <Field label="কে দিলেন (ঐচ্ছিক)"
                    ><Input
                        v-model="service.by_whom"
                        placeholder="যেমন: উপজেলা প্রাণিসম্পদ"
                /></Field>
                <div class="flex gap-2 sm:col-span-2">
                    <Button type="submit" :disabled="service.processing"
                        >লিখুন</Button
                    >
                    <Button
                        type="button"
                        variant="ghost"
                        @click="serving = false"
                        >বাদ</Button
                    >
                </div>
            </form>

            <div v-if="current" class="space-y-3">
                <div class="flex flex-wrap items-baseline gap-x-3 gap-y-1">
                    <span
                        :class="[
                            'rounded-full px-2 py-0.5 text-xs font-medium',
                            current.result === 'pregnant'
                                ? 'bg-paddy/15 text-paddy'
                                : 'bg-turmeric/20 text-turmeric',
                        ]"
                        >{{ current.result_label }}</span
                    >
                    <span class="text-lg font-semibold"
                        >{{ current.months }} চলছে</span
                    >
                    <span
                        v-if="current.estimated"
                        class="text-xs text-muted-foreground"
                        >(আনুমানিক)</span
                    >
                </div>
                <div class="relative pt-1 pb-5">
                    <div class="h-2.5 overflow-hidden rounded-full bg-muted">
                        <div
                            class="h-full rounded-full bg-paddy"
                            :style="{ width: `${current.progress}%` }"
                        />
                    </div>
                    <span
                        v-for="m in marks"
                        :key="m.day"
                        class="absolute top-4 -translate-x-1/2 text-[10px] whitespace-nowrap text-muted-foreground"
                        :style="{ left: `${(m.day / 283) * 100}%` }"
                        >{{ m.label }}</span
                    >
                </div>
                <p class="text-sm">
                    বাচ্চা হওয়ার আনুমানিক দিন
                    <b>{{ day(current.expected_on) }}</b>
                    <span
                        v-if="current.days_left !== null"
                        :class="
                            current.days_left < 0
                                ? 'text-destructive'
                                : 'text-muted-foreground'
                        "
                    >
                        ·
                        {{
                            current.days_left >= 0
                                ? `আর ${current.days_left} দিন`
                                : `${Math.abs(current.days_left)} দিন পার`
                        }}</span
                    >
                </p>
                <p class="text-xs text-muted-foreground">
                    বীজ {{ day(current.served_on) }} · {{ current.method
                    }}{{ current.sire ? ` · ${current.sire}` : '' }}
                </p>

                <div
                    v-if="current.stage"
                    class="rounded-lg border border-paddy/30 bg-paddy/8 p-3"
                >
                    <p class="mb-1.5 text-sm font-semibold">
                        এখন করণীয় — {{ current.stage.title }}
                    </p>
                    <ul class="space-y-1 text-sm">
                        <li
                            v-for="task in current.stage.tasks"
                            :key="task"
                            class="flex gap-2"
                        >
                            <span
                                class="mt-2 size-1.5 shrink-0 rounded-full bg-paddy"
                            />{{ task }}
                        </li>
                    </ul>
                </div>

                <div
                    v-if="can['post-daily'] && active"
                    class="flex flex-wrap gap-2"
                >
                    <template v-if="current.result === 'pending'">
                        <Button size="sm" @click="setResult('pregnant')"
                            >গর্ভ হয়েছে</Button
                        >
                        <Button
                            size="sm"
                            variant="secondary"
                            @click="setResult('empty')"
                            >গর্ভ হয়নি</Button
                        >
                    </template>
                    <Button
                        size="sm"
                        variant="secondary"
                        @click="calving = !calving"
                        ><Baby /> বাচ্চা হয়েছে</Button
                    >
                </div>

                <form
                    v-if="calving"
                    class="grid gap-3 rounded-lg bg-muted/50 p-3 sm:grid-cols-2"
                    @submit.prevent="saveCalving"
                >
                    <Field label="তারিখ" :error="calf.errors.calved_on"
                        ><Input
                            v-model="calf.calved_on"
                            type="date"
                            :max="today()"
                    /></Field>
                    <Field label="কী হলো" :error="calf.errors.outcome">
                        <NSelect v-model="calf.outcome">
                            <option
                                v-for="(label, key) in options.outcomes"
                                :key="key"
                                :value="key"
                            >
                                {{ label }}
                            </option>
                        </NSelect>
                    </Field>
                    <Field label="নোট" class="sm:col-span-2"
                        ><Input
                            v-model="calf.notes"
                            placeholder="যেমন: রাত ২টায়, ডাক্তার লাগেনি"
                    /></Field>
                    <div class="flex gap-2 sm:col-span-2">
                        <Button type="submit" :disabled="calf.processing"
                            >লিখুন</Button
                        >
                        <Button
                            type="button"
                            variant="ghost"
                            @click="calving = false"
                            >বাদ</Button
                        >
                    </div>
                </form>
            </div>
            <p v-else-if="!serving" class="text-sm text-muted-foreground">
                এখন কোনো গর্ভের হিসাব খোলা নেই। গরম এলে ১২–১৮ ঘণ্টার মধ্যে বীজ
                দিন, তারিখটা এখানে লিখুন।
            </p>

            <ul
                v-if="past.length"
                class="mt-4 divide-y divide-border/60 border-t border-border/60 text-sm"
            >
                <li
                    v-for="b in past"
                    :key="b.id"
                    class="flex flex-wrap justify-between gap-2 py-2"
                >
                    <span
                        >বীজ {{ day(b.served_on) }} ·
                        {{
                            b.calved_on
                                ? `বাচ্চা ${day(b.calved_on)}`
                                : b.result_label
                        }}</span
                    >
                    <span class="text-muted-foreground">
                        <template v-if="b.outcome_label">{{
                            b.outcome_label
                        }}</template>
                        <Link
                            v-if="b.calf_id"
                            :href="`/animals/${b.calf_id}`"
                            class="ml-1 underline"
                            >বাছুর</Link
                        >
                        <Link
                            v-else-if="
                                b.calved_on &&
                                b.outcome &&
                                ['female', 'male', 'twins'].includes(
                                    b.outcome,
                                ) &&
                                can['post-entries']
                            "
                            :href="`/animals/create?mother=${animalId}`"
                            class="ml-1 underline"
                            >বাছুর তালিকায় যোগ করুন</Link
                        >
                    </span>
                </li>
            </ul>
        </section>

        <!-- ওজন ও ব্যাচ -->
        <section class="min-w-0 rounded-xl border border-border bg-card p-4">
            <div class="mb-3 flex items-center justify-between gap-2">
                <h2 class="font-display flex items-center gap-2 text-lg">
                    <Scale class="size-5 text-pond" /> ওজন ও বাড়া
                </h2>
                <Button
                    v-if="can['post-daily'] && active"
                    size="sm"
                    variant="secondary"
                    @click="weighing = !weighing"
                    >ওজন লিখুন</Button
                >
            </div>

            <Link
                v-if="batch"
                href="/batches"
                class="mb-3 flex items-center gap-2 rounded-lg bg-turmeric/12 p-3 text-sm hover:bg-turmeric/20"
            >
                <Layers class="size-4 text-turmeric" />
                <span
                    ><b>{{ batch.label }}</b> · {{ batch.days }} দিন চলছে
                    (লক্ষ্য {{ batch.target_days }} দিন)</span
                >
            </Link>

            <form
                v-if="weighing"
                class="mb-4 grid gap-3 rounded-lg bg-muted/50 p-3 sm:grid-cols-[1fr_1fr_auto] sm:items-end"
                @submit.prevent="saveWeight"
            >
                <Field label="তারিখ" :error="weight.errors.date"
                    ><Input v-model="weight.date" type="date" :max="today()"
                /></Field>
                <Field label="ওজন (কেজি)" :error="weight.errors.kg"
                    ><Input
                        v-model="weight.kg"
                        type="number"
                        step="0.1"
                        min="0"
                        class="num"
                        required
                /></Field>
                <Button type="submit" :disabled="weight.processing"
                    >লিখুন</Button
                >
            </form>

            <div v-if="weights.length" class="space-y-3">
                <div class="grid grid-cols-3 gap-2 text-center">
                    <div class="rounded-lg bg-muted/60 p-2">
                        <p class="text-xs text-muted-foreground">শুরু</p>
                        <p class="num font-semibold">
                            {{ growth.first_kg }} কেজি
                        </p>
                    </div>
                    <div class="rounded-lg bg-muted/60 p-2">
                        <p class="text-xs text-muted-foreground">এখন</p>
                        <p class="num font-semibold">
                            {{ growth.last_kg }} কেজি
                        </p>
                    </div>
                    <div class="rounded-lg bg-paddy/12 p-2">
                        <p class="text-xs text-muted-foreground">দিনে বাড়া</p>
                        <p class="num font-semibold">
                            {{
                                growth.per_day !== null
                                    ? `${growth.per_day} কেজি`
                                    : '—'
                            }}
                        </p>
                    </div>
                </div>
                <ul class="divide-y divide-border/60 text-sm">
                    <li
                        v-for="w in [...weights].reverse()"
                        :key="w.id"
                        class="flex justify-between py-1.5"
                    >
                        <span>{{ day(w.date) }}</span>
                        <span class="num"
                            >{{ w.kg }} কেজি
                            <small v-if="w.note" class="text-muted-foreground"
                                >· {{ w.note }}</small
                            ></span
                        >
                    </li>
                </ul>
            </div>
            <p v-else-if="!weighing" class="text-sm text-muted-foreground">
                এখনো ওজন লেখা হয়নি। মাপার ফিতা দিয়েও আনুমানিক ওজন লেখা যায় —
                মাসে একবার লিখলেই বাড়ার হিসাব দেখা যাবে।
            </p>

            <div
                v-if="calfStage && ageDays !== null"
                class="mt-4 rounded-lg border border-pond/30 bg-pond/8 p-3"
            >
                <p class="mb-1.5 text-sm font-semibold">
                    বাছুরের যত্ন — বয়স {{ ageDays }} দিন ({{
                        calfStage.title
                    }})
                </p>
                <ul class="space-y-1 text-sm">
                    <li
                        v-for="task in calfStage.tasks"
                        :key="task"
                        class="flex gap-2"
                    >
                        <span
                            class="mt-2 size-1.5 shrink-0 rounded-full bg-pond"
                        />{{ task }}
                    </li>
                </ul>
            </div>
        </section>
    </div>
    <p class="mt-2 text-xs text-muted-foreground">
        ঔষধ, মাত্রা ও টিকার তারিখ অবশ্যই প্রাণিসম্পদ ডাক্তারের পরামর্শে।
    </p>
</template>
