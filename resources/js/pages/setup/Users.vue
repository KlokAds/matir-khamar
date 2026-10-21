<script setup lang="ts">
import { ask } from '@/lib/dialog';
import { Head, router, useForm } from '@inertiajs/vue3';
import { ShieldCheck, ShieldOff, UserPlus } from '@lucide/vue';
import { ref } from 'vue';
import Field from '@/components/khamar/Field.vue';
import NSelect from '@/components/khamar/NSelect.vue';
import PageTitle from '@/components/khamar/PageTitle.vue';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';

defineOptions({
    layout: { breadcrumbs: [{ title: 'ব্যবহারকারী', href: '/setup/users' }] },
});

defineProps<{
    users: {
        id: number;
        name: string;
        email: string;
        role: string;
        role_label: string;
        is_active: boolean;
        two_factor: boolean;
        last_login_at: string | null;
        last_login_ip: string | null;
    }[];
    roles: Record<string, string>;
}>();

const adding = ref(false);
const create = useForm({ name: '', email: '', role: 'entry', password: '' });

function update(
    user: { id: number; role: string; is_active: boolean },
    changes: Record<string, unknown>,
) {
    router.put(
        `/setup/users/${user.id}`,
        { role: user.role, is_active: user.is_active, ...changes },
        { preserveScroll: true },
    );
}

async function resetPassword(user: {
    id: number;
    role: string;
    is_active: boolean;
}) {
    const password = await ask.prompt({
        title: 'নতুন পাসওয়ার্ড',
        body: 'অক্ষর ও সংখ্যা মিলিয়ে। ব্যবহারকারীকে নিজে জানিয়ে দিন।',
        label: 'পাসওয়ার্ড',
        inputType: 'password',
        minLength: 10,
        confirmText: 'বদলান',
    });

    if (password) {
        update(user, { password });
    }
}
</script>

<template>
    <Head title="ব্যবহারকারী" />

    <div class="flex flex-1 flex-col p-4 md:p-6">
        <PageTitle
            title="কে কী করতে পারবেন"
            subtitle="অ্যাডমিন ও ম্যানেজারের ২-ধাপ যাচাই (2FA) চালু না করলে হিসাব খুলবে না। কাউকে মোছা যায় না — বন্ধ করা যায়, যাতে তাঁর লেখা এন্ট্রির ইতিহাস থাকে।"
        >
            <template #actions
                ><Button @click="adding = !adding"
                    ><UserPlus /> নতুন ব্যবহারকারী</Button
                ></template
            >
        </PageTitle>

        <form
            v-if="adding"
            class="mb-5 grid gap-3 rounded-xl border border-border bg-card p-4 sm:grid-cols-2 lg:grid-cols-5 lg:items-end"
            @submit.prevent="
                create.post('/setup/users', {
                    onSuccess: () => {
                        adding = false;
                        create.reset();
                    },
                })
            "
        >
            <Field label="নাম" :error="create.errors.name"
                ><Input v-model="create.name" required
            /></Field>
            <Field label="ইমেইল" :error="create.errors.email"
                ><Input v-model="create.email" type="email" required
            /></Field>
            <Field label="ভূমিকা"
                ><NSelect v-model="create.role"
                    ><option
                        v-for="(label, key) in roles"
                        :key="key"
                        :value="key"
                    >
                        {{ label }}
                    </option></NSelect
                ></Field
            >
            <Field label="প্রথম পাসওয়ার্ড" :error="create.errors.password"
                ><Input
                    v-model="create.password"
                    type="password"
                    required
                    autocomplete="new-password"
            /></Field>
            <Button type="submit" :disabled="create.processing"
                >যোগ করুন</Button
            >
        </form>

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
                            {{ u.email }}
                        </p>
                        <p class="mt-1 text-xs text-muted-foreground">
                            শেষ লগইন: {{ u.last_login_at ?? 'কখনো না'
                            }}<span v-if="u.last_login_ip">
                                · {{ u.last_login_ip }}</span
                            >
                        </p>
                    </div>
                    <span
                        :class="[
                            'flex items-center gap-1 rounded-full px-2 py-1 text-xs',
                            u.two_factor
                                ? 'bg-paddy/15 text-paddy'
                                : 'bg-clay/15 text-clay',
                        ]"
                    >
                        <component
                            :is="u.two_factor ? ShieldCheck : ShieldOff"
                            class="size-3.5"
                        />
                        {{ u.two_factor ? '2FA চালু' : '2FA নেই' }}
                    </span>
                </div>
                <div class="mt-3 flex flex-wrap items-center gap-2">
                    <NSelect
                        :model-value="u.role"
                        class="w-auto"
                        @update:model-value="(role) => update(u, { role })"
                    >
                        <option
                            v-for="(label, key) in roles"
                            :key="key"
                            :value="key"
                        >
                            {{ label }}
                        </option>
                    </NSelect>
                    <Button
                        size="sm"
                        variant="secondary"
                        @click="update(u, { is_active: !u.is_active })"
                        >{{ u.is_active ? 'বন্ধ করুন' : 'চালু করুন' }}</Button
                    >
                    <Button size="sm" variant="ghost" @click="resetPassword(u)"
                        >পাসওয়ার্ড বদল</Button
                    >
                    <Button
                        v-if="u.two_factor"
                        size="sm"
                        variant="ghost"
                        @click="update(u, { reset_two_factor: true })"
                        >2FA রিসেট (ফোন হারালে)</Button
                    >
                </div>
            </div>
        </div>
    </div>
</template>
