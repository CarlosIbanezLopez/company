<script setup lang="ts">
import { Head, Link, router } from '@inertiajs/vue3';
import { Download, FileText, Search } from '@lucide/vue';
import { ref } from 'vue';
import Paginacion from '@/components/Paginacion.vue';
import Encabezado from '@/components/sitio/Encabezado.vue';
import { fecha, numero, u } from '@/lib/sitio';
import type { Paginado } from '@/types';

type Documento = { id: number; titulo: string; descripcion: string | null; fecha_publicacion: string | null; descargas: number; archivo_url: string | null; categoria: { nombre: string; slug: string } };

const props = defineProps<{
    documentos: Paginado<Documento>;
    categorias: { id: number; nombre: string; slug: string; descripcion: string | null; documentos_count: number }[];
    filtros: { q?: string; categoria?: string };
}>();

const texto = ref(props.filtros.q ?? '');

function filtrar(cambios: Record<string, string | undefined>) {
    const datos = { ...props.filtros, ...cambios };
    Object.keys(datos).forEach((k) => !datos[k as keyof typeof datos] && delete datos[k as keyof typeof datos]);
    router.get(u('/biblioteca'), datos, { preserveScroll: true, preserveState: true });
}
</script>

<template>
    <Head title="Biblioteca digital" />
    <Encabezado titulo="Biblioteca digital" descripcion="Reglamentos, instructivos, formularios y convocatorias de la Carrera en formato PDF." :migas="[{ titulo: 'Investigación' }, { titulo: 'Biblioteca digital' }]" />

    <div class="mx-auto grid max-w-7xl gap-8 px-4 py-12 sm:px-6 lg:grid-cols-4">
        <aside class="space-y-4">
            <form class="flex" @submit.prevent="filtrar({ q: texto })">
                <input v-model="texto" type="search" placeholder="Buscar documento" class="h-10 w-full border border-input bg-background px-3 text-sm outline-none focus:border-primary" />
                <button class="inline-flex h-10 w-11 shrink-0 items-center justify-center bg-primary text-primary-foreground" aria-label="Buscar"><Search class="size-4" /></button>
            </form>
            <nav class="border bg-card">
                <p class="border-b bg-muted px-4 py-3 text-xs font-bold uppercase tracking-wider">Categorías</p>
                <button type="button" class="flex w-full justify-between border-l-4 px-4 py-2.5 text-left text-sm" :class="!filtros.categoria ? 'border-marca bg-accent font-semibold' : 'border-transparent hover:bg-accent'" @click="filtrar({ categoria: undefined })">
                    Todas
                </button>
                <button
                    v-for="c in categorias"
                    :key="c.id"
                    type="button"
                    class="flex w-full justify-between border-l-4 px-4 py-2.5 text-left text-sm"
                    :class="filtros.categoria === c.slug ? 'border-marca bg-accent font-semibold' : 'border-transparent hover:bg-accent'"
                    @click="filtrar({ categoria: c.slug })"
                >
                    {{ c.nombre }} <span class="text-muted-foreground">{{ c.documentos_count }}</span>
                </button>
            </nav>
        </aside>

        <div class="lg:col-span-3">
            <p class="mb-4 text-sm text-muted-foreground">{{ documentos.total }} documento(s) encontrado(s)</p>
            <ul v-if="documentos.data.length" class="divide-y border bg-card">
                <li v-for="d in documentos.data" :key="d.id" class="flex flex-col gap-4 p-5 sm:flex-row sm:items-center">
                    <div class="flex size-12 shrink-0 items-center justify-center bg-marca/10 text-marca"><FileText class="size-6" /></div>
                    <div class="min-w-0 flex-1">
                        <p class="text-xs font-semibold uppercase tracking-wide text-primary">{{ d.categoria.nombre }}</p>
                        <h3 class="font-semibold">{{ d.titulo }}</h3>
                        <p v-if="d.descripcion" class="text-sm text-muted-foreground">{{ d.descripcion }}</p>
                        <p class="mt-1 text-xs text-muted-foreground">Publicado: {{ fecha(d.fecha_publicacion) }} · {{ numero(d.descargas) }} descargas</p>
                    </div>
                    <div class="flex shrink-0 gap-2">
                        <a v-if="d.archivo_url" :href="d.archivo_url" target="_blank" rel="noopener" class="inline-flex h-10 items-center border px-4 text-sm font-medium hover:border-primary">Ver</a>
                        <a :href="u(`/biblioteca/${d.id}/descargar`)" class="inline-flex h-10 items-center gap-2 bg-primary px-4 text-sm font-semibold text-primary-foreground hover:opacity-90"><Download class="size-4" /> Descargar</a>
                    </div>
                </li>
            </ul>
            <p v-else class="border bg-card p-8 text-center text-muted-foreground">No hay documentos que coincidan con la búsqueda.</p>
            <Paginacion class="mt-6" :links="documentos.links" />
        </div>
    </div>
</template>
