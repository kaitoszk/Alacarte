<script setup lang="ts">
import { Button } from '@/components/ui/button';
import AppLayout from '@/layouts/AppLayout.vue';
import { type BreadcrumbItem } from '@/types';
import type { CartGroup } from '@/types/cart';
import { Head, Link } from '@inertiajs/vue3';

defineProps<{
    groups: CartGroup[];
}>();

const breadcrumbs: BreadcrumbItem[] = [{ title: '買い物リスト', href: '/shopping-list' }];
</script>

<template>
    <Head title="買い物リスト" />

    <AppLayout :breadcrumbs="breadcrumbs">
        <div class="flex flex-1 flex-col gap-6 p-4">
            <h1 class="text-2xl font-semibold">買い物リスト</h1>

            <div v-if="groups.length === 0" class="flex flex-col items-center gap-3 py-16 text-center">
                <p class="text-muted-foreground">買い物リストは空です。</p>
                <Button as-child variant="secondary">
                    <Link :href="route('recipes.index')">レシピを探す</Link>
                </Button>
            </div>

            <div v-else class="flex max-w-2xl flex-col gap-6">
                <section v-for="group in groups" :key="group.recipe.id" class="rounded-lg border p-4">
                    <h2 class="mb-3 text-lg font-medium">
                        <span v-if="group.recipe.is_deleted">{{ group.recipe.title }}（削除されたレシピ）</span>
                        <Link v-else :href="route('recipes.show', { recipe: group.recipe.id })" class="hover:underline">
                            {{ group.recipe.title }}
                        </Link>
                    </h2>
                    <ul class="divide-y">
                        <li
                            v-for="item in group.items"
                            :key="item.id"
                            class="flex justify-between py-2 text-sm"
                            :class="item.is_checked ? 'text-muted-foreground line-through' : ''"
                        >
                            <span>{{ item.name }}</span>
                            <span>{{ item.quantity }}</span>
                        </li>
                    </ul>
                </section>
            </div>
        </div>
    </AppLayout>
</template>
