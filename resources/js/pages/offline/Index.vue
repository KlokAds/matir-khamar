<script setup lang="ts">
import { ask } from '@/lib/dialog';
import { Head } from '@inertiajs/vue3';
import {
    CloudOff,
    CloudUpload,
    HandCoins,
    Milk,
    Moon,
    RefreshCw,
    Sun,
    Trash2,
    Wheat,
    Wifi,
} from '@lucide/vue';
import { computed, onMounted, onUnmounted, reactive, ref } from 'vue';
import Field from '@/components/khamar/Field.vue';
import SessionPicker from '@/components/khamar/SessionPicker.vue';
import NSelect from '@/components/khamar/NSelect.vue';
import PageTitle from '@/components/khamar/PageTitle.vue';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { currentSession, day, qty, taka, today } from '@/lib/khamar';
import type { Center, Option } from '@/lib/khamar';
import { enqueue, listFor, remove, syncNow } from '@/lib/offlineQueue';
import type { QueueItem, SyncOutcome } from '@/lib/offlineQueue';

defineOptions({
    layout: { breadcrumbs: [{ title: 'অফলাইন এন্ট্রি', href: '/offline' }] },
});

const props = defineProps<{
    userId: number;
    snapshotAt: string;
    sessions: Record<string, string>;
    feedSessions: Record<string, string>;
    cows: { id: number; tag: string; name: string | null }[];
    items: {
        id: number;
        name: string;
        unit_label: string;
        qty_on_hand: number;
        average_cost: number;
    }[];
    centers: Center[];
    canExpense: boolean;
    moneyAccounts: Option[];
    expenseAccounts: Option[];
}>();

const tab = ref<'milk' | 'feed' | 'expense'>('milk');
const online = ref(navigator.onLine);
const queue = ref<QueueItem[]>([]);
const syncing = ref(false);
const lastOutcome = ref<SyncOutcome | null>(null);
const saved = ref('');

const animalCenters = computed(() =>
    props.centers.filter((c) => c.kind !== 'overhead'),
);

const milk = reactive({
    date: today(),
    session: new Date().getHours() < 13 ? 'morning' : 'evening',
    rows: props.cows.length
        ? props.cows.map((cow) => ({
              animal_id: cow.id as number | null,
              liters: '',
          }))
        : [{ animal_id: null, liters: '' }],
});

const feed = reactive({
    session: currentSession(),
    item_id:
        props.items.find((i) => i.qty_on_hand > 0)?.id ??
        props.items[0]?.id ??
        null,
    date: today(),
    note: '',
    splits: animalCenters.value
        .filter((c) => c.holds_animals)
        .map((c) => ({ cost_center_id: c.id, qty: '' })),
});

const expense = reactive({
    date: today(),
    money_account_id: props.moneyAccounts[0]?.id ?? null,
    account_id: null as number | null,
    cost_center_id:
        props.centers.find((c) => c.code === 'SHADHARON')?.id ?? null,
    amount: '',
    description: '',
});

const item = computed(() => props.items.find((i) => i.id === feed.item_id));

// Stock left = what the server said when this page was saved, minus feed given on this phone since then.
const localStock = computed(() => {
    const waiting = queue.value
        .filter(
            (q) =>
                q.kind === 'feed' &&
                q.payload.item_id === feed.item_id &&
                (q.status === 'pending' ||
                    (q.status === 'done' && q.recordedAt > props.snapshotAt)),
        )
        .reduce(
            (sum, q) =>
                sum +
                (q.payload.splits as { qty: string }[]).reduce(
                    (s, split) => s + (parseFloat(split.qty) || 0),
                    0,
                ),
            0,
        );

    return (item.value?.qty_on_hand ?? 0) - waiting;
});
const feedTotal = computed(() =>
    feed.splits.reduce((sum, s) => sum + (parseFloat(s.qty) || 0), 0),
);
const milkTotal = computed(() =>
    milk.rows.reduce((sum, r) => sum + (parseFloat(r.liters) || 0), 0),
);
const pending = computed(
    () => queue.value.filter((q) => q.status === 'pending').length,
);
const reachable = computed(
    () => online.value && lastOutcome.value !== 'offline',
);
const centerName = (id: unknown) =>
    props.centers.find((c) => c.id === id)?.name ?? '';
