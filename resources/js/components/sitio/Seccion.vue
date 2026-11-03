<script setup lang="ts">
defineProps<{
    titulo?: string;
    subtitulo?: string;
}>();
</script>

<template>
    <section class="mx-auto max-w-7xl px-4 py-12 sm:px-6">
        <header v-if="titulo" class="mb-8 flex flex-wrap items-end justify-between gap-4 border-b pb-4">
            <div>
                <p v-if="subtitulo" class="text-xs font-semibold uppercase tracking-[0.18em] text-marca">{{ subtitulo }}</p>
                <h2 class="mt-1 text-2xl font-bold tracking-tight md:text-3xl">{{ titulo }}</h2>
            </div>
            <slot name="acciones" />
        </header>
        <slot />
    </section>
</template>
