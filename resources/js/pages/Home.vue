<script setup lang="ts">
import Pagination from '@/components/Pagination.vue';
import RecipeCard from '@/components/recipes/RecipeCard.vue';
import { Button } from '@/components/ui/button';
import AppLayout from '@/layouts/AppLayout.vue';
import { type SharedData } from '@/types';
import type { Feed, Recipe } from '@/types/recipe';
import { Head, Link, router, usePage } from '@inertiajs/vue3';
import { Dices, Shuffle } from 'lucide-vue-next';
import { computed, ref } from 'vue';

type Tab = 'all' | 'following';

const props = defineProps<{
    recipes: Feed<Recipe>;
    tab: Tab;
}>();

const page = usePage<SharedData>();
const isLoggedIn = computed(() => page.props.auth.user !== null);
const isEmpty = computed(() => props.recipes.data.length === 0);

/** タブ切替もシャッフルも、フィード部分だけを取り直す（全データ再取得しない） */
const loadFeed = (tab: Tab) => {
    router.get(
        route('home'),
        { tab },
        {
            only: ['recipes', 'tab'],
            preserveState: true,
            preserveScroll: true,
        },
    );
};

/** 今日のおまかせの条件。選ばなくてもボタン1つで提案できるよう、既定は「指定なし」 */
type TimeLimit = 15 | 30 | 60 | null;

const timeOptions: { value: TimeLimit; label: string }[] = [
    { value: null, label: '指定なし' },
    { value: 15, label: '15分以内' },
    { value: 30, label: '30分以内' },
    { value: 60, label: '60分以内' },
];

const omakaseTime = ref<TimeLimit>(null);
const omakaseFromFavorites = ref(false);

const pickRecipe = () => {
    router.get(route('recipes.random'), {
        ...(omakaseTime.value !== null ? { time: omakaseTime.value } : {}),
        ...(isLoggedIn.value && omakaseFromFavorites.value ? { favorites: 1 } : {}),
    });
};

const tabs: { key: Tab; label: string }[] = [
    { key: 'all', label: 'みんなのレシピ' },
    { key: 'following', label: 'フォロー中' },
];
</script>

<template>
    <Head title="ホーム" />

    <AppLayout>
        <div class="flex flex-1 flex-col gap-6 p-4">
            <!-- 見出し：コンセプト「今日何を作るか」をそのまま問いかける -->
            <section class="rounded-2xl bg-primary/10 px-6 py-8 sm:px-10">
                <h1 class="text-2xl font-bold tracking-tight sm:text-3xl">今日、何つくる？</h1>
                <p class="mt-2 text-sm text-muted-foreground sm:text-base">みんなのレシピを眺めて、今日の一品を決めましょう。</p>

                <!-- 今日のおまかせ：条件に合うレシピを1件選んで詳細へ遷移する -->
                <div class="mt-5 flex flex-col gap-3 rounded-xl bg-background/70 p-4">
                    <div class="flex flex-wrap gap-2" role="group" aria-label="調理時間の上限">
                        <button
                            v-for="option in timeOptions"
                            :key="option.label"
                            type="button"
                            class="rounded-full border px-3.5 py-1.5 text-sm transition"
                            :class="
                                omakaseTime === option.value
                                    ? 'border-primary bg-primary text-primary-foreground'
                                    : 'bg-background hover:border-primary hover:text-primary-strong'
                            "
                            :aria-pressed="omakaseTime === option.value ? 'true' : 'false'"
                            @click="omakaseTime = option.value"
                        >
                            {{ option.label }}
                        </button>
                    </div>
                    <label v-if="isLoggedIn" class="inline-flex w-fit items-center gap-2 text-sm">
                        <input v-model="omakaseFromFavorites" type="checkbox" class="h-4 w-4 accent-primary" />
                        お気に入りから選ぶ
                    </label>
                    <div class="flex flex-wrap gap-2">
                        <Button @click="pickRecipe">
                            <Dices class="h-4 w-4" />
                            今日のおまかせ
                        </Button>
                        <Button v-if="tab === 'all'" variant="secondary" @click="loadFeed('all')">
                            <Shuffle class="h-4 w-4" />
                            別のレシピを見る
                        </Button>
                    </div>
                </div>
            </section>

            <div class="inline-flex w-fit rounded-full border bg-muted p-1" role="tablist">
                <button
                    v-for="item in tabs"
                    :key="item.key"
                    type="button"
                    role="tab"
                    :aria-selected="tab === item.key"
                    class="rounded-full px-4 py-1.5 text-sm font-medium transition"
                    :class="tab === item.key ? 'bg-background text-foreground shadow-sm' : 'text-muted-foreground hover:text-foreground'"
                    @click="loadFeed(item.key)"
                >
                    {{ item.label }}
                </button>
            </div>

            <!-- フォロー中タブの空状態は理由によって出し分ける -->
            <div v-if="isEmpty && tab === 'following' && !isLoggedIn" class="flex flex-col items-center gap-3 py-16 text-center">
                <p class="text-muted-foreground">ログインすると、フォロー中のユーザーのレシピが並びます。</p>
                <Button as-child>
                    <Link :href="route('login')">ログイン</Link>
                </Button>
            </div>

            <div v-else-if="isEmpty && tab === 'following'" class="flex flex-col items-center gap-3 py-16 text-center">
                <p class="text-muted-foreground">フォロー中のユーザーのレシピがまだありません。</p>
                <Button variant="secondary" @click="loadFeed('all')">みんなのレシピを見る</Button>
            </div>

            <p v-else-if="isEmpty" class="py-16 text-center text-muted-foreground">まだレシピがありません。</p>

            <div v-else class="grid gap-4 sm:grid-cols-2 lg:grid-cols-3">
                <RecipeCard v-for="recipe in recipes.data" :key="recipe.id" :recipe="recipe" />
            </div>

            <!-- ページネーションはフォロー中タブのみ（みんなのレシピはシャッフルで引き直す） -->
            <Pagination v-if="recipes.meta && recipes.meta.last_page > 1" :links="recipes.meta.links" />
        </div>
    </AppLayout>
</template>
