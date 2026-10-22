<script setup lang="ts">
import { Head, router, useForm } from '@inertiajs/vue3';
import { HandCoins, Pencil, Plus, TriangleAlert, X } from '@lucide/vue';
import { computed, ref } from 'vue';
import DateField from '@/components/khamar/DateField.vue';
import Field from '@/components/khamar/Field.vue';
import NSelect from '@/components/khamar/NSelect.vue';
import PageTitle from '@/components/khamar/PageTitle.vue';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { ask } from '@/lib/dialog';
import { t } from '@/lib/i18n';
import { centerLabel, day, taka, today } from '@/lib/khamar';
import type { Center } from '@/lib/khamar';

defineOptions({
    layout: {
        breadcrumbs: [{ title: 'বিনিয়োগকারী', href: '/setup/investors' }],
    },
});

type Grant = {
    id: number;
    center_id: number;
    center: string;
    party_id: number | null;
    party: string | null;
    share_percent: number | null;
    since: string | null;
};

type InvestorUser = {
    id: number;
    name: string;
    email: string;
    is_active: boolean;
    role_label: string;
    can_view: boolean;
    capital: number;
    grants: Grant[];
};

const props = defineProps<{
    users: InvestorUser[];
    parties: { id: number; name: string }[];
    centers: Center[];
    shareTaken: Record<string, number>;
}>();

const open = ref(false);
const form = useForm<{
    user_id: number | null;
    cost_center_id: number | null;
    party_id: number | null;
    share_percent: string;
    since: string;
}>({
    user_id: null,
    cost_center_id: null,
    party_id: null,
    share_percent: '',
    since: '',
});

const selectedUser = computed(() =>
    props.users.find((u) => u.id === form.user_id),
);

/** Share already given to others on the chosen project. */
const takenByOthers = computed(() => {
    if (!form.cost_center_id) {
        return 0;
    }

    const total = props.shareTaken[String(form.cost_center_id)] ?? 0;
    const mine =
        selectedUser.value?.grants.find(
            (g) => g.center_id === form.cost_center_id,
        )?.share_percent ?? 0;

    return Math.round((total - mine) * 100) / 100;
});

function start(user?: InvestorUser, grant?: Grant) {
    form.clearErrors();
    form.user_id = user?.id ?? null;
    form.cost_center_id = grant?.center_id ?? null;
    form.party_id = grant?.party_id ?? user?.grants[0]?.party_id ?? null;
    form.share_percent =
        grant?.share_percent !== null && grant?.share_percent !== undefined
            ? String(grant.share_percent)
            : '';
    form.since = grant?.since ?? '';
    open.value = true;
}

function save() {
    form.post('/setup/investors', {
        preserveScroll: true,
        onSuccess: () => {
            open.value = false;
            form.reset();
        },
    });
}

async function revoke(user: InvestorUser, grant: Grant) {
    const ok = await ask.confirm({
        title: t('":project" আর দেখাবেন না?', { project: grant.center }),
        body: t(
            ':name এই প্রজেক্টের হিসাব আর দেখতে পারবেন না। খাতার কোনো এন্ট্রি বদলাবে না।',
            {
                name: user.name,
            },
        ),
        confirmText: t('সরিয়ে দিন'),
        danger: true,
    });

    if (ok) {
        router.delete(`/setup/investors/${grant.id}`, { preserveScroll: true });
    }
}
</script>

