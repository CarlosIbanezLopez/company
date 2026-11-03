<script setup lang="ts">
import { Head, Link, router } from '@inertiajs/vue3';
import { Search } from '@lucide/vue';
import { ref } from 'vue';
import Encabezado from '@/components/sitio/Encabezado.vue';
import { u } from '@/lib/sitio';

const props = defineProps<{
    q: string;
    grupos: { seccion: string; resultados: { titulo: string; extracto: string | null; url: string; fecha: string | null }[] }[];
    total: number;
}>();

const texto = ref(props.q);

function buscar() {
    router.get(u('/buscar'), { q: texto.value });
}

function resaltar(valor: string | null) {
    if (!valor || props.q.length < 3) return valor ?? '';
    const seguro = valor.replace(/[&<>"']/g, (c) => ({ '&': '&amp;', '<': '&lt;', '>': '&gt;', '"': '&quot;', "'": '&#39;' })[c]!);
    const patron = props.q.replace(/[.*+?^${}()|[\]\\]/g, '\\$&');
    return seguro.replace(new RegExp(`(${patron})`, 'gi'), '<mark class="bg-aviso/50 text-inherit">$1</mark>');
}
</script>

<template>
    <Head title="Buscar" />
    <Encabezado titulo="Buscador del sitio" descripcion="Busque en noticias, eventos, documentos, materias, proyectos de investigación y artículos." :migas="[{ titulo: 'Buscar' }]">
        <form class="mt-6 flex max-w-2xl" role="search" @submit.prevent="buscar">
            <input v-model="texto" type="search" minlength="3" placeholder="Escriba al menos 3 caracteres" class="h-12 w-full border-0 bg-white px-4 text-foreground outline-none dark:bg-background" />
            <button class="inline-flex h-12 shrink-0 items-center gap-2 bg-marca px-6 font-semibold text-white"><Search class="size-4" /> Buscar</button>
        </form>
    </Encabezado>

    <div class="mx-auto max-w-5xl px-4 py-12 sm:px-6">
        <p v-if="q.length >= 3" class="mb-8 text-sm text-muted-foreground">{{ total }} resultado(s) para <strong class="text-foreground">«{{ q }}»</strong></p>
        <p v-else class="text-muted-foreground">Ingrese una palabra clave de al menos 3 caracteres.</p>

        <section v-for="g in grupos" :key="g.seccion" class="mb-10">
            <h2 class="mb-3 flex items-center justify-between border-b-2 border-primary pb-2 text-lg font-bold">{{ g.seccion }} <span class="bg-primary px-2 text-sm text-primary-foreground">{{ g.resultados.length }}</span></h2>
            <ul class="divide-y border bg-card">
                <li v-for="r in g.resultados" :key="r.url + r.titulo">
                    <Link :href="r.url" class="block p-4 hover:bg-accent">
                        <p class="font-semibold text-primary" v-html="resaltar(r.titulo)" />
                        <p v-if="r.extracto" class="mt-1 text-sm text-muted-foreground" v-html="resaltar(r.extracto)" />
                        <p v-if="r.fecha" class="mt-1 text-xs text-muted-foreground">{{ r.fecha }}</p>
                    </Link>
                </li>
            </ul>
        </section>
        <p v-if="q.length >= 3 && !grupos.length" class="border bg-card p-8 text-center text-muted-foreground">No se encontraron resultados. Intente con otras palabras.</p>
    </div>
</template>
