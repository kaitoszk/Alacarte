<script setup lang="ts">
import EmptyState from '@/components/EmptyState.vue';
import Pagination from '@/components/Pagination.vue';
import RecipeCard from '@/components/recipes/RecipeCard.vue';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import AppLayout from '@/layouts/AppLayout.vue';
import { type BreadcrumbItem } from '@/types';
import type { Paginated, Recipe, RecipeFilters } from '@/types/recipe';
import { Head, Link, router } from '@inertiajs/vue3';
import { Plus, Search, SearchX, UtensilsCrossed, X } from 'lucide-vue-next';
import { ref } from 'vue';

const props = defineProps<{
    recipes: Paginated<Recipe>;
    filters: RecipeFilters;
}>();

const breadcrumbs: BreadcrumbItem[] = [{ title: 'レシピ', href: '/recipes' }];

const keyword = ref(props.filters.q ?? '');

/** 一覧部分だけを取り直す（全データ再取得しない） */
const search = (q: string) => {
    router.get(route('recipes.index'), q.trim() === '' ? {} : { q: q.trim() }, {
        only: ['recipes', 'filters'],
        preserveState: true,
        preserveScroll: true,
    });
};

const clear = () => {
    keyword.value = '';
    search('');
};
</script>

<template>
    <Head title="レシピ一覧" />

    <AppLayout :breadcrumbs="breadcrumbs">
        <div class="flex flex-1 flex-col gap-6 p-4">
            <div class="flex flex-wrap items-end justify-between gap-3">
                <div>
                    <h1 class="text-2xl font-bold">レシピ</h1>
                    <p class="mt-1 text-sm text-muted-foreground">
                        <template v-if="filters.q">「{{ filters.q }}」の検索結果（{{ recipes.meta.total }}件）</template>
                        <template v-else>新着順に並んでいます。</template>
                    </p>
                </div>
                <Button as-child>
                    <Link :href="route('recipes.create')"><Plus class="h-4 w-4" />レシピを投稿</Link>
                </Button>
            </div>

            <form class="flex max-w-xl gap-2" role="search" @submit.prevent="search(keyword)">
                <div class="relative flex-1">
                    <Search class="pointer-events-none absolute left-3 top-1/2 h-4 w-4 -translate-y-1/2 text-muted-foreground" />
                    <Input
                        v-model="keyword"
                        type="search"
                        maxlength="50"
                        class="pl-9"
                        placeholder="料理名や材料で探す（例: 鶏肉 玉ねぎ）"
                        aria-label="レシピを検索"
                    />
                </div>
                <Button type="submit">検索</Button>
                <Button v-if="filters.q" type="button" variant="ghost" @click="clear"><X class="h-4 w-4" />クリア</Button>
            </form>

            <EmptyState
                v-if="recipes.data.length === 0 && filters.q"
                :icon="SearchX"
                title="条件に合うレシピが見つかりません"
                description="別のキーワードや、材料名を減らして探してみてください。"
            >
                <Button variant="outline" @click="clear">検索をクリア</Button>
            </EmptyState>

            <EmptyState v-else-if="recipes.data.length === 0" :icon="UtensilsCrossed" title="まだレシピがありません" />

            <div v-else class="grid gap-5 sm:grid-cols-2 lg:grid-cols-3 xl:grid-cols-4">
                <RecipeCard v-for="recipe in recipes.data" :key="recipe.id" :recipe="recipe" />
            </div>

            <Pagination v-if="recipes.meta.last_page > 1" :links="recipes.meta.links" />
        </div>
    </AppLayout>
</template>
