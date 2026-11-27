<script setup lang="ts">
import AppLayout from '@/layouts/app/AppHeaderLayout.vue';
import type { BreadcrumbItemType, SharedData } from '@/types';
import { usePage } from '@inertiajs/vue3';
import { computed } from 'vue';

interface Props {
    breadcrumbs?: BreadcrumbItemType[];
}

withDefaults(defineProps<Props>(), {
    breadcrumbs: () => [],
});

const page = usePage<SharedData>();
const flashSuccess = computed(() => page.props.flash.success);
const flashInfo = computed(() => page.props.flash.info);
</script>

<template>
    <AppLayout :breadcrumbs="breadcrumbs">
        <div
            v-if="flashSuccess"
            role="status"
            class="mx-4 mt-4 max-w-2xl rounded-lg border border-primary bg-primary/10 px-4 py-2 text-sm text-primary-strong"
        >
            {{ flashSuccess }}
        </div>
        <div v-if="flashInfo" role="status" class="mx-4 mt-4 max-w-2xl rounded-lg border bg-muted px-4 py-2 text-sm text-muted-foreground">
            {{ flashInfo }}
        </div>
        <slot />
    </AppLayout>
</template>
