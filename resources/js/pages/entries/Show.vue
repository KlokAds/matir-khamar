<script setup lang="ts">
import { Head, Link, useForm } from '@inertiajs/vue3';
import { FileText, Paperclip, Printer, Undo2 } from '@lucide/vue';
import { computed, ref } from 'vue';
import Field from '@/components/khamar/Field.vue';
import PageTitle from '@/components/khamar/PageTitle.vue';
import { Button } from '@/components/ui/button';
import DateField from '@/components/khamar/DateField.vue';
import { Input } from '@/components/ui/input';
import { t } from '@/lib/i18n';
import { day, taka, today, useCan } from '@/lib/khamar';

const props = defineProps<{
    entry: {
        id: number;
        number: string;
        date: string;
        type: string;
        type_label: string;
        description: string;
        reference: string | null;
        party: string | null;
        creator: string | null;
        created_at: string | null;
        reversal_of: { id: number; number: string } | null;
        reversed_by: { id: number; number: string } | null;
        can_reverse: boolean;
        lines: {
            id: number;
            account: string;
            center: string | null;
            animal: string | null;
            budget_head: string | null;
            debit: number;
            credit: number;
            memo: string | null;
        }[];
        attachments: { id: number; name: string; mime: string; size: number }[];
    };
}>();

defineOptions({
    layout: {
        breadcrumbs: [
            { title: 'খাতা', href: '/entries' },
            { title: 'এন্ট্রি', href: '#' },
        ],
    },
});

const can = useCan();
/** Split a translated sentence around its :n slot so a link can sit in the middle. */
const around = (text: string) => {
    const [before, after = ''] = t(text).split(':n');

    return { before, after };
};
const reversedText = computed(() =>
    around('এই এন্ট্রিটি বাতিল করা হয়েছে উল্টো এন্ট্রি :n দিয়ে।'),
);
const reversalOfText = computed(() => around('এটি :n এর বাতিল-এন্ট্রি।'));
const print = () => window.print();
const showReverse = ref(false);
const reverse = useForm({ reason: '', date: today() });
const upload = useForm({ attachments: [] as File[] });

const totalDebit = computed(() =>
    props.entry.lines.reduce((sum, line) => sum + line.debit, 0),
);
const totalCredit = computed(() =>
    props.entry.lines.reduce((sum, line) => sum + line.credit, 0),
);

function pick(event: Event) {
    upload.attachments = Array.from(
        (event.target as HTMLInputElement).files ?? [],
    ).slice(0, 5);
    upload.post(`/entries/${props.entry.id}/attachments`, {
        forceFormData: true,
        preserveScroll: true,
        onSuccess: () => upload.reset(),
    });
}
</script>

