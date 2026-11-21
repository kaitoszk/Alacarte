<script setup lang="ts">
import { Head } from '@inertiajs/vue3';

import AppearanceTabs from '@/components/AppearanceTabs.vue';
import HeadingSmall from '@/components/HeadingSmall.vue';
import { type BreadcrumbItem } from '@/types';

import AppLayout from '@/layouts/AppLayout.vue';
import SettingsLayout from '@/layouts/settings/Layout.vue';

const breadcrumbItems: BreadcrumbItem[] = [
    {
        title: '表示設定',
        href: '/settings/appearance',
    },
];
</script>

<template>
    <AppLayout :breadcrumbs="breadcrumbItems">
        <Head title="表示設定" />

        <SettingsLayout>
            <div class="space-y-6">
                <HeadingSmall title="表示設定" description="ライト・ダークの表示を切り替えます" />
                <AppearanceTabs />
            </div>
        </SettingsLayout>
    </AppLayout>
</template>
