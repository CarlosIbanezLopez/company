<script setup lang="ts">
import { Head, Link, router } from '@inertiajs/vue3';
import { Users } from '@lucide/vue';
import Imagen from '@/components/sitio/Imagen.vue';
import Encabezado from '@/components/sitio/Encabezado.vue';
import Seccion from '@/components/sitio/Seccion.vue';
import { fecha, u } from '@/lib/sitio';

type Proyecto = { id: number; tipo: string; titulo: string; slug: string; responsables: string | null; estado: string; fecha_inicio: string | null; imagen_url: string | null; linea: { nombre: string } | null };

const props = defineProps<{
    lineas: { id: number; nombre: string; descripcion: string | null; proyectos_count: number }[];
    proyectos: Proyecto[];
    estados: Record<string, string>;
    tipos: Record<string, string>;
    filtros: { tipo?: string; linea?: string; estado?: string };
}>();

function filtrar(cambios: Record<string, string | undefined>) {
    const datos: Record<string, string> = {};
    Object.entries({ ...props.filtros, ...cambios }).forEach(([k, v]) => v && (datos[k] = v));
    router.get(u('/investigacion'), datos, { preserveScroll: true, preserveState: true });
}

const colorEstado: Record<string, string> = { finalizado: 'bg-chart-5 text-white', en_curso: 'bg-primary text-primary-foreground', planificado: 'bg-muted text-foreground' };
</script>

<template>
    <Head title="Investigación" />
    <Encabezado titulo="Investigación" descripcion="Líneas y proyectos de investigación, Proyectos de Investigación en Aula (PIA), semilleros y producción académica de la Carrera." :migas="[{ titulo: 'Investigación' }]" />

    <Seccion subtitulo="Ejes temáticos" titulo="Líneas de investigación">
        <div class="grid gap-px border bg-border sm:grid-cols-2 lg:grid-cols-4">
            <button v-for="l in lineas" :key="l.id" type="button" class="bg-card p-5 text-left transition-colors hover:bg-accent" :class="{ 'bg-accent outline-2 outline-primary -outline-offset-2': filtros.linea == String(l.id) }" @click="filtrar({ linea: filtros.linea == String(l.id) ? undefined : String(l.id) })">
                <p class="text-3xl font-black text-primary">{{ l.proyectos_count }}</p>
                <h3 class="mt-1 font-semibold">{{ l.nombre }}</h3>
                <p v-if="l.descripcion" class="mt-2 text-xs text-muted-foreground">{{ l.descripcion }}</p>
            </button>
        </div>
    </Seccion>

    <div class="border-t bg-muted/40">
        <Seccion subtitulo="Trabajos" titulo="Proyectos y producción académica">
            <div class="mb-6 flex flex-wrap gap-2">
                <button type="button" class="border px-3 py-1.5 text-sm" :class="!filtros.tipo ? 'border-primary bg-primary text-primary-foreground' : 'bg-card hover:border-primary'" @click="filtrar({ tipo: undefined })">Todos</button>
                <button v-for="(nombre, clave) in tipos" :key="clave" type="button" class="border px-3 py-1.5 text-sm" :class="filtros.tipo === clave ? 'border-primary bg-primary text-primary-foreground' : 'bg-card hover:border-primary'" @click="filtrar({ tipo: clave })">{{ nombre }}</button>
                <select :value="filtros.estado ?? ''" class="h-9 border border-input bg-card px-3 text-sm" aria-label="Estado" @change="filtrar({ estado: ($event.target as HTMLSelectElement).value || undefined })">
                    <option value="">Todos los estados</option>
                    <option v-for="(nombre, clave) in estados" :key="clave" :value="clave">{{ nombre }}</option>
                </select>
            </div>
            <div v-if="proyectos.length" class="grid gap-6 md:grid-cols-2 lg:grid-cols-3">
                <Link v-for="p in proyectos" :key="p.id" :href="u(`/investigacion/${p.slug}`)" class="group flex flex-col border bg-card hover:border-primary">
                    <div class="aspect-video overflow-hidden"><Imagen :src="p.imagen_url" :alt="p.titulo" clase="transition-transform duration-500 group-hover:scale-105" /></div>
                    <div class="flex flex-1 flex-col p-5">
                        <div class="flex flex-wrap items-center gap-2 text-xs">
                            <span class="border border-marca px-2 py-0.5 font-semibold text-marca">{{ tipos[p.tipo] ?? p.tipo }}</span>
                            <span class="px-2 py-0.5 font-semibold" :class="colorEstado[p.estado]">{{ estados[p.estado] }}</span>
                        </div>
                        <h3 class="mt-3 font-bold leading-snug group-hover:text-primary">{{ p.titulo }}</h3>
                        <p v-if="p.linea" class="mt-1 text-xs text-muted-foreground">{{ p.linea.nombre }}</p>
                        <p v-if="p.responsables" class="mt-auto flex items-center gap-1.5 pt-3 text-xs"><Users class="size-3.5" />{{ p.responsables }}</p>
                        <p class="mt-1 text-xs text-muted-foreground">Inicio: {{ fecha(p.fecha_inicio) }}</p>
                    </div>
                </Link>
            </div>
            <p v-else class="border bg-card p-8 text-center text-muted-foreground">No hay trabajos registrados con estos filtros.</p>
        </Seccion>
    </div>
</template>