<template>
    <Head :title="entry.number" />

    <div class="flex flex-1 flex-col p-4 md:p-6">
        <PageTitle
            :title="t('ভাউচার :n', { n: entry.number })"
            :subtitle="`${t(entry.type_label)} · ${day(entry.date)}`"
        >
            <template #actions>
                <Button variant="secondary" @click="print"
                    ><Printer /> {{ t('প্রিন্ট') }}</Button
                >
                <Button
                    v-if="entry.can_reverse && can['reverse-entries']"
                    variant="destructive"
                    @click="showReverse = !showReverse"
                    ><Undo2 /> {{ t('বাতিল করুন') }}</Button
                >
            </template>
        </PageTitle>

        <div
            v-if="entry.reversed_by"
            class="mb-4 rounded-xl border border-destructive/30 bg-destructive/5 p-3 text-sm"
        >
            {{ reversedText.before }}
            <Link
                :href="`/entries/${entry.reversed_by.id}`"
                class="font-medium underline"
                >{{ entry.reversed_by.number }}</Link
            >
            {{ reversedText.after }}
        </div>
        <div
            v-if="entry.reversal_of"
            class="mb-4 rounded-xl border border-border bg-muted/60 p-3 text-sm"
        >
            {{ reversalOfText.before }}
            <Link
                :href="`/entries/${entry.reversal_of.id}`"
                class="font-medium underline"
                >{{ entry.reversal_of.number }}</Link
            >
            {{ reversalOfText.after }}
        </div>

        <form
            v-if="showReverse"
            class="mb-5 grid gap-3 rounded-xl border border-destructive/40 bg-card p-4 sm:grid-cols-[1fr_180px_auto] sm:items-end"
            @submit.prevent="reverse.post(`/entries/${entry.id}/reverse`)"
        >
            <Field
                :label="t('কেন বাতিল (অডিট লগে থাকবে)')"
                :error="
                    reverse.errors.reason ||
                    (reverse.errors as Record<string, string>).entry
                "
            >
                <Input
                    v-model="reverse.reason"
                    required
                    minlength="3"
                    :placeholder="t('যেমন: টাকার অঙ্ক ভুল ছিল')"
                />
            </Field>
            <Field :label="t('বাতিলের তারিখ')" :error="reverse.errors.date">
                <DateField v-model="reverse.date" :max="today()" />
            </Field>
            <Button
                type="submit"
                variant="destructive"
                :disabled="reverse.processing"
                >{{ t('উল্টো এন্ট্রি দিন') }}</Button
            >
        </form>

        <div class="grid gap-5 lg:grid-cols-3">
            <section
                class="min-w-0 rounded-xl border border-border bg-card lg:col-span-2"
            >
                <div class="border-b border-border p-4">
                    <p class="font-display text-lg">{{ entry.description }}</p>
                    <p class="mt-1 text-sm text-muted-foreground">
                        <span v-if="entry.party">{{ entry.party }} · </span>
                        <span v-if="entry.reference"
                            >{{ t('রসিদ :n', { n: entry.reference }) }} ·
                        </span>
                        {{
                            t('লিখেছেন :name (:at)', {
                                name: entry.creator ?? '',
                                at: entry.created_at ?? '',
                            })
                        }}
                    </p>
                </div>
                <div class="overflow-x-auto">
                    <table class="w-full min-w-[560px] text-sm">
                        <thead class="text-left text-xs text-muted-foreground">
                            <tr class="border-b border-border">
                                <th class="px-4 py-2 font-medium">
                                    {{ t('খাত') }}
                                </th>
                                <th class="px-4 py-2 font-medium">
                                    {{ t('ক্যাটাগরি') }}
                                </th>
                                <th class="px-4 py-2 text-right font-medium">
                                    {{ t('ডেবিট') }}
                                </th>
                                <th class="px-4 py-2 text-right font-medium">
                                    {{ t('ক্রেডিট') }}
                                </th>
                            </tr>
                        </thead>
                        <tbody>
                            <tr
                                v-for="line in entry.lines"
                                :key="line.id"
                                class="border-b border-border/60"
                            >
                                <td class="px-4 py-2.5">
                                    {{ t(line.account) }}
                                    <p
                                        v-if="
                                            line.memo ||
                                            line.animal ||
                                            line.budget_head
                                        "
                                        class="text-xs text-muted-foreground"
                                    >
                                        {{
                                            [
                                                line.memo,
                                                line.animal
                                                    ? t('গরু :tag', {
                                                          tag: line.animal,
                                                      })
                                                    : null,
                                                line.budget_head
                                                    ? t('বাজেট :head', {
                                                          head: line.budget_head,
                                                      })
                                                    : null,
                                            ]
                                                .filter(Boolean)
                                                .join(' · ')
                                        }}
                                    </p>
                                </td>
                                <td class="px-4 py-2.5 text-muted-foreground">
                                    {{ line.center ? t(line.center) : '—' }}
                                </td>
                                <td class="num px-4 py-2.5 text-right">
                                    {{ line.debit ? taka(line.debit) : '' }}
                                </td>
                                <td class="num px-4 py-2.5 text-right">
                                    {{ line.credit ? taka(line.credit) : '' }}
                                </td>
                            </tr>
                        </tbody>
                        <tfoot>
                            <tr class="font-semibold">
                                <td class="px-4 py-2.5" colspan="2">
                                    {{ t('মোট (দুই পাশ সমান)') }}
                                </td>
                                <td class="num px-4 py-2.5 text-right">
                                    {{ taka(totalDebit) }}
                                </td>
                                <td class="num px-4 py-2.5 text-right">
                                    {{ taka(totalCredit) }}
                                </td>
                            </tr>
                        </tfoot>
                    </table>
                </div>
            </section>

            <section
                class="min-w-0 rounded-xl border border-border bg-card p-4"
            >
                <h2 class="mb-3 font-display text-lg">
                    {{ t('ভাউচার / রসিদ') }}
                </h2>
                <ul v-if="entry.attachments.length" class="mb-3 space-y-2">
                    <li v-for="file in entry.attachments" :key="file.id">
                        <a
                            :href="`/attachments/${file.id}`"
                            target="_blank"
                            rel="noopener"
                            class="flex items-center gap-2 rounded-md border border-border px-3 py-2 text-sm hover:bg-accent"
                        >
                            <FileText class="size-4 text-muted-foreground" />
                            <span class="min-w-0 flex-1 truncate">{{
                                file.name
                            }}</span>
                            <span class="text-xs text-muted-foreground"
                                >{{ Math.ceil(file.size / 1024) }} KB</span
                            >
                        </a>
                    </li>
                </ul>
                <p v-else class="mb-3 text-sm text-muted-foreground">
                    {{ t('কোনো ভাউচার যোগ করা হয়নি।') }}
                </p>
                <label
                    v-if="can['post-entries']"
                    class="flex cursor-pointer items-center gap-2 rounded-md border border-dashed border-input px-3 py-2.5 text-sm hover:bg-accent"
                >
                    <Paperclip class="size-4 text-muted-foreground" />
                    {{
                        upload.processing
                            ? t('আপলোড হচ্ছে…')
                            : t('ভাউচার যোগ করুন')
                    }}
                    <input
                        type="file"
                        class="sr-only"
                        accept="image/*,application/pdf"
                        capture="environment"
                        multiple
                        @change="pick"
                    />
                </label>
                <p
                    v-if="upload.errors.attachments"
                    class="mt-2 text-xs text-destructive"
                >
                    {{ upload.errors.attachments }}
                </p>
            </section>
        </div>
    </div>
</template>
