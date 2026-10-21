<script setup lang="ts">
import { Link } from '@inertiajs/vue3';
import CenterIcon from '@/components/khamar/CenterIcon.vue';
import { t } from '@/lib/i18n';
import { taka } from '@/lib/khamar';
import type { TreeNode } from '@/lib/khamar';

const props = withDefaults(
    defineProps<{
        nodes: TreeNode[];
        depth?: number;
        query?: string;
        showClosed?: boolean;
    }>(),
    { depth: 0, query: '', showClosed: false },
);

/**
 * A closed (বন্ধ) project stays out of the list. It still shows if money moved in it during
 * the period, so the rows always add up to the totals above them.
 */
const visible = (node: TreeNode): boolean =>
    props.showClosed ||
    node.is_active ||
    node.income !== 0 ||
    node.expense !== 0;
</script>

<template>
    <template v-for="node in nodes.filter(visible)" :key="node.id">
        <tr
            :class="[
                'border-b border-border/60 hover:bg-accent/40',
                !node.is_active && 'opacity-55',
            ]"
        >
            <td class="py-2.5 pr-3">
                <Link
                    :href="`/centers/${node.id}${query}`"
                    class="flex items-center gap-2 hover:underline"
                    :style="{ paddingLeft: `${depth * 1.25}rem` }"
                >
                    <CenterIcon :kind="node.kind" />
                    <span
                        :class="
                            depth === 0 || node.children.length
                                ? 'font-semibold'
                                : ''
                        "
                        >{{ t(node.name) }}</span
                    >
                    <span
                        v-if="!node.is_active"
                        class="rounded bg-muted px-1.5 text-[11px] text-muted-foreground"
                        >{{ t('বন্ধ') }}</span
                    >
                </Link>
            </td>
            <td class="num py-2.5 pr-3 text-right text-income">
                {{ taka(node.income) }}
            </td>
            <td class="num py-2.5 pr-3 text-right text-expense">
                {{ taka(node.expense) }}
            </td>
            <td
                :class="[
                    'num py-2.5 text-right font-medium',
                    node.profit < 0 ? 'text-destructive' : 'text-foreground',
                ]"
            >
                {{ taka(node.profit) }}
            </td>
        </tr>
        <CenterRows
            v-if="node.children.length"
            :nodes="node.children"
            :depth="depth + 1"
            :query="query"
            :show-closed="showClosed"
        />
    </template>
</template>
