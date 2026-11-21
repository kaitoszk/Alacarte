<script setup lang="ts">
import InputError from '@/components/InputError.vue';
import TextLink from '@/components/TextLink.vue';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import AuthBase from '@/layouts/AuthLayout.vue';
import { Head, useForm } from '@inertiajs/vue3';
import { LoaderCircle } from 'lucide-vue-next';

const form = useForm({
    name: '',
    handle: '',
    email: '',
    password: '',
    password_confirmation: '',
});

const submit = () => {
    form.post(route('register'), {
        onFinish: () => form.reset('password', 'password_confirmation'),
    });
};
</script>

<template>
    <AuthBase title="新規登録" description="必要な情報を入力して、アカウントを作成してください">
        <Head title="新規登録" />

        <form @submit.prevent="submit" class="flex flex-col gap-6">
            <div class="grid gap-6">
                <div class="grid gap-2">
                    <Label for="name">名前</Label>
                    <Input id="name" type="text" required autofocus tabindex="1" autocomplete="name" v-model="form.name" placeholder="山田 太郎" />
                    <InputError :message="form.errors.name" />
                </div>

                <div class="grid gap-2">
                    <Label for="handle">ユーザーID（英数字・-・_）</Label>
                    <Input id="handle" type="text" required tabindex="2" autocomplete="username" v-model="form.handle" placeholder="handle_name" />
                    <InputError :message="form.errors.handle" />
                </div>

                <div class="grid gap-2">
                    <Label for="email">メールアドレス</Label>
                    <Input id="email" type="email" required tabindex="3" autocomplete="email" v-model="form.email" placeholder="email@example.com" />
                    <InputError :message="form.errors.email" />
                </div>

                <div class="grid gap-2">
                    <Label for="password">パスワード</Label>
                    <Input
                        id="password"
                        type="password"
                        required
                        tabindex="4"
                        autocomplete="new-password"
                        v-model="form.password"
                        placeholder="8文字以上"
                    />
                    <InputError :message="form.errors.password" />
                </div>

                <div class="grid gap-2">
                    <Label for="password_confirmation">パスワード（確認）</Label>
                    <Input
                        id="password_confirmation"
                        type="password"
                        required
                        tabindex="5"
                        autocomplete="new-password"
                        v-model="form.password_confirmation"
                        placeholder="もう一度入力"
                    />
                    <InputError :message="form.errors.password_confirmation" />
                </div>

                <Button type="submit" class="mt-2 w-full" tabindex="6" :disabled="form.processing">
                    <LoaderCircle v-if="form.processing" class="h-4 w-4 animate-spin" />
                    アカウントを作成
                </Button>
            </div>

            <div class="text-center text-sm text-muted-foreground">
                すでにアカウントをお持ちの方は
                <TextLink :href="route('login')" class="underline underline-offset-4" :tabindex="7">ログイン</TextLink>
            </div>
        </form>
    </AuthBase>
</template>
