<script setup lang="ts">
import { Head, Link, router, useForm, usePage } from '@inertiajs/vue3';
import {
    ArrowDown,
    ArrowUp,
    Eye,
    Lock,
    Pencil,
    Plus,
    Trash2,
} from '@lucide/vue';
import { computed, ref } from 'vue';
import CenterIcon from '@/components/khamar/CenterIcon.vue';
import Field from '@/components/khamar/Field.vue';
import NSelect from '@/components/khamar/NSelect.vue';
import PageTitle from '@/components/khamar/PageTitle.vue';
import Toggle from '@/components/khamar/Toggle.vue';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { ask } from '@/lib/dialog';
import { centerLabel } from '@/lib/khamar';

/**
 * Every project and category in one list, each row carrying everything that can be done to
 * it: turn on/off (show/hide), edit, move up/down, add one inside, open, delete.
 */
defineOptions({
    layout: {
        breadcrumbs: [
            { title: 'ক্যাটাগরি ও প্রজেক্ট', href: '/centers' },
            { title: 'সাজান ও এডিট', href: '/setup/centers' },
        ],
    },
});

type Row = {
    id: number;
    name: string;
    code: string;
    kind: string;
    kind_label: string;
    holds_animals: boolean;
    depth: number;
    is_active: boolean;
    parent_id: number | null;
    sort: number;
    description: string | null;
    system: boolean;
    entries: number;
    animals: number;
    children: number;
    deletable: boolean;
};

const props = defineProps<{ rows: Row[]; kinds: Record<string, string> }>();

const page = usePage();
const pageError = computed(
    () => (page.props.errors as Record<string, string>)?.center,
);

/* ---------- edit / add ---------- */
const editingId = ref<number | null>(null);
const addingUnder = ref<number | null | 'top'>(null);

const form = useForm({
    name: '',
    code: '',
    kind: 'livestock',
    parent_id: null as number | null,
    holds_animals: false,
    is_active: true,
    sort: 100,
    description: '',
});

function startEdit(row: Row) {
    addingUnder.value = null;
    editingId.value = editingId.value === row.id ? null : row.id;
    form.clearErrors();
    Object.assign(form, {
        name: row.name,
        code: row.code,
        kind: row.kind,
        parent_id: row.parent_id,
        holds_animals: row.holds_animals,
        is_active: row.is_active,
        sort: row.sort,
        description: row.description ?? '',
    });
}

function startAdd(parent: Row | null) {
    editingId.value = null;
    addingUnder.value = parent ? parent.id : 'top';
    form.clearErrors();
    Object.assign(form, {
        name: '',
        code: '',
        kind:
            parent?.kind === 'group'
                ? 'livestock'
                : (parent?.kind ?? 'livestock'),
        parent_id: parent?.id ?? props.rows[0]?.id ?? null,
        holds_animals: false,
        is_active: true,
        sort: 100,
        description: '',
    });
}

const close = () => {
    editingId.value = null;
    addingUnder.value = null;
};

function save() {
    if (editingId.value) {
        form.put(`/setup/centers/${editingId.value}`, {
            preserveScroll: true,
            onSuccess: close,
        });
    } else {
        form.post('/setup/centers', { onSuccess: close });
    }
}

/* ---------- row actions ---------- */
const toggle = (row: Row) =>
    router.post(
        `/setup/centers/${row.id}/toggle`,
        {},
        { preserveScroll: true },
    );

const move = (row: Row, direction: 'up' | 'down') =>
    router.post(
        `/setup/centers/${row.id}/move`,
        { direction },
        { preserveScroll: true },
    );

async function remove(row: Row) {
    if (
        await ask.confirm({
            title: `"${row.name}" মুছবেন?`,
            body: 'এতে কোনো হিসাব নেই, তাই পুরোপুরি মুছে যাবে।',
            confirmText: 'মুছুন',
            danger: true,
        })
    ) {
        router.delete(`/setup/centers/${row.id}`, { preserveScroll: true });
    }
}

const siblings = (row: Row) =>
    props.rows.filter((r) => r.parent_id === row.parent_id);
const isFirst = (row: Row) => siblings(row)[0]?.id === row.id;
const isLast = (row: Row) => siblings(row).at(-1)?.id === row.id;

const whyNoDelete = (row: Row) =>
    row.system
        ? 'অ্যাপের নিজের ক্যাটাগরি — মোছা যায় না'
        : row.children
          ? 'ভেতরে আরও ক্যাটাগরি আছে'
          : 'হিসাব বা গরু আছে — মোছা যায় না, বন্ধ করে লুকান';
</script>

