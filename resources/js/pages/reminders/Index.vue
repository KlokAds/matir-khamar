<script setup lang="ts">
import { Head, Link, router, useForm } from '@inertiajs/vue3';
import {
    AlarmClock,
    BellOff,
    BellRing,
    Check,
    ChevronDown,
    Package,
    Pencil,
    Repeat,
    Send,
    Smartphone,
    Trash2,
    Undo2,
} from '@lucide/vue';
import { computed, onBeforeUnmount, onMounted, ref } from 'vue';
import Field from '@/components/khamar/Field.vue';
import NSelect from '@/components/khamar/NSelect.vue';
import PageTitle from '@/components/khamar/PageTitle.vue';
import Toggle from '@/components/khamar/Toggle.vue';
import { Button } from '@/components/ui/button';
import DateField from '@/components/khamar/DateField.vue';
import { Input } from '@/components/ui/input';
import { ask } from '@/lib/dialog';
import { currentLocale, t } from '@/lib/i18n';
import { centerLabel } from '@/lib/khamar';
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
    alarm: boolean;
    for: string | null;
    for_user_id: number | null;
    cost_center_id: number | null;
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
    deviceList: {
        id: number;
        device: string | null;
        sent: string | null;
        received: string | null;
        mode: string;
    }[];
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
            router.reload({ only: ['devices', 'deviceList'] });
        }
    } finally {
        busy.value = false;
    }
}

async function turnOff() {
    busy.value = true;
    state.value = await disablePush();
    busy.value = false;
    router.reload({ only: ['devices', 'deviceList'] });
}

/*
 * Two checks to find where a notification gets stuck. "Show here" asks this phone to show one
 * by itself — no server; if even that does not appear, the phone blocks the app's notifications.
 * "Send test" goes through the server; the open app hears when the phone receives it.
 */
const check = ref<'' | 'waiting' | 'received' | 'lost' | 'shown' | 'blocked'>(
    '',
);
let waitTimer: ReturnType<typeof setTimeout> | undefined;

function onPushMessage(event: MessageEvent) {
    const data = event.data as { type?: string } | undefined;

    if (data?.type === 'khamar-push' && check.value === 'waiting') {
        check.value = 'received';
        clearTimeout(waitTimer);
    }
}

onMounted(() =>
    navigator.serviceWorker?.addEventListener('message', onPushMessage),
);
onBeforeUnmount(() => {
    navigator.serviceWorker?.removeEventListener('message', onPushMessage);
    clearTimeout(waitTimer);
});

function sendTest() {
    check.value = 'waiting';
    clearTimeout(waitTimer);
    waitTimer = setTimeout(() => {
        if (check.value === 'waiting') {
            check.value = 'lost';
        }
    }, 30000);
    router.post('/push/test', {}, { preserveScroll: true });
}

/** Which phone or computer, from its browser's description. */
const deviceName = (ua: string | null) =>
    !ua
        ? t('অজানা')
        : /android/i.test(ua)
          ? t('Android ফোন')
          : /iphone|ipad/i.test(ua)
            ? t('iPhone')
            : /windows|macintosh|linux/i.test(ua)
              ? t('কম্পিউটার')
              : t('অজানা');

/** Did the last notification reach this phone? */
function deliveryNote(d: { sent: string | null; received: string | null }) {
    if (!d.sent) {
        return { ok: true, text: t('এখনো কিছু পাঠানো হয়নি') };
    }

    if (d.received && d.received >= d.sent) {
        return { ok: true, text: t('ঠিকমতো পৌঁছাচ্ছে ✓') };
    }

    const age = (Date.now() - new Date(d.sent).getTime()) / 60000;

    return age < 2
        ? { ok: true, text: t('পাঠানো হলো — ফোন জানানোর অপেক্ষা…') }
        : {
              ok: false,
              text: t(
                  'ফোনে পৌঁছাচ্ছে না — নিচের ব্যাটারি ও অটোস্টার্ট সেটিং দিন, অ্যাপটা একবার খুলে রাখুন।',
              ),
          };
}

