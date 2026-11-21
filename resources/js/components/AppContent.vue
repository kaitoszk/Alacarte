<script setup lang="ts">
interface Props {
    class?: string;
}

const props = defineProps<Props>();
</script>

<template>
    <main class="mx-auto flex h-full w-full max-w-7xl flex-1 flex-col gap-4" :class="props.class">
        <slot />
    </main>
</template>