<template>
    <Head title="ক্যাটাগরি সাজান" />

    <div class="flex flex-1 flex-col p-4 md:p-6">
        <PageTitle
            title="প্রজেক্ট ও ক্যাটাগরি — সাজান ও এডিট"
            subtitle="সব কাজ এক জায়গায়: চালু/বন্ধ, নাম বদল, ওপরে-নিচে, ভেতরে নতুন, মোছা।"
        >
            <template #actions>
                <Button size="sm" @click="startAdd(null)"
                    ><Plus /> নতুন প্রজেক্ট</Button
                >
            </template>
        </PageTitle>

        <!-- What each button does -->
        <div
            class="mb-4 grid grid-cols-2 gap-x-4 gap-y-1.5 rounded-xl border border-border bg-card p-3 text-xs text-muted-foreground sm:grid-cols-3 lg:grid-cols-6"
        >
            <span class="flex items-center gap-1.5"
                ><span class="h-3 w-5 rounded-full bg-primary" /> চালু =
                তালিকায় দেখা যায়</span
            >
            <span class="flex items-center gap-1.5"
                ><span class="h-3 w-5 rounded-full bg-input" /> বন্ধ =
                লুকানো</span
            >
            <span class="flex items-center gap-1.5"
                ><Pencil class="size-3.5" /> নাম/ধরন বদল</span
            >
            <span class="flex items-center gap-1.5"
                ><ArrowUp class="size-3.5" /><ArrowDown class="size-3.5" /> ক্রম
                বদল</span
            >
            <span class="flex items-center gap-1.5"
                ><Plus class="size-3.5" /> ভেতরে নতুন</span
            >
            <span class="flex items-center gap-1.5"
                ><Trash2 class="size-3.5" /> মোছা (শুধু খালি হলে)</span
            >
        </div>

        <p
            v-if="pageError"
            class="mb-4 rounded-lg border border-destructive/40 bg-destructive/10 p-3 text-sm"
        >
            {{ pageError }}
        </p>

        <form
            v-if="addingUnder === 'top'"
            class="mb-4 grid gap-3 rounded-xl border border-primary/40 bg-card p-4 sm:grid-cols-2"
            @submit.prevent="save"
        >
            <p class="font-medium sm:col-span-2">নতুন প্রজেক্ট / ক্যাটাগরি</p>
            <Field label="নাম" :error="form.errors.name"
                ><Input
                    v-model="form.name"
                    required
                    placeholder="যেমন: হাঁস পালন"
            /></Field>
            <Field label="ইংরেজি কোড" :error="form.errors.code" hint="যেমন DUCK"
                ><Input
                    v-model="form.code"
                    required
                    class="uppercase"
                    @input="form.code = form.code.toUpperCase()"
            /></Field>
            <Field label="ধরন">
                <NSelect v-model="form.kind">
                    <option
                        v-for="(label, key) in kinds"
                        :key="key"
                        :value="key"
                    >
                        {{ label }}
                    </option>
                </NSelect>
            </Field>
            <Field label="কোনটার ভেতরে" :error="form.errors.parent_id">
                <NSelect v-model="form.parent_id">
                    <option v-for="r in rows" :key="r.id" :value="r.id">
                        {{ centerLabel(r) }}
                    </option>
                </NSelect>
            </Field>
            <label class="flex items-center gap-2 text-sm"
                ><input
                    v-model="form.holds_animals"
                    type="checkbox"
                    class="size-4 accent-[var(--primary)]"
                />
                এখানে গরু থাকবে</label
            >
            <div class="flex gap-2 sm:justify-end">
                <Button type="submit" :disabled="form.processing"
                    >যোগ করুন</Button
                >
                <Button type="button" variant="ghost" @click="close"
                    >বাদ</Button
                >
            </div>
        </form>

        <ul class="space-y-1.5">
            <li v-for="row in rows" :key="row.id">
                <div
                    :class="[
                        'rounded-xl border bg-card p-3 transition-opacity',
                        row.is_active
                            ? 'border-border'
                            : 'border-dashed border-border/80',
                    ]"
                    :style="{
                        marginLeft: `${Math.min(row.depth, 3) * 1.1}rem`,
                    }"
                >
                    <div class="flex flex-wrap items-center gap-x-3 gap-y-2">
                        <div
                            :class="[
                                'flex min-w-0 flex-1 basis-full items-center gap-2.5 sm:basis-auto',
                                !row.is_active && 'opacity-60',
                            ]"
                        >
                            <CenterIcon :kind="row.kind" />
                            <div class="min-w-0">
                                <p
                                    :class="[
                                        'flex items-center gap-1.5 truncate',
                                        row.depth < 2 && 'font-semibold',
                                    ]"
                                >
                                    {{ row.name }}
                                    <Lock
                                        v-if="row.system"
                                        class="size-3 shrink-0 text-muted-foreground"
                                    />
                                    <span
                                        v-if="!row.is_active"
                                        class="rounded bg-muted px-1.5 text-[11px] font-normal text-muted-foreground"
                                        >বন্ধ</span
                                    >
                                </p>
                                <p
                                    class="truncate text-xs text-muted-foreground"
                                >
                                    {{ row.kind_label }} · {{ row.code
                                    }}<template v-if="row.entries">
                                        · {{ row.entries }}টি হিসাব</template
                                    ><template v-if="row.animals">
                                        · {{ row.animals }}টি গরু</template
                                    >
                                </p>
                            </div>
                        </div>

                        <div class="flex w-full items-center gap-1 sm:w-auto">
                            <Toggle
                                :model-value="row.is_active"
                                on-text="চালু"
                                off-text="বন্ধ"
                                @update:model-value="toggle(row)"
                            />
                            <span class="mx-1 h-6 w-px bg-border" />
                            <button
                                type="button"
                                class="act"
                                title="এডিট"
                                @click="startEdit(row)"
                            >
                                <Pencil class="size-4" />
                            </button>
                            <button
                                type="button"
                                class="act"
                                title="ওপরে"
                                :disabled="isFirst(row)"
                                @click="move(row, 'up')"
                            >
                                <ArrowUp class="size-4" />
                            </button>
                            <button
                                type="button"
                                class="act"
                                title="নিচে"
                                :disabled="isLast(row)"
                                @click="move(row, 'down')"
                            >
                                <ArrowDown class="size-4" />
                            </button>
                            <button
                                type="button"
                                class="act"
                                title="ভেতরে নতুন"
                                @click="startAdd(row)"
                            >
                                <Plus class="size-4" />
                            </button>
                            <Link
                                :href="`/centers/${row.id}`"
                                class="act"
                                title="হিসাব দেখুন"
                            >
                                <Eye class="size-4" />
                            </Link>
                            <button
                                type="button"
                                class="act hover:text-destructive"
                                :title="
                                    row.deletable ? 'মুছুন' : whyNoDelete(row)
                                "
                                :disabled="!row.deletable"
                                @click="remove(row)"
                            >
                                <Trash2 class="size-4" />
                            </button>
                        </div>
                    </div>

                    <form
                        v-if="editingId === row.id || addingUnder === row.id"
                        class="mt-3 grid gap-3 border-t border-border/70 pt-3 sm:grid-cols-2"
                        @submit.prevent="save"
                    >
                        <p class="text-sm font-medium sm:col-span-2">
                            {{
                                editingId === row.id
                                    ? 'এডিট করুন'
                                    : `"${row.name}"-এর ভেতরে নতুন`
                            }}
                        </p>
                        <Field label="নাম" :error="form.errors.name"
                            ><Input v-model="form.name" required
                        /></Field>
                        <Field
                            label="ইংরেজি কোড"
                            :error="form.errors.code"
                            :hint="
                                editingId === row.id && row.system
                                    ? 'অ্যাপের নিজের কোড — বদলানো যায় না'
                                    : undefined
                            "
                            ><Input
                                v-model="form.code"
                                required
                                class="uppercase"
                                :disabled="editingId === row.id && row.system"
                                @input="form.code = form.code.toUpperCase()"
                        /></Field>
                        <Field label="ধরন">
                            <NSelect v-model="form.kind">
                                <option
                                    v-for="(label, key) in kinds"
                                    :key="key"
                                    :value="key"
                                >
                                    {{ label }}
                                </option>
                            </NSelect>
                        </Field>
                        <Field
                            label="কোনটার ভেতরে"
                            :error="form.errors.parent_id"
                        >
                            <NSelect v-model="form.parent_id">
                                <option :value="null">— সবার ওপরে —</option>
                                <option
                                    v-for="r in rows.filter(
                                        (x) =>
                                            x.id !== row.id ||
                                            addingUnder === row.id,
                                    )"
                                    :key="r.id"
                                    :value="r.id"
                                >
                                    {{ centerLabel(r) }}
                                </option>
                            </NSelect>
                        </Field>
                        <Field label="বিবরণ (ঐচ্ছিক)" class="sm:col-span-2"
                            ><Input v-model="form.description"
                        /></Field>
                        <label class="flex items-center gap-2 text-sm"
                            ><input
                                v-model="form.holds_animals"
                                type="checkbox"
                                class="size-4 accent-[var(--primary)]"
                            />
                            এখানে গরু থাকবে</label
                        >
                        <p
                            v-if="form.errors.holds_animals"
                            class="text-sm text-destructive sm:col-span-2"
                        >
                            {{ form.errors.holds_animals }}
                        </p>
                        <div class="flex gap-2 sm:justify-end">
                            <Button type="submit" :disabled="form.processing">{{
                                editingId === row.id ? 'সেভ করুন' : 'যোগ করুন'
                            }}</Button>
                            <Button type="button" variant="ghost" @click="close"
                                >বাদ</Button
                            >
                        </div>
                    </form>
                </div>
            </li>
        </ul>
    </div>
</template>

<style scoped>
@reference '../../../css/app.css';

.act {
    @apply flex size-8 items-center justify-center rounded-md text-muted-foreground transition-colors hover:bg-accent hover:text-foreground disabled:pointer-events-none disabled:opacity-30;
}
</style>
