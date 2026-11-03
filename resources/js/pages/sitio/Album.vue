<script setup lang="ts">
import { Head } from '@inertiajs/vue3';
import { ChevronLeft, ChevronRight, PlayCircle, X } from '@lucide/vue';
import { onBeforeUnmount, onMounted, ref } from 'vue';
import Imagen from '@/components/sitio/Imagen.vue';
import Encabezado from '@/components/sitio/Encabezado.vue';
import { fecha, u, youtubeEmbed } from '@/lib/sitio';

const props = defineProps<{
    album: {
        titulo: string; descripcion: string | null; fecha: string | null;
        fotos: { id: number; imagen_url: string | null; descripcion: string | null }[];
        videos: { id: number; titulo: string; url: string }[];
    };
}>();

const abierta = ref<number | null>(null);

function mover(paso: number) {
    if (abierta.value === null) return;
    const total = props.album.fotos.length;
    abierta.value = (abierta.value + paso + total) % total;
}

function teclado(e: KeyboardEvent) {
    if (abierta.value === null) return;
    if (e.key === 'Escape') abierta.value = null;
    if (e.key === 'ArrowRight') mover(1);
    if (e.key === 'ArrowLeft') mover(-1);
}

onMounted(() => window.addEventListener('keydown', teclado));
onBeforeUnmount(() => window.removeEventListener('keydown', teclado));
</script>

<template>
    <Head :title="album.titulo" />
    <Encabezado :titulo="album.titulo" :descripcion="album.descripcion" :migas="[{ titulo: 'Galería', url: u('/galeria') }, { titulo: fecha(album.fecha) }]" />

    <div class="mx-auto max-w-7xl px-4 py-12 sm:px-6">
        <div class="grid grid-cols-2 gap-2 md:grid-cols-3 lg:grid-cols-4">
            <button v-for="(f, i) in album.fotos" :key="f.id" type="button" class="group aspect-square overflow-hidden border" :aria-label="`Ver ${f.descripcion ?? 'fotografía'}`" @click="abierta = i">
                <Imagen :src="f.imagen_url" :alt="f.descripcion ?? album.titulo" clase="transition-transform duration-500 group-hover:scale-110" />
            </button>
        </div>

        <div v-if="album.videos.length" class="mt-12">
            <h2 class="mb-4 text-xl font-bold">Videos del álbum</h2>
            <div class="grid gap-6 md:grid-cols-2">
                <div v-for="v in album.videos" :key="v.id">
                    <iframe v-if="youtubeEmbed(v.url)" :src="youtubeEmbed(v.url)!" :title="v.titulo" class="aspect-video w-full border" allowfullscreen loading="lazy" />
                    <a v-else :href="v.url" target="_blank" rel="noopener" class="flex items-center gap-3 border bg-card p-5 hover:border-primary"><PlayCircle class="size-8 text-marca" /> {{ v.titulo }}</a>
                </div>
            </div>
        </div>
    </div>

    <!-- Visor de fotografías -->
    <div v-if="abierta !== null" class="fixed inset-0 z-50 flex items-center justify-center bg-black/90 p-4" role="dialog" aria-modal="true" @click.self="abierta = null">
        <button type="button" class="absolute top-4 right-4 inline-flex size-11 items-center justify-center border border-white/30 text-white" aria-label="Cerrar" @click="abierta = null"><X class="size-5" /></button>
        <button type="button" class="absolute left-4 inline-flex size-11 items-center justify-center border border-white/30 text-white" aria-label="Anterior" @click="mover(-1)"><ChevronLeft class="size-5" /></button>
        <figure class="max-h-full max-w-5xl">
            <img :src="album.fotos[abierta].imagen_url ?? ''" :alt="album.fotos[abierta].descripcion ?? ''" class="max-h-[80vh] w-auto" />
            <figcaption class="mt-3 text-center text-sm text-white/80">{{ album.fotos[abierta].descripcion }} · {{ abierta + 1 }} / {{ album.fotos.length }}</figcaption>
        </figure>
        <button type="button" class="absolute right-4 inline-flex size-11 items-center justify-center border border-white/30 text-white" aria-label="Siguiente" @click="mover(1)"><ChevronRight class="size-5" /></button>
    </div>
</template>
