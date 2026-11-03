<script setup lang="ts">
import { Head, Link, router } from '@inertiajs/vue3';
import { Star } from '@lucide/vue';
import Paginacion from '@/components/Paginacion.vue';
import Imagen from '@/components/sitio/Imagen.vue';
import Encabezado from '@/components/sitio/Encabezado.vue';
import { fecha, u } from '@/lib/sitio';
import type { Paginado } from '@/types';

type Noticia = { id: number; titulo: string; slug: string; resumen: string | null; imagen_url: string | null; fecha_publicacion: string; destacado: boolean; categoria: { nombre: string } | null };

defineProps<{ noticias: Paginado<Noticia>; categorias: { nombre: string; slug: string }[]; categoria: string | null }>();
</script>

<template>
    <Head title="Noticias y comunicados" />
    <Encabezado titulo="Noticias y comunicados" descripcion="Noticias institucionales, actividades académicas, convocatorias y reconocimientos." :migas="[{ titulo: 'Noticias' }]" />

    <div class="mx-auto max-w-7xl px-4 py-12 sm:px-6">
        <div class="mb-8 flex flex-wrap gap-2">
            <button type="button" class="border px-3 py-1.5 text-sm" :class="!categoria ? 'border-primary bg-primary text-primary-foreground' : 'bg-card hover:border-primary'" @click="router.get(u('/noticias'))">Todas</button>
            <button v-for="c in categorias" :key="c.slug" type="button" class="border px-3 py-1.5 text-sm" :class="categoria === c.slug ? 'border-primary bg-primary text-primary-foreground' : 'bg-card hover:border-primary'" @click="router.get(u('/noticias'), { categoria: c.slug })">{{ c.nombre }}</button>
        </div>

        <div v-if="noticias.data.length" class="grid gap-6 md:grid-cols-2 lg:grid-cols-3">
            <Link v-for="n in noticias.data" :key="n.id" :href="u(`/noticias/${n.slug}`)" class="group flex flex-col border bg-card hover:border-primary">
                <div class="relative aspect-video overflow-hidden">
                    <Imagen :src="n.imagen_url" :alt="n.titulo" clase="transition-transform duration-500 group-hover:scale-105" />
                    <span v-if="n.destacado" class="absolute top-0 left-0 inline-flex items-center gap-1 bg-marca px-2 py-1 text-xs font-semibold text-white"><Star class="size-3" /> Destacada</span>
                </div>
                <div class="flex flex-1 flex-col p-5">
                    <div class="flex items-center gap-2 text-xs">
                        <span v-if="n.categoria" class="font-semibold uppercase tracking-wide text-primary">{{ n.categoria.nombre }}</span>
                        <span class="text-muted-foreground">{{ fecha(n.fecha_publicacion) }}</span>
                    </div>
                    <h2 class="mt-2 text-lg font-bold leading-snug group-hover:text-primary">{{ n.titulo }}</h2>
                    <p v-if="n.resumen" class="mt-2 line-clamp-3 text-sm text-muted-foreground">{{ n.resumen }}</p>
                </div>
            </Link>
        </div>
        <p v-else class="text-muted-foreground">No hay noticias en esta categoría.</p>
        <Paginacion class="mt-8" :links="noticias.links" />
    </div>
</template>
