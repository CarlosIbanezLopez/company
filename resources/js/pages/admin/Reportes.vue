<script setup lang="ts">
import { Head, router } from '@inertiajs/vue3';
import { Download, Printer } from '@lucide/vue';
import { computed, ref } from 'vue';
import BarrasHorizontales from '@/components/admin/BarrasHorizontales.vue';
import { fecha, numero, u } from '@/lib/sitio';

type Par = { etiqueta: string | number; total: number };

const props = defineProps<{
    filtros: { desde: string; hasta: string };
    resumen: { visitas_periodo: number; visitas_historicas: number; paginas_registradas: number; consultas_periodo: number };
    visitasPorDia: { fecha: string; total: number }[];
    paginasPeriodo: { ruta: string; total: number }[];
    paginasHistorico: { ruta: string; total: number; ultima_visita: string | null }[];
    consultasPorEstado: Par[];
    consultasPorCategoria: Par[];
    graduadosPorSituacion: Par[];
    graduadosPorAnio: Par[];
    documentosMasDescargados: { id: number; titulo: string; descargas: number; categoria: { nombre: string } | null }[];
    contenido: Par[];
    innova: { solicitudes: Par[]; postulaciones: Par[] };
    actividadUsuarios: Par[];
}>();

const desde = ref(props.filtros.desde);
const hasta = ref(props.filtros.hasta);

function filtrar() {
    router.get(u('/admin/reportes'), { desde: desde.value, hasta: hasta.value }, { preserveScroll: true });
}

function rango(dias: number) {
    const fin = new Date();
    const inicio = new Date();
    inicio.setDate(fin.getDate() - (dias - 1));
    desde.value = inicio.toISOString().slice(0, 10);
    hasta.value = fin.toISOString().slice(0, 10);
    filtrar();
}

const exportar = computed(() => `${u('/admin/reportes/visitas.csv')}?desde=${props.filtros.desde}&hasta=${props.filtros.hasta}`);

// Gráfico de columnas de visitas diarias (una sola serie, sin leyenda).
const maxDia = computed(() => Math.max(1, ...props.visitasPorDia.map((d) => d.total)));
const escala = computed(() => {
    const m = maxDia.value;
    const paso = Math.pow(10, Math.floor(Math.log10(m)));
    const tope = Math.ceil(m / paso) * paso;
    return { tope, marcas: [tope, tope / 2, 0] };
});
const cadaEtiqueta = computed(() => Math.max(1, Math.ceil(props.visitasPorDia.length / 10)));
const sobre = ref<number | null>(null);

function imprimir() {
    window.print();
}

const pares = (l: Par[]) => l.map((p) => ({ etiqueta: String(p.etiqueta), total: p.total }));
</script>

