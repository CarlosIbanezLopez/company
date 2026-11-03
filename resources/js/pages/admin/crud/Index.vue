<script setup lang="ts">
import { Head, Link, router } from '@inertiajs/vue3';
import { Check, Download, Pencil, Plus, Search, Trash2, X } from '@lucide/vue';
import { ref, watch } from 'vue';
import Icono from '@/components/Icono.vue';
import Paginacion from '@/components/Paginacion.vue';
import { Dialog, DialogContent, DialogDescription, DialogFooter, DialogHeader, DialogTitle } from '@/components/ui/dialog';
import { etiquetaEstado, fecha, valorDe } from '@/lib/sitio';
import type { Opcion, Paginado } from '@/types';

type Columna = { clave: string; etiqueta: string; tipo?: 'text' | 'image' | 'bool' | 'date' | 'datetime' | 'badge' | 'link' };
type Filtro = { clave: string; etiqueta: string; tipo?: 'select' | 'date'; opciones?: Opcion[] };
type Accion = { etiqueta: string; url: string; icono?: string; externo?: boolean };

const props = defineProps<{
    config: {
        titulo: string;
        singular: string;
        femenino?: boolean;
        descripcion: string | null;
        urls: { index: string; crear: string | null; editar: string | null; eliminar: string | null };
        acciones: Accion[];
        accionesFila: Accion[];
    };
    columnas: Columna[];
    filtros: Filtro[];
    registros: Paginado<Record<string, unknown> & { id: number }>;
    consulta: Record<string, string | null>;
    buscar: boolean;
}>();

const valores = ref<Record<string, string>>(Object.fromEntries([['q', props.consulta.q ?? ''], ...props.filtros.map((f) => [f.clave, props.consulta[f.clave] ?? ''])]));

function aplicar() {
    const datos = Object.fromEntries(Object.entries(valores.value).filter(([, v]) => v !== '' && v !== null));
    router.get(props.config.urls.index, datos, { preserveState: true, preserveScroll: true, replace: true });
}

let espera: ReturnType<typeof setTimeout> | undefined;
watch(() => valores.value.q, () => {
    clearTimeout(espera);
    espera = setTimeout(aplicar, 400);
});

function limpiar() {
    valores.value = Object.fromEntries(Object.keys(valores.value).map((k) => [k, '']));
    aplicar();
}

const hayFiltros = () => Object.values(valores.value).some((v) => v);

const porEliminar = ref<(Record<string, unknown> & { id: number }) | null>(null);

function eliminar() {
    if (!porEliminar.value || !props.config.urls.eliminar) return;
    router.delete(props.config.urls.eliminar.replace('__ID__', String(porEliminar.value.id)), {
        preserveScroll: true,
        onFinish: () => (porEliminar.value = null),
    });
}

const urlFila = (url: string, id: number) => url.replace('__ID__', String(id));

function texto(fila: Record<string, unknown>, clave: string): string {
    const v = valorDe(fila, clave);
    return v === null || v === undefined || v === '' ? '—' : String(v);
}

function nombreFila(fila: Record<string, unknown>): string {
    const c = props.columnas.find((col) => !col.tipo || col.tipo === 'text');
    return c ? texto(fila, c.clave) : `#${fila.id}`;
}

const coloresBadge: Record<string, string> = {
    publicado: 'bg-exito/15 text-exito border-exito/40',
    vigente: 'bg-exito/15 text-exito border-exito/40',
    atendida: 'bg-exito/15 text-exito border-exito/40',
    aceptada: 'bg-exito/15 text-exito border-exito/40',
    respondida: 'bg-exito/15 text-exito border-exito/40',
    finalizado: 'bg-muted text-muted-foreground',
    archivado: 'bg-muted text-muted-foreground',
    cerrada: 'bg-muted text-muted-foreground',
    borrador: 'bg-aviso/20 border-aviso/50',
    pendiente: 'bg-aviso/20 border-aviso/50',
    nueva: 'bg-marca/15 text-marca border-marca/40',
    eliminar: 'bg-marca/15 text-marca border-marca/40',
    rechazada: 'bg-marca/15 text-marca border-marca/40',
};
</script>

