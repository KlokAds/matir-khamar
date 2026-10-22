<script setup lang="ts">
import { Head, Link, router } from '@inertiajs/vue3';
import { Search, X } from '@lucide/vue';
import { reactive, watch } from 'vue';
import DateField from '@/components/khamar/DateField.vue';
import NSelect from '@/components/khamar/NSelect.vue';
import PageTitle from '@/components/khamar/PageTitle.vue';
import { Input } from '@/components/ui/input';
import { t } from '@/lib/i18n';

defineOptions({
    layout: { breadcrumbs: [{ title: 'অডিট লগ', href: '/setup/audit' }] },
});

const props = defineProps<{
    filters: {
        q?: string;
        action?: string;
        user?: number | string;
        from?: string;
        to?: string;
    };
    users: { id: number; name: string }[];
    logs: {
        data: {
            id: number;
            at: string | null;
            user: string | null;
            action: string;
            model: string | null;
            model_id: number | null;
            old: Record<string, unknown> | null;
            new: Record<string, unknown> | null;
            ip: string | null;
        }[];
        links: { url: string | null; label: string; active: boolean }[];
    };
}>();

const actions: Record<string, string> = {
    created: 'তৈরি',
    updated: 'বদল',
    deleted: 'মোছা',
    login: 'লগইন',
    logout: 'লগআউট',
    login_failed: 'ভুল লগইন চেষ্টা',
    period_unlocked: 'মাসের লক খোলা',
};

/* Search and filters: the list follows as you type (a short pause first). */
const filter = reactive({
    q: props.filters.q ?? '',
    action: props.filters.action ?? '',
    user: props.filters.user ? Number(props.filters.user) : null,
    from: props.filters.from ?? '',
    to: props.filters.to ?? '',
});
let timer: ReturnType<typeof setTimeout> | undefined;

watch(filter, () => {
    clearTimeout(timer);
    timer = setTimeout(() => {
        const query = Object.fromEntries(
            Object.entries(filter).filter(([, v]) => v !== '' && v !== null),
        );
        router.get('/setup/audit', query, {
            preserveState: true,
            preserveScroll: true,
            replace: true,
        });
    }, 350);
});

const clear = () =>
    Object.assign(filter, { q: '', action: '', user: null, from: '', to: '' });
const filtered = () =>
    !!(filter.q || filter.action || filter.user || filter.from || filter.to);

const tone = (action: string) =>
    ['login_failed', 'deleted', 'period_unlocked'].includes(action)
        ? 'bg-destructive/10 text-destructive'
        : action === 'updated'
          ? 'bg-turmeric/25'
          : 'bg-muted';
const short = (values: Record<string, unknown> | null) =>
    values
        ? Object.entries(values)
              .filter(
                  ([key]) => !['created_at', 'updated_at', 'id'].includes(key),
              )
              .slice(0, 6)
              .map(
                  ([key, value]) =>
                      `${key}: ${typeof value === 'object' ? JSON.stringify(value) : value}`,
              )
              .join(' · ')
        : '';
</script>

<template>
    <Head :title="t('অডিট লগ')" />

    <div class="flex flex-1 flex-col p-4 md:p-6">
        <PageTitle
            :title="t('অডিট লগ')"
            :subtitle="
                t(
                    'কে, কখন, কোথা থেকে, কী করেছেন — সব লেখা থাকে, এখান থেকে কিছু মোছা যায় না।',
                )
            "
        />

        <div
            class="mb-4 grid gap-2 rounded-xl border border-border bg-card p-3 sm:grid-cols-2 lg:grid-cols-[minmax(0,2fr)_repeat(4,minmax(0,1fr))_auto]"
        >
            <div class="relative sm:col-span-2 lg:col-span-1">
                <Search
                    class="pointer-events-none absolute top-1/2 left-3 size-4 -translate-y-1/2 text-muted-foreground"
                />
                <Input
                    v-model="filter.q"
                    type="search"
                    class="pl-9"
                    :placeholder="t('খুঁজুন: নাম, টাকা, এন্ট্রি নম্বর, IP…')"
                />
            </div>
            <NSelect v-model="filter.action">
                <option value="">{{ t('সব কাজ') }}</option>
                <option v-for="(label, key) in actions" :key="key" :value="key">
                    {{ t(label) }}
                </option>
            </NSelect>
            <NSelect v-model="filter.user">
                <option :value="null">{{ t('সবাই') }}</option>
                <option v-for="u in users" :key="u.id" :value="u.id">
                    {{ u.name }}
                </option>
            </NSelect>
            <DateField
                v-model="filter.from"
                clearable
                :placeholder="t('থেকে')"
            />
            <DateField
                v-model="filter.to"
                clearable
                :placeholder="t('পর্যন্ত')"
            />
            <button
                v-if="filtered()"
                type="button"
                class="btn-chip h-10"
                @click="clear"
            >
                <X /> {{ t('মুছুন') }}
            </button>
        </div>

        <p
            v-if="!logs.data.length"
            class="rounded-xl border border-dashed border-border p-8 text-center text-sm text-muted-foreground"
        >
            {{ t('এই খোঁজে কিছু পাওয়া যায়নি।') }}
        </p>

        <div
            v-else
            class="overflow-x-auto rounded-xl border border-border bg-card"
        >
            <table class="w-full min-w-[820px] text-sm">
                <tbody>
                    <tr
                        v-for="log in logs.data"
                        :key="log.id"
                        class="border-b border-border/60 align-top"
                    >
                        <td
                            class="px-4 py-2.5 whitespace-nowrap text-muted-foreground"
                        >
                            {{ log.at }}
                        </td>
                        <td class="px-4 py-2.5 whitespace-nowrap">
                            {{ log.user ?? t('সিস্টেম') }}
                        </td>
                        <td class="px-4 py-2.5">
                            <span
                                :class="[
                                    'rounded px-1.5 py-0.5 text-[11px]',
                                    tone(log.action),
                                ]"
                                >{{
                                    actions[log.action]
                                        ? t(actions[log.action])
                                        : log.action
                                }}</span
                            >
                        </td>
                        <td class="px-4 py-2.5 whitespace-nowrap">
                            {{ log.model }}
                            <span class="num text-muted-foreground"
                                >#{{ log.model_id }}</span
                            >
                        </td>
                        <td
                            class="max-w-md px-4 py-2.5 text-xs text-muted-foreground"
                        >
                            <p
                                v-if="log.old"
                                class="line-clamp-2 line-through opacity-70"
                            >
                                {{ short(log.old) }}
                            </p>
                            <p v-if="log.new" class="line-clamp-2">
                                {{ short(log.new) }}
                            </p>
                        </td>
                        <td
                            class="num px-4 py-2.5 text-xs text-muted-foreground"
                        >
                            {{ log.ip }}
                        </td>
                    </tr>
                </tbody>
            </table>
        </div>
        <nav v-if="logs.links.length > 3" class="mt-4 flex flex-wrap gap-1">
            <Link
                v-for="link in logs.links"
                :key="link.label"
                :href="link.url ?? ''"
                :class="[
                    'rounded border px-2.5 py-1 text-sm',
                    link.active
                        ? 'border-primary bg-primary text-primary-foreground'
                        : 'border-border bg-card',
                    !link.url && 'pointer-events-none opacity-40',
                ]"
                preserve-scroll
                ><span v-html="link.label"
            /></Link>
        </nav>
    </div>
</template>
