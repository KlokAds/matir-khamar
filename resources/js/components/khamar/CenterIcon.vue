<script setup lang="ts">
import {
    Beef,
    Factory,
    Fish,
    FolderTree,
    Layers,
    Sprout,
    Store,
    Tractor,
} from '@lucide/vue';
import { computed } from 'vue';

const props = defineProps<{ kind: string }>();

const icon = computed(
    () =>
        ({
            group: FolderTree,
            livestock: Beef,
            crop: Sprout,
            fishery: Fish,
            processing: Factory,
            store: Store,
            overhead: Layers,
        })[props.kind] ?? Tractor,
);

const tone = computed(
    () =>
        ({
            group: 'bg-soil/10 text-soil',
            livestock: 'bg-clay/14 text-clay',
            crop: 'bg-paddy/14 text-paddy',
            fishery: 'bg-pond/14 text-pond',
            processing: 'bg-turmeric/30 text-soil',
            store: 'bg-pond/14 text-pond',
            overhead: 'bg-muted text-muted-foreground',
        })[props.kind] ?? 'bg-muted text-muted-foreground',
);
</script>

<template>
    <span :class="['inline-flex shrink-0 items-center justify-center rounded-md p-1.5', tone]">
        <component :is="icon" class="size-4" />
    </span>
</template>