const checkText = computed<Record<string, string>>(() => ({
    waiting: t('পাঠানো হলো — ফোনে আসার অপেক্ষা…'),
    received: t(
        'ফোনে এসে পৌঁছেছে ✓ — উপরে নোটিফিকেশন না দেখালে নিচের সেটিং দেখুন (পপ-আপ, শব্দ)।',
    ),
    lost: t(
        '৩০ সেকেন্ডেও ফোনে পৌঁছায়নি — ফোন অ্যাপটাকে ঘুম পাড়িয়ে রাখছে। নিচের ব্যাটারি ও অটোস্টার্ট সেটিং দিন।',
    ),
    shown: t(
        'ফোন নিজে দেখাতে পারে। উপরে না এলে নিচের সেটিংয়ে নোটিফিকেশন চালু করুন।',
    ),
    blocked: t(
        'ফোন নোটিফিকেশন দেখাতে দিচ্ছে না — নিচের সেটিংয়ে নোটিফিকেশন চালু করুন।',
    ),
}));

const phoneSteps = [
    'ফোনের Settings → Apps → “মাটির খামার” (অ্যাপ না থাকলে Chrome) → Notifications: চালু। ভেতরের প্রতিটি ধরনে “Pop-up / Floating / ব্যানার” আর “Sound” চালু করুন।',
    'একই জায়গায় Battery / Battery saver → “No restrictions” বা “Unrestricted”।',
    'Xiaomi/Redmi/Vivo/Oppo/Realme ফোনে: “Autostart” চালু করুন (Security বা Phone Manager অ্যাপে)। রিসেন্ট অ্যাপ থেকে অ্যাপটা লক করে রাখুন, যাতে সরিয়ে না দেয়।',
    '“Do not disturb” বন্ধ আছে কি না দেখুন। তারপর আবার “পরীক্ষা পাঠান” চাপুন।',
];

async function showHere() {
    try {
        const registration = await navigator.serviceWorker.ready;
        await registration.showNotification(
            t('মাটির খামার — এই ফোনের পরীক্ষা'),
            {
                body: t('এটা দেখা গেলে ফোন অ্যাপের নোটিফিকেশন দেখাতে পারে।'),
                icon: '/icons/icon-192.png',
                badge: '/icons/icon-192.png',
                // A new tag each time, so every test makes its sound.
                tag: `local-test-${Date.now()}`,
            },
        );
        check.value = 'shown';
    } catch {
        check.value = 'blocked';
    }
}

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
        label: t('১ ঘণ্টা পরে'),
        at: () => new Date(Date.now() + 3600_000),
    },
    {
        key: '3h',
        label: t('৩ ঘণ্টা পরে'),
        at: () => new Date(Date.now() + 3 * 3600_000),
    },
    {
        key: 'evening',
        label: t('আজ সন্ধ্যা ৬টা'),
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
        label: t('কাল সকাল ৭টা'),
        at: () => {
            const d = new Date();
            d.setDate(d.getDate() + 1);
            d.setHours(7, 0, 0, 0);

            return d;
        },
    },
    { key: 'custom', label: t('নিজে সময় দিন'), at: () => new Date() },
] as const;