<template>
    <Head :title="t('বিনিয়োগকারী')" />

    <div class="flex flex-1 flex-col p-4 md:p-6">
        <PageTitle
            :title="t('বিনিয়োগকারী কোন প্রজেক্ট দেখবেন')"
            :subtitle="
                t(
                    'বিনিয়োগকারী লগইন করে শুধু এখানে দেওয়া প্রজেক্টের আয়, খরচ, লাভ, তাঁর মূলধন ও ভাগ দেখবেন — খামারের আর কিছু নয়।',
                )
            "
        >
            <template #actions>
                <Button @click="start()"
                    ><Plus /> {{ t('প্রজেক্ট দেখতে দিন') }}</Button
                >
            </template>
        </PageTitle>

        <form
            v-if="open"
            class="mb-5 grid gap-3 rounded-xl border border-border bg-card p-4 sm:grid-cols-2 lg:grid-cols-3"
            @submit.prevent="save"
        >
            <Field :label="t('কার লগইন')" :error="form.errors.user_id">
                <NSelect
                    v-model="form.user_id"
                    :placeholder="t('বেছে নিন')"
                    required
                >
                    <option v-for="u in users" :key="u.id" :value="u.id">
                        {{ u.name }} ({{ u.email }})
                    </option>
                </NSelect>
            </Field>
            <Field
                :label="t('প্রজেক্ট / ক্যাটাগরি')"
                :error="form.errors.cost_center_id"
                :hint="t('নিচের সব উপ-ক্যাটাগরিও এতে ধরা হবে।')"
            >
                <NSelect
                    v-model="form.cost_center_id"
                    :placeholder="t('বেছে নিন')"
                    required
                >
                    <option v-for="c in centers" :key="c.id" :value="c.id">
                        {{ centerLabel(c) }}
                    </option>
                </NSelect>
            </Field>
            <Field
                :label="t('পার্টি (মূলধনের হিসাব)')"
                :error="form.errors.party_id"
                :hint="t('এই পার্টির বিনিয়োগ ও উত্তোলন তাঁর পাতায় দেখাবে।')"
            >
                <NSelect v-model="form.party_id" :placeholder="t('নেই')">
                    <option :value="null">{{ t('নেই') }}</option>
                    <option v-for="p in parties" :key="p.id" :value="p.id">
                        {{ p.name }}
                    </option>
                </NSelect>
            </Field>
            <Field
                :label="t('লাভে ভাগ (%)')"
                :error="form.errors.share_percent"
                :hint="
                    takenByOthers > 0
                        ? t('অন্যদের দেওয়া আছে :n%', { n: takenByOthers })
                        : t('খালি রাখলে ভাগ দেখাবে না।')
                "
            >
                <Input
                    v-model="form.share_percent"
                    type="number"
                    inputmode="decimal"
                    min="0"
                    max="100"
                    step="0.01"
                />
            </Field>
            <Field
                :label="t('কবে থেকে')"
                :error="form.errors.since"
                :hint="t('দিলে মোট লাভ এই দিন থেকে গোনা হবে।')"
            >
                <DateField v-model="form.since" :max="today()" clearable />
            </Field>
            <div class="flex items-end gap-2">
                <Button type="submit" :disabled="form.processing">{{
                    t('রাখুন')
                }}</Button>
                <Button
                    type="button"
                    variant="secondary"
                    @click="open = false"
                    >{{ t('বাদ দিন') }}</Button
                >
            </div>
        </form>

        <div
            v-if="!users.length"
            class="rounded-xl border border-dashed border-border p-6 text-center text-sm text-muted-foreground"
        >
            {{
                t(
                    'এখনো কোনো বিনিয়োগকারী লগইন নেই। ব্যবহারকারী পাতায় "বিনিয়োগকারী" ভূমিকা দিয়ে একজন যোগ করুন।',
                )
            }}
        </div>

        <div class="grid gap-3 lg:grid-cols-2">
            <div
                v-for="u in users"
                :key="u.id"
                :class="[
                    'rounded-xl border border-border bg-card p-4',
                    !u.is_active && 'opacity-60',
                ]"
            >
                <div class="flex items-start justify-between gap-3">
                    <div class="min-w-0">
                        <p class="font-medium">{{ u.name }}</p>
                        <p class="truncate text-sm text-muted-foreground">
                            {{ u.email }} · {{ u.role_label }}
                        </p>
                    </div>
                    <span
                        class="num flex shrink-0 items-center gap-1 rounded-full bg-paddy/12 px-2.5 py-1 text-xs font-medium text-paddy"
                        ><HandCoins class="size-3.5" />
                        {{ taka(u.capital) }}</span
                    >
                </div>

                <p
                    v-if="!u.can_view"
                    class="mt-2 flex items-center gap-1.5 text-xs text-clay"
                >
                    <TriangleAlert class="size-3.5" />
                    {{
                        t(
                            'এই ভূমিকায় "বিনিয়োগকারীর পাতা দেখা" অনুমতি নেই, তাই পাতা খুলবে না।',
                        )
                    }}
                </p>

                <ul v-if="u.grants.length" class="mt-3 grid gap-2">
                    <li
                        v-for="g in u.grants"
                        :key="g.id"
                        class="flex items-center gap-2 rounded-lg bg-muted/50 px-3 py-2"
                    >
                        <div class="min-w-0 flex-1">
                            <p class="truncate text-sm font-medium">
                                {{ g.center }}
                            </p>
                            <p class="text-xs text-muted-foreground">
                                <span v-if="g.share_percent !== null"
                                    >{{ t('ভাগ :n%', { n: g.share_percent }) }}
                                    ·
                                </span>
                                <span v-if="g.party">{{ g.party }} · </span>
                                <span v-if="g.since">{{
                                    t(':date থেকে', { date: day(g.since) })
                                }}</span>
                            </p>
                        </div>
                        <Button
                            variant="secondary"
                            size="icon"
                            :aria-label="t('বদলান')"
                            :title="t('বদলান')"
                            class="rounded-full hover:text-foreground"
                            @click="start(u, g)"
                            ><Pencil
                        /></Button>
                        <Button
                            variant="secondary"
                            size="icon"
                            :aria-label="t('সরিয়ে দিন')"
                            :title="t('সরিয়ে দিন')"
                            class="rounded-full text-muted-foreground hover:text-destructive"
                            @click="revoke(u, g)"
                            ><X
                        /></Button>
                    </li>
                </ul>
                <p v-else class="mt-3 text-sm text-muted-foreground">
                    {{ t('কোনো প্রজেক্ট দেওয়া হয়নি।') }}
                </p>

                <Button
                    variant="outline"
                    size="sm"
                    class="mt-3"
                    @click="start(u)"
                    ><Plus /> {{ t('প্রজেক্ট যোগ') }}</Button
                >
            </div>
        </div>
    </div>
</template>
