<script setup lang="ts">
import AppLogo from '@/components/AppLogo.vue';
import { Avatar, AvatarFallback, AvatarImage } from '@/components/ui/avatar';
import { Breadcrumb, BreadcrumbItem, BreadcrumbLink, BreadcrumbList, BreadcrumbPage, BreadcrumbSeparator } from '@/components/ui/breadcrumb';
import { Button } from '@/components/ui/button';
import { DropdownMenu, DropdownMenuContent, DropdownMenuTrigger } from '@/components/ui/dropdown-menu';
import { Sheet, SheetContent, SheetHeader, SheetTitle, SheetTrigger } from '@/components/ui/sheet';
import UserMenuContent from '@/components/UserMenuContent.vue';
import { getInitials } from '@/composables/useInitials';
import type { BreadcrumbItem as BreadcrumbItemType, NavItem, SharedData } from '@/types';
import { Link, usePage } from '@inertiajs/vue3';
import { Heart, Home, Menu, Plus, ShoppingCart, UtensilsCrossed } from 'lucide-vue-next';
import { computed } from 'vue';

interface Props {
    breadcrumbs?: BreadcrumbItemType[];
}

const props = withDefaults(defineProps<Props>(), {
    breadcrumbs: () => [],
});

const page = usePage<SharedData>();
const auth = computed(() => page.props.auth);

// お気に入り・買い物リストは認証必須ルートなので、ゲストには出さない
const navItems = computed<NavItem[]>(() => [
    { title: 'ホーム', href: '/', icon: Home },
    { title: 'レシピ', href: '/recipes', icon: UtensilsCrossed },
    ...(auth.value.user
        ? [
              { title: 'お気に入り', href: '/favorites', icon: Heart },
              { title: '買い物リスト', href: '/shopping-list', icon: ShoppingCart },
          ]
        : []),
]);

/** ホームは完全一致、それ以外は配下のページ（/recipes/1 など）も含めて現在地とみなす */
const isActive = (href: string): boolean => {
    const path = page.url.split('?')[0];

    return href === '/' ? path === '/' : path === href || path.startsWith(`${href}/`);
};
</script>

<template>
    <header class="sticky top-0 z-40 border-b bg-background/95 backdrop-blur supports-[backdrop-filter]:bg-background/80">
        <div class="mx-auto flex h-16 items-center gap-2 px-4 md:max-w-7xl">
            <!-- スマホ用メニュー -->
            <div class="lg:hidden">
                <Sheet>
                    <SheetTrigger :as-child="true">
                        <Button variant="ghost" size="icon" class="h-9 w-9" aria-label="メニューを開く">
                            <Menu class="h-5 w-5" />
                        </Button>
                    </SheetTrigger>
                    <SheetContent side="left" class="w-[280px] p-6">
                        <SheetTitle class="sr-only">メニュー</SheetTitle>
                        <SheetHeader class="flex flex-row items-center text-left">
                            <AppLogo />
                        </SheetHeader>
                        <nav class="-mx-3 mt-6 flex flex-col gap-1">
                            <Link
                                v-for="item in navItems"
                                :key="item.href"
                                :href="item.href"
                                class="flex items-center gap-3 rounded-lg px-3 py-2 text-sm font-medium hover:bg-accent"
                                :class="isActive(item.href) ? 'bg-accent text-foreground' : 'text-muted-foreground'"
                            >
                                <component :is="item.icon" class="h-5 w-5" />
                                {{ item.title }}
                            </Link>
                        </nav>
                        <div class="mt-6 flex flex-col gap-2">
                            <Button v-if="auth.user" as-child>
                                <Link :href="route('recipes.create')"><Plus class="h-4 w-4" />レシピを投稿</Link>
                            </Button>
                            <template v-else>
                                <Button as-child>
                                    <Link :href="route('register')">新規登録</Link>
                                </Button>
                                <Button variant="outline" as-child>
                                    <Link :href="route('login')">ログイン</Link>
                                </Button>
                            </template>
                        </div>
                    </SheetContent>
                </Sheet>
            </div>

            <Link :href="route('home')" class="flex items-center gap-x-2" aria-label="Alacarte ホーム">
                <AppLogo />
            </Link>

            <!-- PC 用ナビ -->
            <nav class="ml-8 hidden h-16 items-stretch gap-1 lg:flex">
                <Link
                    v-for="item in navItems"
                    :key="item.href"
                    :href="item.href"
                    class="relative flex items-center gap-2 px-3 text-sm font-medium transition-colors"
                    :class="isActive(item.href) ? 'text-foreground' : 'text-muted-foreground hover:text-foreground'"
                >
                    <component :is="item.icon" class="h-4 w-4" />
                    {{ item.title }}
                    <span v-if="isActive(item.href)" class="absolute inset-x-2 bottom-0 h-0.5 rounded-full bg-primary" />
                </Link>
            </nav>

            <div class="ml-auto flex items-center gap-2">
                <template v-if="auth.user">
                    <Button size="sm" class="hidden sm:inline-flex" as-child>
                        <Link :href="route('recipes.create')"><Plus class="h-4 w-4" />レシピを投稿</Link>
                    </Button>

                    <DropdownMenu>
                        <DropdownMenuTrigger :as-child="true">
                            <Button
                                variant="ghost"
                                size="icon"
                                class="relative size-10 w-auto rounded-full p-1 focus-within:ring-2 focus-within:ring-primary"
                                aria-label="ユーザーメニュー"
                            >
                                <Avatar class="size-8 overflow-hidden rounded-full">
                                    <AvatarImage :src="auth.user.avatar ?? ''" :alt="auth.user.name" />
                                    <AvatarFallback class="rounded-lg bg-neutral-200 font-semibold text-black dark:bg-neutral-700 dark:text-white">
                                        {{ getInitials(auth.user.name) }}
                                    </AvatarFallback>
                                </Avatar>
                            </Button>
                        </DropdownMenuTrigger>
                        <DropdownMenuContent align="end" class="w-56">
                            <UserMenuContent :user="auth.user" />
                        </DropdownMenuContent>
                    </DropdownMenu>
                </template>

                <template v-else>
                    <Button variant="ghost" size="sm" as-child>
                        <Link :href="route('login')">ログイン</Link>
                    </Button>
                    <Button size="sm" class="hidden sm:inline-flex" as-child>
                        <Link :href="route('register')">新規登録</Link>
                    </Button>
                </template>
            </div>
        </div>

        <div v-if="props.breadcrumbs.length > 1" class="border-t">
            <div class="mx-auto flex h-10 items-center px-4 md:max-w-7xl">
                <Breadcrumb>
                    <BreadcrumbList>
                        <template v-for="(item, index) in props.breadcrumbs" :key="index">
                            <BreadcrumbItem>
                                <BreadcrumbPage v-if="index === props.breadcrumbs.length - 1">{{ item.title }}</BreadcrumbPage>
                                <BreadcrumbLink v-else as-child>
                                    <Link :href="item.href">{{ item.title }}</Link>
                                </BreadcrumbLink>
                            </BreadcrumbItem>
                            <BreadcrumbSeparator v-if="index !== props.breadcrumbs.length - 1" />
                        </template>
                    </BreadcrumbList>
                </Breadcrumb>
            </div>
        </div>
    </header>
</template>
