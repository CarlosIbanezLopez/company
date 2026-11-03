<script setup lang="ts">
import { Link } from '@inertiajs/vue3';
import { ArrowLeft } from '@lucide/vue';
import TemaToggle from '@/components/TemaToggle.vue';
import { u } from '@/lib/sitio';

defineProps<{
    title?: string;
    description?: string;
}>();
</script>

<template>
    <div class="grid min-h-svh bg-background lg:grid-cols-2">
        <div class="relative hidden flex-col justify-between bg-[oklch(0.25_0.08_262)] p-10 text-white lg:flex dark:bg-[oklch(0.13_0.02_262)]">
            <span class="absolute inset-y-0 right-0 w-1.5 bg-marca" />
            <Link :href="u('/')" class="flex items-center gap-3">
                <span class="relative flex size-11 items-center justify-center bg-white text-sm font-black text-[oklch(0.3_0.1_262)]">AE<span class="absolute inset-x-0 bottom-0 h-1 bg-marca" /></span>
                <span class="leading-tight"><span class="block text-xs uppercase tracking-[0.16em] text-white/70">Carrera de</span><span class="font-bold">Administración de Empresas</span></span>
            </Link>
            <div>
                <p class="text-4xl font-bold tracking-tight">Portal institucional</p>
                <p class="mt-3 max-w-md text-white/75">Acceso para administradores, encargados de módulo y graduados de la Carrera de Administración de Empresas · FCEE · UAGRM.</p>
            </div>
            <p class="text-xs text-white/60">Universidad Autónoma Gabriel René Moreno</p>
        </div>
        <div class="flex flex-col p-6 md:p-10">
            <div class="flex items-center justify-between">
                <Link :href="u('/')" class="inline-flex items-center gap-2 text-sm text-muted-foreground hover:text-primary"><ArrowLeft class="size-4" /> Volver al sitio</Link>
                <TemaToggle />
            </div>
            <div class="flex flex-1 items-center justify-center">
                <div class="w-full max-w-sm">
                    <div class="mb-8">
                        <span class="mb-4 block h-1 w-12 bg-marca" />
                        <h1 class="text-2xl font-bold">{{ title }}</h1>
                        <p class="mt-2 text-sm text-muted-foreground">{{ description }}</p>
                    </div>
                    <slot />
                </div>
            </div>
        </div>
    </div>
</template>
