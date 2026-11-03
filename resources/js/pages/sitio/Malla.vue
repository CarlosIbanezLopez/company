<script setup lang="ts">
import { Head } from '@inertiajs/vue3';
import { ArrowDownRight, ArrowUpLeft, Clock, X } from '@lucide/vue';
import { computed, onMounted, ref } from 'vue';
import Encabezado from '@/components/sitio/Encabezado.vue';

type Materia = {
    id: number; sigla: string; nombre: string; semestre: number; creditos: number; horas_teoricas: number; horas_practicas: number;
    tipo: string; descripcion: string | null; prerequisitos: number[]; posteriores: number[];
};

const props = defineProps<{ materias: Materia[]; semestres: number[] }>();

const seleccionada = ref<Materia | null>(null);
const porId = computed(() => new Map(props.materias.map((m) => [m.id, m])));
const porSemestre = (s: number) => props.materias.filter((m) => m.semestre === s);

const prerequisitos = computed(() => new Set(seleccionada.value?.prerequisitos ?? []));
const posteriores = computed(() => new Set(seleccionada.value?.posteriores ?? []));

function estado(m: Materia) {
    if (!seleccionada.value) return 'normal';
    if (m.id === seleccionada.value.id) return 'actual';
    if (prerequisitos.value.has(m.id)) return 'previa';
    if (posteriores.value.has(m.id)) return 'posterior';
    return 'atenuada';
}

function seleccionar(m: Materia) {
    seleccionada.value = seleccionada.value?.id === m.id ? null : m;
}

onMounted(() => {
    const id = Number(new URLSearchParams(window.location.search).get('materia'));
    if (id && porId.value.get(id)) seleccionada.value = porId.value.get(id)!;
});

const clases: Record<string, string> = {
    normal: 'border-border bg-card hover:border-primary',
    actual: 'border-primary bg-primary text-primary-foreground',
    previa: 'border-marca bg-marca/10 ring-2 ring-marca',
    posterior: 'border-chart-5 bg-chart-5/15 ring-2 ring-chart-5',
    atenuada: 'border-border bg-card opacity-35',
};

const totalCreditos = computed(() => props.materias.reduce((a, m) => a + m.creditos, 0));
</script>

<template>
    <Head title="Malla curricular" />
    <Encabezado titulo="Malla curricular interactiva" descripcion="Seleccione una materia para ver sus datos, los prerrequisitos que necesita y las materias que dependen de ella." :migas="[{ titulo: 'Académica' }, { titulo: 'Malla curricular' }]">
        <div class="mt-6 flex flex-wrap gap-6 text-sm">
            <span><strong class="text-2xl">{{ semestres.length }}</strong> semestres</span>
            <span><strong class="text-2xl">{{ materias.length }}</strong> materias</span>
            <span><strong class="text-2xl">{{ totalCreditos }}</strong> créditos</span>
        </div>
    </Encabezado>

    <div class="mx-auto max-w-[1500px] px-4 py-8 sm:px-6">
        <div class="mb-5 flex flex-wrap items-center gap-4 text-xs">
            <span class="inline-flex items-center gap-2"><span class="size-3.5 bg-primary" /> Materia seleccionada</span>
            <span class="inline-flex items-center gap-2"><span class="size-3.5 border-2 border-marca bg-marca/10" /> Prerrequisito</span>
            <span class="inline-flex items-center gap-2"><span class="size-3.5 border-2 border-chart-5 bg-chart-5/15" /> Materia posterior que depende de ella</span>
        </div>

        <div class="overflow-x-auto pb-4">
            <div class="grid min-w-[1100px] gap-3" :style="{ gridTemplateColumns: `repeat(${semestres.length}, minmax(0, 1fr))` }">
                <div v-for="s in semestres" :key="s" class="flex flex-col gap-2">
                    <div class="bg-primary px-2 py-2 text-center text-xs font-bold uppercase tracking-wider text-primary-foreground dark:bg-secondary dark:text-foreground">{{ s }}° semestre</div>
                    <button
                        v-for="m in porSemestre(s)"
                        :key="m.id"
                        type="button"
                        class="border-2 p-2 text-left transition-all"
                        :class="clases[estado(m)]"
                        :aria-pressed="seleccionada?.id === m.id"
                        @click="seleccionar(m)"
                    >
                        <span class="block text-[11px] font-bold tracking-wide opacity-80">{{ m.sigla }}</span>
                        <span class="mt-0.5 block text-xs leading-snug font-semibold">{{ m.nombre }}</span>
                        <span class="mt-1 block text-[10px] opacity-70">{{ m.creditos }} créditos</span>
                    </button>
                </div>
            </div>
        </div>

        <!-- Detalle de la materia seleccionada -->
        <div v-if="seleccionada" class="sticky bottom-0 z-30 mt-4 border-2 border-primary bg-card shadow-2xl">
            <div class="flex items-start justify-between gap-4 border-b bg-muted px-5 py-3">
                <div>
                    <p class="text-xs font-bold tracking-wider text-marca">{{ seleccionada.sigla }} · {{ seleccionada.semestre }}° SEMESTRE · {{ seleccionada.tipo.toUpperCase() }}</p>
                    <h2 class="text-xl font-bold">{{ seleccionada.nombre }}</h2>
                </div>
                <button type="button" class="inline-flex size-9 items-center justify-center border hover:border-primary" aria-label="Cerrar detalle" @click="seleccionada = null"><X class="size-4" /></button>
            </div>
            <div class="grid gap-6 p-5 md:grid-cols-3">
                <div>
                    <p class="text-sm text-muted-foreground">{{ seleccionada.descripcion }}</p>
                    <p class="mt-3 flex items-center gap-2 text-sm"><Clock class="size-4" /> {{ seleccionada.horas_teoricas }} h teóricas · {{ seleccionada.horas_practicas }} h prácticas · {{ seleccionada.creditos }} créditos</p>
                </div>
                <div>
                    <p class="mb-2 flex items-center gap-2 text-xs font-bold uppercase tracking-wider text-marca"><ArrowUpLeft class="size-4" /> Prerrequisitos</p>
                    <ul v-if="seleccionada.prerequisitos.length" class="space-y-1 text-sm">
                        <li v-for="id in seleccionada.prerequisitos" :key="id"><button class="text-left hover:text-primary hover:underline" @click="seleccionar(porId.get(id)!)">{{ porId.get(id)?.sigla }} - {{ porId.get(id)?.nombre }}</button></li>
                    </ul>
                    <p v-else class="text-sm text-muted-foreground">No tiene prerrequisitos.</p>
                </div>
                <div>
                    <p class="mb-2 flex items-center gap-2 text-xs font-bold uppercase tracking-wider text-chart-5"><ArrowDownRight class="size-4" /> Habilita a</p>
                    <ul v-if="seleccionada.posteriores.length" class="space-y-1 text-sm">
                        <li v-for="id in seleccionada.posteriores" :key="id"><button class="text-left hover:text-primary hover:underline" @click="seleccionar(porId.get(id)!)">{{ porId.get(id)?.sigla }} - {{ porId.get(id)?.nombre }}</button></li>
                    </ul>
                    <p v-else class="text-sm text-muted-foreground">Ninguna materia depende de esta.</p>
                </div>
            </div>
        </div>
    </div>
</template>
