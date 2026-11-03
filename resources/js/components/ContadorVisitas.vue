<script setup lang="ts">
import { usePage } from '@inertiajs/vue3';
import { Eye } from '@lucide/vue';
import { computed } from 'vue';
import { numero } from '@/lib/sitio';

const page = usePage();
const visitas = computed(() => page.props.visitas as number | null);
</script>

<template>
    <div v-if="visitas" class="inline-flex items-center gap-2 text-xs" data-test="contador-visitas">
        <Eye class="size-3.5" />
        <span>Esta página ha sido visitada <strong class="font-semibold tabular-nums">{{ numero(visitas) }}</strong> {{ visitas === 1 ? 'vez' : 'veces' }}</span>
    </div>
</template>
