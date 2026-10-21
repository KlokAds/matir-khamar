<script setup lang="ts">
import { Head, Link, router, useForm, usePage } from '@inertiajs/vue3';
import { CloudDownload, KeyRound, ListChecks, PlugZap, Send, ShieldAlert, ShieldCheck } from '@lucide/vue';
import { computed, reactive, ref } from 'vue';
import Field from '@/components/khamar/Field.vue';
import NSelect from '@/components/khamar/NSelect.vue';
import PageTitle from '@/components/khamar/PageTitle.vue';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { centerLabel, day, monthLabel, qty, taka } from '@/lib/khamar';
import type { Center } from '@/lib/khamar';

defineOptions({
    layout: { breadcrumbs: [{ title: 'বিলিং থেকে আনা', href: '/setup/import' }] },
});

type Mapping = { id: number; kind: string; external_id: string; external_name: string; account_id: number | null; cost_center_id: number | null; item_id: number | null; ignore: boolean; complete: boolean };

type ConnectionTest = { ok: boolean; message: string; can_write: boolean | null; counts: Record<string, number> };

const props = defineProps<{
    configured: boolean;
    connection: { source: 'app' | 'env' | 'none'; host: string; port: string; database: string; username: string; company_id: string; password_set: boolean; updated_at: string | null };
    connectionTest: ConnectionTest | null;
    status: string | null;
    statuses: Record<string, string>;
    counts: Record<string, { n: number; total: number }>;
    mappings: Mapping[];
    kinds: Record<string, string>;
    accounts: Record<string, { id: number; code: string; name: string }[]>;
    centers: Center[];
    items: { id: number; name: string; unit_label: string }[];
    records: {
        data: { id: number; table: string; external_id: string; number: string | null; date: string; amount: number; qty: number | null; category_id: number | null; notes: string; status: string; problem: string | null; entry_id: number | null }[];
        links: { url: string | null; label: string; active: boolean }[];
    };
    reconcile: { table: string; month: string; rows: number; crater: number; books: number; difference: number }[];
}>();

const page = usePage();
const errors = computed(() => Object.values((page.props.errors ?? {}) as Record<string, string>));

// One editable copy per mapping row; saved one row at a time.
const edits = reactive(Object.fromEntries(props.mappings.map((m) => [m.id, { target: m.ignore ? 'ignore' : m.item_id ? 'stock' : 'account', account_id: m.account_id, cost_center_id: m.cost_center_id, item_id: m.item_id }])));
const grouped = computed(() => Object.keys(props.kinds).map((kind) => ({ kind, rows: props.mappings.filter((m) => m.kind === kind) })).filter((g) => g.rows.length));
const undecided = computed(() => props.mappings.filter((m) => !m.complete && m.kind !== 'payment_method').length);
const usable = computed(() => props.centers.filter((c) => c.kind !== 'group'));
const qtyDraft = reactive<Record<number, string>>({});

function save(map: Mapping) {
    const e = edits[map.id];
    router.put(`/setup/import/mappings/${map.id}`, {
        ignore: e.target === 'ignore',
        account_id: e.target === 'account' ? e.account_id : null,
        cost_center_id: e.target === 'account' ? e.cost_center_id : null,
        item_id: e.target === 'stock' ? e.item_id : null,
    }, { preserveScroll: true });
}

const action = useForm({});
const showConnection = ref(props.connection.source === 'none');
const conn = useForm({
    host: props.connection.host || 'localhost',
    port: props.connection.port || '3306',
    database: props.connection.database,
    username: props.connection.username,
    password: '',
    company_id: props.connection.company_id,
    current_password: '',
});
const saveConnection = () => conn.put('/setup/import/connection', { preserveScroll: true, onSuccess: () => conn.reset('password', 'current_password') });
const forget = useForm({ current_password: '' });
const forgetConnection = () => {
    if (window.confirm('বিলিং সংযোগের তথ্য মুছে ফেলবেন? আগে আনা সারিগুলো থাকবে।')) {
        forget.delete('/setup/import/connection', { preserveScroll: true });
    }
};
const tables: Record<string, string> = { expenses: 'খরচ', payments: 'পেমেন্ট', expense_categories: 'খরচের ক্যাটাগরি', customers: 'ক্রেতা' };
const run = (url: string) => action.post(url, { preserveScroll: true });
const saveQty = (id: number) => router.post(`/setup/import/records/${id}/qty`, { qty: qtyDraft[id] }, { preserveScroll: true });
const resolve = (id: number, value: 'repost' | 'keep') => router.post(`/setup/import/records/${id}/resolve`, { action: value }, { preserveScroll: true });
const stockCategoryIds = computed(() => props.mappings.filter((m) => m.kind === 'expense_category' && m.item_id).map((m) => Number(m.external_id)));
</script>

