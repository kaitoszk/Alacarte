<script setup lang="ts">
import RecipeForm from '@/components/recipes/RecipeForm.vue';
import AppLayout from '@/layouts/AppLayout.vue';
import { type BreadcrumbItem } from '@/types';
import type { Recipe } from '@/types/recipe';
import { Head } from '@inertiajs/vue3';
import { computed } from 'vue';

const props = defineProps<{
    recipe: { data: Recipe };
}>();

const recipe = computed(() => props.recipe.data);

const heading = computed(() => (recipe.value.is_published ? 'レシピを編集' : '下書きを編集'));

// 下書きは /drafts から開くため、パンくずの親も下書き一覧にする
const breadcrumbs = computed<BreadcrumbItem[]>(() => [
    recipe.value.is_published ? { title: 'レシピ', href: '/recipes' } : { title: '下書き', href: '/drafts' },
    { title: recipe.value.title, href: route('recipes.show', { recipe: recipe.value.id }) },
    { title: '編集', href: route('recipes.edit', { recipe: recipe.value.id }) },
]);
</script>

<template>
    <Head :title="`${recipe.title} を編集`" />

    <AppLayout :breadcrumbs="breadcrumbs">
        <div class="mx-auto flex w-full max-w-2xl flex-1 flex-col gap-6 p-4">
            <h1 class="text-2xl font-semibold">{{ heading }}</h1>
            <RecipeForm :recipe="recipe" />
        </div>
    </AppLayout>
</template>
