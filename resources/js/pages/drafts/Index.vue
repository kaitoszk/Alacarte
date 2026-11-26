<script setup lang="ts">
import ConfirmModal from '@/components/ConfirmModal.vue';
import EmptyState from '@/components/EmptyState.vue';
import Pagination from '@/components/Pagination.vue';
import { Button } from '@/components/ui/button';
import AppLayout from '@/layouts/AppLayout.vue';
import { type BreadcrumbItem } from '@/types';
import type { Paginated, Recipe } from '@/types/recipe';
import { Head, Link, router } from '@inertiajs/vue3';
import { FilePen } from 'lucide-vue-next';
import { ref } from 'vue';

defineProps<{
    drafts: Paginated<Recipe>;
}>();

const breadcrumbs: BreadcrumbItem[] = [{ title: '下書き', href: '/drafts' }];

const formatter = new Intl.DateTimeFormat('ja-JP', { dateStyle: 'medium', timeStyle: 'short' });
const formatUpdatedAt = (value: string) => formatter.format(new Date(value));

/** 削除確認を開いている下書きの id。モーダルは行ごとに置き、開閉はこの値で管理する */
const confirmingId = ref<number | null>(null);

const destroy = (draft: Recipe) => {
    router.delete(route('recipes.destroy', { recipe: draft.id }), {
        preserveScroll: true,
        onSuccess: () => (confirmingId.value = null),
    });
};
</script>

<template>
    <Head title="下書き" />

    <AppLayout :breadcrumbs="breadcrumbs">
        <div class="flex flex-1 flex-col gap-6 p-4">
            <div>
                <h1 class="text-2xl font-bold">下書き</h1>
                <p class="mt-1 text-sm text-muted-foreground">途中まで書いたレシピです。公開するまで自分にしか見えません。</p>
            </div>

            <EmptyState
                v-if="drafts.data.length === 0"
                :icon="FilePen"
                title="下書きはありません"
                description="レシピの投稿画面で「下書き保存」を押すと、ここに並びます。"
            >
                <Button as-child>
                    <Link :href="route('recipes.create')">レシピを書く</Link>
                </Button>
            </EmptyState>

            <ul v-else class="flex max-w-2xl flex-col divide-y rounded-xl border bg-card">
                <li v-for="draft in drafts.data" :key="draft.id" class="flex flex-wrap items-center justify-between gap-3 px-5 py-4">
                    <div class="min-w-0">
                        <Link
                            :href="route('recipes.show', { recipe: draft.id })"
                            class="break-words font-medium hover:text-primary-strong hover:underline"
                        >
                            {{ draft.title }}
                        </Link>
                        <p class="mt-1 text-xs text-muted-foreground">最終更新: {{ formatUpdatedAt(draft.updated_at) }}</p>
                    </div>
                    <div class="flex gap-2">
                        <Button variant="outline" size="sm" as-child>
                            <Link :href="route('recipes.edit', { recipe: draft.id })">編集</Link>
                        </Button>
                        <ConfirmModal
                            :open="confirmingId === draft.id"
                            title="下書きを削除しますか？"
                            :description="`「${draft.title}」を削除します。この操作は取り消せません。`"
                            @update:open="(open: boolean) => (confirmingId = open ? draft.id : null)"
                            @confirm="destroy(draft)"
                        >
                            <template #trigger>
                                <Button variant="outline" size="sm">削除</Button>
                            </template>
                        </ConfirmModal>
                    </div>
                </li>
            </ul>

            <Pagination v-if="drafts.meta.last_page > 1" :links="drafts.meta.links" />
        </div>
    </AppLayout>
</template>
