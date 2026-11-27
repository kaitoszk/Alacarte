<script setup lang="ts">
import { Button } from '@/components/ui/button';
import { Dialog, DialogContent, DialogDescription, DialogTitle } from '@/components/ui/dialog';
import type { Step } from '@/types/recipe';
import { useEventListener, useWakeLock } from '@vueuse/core';
import { ChevronLeft, ChevronRight } from 'lucide-vue-next';
import { computed, onBeforeUnmount, ref, watch } from 'vue';

/**
 * 調理モード。手順を全画面で1つずつ表示し、開いている間は画面のスリープを防ぐ。
 * Wake Lock 非対応のブラウザではスリープ防止だけを諦め、表示はそのまま使える。
 */
const props = defineProps<{
    title: string;
    steps: Step[];
}>();

const open = defineModel<boolean>('open', { default: false });

const currentIndex = ref(0);
const currentStep = computed(() => props.steps[currentIndex.value]);
const isFirst = computed(() => currentIndex.value === 0);
const isLast = computed(() => currentIndex.value === props.steps.length - 1);

const prev = () => {
    if (!isFirst.value) {
        currentIndex.value--;
    }
};

const next = () => {
    if (isLast.value) {
        open.value = false;
        return;
    }
    currentIndex.value++;
};

const wakeLock = useWakeLock();

const requestWakeLock = async () => {
    if (!wakeLock.isSupported.value) {
        return;
    }
    try {
        await wakeLock.request('screen');
    } catch {
        // 省電力モードなどで拒否されても、調理モード自体は使えるので握りつぶす
    }
};

const releaseWakeLock = async () => {
    if (wakeLock.isSupported.value) {
        await wakeLock.release();
    }
};

watch(open, (isOpen) => {
    if (isOpen) {
        currentIndex.value = 0;
        requestWakeLock();
    } else {
        releaseWakeLock();
    }
});

onBeforeUnmount(releaseWakeLock);

useEventListener('keydown', (event: KeyboardEvent) => {
    if (!open.value) {
        return;
    }
    if (event.key === 'ArrowLeft') {
        prev();
    } else if (event.key === 'ArrowRight' && !isLast.value) {
        // 最後の手順で → を押しても勝手に閉じないようにする
        next();
    }
});
</script>

<template>
    <Dialog v-model:open="open">
        <DialogContent
            class="left-0 top-0 flex h-dvh max-w-none translate-x-0 translate-y-0 flex-col gap-6 rounded-none border-0 p-4 data-[state=closed]:animate-none data-[state=open]:animate-none sm:rounded-none sm:p-8"
        >
            <div class="flex flex-col gap-1 pr-8">
                <DialogTitle class="truncate text-base text-muted-foreground">{{ title }}</DialogTitle>
                <DialogDescription class="sr-only">手順を1つずつ表示します</DialogDescription>
                <p class="text-xl font-semibold">手順 {{ currentIndex + 1 }} / {{ steps.length }}</p>
            </div>

            <div v-if="currentStep" class="flex min-h-0 flex-1 flex-col gap-6 overflow-y-auto">
                <p class="whitespace-pre-wrap text-2xl leading-relaxed sm:text-3xl">{{ currentStep.description }}</p>
                <div v-if="currentStep.images?.length" class="flex flex-wrap gap-3">
                    <img
                        v-for="image in currentStep.images"
                        :key="image.id"
                        :src="image.step_image_url"
                        :alt="`手順${currentStep.order_number}の画像`"
                        class="max-h-[40vh] w-full rounded-lg object-cover sm:w-auto"
                    />
                </div>
            </div>

            <div class="grid grid-cols-2 gap-4">
                <Button type="button" variant="secondary" size="lg" class="h-16 text-lg" :disabled="isFirst" @click="prev">
                    <ChevronLeft class="h-6 w-6" />
                    前へ
                </Button>
                <Button type="button" size="lg" class="h-16 text-lg" @click="next">
                    {{ isLast ? '完了' : '次へ' }}
                    <ChevronRight v-if="!isLast" class="h-6 w-6" />
                </Button>
            </div>
        </DialogContent>
    </Dialog>
</template>
