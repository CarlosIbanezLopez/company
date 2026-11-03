<script setup lang="ts">
import { Head, Link } from '@inertiajs/vue3';
import { Download, FileText } from '@lucide/vue';
import Imagen from '@/components/sitio/Imagen.vue';
import Encabezado from '@/components/sitio/Encabezado.vue';
import { fecha, u } from '@/lib/sitio';

defineProps<{
    edicion: {
        numero: number; titulo: string; fecha_publicacion: string | null; descripcion: string | null; portada_url: string | null; archivo_pdf_url: string | null;
        articulos: { id: number; titulo: string; autores: string; resumen: string | null; palabras_clave: string | null; archivo_pdf_url: string | null }[];
    };
    otras: { id: number; numero: number; titulo: string; portada_url: string | null }[];
}>();
</script>

<template>
    <Head :title="`INNOVAR N° ${edicion.numero}`" />
    <Encabezado :titulo="`INNOVAR N° ${edicion.numero}: ${edicion.titulo}`" :descripcion="`Publicada el ${fecha(edicion.fecha_publicacion)}`" :migas="[{ titulo: 'Revista INNOVAR', url: u('/revista-innovar') }, { titulo: `N° ${edicion.numero}` }]" />

    <div class="mx-auto grid max-w-7xl gap-10 px-4 py-12 sm:px-6 lg:grid-cols-4">
        <aside class="space-y-4">
            <div class="aspect-[3/4] border shadow-lg"><Imagen :src="edicion.portada_url" :alt="`Portada N° ${edicion.numero}`" /></div>
            <a v-if="edicion.archivo_pdf_url" :href="edicion.archivo_pdf_url" target="_blank" rel="noopener" class="flex items-center justify-center gap-2 bg-marca px-4 py-3 text-sm font-semibold text-white hover:opacity-90"><Download class="size-4" /> Revista completa (PDF)</a>
            <p v-if="edicion.descripcion" class="text-sm text-muted-foreground">{{ edicion.descripcion }}</p>
        </aside>
        <div class="lg:col-span-3">
            <h2 class="mb-4 text-xl font-bold">Artículos de esta edición</h2>
            <ol class="space-y-4">
                <li v-for="(a, i) in edicion.articulos" :key="a.id" class="flex gap-4 border bg-card p-5">
                    <span class="text-3xl font-black text-primary/30">{{ String(i + 1).padStart(2, '0') }}</span>
                    <div class="flex-1">
                        <h3 class="text-lg font-bold leading-snug">{{ a.titulo }}</h3>
                        <p class="mt-1 text-sm font-medium text-primary">{{ a.autores }}</p>
                        <p v-if="a.resumen" class="mt-2 text-sm text-muted-foreground">{{ a.resumen }}</p>
                        <p v-if="a.palabras_clave" class="mt-3 flex flex-wrap gap-1.5">
                            <span v-for="p in a.palabras_clave.split(',')" :key="p" class="bg-muted px-2 py-0.5 text-xs">{{ p.trim() }}</span>
                        </p>
                        <a v-if="a.archivo_pdf_url" :href="a.archivo_pdf_url" target="_blank" rel="noopener" class="mt-3 inline-flex items-center gap-2 text-sm font-semibold text-primary hover:underline"><FileText class="size-4" /> Leer artículo (PDF)</a>
                    </div>
                </li>
            </ol>
            <template v-if="otras.length">
                <h2 class="mt-12 mb-4 text-xl font-bold">Otras ediciones</h2>
                <div class="grid grid-cols-2 gap-4 sm:grid-cols-4">
                    <Link v-for="o in otras" :key="o.id" :href="u(`/revista-innovar/${o.numero}`)" class="group">
                        <div class="aspect-[3/4] border"><Imagen :src="o.portada_url" :alt="o.titulo" /></div>
                        <p class="mt-2 text-sm font-semibold group-hover:text-primary">N° {{ o.numero }}</p>
                    </Link>
                </div>
            </template>
        </div>
    </div>
</template>
