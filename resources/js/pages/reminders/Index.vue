<script setup lang="ts">
import { Head, Link, router, useForm } from '@inertiajs/vue3';
import {
    AlarmClock,
    BellOff,
    BellRing,
    Check,
    Package,
    Repeat,
    Send,
    Smartphone,
} from '@lucide/vue';
import { computed, onMounted, ref } from 'vue';
import Field from '@/components/khamar/Field.vue';
import NSelect from '@/components/khamar/NSelect.vue';
import PageTitle from '@/components/khamar/PageTitle.vue';
import Toggle from '@/components/khamar/Toggle.vue';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { centerLabel, useCan } from '@/lib/khamar';
import type { Center } from '@/lib/khamar';
import { disablePush, enablePush, pushState } from '@/lib/push';
import type { PushState } from '@/lib/push';

defineOptions({
    layout: {
        breadcrumbs: [{ title: 'মনে করিয়ে দিন', href: '/reminders' }],
    },
});

type Row = {
    id: number;
    title: string;
    body: string | null;
    due_at: string;
    repeat: string;
    for: string | null;
    by: string | null;
    center: string | null;
    animal: string | null;
    animal_id: number | null;
    done_at: string | null;
    overdue: boolean;
};

const props = defineProps<{
    open: Row[];
    done: Row[];
    repeats: Record<string, string>;
    people: { id: number; name: string }[];
    centers: Center[];
    animals: { id: number; tag: string; name: string | null }[];
    vapidKey: string | null;
    devices: number;
    prefs: { reminders: boolean; morning: boolean; evening: boolean };
    stock: {
        id: number;
        name: string;
        unit: string;
        qty_on_hand: number;
        per_day: number | null;
        days_left: number | null;
        low: boolean;
    }[];
}>();

const can = useCan();

/* ---------- this phone's notifications ---------- */
const state = ref<PushState>('off');
const busy = ref(false);

onMounted(async () => {
    state.value = await pushState();
});

async function turnOn() {
    if (!props.vapidKey) {
        return;
    }

    busy.value = true;

    try {
        state.value = await enablePush(props.vapidKey);

        if (state.value === 'on') {
            router.reload({ only: ['devices'] });
        }
    } finally {
        busy.value = false;
    }
}

async function turnOff() {
    busy.value = true;
    state.value = await disablePush();
    busy.value = false;
    router.reload({ only: ['devices'] });
}

const sendTest = () => router.post('/push/test', {}, { preserveScroll: true });

const prefs = ref({ ...props.prefs });
const savePrefs = () =>
    router.put('/notify-prefs', prefs.value, { preserveScroll: true });

/* ---------- new reminder ---------- */
const pad = (n: number) => String(n).padStart(2, '0');
const local = (d: Date) =>
    `${d.getFullYear()}-${pad(d.getMonth() + 1)}-${pad(d.getDate())}T${pad(d.getHours())}:${pad(d.getMinutes())}`;

const presets = [
    {
        key: '1h',
        label: '১ ঘণ্টা পরে',
        at: () => new Date(Date.now() + 3600_000),
    },
    {
        key: '3h',
        label: '৩ ঘণ্টা পরে',
        at: () => new Date(Date.now() + 3 * 3600_000),
    },
    {
        key: 'evening',
        label: 'আজ সন্ধ্যা ৬টা',
        at: () => {
            const d = new Date();
            d.setHours(18, 0, 0, 0);

            if (d.getTime() < Date.now()) {
                d.setDate(d.getDate() + 1);
            }

            return d;
        },
    },
    {
        key: 'morning',
        label: 'কাল সকাল ৭টা',
        at: () => {
            const d = new Date();
            d.setDate(d.getDate() + 1);
            d.setHours(7, 0, 0, 0);

            return d;
        },
    },
    { key: 'custom', label: 'নিজে সময় দিন', at: () => new Date() },
] as const;

const preset = ref<(typeof presets)[number]['key']>('1h');
const f = useForm({
    title: '',
    body: '',
    due_at: local(presets[0].at()),
    repeat: 'none',
    for_user_id: null as number | null,
    cost_center_id: null as number | null,
    animal_id: null as number | null,
});