const cowLabel = (id: number | null) => {
    const cow = props.cows.find((c) => c.id === id);

    return cow
        ? `${cow.tag}${cow.name ? ' · ' + cow.name : ''}`
        : 'সব গাভী মিলিয়ে';
};

async function reload() {
    queue.value = await listFor(props.userId);
}

async function send() {
    syncing.value = true;
    lastOutcome.value = await syncNow(props.userId);
    syncing.value = false;
    await reload();
}

async function save(kind: 'milk' | 'feed' | 'expense') {
    if (kind === 'milk') {
        if (milkTotal.value <= 0) {
            return;
        }

        await enqueue(
            props.userId,
            'milk',
            `দুধ ${qty(milkTotal.value)} লি. — ${props.sessions[milk.session]}, ${day(milk.date)}`,
            {
                date: milk.date,
                session: milk.session,
                rows: milk.rows.filter((r) => parseFloat(r.liters) > 0),
            },
        );
        milk.rows.forEach((r) => (r.liters = ''));
    }

    if (kind === 'feed') {
        if (
            feedTotal.value <= 0 ||
            feedTotal.value > localStock.value + 0.0005
        ) {
            return;
        }

        const parts = feed.splits.filter((s) => parseFloat(s.qty) > 0);
        await enqueue(
            props.userId,
            'feed',
            `${item.value?.name} ${qty(feedTotal.value, item.value?.unit_label)} — ${parts.map((s) => centerName(s.cost_center_id)).join(', ')} (${props.feedSessions[feed.session]})`,
            {
                item_id: feed.item_id,
                session: feed.session,
                date: feed.date,
                note: feed.note,
                splits: parts,
            },
        );
        feed.splits.forEach((s) => (s.qty = ''));
        feed.note = '';
    }

    if (kind === 'expense') {
        if (!expense.account_id || !(parseFloat(expense.amount) > 0)) {
            return;
        }

        const account =
            props.expenseAccounts.find((a) => a.id === expense.account_id)
                ?.name ?? '';
        await enqueue(
            props.userId,
            'expense',
            `${account} ${taka(Math.round(parseFloat(expense.amount) * 100))} — ${centerName(expense.cost_center_id)}`,
            { ...expense },
        );
        expense.amount = '';
        expense.description = '';
    }

    saved.value = online.value
        ? 'রাখা হয়েছে, পাঠানো হচ্ছে…'
        : 'ফোনে রাখা হয়েছে। নেট এলে নিজে থেকেই যাবে।';
    window.setTimeout(() => (saved.value = ''), 4000);
    await reload();

    if (online.value) {
        await send();
    }
}

async function discard(uuid: string) {
    if (
        await ask.confirm({
            title: 'এন্ট্রিটি মুছবেন?',
            body: 'এটা শুধু এই ফোন থেকে মুছবে; খাতায় ওঠেনি।',
            confirmText: 'মুছুন',
            danger: true,
        })
    ) {
        await remove(uuid);
        await reload();
    }
}

const goOnline = () => {
    online.value = true;
    void send();
};
const goOffline = () => (online.value = false);

let retry: number | undefined;

onMounted(() => {
    void reload().then(() => (online.value ? send() : undefined));
    window.addEventListener('online', goOnline);
    window.addEventListener('offline', goOffline);

    // While entries wait and the server could not be reached, keep knocking every 20 seconds.
    retry = window.setInterval(() => {
        if (pending.value > 0 && online.value && !syncing.value) {
            void send();
        }
    }, 20000);
});

onUnmounted(() => {
    window.removeEventListener('online', goOnline);
    window.removeEventListener('offline', goOffline);
    window.clearInterval(retry);
});

