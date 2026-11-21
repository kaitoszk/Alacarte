<script setup lang="ts">
import TextLink from '@/components/TextLink.vue';
import { Button } from '@/components/ui/button';
import AuthLayout from '@/layouts/AuthLayout.vue';
import { Head, useForm } from '@inertiajs/vue3';
import { LoaderCircle } from 'lucide-vue-next';

defineProps<{
    status?: string;
}>();

const form = useForm({});

const submit = () => {
    form.post(route('verification.send'));
};
</script>

<template>
    <AuthLayout title="メールアドレスの確認" description="お送りしたメールのリンクを開いて、メールアドレスを確認してください">
        <Head title="メールアドレスの確認" />

        <div v-if="status === 'verification-link-sent'" class="mb-4 text-center text-sm font-medium text-green-600">
            登録したメールアドレスに、確認用のリンクを再送しました。
        </div>

        <form @submit.prevent="submit" class="space-y-6 text-center">
            <Button :disabled="form.processing" variant="secondary">
                <LoaderCircle v-if="form.processing" class="h-4 w-4 animate-spin" />
                確認メールを再送する
            </Button>

            <TextLink :href="route('logout')" method="post" as="button" class="mx-auto block text-sm">ログアウト</TextLink>
        </form>
    </AuthLayout>
</template>
