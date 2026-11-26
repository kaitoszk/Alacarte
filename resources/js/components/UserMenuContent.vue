<script setup lang="ts">
import UserInfo from '@/components/UserInfo.vue';
import { DropdownMenuGroup, DropdownMenuItem, DropdownMenuLabel, DropdownMenuSeparator } from '@/components/ui/dropdown-menu';
import type { User } from '@/types';
import { Link } from '@inertiajs/vue3';
import { FilePen, LogOut, Settings, User as UserIcon } from 'lucide-vue-next';

interface Props {
    user: User;
}

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
            <Link class="block w-full" :href="route('users.show', { user: user.handle })" as="button">
                <UserIcon class="mr-2 h-4 w-4" />
                マイページ
            </Link>
        </DropdownMenuItem>
        <DropdownMenuItem :as-child="true">
            <Link class="block w-full" :href="route('drafts.index')" as="button">
                <FilePen class="mr-2 h-4 w-4" />
                下書き
            </Link>
        </DropdownMenuItem>
        <DropdownMenuItem :as-child="true">
            <Link class="block w-full" :href="route('profile.edit')" as="button">
                <Settings class="mr-2 h-4 w-4" />
                設定
            </Link>
        </DropdownMenuItem>
    </DropdownMenuGroup>
    <DropdownMenuSeparator />
    <DropdownMenuItem :as-child="true">
        <Link class="block w-full" method="post" :href="route('logout')" as="button">
            <LogOut class="mr-2 h-4 w-4" />
            ログアウト
        </Link>
    </DropdownMenuItem>
</template>
