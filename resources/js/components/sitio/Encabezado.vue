<script setup lang="ts">
import { Link } from '@inertiajs/vue3';
import { ChevronRight } from '@lucide/vue';
import { u } from '@/lib/sitio';

defineProps<{
    titulo: string;
    descripcion?: string | null;
    migas?: { titulo: string; url?: string }[];
}>();
</script>

<template>
    <section class="border-b bg-primary text-primary-foreground dark:bg-card dark:text-foreground">
        <div class="mx-auto max-w-7xl px-4 py-10 sm:px-6 md:py-14">
            <nav class="mb-4 flex flex-wrap items-center gap-1 text-xs uppercase tracking-wider opacity-80" aria-label="Ruta de navegación">
                <Link :href="u('/')" class="hover:underline">Inicio</Link>
                <template v-for="miga in migas ?? []" :key="miga.titulo">
                    <ChevronRight class="size-3" />
                    <Link v-if="miga.url" :href="miga.url" class="hover:underline">{{ miga.titulo }}</Link>
                    <span v-else>{{ miga.titulo }}</span>
                </template>
            </nav>
            <div class="flex items-start gap-4">
                <span class="mt-1 hidden h-10 w-1.5 shrink-0 bg-marca sm:block" />
                <div>
                    <h1 class="text-3xl font-bold tracking-tight text-balance md:text-4xl">{{ titulo }}</h1>
                    <p v-if="descripcion" class="mt-3 max-w-3xl text-base opacity-85 md:text-lg">{{ descripcion }}</p>
                </div>
            </div>
            <slot />
        </div>
    </section>
</template>
