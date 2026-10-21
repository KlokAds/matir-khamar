<script setup lang="ts">
import { ask } from '@/lib/dialog';
import { t } from '@/lib/i18n';
import { Head, router, useForm, usePage } from '@inertiajs/vue3';
import { Plus, ShieldCheck, Trash2, UserRound } from '@lucide/vue';
import { computed, reactive, ref } from 'vue';
import Field from '@/components/khamar/Field.vue';
import PageTitle from '@/components/khamar/PageTitle.vue';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';

defineOptions({
    layout: { breadcrumbs: [{ title: 'রোল ও অনুমতি', href: '/setup/roles' }] },
});

type RoleRow = {
    id: number;
    key: string;
    name: string;
    abilities: string[];
    requires_two_factor: boolean;
    is_system: boolean;
    is_admin: boolean;
    users: number;
};

const props = defineProps<{
    roles: RoleRow[];
    abilities: Record<string, string>;
}>();

const page = usePage();
const error = computed(
    () => (page.props.errors as Record<string, string>).role,
);

// One editable copy per role; each saves on its own.
const drafts = reactive(
    Object.fromEntries(
        props.roles.map((role) => [
            role.id,
            {
                name: role.name,
                abilities: [...role.abilities],
                requires_two_factor: role.requires_two_factor,
            },
        ]),
    ),
);

const changed = (role: RoleRow) => {
    const d = drafts[role.id];

    return (
        d.name !== role.name ||
        d.requires_two_factor !== role.requires_two_factor ||
        [...d.abilities].sort().join() !== [...role.abilities].sort().join()
    );
};

function toggle(role: RoleRow, ability: string) {
    if (role.is_admin) {
        return;
    }

    const list = drafts[role.id].abilities;
    const at = list.indexOf(ability);
    if (at === -1) {
        list.push(ability);
    } else {
        list.splice(at, 1);
    }
}

const save = (role: RoleRow) =>
    router.put(`/setup/roles/${role.id}`, drafts[role.id], {
        preserveScroll: true,
    });

const remove = async (role: RoleRow) => {
    if (
        await ask.confirm({
            title: t('":name" রোল মুছবেন?', { name: t(role.name) }),
            confirmText: t('মুছুন'),
            danger: true,
        })
    ) {
        router.delete(`/setup/roles/${role.id}`, { preserveScroll: true });
    }
};

const adding = ref(false);
const create = useForm({
    name: '',
    abilities: ['view-reports'] as string[],
    requires_two_factor: true,
});
</script>

<template>
    <Head :title="t('রোল ও অনুমতি')" />

    <div class="flex flex-1 flex-col p-4 md:p-6">
        <PageTitle
            :title="t('রোল ও অনুমতি')"
            :subtitle="
                t(
                    'কোন রোল কী করতে পারবে টিক দিয়ে ঠিক করুন। হিসাব অ্যাডমিন সবসময় সব পারে, যাতে কেউ খামারকে তালাবন্ধ করতে না পারে। সুপার অ্যাডমিন (আপনি) এই তালিকার বাইরে।',
                )
            "
        >
            <template #actions>
                <Button @click="adding = !adding"
                    ><Plus /> {{ t('নতুন রোল') }}</Button
                >
            </template>
        </PageTitle>

        <p
            v-if="error"
            class="mb-4 rounded-lg bg-destructive/10 p-3 text-sm text-destructive"
        >
            {{ error }}
        </p>

        <form
            v-if="adding"
            class="mb-5 rounded-xl border border-border bg-card p-4"
            @submit.prevent="
                create.post('/setup/roles', {
                    preserveScroll: true,
                    onSuccess: () => {
                        adding = false;
                        create.reset();
                    },
                })
            "
        >
            <div class="grid gap-3 sm:max-w-sm">
                <Field :label="t('রোলের নাম')" :error="create.errors.name">
                    <Input
                        v-model="create.name"
                        required
                        :placeholder="t('যেমন: হিসাবরক্ষক, পশু ডাক্তার')"
                    />
                </Field>
            </div>
            <div class="mt-4 grid gap-2 sm:grid-cols-2">
                <label
                    v-for="(label, key) in abilities"
                    :key="key"
                    class="flex items-start gap-2 rounded-lg border border-border/70 p-2.5 text-sm"
                >
                    <input
                        v-model="create.abilities"
                        type="checkbox"
                        :value="key"
                        class="mt-1 size-4 accent-[var(--primary)]"
                    />
                    <span>{{ t(label) }}</span>
                </label>
            </div>
            <label class="mt-3 flex items-center gap-2 text-sm">
                <input
                    v-model="create.requires_two_factor"
                    type="checkbox"
                    class="size-4 accent-[var(--primary)]"
                />
                {{ t('২-ধাপ যাচাই (2FA) বাধ্যতামূলক') }}
            </label>
            <div class="mt-4 flex gap-2">
                <Button type="submit" :disabled="create.processing">{{
                    t('তৈরি করুন')
                }}</Button>
                <Button type="button" variant="ghost" @click="adding = false">{{
                    t('বাদ')
                }}</Button>
            </div>
        </form>

        <div class="grid gap-4 xl:grid-cols-2">
            <section
                v-for="role in roles"
                :key="role.id"
                class="min-w-0 rounded-xl border border-border bg-card p-4"
            >
                <div class="flex flex-wrap items-center justify-between gap-2">
                    <Input
                        v-model="drafts[role.id].name"
                        class="max-w-64 font-medium"
                    />
                    <div
                        class="flex items-center gap-3 text-xs text-muted-foreground"
                    >
                        <span class="flex items-center gap-1"
                            ><UserRound class="size-3.5" />
                            {{ t(':n জন', { n: role.users }) }}</span
                        >
                        <span
                            v-if="role.is_system"
                            class="rounded bg-muted px-1.5 py-0.5"
                            >{{ t('শুরু থেকে') }}</span
                        >
                        <button
                            v-else
                            type="button"
                            class="rounded p-1 hover:bg-accent hover:text-destructive"
                            :aria-label="t('মুছুন')"
                            @click="remove(role)"
                        >
                            <Trash2 class="size-4" />
                        </button>
                    </div>
                </div>

                <div class="mt-3 grid gap-1.5">
                    <label
                        v-for="(label, key) in abilities"
                        :key="key"
                        :class="[
                            'flex items-start gap-2 rounded-md px-2 py-1.5 text-sm',
                            role.is_admin
                                ? 'opacity-70'
                                : 'cursor-pointer hover:bg-accent/50',
                        ]"
                    >
                        <input
                            type="checkbox"
                            :checked="
                                drafts[role.id].abilities.includes(String(key))
                            "
                            :disabled="role.is_admin"
                            class="mt-1 size-4 accent-[var(--primary)]"
                            @change="toggle(role, String(key))"
                        />
                        <span>{{ t(label) }}</span>
                    </label>
                </div>

                <label
                    class="mt-3 flex items-center gap-2 border-t border-dashed border-border pt-3 text-sm"
                >
                    <input
                        v-model="drafts[role.id].requires_two_factor"
                        type="checkbox"
                        class="size-4 accent-[var(--primary)]"
                    />
                    <ShieldCheck class="size-4 text-paddy" />
                    {{ t('২-ধাপ যাচাই (2FA) বাধ্যতামূলক') }}
                </label>

                <div class="mt-3 flex justify-end">
                    <Button
                        size="sm"
                        :disabled="!changed(role)"
                        @click="save(role)"
                        >{{ t('রাখুন') }}</Button
                    >
                </div>
            </section>
        </div>
    </div>
</template>
