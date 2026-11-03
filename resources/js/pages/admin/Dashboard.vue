<script setup lang="ts">
import { Head, Link, usePage } from '@inertiajs/vue3';
import { ArrowRight } from '@lucide/vue';
import { computed } from 'vue';
import BarrasHorizontales from '@/components/admin/BarrasHorizontales.vue';
import Icono from '@/components/Icono.vue';
import { etiquetaEstado, fecha, numero, u } from '@/lib/sitio';

defineProps<{
    tarjetas: { titulo: string; valor: number; icono: string }[];
    consultas: { id: number; nombre: string; mensaje: string; estado: string; created_at: string; categoria: { nombre: string } | null }[];
    bitacora: { id: number; accion: string; modulo: string; descripcion: string; created_at: string; usuario: { name: string } | null }[];
    paginasMasVistas: { ruta: string; total: number }[];
}>();

const page = usePage();
const usuario = computed(() => page.props.auth.user);
const puede = (p: string) => (usuario.value.permisos ?? []).includes(p);
</script>

<template>
    <Head title="Panel de administración" />

    <div class="space-y-6 p-4 md:p-6">
        <header class="border-b pb-4">
            <span class="mb-2 block h-1 w-10 bg-marca" />
            <h1 class="text-2xl font-bold tracking-tight">Bienvenido, {{ usuario.name }}</h1>
            <p class="mt-1 text-sm text-muted-foreground">Rol: <strong>{{ usuario.rol }}</strong> · Resumen general del portal de la Carrera.</p>
        </header>

        <div class="grid gap-px border bg-border sm:grid-cols-2 xl:grid-cols-6">
            <div v-for="t in tarjetas" :key="t.titulo" class="bg-card p-5">
                <div class="flex items-center justify-between text-muted-foreground">
                    <p class="text-xs font-semibold uppercase tracking-wide">{{ t.titulo }}</p>
                    <Icono :nombre="t.icono" class="size-4" />
                </div>
                <p class="mt-3 text-3xl font-black tabular-nums">{{ numero(t.valor) }}</p>
            </div>
        </div>

        <div class="grid gap-6 xl:grid-cols-3">
            <section v-if="puede('consultas.gestionar')" class="border bg-card xl:col-span-2">
                <header class="flex items-center justify-between border-b px-5 py-3">
                    <h2 class="font-bold">Últimas consultas recibidas</h2>
                    <Link :href="u('/admin/consultas')" class="inline-flex items-center gap-1 text-sm text-primary hover:underline">Ver todas <ArrowRight class="size-4" /></Link>
                </header>
                <ul class="divide-y">
                    <li v-for="c in consultas" :key="c.id">
                        <Link :href="u(`/admin/consultas/${c.id}/editar`)" class="flex items-start justify-between gap-4 px-5 py-3 hover:bg-accent/50">
                            <div class="min-w-0">
                                <p class="text-sm font-semibold">{{ c.nombre }} <span class="font-normal text-muted-foreground">· {{ c.categoria?.nombre }}</span></p>
                                <p class="truncate text-sm text-muted-foreground">{{ c.mensaje }}</p>
                            </div>
                            <div class="shrink-0 text-right">
                                <span class="border px-2 py-0.5 text-xs capitalize" :class="c.estado === 'nueva' ? 'border-marca/40 bg-marca/15 text-marca' : ''">{{ etiquetaEstado(c.estado) }}</span>
                                <p class="mt-1 text-xs text-muted-foreground">{{ fecha(c.created_at) }}</p>
                            </div>
                        </Link>
                    </li>
                    <li v-if="!consultas.length" class="px-5 py-8 text-center text-sm text-muted-foreground">No hay consultas.</li>
                </ul>
            </section>

            <section class="border bg-card">
                <header class="border-b px-5 py-3"><h2 class="font-bold">Páginas más visitadas</h2></header>
                <div class="p-5"><BarrasHorizontales :datos="paginasMasVistas.map((p) => ({ etiqueta: p.ruta, total: p.total }))" /></div>
            </section>

            <section v-if="puede('bitacora.ver')" class="border bg-card xl:col-span-3">
                <header class="flex items-center justify-between border-b px-5 py-3">
                    <h2 class="font-bold">Actividad reciente (bitácora)</h2>
                    <Link :href="u('/admin/bitacora')" class="inline-flex items-center gap-1 text-sm text-primary hover:underline">Ver bitácora <ArrowRight class="size-4" /></Link>
                </header>
                <div class="overflow-x-auto">
                    <table class="w-full text-sm">
                        <tbody class="divide-y">
                            <tr v-for="b in bitacora" :key="b.id">
                                <td class="px-5 py-2.5 whitespace-nowrap text-muted-foreground">{{ fecha(b.created_at, true) }}</td>
                                <td class="px-5 py-2.5 font-medium whitespace-nowrap">{{ b.usuario?.name }}</td>
                                <td class="px-5 py-2.5"><span class="border bg-secondary px-2 py-0.5 text-xs capitalize">{{ etiquetaEstado(b.accion) }}</span></td>
                                <td class="px-5 py-2.5 text-muted-foreground">{{ b.modulo }}</td>
                                <td class="px-5 py-2.5">{{ b.descripcion }}</td>
                            </tr>
                            <tr v-if="!bitacora.length"><td class="px-5 py-8 text-center text-muted-foreground">Sin actividad registrada.</td></tr>
                        </tbody>
                    </table>
                </div>
            </section>
        </div>
    </div>
</template>
