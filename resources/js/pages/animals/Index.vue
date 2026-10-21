<script setup lang="ts">
import { Head, Link } from '@inertiajs/vue3';
import { Plus } from '@lucide/vue';
import CenterIcon from '@/components/khamar/CenterIcon.vue';
import PageTitle from '@/components/khamar/PageTitle.vue';
import { Button } from '@/components/ui/button';
import { t, tAll } from '@/lib/i18n';
import { day, taka, useCan } from '@/lib/khamar';

defineOptions({
    layout: { breadcrumbs: [{ title: 'গরুর তালিকা', href: '/animals' }] },
});

defineProps<{
    status: string;
    statuses: Record<string, string>;
    animals: {
        id: number;
        tag: string;
        name: string | null;
        sex: string;
        breed: string | null;
        center: string;
        acquired_on: string;
        purchase_cost: number;
        sale_amount: number | null;
    }[];
}>();

const can = useCan();
</script>

<template>
    <Head :title="t('গরুর তালিকা')" />

    <div class="flex flex-1 flex-col p-4 md:p-6">
        <PageTitle
            :title="t('গরুর তালিকা')"
            :subtitle="
                t(
                    'প্রতিটি গরুর আলাদা কার্ড — কেনা দাম, কোন ক্যাটাগরিতে আছে, কত খরচ হলো, বিক্রিতে কত লাভ',
                )
            "
        >
            <template #actions>
                <Button v-if="can['post-entries']" as-child
                    ><Link href="/animals/create"
                        ><Plus /> {{ t('গরু যোগ করুন') }}</Link
                    ></Button
                >
            </template>
        </PageTitle>

        <div class="mb-4 flex gap-1.5">
            <Link
                v-for="(label, key) in tAll(statuses)"
                :key="key"
                :href="`/animals?status=${key}`"
                :class="[
                    'rounded-full border px-3 py-1.5 text-sm',
                    status === key
                        ? 'border-primary bg-primary text-primary-foreground'
                        : 'border-border bg-card hover:bg-accent',
                ]"
                preserve-state
                >{{ label }}</Link
            >
        </div>

        <div class="grid gap-3 sm:grid-cols-2 xl:grid-cols-3">
            <Link
                v-for="a in animals"
                :key="a.id"
                :href="`/animals/${a.id}`"
                class="group rounded-xl border border-border bg-card p-4 transition-shadow hover:shadow-md"
            >
                <div class="flex items-center gap-3">
                    <CenterIcon kind="livestock" />
                    <div class="min-w-0 flex-1">
                        <p class="font-display text-lg leading-tight">
                            {{ a.tag }}
                            <span
                                v-if="a.name"
                                class="text-base text-muted-foreground"
                                >· {{ a.name }}</span
                            >
                        </p>
                        <p class="text-xs text-muted-foreground">
                            {{
                                a.sex === 'female'
                                    ? t('গাভী/বকনা')
                                    : t('ষাঁড়/এঁড়ে')
                            }}<span v-if="a.breed"> · {{ a.breed }}</span>
                        </p>
                    </div>
                    <span class="rounded-full bg-accent px-2.5 py-1 text-xs">{{
                        t(a.center)
                    }}</span>
                </div>
                <div
                    class="mt-3 flex justify-between border-t border-dashed border-border pt-3 text-sm"
                >
                    <span class="text-muted-foreground">{{
                        t(':date থেকে', { date: day(a.acquired_on) })
                    }}</span>
                    <span class="num">{{
                        a.sale_amount !== null
                            ? t('বিক্রি :amount', {
                                  amount: taka(a.sale_amount),
                              })
                            : t('দাম :amount', {
                                  amount: taka(a.purchase_cost),
                              })
                    }}</span>
                </div>
            </Link>
            <p
                v-if="!animals.length"
                class="col-span-full rounded-xl border border-dashed border-border p-8 text-center text-muted-foreground"
            >
                {{ t('এখানে কোনো গরু নেই।') }}
            </p>
        </div>
    </div>
</template>
