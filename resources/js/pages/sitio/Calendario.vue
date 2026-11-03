<script setup lang="ts">
import { Head, router } from '@inertiajs/vue3';
import Encabezado from '@/components/sitio/Encabezado.vue';
import { fecha, u } from '@/lib/sitio';

defineProps<{
    actividades: { id: number; titulo: string; descripcion: string | null; fecha_inicio: string; fecha_fin: string | null; tipo: string }[];
    gestiones: string[];
    gestion: string | null;
    tipos: Record<string, string>;
}>();

const colores: Record<string, string> = {
    inscripciones: 'bg-chart-2', clases: 'bg-primary', examenes: 'bg-marca', feriado: 'bg-muted-foreground', academico: 'bg-chart-5', titulacion: 'bg-chart-4',
};

const hoy = new Date().toISOString().slice(0, 10);
</script>

<template>
    <Head title="Calendario académico" />
    <Encabezado titulo="Calendario académico" descripcion="Fechas importantes de la gestión: inscripciones, clases, exámenes y titulación." :migas="[{ titulo: 'Académica' }, { titulo: 'Calendario académico' }]" />

    <div class="mx-auto max-w-5xl px-4 py-12 sm:px-6">
        <div class="mb-8 flex flex-wrap items-center justify-between gap-4">
            <div class="flex flex-wrap gap-2">
                <button
                    v-for="g in gestiones"
                    :key="g"
                    type="button"
                    class="border px-4 py-2 text-sm font-semibold"
                    :class="g === gestion ? 'border-primary bg-primary text-primary-foreground' : 'hover:border-primary'"
                    @click="router.get(u('/calendario-academico'), { gestion: g }, { preserveScroll: true })"
                >
                    Gestión {{ g }}
                </button>
            </div>
            <div class="flex flex-wrap gap-3 text-xs">
                <span v-for="(nombre, clave) in tipos" :key="clave" class="inline-flex items-center gap-1.5"><span class="size-3" :class="colores[clave]" />{{ nombre }}</span>
            </div>
        </div>

        <ol v-if="actividades.length" class="border-l-2 border-border">
            <li v-for="a in actividades" :key="a.id" class="relative pb-6 pl-8">
                <span class="absolute top-1.5 -left-[9px] size-4 border-2 border-background" :class="colores[a.tipo] ?? 'bg-primary'" />
                <div class="border bg-card p-4" :class="{ 'opacity-60': (a.fecha_fin ?? a.fecha_inicio) < hoy }">
                    <p class="text-xs font-bold uppercase tracking-wider text-muted-foreground">
                        {{ fecha(a.fecha_inicio) }}<template v-if="a.fecha_fin"> — {{ fecha(a.fecha_fin) }}</template>
                        <span class="ml-2 text-marca">{{ tipos[a.tipo] ?? a.tipo }}</span>
                    </p>
                    <h3 class="mt-1 text-lg font-semibold">{{ a.titulo }}</h3>
                    <p v-if="a.descripcion" class="mt-1 text-sm text-muted-foreground">{{ a.descripcion }}</p>
                </div>
            </li>
        </ol>
        <p v-else class="text-muted-foreground">No hay actividades registradas para esta gestión.</p>
    </div>
</template>
