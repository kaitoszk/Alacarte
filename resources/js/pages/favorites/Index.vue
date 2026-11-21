<script setup lang="ts">
import EmptyState from '@/components/EmptyState.vue';
import Pagination from '@/components/Pagination.vue';
import RecipeCard from '@/components/recipes/RecipeCard.vue';
import { Button } from '@/components/ui/button';
import AppLayout from '@/layouts/AppLayout.vue';
import { type BreadcrumbItem } from '@/types';
import type { Paginated, Recipe } from '@/types/recipe';
import { Head, Link } from '@inertiajs/vue3';
import { Heart } from 'lucide-vue-next';

defineProps<{
    recipes: Paginated<Recipe>;
}>();

const breadcrumbs: BreadcrumbItem[] = [{ title: 'お気に入り', href: '/favorites' }];
</script>

<template>
    <Head title="お気に入り" />

    <AppLayout :breadcrumbs="breadcrumbs">
        <div class="flex flex-1 flex-col gap-6 p-4">
            <div>
                <h1 class="text-2xl font-bold">お気に入り</h1>
                <p class="mt-1 text-sm text-muted-foreground">気になったレシピをまとめて見返せます。</p>
            </div>

            <EmptyState
                v-if="recipes.data.length === 0"
                :icon="Heart"
                title="まだお気に入りがありません"
                description="レシピ詳細のハートを押すと、ここに並びます。"
            >
                <Button as-child>
                    <Link :href="route('home')">レシピを探す</Link>
                </Button>
            </EmptyState>

            <div v-else class="grid gap-5 sm:grid-cols-2 lg:grid-cols-3 xl:grid-cols-4">
                <RecipeCard v-for="recipe in recipes.data" :key="recipe.id" :recipe="recipe" />
            </div>

            <Pagination v-if="recipes.meta.last_page > 1" :links="recipes.meta.links" />
        </div>
    </AppLayout>
</template>