<template>
    <Head :title="config.titulo" />

    <div class="space-y-5 p-4 md:p-6">
        <header class="flex flex-wrap items-end justify-between gap-4 border-b pb-4">
            <div>
                <span class="mb-2 block h-1 w-10 bg-marca" />
                <h1 class="text-2xl font-bold tracking-tight">{{ config.titulo }}</h1>
                <p v-if="config.descripcion" class="mt-1 text-sm text-muted-foreground">{{ config.descripcion }}</p>
            </div>
            <div class="flex flex-wrap gap-2">
                <template v-for="a in config.acciones" :key="a.url">
                    <a v-if="a.externo" :href="a.url" class="boton-borde"><Download class="size-4" /> {{ a.etiqueta }}</a>
                    <Link v-else :href="a.url" class="boton-borde"><Icono :nombre="a.icono" class="size-4" /> {{ a.etiqueta }}</Link>
                </template>
                <Link v-if="config.urls.crear" :href="config.urls.crear" class="boton"><Plus class="size-4" /> {{ config.femenino ? 'Nueva' : 'Nuevo' }} {{ config.singular }}</Link>
            </div>
        </header>

        <!-- Búsqueda y filtros -->
        <div v-if="buscar || filtros.length" class="flex flex-wrap items-end gap-3 border bg-card p-3">
            <div v-if="buscar" class="relative min-w-56 flex-1">
                <Search class="pointer-events-none absolute top-1/2 left-3 size-4 -translate-y-1/2 text-muted-foreground" />
                <input v-model="valores.q" type="search" placeholder="Buscar..." class="campo pl-9" aria-label="Buscar" />
            </div>
            <div v-for="f in filtros" :key="f.clave" class="min-w-40">
                <label class="mb-1 block text-xs font-medium text-muted-foreground">{{ f.etiqueta }}</label>
                <input v-if="f.tipo === 'date'" v-model="valores[f.clave]" type="date" class="campo" @change="aplicar" />
                <select v-else v-model="valores[f.clave]" class="campo" @change="aplicar">
                    <option value="">Todos</option>
                    <option v-for="o in f.opciones" :key="String(o.value)" :value="String(o.value)">{{ o.label }}</option>
                </select>
            </div>
            <button v-if="hayFiltros()" type="button" class="boton-borde" @click="limpiar"><X class="size-4" /> Limpiar</button>
        </div>

        <!-- Tabla de registros -->
        <div class="overflow-x-auto border bg-card">
            <table class="w-full text-sm">
                <thead class="bg-muted text-left text-xs uppercase tracking-wide text-muted-foreground">
                    <tr>
                        <th v-for="c in columnas" :key="c.clave" class="px-4 py-3 font-semibold whitespace-nowrap">{{ c.etiqueta }}</th>
                        <th v-if="config.urls.editar || config.urls.eliminar || config.accionesFila.length" class="w-1 px-4 py-3 text-right font-semibold">Acciones</th>
                    </tr>
                </thead>
                <tbody class="divide-y">
                    <tr v-for="fila in registros.data" :key="fila.id" class="hover:bg-accent/50">
                        <td v-for="c in columnas" :key="c.clave" class="px-4 py-3 align-middle">
                            <template v-if="c.tipo === 'image'">
                                <img v-if="valorDe(fila, c.clave)" :src="String(valorDe(fila, c.clave))" alt="" class="size-12 border object-cover" />
                                <span v-else class="block size-12 border bg-muted" />
                            </template>
                            <template v-else-if="c.tipo === 'bool'">
                                <span v-if="valorDe(fila, c.clave)" class="inline-flex items-center gap-1 text-exito"><Check class="size-4" /> Sí</span>
                                <span v-else class="inline-flex items-center gap-1 text-muted-foreground"><X class="size-4" /> No</span>
                            </template>
                            <template v-else-if="c.tipo === 'date'">{{ fecha(valorDe(fila, c.clave) as string) }}</template>
                            <template v-else-if="c.tipo === 'datetime'"><span class="whitespace-nowrap">{{ fecha(valorDe(fila, c.clave) as string, true) }}</span></template>
                            <template v-else-if="c.tipo === 'badge'">
                                <span v-if="valorDe(fila, c.clave)" class="inline-block border px-2 py-0.5 text-xs font-medium whitespace-nowrap capitalize" :class="coloresBadge[String(valorDe(fila, c.clave))] ?? 'bg-secondary'">{{ etiquetaEstado(String(valorDe(fila, c.clave))) }}</span>
                                <span v-else class="text-muted-foreground">—</span>
                            </template>
                            <template v-else-if="c.tipo === 'link'">
                                <a v-if="valorDe(fila, c.clave)" :href="String(valorDe(fila, c.clave))" target="_blank" rel="noopener" class="text-primary hover:underline">Abrir</a>
                                <span v-else class="text-muted-foreground">—</span>
                            </template>
                            <span v-else class="line-clamp-2 max-w-md">{{ texto(fila, c.clave) }}</span>
                        </td>
                        <td v-if="config.urls.editar || config.urls.eliminar || config.accionesFila.length" class="px-4 py-3">
                            <div class="flex justify-end gap-1">
                                <Link v-for="a in config.accionesFila" :key="a.etiqueta" :href="urlFila(a.url, fila.id)" class="inline-flex h-8 items-center gap-1.5 border px-2.5 text-xs font-medium hover:border-primary hover:text-primary"><Icono :nombre="a.icono" class="size-3.5" />{{ a.etiqueta }}</Link>
                                <Link v-if="config.urls.editar" :href="urlFila(config.urls.editar, fila.id)" class="inline-flex size-8 items-center justify-center border hover:border-primary hover:text-primary" :title="`Editar ${config.singular}`" :aria-label="`Editar ${nombreFila(fila)}`"><Pencil class="size-3.5" /></Link>
                                <button v-if="config.urls.eliminar" type="button" class="inline-flex size-8 items-center justify-center border hover:border-destructive hover:bg-destructive hover:text-white" :title="`Eliminar ${config.singular}`" :aria-label="`Eliminar ${nombreFila(fila)}`" @click="porEliminar = fila"><Trash2 class="size-3.5" /></button>
                            </div>
                        </td>
                    </tr>
                    <tr v-if="!registros.data.length">
                        <td :colspan="columnas.length + 1" class="px-4 py-12 text-center text-muted-foreground">No hay registros{{ hayFiltros() ? ' con estos filtros' : '' }}.</td>
                    </tr>
                </tbody>
            </table>
        </div>

        <div class="flex flex-wrap items-center justify-between gap-3">
            <p class="text-sm text-muted-foreground">Mostrando {{ registros.from ?? 0 }}–{{ registros.to ?? 0 }} de {{ registros.total }}</p>
            <Paginacion :links="registros.links" />
        </div>
    </div>

    <Dialog :open="!!porEliminar" @update:open="(v) => !v && (porEliminar = null)">
        <DialogContent>
            <DialogHeader>
                <DialogTitle>¿Eliminar {{ config.singular }}?</DialogTitle>
                <DialogDescription>Se eliminará «{{ porEliminar ? nombreFila(porEliminar) : '' }}». Esta acción queda registrada en la bitácora y no se puede deshacer.</DialogDescription>
            </DialogHeader>
            <DialogFooter class="gap-2">
                <button type="button" class="boton-borde" @click="porEliminar = null">Cancelar</button>
                <button type="button" class="boton bg-destructive text-white" @click="eliminar"><Trash2 class="size-4" /> Eliminar</button>
            </DialogFooter>
        </DialogContent>
    </Dialog>
</template>
