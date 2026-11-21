<script setup lang="ts">
import Pagination from '@/components/Pagination.vue';
import RecipeCard from '@/components/recipes/RecipeCard.vue';
import { Button } from '@/components/ui/button';
import AppLayout from '@/layouts/AppLayout.vue';
import { type BreadcrumbItem } from '@/types';
import type { Paginated, Recipe } from '@/types/recipe';
import { Head, Link } from '@inertiajs/vue3';

defineProps<{
    recipes: Paginated<Recipe>;
}>();

const breadcrumbs: BreadcrumbItem[] = [{ title: 'お気に入り', href: '/favorites' }];
</script>

<template>
    <Head title="お気に入り" />

    <AppLayout :breadcrumbs="breadcrumbs">
        <div class="flex flex-1 flex-col gap-6 p-4">
            <h1 class="text-2xl font-semibold">お気に入り</h1>

            <div v-if="recipes.data.length === 0" class="flex flex-col items-center gap-3 py-16 text-center">
                <p class="text-muted-foreground">まだお気に入りがありません。</p>
                <Button as-child variant="secondary">
                    <Link :href="route('home')">レシピを探す</Link>
                </Button>
            </div>

            <div v-else class="grid gap-4 sm:grid-cols-2 lg:grid-cols-3">
                <RecipeCard v-for="recipe in recipes.data" :key="recipe.id" :recipe="recipe" />
            </div>

            <Pagination v-if="recipes.meta.last_page > 1" :links="recipes.meta.links" />
        </div>
    </AppLayout>
</template>
