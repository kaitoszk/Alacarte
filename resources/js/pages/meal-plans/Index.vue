<script setup lang="ts">
import { Button } from '@/components/ui/button';
import AppLayout from '@/layouts/AppLayout.vue';
import { formatMonthDayWithWeekday } from '@/lib/date';
import { type BreadcrumbItem } from '@/types';
import type { MealPlan, MealPlanDay } from '@/types/meal-plan';
import { Head, Link, router, useForm } from '@inertiajs/vue3';
import { ChevronLeft, ChevronRight, ImageOff, ShoppingCart, X } from 'lucide-vue-next';
import { computed } from 'vue';

const props = defineProps<{
    days: MealPlanDay[];
    /** 日本時間の今日（Y-m-d） */
    today: string;
    prevWeek: string;
    nextWeek: string;
}>();

const breadcrumbs: BreadcrumbItem[] = [{ title: '献立', href: '/meal-plan' }];

const weekLabel = computed(
    () => `${formatMonthDayWithWeekday(props.days[0].date)} 〜 ${formatMonthDayWithWeekday(props.days[props.days.length - 1].date)}`,
);
const isThisWeek = computed(() => props.days.some((day) => day.date === props.today));
/** 過去の日の献立は買い物の対象にしないので、今日以降に献立がある週だけボタンを出す（Y-m-d は文字列比較で日付順になる） */
const hasUpcomingMealPlans = computed(() => props.days.some((day) => day.date >= props.today && day.meal_plans.length > 0));

/** 同じレシピは二重に入らないので、確認モーダルは出さない */
const cartForm = useForm({ week: '' });
const addWeekToCart = () => {
    cartForm.week = props.days[0].date;
    cartForm.post(route('meal-plans.cart.store'), { preserveScroll: true });
};

/** 外してもレシピ詳細からすぐ入れ直せるので、確認モーダルは出さない */
const remove = (mealPlan: MealPlan) => {
    router.delete(route('meal-plans.destroy', { mealPlan: mealPlan.id }), { preserveScroll: true });
};
</script>

<template>
    <Head title="献立" />

    <AppLayout :breadcrumbs="breadcrumbs">
        <div class="flex max-w-2xl flex-1 flex-col gap-6 p-4">
            <div class="flex flex-wrap items-center justify-between gap-2">
                <h1 class="text-2xl font-bold">献立</h1>

                <nav class="flex items-center gap-1" aria-label="週の切り替え">
                    <Button variant="ghost" size="icon" as-child>
                        <Link :href="route('meal-plans.index', { week: prevWeek })" aria-label="前の週" preserve-scroll>
                            <ChevronLeft class="h-4 w-4" />
                        </Link>
                    </Button>
                    <span class="min-w-40 text-center text-sm font-medium">{{ weekLabel }}</span>
                    <Button variant="ghost" size="icon" as-child>
                        <Link :href="route('meal-plans.index', { week: nextWeek })" aria-label="次の週" preserve-scroll>
                            <ChevronRight class="h-4 w-4" />
                        </Link>
                    </Button>
                    <Button v-if="!isThisWeek" variant="outline" size="sm" as-child>
                        <Link :href="route('meal-plans.index')">今週</Link>
                    </Button>
                </nav>
            </div>

            <p class="text-sm text-muted-foreground">レシピ詳細の「献立に追加」で、作る日を決められます。</p>

            <div v-if="hasUpcomingMealPlans">
                <Button variant="outline" :disabled="cartForm.processing" @click="addWeekToCart">
                    <ShoppingCart class="h-4 w-4" />
                    この週の材料を買い物リストに追加
                </Button>
                <p v-if="cartForm.errors.week" class="mt-1 text-sm text-destructive">{{ cartForm.errors.week }}</p>
            </div>

            <ol class="flex flex-col gap-3">
                <li
                    v-for="day in days"
                    :key="day.date"
                    :data-date="day.date"
                    class="rounded-xl border bg-card p-4"
                    :class="day.date === today ? 'border-primary ring-1 ring-primary' : ''"
                >
                    <h2 class="mb-2 flex items-center gap-2 font-medium">
                        {{ formatMonthDayWithWeekday(day.date) }}
                        <span
                            v-if="day.date === today"
                            class="rounded-full border border-primary-strong px-2 py-0.5 text-xs font-semibold text-primary-strong"
                            >今日</span
                        >
                    </h2>

                    <p v-if="day.meal_plans.length === 0" class="text-sm text-muted-foreground">まだ決まっていません</p>

                    <ul v-else class="divide-y">
                        <li v-for="mealPlan in day.meal_plans" :key="mealPlan.id" class="flex items-center gap-3 py-2">
                            <img
                                v-if="mealPlan.recipe.recipe_image_url"
                                :src="mealPlan.recipe.recipe_image_url"
                                alt=""
                                class="h-12 w-12 shrink-0 rounded-md object-cover"
                            />
                            <span v-else class="flex h-12 w-12 shrink-0 items-center justify-center rounded-md bg-muted text-muted-foreground">
                                <ImageOff class="h-5 w-5" />
                            </span>
                            <Link
                                :href="route('recipes.show', { recipe: mealPlan.recipe.id })"
                                class="min-w-0 flex-1 truncate hover:text-primary-strong hover:underline"
                            >
                                {{ mealPlan.recipe.title }}
                            </Link>
                            <Button
                                variant="ghost"
                                size="icon"
                                class="shrink-0"
                                :aria-label="`${mealPlan.recipe.title}を献立から外す`"
                                @click="remove(mealPlan)"
                            >
                                <X class="h-4 w-4" />
                            </Button>
                        </li>
                    </ul>
                </li>
            </ol>
        </div>
    </AppLayout>
</template>
