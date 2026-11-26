<script setup lang="ts">
import ConfirmModal from '@/components/ConfirmModal.vue';
import CommentSection from '@/components/recipes/CommentSection.vue';
import { Avatar, AvatarFallback, AvatarImage } from '@/components/ui/avatar';
import { Button } from '@/components/ui/button';
import { getInitials } from '@/composables/useInitials';
import AppLayout from '@/layouts/AppLayout.vue';
import { type BreadcrumbItem, type SharedData } from '@/types';
import type { Recipe, RecipeComment } from '@/types/recipe';
import { Head, Link, router, usePage } from '@inertiajs/vue3';
import { Clock, Eye, Heart, ImageOff, ShoppingCart, UserCheck, UserPlus, Users } from 'lucide-vue-next';
import { computed, ref } from 'vue';

const props = defineProps<{
    recipe: { data: Recipe };
    comments: { data: RecipeComment[] };
    isFollowingAuthor: boolean;
    isInCart: boolean;
}>();

const page = usePage<SharedData>();

const recipe = computed(() => props.recipe.data);
const isLoggedIn = computed(() => page.props.auth.user !== null);
const isOwner = computed(() => page.props.auth?.user?.id === recipe.value.user?.id);
/** 下書きは本人のプレビュー。お気に入り・買い物リスト・コメント・フォローはできないので出さない */
const isDraft = computed(() => !recipe.value.is_published);

/** お気に入りのトグル。押した位置を保ったまま再描画する */
const toggleFavorite = () => {
    const params = { recipe: recipe.value.id };
    const options = { preserveScroll: true };

    if (recipe.value.is_favorited) {
        router.delete(route('favorites.destroy', params), options);

        return;
    }

    router.post(route('favorites.store', params), {}, options);
};

/** 材料をまとめて買い物リストに追加する。追加済みならボタンは無効 */
const isAddingToCart = ref(false);
const addToCart = () => {
    router.post(
        route('carts.store', { recipe: recipe.value.id }),
        {},
        {
            preserveScroll: true,
            onStart: () => (isAddingToCart.value = true),
            onFinish: () => (isAddingToCart.value = false),
        },
    );
};

/** 投稿者のフォローをトグルする。自分のレシピとゲストには出さない */
const canFollowAuthor = computed(() => isLoggedIn.value && !isOwner.value && !isDraft.value && recipe.value.user !== undefined);

const toggleFollow = () => {
    const author = recipe.value.user;

    if (author === undefined) {
        return;
    }

    const params = { user: author.handle };
    const options = { preserveScroll: true };

    if (props.isFollowingAuthor) {
        router.delete(route('follows.destroy', params), options);

        return;
    }

    router.post(route('follows.store', params), {}, options);
};

const breadcrumbs = computed<BreadcrumbItem[]>(() => [
    isDraft.value ? { title: '下書き', href: '/drafts' } : { title: 'レシピ', href: '/recipes' },
    { title: recipe.value.title, href: route('recipes.show', { recipe: recipe.value.id }) },
]);

const destroy = () => {
    router.delete(route('recipes.destroy', { recipe: recipe.value.id }));
};
</script>

