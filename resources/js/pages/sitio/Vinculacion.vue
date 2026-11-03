<script setup lang="ts">
import { Head, Link, router } from '@inertiajs/vue3';
import { ArrowRight, CalendarRange } from '@lucide/vue';
import Imagen from '@/components/sitio/Imagen.vue';
import Encabezado from '@/components/sitio/Encabezado.vue';
import { fecha, u } from '@/lib/sitio';

defineProps<{
    convenios: { id: number; institucion: string; tipo: string; finalidad: string | null; fecha_inicio: string | null; fecha_fin: string | null; logo_url: string | null; estado: string }[];
    tipos: Record<string, string>;
    estados: Record<string, string>;
    tipo: string | null;
}>();

const colorEstado: Record<string, string> = { vigente: 'bg-chart-5 text-white', en_tramite: 'bg-aviso text-black', finalizado: 'bg-muted text-muted-foreground' };
</script>

<template>
    <Head title="Vinculación y extensión" />
    <Encabezado titulo="Vinculación y extensión" descripcion="Convenios institucionales, prácticas empresariales, proyectos con empresas, consultorías y actividades de interacción social." :migas="[{ titulo: 'Vinculación' }]" />

    <div class="mx-auto max-w-7xl px-4 py-12 sm:px-6">
        <div class="mb-8 flex flex-wrap gap-2">
            <button type="button" class="border px-3 py-1.5 text-sm" :class="!tipo ? 'border-primary bg-primary text-primary-foreground' : 'bg-card hover:border-primary'" @click="router.get(u('/vinculacion'), {}, { preserveScroll: true })">Todos</button>
            <button v-for="(nombre, clave) in tipos" :key="clave" type="button" class="border px-3 py-1.5 text-sm" :class="tipo === clave ? 'border-primary bg-primary text-primary-foreground' : 'bg-card hover:border-primary'" @click="router.get(u('/vinculacion'), { tipo: clave }, { preserveScroll: true })">{{ nombre }}</button>
        </div>

        <div v-if="convenios.length" class="grid gap-6 md:grid-cols-2">
            <article v-for="c in convenios" :key="c.id" class="flex border bg-card">
                <div class="size-28 shrink-0 border-r sm:size-36"><Imagen :src="c.logo_url" :alt="c.institucion" /></div>
                <div class="flex flex-1 flex-col p-5">
                    <div class="flex flex-wrap items-center gap-2 text-xs">
                        <span class="font-semibold uppercase tracking-wide text-marca">{{ tipos[c.tipo] }}</span>
                        <span class="px-2 py-0.5 font-semibold" :class="colorEstado[c.estado]">{{ estados[c.estado] }}</span>
                    </div>
                    <h3 class="mt-2 font-bold leading-snug">{{ c.institucion }}</h3>
                    <p v-if="c.finalidad" class="mt-2 text-sm text-muted-foreground">{{ c.finalidad }}</p>
                    <p v-if="c.fecha_inicio" class="mt-auto flex items-center gap-1.5 pt-3 text-xs text-muted-foreground"><CalendarRange class="size-3.5" /> {{ fecha(c.fecha_inicio) }} — {{ c.fecha_fin ? fecha(c.fecha_fin) : 'indefinido' }}</p>
                </div>
            </article>
        </div>
        <p v-else class="text-muted-foreground">No hay registros para este tipo de relación.</p>

        <Link :href="u('/innova')" class="group mt-12 flex flex-col items-start justify-between gap-4 bg-primary p-8 text-primary-foreground sm:flex-row sm:items-center">
            <div>
                <p class="text-xs font-semibold uppercase tracking-[0.18em] opacity-75">Consultoría estudiantil</p>
                <p class="mt-1 text-2xl font-bold">Empresa Junior INNOVA</p>
                <p class="mt-1 text-sm opacity-85">Solicite un servicio de consultoría o postule para ser parte del equipo.</p>
            </div>
            <span class="inline-flex items-center gap-2 bg-marca px-5 py-3 text-sm font-semibold text-white">Conocer INNOVA <ArrowRight class="size-4 transition-transform group-hover:translate-x-1" /></span>
        </Link>
    </div>
</template>
