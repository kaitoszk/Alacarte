<script setup lang="ts">
import { useForm } from '@inertiajs/vue3';
import { ref } from 'vue';

import ConfirmModal from '@/components/ConfirmModal.vue';
import HeadingSmall from '@/components/HeadingSmall.vue';
import InputError from '@/components/InputError.vue';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';

const isOpen = ref(false);
const passwordInput = ref<InstanceType<typeof Input> | null>(null);

const form = useForm({
    password: '',
});

const deleteUser = () => {
    form.delete(route('profile.destroy'), {
        preserveScroll: true,
        onSuccess: () => closeModal(),
        onError: () => (passwordInput.value?.$el as HTMLInputElement | undefined)?.focus(),
        onFinish: () => form.reset(),
    });
};

const closeModal = () => {
    isOpen.value = false;
    form.clearErrors();
    form.reset();
};
</script>

<template>
    <div class="space-y-6">
        <HeadingSmall title="退会" description="アカウントと、投稿したレシピなどのデータを削除します" />
        <div class="space-y-4 rounded-lg border border-red-100 bg-red-50 p-4 dark:border-red-200/10 dark:bg-red-700/10">
            <div class="relative space-y-0.5 text-red-600 dark:text-red-100">
                <p class="font-medium">注意</p>
                <p class="text-sm">この操作は取り消せません。</p>
            </div>
            <ConfirmModal
                v-model:open="isOpen"
                title="本当に退会しますか？"
                description="退会すると、アカウントに紐づくデータはすべて削除されます。確認のため、パスワードを入力してください。"
                confirm-label="退会する"
                :processing="form.processing"
                @confirm="deleteUser"
                @cancel="closeModal"
            >
                <template #trigger>
                    <Button variant="destructive">退会する</Button>
                </template>

                <div class="grid gap-2">
                    <Label for="password" class="sr-only">パスワード</Label>
                    <Input id="password" ref="passwordInput" v-model="form.password" type="password" name="password" placeholder="パスワード" />
                    <InputError :message="form.errors.password" />
                </div>
            </ConfirmModal>
        </div>
    </div>
</template>
