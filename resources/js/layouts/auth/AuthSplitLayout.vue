<script setup lang="ts">
import AppLogo from '@/components/AppLogo.vue';
import AppLogoIcon from '@/components/AppLogoIcon.vue';
import { Link } from '@inertiajs/vue3';

defineProps<{
    title?: string;
    description?: string;
}>();
</script>

<template>
    <div class="grid min-h-dvh lg:grid-cols-2">
        <!-- PC: 左にブランド面（写真の代わりに色と文字で表現する） -->
        <div class="relative hidden flex-col justify-between overflow-hidden bg-primary p-10 text-primary-foreground lg:flex">
            <Link :href="route('home')" class="relative z-10 flex items-center gap-2 text-lg font-semibold">
                <span class="flex size-9 items-center justify-center rounded-lg bg-white/15">
                    <AppLogoIcon class="size-6 text-white" />
                </span>
                Alacarte
            </Link>

            <div class="relative z-10 space-y-4">
                <p class="text-4xl font-bold leading-tight">今日、何つくる？を、<br />もっとかんたんに。</p>
                <p class="max-w-md text-base text-white/85">みんなのレシピを眺めて、決めたら材料をそのまま買い物リストへ。</p>
            </div>

            <!-- 装飾の円 -->
            <div class="pointer-events-none absolute -bottom-24 -right-24 size-96 rounded-full bg-white/10" aria-hidden="true" />
            <div class="pointer-events-none absolute -right-10 top-24 size-40 rounded-full bg-white/10" aria-hidden="true" />
        </div>

        <div class="flex items-center justify-center px-6 py-12">
            <div class="flex w-full max-w-sm flex-col gap-8">
                <!-- スマホ: 上にロゴ -->
                <Link :href="route('home')" class="flex justify-center lg:hidden" aria-label="Alacarte ホーム">
                    <AppLogo />
                </Link>

                <div class="flex flex-col gap-2 text-center">
                    <h1 v-if="title" class="text-2xl font-bold tracking-tight">{{ title }}</h1>
                    <p v-if="description" class="text-sm text-muted-foreground">{{ description }}</p>
                </div>
                <slot />
            </div>
        </div>
    </div>
</template>
