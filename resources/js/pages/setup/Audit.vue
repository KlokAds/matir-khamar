<script setup lang="ts">
import { Head, Link } from '@inertiajs/vue3';
import PageTitle from '@/components/khamar/PageTitle.vue';

defineOptions({
    layout: { breadcrumbs: [{ title: 'অডিট লগ', href: '/setup/audit' }] },
});

defineProps<{
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
    <Head title="অডিট লগ" />

    <div class="flex flex-1 flex-col p-4 md:p-6">
        <PageTitle
            title="অডিট লগ"
            subtitle="কে, কখন, কোথা থেকে, কী করেছেন — সব লেখা থাকে, এখান থেকে কিছু মোছা যায় না।"
        />

        <div class="overflow-x-auto rounded-xl border border-border bg-card">
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
                            {{ log.user ?? 'সিস্টেম' }}
                        </td>
                        <td class="px-4 py-2.5">
                            <span
                                :class="[
                                    'rounded px-1.5 py-0.5 text-[11px]',
                                    tone(log.action),
                                ]"
                                >{{ actions[log.action] ?? log.action }}</span
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
