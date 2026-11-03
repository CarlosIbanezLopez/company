<script setup lang="ts">
import { Head, Link } from '@inertiajs/vue3';
import { Images, PlayCircle } from '@lucide/vue';
import Imagen from '@/components/sitio/Imagen.vue';
import Encabezado from '@/components/sitio/Encabezado.vue';
import Seccion from '@/components/sitio/Seccion.vue';
import { fecha, u } from '@/lib/sitio';

defineProps<{
    albumes: { id: number; titulo: string; slug: string; descripcion: string | null; fecha: string | null; portada_url: string | null; fotos_count: number }[];
    videos: { id: number; titulo: string; url: string; descripcion: string | null }[];
}>();
</script>

<template>
    <Head title="Galería multimedia" />
    <Encabezado titulo="Galería multimedia" descripcion="Fotografías y videos de las actividades de la Carrera." :migas="[{ titulo: 'Noticias' }, { titulo: 'Galería' }]" />

    <Seccion subtitulo="Fotografías" titulo="Álbumes">
        <div v-if="albumes.length" class="grid gap-6 sm:grid-cols-2 lg:grid-cols-3">
            <Link v-for="a in albumes" :key="a.id" :href="u(`/galeria/${a.slug}`)" class="group relative block aspect-[4/3] overflow-hidden border">
                <Imagen :src="a.portada_url" :alt="a.titulo" clase="transition-transform duration-500 group-hover:scale-105" />
                <div class="absolute inset-x-0 bottom-0 bg-gradient-to-t from-black/85 to-transparent p-5 text-white">
                    <p class="text-xs opacity-80">{{ fecha(a.fecha) }}</p>
                    <p class="text-lg font-bold">{{ a.titulo }}</p>
                    <p class="mt-1 inline-flex items-center gap-1.5 text-xs"><Images class="size-3.5" /> {{ a.fotos_count }} fotos</p>
                </div>
            </Link>
        </div>
        <p v-else class="text-muted-foreground">Aún no hay álbumes publicados.</p>
    </Seccion>

    <div v-if="videos.length" class="border-t bg-muted/40">
        <Seccion subtitulo="Audiovisual" titulo="Videos">
            <div class="grid gap-4 sm:grid-cols-2 lg:grid-cols-3">
                <a v-for="v in videos" :key="v.id" :href="v.url" target="_blank" rel="noopener" class="group flex items-start gap-4 border bg-card p-5 hover:border-primary">
                    <PlayCircle class="size-10 shrink-0 text-marca" />
                    <div>
                        <p class="font-semibold group-hover:text-primary">{{ v.titulo }}</p>
                        <p v-if="v.descripcion" class="mt-1 text-sm text-muted-foreground">{{ v.descripcion }}</p>
                    </div>
                </a>
            </div>
        </Seccion>
    </div>
</template>
