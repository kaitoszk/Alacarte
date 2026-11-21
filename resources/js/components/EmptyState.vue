<script setup lang="ts">
import type { Component } from 'vue';

/**
 * 一覧が空のときの表示をそろえる共通コンポーネント。
 * ボタンなどの操作は default スロットに入れる。
 */
defineProps<{
    icon: Component;
    title: string;
    description?: string;
}>();
</script>

<template>
    <div class="flex flex-col items-center gap-3 rounded-xl border border-dashed px-6 py-16 text-center">
        <span class="flex size-12 items-center justify-center rounded-full bg-primary/10 text-primary">
            <component :is="icon" class="size-6" />
        </span>
        <p class="font-medium">{{ title }}</p>
        <p v-if="description" class="max-w-sm text-sm text-muted-foreground">{{ description }}</p>
        <div v-if="$slots.default" class="mt-2 flex flex-wrap justify-center gap-2">
            <slot />
        </div>
    </div>
</template>
