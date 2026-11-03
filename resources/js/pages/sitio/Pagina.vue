<script setup lang="ts">
import { Head, Link } from '@inertiajs/vue3';
import Encabezado from '@/components/sitio/Encabezado.vue';
import { u } from '@/lib/sitio';

const props = defineProps<{
    pagina: { titulo: string; resumen: string | null; html: string; imagen_url: string | null; slug: string; updated_at: string };
    seccion: string;
    hermanas: { slug: string; titulo: string }[];
    prefijo: string;
}>();

const url = (slug: string) => u(`/${props.prefijo}/${slug}`);
</script>

<template>
    <Head :title="pagina.titulo" />
    <Encabezado :titulo="pagina.titulo" :descripcion="pagina.resumen" :migas="[{ titulo: seccion }, { titulo: pagina.titulo }]" />

    <div class="mx-auto grid max-w-7xl gap-10 px-4 py-12 sm:px-6 lg:grid-cols-4">
        <aside v-if="hermanas.length" class="lg:col-span-1">
            <nav class="sticky top-24 border bg-card">
                <p class="border-b bg-muted px-4 py-3 text-xs font-bold uppercase tracking-wider">{{ seccion }}</p>
                <Link
                    v-for="h in hermanas"
                    :key="h.slug"
                    :href="url(h.slug)"
                    class="block border-l-4 px-4 py-2.5 text-sm transition-colors"
                    :class="h.slug === pagina.slug ? 'border-marca bg-accent font-semibold text-primary' : 'border-transparent hover:bg-accent'"
                >
                    {{ h.titulo }}
                </Link>
            </nav>
        </aside>
        <article :class="hermanas.length ? 'lg:col-span-3' : 'lg:col-span-4 max-w-4xl'">
            <div class="contenido text-[17px]" v-html="pagina.html" />
            <figure v-if="pagina.imagen_url" class="mt-8 border bg-card p-2">
                <img :src="pagina.imagen_url" :alt="pagina.titulo" class="w-full" />
            </figure>
        </article>
    </div>
</template>