function pick(key: (typeof presets)[number]['key']) {
    preset.value = key;

    if (key !== 'custom') {
        f.due_at = local(presets.find((p) => p.key === key)!.at());
    }
}

const more = ref(false);

function save() {
    if (preset.value !== 'custom') {
        f.due_at = local(presets.find((p) => p.key === preset.value)!.at());
    }

    f.post('/reminders', {
        preserveScroll: true,
        onSuccess: () => {
            f.reset('title', 'body', 'cost_center_id', 'animal_id');
            pick('1h');
        },
    });
}

function remindToBuy(item: { name: string }) {
    f.title = `${item.name} কিনতে হবে`;
    pick('morning');
    window.scrollTo({ top: 0, behavior: 'smooth' });
}

/* ---------- the list ---------- */
const act = (row: Row, action: 'done' | 'undo' | 'snooze', minutes = 60) =>
    router.put(
        `/reminders/${row.id}`,
        { action, minutes },
        { preserveScroll: true },
    );

const minutesUntilMorning = () => {
    const d = new Date();
    d.setDate(d.getDate() + 1);
    d.setHours(7, 0, 0, 0);

    return Math.max(5, Math.round((d.getTime() - Date.now()) / 60000));
};

const todayKey = local(new Date()).slice(0, 10);
const groups = computed(() => [
    {
        key: 'late',
        title: 'সময় পার হয়ে গেছে',
        rows: props.open.filter((r) => r.overdue),
    },
    {
        key: 'today',
        title: 'আজ',
        rows: props.open.filter(
            (r) => !r.overdue && r.due_at.slice(0, 10) === todayKey,
        ),
    },
    {
        key: 'later',
        title: 'সামনে',
        rows: props.open.filter(
            (r) => !r.overdue && r.due_at.slice(0, 10) > todayKey,
        ),
    },
]);

function when(value: string): string {
    const d = new Date(value);
    const time = d.toLocaleTimeString('bn-BD', {
        hour: 'numeric',
        minute: '2-digit',
    });
    const dayDiff = Math.round(
        (new Date(value.slice(0, 10)).getTime() -
            new Date(todayKey).getTime()) /
            86400_000,
    );

    if (dayDiff === 0) {
        return `আজ ${time}`;
    }

    if (dayDiff === 1) {
        return `কাল ${time}`;
    }

    if (dayDiff === -1) {
        return `গতকাল ${time}`;
    }

    return `${d.toLocaleDateString('bn-BD', { day: 'numeric', month: 'short' })} ${time}`;
}

const lowStock = computed(() => props.stock.filter((s) => s.low));
const trackedStock = computed(() =>
    props.stock.filter((s) => s.days_left !== null),
);
</script>

