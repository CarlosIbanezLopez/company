<script setup lang="ts">
import { Head } from '@inertiajs/vue3';
import { CalendarDays, ExternalLink, Users } from '@lucide/vue';
import Encabezado from '@/components/sitio/Encabezado.vue';
import { fecha, u } from '@/lib/sitio';

defineProps<{
    proyecto: {
        titulo: string; tipo: string; html: string; responsables: string | null; estado: string; fecha_inicio: string | null; fecha_fin: string | null;
        resultados: string | null; enlace_publicacion: string | null; enlace_repositorio: string | null; imagen_url: string | null; linea: { nombre: string } | null;
    };
    estados: Record<string, string>;
    tipos: Record<string, string>;
}>();
</script>

<template>
    <Head :title="proyecto.titulo" />
    <Encabezado :titulo="proyecto.titulo" :descripcion="proyecto.linea?.nombre" :migas="[{ titulo: 'Investigación', url: u('/investigacion') }, { titulo: tipos[proyecto.tipo] ?? 'Proyecto' }]" />

    <div class="mx-auto grid max-w-7xl gap-10 px-4 py-12 sm:px-6 lg:grid-cols-3">
        <article class="lg:col-span-2">
            <img v-if="proyecto.imagen_url" :src="proyecto.imagen_url" :alt="proyecto.titulo" class="mb-8 aspect-video w-full border object-cover" />
            <div class="contenido" v-html="proyecto.html" />
            <section v-if="proyecto.resultados" class="mt-8 border-l-4 border-chart-5 bg-card p-5">
                <h2 class="font-bold">Resultados obtenidos</h2>
                <p class="mt-2 text-sm whitespace-pre-line">{{ proyecto.resultados }}</p>
            </section>
        </article>
        <aside class="h-fit border bg-card">
            <dl class="divide-y text-sm">
                <div class="p-4"><dt class="text-xs font-bold uppercase tracking-wider text-muted-foreground">Tipo</dt><dd class="mt-1 font-semibold">{{ tipos[proyecto.tipo] }}</dd></div>
                <div class="p-4"><dt class="text-xs font-bold uppercase tracking-wider text-muted-foreground">Estado</dt><dd class="mt-1 font-semibold text-primary">{{ estados[proyecto.estado] }}</dd></div>
                <div v-if="proyecto.responsables" class="p-4"><dt class="text-xs font-bold uppercase tracking-wider text-muted-foreground">Responsables</dt><dd class="mt-1 flex gap-2"><Users class="size-4 shrink-0" />{{ proyecto.responsables }}</dd></div>
                <div class="p-4"><dt class="text-xs font-bold uppercase tracking-wider text-muted-foreground">Periodo de ejecución</dt><dd class="mt-1 flex gap-2"><CalendarDays class="size-4" />{{ fecha(proyecto.fecha_inicio) }} — {{ proyecto.fecha_fin ? fecha(proyecto.fecha_fin) : 'en curso' }}</dd></div>
                <div v-if="proyecto.enlace_publicacion || proyecto.enlace_repositorio" class="space-y-2 p-4">
                    <a v-if="proyecto.enlace_publicacion" :href="proyecto.enlace_publicacion" target="_blank" rel="noopener" class="flex items-center gap-2 text-primary hover:underline"><ExternalLink class="size-4" /> Ver publicación</a>
                    <a v-if="proyecto.enlace_repositorio" :href="proyecto.enlace_repositorio" target="_blank" rel="noopener" class="flex items-center gap-2 text-primary hover:underline"><ExternalLink class="size-4" /> Ver repositorio</a>
                </div>
            </dl>
        </aside>
    </div>
</template>
