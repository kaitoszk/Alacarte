<script setup lang="ts">
import { Avatar, AvatarFallback, AvatarImage } from '@/components/ui/avatar';
import { getInitials } from '@/composables/useInitials';
import type { Recipe } from '@/types/recipe';
import { Link } from '@inertiajs/vue3';
import { Clock, Heart, MessageCircle } from 'lucide-vue-next';

defineProps<{
    recipe: Recipe;
}>();
</script>

<template>
    <Link
        :href="route('recipes.show', { recipe: recipe.id })"
        class="group flex flex-col overflow-hidden rounded-xl border bg-card transition hover:-translate-y-0.5 hover:shadow-md"
    >
        <div class="relative overflow-hidden">
            <img
                :src="recipe.recipe_image_url ?? undefined"
                :alt="recipe.title"
                class="aspect-[4/3] w-full object-cover transition duration-300 group-hover:scale-105"
                loading="lazy"
            />
            <span class="absolute left-3 top-3 flex items-center gap-1 rounded-full bg-background/90 px-2.5 py-1 text-xs font-medium shadow-sm">
                <Clock class="h-3.5 w-3.5" />{{ recipe.cooking_time }}分
            </span>
        </div>

        <div class="flex flex-1 flex-col gap-2 p-4">
            <h2 class="line-clamp-2 font-semibold leading-snug group-hover:text-primary-strong">{{ recipe.title }}</h2>
            <p v-if="recipe.description" class="line-clamp-1 text-sm text-muted-foreground">{{ recipe.description }}</p>

            <div class="mt-auto flex items-center justify-between gap-2 pt-2 text-xs text-muted-foreground">
                <span v-if="recipe.user" class="flex min-w-0 items-center gap-1.5">
                    <Avatar class="size-6 shrink-0">
                        <AvatarImage v-if="recipe.user.user_image_url" :src="recipe.user.user_image_url" :alt="recipe.user.name" />
                        <AvatarFallback class="text-[10px]">{{ getInitials(recipe.user.name) }}</AvatarFallback>
                    </Avatar>
                    <span class="truncate">{{ recipe.user.name }}</span>
                </span>

                <span class="flex shrink-0 items-center gap-3">
                    <span v-if="recipe.favorites_count !== undefined" class="flex items-center gap-1" aria-label="お気に入り数">
                        <Heart class="h-3.5 w-3.5" />{{ recipe.favorites_count }}
                    </span>
                    <span v-if="recipe.comments_count !== undefined" class="flex items-center gap-1" aria-label="コメント数">
                        <MessageCircle class="h-3.5 w-3.5" />{{ recipe.comments_count }}
                    </span>
                </span>
            </div>
        </div>
    </Link>
</template>
