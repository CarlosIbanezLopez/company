<script setup lang="ts">
import { Head, Link } from '@inertiajs/vue3';
import { CalendarDays, User } from '@lucide/vue';
import Imagen from '@/components/sitio/Imagen.vue';
import Encabezado from '@/components/sitio/Encabezado.vue';
import { fecha, u } from '@/lib/sitio';

defineProps<{
    noticia: { titulo: string; resumen: string | null; html: string; imagen_url: string | null; fecha_publicacion: string; categoria: { nombre: string; slug: string } | null; autor: { name: string } | null };
    relacionadas: { id: number; titulo: string; slug: string; fecha_publicacion: string; imagen_url: string | null }[];
}>();
</script>

<template>
    <Head :title="noticia.titulo" />
    <Encabezado :titulo="noticia.titulo" :descripcion="noticia.resumen" :migas="[{ titulo: 'Noticias', url: u('/noticias') }, { titulo: noticia.categoria?.nombre ?? 'Noticia' }]">
        <div class="mt-6 flex flex-wrap gap-5 text-sm opacity-85">
            <span class="inline-flex items-center gap-2"><CalendarDays class="size-4" /> {{ fecha(noticia.fecha_publicacion) }}</span>
            <span v-if="noticia.autor" class="inline-flex items-center gap-2"><User class="size-4" /> {{ noticia.autor.name }}</span>
        </div>
    </Encabezado>

    <div class="mx-auto grid max-w-7xl gap-10 px-4 py-12 sm:px-6 lg:grid-cols-3">
        <article class="lg:col-span-2">
            <img v-if="noticia.imagen_url" :src="noticia.imagen_url" :alt="noticia.titulo" class="mb-8 aspect-video w-full border object-cover" />
            <div class="contenido text-[17px]" v-html="noticia.html" />
        </article>
        <aside v-if="relacionadas.length">
            <h2 class="mb-4 border-b pb-2 text-sm font-bold uppercase tracking-wider">Noticias relacionadas</h2>
            <ul class="space-y-4">
                <li v-for="r in relacionadas" :key="r.id">
                    <Link :href="u(`/noticias/${r.slug}`)" class="group flex gap-3">
                        <div class="size-20 shrink-0 border"><Imagen :src="r.imagen_url" :alt="r.titulo" /></div>
                        <div>
                            <p class="text-xs text-muted-foreground">{{ fecha(r.fecha_publicacion) }}</p>
                            <p class="text-sm font-semibold leading-snug group-hover:text-primary">{{ r.titulo }}</p>
                        </div>
                    </Link>
                </li>
            </ul>
        </aside>
    </div>
</template>