<template>
    <Head title="Reportes y estadísticas" />

    <div class="space-y-6 p-4 md:p-6">
        <header class="flex flex-wrap items-end justify-between gap-4 border-b pb-4">
            <div>
                <span class="mb-2 block h-1 w-10 bg-marca" />
                <h1 class="text-2xl font-bold tracking-tight">Reportes y estadísticas</h1>
                <p class="mt-1 text-sm text-muted-foreground">Período: {{ fecha(filtros.desde) }} — {{ fecha(filtros.hasta) }}</p>
            </div>
            <div class="flex gap-2 print:hidden">
                <a :href="exportar" class="boton-borde"><Download class="size-4" /> Exportar visitas (CSV)</a>
                <button type="button" class="boton-borde" @click="imprimir"><Printer class="size-4" /> Imprimir / PDF</button>
            </div>
        </header>

        <!-- Filtros de período -->
        <form class="flex flex-wrap items-end gap-3 border bg-card p-3 print:hidden" @submit.prevent="filtrar">
            <div><label class="mb-1 block text-xs font-medium text-muted-foreground" for="desde">Desde</label><input id="desde" v-model="desde" type="date" class="campo" /></div>
            <div><label class="mb-1 block text-xs font-medium text-muted-foreground" for="hasta">Hasta</label><input id="hasta" v-model="hasta" type="date" class="campo" /></div>
            <button class="boton">Aplicar</button>
            <div class="flex gap-1">
                <button type="button" class="boton-borde" @click="rango(7)">7 días</button>
                <button type="button" class="boton-borde" @click="rango(30)">30 días</button>
                <button type="button" class="boton-borde" @click="rango(90)">90 días</button>
            </div>
        </form>

        <!-- Indicadores -->
        <div class="grid gap-px border bg-border sm:grid-cols-2 lg:grid-cols-4">
            <div class="bg-card p-5"><p class="text-xs font-semibold uppercase tracking-wide text-muted-foreground">Visitas en el período</p><p class="mt-2 text-3xl font-black tabular-nums">{{ numero(resumen.visitas_periodo) }}</p></div>
            <div class="bg-card p-5"><p class="text-xs font-semibold uppercase tracking-wide text-muted-foreground">Visitas históricas</p><p class="mt-2 text-3xl font-black tabular-nums">{{ numero(resumen.visitas_historicas) }}</p></div>
            <div class="bg-card p-5"><p class="text-xs font-semibold uppercase tracking-wide text-muted-foreground">Páginas con contador</p><p class="mt-2 text-3xl font-black tabular-nums">{{ numero(resumen.paginas_registradas) }}</p></div>
            <div class="bg-card p-5"><p class="text-xs font-semibold uppercase tracking-wide text-muted-foreground">Consultas en el período</p><p class="mt-2 text-3xl font-black tabular-nums">{{ numero(resumen.consultas_periodo) }}</p></div>
        </div>

        <!-- Visitas por día -->
        <section class="border bg-card">
            <header class="flex items-center justify-between border-b px-5 py-3">
                <h2 class="font-bold">Visitas por día</h2>
                <p class="text-sm text-muted-foreground tabular-nums">
                    <template v-if="sobre !== null">{{ fecha(visitasPorDia[sobre].fecha) }}: <strong class="text-foreground">{{ numero(visitasPorDia[sobre].total) }}</strong> visitas</template>
                    <template v-else>Pase el cursor sobre una columna</template>
                </p>
            </header>
            <div class="p-5">
                <div class="flex gap-3">
                    <div class="flex h-56 flex-col justify-between text-right text-xs text-muted-foreground tabular-nums">
                        <span v-for="m in escala.marcas" :key="m">{{ numero(m) }}</span>
                    </div>
                    <div class="relative h-56 flex-1 border-b border-l" @mouseleave="sobre = null">
                        <div class="pointer-events-none absolute inset-x-0 top-0 border-t border-dashed border-border" />
                        <div class="pointer-events-none absolute inset-x-0 top-1/2 border-t border-dashed border-border" />
                        <div class="absolute inset-0 flex items-end gap-[2px] px-[2px]">
                            <div v-for="(d, i) in visitasPorDia" :key="d.fecha" class="group relative flex h-full flex-1 cursor-default items-end" @mouseenter="sobre = i">
                                <div class="w-full bg-chart-1 transition-opacity" :class="{ 'opacity-60': sobre !== null && sobre !== i }" :style="{ height: `${(d.total / escala.tope) * 100}%` }" />
                            </div>
                        </div>
                    </div>
                </div>
                <div class="mt-2 ml-12 flex gap-[2px] text-[10px] text-muted-foreground">
                    <span v-for="(d, i) in visitasPorDia" :key="d.fecha" class="flex-1 text-center">{{ i % cadaEtiqueta === 0 ? d.fecha.slice(8, 10) + '/' + d.fecha.slice(5, 7) : '' }}</span>
                </div>
                <details class="mt-4 text-sm">
                    <summary class="cursor-pointer text-primary">Ver datos en tabla</summary>
                    <div class="mt-2 max-h-64 overflow-y-auto border">
                        <table class="w-full">
                            <thead class="bg-muted text-left text-xs uppercase"><tr><th class="px-3 py-2">Fecha</th><th class="px-3 py-2 text-right">Visitas</th></tr></thead>
                            <tbody class="divide-y"><tr v-for="d in visitasPorDia" :key="d.fecha"><td class="px-3 py-1.5">{{ fecha(d.fecha) }}</td><td class="px-3 py-1.5 text-right tabular-nums">{{ numero(d.total) }}</td></tr></tbody>
                        </table>
                    </div>
                </details>
            </div>
        </section>

        <div class="grid gap-6 lg:grid-cols-2">
            <section class="border bg-card">
                <header class="border-b px-5 py-3"><h2 class="font-bold">Páginas más visitadas en el período</h2></header>
                <div class="p-5"><BarrasHorizontales :datos="paginasPeriodo.map((p) => ({ etiqueta: p.ruta, total: p.total }))" /></div>
            </section>
            <section class="border bg-card">
                <header class="border-b px-5 py-3"><h2 class="font-bold">Contador histórico por página</h2></header>
                <div class="max-h-[420px] overflow-y-auto">
                    <table class="w-full text-sm">
                        <thead class="sticky top-0 bg-muted text-left text-xs uppercase text-muted-foreground"><tr><th class="px-5 py-2">Página</th><th class="px-5 py-2 text-right">Visitas</th><th class="px-5 py-2">Última visita</th></tr></thead>
                        <tbody class="divide-y">
                            <tr v-for="p in paginasHistorico" :key="p.ruta"><td class="px-5 py-2 font-mono text-xs">{{ p.ruta }}</td><td class="px-5 py-2 text-right font-semibold tabular-nums">{{ numero(p.total) }}</td><td class="px-5 py-2 text-xs text-muted-foreground">{{ fecha(p.ultima_visita, true) }}</td></tr>
                        </tbody>
                    </table>
                </div>
            </section>

            <section class="border bg-card">
                <header class="border-b px-5 py-3"><h2 class="font-bold">Consultas por categoría</h2></header>
                <div class="p-5"><BarrasHorizontales :datos="pares(consultasPorCategoria)" /></div>
            </section>
            <section class="border bg-card">
                <header class="border-b px-5 py-3"><h2 class="font-bold">Consultas por estado</h2></header>
                <div class="p-5"><BarrasHorizontales :datos="pares(consultasPorEstado)" /></div>
            </section>

            <section class="border bg-card">
                <header class="border-b px-5 py-3"><h2 class="font-bold">Graduados por situación laboral</h2></header>
                <div class="p-5"><BarrasHorizontales :datos="pares(graduadosPorSituacion)" vacio="Aún no hay graduados registrados." /></div>
            </section>
            <section class="border bg-card">
                <header class="border-b px-5 py-3"><h2 class="font-bold">Graduados por año de graduación</h2></header>
                <div class="p-5"><BarrasHorizontales :datos="pares(graduadosPorAnio)" vacio="Aún no hay graduados registrados." /></div>
            </section>

            <section class="border bg-card">
                <header class="border-b px-5 py-3"><h2 class="font-bold">Documentos más descargados</h2></header>
                <div class="p-5"><BarrasHorizontales :datos="documentosMasDescargados.map((d) => ({ etiqueta: d.titulo, total: d.descargas }))" /></div>
            </section>
            <section class="border bg-card">
                <header class="border-b px-5 py-3"><h2 class="font-bold">Contenido publicado por módulo</h2></header>
                <div class="p-5"><BarrasHorizontales :datos="pares(contenido)" /></div>
            </section>

            <section class="border bg-card">
                <header class="border-b px-5 py-3"><h2 class="font-bold">INNOVA: solicitudes y postulaciones</h2></header>
                <div class="grid gap-6 p-5 sm:grid-cols-2">
                    <div><p class="mb-3 text-xs font-bold uppercase text-muted-foreground">Solicitudes de servicio</p><BarrasHorizontales :datos="pares(innova.solicitudes)" vacio="Sin solicitudes." /></div>
                    <div><p class="mb-3 text-xs font-bold uppercase text-muted-foreground">Postulaciones</p><BarrasHorizontales :datos="pares(innova.postulaciones)" vacio="Sin postulaciones." /></div>
                </div>
            </section>
            <section class="border bg-card">
                <header class="border-b px-5 py-3"><h2 class="font-bold">Actividad de usuarios (bitácora)</h2></header>
                <div class="p-5"><BarrasHorizontales :datos="pares(actividadUsuarios)" vacio="Sin actividad registrada en el período." /></div>
            </section>
        </div>
    </div>
</template>