const preset = ref<(typeof presets)[number]['key']>('1h');
const f = useForm({
    title: '',
    body: '',
    due_at: local(presets[0].at()),
    repeat: 'none',
    alarm: false,
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

/* The same form changes an existing reminder: its time stays as it was unless changed. */
const editing = ref<Row | null>(null);
const formEl = ref<HTMLElement | null>(null);

function startEdit(row: Row) {
    editing.value = row;
    f.clearErrors();
    f.title = row.title;
    f.body = row.body ?? '';
    f.due_at = row.due_at;
    f.repeat = row.repeat;
    f.alarm = row.alarm;
    f.for_user_id = row.for_user_id;
    f.cost_center_id = row.cost_center_id;
    f.animal_id = row.animal_id;
    preset.value = 'custom';
    more.value = Boolean(row.body || row.cost_center_id || row.animal_id);
    formEl.value?.scrollIntoView({ behavior: 'smooth', block: 'start' });
}

function stopEdit() {
    editing.value = null;
    f.reset();
    pick('1h');
}

function save() {
    if (preset.value !== 'custom') {
        f.due_at = local(presets.find((p) => p.key === preset.value)!.at());
    }

    if (editing.value) {
        f.patch(`/reminders/${editing.value.id}`, {
            preserveScroll: true,
            onSuccess: stopEdit,
        });

        return;
    }

    f.post('/reminders', {
        preserveScroll: true,
        onSuccess: () => {
            f.reset('title', 'body', 'cost_center_id', 'animal_id');
            pick('1h');
        },
    });
}

async function remove(row: Row) {
    if (
        await ask.confirm({
            title: t('রিমাইন্ডার মুছবেন?'),
            body: row.title,
            confirmText: t('মুছে ফেলুন'),
            danger: true,
        })
    ) {
        router.delete(`/reminders/${row.id}`, {
            preserveScroll: true,
            onSuccess: () => {
                if (editing.value?.id === row.id) {
                    stopEdit();
                }
            },
        });
    }
}

function remindToBuy(item: { name: string }) {
    f.title = t(':name কিনতে হবে', { name: t(item.name) });
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
        title: t('সময় পার হয়ে গেছে'),
        rows: props.open.filter((r) => r.overdue),
    },
    {
        key: 'today',
        title: t('আজ'),
        rows: props.open.filter(
            (r) => !r.overdue && r.due_at.slice(0, 10) === todayKey,
        ),
    },
    {
        key: 'later',
        title: t('সামনে'),
        rows: props.open.filter(
            (r) => !r.overdue && r.due_at.slice(0, 10) > todayKey,
        ),
    },
]);

function when(value: string): string {
    const d = new Date(value);
    const locale = currentLocale() === 'en' ? 'en-GB' : 'bn-BD';
    const time = d.toLocaleTimeString(locale, {
        hour: 'numeric',
        minute: '2-digit',
    });
    const dayDiff = Math.round(
        (new Date(value.slice(0, 10)).getTime() -
            new Date(todayKey).getTime()) /
            86400_000,
    );

    if (dayDiff === 0) {
        return t('আজ :time', { time });
    }

    if (dayDiff === 1) {
        return t('কাল :time', { time });
    }

    if (dayDiff === -1) {
        return t('গতকাল :time', { time });
    }

    return `${d.toLocaleDateString(locale, { day: 'numeric', month: 'short' })} ${time}`;
}

const lowStock = computed(() => props.stock.filter((s) => s.low));
const trackedStock = computed(() =>
    props.stock.filter((s) => s.days_left !== null),
);
</script>

