<script setup lang="ts">
import { Head, Link, router } from '@inertiajs/vue3';
import { Paperclip, Plus, Search } from '@lucide/vue';
import { reactive } from 'vue';
import NSelect from '@/components/khamar/NSelect.vue';
import PageTitle from '@/components/khamar/PageTitle.vue';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { centerLabel, day, taka, useCan } from '@/lib/khamar';
import type { Center } from '@/lib/khamar';

defineOptions({
    layout: { breadcrumbs: [{ title: 'খাতা', href: '/entries' }] },
});

const props = defineProps<{
    entries: {
        data: {
            id: number;
            number: string;
            date: string;
            type: string;
            type_label: string;
            description: string;
            amount: number;
            creator: string | null;
            attachments: number;
            reversed: boolean;
        }[];
        links: { url: string | null; label: string; active: boolean }[];
        total: number;
    };
    filters: Record<string, string | undefined>;
    types: Record<string, string>;
    centers: Center[];
}>();

const can = useCan();
const filter = reactive({
    q: props.filters.q ?? '',
    type: props.filters.type ?? '',
    center: props.filters.center ?? '',
    from: props.filters.from ?? '',
    to: props.filters.to ?? '',
});

function apply() {
    const query = Object.fromEntries(
        Object.entries(filter).filter(
            ([, value]) => value !== '' && value !== null,
        ),
    );
    router.get('/entries', query, {
        preserveState: true,
        preserveScroll: true,
    });
}
</script>

<template>
    <Head title="খাতা" />

    <div class="flex flex-1 flex-col p-4 md:p-6">
        <PageTitle
            title="খাতা"
            :subtitle="`মোট ${entries.total}টি এন্ট্রি — প্রতিটির নম্বর ধারাবাহিক, কোনোটা মোছা যায় না`"
        >
            <template #actions>
                <Button v-if="can['post-entries']" as-child>
                    <Link href="/entries/create"><Plus /> নতুন এন্ট্রি</Link>
                </Button>
            </template>
        </PageTitle>

        <form
            class="mb-4 grid gap-2 rounded-xl border border-border bg-card p-3 sm:grid-cols-2 lg:grid-cols-6"
            @submit.prevent="apply"
        >
            <div class="relative lg:col-span-2">
                <Search
                    class="absolute top-2.5 left-2.5 size-4 text-muted-foreground"
                />
                <Input
                    v-model="filter.q"
                    placeholder="বিবরণ বা নম্বর খুঁজুন"
                    class="h-10 pl-8"
                />
            </div>
            <NSelect v-model="filter.type">
                <option value="">সব ধরন</option>
                <option v-for="(label, key) in types" :key="key" :value="key">
                    {{ label }}
                </option>
            </NSelect>
            <NSelect v-model="filter.center">
                <option value="">সব ক্যাটাগরি</option>
                <option v-for="c in centers" :key="c.id" :value="String(c.id)">
                    {{ centerLabel(c) }}
                </option>
            </NSelect>
            <Input v-model="filter.from" type="date" class="h-10" />
            <div class="flex gap-2">
                <Input v-model="filter.to" type="date" class="h-10" />
                <Button type="submit" class="h-10">খুঁজুন</Button>
            </div>
        </form>

        <div class="overflow-x-auto rounded-xl border border-border bg-card">
            <table class="w-full min-w-[720px] text-sm">
                <thead
                    class="bg-muted/60 text-left text-xs text-muted-foreground"
                >
                    <tr>
                        <th class="px-3 py-2.5 font-medium">নম্বর</th>
                        <th class="px-3 py-2.5 font-medium">তারিখ</th>
                        <th class="px-3 py-2.5 font-medium">ধরন</th>
                        <th class="px-3 py-2.5 font-medium">বিবরণ</th>
                        <th class="px-3 py-2.5 text-right font-medium">টাকা</th>
                        <th class="px-3 py-2.5 font-medium">যিনি লিখেছেন</th>
                    </tr>
                </thead>
                <tbody>
                    <tr
                        v-for="entry in entries.data"
                        :key="entry.id"
                        class="border-t border-border/60 hover:bg-accent/40"
                    >
                        <td class="px-3 py-2.5">
                            <Link
                                :href="`/entries/${entry.id}`"
                                class="num font-medium text-primary hover:underline"
                                >{{ entry.number }}</Link
                            >
                        </td>
                        <td
                            class="px-3 py-2.5 whitespace-nowrap text-muted-foreground"
                        >
                            {{ day(entry.date) }}
                        </td>
                        <td class="px-3 py-2.5 whitespace-nowrap">
                            {{ entry.type_label }}
                        </td>
                        <td class="max-w-80 px-3 py-2.5">
                            <span
                                :class="[
                                    'line-clamp-1',
                                    entry.reversed && 'line-through opacity-60',
                                ]"
                                >{{ entry.description }}</span
                            >
                            <span
                                v-if="entry.attachments"
                                class="inline-flex items-center gap-0.5 text-xs text-muted-foreground"
                                ><Paperclip class="size-3" />
                                {{ entry.attachments }}</span
                            >
                            <span
                                v-if="entry.reversed"
                                class="ml-1 rounded bg-destructive/10 px-1.5 text-[11px] text-destructive"
                                >বাতিল</span
                            >
                        </td>
                        <td class="num px-3 py-2.5 text-right font-medium">
                            {{ taka(entry.amount) }}
                        </td>
                        <td class="px-3 py-2.5 text-muted-foreground">
                            {{ entry.creator }}
                        </td>
                    </tr>
                    <tr v-if="!entries.data.length">
                        <td
                            colspan="6"
                            class="px-3 py-10 text-center text-muted-foreground"
                        >
                            এখনো কোনো এন্ট্রি নেই।
                        </td>
                    </tr>
                </tbody>
            </table>
        </div>

        <nav v-if="entries.links.length > 3" class="mt-4 flex flex-wrap gap-1">
            <Link
                v-for="link in entries.links"
                :key="link.label"
                :href="link.url ?? ''"
                :class="[
                    'rounded-md border px-3 py-1.5 text-sm',
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
