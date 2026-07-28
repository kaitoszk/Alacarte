<script setup lang="ts">
import RecipeCard from '@/components/recipes/RecipeCard.vue';
import { Avatar, AvatarFallback, AvatarImage } from '@/components/ui/avatar';
import { Button } from '@/components/ui/button';
import { useInitials } from '@/composables/useInitials';
import AppLayout from '@/layouts/AppLayout.vue';
import { type BreadcrumbItem, type SharedData } from '@/types';
import type { Paginated, Recipe, UserProfile } from '@/types/recipe';
import { Head, Link, router, usePage } from '@inertiajs/vue3';
import { UserCheck, UserPlus } from 'lucide-vue-next';
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

const breadcrumbs = computed<BreadcrumbItem[]>(() => [
    { title: user.value.name, href: route('users.show', { user: user.value.handle }) },
]);
</script>

<template>
    <Head :title="user.name" />

    <AppLayout :breadcrumbs="breadcrumbs">
        <div class="flex flex-1 flex-col gap-6 p-4">
            <div class="flex flex-wrap items-start justify-between gap-4">
                <div class="flex items-center gap-4">
                    <Avatar class="h-16 w-16 overflow-hidden rounded-full">
                        <AvatarImage v-if="user.user_image_url" :src="user.user_image_url" :alt="user.name" />
                        <AvatarFallback class="rounded-full text-lg text-black dark:text-white">
                            {{ getInitials(user.name) }}
                        </AvatarFallback>
                    </Avatar>

                    <div class="flex flex-col gap-1">
                        <h1 class="text-2xl font-semibold">{{ user.name }}</h1>
                        <p class="text-sm text-muted-foreground">@{{ user.handle }}</p>
                        <div class="flex flex-wrap gap-4 pt-1 text-sm text-muted-foreground">
                            <span>レシピ <span class="font-medium text-foreground">{{ user.recipes_count }}</span></span>
                            <span>フォロー中 <span class="font-medium text-foreground">{{ user.followings_count }}</span></span>
                            <span>フォロワー <span class="font-medium text-foreground">{{ user.followers_count }}</span></span>
                        </div>
                    </div>
                </div>

                <Button v-if="canFollow" :variant="isFollowing ? 'secondary' : 'default'" class="gap-1.5" @click="toggleFollow">
                    <UserCheck v-if="isFollowing" class="h-4 w-4" />
                    <UserPlus v-else class="h-4 w-4" />
                    {{ isFollowing ? 'フォロー中' : 'フォロー' }}
                </Button>

                <Button v-else-if="isMe" as-child>
                    <Link :href="route('recipes.create')">レシピを投稿</Link>
                </Button>
            </div>

            <div v-if="recipes.data.length === 0" class="flex flex-col items-center gap-3 py-16 text-center">
                <p class="text-muted-foreground">
                    {{ isMe ? 'まだレシピを投稿していません。' : 'まだレシピがありません。' }}
                </p>
                <Button v-if="isMe" as-child variant="secondary">
                    <Link :href="route('recipes.create')">最初のレシピを投稿する</Link>
                </Button>
            </div>

            <div v-else class="grid gap-4 sm:grid-cols-2 lg:grid-cols-3">
                <RecipeCard v-for="recipe in recipes.data" :key="recipe.id" :recipe="recipe" />
            </div>

            <div v-if="recipes.meta.last_page > 1" class="flex flex-wrap justify-center gap-1">
                <template v-for="link in recipes.meta.links" :key="link.label">
                    <Link
                        v-if="link.url"
                        :href="link.url"
                        class="rounded-md border px-3 py-1.5 text-sm"
                        :class="link.active ? 'bg-primary text-primary-foreground' : 'hover:bg-accent'"
                    >
                        <!-- ラベルに &laquo; などのエンティティが入るため v-html。コンポーネントではなく素の要素に付ける -->
                        <span v-html="link.label" />
                    </Link>
                    <span v-else class="rounded-md border px-3 py-1.5 text-sm text-muted-foreground" v-html="link.label" />
                </template>
            </div>
        </div>
    </AppLayout>
</template>
