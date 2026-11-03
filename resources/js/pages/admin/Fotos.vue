<script setup lang="ts">
import { Head, Link, router, useForm } from '@inertiajs/vue3';
import { ArrowLeft, Trash2, Upload } from '@lucide/vue';

const props = defineProps<{
    album: { id: number; titulo: string; fotos: { id: number; imagen_url: string; descripcion: string | null }[] };
    urls: { subir: string; eliminar: string; volver: string };
}>();

const form = useForm<{ fotos: File[]; descripcion: string }>({ fotos: [], descripcion: '' });

function seleccionar(e: Event) {
    form.fotos = Array.from((e.target as HTMLInputElement).files ?? []);
}

function subir() {
    form.post(props.urls.subir, { forceFormData: true, preserveScroll: true, onSuccess: () => form.reset() });
}

function eliminar(id: number) {
    if (confirm('¿Eliminar esta fotografía?')) {
        router.delete(props.urls.eliminar.replace('__FOTO__', String(id)), { preserveScroll: true });
    }
}
</script>

<template>
    <Head :title="`Fotos - ${album.titulo}`" />

    <div class="space-y-5 p-4 md:p-6">
        <header class="border-b pb-4">
            <Link :href="urls.volver" class="mb-2 inline-flex items-center gap-1 text-sm text-muted-foreground hover:text-primary"><ArrowLeft class="size-4" /> Álbumes</Link>
            <h1 class="text-2xl font-bold tracking-tight">Fotografías: {{ album.titulo }}</h1>
            <p class="mt-1 text-sm text-muted-foreground">Suba hasta 20 imágenes a la vez (JPG, PNG o WEBP, máximo 5 MB cada una).</p>
        </header>

        <form class="flex flex-wrap items-end gap-3 border bg-card p-4" @submit.prevent="subir">
            <div class="min-w-64 flex-1">
                <label class="etiqueta" for="fotos">Imágenes</label>
                <input id="fotos" type="file" accept="image/jpeg,image/png,image/webp" multiple class="campo pt-1.5 file:mr-3 file:border-0 file:bg-secondary file:px-3 file:py-1" @change="seleccionar" />
                <p v-if="form.errors.fotos" class="mt-1 text-xs text-destructive">{{ form.errors.fotos }}</p>
            </div>
            <div class="min-w-64 flex-1">
                <label class="etiqueta" for="descripcion">Descripción (opcional)</label>
                <input id="descripcion" v-model="form.descripcion" class="campo" maxlength="255" />
            </div>
            <button class="boton" :disabled="form.processing || !form.fotos.length"><Upload class="size-4" /> Subir {{ form.fotos.length || '' }} foto(s)</button>
        </form>

        <div v-if="album.fotos.length" class="grid grid-cols-2 gap-3 sm:grid-cols-3 lg:grid-cols-5">
            <figure v-for="f in album.fotos" :key="f.id" class="group relative border bg-card">
                <img :src="f.imagen_url" :alt="f.descripcion ?? ''" class="aspect-square w-full object-cover" />
                <figcaption class="truncate px-2 py-1.5 text-xs text-muted-foreground">{{ f.descripcion ?? 'Sin descripción' }}</figcaption>
                <button type="button" class="absolute top-2 right-2 inline-flex size-8 items-center justify-center bg-destructive text-white opacity-0 transition-opacity group-hover:opacity-100 focus:opacity-100" aria-label="Eliminar fotografía" @click="eliminar(f.id)"><Trash2 class="size-4" /></button>
            </figure>
        </div>
        <p v-else class="border bg-card p-10 text-center text-muted-foreground">El álbum todavía no tiene fotografías.</p>
    </div>
</template>