<template>
    <Head :title="recipe.title" />

    <AppLayout :breadcrumbs="breadcrumbs">
        <div class="mx-auto flex w-full max-w-5xl flex-1 flex-col gap-8 p-4">
            <img
                v-if="recipe.recipe_image_url"
                :src="recipe.recipe_image_url"
                :alt="recipe.title"
                class="aspect-[16/9] max-h-[480px] w-full rounded-2xl object-cover"
            />
            <div
                v-else
                class="flex aspect-[16/9] max-h-[480px] w-full flex-col items-center justify-center gap-2 rounded-2xl bg-muted text-muted-foreground"
            >
                <ImageOff class="h-10 w-10" />
                <span class="text-sm">完成写真はまだありません</span>
            </div>

            <!-- タイトル・チップ・操作 -->
            <div class="flex flex-col gap-4">
                <div class="flex flex-wrap items-start justify-between gap-4">
                    <div class="flex flex-col items-start gap-2">
                        <span
                            v-if="isDraft"
                            class="rounded-full bg-amber-100 px-3 py-0.5 text-xs font-semibold text-amber-800 dark:bg-amber-900/40 dark:text-amber-200"
                        >
                            下書き
                        </span>
                        <h1 class="text-2xl font-bold leading-tight sm:text-3xl">{{ recipe.title }}</h1>
                    </div>

                    <div v-if="isOwner" class="flex gap-2">
                        <Button variant="outline" size="sm" as-child>
                            <Link :href="route('recipes.edit', { recipe: recipe.id })">編集</Link>
                        </Button>
                        <ConfirmModal title="このレシピを削除しますか？" description="削除すると一覧から表示されなくなります。" @confirm="destroy">
                            <template #trigger>
                                <Button variant="destructive" size="sm">削除</Button>
                            </template>
                        </ConfirmModal>
                    </div>
                </div>

                <div class="flex flex-wrap items-center gap-2 text-sm">
                    <span v-if="recipe.cooking_time !== null" class="flex items-center gap-1.5 rounded-full bg-muted px-3 py-1"
                        ><Clock class="h-4 w-4" />{{ recipe.cooking_time }}分</span
                    >
                    <span v-if="recipe.servings !== null" class="flex items-center gap-1.5 rounded-full bg-muted px-3 py-1"
                        ><Users class="h-4 w-4" />{{ recipe.servings }}人分</span
                    >
                    <span class="flex items-center gap-1.5 rounded-full bg-muted px-3 py-1" aria-label="閲覧数"
                        ><Eye class="h-4 w-4" />{{ recipe.view_count }}</span
                    >

                    <Button
                        v-if="!isDraft && isLoggedIn"
                        :variant="recipe.is_favorited ? 'secondary' : 'outline'"
                        size="sm"
                        class="gap-1.5 rounded-full"
                        @click="toggleFavorite"
                    >
                        <Heart class="h-4 w-4" :class="recipe.is_favorited ? 'fill-primary text-primary' : ''" />
                        {{ recipe.is_favorited ? 'お気に入り済み' : 'お気に入り' }}
                        <span class="text-muted-foreground">{{ recipe.favorites_count }}</span>
                    </Button>
                    <span v-else-if="!isDraft" class="flex items-center gap-1.5 rounded-full bg-muted px-3 py-1" aria-label="お気に入り数">
                        <Heart class="h-4 w-4" />{{ recipe.favorites_count }}
                    </span>
                </div>

                <p v-if="recipe.description" class="whitespace-pre-wrap text-muted-foreground">{{ recipe.description }}</p>

                <!-- 投稿者 -->
                <div v-if="recipe.user" class="flex items-center justify-between gap-3 rounded-xl border p-4">
                    <Link :href="route('users.show', { user: recipe.user.handle })" class="flex min-w-0 items-center gap-3 hover:underline">
                        <Avatar class="size-10">
                            <AvatarImage v-if="recipe.user.user_image_url" :src="recipe.user.user_image_url" :alt="recipe.user.name" />
                            <AvatarFallback>{{ getInitials(recipe.user.name) }}</AvatarFallback>
                        </Avatar>
                        <span class="flex min-w-0 flex-col">
                            <span class="truncate font-medium">{{ recipe.user.name }}</span>
                            <span class="truncate text-xs text-muted-foreground">@{{ recipe.user.handle }}</span>
                        </span>
                    </Link>
                    <Button
                        v-if="canFollowAuthor"
                        :variant="isFollowingAuthor ? 'secondary' : 'default'"
                        size="sm"
                        class="gap-1.5"
                        @click="toggleFollow"
                    >
                        <UserCheck v-if="isFollowingAuthor" class="h-3.5 w-3.5" />
                        <UserPlus v-else class="h-3.5 w-3.5" />
                        {{ isFollowingAuthor ? 'フォロー中' : 'フォロー' }}
                    </Button>
                </div>
            </div>

            <!-- 材料（PC では左に固定）と作り方 -->
            <div class="grid gap-8 lg:grid-cols-[320px_1fr]">
                <section v-if="recipe.ingredients?.length" class="flex flex-col gap-3 self-start rounded-xl border bg-card p-5 lg:sticky lg:top-28">
                    <h2 class="text-lg font-semibold">
                        材料<span v-if="recipe.servings !== null" class="ml-1 text-sm font-normal text-muted-foreground"
                            >（{{ recipe.servings }}人分）</span
                        >
                    </h2>
                    <ul class="divide-y">
                        <li v-for="ingredient in recipe.ingredients" :key="ingredient.id" class="flex justify-between gap-4 py-2.5 text-sm">
                            <span>{{ ingredient.name }}</span>
                            <span class="shrink-0 text-muted-foreground">{{ ingredient.quantity }}</span>
                        </li>
                    </ul>
                    <Button v-if="!isDraft && !isLoggedIn" class="w-full gap-1.5" as-child>
                        <Link :href="route('login')"><ShoppingCart class="h-4 w-4" />買い物リストに追加</Link>
                    </Button>
                    <Button
                        v-else-if="!isDraft"
                        class="w-full gap-1.5"
                        :variant="isInCart ? 'secondary' : 'default'"
                        :disabled="isInCart || isAddingToCart"
                        @click="addToCart"
                    >
                        <ShoppingCart class="h-4 w-4" />
                        {{ isInCart ? '買い物リストに追加済み' : '買い物リストに追加' }}
                    </Button>
                </section>

                <section v-if="recipe.steps?.length" class="flex flex-col gap-6">
                    <h2 class="text-lg font-semibold">作り方</h2>
                    <ol class="flex flex-col gap-6">
                        <li v-for="step in recipe.steps" :key="step.id" class="flex gap-4">
                            <span
                                class="flex h-8 w-8 shrink-0 items-center justify-center rounded-full bg-primary text-sm font-semibold text-primary-foreground"
                            >
                                {{ step.order_number }}
                            </span>
                            <div class="flex min-w-0 flex-col gap-3 pt-1">
                                <p class="whitespace-pre-wrap leading-relaxed">{{ step.description }}</p>
                                <div v-if="step.images?.length" class="flex flex-wrap gap-2">
                                    <img
                                        v-for="image in step.images"
                                        :key="image.id"
                                        :src="image.step_image_url"
                                        :alt="`手順${step.order_number}の画像`"
                                        class="h-36 w-36 rounded-lg object-cover sm:h-44 sm:w-44"
                                        loading="lazy"
                                    />
                                </div>
                            </div>
                        </li>
                    </ol>
                </section>
            </div>

            <CommentSection v-if="!isDraft" :recipe-id="recipe.id" :comments="comments.data" />
        </div>
    </AppLayout>
</template>
