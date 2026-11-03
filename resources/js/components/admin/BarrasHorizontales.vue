<script setup lang="ts">
import { computed } from 'vue';
import { numero } from '@/lib/sitio';

/** Ranking de una sola serie: etiqueta, barra proporcional y valor en texto. */
const props = defineProps<{
    datos: { etiqueta: string; total: number }[];
    vacio?: string;
}>();

const maximo = computed(() => Math.max(1, ...props.datos.map((d) => d.total)));
</script>

<template>
    <ul v-if="datos.length" class="space-y-2.5">
        <li v-for="d in datos" :key="d.etiqueta" class="group" :title="`${d.etiqueta}: ${numero(d.total)}`">
            <div class="mb-1 flex items-baseline justify-between gap-3 text-sm">
                <span class="truncate">{{ d.etiqueta }}</span>
                <span class="shrink-0 font-semibold tabular-nums">{{ numero(d.total) }}</span>
            </div>
            <div class="h-2 bg-muted">
                <div class="h-2 bg-chart-1 transition-opacity group-hover:opacity-80" :style="{ width: `${(d.total / maximo) * 100}%` }" />
            </div>
        </li>
    </ul>
    <p v-else class="text-sm text-muted-foreground">{{ vacio ?? 'Sin datos en el período.' }}</p>
</template>