<template>
    <Head title="বিলিং থেকে আনা" />

    <div class="flex flex-1 flex-col p-4 md:p-6">
        <PageTitle title="billing.klokads.com থেকে খামারের হিসাব" subtitle="বিলিং-এ এজেন্সির হিসাবও আছে — শুধু যেগুলো খামারের বলে ম্যাপ করবেন সেগুলোই আসবে, বাকি বাদ। বিলিং-এ কিছু লেখা বা বদলানো হয় না।">
            <template #actions>
                <Button variant="secondary" :disabled="!configured || action.processing" @click="run('/setup/import/fetch')"><CloudDownload /> নতুন সারি আনুন</Button>
                <Button :disabled="action.processing" @click="run('/setup/import/post')"><Send /> খাতায় তুলুন</Button>
            </template>
        </PageTitle>

        <section class="mb-6 min-w-0 rounded-xl border border-border bg-card p-4">
            <div class="flex flex-wrap items-center justify-between gap-3">
                <div class="flex items-center gap-3">
                    <span :class="['rounded-lg p-2', configured ? 'bg-paddy/15 text-paddy' : 'bg-clay/15 text-clay']"><PlugZap class="size-5" /></span>
                    <div>
                        <h2 class="font-display text-lg">বিলিং ডাটাবেসের সংযোগ</h2>
                        <p class="text-sm text-muted-foreground">
                            <template v-if="connection.source === 'app'">{{ connection.username }}@{{ connection.host }} / {{ connection.database }} · পাসওয়ার্ড এনক্রিপ্ট করে রাখা<span v-if="connection.updated_at"> · {{ connection.updated_at }}</span></template>
                            <template v-else-if="connection.source === 'env'">সার্ভারের .env থেকে নেওয়া হচ্ছে</template>
                            <template v-else>এখনো দেওয়া হয়নি</template>
                        </p>
                    </div>
                </div>
                <div class="flex gap-2">
                    <Button v-if="configured" variant="secondary" size="sm" :disabled="action.processing" @click="run('/setup/import/connection/test')">সংযোগ পরীক্ষা</Button>
                    <Button variant="secondary" size="sm" @click="showConnection = !showConnection"><KeyRound /> {{ connection.source === 'app' ? 'বদলান' : 'তথ্য দিন' }}</Button>
                </div>
            </div>

            <div v-if="connectionTest" :class="['mt-4 rounded-lg p-3 text-sm', connectionTest.ok ? 'bg-paddy/10' : 'bg-destructive/10 text-destructive']">
                <p class="font-medium">{{ connectionTest.message }}</p>
                <p v-if="connectionTest.ok" class="mt-1 text-muted-foreground">
                    বিলিং-এ আছে: <span v-for="(n, t) in connectionTest.counts" :key="t" class="mr-3">{{ tables[t] ?? t }} <b class="num text-foreground">{{ n }}</b></span>
                </p>
                <p v-if="connectionTest.can_write === true" class="mt-2 flex items-start gap-2 text-clay"><ShieldAlert class="mt-0.5 size-4 shrink-0" /> এই ডাটাবেস ইউজারের লেখার/মোছার অনুমতি আছে। এই অ্যাপ শুধু পড়ে, তবু নিরাপত্তার জন্য hPanel-এ শুধু SELECT অনুমতির আলাদা ইউজার বানিয়ে সেটা দিন।</p>
                <p v-if="connectionTest.can_write === false" class="mt-2 flex items-center gap-2 text-paddy"><ShieldCheck class="size-4" /> ইউজারটির শুধু পড়ার অনুমতি — ঠিক আছে।</p>
            </div>

            <form v-if="showConnection" class="mt-4 grid gap-3 border-t border-border pt-4 sm:grid-cols-2 lg:grid-cols-3" autocomplete="off" @submit.prevent="saveConnection">
                <Field label="হোস্ট" :error="conn.errors.host" hint="একই Hostinger সার্ভারে হলে localhost"><Input v-model="conn.host" required /></Field>
                <Field label="পোর্ট" :error="conn.errors.port"><Input v-model="conn.port" type="number" required /></Field>
                <Field label="ডাটাবেসের নাম" :error="conn.errors.database"><Input v-model="conn.database" required /></Field>
                <Field label="ডাটাবেস ইউজার" :error="conn.errors.username" hint="শুধু SELECT অনুমতির ইউজার দিন"><Input v-model="conn.username" required autocomplete="off" /></Field>
                <Field label="ডাটাবেস পাসওয়ার্ড" :error="conn.errors.password" :hint="connection.password_set ? 'রাখা আছে — বদলাতে চাইলেই শুধু লিখুন' : ''">
                    <Input v-model="conn.password" type="password" autocomplete="new-password" :placeholder="connection.password_set ? '••••••••' : ''" :required="!connection.password_set" />
                </Field>
                <Field label="Crater কোম্পানি নম্বর (ঐচ্ছিক)" :error="conn.errors.company_id" hint="খামারের আলাদা কোম্পানি থাকলে শুধু সেটা পড়া হবে"><Input v-model="conn.company_id" type="number" min="1" /></Field>
                <div class="sm:col-span-2 lg:col-span-3">
                    <Field label="নিশ্চিত করতে আপনার নিজের লগইন পাসওয়ার্ড" :error="conn.errors.current_password">
                        <Input v-model="conn.current_password" type="password" autocomplete="current-password" required class="sm:max-w-sm" />
                    </Field>
                </div>
                <div class="flex flex-wrap items-center gap-3 sm:col-span-2 lg:col-span-3">
                    <Button type="submit" :disabled="conn.processing">রাখুন ও পরীক্ষা করুন</Button>
                    <p class="text-xs text-muted-foreground">পাসওয়ার্ড APP_KEY দিয়ে এনক্রিপ্ট হয়ে থাকে, কোনো পাতায় আর দেখানো হয় না।</p>
                </div>
            </form>

            <form v-if="showConnection && connection.source === 'app'" class="mt-3 flex flex-wrap items-end gap-2 border-t border-dashed border-border pt-3" @submit.prevent="forgetConnection">
                <Field label="সংযোগ মুছতে নিজের পাসওয়ার্ড" :error="forget.errors.current_password">
                    <Input v-model="forget.current_password" type="password" autocomplete="current-password" required class="w-56" />
                </Field>
                <Button type="submit" variant="ghost" size="sm" class="text-destructive" :disabled="forget.processing">সংযোগ মুছে ফেলুন</Button>
            </form>
        </section>

        <p v-for="(e, i) in errors" :key="i" class="mb-3 text-sm text-destructive">{{ e }}</p>

        <div class="mb-5 flex flex-wrap gap-2">
            <Link href="/setup/import" :class="['rounded-full border px-3 py-1.5 text-sm', !status ? 'border-primary bg-primary text-primary-foreground' : 'border-border bg-card']">সব</Link>
            <Link v-for="(label, key) in statuses" :key="key" :href="`/setup/import?status=${key}`" :class="['rounded-full border px-3 py-1.5 text-sm', status === key ? 'border-primary bg-primary text-primary-foreground' : 'border-border bg-card']">
                {{ label }} <span class="num opacity-70">{{ counts[key]?.n ?? 0 }}</span>
            </Link>
        </div>

        <section class="mb-6 rounded-xl border border-border bg-card p-4">
            <div class="mb-3 flex flex-wrap items-center justify-between gap-2">
                <h2 class="font-display text-lg">১. কোনটা খামারের, কোথায় যাবে</h2>
                <Button v-if="undecided" size="sm" variant="secondary" @click="run('/setup/import/ignore-undecided')"><ListChecks /> বাকি {{ undecided }}টি "খামারের নয়"</Button>
            </div>
            <p v-if="!mappings.length" class="text-sm text-muted-foreground">প্রথমে "নতুন সারি আনুন" চাপুন — বিলিং-এর ক্যাটাগরিগুলো এখানে চলে আসবে।</p>

            <div v-for="group in grouped" :key="group.kind" class="mb-5 last:mb-0">
                <p class="mb-2 text-xs font-medium tracking-wide text-muted-foreground uppercase">{{ kinds[group.kind] }}</p>
                <div class="space-y-2">
                    <div v-for="m in group.rows" :key="m.id" :class="['grid items-center gap-2 rounded-lg border p-2.5 text-sm md:grid-cols-[14rem_10rem_1fr_1fr_auto]', m.complete ? 'border-border' : 'border-turmeric bg-turmeric/10']">
                        <span class="font-medium">{{ m.external_name }}</span>
                        <NSelect v-model="edits[m.id].target">
                            <template v-if="group.kind === 'payment_method'"><option value="account">এই হিসাবে</option></template>
                            <template v-else>
                                <option value="account">খাত + ক্যাটাগরি</option>
                                <option v-if="group.kind === 'expense_category'" value="stock">স্টকে (কেজি)</option>
                                <option value="ignore">খামারের নয়</option>
                            </template>
                        </NSelect>
                        <template v-if="edits[m.id].target === 'account'">
                            <NSelect v-model="edits[m.id].account_id" placeholder="খাত বাছুন">
                                <option v-for="a in accounts[group.kind]" :key="a.id" :value="a.id">{{ a.name }}</option>
                            </NSelect>
                            <NSelect v-if="group.kind !== 'payment_method'" v-model="edits[m.id].cost_center_id" placeholder="ক্যাটাগরি">
                                <option v-for="c in usable" :key="c.id" :value="c.id">{{ centerLabel(c) }}</option>
                            </NSelect>
                            <span v-else />
                        </template>
                        <template v-else-if="edits[m.id].target === 'stock'">
                            <NSelect v-model="edits[m.id].item_id" placeholder="কোন মাল">
                                <option v-for="i in items" :key="i.id" :value="i.id">{{ i.name }}</option>
                            </NSelect>
                            <span class="text-xs text-muted-foreground">প্রতি সারিতে পরিমাণ লিখতে হবে; পরে কেজি অনুযায়ী ক্যাটাগরিতে যাবে</span>
                        </template>
                        <template v-else><span class="text-xs text-muted-foreground md:col-span-2">এগুলো আসবে না</span></template>
                        <Button size="sm" variant="secondary" @click="save(m)">রাখুন</Button>
                    </div>
                </div>
            </div>
        </section>

        <section class="mb-6 rounded-xl border border-border bg-card">
            <h2 class="border-b border-border p-4 font-display text-lg">২. বিলিং-এর সারি (তারিখের ক্রমে খাতায় ওঠে)</h2>
            <div class="overflow-x-auto">
                <table class="w-full min-w-[760px] text-sm">
                    <tbody>
                        <tr v-for="r in records.data" :key="r.id" class="border-b border-border/60">
                            <td class="px-4 py-2.5 whitespace-nowrap text-muted-foreground">{{ day(r.date) }}</td>
                            <td class="px-4 py-2.5">
                                {{ r.table === 'expenses' ? 'খরচ' : 'পেমেন্ট' }} {{ r.number ?? '#' + r.external_id }}
                                <p class="line-clamp-1 text-xs text-muted-foreground">{{ r.notes }}</p>
                            </td>
                            <td class="num px-4 py-2.5 text-right">{{ taka(r.amount) }}</td>
                            <td class="px-4 py-2.5">
                                <span :class="['rounded px-1.5 py-0.5 text-[11px]', { pending: 'bg-turmeric/25', posted: 'bg-paddy/15 text-paddy', ignored: 'bg-muted text-muted-foreground', changed: 'bg-destructive/10 text-destructive' }[r.status]]">{{ statuses[r.status] }}</span>
                                <p v-if="r.problem" class="mt-0.5 text-xs text-clay">{{ r.problem }}</p>
                            </td>
                            <td class="px-4 py-2.5">
                                <Link v-if="r.entry_id" :href="`/entries/${r.entry_id}`" class="text-primary hover:underline">এন্ট্রি</Link>
                                <form v-else-if="r.status === 'pending' && r.table === 'expenses' && stockCategoryIds.includes(Number(r.category_id))" class="flex items-center gap-1" @submit.prevent="saveQty(r.id)">
                                    <Input v-model="qtyDraft[r.id]" type="number" step="0.001" min="0" class="num h-8 w-24" :placeholder="r.qty ? qty(r.qty) : 'কেজি'" />
                                    <Button size="sm" variant="secondary" type="submit">রাখুন</Button>
                                </form>
                                <div v-if="r.status === 'changed'" class="mt-1 flex gap-1">
                                    <Button size="sm" variant="secondary" @click="resolve(r.id, 'repost')">আবার তুলুন</Button>
                                    <Button size="sm" variant="ghost" @click="resolve(r.id, 'keep')">আগেরটা ঠিক</Button>
                                </div>
                            </td>
                        </tr>
                        <tr v-if="!records.data.length"><td class="px-4 py-8 text-center text-muted-foreground">কোনো সারি নেই।</td></tr>
                    </tbody>
                </table>
            </div>
            <nav v-if="records.links.length > 3" class="flex flex-wrap gap-1 p-3">
                <Link v-for="link in records.links" :key="link.label" :href="link.url ?? ''" :class="['rounded border px-2.5 py-1 text-sm', link.active ? 'border-primary bg-primary text-primary-foreground' : 'border-border', !link.url && 'pointer-events-none opacity-40']" preserve-scroll><span v-html="link.label" /></Link>
            </nav>
        </section>

        <section class="min-w-0 rounded-xl border border-border bg-card p-4">
            <h2 class="mb-1 font-display text-lg">৩. মিলিয়ে দেখা</h2>
            <p class="mb-3 text-sm text-muted-foreground">বিলিং-এ আজ যা আছে বনাম খাতায় যা উঠেছে। পার্থক্য থাকলে বিলিং-এ পরে কিছু বদলানো হয়েছে।</p>
            <table class="w-full text-sm">
                <thead class="text-left text-xs text-muted-foreground">
                    <tr class="border-b border-border"><th class="pb-2 font-medium">মাস</th><th class="pb-2 font-medium">ধরন</th><th class="pb-2 text-right font-medium">সারি</th><th class="pb-2 text-right font-medium">বিলিং</th><th class="pb-2 text-right font-medium">খাতা</th><th class="pb-2 text-right font-medium">পার্থক্য</th></tr>
                </thead>
                <tbody>
                    <tr v-for="row in reconcile" :key="row.table + row.month" class="border-b border-border/60">
                        <td class="py-2">{{ monthLabel(row.month) }}</td>
                        <td class="py-2">{{ row.table === 'expenses' ? 'খরচ' : 'পেমেন্ট' }}</td>
                        <td class="num py-2 text-right">{{ row.rows }}</td>
                        <td class="num py-2 text-right">{{ taka(row.crater) }}</td>
                        <td class="num py-2 text-right">{{ taka(row.books) }}</td>
                        <td :class="['num py-2 text-right font-medium', row.difference !== 0 ? 'text-destructive' : 'text-paddy']">{{ row.difference === 0 ? '✓ মিলেছে' : taka(row.difference) }}</td>
                    </tr>
                    <tr v-if="!reconcile.length"><td colspan="6" class="py-6 text-center text-muted-foreground">এখনো কিছু খাতায় ওঠেনি।</td></tr>
                </tbody>
            </table>
        </section>
    </div>
</template>