<template>
    <Head title="মনে করিয়ে দিন" />

    <div class="flex flex-1 flex-col p-4 md:p-6">
        <PageTitle
            title="মনে করিয়ে দিন"
            subtitle="মাঠে দাঁড়িয়ে মনে পড়লো? লিখে সময় দিন — ঠিক সময়ে ফোনে নোটিফিকেশন যাবে।"
        />

        <div class="grid gap-6 lg:grid-cols-5">
            <div class="space-y-4 lg:col-span-2">
                <!-- This phone -->
                <section class="rounded-xl border border-border bg-card p-4">
                    <h2
                        class="font-display mb-2 flex items-center gap-2 text-lg"
                    >
                        <Smartphone class="size-5 text-paddy" /> এই ফোনে
                        নোটিফিকেশন
                    </h2>

                    <p v-if="!vapidKey" class="text-sm text-muted-foreground">
                        সার্ভারে নোটিফিকেশনের চাবি তৈরি করা যায়নি। অ্যাপ
                        আপডেটের পর আবার চেষ্টা করুন।
                    </p>
                    <template v-else-if="state === 'on'">
                        <p class="mb-3 flex items-center gap-2 text-sm">
                            <BellRing class="size-4 text-paddy" /> চালু আছে ·
                            আপনার মোট {{ devices }}টি ফোন/ব্রাউজারে যাবে
                        </p>
                        <div class="flex flex-wrap gap-2">
                            <Button
                                size="sm"
                                variant="secondary"
                                @click="sendTest"
                                ><Send /> পরীক্ষা পাঠান</Button
                            >
                            <Button
                                size="sm"
                                variant="ghost"
                                :disabled="busy"
                                @click="turnOff"
                                ><BellOff /> এই ফোনে বন্ধ</Button
                            >
                        </div>
                    </template>
                    <template v-else-if="state === 'off'">
                        <p class="mb-3 text-sm text-muted-foreground">
                            চালু করলে অ্যাপ বন্ধ থাকলেও সময়মতো ফোনে আসবে, শব্দ
                            ও কাঁপুনি সহ।
                        </p>
                        <Button :disabled="busy" @click="turnOn"
                            ><BellRing /> নোটিফিকেশন চালু করুন</Button
                        >
                    </template>
                    <p
                        v-else-if="state === 'blocked'"
                        class="text-sm text-muted-foreground"
                    >
                        এই ফোনে নোটিফিকেশন আটকানো আছে। ব্রাউজারের ঠিকানার পাশের
                        🔒 চিহ্ন → অনুমতি → নোটিফিকেশন "অনুমতি দিন" করে পাতাটি
                        আবার খুলুন।
                    </p>
                    <p
                        v-else-if="state === 'needs-install'"
                        class="text-sm text-muted-foreground"
                    >
                        আইফোনে আগে অ্যাপটি হোম স্ক্রিনে রাখুন: Safari-র শেয়ার
                        বোতাম → "Add to Home Screen"। তারপর সেখান থেকে খুলে
                        এখানে চালু করুন।
                    </p>
                    <p v-else class="text-sm text-muted-foreground">
                        এই ব্রাউজারে নোটিফিকেশন চলে না। ফোনে Chrome দিয়ে খুলুন।
                    </p>

                    <div
                        class="mt-4 grid gap-2 border-t border-border/70 pt-3 text-sm"
                    >
                        <label class="flex items-center justify-between gap-3">
                            <span>নিজের রিমাইন্ডার</span>
                            <Toggle
                                v-model="prefs.reminders"
                                @update:model-value="savePrefs"
                            />
                        </label>
                        <label class="flex items-center justify-between gap-3">
                            <span
                                >সকাল ৭টায় আজকের কাজের তালিকা
                                <small class="block text-muted-foreground"
                                    >গর্ভ পরীক্ষা, বাচ্চা হওয়ার দিন, স্টক
                                    ফুরানো…</small
                                ></span
                            >
                            <Toggle
                                v-model="prefs.morning"
                                @update:model-value="savePrefs"
                            />
                        </label>
                        <label class="flex items-center justify-between gap-3">
                            <span
                                >সন্ধ্যা ৭টায়, দুধ/খাবার না লিখলে
                                <small class="block text-muted-foreground"
                                    >সেদিন লেখা হয়ে থাকলে আসবে না</small
                                ></span
                            >
                            <Toggle
                                v-model="prefs.evening"
                                @update:model-value="savePrefs"
                            />
                        </label>
                    </div>
                </section>

                <!-- New reminder -->
                <form
                    class="rounded-xl border border-border bg-card p-4"
                    @submit.prevent="save"
                >
                    <h2
                        class="font-display mb-3 flex items-center gap-2 text-lg"
                    >
                        <AlarmClock class="size-5 text-turmeric" /> নতুন
                        রিমাইন্ডার
                    </h2>
                    <div class="grid gap-3">
                        <Field label="কী মনে করাবো" :error="f.errors.title">
                            <Input
                                v-model="f.title"
                                required
                                maxlength="160"
                                placeholder="যেমন: ঘাসের জমিতে সার দিতে হবে"
                            />
                        </Field>

                        <Field label="কখন" :error="f.errors.due_at">
                            <div class="grid grid-cols-2 gap-1.5">
                                <button
                                    v-for="p in presets"
                                    :key="p.key"
                                    type="button"
                                    :class="[
                                        'h-10 rounded-md border text-sm transition-colors',
                                        preset === p.key
                                            ? 'border-primary bg-primary text-primary-foreground'
                                            : 'border-input bg-card hover:bg-accent',
                                        p.key === 'custom' && 'col-span-2',
                                    ]"
                                    @click="pick(p.key)"
                                >
                                    {{ p.label }}
                                </button>
                            </div>
                            <Input
                                v-if="preset === 'custom'"
                                v-model="f.due_at"
                                type="datetime-local"
                                class="mt-2"
                                required
                            />
                        </Field>

                        <div class="grid grid-cols-2 gap-3">
                            <Field label="কতবার">
                                <NSelect v-model="f.repeat">
                                    <option
                                        v-for="(label, key) in repeats"
                                        :key="key"
                                        :value="key"
                                    >
                                        {{ label }}
                                    </option>
                                </NSelect>
                            </Field>
                            <Field label="কাকে">
                                <NSelect v-model="f.for_user_id">
                                    <option :value="null">সবাইকে</option>
                                    <option
                                        v-for="p in people"
                                        :key="p.id"
                                        :value="p.id"
                                    >
                                        {{ p.name }}
                                    </option>
                                </NSelect>
                            </Field>
                        </div>

                        <button
                            v-if="!more"
                            type="button"
                            class="text-left text-sm text-muted-foreground underline-offset-4 hover:underline"
                            @click="more = true"
                        >
                            + বিস্তারিত, ক্যাটাগরি বা গরু যোগ করুন
                        </button>
                        <template v-else>
                            <Field label="বিস্তারিত (ঐচ্ছিক)">
                                <Input
                                    v-model="f.body"
                                    placeholder="যেমন: ইউরিয়া ১০ কেজি, উত্তরের জমি"
                                />
                            </Field>
                            <div class="grid grid-cols-2 gap-3">
                                <Field label="ক্যাটাগরি">
                                    <NSelect v-model="f.cost_center_id">
                                        <option :value="null">—</option>
                                        <option
                                            v-for="c in centers"
                                            :key="c.id"
                                            :value="c.id"
                                            :disabled="c.kind === 'group'"
                                        >
                                            {{ centerLabel(c) }}
                                        </option>
                                    </NSelect>
                                </Field>
                                <Field label="গরু">
                                    <NSelect v-model="f.animal_id">
                                        <option :value="null">—</option>
                                        <option
                                            v-for="a in animals"
                                            :key="a.id"
                                            :value="a.id"
                                        >
                                            {{ a.tag }} {{ a.name ?? '' }}
                                        </option>
                                    </NSelect>
                                </Field>
                            </div>
                        </template>

                        <Button type="submit" :disabled="f.processing"
                            >সময় ঠিক করুন</Button
                        >
                    </div>
                </form>
            </div>

            <div class="min-w-0 space-y-5 lg:col-span-3">
                <!-- Stock that is running out -->
                <section
                    v-if="trackedStock.length"
                    class="rounded-xl border border-border bg-card p-4"
                >
                    <h2
                        class="font-display mb-3 flex items-center gap-2 text-lg"
                    >
                        <Package class="size-5 text-clay" /> স্টক আর কত দিন চলবে
                    </h2>
                    <ul class="divide-y divide-border/60">
                        <li
                            v-for="s in trackedStock"
                            :key="s.id"
                            class="flex items-center justify-between gap-3 py-2 text-sm"
                        >
                            <span class="min-w-0">
                                <span class="font-medium">{{ s.name }}</span>
                                <small class="num block text-muted-foreground"
                                    >আছে {{ s.qty_on_hand }} {{ s.unit }} · দিনে
                                    গড়ে {{ s.per_day }} {{ s.unit }}</small
                                >
                            </span>
                            <span class="flex shrink-0 items-center gap-2">
                                <b
                                    :class="[
                                        'num',
                                        s.low ? 'text-destructive' : '',
                                    ]"
                                    >{{ Math.round(s.days_left ?? 0) }} দিন</b
                                >
                                <Button
                                    v-if="s.low"
                                    size="sm"
                                    variant="secondary"
                                    @click="remindToBuy(s)"
                                    >মনে করাও</Button
                                >
                            </span>
                        </li>
                    </ul>
                    <p
                        v-if="lowStock.length"
                        class="mt-2 text-xs text-muted-foreground"
                    >
                        ৫ দিনের কম থাকলে সকালের তালিকাতেও আসবে।
                    </p>
                </section>

                <!-- Open reminders -->
                <template v-for="group in groups" :key="group.key">
                    <section v-if="group.rows.length">
                        <h2
                            :class="[
                                'mb-2 text-sm font-semibold',
                                group.key === 'late'
                                    ? 'text-destructive'
                                    : 'text-muted-foreground',
                            ]"
                        >
                            {{ group.title }} ({{ group.rows.length }})
                        </h2>
                        <ul class="space-y-2">
                            <li
                                v-for="r in group.rows"
                                :key="r.id"
                                class="rounded-xl border border-border bg-card p-3"
                            >
                                <div class="flex items-start gap-3">
                                    <button
                                        type="button"
                                        class="mt-0.5 flex size-6 shrink-0 items-center justify-center rounded-full border-2 border-paddy/60 text-paddy hover:bg-paddy hover:text-primary-foreground"
                                        aria-label="হয়ে গেছে"
                                        @click="act(r, 'done')"
                                    >
                                        <Check class="size-3.5" />
                                    </button>
                                    <div class="min-w-0 flex-1">
                                        <p class="font-medium">{{ r.title }}</p>
                                        <p
                                            v-if="r.body"
                                            class="text-sm text-muted-foreground"
                                        >
                                            {{ r.body }}
                                        </p>
                                        <p
                                            class="mt-1 flex flex-wrap items-center gap-x-2 gap-y-1 text-xs text-muted-foreground"
                                        >
                                            <span
                                                :class="
                                                    r.overdue
                                                        ? 'font-medium text-destructive'
                                                        : 'text-foreground'
                                                "
                                                >{{ when(r.due_at) }}</span
                                            >
                                            <span
                                                v-if="r.repeat !== 'none'"
                                                class="inline-flex items-center gap-1"
                                                ><Repeat class="size-3" />{{
                                                    repeats[r.repeat]
                                                }}</span
                                            >
                                            <span>{{
                                                r.for ? `→ ${r.for}` : '→ সবাই'
                                            }}</span>
                                            <span v-if="r.center">{{
                                                r.center
                                            }}</span>
                                            <Link
                                                v-if="r.animal_id"
                                                :href="`/animals/${r.animal_id}`"
                                                class="underline"
                                                >{{ r.animal }}</Link
                                            >
                                        </p>
                                    </div>
                                </div>
                                <div class="mt-2 flex gap-1.5 pl-9">
                                    <Button
                                        size="sm"
                                        variant="ghost"
                                        class="h-8"
                                        @click="act(r, 'snooze', 60)"
                                        >১ ঘণ্টা পরে</Button
                                    >
                                    <Button
                                        size="sm"
                                        variant="ghost"
                                        class="h-8"
                                        @click="
                                            act(
                                                r,
                                                'snooze',
                                                minutesUntilMorning(),
                                            )
                                        "
                                        >কাল সকালে</Button
                                    >
                                </div>
                            </li>
                        </ul>
                    </section>
                </template>

                <p
                    v-if="!open.length"
                    class="rounded-xl border border-dashed border-border px-4 py-10 text-center text-muted-foreground"
                >
                    কোনো রিমাইন্ডার বাকি নেই।
                </p>

                <details v-if="done.length" class="group">
                    <summary
                        class="cursor-pointer text-sm text-muted-foreground"
                    >
                        হয়ে যাওয়া ({{ done.length }})
                    </summary>
                    <ul class="mt-2 divide-y divide-border/60 text-sm">
                        <li
                            v-for="r in done"
                            :key="r.id"
                            class="flex items-center justify-between gap-3 py-2"
                        >
                            <span class="text-muted-foreground line-through">{{
                                r.title
                            }}</span>
                            <button
                                type="button"
                                class="shrink-0 text-xs underline"
                                @click="act(r, 'undo')"
                            >
                                ফিরিয়ে আনুন
                            </button>
                        </li>
                    </ul>
                </details>

                <p
                    v-if="can['manage-update']"
                    class="rounded-lg bg-muted/50 p-3 text-xs text-muted-foreground"
                >
                    ঠিক মিনিটে পাঠাতে সার্ভারকে প্রতি মিনিটে একবার জাগাতে হয়।
                    একটি ফ্রি মনিটর (যেমন cron-job.org) দিয়ে প্রতি মিনিটে
                    <code>https://khamar.klokads.com/up</code> খোলালেই হবে —
                    hPanel ক্রন লাগবে না।
                </p>
            </div>
        </div>
    </div>
</template>