<template>
    <Head :title="t('মনে করিয়ে দিন')" />

    <div class="flex flex-1 flex-col p-4 md:p-6">
        <PageTitle
            :title="t('মনে করিয়ে দিন')"
            :subtitle="
                t(
                    'মাঠে দাঁড়িয়ে মনে পড়লো? লিখে সময় দিন — ঠিক সময়ে ফোনে নোটিফিকেশন যাবে।',
                )
            "
        />

        <div class="grid gap-6 lg:grid-cols-5">
            <div class="space-y-4 lg:col-span-2">
                <!-- This phone -->
                <section class="rounded-xl border border-border bg-card p-4">
                    <h2
                        class="mb-2 flex items-center gap-2 font-display text-lg"
                    >
                        <Smartphone class="size-5 text-paddy" />
                        {{ t('এই ফোনে নোটিফিকেশন') }}
                    </h2>

                    <p v-if="!vapidKey" class="text-sm text-muted-foreground">
                        {{
                            t(
                                'সার্ভারে নোটিফিকেশনের চাবি তৈরি করা যায়নি। অ্যাপ আপডেটের পর আবার চেষ্টা করুন।',
                            )
                        }}
                    </p>
                    <template v-else-if="state === 'on'">
                        <p class="mb-3 flex items-center gap-2 text-sm">
                            <BellRing class="size-4 text-paddy" />
                            {{
                                t(
                                    'চালু আছে · আপনার মোট :nটি ফোন/ব্রাউজারে যাবে',
                                    { n: devices },
                                )
                            }}
                        </p>
                        <ul
                            v-if="deviceList.length"
                            class="mb-3 space-y-1.5 text-sm"
                        >
                            <li
                                v-for="d in deviceList"
                                :key="d.id"
                                class="flex flex-wrap items-center justify-between gap-x-3 gap-y-1 rounded-lg border border-border bg-background/60 px-3 py-2"
                            >
                                <span class="font-medium">{{
                                    deviceName(d.device)
                                }}</span>
                                <span
                                    :class="[
                                        'text-xs leading-normal',
                                        deliveryNote(d).ok
                                            ? 'text-paddy'
                                            : 'text-clay',
                                    ]"
                                    >{{ deliveryNote(d).text }}</span
                                >
                            </li>
                        </ul>
                        <div class="flex flex-wrap gap-2">
                            <Button
                                size="sm"
                                variant="secondary"
                                @click="showHere"
                                ><BellRing /> {{ t('এই ফোনে দেখান') }}</Button
                            >
                            <Button
                                size="sm"
                                variant="secondary"
                                @click="sendTest"
                                ><Send /> {{ t('পরীক্ষা পাঠান') }}</Button
                            >
                            <Button
                                size="sm"
                                variant="secondary"
                                :disabled="busy"
                                @click="turnOff"
                                ><BellOff /> {{ t('এই ফোনে বন্ধ') }}</Button
                            >
                        </div>
                        <p
                            v-if="check"
                            :class="[
                                'mt-3 rounded-lg px-3 py-2 text-sm leading-normal',
                                check === 'received' || check === 'shown'
                                    ? 'bg-paddy/10 text-paddy'
                                    : check === 'waiting'
                                      ? 'bg-muted text-muted-foreground'
                                      : 'bg-clay/10 text-clay',
                            ]"
                        >
                            {{ checkText[check] }}
                        </p>
                        <details
                            class="mt-3 rounded-lg border border-border bg-background/60 text-sm"
                            :open="check === 'lost' || check === 'blocked'"
                        >
                            <summary
                                class="cursor-pointer px-3 py-2 font-medium"
                            >
                                {{
                                    t(
                                        'WhatsApp-এর মতো শব্দ করে আসতে — ফোনের সেটিং (একবার)',
                                    )
                                }}
                            </summary>
                            <ol
                                class="list-decimal space-y-1.5 border-t border-border px-3 py-3 pl-7 leading-normal text-muted-foreground"
                            >
                                <li v-for="step in phoneSteps" :key="step">
                                    {{ t(step) }}
                                </li>
                            </ol>
                        </details>
                    </template>
                    <template v-else-if="state === 'off'">
                        <p class="mb-3 text-sm text-muted-foreground">
                            {{
                                t(
                                    'চালু করলে অ্যাপ বন্ধ থাকলেও সময়মতো ফোনে আসবে, শব্দ ও কাঁপুনি সহ।',
                                )
                            }}
                        </p>
                        <Button :disabled="busy" @click="turnOn"
                            ><BellRing />
                            {{ t('নোটিফিকেশন চালু করুন') }}</Button
                        >
                    </template>
                    <p
                        v-else-if="state === 'blocked'"
                        class="text-sm text-muted-foreground"
                    >
                        {{
                            t(
                                'এই ফোনে নোটিফিকেশন আটকানো আছে। ব্রাউজারের ঠিকানার পাশের 🔒 চিহ্ন → অনুমতি → নোটিফিকেশন "অনুমতি দিন" করে পাতাটি আবার খুলুন।',
                            )
                        }}
                    </p>
                    <p
                        v-else-if="state === 'needs-install'"
                        class="text-sm text-muted-foreground"
                    >
                        {{
                            t(
                                'আইফোনে আগে অ্যাপটি হোম স্ক্রিনে রাখুন: Safari-র শেয়ার বোতাম → "Add to Home Screen"। তারপর সেখান থেকে খুলে এখানে চালু করুন।',
                            )
                        }}
                    </p>
                    <p v-else class="text-sm text-muted-foreground">
                        {{
                            t(
                                'এই ব্রাউজারে নোটিফিকেশন চলে না। ফোনে Chrome দিয়ে খুলুন।',
                            )
                        }}
                    </p>

                    <div
                        class="mt-4 grid gap-2 border-t border-border/70 pt-3 text-sm"
                    >
                        <label class="flex items-center justify-between gap-3">
                            <span>{{ t('নিজের রিমাইন্ডার') }}</span>
                            <Toggle
                                v-model="prefs.reminders"
                                @update:model-value="savePrefs"
                            />
                        </label>
                        <label class="flex items-center justify-between gap-3">
                            <span
                                >{{ t('সকাল ৭টায় আজকের কাজের তালিকা') }}
                                <small class="block text-muted-foreground">{{
                                    t(
                                        'গর্ভ পরীক্ষা, বাচ্চা হওয়ার দিন, স্টক ফুরানো…',
                                    )
                                }}</small></span
                            >
                            <Toggle
                                v-model="prefs.morning"
                                @update:model-value="savePrefs"
                            />
                        </label>
                        <label class="flex items-center justify-between gap-3">
                            <span
                                >{{
                                    t(
                                        'রাত ৮টায় (আবার ৯:৩০-এ), দুধ/খাবার না লিখলে',
                                    )
                                }}
                                <small class="block text-muted-foreground">{{
                                    t('সেদিন লেখা হয়ে থাকলে আসবে না')
                                }}</small></span
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
                    ref="formEl"
                    :class="[
                        'scroll-mt-20 rounded-xl border bg-card p-4',
                        editing
                            ? 'border-turmeric ring-2 ring-turmeric/30'
                            : 'border-border',
                    ]"
                    @submit.prevent="save"
                >
                    <h2
                        class="mb-3 flex items-center gap-2 font-display text-lg"
                    >
                        <component
                            :is="editing ? Pencil : AlarmClock"
                            class="size-5 text-turmeric"
                        />
                        {{
                            editing
                                ? t('রিমাইন্ডার বদলান')
                                : t('নতুন রিমাইন্ডার')
                        }}
                    </h2>
                    <div class="grid gap-3">
                        <Field
                            :label="t('কী মনে করাবো')"
                            :error="f.errors.title"
                        >
                            <Input
                                v-model="f.title"
                                required
                                maxlength="160"
                                :placeholder="
                                    t('যেমন: ঘাসের জমিতে সার দিতে হবে')
                                "
                            />
                        </Field>

                        <Field :label="t('কখন')" :error="f.errors.due_at">
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
                            <DateField
                                mode="datetime"
                                v-if="preset === 'custom'"
                                v-model="f.due_at"
                                class="mt-2"
                                required
                            />
                        </Field>

                        <label
                            class="flex cursor-pointer items-center justify-between gap-3 rounded-lg border border-turmeric/40 bg-turmeric/8 px-3 py-2.5"
                        >
                            <span class="text-sm">
                                <b class="flex items-center gap-1.5 font-medium"
                                    >🔔 {{ t('অ্যালার্মের মতো বাজবে') }}</b
                                >

                                <small class="block text-muted-foreground">{{
                                    t(
                                        '"হয়ে গেছে" না চাপা পর্যন্ত প্রতি ২ মিনিটে আবার বাজবে (২০ মিনিট পর্যন্ত)',
                                    )
                                }}</small>
                            </span>

                            <Toggle v-model="f.alarm" />
                        </label>

                        <div class="grid grid-cols-2 gap-3">
                            <Field :label="t('কতবার')">
                                <NSelect v-model="f.repeat">
                                    <option
                                        v-for="(label, key) in repeats"
                                        :key="key"
                                        :value="key"
                                    >
                                        {{ t(label) }}
                                    </option>
                                </NSelect>
                            </Field>
                            <Field :label="t('কাকে')">
                                <NSelect v-model="f.for_user_id">
                                    <option :value="null">
                                        {{ t('সবাইকে') }}
                                    </option>
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

                        <Button
                            v-if="!more"
                            type="button"
                            size="sm"
                            variant="secondary"
                            class="h-auto min-h-9 justify-self-start py-2 text-left leading-snug whitespace-normal"
                            @click="more = true"
                        >
                            {{ t('+ বিস্তারিত, ক্যাটাগরি বা গরু যোগ করুন') }}
                        </Button>
                        <template v-else>
                            <Field :label="t('বিস্তারিত (ঐচ্ছিক)')">
                                <Input
                                    v-model="f.body"
                                    :placeholder="
                                        t('যেমন: ইউরিয়া ১০ কেজি, উত্তরের জমি')
                                    "
                                />
                            </Field>
                            <div class="grid grid-cols-2 gap-3">
                                <Field :label="t('ক্যাটাগরি')">
                                    <NSelect v-model="f.cost_center_id">
                                        <option :value="null">—</option>
                                        <option
                                            v-for="c in centers"
                                            :key="c.id"
                                            :value="c.id"
                                            :disabled="c.kind === 'group'"
                                        >
                                            {{
                                                centerLabel({
                                                    ...c,
                                                    name: t(c.name),
                                                })
                                            }}
                                        </option>
                                    </NSelect>
                                </Field>
                                <Field :label="t('গরু')">
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

                        <div class="flex gap-2">
                            <Button
                                type="submit"
                                class="flex-1"
                                :disabled="f.processing"
                                >{{
                                    editing
                                        ? t('বদলে রাখুন')
                                        : t('সময় ঠিক করুন')
                                }}</Button
                            >
                            <Button
                                v-if="editing"
                                type="button"
                                variant="secondary"
                                @click="stopEdit"
                                >{{ t('বাতিল') }}</Button
                            >
                        </div>
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
                        class="mb-3 flex items-center gap-2 font-display text-lg"
                    >
                        <Package class="size-5 text-clay" />
                        {{ t('স্টক আর কত দিন চলবে') }}
                    </h2>
                    <ul class="divide-y divide-border/60">
                        <li
                            v-for="s in trackedStock"
                            :key="s.id"
                            class="flex items-center justify-between gap-3 py-2 text-sm"
                        >
                            <span class="min-w-0">
                                <span class="font-medium">{{ t(s.name) }}</span>
                                <small
                                    class="num block text-muted-foreground"
                                    >{{
                                        t(
                                            'আছে :qty :unit · দিনে গড়ে :per :unit',
                                            {
                                                qty: s.qty_on_hand,
                                                per: s.per_day ?? '',
                                                unit: t(s.unit),
                                            },
                                        )
                                    }}</small
                                >
                            </span>
                            <span class="flex shrink-0 items-center gap-2">
                                <b
                                    :class="[
                                        'num',
                                        s.low ? 'text-destructive' : '',
                                    ]"
                                    >{{
                                        t(':n দিন', {
                                            n: Math.round(s.days_left ?? 0),
                                        })
                                    }}</b
                                >
                                <Button
                                    v-if="s.low"
                                    size="sm"
                                    variant="secondary"
                                    @click="remindToBuy(s)"
                                    >{{ t('মনে করাও') }}</Button
                                >
                            </span>
                        </li>
                    </ul>
                    <p
                        v-if="lowStock.length"
                        class="mt-2 text-xs text-muted-foreground"
                    >
                        {{ t('৫ দিনের কম থাকলে সকালের তালিকাতেও আসবে।') }}
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
                                        class="flex size-8 shrink-0 items-center justify-center rounded-full border-2 border-paddy/60 bg-card text-paddy shadow-xs transition-colors hover:bg-paddy hover:text-primary-foreground active:bg-paddy active:text-primary-foreground"
                                        :aria-label="t('হয়ে গেছে')"
                                        :title="t('হয়ে গেছে')"
                                        @click="act(r, 'done')"
                                    >
                                        <Check class="size-4" />
                                    </button>
                                    <div class="min-w-0 flex-1">
                                        <p class="leading-snug font-medium">
                                            {{ r.alarm ? '🔔 ' : ''
                                            }}{{ r.title }}
                                        </p>
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
                                                    t(repeats[r.repeat])
                                                }}</span
                                            >
                                            <span>{{
                                                r.for
                                                    ? `→ ${r.for}`
                                                    : t('→ সবাই')
                                            }}</span>
                                            <span v-if="r.center">{{
                                                t(r.center)
                                            }}</span>
                                            <Link
                                                v-if="r.animal_id"
                                                :href="`/animals/${r.animal_id}`"
                                                class="font-medium text-primary underline underline-offset-2"
                                                >{{ r.animal }}</Link
                                            >
                                        </p>
                                    </div>
                                </div>
                                <div class="mt-2 flex flex-wrap gap-2 pl-11">
                                    <Button
                                        size="sm"
                                        variant="secondary"
                                        class="h-8"
                                        @click="act(r, 'snooze', 60)"
                                        ><AlarmClock />
                                        {{ t('১ ঘণ্টা পরে') }}</Button
                                    >
                                    <Button
                                        size="sm"
                                        variant="secondary"
                                        class="h-8"
                                        @click="
                                            act(
                                                r,
                                                'snooze',
                                                minutesUntilMorning(),
                                            )
                                        "
                                        >{{ t('কাল সকালে') }}</Button
                                    >
                                    <Button
                                        size="sm"
                                        variant="secondary"
                                        class="h-8"
                                        @click="startEdit(r)"
                                        ><Pencil /> {{ t('বদলান') }}</Button
                                    >
                                    <Button
                                        size="sm"
                                        variant="secondary"
                                        class="h-8 text-destructive"
                                        :aria-label="t('মুছে ফেলুন')"
                                        :title="t('মুছে ফেলুন')"
                                        @click="remove(r)"
                                        ><Trash2
                                    /></Button>
                                </div>
                            </li>
                        </ul>
                    </section>
                </template>

                <p
                    v-if="!open.length"
                    class="rounded-xl border border-dashed border-border px-4 py-10 text-center text-muted-foreground"
                >
                    {{ t('কোনো রিমাইন্ডার বাকি নেই।') }}
                </p>

                <details v-if="done.length" class="group">
                    <summary
                        class="btn-chip cursor-pointer list-none text-muted-foreground [&::-webkit-details-marker]:hidden"
                    >
                        {{ t('হয়ে যাওয়া (:n)', { n: done.length }) }}
                        <ChevronDown
                            class="transition-transform group-open:rotate-180"
                        />
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
                                class="btn-chip"
                                @click="act(r, 'undo')"
                            >
                                <Undo2 /> {{ t('ফিরিয়ে আনুন') }}
                            </button>
                        </li>
                    </ul>
                </details>
            </div>
        </div>
    </div>
</template>
