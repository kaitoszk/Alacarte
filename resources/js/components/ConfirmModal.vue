<script setup lang="ts">
import { Button } from '@/components/ui/button';
import {
    Dialog,
    DialogClose,
    DialogContent,
    DialogDescription,
    DialogFooter,
    DialogHeader,
    DialogTitle,
    DialogTrigger,
} from '@/components/ui/dialog';

/**
 * アプリ共通の確認モーダル。ui/dialog を直接組み立てず、確認が必要な操作はすべてこれを使う。
 * - trigger スロット: モーダルを開くボタン
 * - default スロット: 本文に差し込む入力欄など（例: 退会時のパスワード）
 */
withDefaults(
    defineProps<{
        title: string;
        description?: string;
        confirmLabel?: string;
        cancelLabel?: string;
        variant?: 'destructive' | 'default';
        processing?: boolean;
    }>(),
    {
        description: undefined,
        confirmLabel: '削除する',
        cancelLabel: 'キャンセル',
        variant: 'destructive',
        processing: false,
    },
);

const open = defineModel<boolean>('open', { default: false });

const emit = defineEmits<{
    confirm: [];
    cancel: [];
}>();
</script>

<template>
    <Dialog v-model:open="open">
        <DialogTrigger v-if="$slots.trigger" as-child>
            <slot name="trigger" />
        </DialogTrigger>
        <DialogContent>
            <!-- form にして Enter キーでも確定できるようにする -->
            <form class="space-y-6" @submit.prevent="emit('confirm')">
                <DialogHeader class="space-y-3">
                    <DialogTitle>{{ title }}</DialogTitle>
                    <DialogDescription v-if="description">{{ description }}</DialogDescription>
                </DialogHeader>

                <slot />

                <DialogFooter class="gap-2">
                    <DialogClose as-child>
                        <Button type="button" variant="secondary" @click="emit('cancel')">{{ cancelLabel }}</Button>
                    </DialogClose>
                    <Button type="submit" :variant="variant" :disabled="processing">{{ confirmLabel }}</Button>
                </DialogFooter>
            </form>
        </DialogContent>
    </Dialog>
</template>
