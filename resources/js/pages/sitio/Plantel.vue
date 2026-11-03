<script setup lang="ts">
import { Head, router } from '@inertiajs/vue3';
import { BookOpen, GraduationCap, Mail, Search } from '@lucide/vue';
import { ref } from 'vue';
import Imagen from '@/components/sitio/Imagen.vue';
import Encabezado from '@/components/sitio/Encabezado.vue';
import Seccion from '@/components/sitio/Seccion.vue';
import { u } from '@/lib/sitio';

type Persona = { id: number; nombre: string; tipo: string; cargo: string | null; especialidad: string | null; materias: string | null; formacion: string | null; correo: string | null; foto_url: string | null };

const props = defineProps<{ autoridades: Persona[]; docentes: Persona[]; q: string | null }>();

const texto = ref(props.q ?? '');

function buscar() {
    router.get(u('/plantel-docente'), texto.value ? { q: texto.value } : {}, { preserveState: true, preserveScroll: true });
}
</script>

<template>
    <Head title="Autoridades y docentes" />
    <Encabezado titulo="Autoridades y docentes" descripcion="Plantel de autoridades y docentes de la Carrera de Administración de Empresas." :migas="[{ titulo: 'La Carrera' }, { titulo: 'Autoridades y docentes' }]" />

    <Seccion v-if="autoridades.length" subtitulo="Gestión" titulo="Autoridades">
        <div class="grid gap-6 md:grid-cols-2">
            <article v-for="a in autoridades" :key="a.id" class="flex border bg-card">
                <div class="w-36 shrink-0 sm:w-44"><Imagen :src="a.foto_url" :alt="a.nombre" clase="aspect-[3/4]" /></div>
                <div class="flex flex-col justify-center border-l-4 border-marca p-5">
                    <p class="text-xs font-semibold uppercase tracking-wider text-marca">{{ a.cargo }}</p>
                    <h3 class="mt-1 text-xl font-bold">{{ a.nombre }}</h3>
                    <p v-if="a.formacion" class="mt-2 flex items-start gap-2 text-sm text-muted-foreground"><GraduationCap class="mt-0.5 size-4 shrink-0" />{{ a.formacion }}</p>
                    <a v-if="a.correo" :href="`mailto:${a.correo}`" class="mt-2 inline-flex items-center gap-2 text-sm text-primary hover:underline"><Mail class="size-4" />{{ a.correo }}</a>
                </div>
            </article>
        </div>
    </Seccion>

    <div class="border-t bg-muted/40">
        <Seccion subtitulo="Plantel" titulo="Docentes">
            <template #acciones>
                <form class="flex w-full max-w-sm" @submit.prevent="buscar">
                    <input v-model="texto" type="search" placeholder="Buscar por nombre, área o materia" class="h-10 w-full border border-input bg-background px-3 text-sm outline-none focus:border-primary" />
                    <button class="inline-flex h-10 w-11 shrink-0 items-center justify-center bg-primary text-primary-foreground" aria-label="Buscar"><Search class="size-4" /></button>
                </form>
            </template>
            <div v-if="docentes.length" class="grid gap-6 sm:grid-cols-2 lg:grid-cols-4">
                <article v-for="d in docentes" :key="d.id" class="flex flex-col border bg-card">
                    <div class="aspect-square"><Imagen :src="d.foto_url" :alt="d.nombre" /></div>
                    <div class="flex flex-1 flex-col border-t-4 border-primary p-4">
                        <h3 class="font-bold leading-snug">{{ d.nombre }}</h3>
                        <p v-if="d.especialidad" class="text-xs font-semibold uppercase tracking-wide text-marca">{{ d.especialidad }}</p>
                        <p v-if="d.formacion" class="mt-2 text-xs text-muted-foreground">{{ d.formacion }}</p>
                        <p v-if="d.materias" class="mt-2 flex items-start gap-1.5 text-xs"><BookOpen class="mt-0.5 size-3.5 shrink-0" />{{ d.materias }}</p>
                        <a v-if="d.correo" :href="`mailto:${d.correo}`" class="mt-auto inline-flex items-center gap-1.5 pt-3 text-xs text-primary hover:underline"><Mail class="size-3.5" />{{ d.correo }}</a>
                    </div>
                </article>
            </div>
            <p v-else class="text-muted-foreground">No se encontraron docentes con ese criterio.</p>
        </Seccion>
    </div>
</template>
