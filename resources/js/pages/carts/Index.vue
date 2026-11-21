<script setup lang="ts">
import ConfirmModal from '@/components/ConfirmModal.vue';
import { Button } from '@/components/ui/button';
import { Checkbox } from '@/components/ui/checkbox';
import AppLayout from '@/layouts/AppLayout.vue';
import { type BreadcrumbItem } from '@/types';
import type { CartGroup, CartItem } from '@/types/cart';
import { Head, Link, router } from '@inertiajs/vue3';
import { Trash2 } from 'lucide-vue-next';
import { computed, ref } from 'vue';

const props = defineProps<{
    groups: CartGroup[];
}>();

const breadcrumbs: BreadcrumbItem[] = [{ title: '買い物リスト', href: '/shopping-list' }];

const hasChecked = computed(() => props.groups.some((group) => group.items.some((item) => item.is_checked)));

/** 反転後の値を明示して送る（サーバー側は冪等） */
const toggle = (item: CartItem) => {
    router.patch(route('carts.update', { cart: item.id }), { is_checked: !item.is_checked }, { preserveScroll: true });
};

const destroy = (item: CartItem) => {
    router.delete(route('carts.destroy', { cart: item.id }), { preserveScroll: true });
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
                <h1 class="text-2xl font-semibold">買い物リスト</h1>

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

            <div v-if="groups.length === 0" class="flex flex-col items-center gap-3 py-16 text-center">
                <p class="text-muted-foreground">買い物リストは空です。</p>
                <Button as-child variant="secondary">
                    <Link :href="route('recipes.index')">レシピを探す</Link>
                </Button>
            </div>

            <div v-else class="flex max-w-2xl flex-col gap-6">
                <section v-for="group in groups" :key="group.recipe.id" class="rounded-lg border p-4">
                    <h2 class="mb-3 text-lg font-medium">
                        <span v-if="group.recipe.is_deleted">{{ group.recipe.title }}（削除されたレシピ）</span>
                        <Link v-else :href="route('recipes.show', { recipe: group.recipe.id })" class="hover:underline">
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
                                :aria-pressed="item.is_checked"
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
        </div>
    </AppLayout>
</template>
