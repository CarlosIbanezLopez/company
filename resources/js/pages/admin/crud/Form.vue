<script setup lang="ts">
import { Head, Link, useForm } from '@inertiajs/vue3';
import { ArrowLeft, FileText, Save } from '@lucide/vue';
import { computed, ref } from 'vue';
import type { Opcion } from '@/types';

type Campo = {
    nombre: string;
    etiqueta: string;
    tipo: 'text' | 'email' | 'url' | 'number' | 'date' | 'time' | 'password' | 'textarea' | 'markdown' | 'select' | 'multiselect' | 'checkboxes' | 'checkbox' | 'image' | 'file' | 'readonly';
    requerido?: boolean;
    ayuda?: string;
    ancho?: 'full';
    opciones?: Opcion[];
    accept?: string;
};

const props = defineProps<{
    config: { titulo: string; singular: string;
        femenino?: boolean; descripcion: string | null; urls: { index: string | null; guardar?: string | null; actualizar: string | null } };
    campos: Campo[];
    valores: Record<string, unknown>;
    registroId: number | string | null;
}>();

const editando = computed(() => props.registroId !== null);

// Valores iniciales del formulario según el tipo de cada campo.
// eslint-disable-next-line @typescript-eslint/no-explicit-any
type Datos = Record<string, any>;
const inicial: Datos = {};
for (const c of props.campos) {
    const v = props.valores[c.nombre];
    if (c.tipo === 'readonly') continue;
    if (c.tipo === 'image' || c.tipo === 'file') {
        inicial[c.nombre] = null;
        inicial[`${c.nombre}_quitar`] = false;
    } else if (c.tipo === 'checkbox') {
        inicial[c.nombre] = Boolean(v);
    } else if (c.tipo === 'multiselect' || c.tipo === 'checkboxes') {
        inicial[c.nombre] = Array.isArray(v) ? v.map(Number) : [];
    } else if (c.tipo === 'password') {
        inicial[c.nombre] = '';
    } else {
        inicial[c.nombre] = v ?? '';
    }
}

const form = useForm<Datos>(inicial) as ReturnType<typeof useForm<Datos>> & Datos;
const vistas = ref<Record<string, string>>({});

function archivo(c: Campo, e: Event) {
    const f = (e.target as HTMLInputElement).files?.[0] ?? null;
    form[c.nombre] = f;
    if (f && c.tipo === 'image') vistas.value[c.nombre] = URL.createObjectURL(f);
}

function guardar() {
    const opciones = { forceFormData: true, preserveScroll: true };
    if (editando.value && props.config.urls.actualizar) {
        form.transform((d) => ({ ...d, _method: 'put' })).post(props.config.urls.actualizar.replace('__ID__', String(props.registroId)), opciones);
    } else if (props.config.urls.guardar) {
        form.post(props.config.urls.guardar, opciones);
    }
}

function grupos(opciones: Opcion[] = []) {
    const mapa = new Map<string, Opcion[]>();
    for (const o of opciones) {
        const g = o.grupo ?? '';
        mapa.set(g, [...(mapa.get(g) ?? []), o]);
    }
    return [...mapa.entries()];
}

function alternar(nombre: string, valor: number) {
    const lista = (form[nombre] as number[]) ?? [];
    form[nombre] = lista.includes(valor) ? lista.filter((x) => x !== valor) : [...lista, valor];
}

function alternarGrupo(nombre: string, opciones: Opcion[], marcar: boolean) {
    const ids = opciones.map((o) => Number(o.value));
    const lista = ((form[nombre] as number[]) ?? []).filter((x) => !ids.includes(x));
    form[nombre] = marcar ? [...lista, ...ids] : lista;
}

const filtroMulti = ref<Record<string, string>>({});
const opcionesFiltradas = (c: Campo) => (c.opciones ?? []).filter((o) => o.label.toLowerCase().includes((filtroMulti.value[c.nombre] ?? '').toLowerCase()));

const errores = computed(() => form.errors as Record<string, string>);
const error = (nombre: string) => errores.value[nombre] ?? errores.value[`${nombre}.0`];
</script>

