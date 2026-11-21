<script setup lang="ts">
import type { PaginationLink } from '@/types/recipe';
import { Link } from '@inertiajs/vue3';

defineProps<{
    links: PaginationLink[];
}>();

// Laravel のページネーターはラベルに &laquo; / &raquo; を含めるため、v-html を使わず文字に置き換えて描画する
const toText = (label: string): string => label.replace('&laquo;', '«').replace('&raquo;', '»');
</script>

<template>
    <div class="flex flex-wrap justify-center gap-1">
        <template v-for="link in links" :key="link.label">
            <Link
                v-if="link.url"
                :href="link.url"
                class="rounded-md border px-3 py-1.5 text-sm"
                :class="link.active ? 'bg-primary text-primary-foreground' : 'hover:bg-accent'"
            >
                {{ toText(link.label) }}
            </Link>
            <span v-else class="rounded-md border px-3 py-1.5 text-sm text-muted-foreground">{{ toText(link.label) }}</span>
        </template>
    </div>
</template>
