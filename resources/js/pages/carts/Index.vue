<script setup lang="ts">
import ConfirmModal from '@/components/ConfirmModal.vue';
import EmptyState from '@/components/EmptyState.vue';
import { Button } from '@/components/ui/button';
import { Checkbox } from '@/components/ui/checkbox';
import AppLayout from '@/layouts/AppLayout.vue';
import { mergeCartItems } from '@/lib/cart';
import { type BreadcrumbItem } from '@/types';
import type { CartGroup, CartItem, MergedCartItem } from '@/types/cart';
import { Head, Link, router } from '@inertiajs/vue3';
import { useStorage } from '@vueuse/core';
import { ShoppingCart, Trash2 } from 'lucide-vue-next';
import { computed, ref } from 'vue';

const props = defineProps<{
    groups: CartGroup[];
}>();

const breadcrumbs: BreadcrumbItem[] = [{ title: '買い物リスト', href: '/shopping-list' }];

type View = 'ingredient' | 'recipe';

const views: ReadonlyArray<{ key: View; label: string }> = [
    { key: 'ingredient', label: '材料ごと' },
    { key: 'recipe', label: 'レシピごと' },
];

/** PATCH/DELETE 後はページが作り直されるので、ref ではなく localStorage に持つ */
const view = useStorage<View>('shopping-list-view', 'ingredient');

const mergedItems = computed(() => mergeCartItems(props.groups));

const hasChecked = computed(() => props.groups.some((group) => group.items.some((item) => item.is_checked)));

/** 反転後の値を明示して送る（サーバー側は冪等） */
const toggle = (item: CartItem) => {
    router.patch(route('carts.update', { cart: item.id }), { is_checked: !item.is_checked }, { preserveScroll: true });
};

const destroy = (item: CartItem) => {
    router.delete(route('carts.destroy', { cart: item.id }), { preserveScroll: true });
};

/** まとめた行は、元の行がすべてチェック済みなら外し、1つでも未チェックなら全行に付ける */
const toggleMerged = (item: MergedCartItem) => {
    router.patch(route('carts.bulkUpdate'), { ids: item.ids, is_checked: !item.is_checked }, { preserveScroll: true });
};

const destroyMerged = (item: MergedCartItem) => {
    router.delete(route('carts.bulkDestroy'), { data: { ids: item.ids }, preserveScroll: true });
};

const isConfirmOpen = ref(false);
const destroyChecked = () => {
    router.delete(route('carts.destroyChecked'), {
        preserveScroll: true,
        onSuccess: () => (isConfirmOpen.value = false),
    });
};
</script>

<template>
    <Head title="買い物リスト" />

    <AppLayout :breadcrumbs="breadcrumbs">
        <div class="flex flex-1 flex-col gap-6 p-4">
            <div class="flex max-w-2xl flex-wrap items-center justify-between gap-2">
                <h1 class="text-2xl font-bold">買い物リスト</h1>

                <ConfirmModal
                    v-if="hasChecked"
                    v-model:open="isConfirmOpen"
                    title="チェック済みの材料を削除しますか？"
                    description="チェックを付けた材料が買い物リストから消えます。"
                    @confirm="destroyChecked"
                >
                    <template #trigger>
                        <Button variant="outline" size="sm">チェック済みを削除</Button>
                    </template>
                </ConfirmModal>
            </div>

            <EmptyState
                v-if="groups.length === 0"
                :icon="ShoppingCart"
                title="買い物リストは空です"
                description="レシピ詳細の「買い物リストに追加」で、材料がまとめて入ります。"
            >
                <Button as-child>
                    <Link :href="route('recipes.index')">レシピを探す</Link>
                </Button>
            </EmptyState>

            <template v-else>
                <div class="inline-flex w-fit rounded-full border bg-muted p-1" role="tablist" aria-label="表示の切り替え">
                    <button
                        v-for="item in views"
                        :key="item.key"
                        type="button"
                        role="tab"
                        :aria-selected="view === item.key"
                        class="rounded-full px-4 py-1.5 text-sm font-medium transition"
                        :class="view === item.key ? 'bg-background text-foreground shadow-sm' : 'text-muted-foreground hover:text-foreground'"
                        @click="view = item.key"
                    >
                        {{ item.label }}
                    </button>
                </div>

                <section v-if="view === 'ingredient'" class="max-w-2xl rounded-xl border bg-card p-5">
                    <ul class="divide-y">
                        <li v-for="item in mergedItems" :key="item.name" class="flex items-center gap-2 py-1">
                            <!-- 行全体をボタンにしてチェックを切り替える。Checkbox は表示専用（クリックが二重に発火しないように） -->
                            <button
                                type="button"
                                class="flex flex-1 items-center gap-3 rounded-md px-1 py-1.5 text-left text-sm hover:bg-accent"
                                :class="item.is_checked ? 'text-muted-foreground line-through' : ''"
                                :aria-pressed="item.is_checked ? 'true' : 'false'"
                                @click="toggleMerged(item)"
                            >
                                <Checkbox :checked="item.is_checked" class="pointer-events-none" tabindex="-1" aria-hidden="true" />
                                <span class="flex flex-1 flex-col">
                                    <span>{{ item.name }}</span>
                                    <span class="text-xs text-muted-foreground">{{ item.recipeTitles.join('・') }}</span>
                                </span>
                                <span>{{ item.quantities.join(' + ') }}</span>
                            </button>
                            <Button variant="ghost" size="icon" :aria-label="`${item.name}を削除`" @click="destroyMerged(item)">
                                <Trash2 class="h-4 w-4" />
                            </Button>
                        </li>
                    </ul>
                </section>

                <div v-else class="flex max-w-2xl flex-col gap-6">
                    <section v-for="group in groups" :key="group.recipe.id" class="rounded-xl border bg-card p-5">
                        <h2 class="mb-3 text-lg font-medium">
                            <span v-if="group.recipe.is_deleted">{{ group.recipe.title }}（削除されたレシピ）</span>
                            <Link v-else :href="route('recipes.show', { recipe: group.recipe.id })" class="hover:text-primary-strong hover:underline">
                                {{ group.recipe.title }}
                            </Link>
                        </h2>
                        <ul class="divide-y">
                            <li v-for="item in group.items" :key="item.id" class="flex items-center gap-2 py-1">
                                <!-- 行全体をボタンにしてチェックを切り替える。Checkbox は表示専用（クリックが二重に発火しないように） -->
                                <button
                                    type="button"
                                    class="flex flex-1 items-center gap-3 rounded-md px-1 py-1.5 text-left text-sm hover:bg-accent"
                                    :class="item.is_checked ? 'text-muted-foreground line-through' : ''"
                                    :aria-pressed="item.is_checked ? 'true' : 'false'"
                                    @click="toggle(item)"
                                >
                                    <Checkbox :checked="item.is_checked" class="pointer-events-none" tabindex="-1" aria-hidden="true" />
                                    <span class="flex-1">{{ item.name }}</span>
                                    <span>{{ item.quantity }}</span>
                                </button>
                                <Button variant="ghost" size="icon" :aria-label="`${item.name}を削除`" @click="destroy(item)">
                                    <Trash2 class="h-4 w-4" />
                                </Button>
                            </li>
                        </ul>
                    </section>
                </div>
            </template>
        </div>
    </AppLayout>
</template>