<template>
    <Head :title="config.titulo" />

    <div class="mx-auto max-w-5xl space-y-5 p-4 md:p-6">
        <header class="flex flex-wrap items-end justify-between gap-4 border-b pb-4">
            <div>
                <Link v-if="config.urls.index" :href="config.urls.index" class="mb-2 inline-flex items-center gap-1 text-sm text-muted-foreground hover:text-primary"><ArrowLeft class="size-4" /> {{ config.titulo }}</Link>
                <h1 class="text-2xl font-bold tracking-tight">
                    <template v-if="config.urls.index">{{ editando ? 'Editar' : config.femenino ? 'Nueva' : 'Nuevo' }} {{ config.singular }}</template>
                    <template v-else>{{ config.titulo }}</template>
                </h1>
                <p v-if="config.descripcion" class="mt-1 text-sm text-muted-foreground">{{ config.descripcion }}</p>
            </div>
        </header>

        <form class="border bg-card" @submit.prevent="guardar">
            <div class="grid gap-5 p-5 md:grid-cols-2 md:p-6">
                <div v-for="c in campos" :key="c.nombre" :class="{ 'md:col-span-2': c.ancho === 'full' || ['markdown', 'checkboxes', 'multiselect'].includes(c.tipo) }">
                    <template v-if="c.tipo === 'checkbox'">
                        <label class="flex cursor-pointer items-center gap-3 border p-3 hover:border-primary">
                            <input v-model="form[c.nombre]" type="checkbox" class="size-4 accent-[var(--primary)]" />
                            <span class="text-sm font-medium">{{ c.etiqueta }}</span>
                        </label>
                    </template>

                    <template v-else>
                        <label :for="`campo-${c.nombre}`" class="etiqueta">{{ c.etiqueta }} <span v-if="c.requerido" class="text-marca">*</span></label>

                        <div v-if="c.tipo === 'readonly'" class="min-h-10 border bg-muted/50 px-3 py-2 text-sm whitespace-pre-line">{{ valores[c.nombre] ?? '—' }}</div>

                        <textarea v-else-if="c.tipo === 'textarea'" :id="`campo-${c.nombre}`" v-model="form[c.nombre] as string" class="campo" rows="4" :aria-invalid="!!error(c.nombre)" />

                        <div v-else-if="c.tipo === 'markdown'">
                            <textarea :id="`campo-${c.nombre}`" v-model="form[c.nombre] as string" class="campo min-h-64 font-mono text-[13px]" :aria-invalid="!!error(c.nombre)" />
                            <p class="mt-1 text-xs text-muted-foreground">Formato Markdown: <code>## Subtítulo</code>, <code>**negrita**</code>, <code>- lista</code>, <code>1. lista numerada</code>, <code>[texto](https://enlace)</code>, <code>&gt; cita</code>.</p>
                        </div>

                        <select v-else-if="c.tipo === 'select'" :id="`campo-${c.nombre}`" v-model="form[c.nombre]" class="campo" :aria-invalid="!!error(c.nombre)">
                            <option value="">Seleccione...</option>
                            <template v-for="[g, ops] in grupos(c.opciones)" :key="g">
                                <optgroup v-if="g" :label="g"><option v-for="o in ops" :key="String(o.value)" :value="o.value">{{ o.label }}</option></optgroup>
                                <template v-else><option v-for="o in ops" :key="String(o.value)" :value="o.value">{{ o.label }}</option></template>
                            </template>
                        </select>

                        <div v-else-if="c.tipo === 'multiselect'" class="border">
                            <input v-model="filtroMulti[c.nombre]" type="search" placeholder="Filtrar..." class="w-full border-b bg-transparent px-3 py-2 text-sm outline-none" />
                            <div class="max-h-64 overflow-y-auto p-2">
                                <template v-for="[g, ops] in grupos(opcionesFiltradas(c))" :key="g">
                                    <p v-if="g" class="mt-2 px-1 text-xs font-bold uppercase text-muted-foreground first:mt-0">{{ g }}</p>
                                    <label v-for="o in ops" :key="String(o.value)" class="flex cursor-pointer items-center gap-2 px-1 py-1 text-sm hover:bg-accent">
                                        <input type="checkbox" class="size-4 accent-[var(--primary)]" :checked="(form[c.nombre] as number[]).includes(Number(o.value))" @change="alternar(c.nombre, Number(o.value))" />
                                        {{ o.label }}
                                    </label>
                                </template>
                            </div>
                            <p class="border-t bg-muted/50 px-3 py-1.5 text-xs text-muted-foreground">{{ (form[c.nombre] as number[]).length }} seleccionado(s)</p>
                        </div>

                        <div v-else-if="c.tipo === 'checkboxes'" class="grid gap-3 sm:grid-cols-2">
                            <fieldset v-for="[g, ops] in grupos(c.opciones)" :key="g" class="border p-3">
                                <legend class="flex w-full items-center justify-between gap-2 px-1 text-xs font-bold uppercase tracking-wide">
                                    {{ g || 'General' }}
                                    <span class="flex gap-2 font-normal normal-case">
                                        <button type="button" class="text-primary hover:underline" @click="alternarGrupo(c.nombre, ops, true)">todos</button>
                                        <button type="button" class="text-muted-foreground hover:underline" @click="alternarGrupo(c.nombre, ops, false)">ninguno</button>
                                    </span>
                                </legend>
                                <label v-for="o in ops" :key="String(o.value)" class="flex cursor-pointer items-center gap-2 py-1 text-sm">
                                    <input type="checkbox" class="size-4 accent-[var(--primary)]" :checked="(form[c.nombre] as number[]).includes(Number(o.value))" @change="alternar(c.nombre, Number(o.value))" />
                                    {{ o.label }}
                                </label>
                            </fieldset>
                        </div>

                        <div v-else-if="c.tipo === 'image'" class="flex items-start gap-4">
                            <div class="size-28 shrink-0 border bg-muted">
                                <img v-if="vistas[c.nombre] || (valores[`${c.nombre}_url`] && !form[`${c.nombre}_quitar`])" :src="vistas[c.nombre] || String(valores[`${c.nombre}_url`])" alt="Vista previa" class="h-full w-full object-cover" />
                            </div>
                            <div class="flex-1 space-y-2">
                                <input :id="`campo-${c.nombre}`" type="file" accept="image/*" class="campo pt-1.5 file:mr-3 file:border-0 file:bg-secondary file:px-3 file:py-1 file:text-sm" @change="archivo(c, $event)" />
                                <label v-if="valores[`${c.nombre}_url`]" class="flex items-center gap-2 text-xs"><input v-model="form[`${c.nombre}_quitar`]" type="checkbox" class="accent-[var(--destructive)]" /> Quitar la imagen actual</label>
                            </div>
                        </div>

                        <div v-else-if="c.tipo === 'file'" class="space-y-2">
                            <input :id="`campo-${c.nombre}`" type="file" :accept="c.accept" class="campo pt-1.5 file:mr-3 file:border-0 file:bg-secondary file:px-3 file:py-1 file:text-sm" @change="archivo(c, $event)" />
                            <div v-if="valores[`${c.nombre}_url`]" class="flex items-center gap-4 text-xs">
                                <a :href="String(valores[`${c.nombre}_url`])" target="_blank" class="inline-flex items-center gap-1 text-primary hover:underline"><FileText class="size-3.5" /> Ver archivo actual</a>
                                <label class="flex items-center gap-2"><input v-model="form[`${c.nombre}_quitar`]" type="checkbox" /> Quitar archivo</label>
                            </div>
                        </div>

                        <input
                            v-else
                            :id="`campo-${c.nombre}`"
                            v-model="form[c.nombre]"
                            :type="c.tipo"
                            class="campo"
                            :autocomplete="c.tipo === 'password' ? 'new-password' : undefined"
                            :aria-invalid="!!error(c.nombre)"
                        />
                    </template>

                    <p v-if="c.ayuda && !error(c.nombre)" class="mt-1 text-xs text-muted-foreground">{{ c.ayuda }}</p>
                    <p v-if="error(c.nombre)" class="mt-1 text-xs font-medium text-destructive">{{ error(c.nombre) }}</p>
                </div>
            </div>

            <div class="flex flex-wrap items-center justify-between gap-3 border-t bg-muted/40 px-5 py-4 md:px-6">
                <p class="text-xs text-muted-foreground">Los campos con <span class="text-marca">*</span> son obligatorios. Los cambios quedan registrados en la bitácora.</p>
                <div class="flex gap-2">
                    <Link v-if="config.urls.index" :href="config.urls.index" class="boton-borde">Cancelar</Link>
                    <button type="submit" class="boton" :disabled="form.processing"><Save class="size-4" /> Guardar</button>
                </div>
            </div>
            <div v-if="form.progress" class="h-1 bg-muted"><div class="h-1 bg-primary transition-all" :style="{ width: `${form.progress.percentage}%` }" /></div>
        </form>
    </div>
</template>
