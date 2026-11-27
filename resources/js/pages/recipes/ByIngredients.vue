<script setup lang="ts">
import EmptyState from '@/components/EmptyState.vue';
import InputError from '@/components/InputError.vue';
import Pagination from '@/components/Pagination.vue';
import RecipeCard from '@/components/recipes/RecipeCard.vue';
import { Input } from '@/components/ui/input';
import AppLayout from '@/layouts/AppLayout.vue';
import { type BreadcrumbItem } from '@/types';
import type { Paginated, Recipe } from '@/types/recipe';
import { Head, router } from '@inertiajs/vue3';
import { Refrigerator, SearchX, X } from 'lucide-vue-next';
import { computed, ref } from 'vue';

const MAX_INGREDIENTS = 5;

const props = defineProps<{
    /** 材料が未入力のときは null（探さない） */
    recipes: Paginated<Recipe> | null;
    ingredients: string[];
    errors: Record<string, string>;
}>();

const breadcrumbs: BreadcrumbItem[] = [
    { title: 'レシピ', href: '/recipes' },
    { title: '冷蔵庫の材料から探す', href: '/recipes/by-ingredients' },
];

const tags = ref<string[]>([...props.ingredients]);
const draft = ref('');

const isFull = computed(() => tags.value.length >= MAX_INGREDIENTS);

/** ingredients / ingredients.0 などのうち最初のエラー */
const error = computed(() => Object.entries(props.errors).find(([key]) => key.startsWith('ingredients'))?.[1]);

/** 材料を変えるたびに結果部分だけを取り直す */
const visit = () => {
    router.get(
        route('recipes.by-ingredients'),
        { ingredients: tags.value },
        { only: ['recipes', 'ingredients'], preserveState: true, preserveScroll: true, replace: true },
    );
};

const addTag = () => {
    const name = draft.value.trim();
    draft.value = '';

    if (name === '' || isFull.value || tags.value.includes(name)) {
        return;
    }

    tags.value.push(name);
    visit();
};

const removeTag = (index: number) => {
    tags.value.splice(index, 1);
    visit();
};

const onKeydown = (event: KeyboardEvent) => {
    // 日本語入力の変換確定の Enter ではタグにしない
    if (event.isComposing) {
        return;
    }
    if (event.key === 'Enter' || event.key === ',' || event.key === '、') {
        event.preventDefault();
        addTag();
    }
};
</script>

<template>
    <Head title="冷蔵庫の材料から探す" />

    <AppLayout :breadcrumbs="breadcrumbs">
        <div class="flex flex-1 flex-col gap-6 p-4">
            <div>
                <h1 class="text-2xl font-bold">冷蔵庫の材料から探す</h1>
                <p class="mt-1 text-sm text-muted-foreground">
                    手元の材料を{{ MAX_INGREDIENTS }}つまで入れると、一致した材料の多いレシピから順に表示します。
                </p>
            </div>

            <div class="flex max-w-xl flex-col gap-2">
                <Input
                    v-model="draft"
                    maxlength="50"
                    :disabled="isFull"
                    :placeholder="isFull ? `材料は${MAX_INGREDIENTS}つまでです` : '材料名を入れて Enter（例: 豚肉）'"
                    aria-label="材料を追加"
                    @keydown="onKeydown"
                />
                <ul v-if="tags.length > 0" class="flex flex-wrap gap-2" aria-label="入力した材料">
                    <li
                        v-for="(tag, index) in tags"
                        :key="tag"
                        class="flex items-center gap-1 rounded-full border border-primary bg-primary/10 py-1 pl-3 pr-1 text-sm"
                    >
                        {{ tag }}
                        <button type="button" class="rounded-full p-0.5 hover:bg-primary/20" :aria-label="`${tag}を外す`" @click="removeTag(index)">
                            <X class="h-3.5 w-3.5" />
                        </button>
                    </li>
                </ul>
                <InputError :message="error" />
            </div>

            <EmptyState
                v-if="recipes === null"
                :icon="Refrigerator"
                title="材料を入れてください"
                description="冷蔵庫にある材料を入れると、作れそうなレシピが見つかります。"
            />

            <EmptyState
                v-else-if="recipes.data.length === 0"
                :icon="SearchX"
                title="その材料を使うレシピが見つかりません"
                description="材料を変えたり、短い名前（例: 「豚」）にしたりしてみてください。"
            />

            <template v-else>
                <p class="text-sm text-muted-foreground">{{ recipes.meta.total }}件</p>
                <div class="grid gap-5 sm:grid-cols-2 lg:grid-cols-3 xl:grid-cols-4">
                    <div v-for="recipe in recipes.data" :key="recipe.id" class="relative grid">
                        <RecipeCard :recipe="recipe" />
                        <span
                            v-if="recipe.matched_ingredients_count !== undefined"
                            class="pointer-events-none absolute right-3 top-3 rounded-full bg-primary px-2.5 py-1 text-xs font-medium text-primary-foreground shadow-sm"
                        >
                            {{ recipe.matched_ingredients_count }}つ一致
                        </span>
                    </div>
                </div>
            </template>

            <Pagination v-if="recipes !== null && recipes.meta.last_page > 1" :links="recipes.meta.links" />
        </div>
    </AppLayout>
</template>
