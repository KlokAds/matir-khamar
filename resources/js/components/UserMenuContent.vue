<script setup lang="ts">
import { Link, router } from '@inertiajs/vue3';
import { LogOut, Settings } from '@lucide/vue';
import {
    DropdownMenuGroup,
    DropdownMenuItem,
    DropdownMenuLabel,
    DropdownMenuSeparator,
} from '@/components/ui/dropdown-menu';
import UserInfo from '@/components/UserInfo.vue';
import { prepareLogout } from '@/lib/offline';
import { pendingCount } from '@/lib/offlineQueue';
import { logout } from '@/routes';
import { edit } from '@/routes/profile';
import type { User } from '@/types';

type Props = {
    user: User;
};

const handleLogout = async (event: MouseEvent) => {
    // Entries still on this phone survive logout; the saved offline page does not.
    event.preventDefault();

    if (!(await prepareLogout(pendingCount.value))) {
        return;
    }

    router.flushAll();
    router.post(logout().url);
};

defineProps<Props>();
</script>

<template>
    <DropdownMenuLabel class="p-0 font-normal">
        <div class="flex items-center gap-2 px-1 py-1.5 text-left text-sm">
            <UserInfo :user="user" :show-email="true" />
        </div>
    </DropdownMenuLabel>
    <DropdownMenuSeparator />
    <DropdownMenuGroup>
        <DropdownMenuItem :as-child="true">
            <Link class="block w-full cursor-pointer" :href="edit()" prefetch>
                <Settings class="mr-2 h-4 w-4" />
                সেটিংস ও নিরাপত্তা
            </Link>
        </DropdownMenuItem>
    </DropdownMenuGroup>
    <DropdownMenuSeparator />
    <DropdownMenuItem :as-child="true">
        <button
            type="button"
            class="block w-full cursor-pointer text-left"
            data-test="logout-button"
            @click="handleLogout"
        >
            <LogOut class="mr-2 inline h-4 w-4" />
            লগআউট
        </button>
    </DropdownMenuItem>
</template>
