<script setup lang="ts">
import { Head, Link } from '@inertiajs/vue3';
import { ArrowRight, FileText } from '@lucide/vue';
import Imagen from '@/components/sitio/Imagen.vue';
import Encabezado from '@/components/sitio/Encabezado.vue';
import { fecha, u } from '@/lib/sitio';

defineProps<{
    ediciones: { id: number; numero: number; titulo: string; fecha_publicacion: string | null; descripcion: string | null; portada_url: string | null; archivo_pdf_url: string | null; articulos_count: number }[];
    presentacion: string | null;
}>();
</script>

<template>
    <Head title="Revista INNOVAR" />
    <Encabezado titulo="Revista Académica INNOVAR" descripcion="Revista académica institucional de la Carrera de Administración de Empresas." :migas="[{ titulo: 'INNOVAR' }]" />

    <div class="mx-auto max-w-7xl px-4 py-12 sm:px-6">
        <div class="mb-12 grid gap-8 lg:grid-cols-3">
            <div class="contenido lg:col-span-2" v-html="presentacion" />
            <div class="border-l-4 border-marca bg-card p-6">
                <p class="font-bold">¿Desea publicar en INNOVAR?</p>
                <p class="mt-2 text-sm text-muted-foreground">Revise los requisitos y el proceso de evaluación para autores y colaboradores.</p>
                <Link :href="u('/revista-innovar/normas-para-autores')" class="mt-4 inline-flex items-center gap-2 bg-primary px-4 py-2.5 text-sm font-semibold text-primary-foreground">Normas para autores <ArrowRight class="size-4" /></Link>
            </div>
        </div>

        <h2 class="mb-6 border-b pb-3 text-2xl font-bold">Ediciones publicadas</h2>
        <div v-if="ediciones.length" class="grid gap-8 sm:grid-cols-2 lg:grid-cols-4">
            <article v-for="e in ediciones" :key="e.id" class="group flex flex-col">
                <Link :href="u(`/revista-innovar/${e.numero}`)" class="block aspect-[3/4] overflow-hidden border shadow-sm transition-shadow group-hover:shadow-xl">
                    <Imagen :src="e.portada_url" :alt="`Portada INNOVAR N° ${e.numero}`" />
                </Link>
                <p class="mt-4 text-xs font-bold tracking-wider text-marca">N° {{ e.numero }} · {{ fecha(e.fecha_publicacion) }}</p>
                <Link :href="u(`/revista-innovar/${e.numero}`)" class="mt-1 font-bold leading-snug hover:text-primary">{{ e.titulo }}</Link>
                <p class="mt-1 flex items-center gap-1 text-xs text-muted-foreground"><FileText class="size-3.5" /> {{ e.articulos_count }} artículos</p>
            </article>
        </div>
        <p v-else class="text-muted-foreground">Aún no hay ediciones publicadas.</p>
    </div>
</template>
