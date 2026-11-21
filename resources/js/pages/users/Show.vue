<script setup lang="ts">
import EmptyState from '@/components/EmptyState.vue';
import Pagination from '@/components/Pagination.vue';
import RecipeCard from '@/components/recipes/RecipeCard.vue';
import { Avatar, AvatarFallback, AvatarImage } from '@/components/ui/avatar';
import { Button } from '@/components/ui/button';
import { useInitials } from '@/composables/useInitials';
import AppLayout from '@/layouts/AppLayout.vue';
import { type BreadcrumbItem, type SharedData } from '@/types';
import type { Paginated, Recipe, UserProfile } from '@/types/recipe';
import { Head, Link, router, usePage } from '@inertiajs/vue3';
import { Plus, UserCheck, UserPlus, UtensilsCrossed } from 'lucide-vue-next';
import { computed } from 'vue';

const props = defineProps<{
    user: { data: UserProfile };
    recipes: Paginated<Recipe>;
    isFollowing: boolean;
}>();

const page = usePage<SharedData>();
const { getInitials } = useInitials();

const user = computed(() => props.user.data);
const isLoggedIn = computed(() => page.props.auth.user !== null);
const isMe = computed(() => page.props.auth.user?.id === user.value.id);

/** フォローボタンは自分自身とゲストには出さない */
const canFollow = computed(() => isLoggedIn.value && !isMe.value);

const toggleFollow = () => {
    const params = { user: user.value.handle };
    const options = { preserveScroll: true };

    if (props.isFollowing) {
        router.delete(route('follows.destroy', params), options);

        return;
    }

    router.post(route('follows.store', params), {}, options);
};

const breadcrumbs = computed<BreadcrumbItem[]>(() => [{ title: user.value.name, href: route('users.show', { user: user.value.handle }) }]);
</script>

<template>
    <Head :title="user.name" />

    <AppLayout :breadcrumbs="breadcrumbs">
        <div class="flex flex-1 flex-col gap-6 p-4">
            <!-- プロフィール -->
            <section class="flex flex-col gap-5 rounded-2xl border bg-card p-6 sm:flex-row sm:items-center sm:justify-between">
                <div class="flex items-center gap-4">
                    <Avatar class="size-20 overflow-hidden rounded-full ring-4 ring-primary/15">
                        <AvatarImage v-if="user.user_image_url" :src="user.user_image_url" :alt="user.name" />
                        <AvatarFallback class="rounded-full text-xl">{{ getInitials(user.name) }}</AvatarFallback>
                    </Avatar>

                    <div class="flex min-w-0 flex-col gap-1">
                        <h1 class="truncate text-2xl font-bold">{{ user.name }}</h1>
                        <p class="text-sm text-muted-foreground">@{{ user.handle }}</p>
                    </div>
                </div>

                <Button v-if="canFollow" :variant="isFollowing ? 'secondary' : 'default'" class="gap-1.5" @click="toggleFollow">
                    <UserCheck v-if="isFollowing" class="h-4 w-4" />
                    <UserPlus v-else class="h-4 w-4" />
                    {{ isFollowing ? 'フォロー中' : 'フォロー' }}
                </Button>

                <Button v-else-if="isMe" as-child>
                    <Link :href="route('recipes.create')"><Plus class="h-4 w-4" />レシピを投稿</Link>
                </Button>
            </section>

            <dl class="grid grid-cols-3 divide-x rounded-xl border bg-card text-center">
                <div class="py-3">
                    <dt class="text-xs text-muted-foreground">レシピ</dt>
                    <dd class="text-lg font-bold">{{ user.recipes_count }}</dd>
                </div>
                <div class="py-3">
                    <dt class="text-xs text-muted-foreground">フォロー中</dt>
                    <dd class="text-lg font-bold">{{ user.followings_count }}</dd>
                </div>
                <div class="py-3">
                    <dt class="text-xs text-muted-foreground">フォロワー</dt>
                    <dd class="text-lg font-bold">{{ user.followers_count }}</dd>
                </div>
            </dl>

            <h2 class="text-lg font-semibold">{{ isMe ? '投稿したレシピ' : `${user.name}さんのレシピ` }}</h2>

            <EmptyState
                v-if="recipes.data.length === 0"
                :icon="UtensilsCrossed"
                :title="isMe ? 'まだレシピを投稿していません' : 'まだレシピがありません'"
                :description="isMe ? '得意料理を投稿して、みんなの「今日何つくる？」に応えましょう。' : undefined"
            >
                <Button v-if="isMe" as-child>
                    <Link :href="route('recipes.create')">最初のレシピを投稿する</Link>
                </Button>
            </EmptyState>

            <div v-else class="grid gap-5 sm:grid-cols-2 lg:grid-cols-3 xl:grid-cols-4">
                <RecipeCard v-for="recipe in recipes.data" :key="recipe.id" :recipe="recipe" />
            </div>

            <Pagination v-if="recipes.meta.last_page > 1" :links="recipes.meta.links" />
        </div>
    </AppLayout>
</template>