const outcomeText: Record<SyncOutcome, string> = {
    ok: 'সব পাঠানো হয়েছে।',
    nothing: 'পাঠানোর মতো কিছু নেই।',
    offline: 'নেট নেই — নেট এলে নিজে থেকেই যাবে।',
    'signed-out':
        'লগইন মেয়াদ শেষ — নেট থাকা অবস্থায় আবার লগইন করুন, এন্ট্রিগুলো ফোনেই আছে।',
    error: 'সার্ভারে সমস্যা — একটু পরে আবার চেষ্টা হবে।',
};
</script>

<template>
    <Head title="অফলাইন এন্ট্রি" />

    <div class="flex flex-1 flex-col p-4 md:p-6">
        <PageTitle
            title="অফলাইন এন্ট্রি"
            :subtitle="`নেট না থাকলেও লিখুন — ফোনে জমা থাকবে। তথ্য হালনাগাদ: ${day(snapshotAt)}`"
        >
            <template #actions>
                <span
                    :class="[
                        'flex items-center gap-1.5 rounded-full px-3 py-1.5 text-sm',
                        reachable
                            ? 'bg-paddy/15 text-paddy'
                            : 'bg-clay/15 text-clay',
                    ]"
                >
                    <component
                        :is="reachable ? Wifi : CloudOff"
                        class="size-4"
                    />
                    {{
                        reachable
                            ? 'সার্ভারের সাথে যুক্ত'
                            : online
                              ? 'সার্ভারে পৌঁছানো যাচ্ছে না'
                              : 'নেট নেই'
                    }}
                </span>
                <Button
                    variant="secondary"
                    :disabled="syncing || !pending"
                    @click="send"
                >
                    <component
                        :is="syncing ? RefreshCw : CloudUpload"
                        :class="syncing && 'animate-spin'"
                    />
                    পাঠান ({{ pending }})
                </Button>
            </template>
        </PageTitle>

        <p
            v-if="lastOutcome && lastOutcome !== 'nothing'"
            :class="[
                'mb-4 rounded-lg p-3 text-sm',
                lastOutcome === 'ok' ? 'bg-paddy/10' : 'bg-clay/10',
            ]"
        >
            {{ outcomeText[lastOutcome] }}
        </p>
        <p v-if="saved" class="mb-4 rounded-lg bg-pond/10 p-3 text-sm">
            {{ saved }}
        </p>

        <div class="mb-4 grid grid-cols-3 gap-1.5 sm:max-w-md">
            <button
                type="button"
                :class="[
                    'flex items-center justify-center gap-1.5 rounded-lg border py-2.5 text-sm',
                    tab === 'milk'
                        ? 'border-primary bg-primary text-primary-foreground'
                        : 'border-border bg-card',
                ]"
                @click="tab = 'milk'"
            >
                <Milk class="size-4" /> দুধ
            </button>
            <button
                type="button"
                :class="[
                    'flex items-center justify-center gap-1.5 rounded-lg border py-2.5 text-sm',
                    tab === 'feed'
                        ? 'border-primary bg-primary text-primary-foreground'
                        : 'border-border bg-card',
                ]"
                @click="tab = 'feed'"
            >
                <Wheat class="size-4" /> খাবার
            </button>
            <button
                v-if="canExpense"
                type="button"
                :class="[
                    'flex items-center justify-center gap-1.5 rounded-lg border py-2.5 text-sm',
                    tab === 'expense'
                        ? 'border-primary bg-primary text-primary-foreground'
                        : 'border-border bg-card',
                ]"
                @click="tab = 'expense'"
            >
                <HandCoins class="size-4" /> খরচ
            </button>
        </div>

        <div class="grid gap-6 lg:grid-cols-5">
            <section
                class="min-w-0 rounded-xl border border-border bg-card p-4 lg:col-span-3"
            >
                <form
                    v-if="tab === 'milk'"
                    class="space-y-3"
                    @submit.prevent="save('milk')"
                >
                    <div class="grid grid-cols-2 gap-3">
                        <Field label="তারিখ"
                            ><Input
                                v-model="milk.date"
                                type="date"
                                :max="today()"
                        /></Field>
                        <div class="grid gap-1.5">
                            <span class="text-sm font-medium">বেলা</span>
                            <div
                                class="grid grid-cols-2 gap-1 rounded-md border border-input p-1"
                            >
                                <button
                                    v-for="(label, key) in sessions"
                                    :key="key"
                                    type="button"
                                    :class="[
                                        'flex items-center justify-center gap-1 rounded py-1.5 text-sm',
                                        milk.session === key
                                            ? 'bg-primary text-primary-foreground'
                                            : '',
                                    ]"
                                    @click="milk.session = key"
                                >
                                    <component
                                        :is="key === 'morning' ? Sun : Moon"
                                        class="size-4"
                                    />
                                    {{ label }}
                                </button>
                            </div>
                        </div>
                    </div>
                    <div
                        v-for="(row, index) in milk.rows"
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
                                class="num h-12 pr-10 text-right text-lg"
                                placeholder="0"
                            />
                            <span
                                class="pointer-events-none absolute inset-y-0 right-3 flex items-center text-xs text-muted-foreground"
                                >লি.</span
                            >
                        </div>
                    </div>
                    <Button
                        type="submit"
                        class="h-11 w-full text-base"
                        :disabled="milkTotal <= 0"
                        >রাখুন — {{ qty(milkTotal, 'লিটার') }}</Button
                    >
                </form>

                <form
                    v-if="tab === 'feed'"
                    class="space-y-3"
                    @submit.prevent="save('feed')"
                >
                    <SessionPicker
                        v-model="feed.session"
                        :sessions="feedSessions"
                    />
                    <div class="grid grid-cols-2 gap-3">
                        <Field label="কোন মাল">
                            <NSelect v-model="feed.item_id"
                                ><option
                                    v-for="i in items"
                                    :key="i.id"
                                    :value="i.id"
                                >
                                    {{ i.name }}
                                </option></NSelect
                            >
                        </Field>
                        <Field label="তারিখ"
                            ><Input
                                v-model="feed.date"
                                type="date"
                                :max="today()"
                        /></Field>
                    </div>
                    <p class="rounded-lg bg-muted/60 p-3 text-sm">
                        আনুমানিক স্টক
                        <b class="num">{{
                            qty(localStock, item?.unit_label)
                        }}</b>
                        (না-পাঠানো এন্ট্রি বাদে)
                    </p>
                    <div
                        v-for="(split, index) in feed.splits"
                        :key="index"
                        class="grid grid-cols-[1fr_auto] items-center gap-2 rounded-lg border border-border/70 bg-background/60 p-2"
                    >
                        <NSelect v-model="split.cost_center_id"
                            ><option
                                v-for="c in animalCenters"
                                :key="c.id"
                                :value="c.id"
                            >
                                {{ c.name }}
                            </option></NSelect
                        >
                        <div class="relative w-32">
                            <Input
                                v-model="split.qty"
                                type="number"
                                inputmode="decimal"
                                step="0.001"
                                min="0"
                                class="num h-12 pr-12 text-right text-lg"
                                placeholder="0"
                            />
                            <span
                                class="pointer-events-none absolute inset-y-0 right-3 flex items-center text-xs text-muted-foreground"
                                >{{ item?.unit_label }}</span
                            >
                        </div>
                    </div>
                    <Field label="নোট"><Input v-model="feed.note" /></Field>
                    <p
                        v-if="feedTotal > localStock + 0.0005"
                        class="text-sm text-destructive"
                    >
                        স্টকে এত মাল নেই।
                    </p>
                    <Button
                        type="submit"
                        class="h-11 w-full text-base"
                        :disabled="
                            feedTotal <= 0 || feedTotal > localStock + 0.0005
                        "
                        >রাখুন — {{ qty(feedTotal, item?.unit_label) }}</Button
                    >
                </form>

                <form
                    v-if="tab === 'expense' && canExpense"
                    class="grid gap-3 sm:grid-cols-2"
                    @submit.prevent="save('expense')"
                >
                    <Field label="তারিখ"
                        ><Input
                            v-model="expense.date"
                            type="date"
                            :max="today()"
                    /></Field>
                    <Field label="টাকা কোথা থেকে"
                        ><NSelect v-model="expense.money_account_id"
                            ><option
                                v-for="a in moneyAccounts"
                                :key="a.id"
                                :value="a.id"
                            >
                                {{ a.name }}
                            </option></NSelect
                        ></Field
                    >
                    <Field label="খাত"
                        ><NSelect
                            v-model="expense.account_id"
                            placeholder="বাছুন"
                            ><option
                                v-for="a in expenseAccounts"
                                :key="a.id"
                                :value="a.id"
                            >
                                {{ a.name }}
                            </option></NSelect
                        ></Field
                    >
                    <Field label="ক্যাটাগরি"
                        ><NSelect v-model="expense.cost_center_id"
                            ><option
                                v-for="c in centers"
                                :key="c.id"
                                :value="c.id"
                            >
                                {{ c.name }}
                            </option></NSelect
                        ></Field
                    >
                    <Field label="টাকা (৳)"
                        ><Input
                            v-model="expense.amount"
                            type="number"
                            inputmode="decimal"
                            step="0.01"
                            min="0"
                            class="num h-12 text-lg"
                    /></Field>
                    <Field label="বিবরণ"
                        ><Input v-model="expense.description"
                    /></Field>
                    <p class="text-xs text-muted-foreground sm:col-span-2">
                        রসিদের ছবি নেট এলে খাতা থেকে এন্ট্রি খুলে যোগ করুন।
                    </p>
                    <Button
                        type="submit"
                        class="h-11 text-base sm:col-span-2"
                        :disabled="
                            !expense.account_id ||
                            !(parseFloat(expense.amount) > 0)
                        "
                        >রাখুন</Button
                    >
                </form>
            </section>

            <section
                class="min-w-0 rounded-xl border border-border bg-card lg:col-span-2"
            >
                <h2 class="font-display border-b border-border p-4 text-lg">
                    এই ফোনের এন্ট্রি
                </h2>
                <ul class="divide-y divide-border/70">
                    <li
                        v-for="q in queue"
                        :key="q.uuid"
                        class="flex items-start gap-3 px-4 py-3 text-sm"
                    >
                        <span
                            :class="[
                                'mt-1 size-2.5 shrink-0 rounded-full',
                                {
                                    pending: 'bg-turmeric',
                                    done: 'bg-paddy',
                                    failed: 'bg-destructive',
                                }[q.status],
                            ]"
                        />
                        <div class="min-w-0 flex-1">
                            <p class="truncate">{{ q.label }}</p>
                            <p class="text-xs text-muted-foreground">
                                {{
                                    {
                                        pending: 'পাঠানোর অপেক্ষায়',
                                        done: 'খাতায় উঠেছে',
                                        failed: 'হয়নি',
                                    }[q.status]
                                }}
                                <span v-if="q.number"> · {{ q.number }}</span>
                            </p>
                            <p
                                v-if="q.status === 'failed' && q.message"
                                class="text-xs text-destructive"
                            >
                                {{ q.message }}
                            </p>
                        </div>
                        <button
                            v-if="q.status !== 'done'"
                            type="button"
                            class="rounded p-1 text-muted-foreground hover:bg-accent"
                            aria-label="মুছুন"
                            @click="discard(q.uuid)"
                        >
                            <Trash2 class="size-4" />
                        </button>
                    </li>
                    <li
                        v-if="!queue.length"
                        class="px-4 py-8 text-center text-sm text-muted-foreground"
                    >
                        এখনো কিছু নেই।
                    </li>
                </ul>
            </section>
        </div>
    </div>
</template>
