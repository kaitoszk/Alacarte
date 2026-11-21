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
import { computed, ref } from 'vue';

const props = defineProps<{
    recipes: Paginated<Recipe>;
    filters: RecipeFilters;
}>();

const breadcrumbs: BreadcrumbItem[] = [{ title: 'レシピ', href: '/recipes' }];

const keyword = ref(props.filters.q ?? '');

const timeOptions: { value: RecipeFilters['time']; label: string }[] = [
    { value: null, label: 'すべて' },
    { value: 15, label: '15分以内' },
    { value: 30, label: '30分以内' },
    { value: 60, label: '60分以内' },
];

/** 条件を変えて一覧部分だけを取り直す（全データ再取得しない）。既定値は URL に出さない */
const visit = (filters: RecipeFilters) => {
    const query: Record<string, string | number> = {};

    if (filters.q && filters.q.trim() !== '') {
        query.q = filters.q.trim();
    }
    if (filters.time !== null) {
        query.time = filters.time;
    }
    if (filters.sort !== 'new') {
        query.sort = filters.sort;
    }

    router.get(route('recipes.index'), query, {
        only: ['recipes', 'filters'],
        preserveState: true,
        preserveScroll: true,
    });
};

const search = () => visit({ ...props.filters, q: keyword.value });

const clearKeyword = () => {
    keyword.value = '';
    visit({ ...props.filters, q: null });
};

const setTime = (time: RecipeFilters['time']) => visit({ ...props.filters, time });

const setSort = (event: Event) => visit({ ...props.filters, sort: (event.target as HTMLSelectElement).value as RecipeFilters['sort'] });

const resetAll = () => {
    keyword.value = '';
    visit({ q: null, time: null, sort: 'new' });
};

const hasCondition = computed(() => Boolean(props.filters.q) || props.filters.time !== null);

/** 「「鶏」・15分以内・人気順（10件）」のような説明 */
const summary = computed(() => {
    const parts = [
        props.filters.q ? `「${props.filters.q}」` : null,
        props.filters.time !== null ? `${props.filters.time}分以内` : null,
        props.filters.sort === 'popular' ? '人気順' : '新着順',
    ].filter((part): part is string => part !== null);

    return `${parts.join('・')}（${props.recipes.meta.total}件）`;
});
</script>

<template>
    <Head title="レシピ一覧" />

    <AppLayout :breadcrumbs="breadcrumbs">
        <div class="flex flex-1 flex-col gap-6 p-4">
            <div class="flex flex-wrap items-end justify-between gap-3">
                <div>
                    <h1 class="text-2xl font-bold">レシピ</h1>
                    <p class="mt-1 text-sm text-muted-foreground">{{ summary }}</p>
                </div>
                <Button as-child>
                    <Link :href="route('recipes.create')"><Plus class="h-4 w-4" />レシピを投稿</Link>
                </Button>
            </div>

            <form class="flex max-w-xl gap-2" role="search" @submit.prevent="search">
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
                <Button v-if="filters.q" type="button" variant="ghost" @click="clearKeyword"><X class="h-4 w-4" />クリア</Button>
            </form>

            <div class="flex flex-wrap items-center justify-between gap-3">
                <div class="flex flex-wrap gap-2" role="group" aria-label="調理時間で絞り込む">
                    <button
                        v-for="option in timeOptions"
                        :key="option.label"
                        type="button"
                        class="rounded-full border px-3.5 py-1.5 text-sm transition"
                        :class="
                            filters.time === option.value
                                ? 'border-primary bg-primary text-primary-foreground'
                                : 'hover:border-primary hover:text-primary-strong'
                        "
                        :aria-pressed="filters.time === option.value ? 'true' : 'false'"
                        @click="setTime(option.value)"
                    >
                        {{ option.label }}
                    </button>
                </div>

                <label class="flex items-center gap-2 text-sm">
                    <span class="text-muted-foreground">並び順</span>
                    <select
                        :value="filters.sort"
                        class="h-9 rounded-md border border-input bg-background px-3 text-sm shadow-sm focus-visible:outline-none focus-visible:ring-1 focus-visible:ring-ring"
                        @change="setSort"
                    >
                        <option value="new">新着順</option>
                        <option value="popular">人気順（お気に入り数）</option>
                    </select>
                </label>
            </div>

            <EmptyState
                v-if="recipes.data.length === 0 && hasCondition"
                :icon="SearchX"
                title="条件に合うレシピが見つかりません"
                description="キーワードを変えたり、調理時間の条件をゆるめたりしてみてください。"
            >
                <Button variant="outline" @click="resetAll">条件をリセット</Button>
            </EmptyState>

            <EmptyState v-else-if="recipes.data.length === 0" :icon="UtensilsCrossed" title="まだレシピがありません" />

            <div v-else class="grid gap-5 sm:grid-cols-2 lg:grid-cols-3 xl:grid-cols-4">
                <RecipeCard v-for="recipe in recipes.data" :key="recipe.id" :recipe="recipe" />
            </div>

            <Pagination v-if="recipes.meta.last_page > 1" :links="recipes.meta.links" />
        </div>
    </AppLayout>
</template>
