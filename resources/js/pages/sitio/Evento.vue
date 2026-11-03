<script setup lang="ts">
import { Head } from '@inertiajs/vue3';
import { CalendarDays, Clock, ExternalLink, MapPin } from '@lucide/vue';
import Encabezado from '@/components/sitio/Encabezado.vue';
import { fecha, hora, u } from '@/lib/sitio';

defineProps<{
    evento: { titulo: string; tipo: string; html: string; fecha: string; hora_inicio: string | null; hora_fin: string | null; lugar: string | null; afiche_url: string | null; enlace_inscripcion: string | null; finalizado: boolean };
    tipos: Record<string, string>;
}>();
</script>

<template>
    <Head :title="evento.titulo" />
    <Encabezado :titulo="evento.titulo" :descripcion="tipos[evento.tipo]" :migas="[{ titulo: 'Eventos', url: u('/eventos') }, { titulo: evento.finalizado ? 'Historial' : 'Próximo' }]" />

    <div class="mx-auto grid max-w-6xl gap-10 px-4 py-12 sm:px-6 md:grid-cols-5">
        <div class="md:col-span-2">
            <img v-if="evento.afiche_url" :src="evento.afiche_url" :alt="`Afiche: ${evento.titulo}`" class="w-full border shadow-lg" />
        </div>
        <div class="md:col-span-3">
            <p v-if="evento.finalizado" class="mb-4 border-l-4 border-muted-foreground bg-muted px-4 py-2 text-sm">Este evento ya finalizó y forma parte del historial.</p>
            <dl class="grid gap-px border bg-border sm:grid-cols-3">
                <div class="bg-card p-4"><dt class="flex items-center gap-1.5 text-xs font-bold uppercase text-muted-foreground"><CalendarDays class="size-4" /> Fecha</dt><dd class="mt-1 font-semibold">{{ fecha(evento.fecha) }}</dd></div>
                <div class="bg-card p-4"><dt class="flex items-center gap-1.5 text-xs font-bold uppercase text-muted-foreground"><Clock class="size-4" /> Horario</dt><dd class="mt-1 font-semibold">{{ hora(evento.hora_inicio) || '—' }}<template v-if="evento.hora_fin"> - {{ hora(evento.hora_fin) }}</template></dd></div>
                <div class="bg-card p-4"><dt class="flex items-center gap-1.5 text-xs font-bold uppercase text-muted-foreground"><MapPin class="size-4" /> Lugar</dt><dd class="mt-1 font-semibold">{{ evento.lugar ?? '—' }}</dd></div>
            </dl>
            <div class="contenido mt-8" v-html="evento.html" />
            <a v-if="evento.enlace_inscripcion && !evento.finalizado" :href="evento.enlace_inscripcion" target="_blank" rel="noopener" class="boton mt-8 bg-marca text-white">Inscribirme <ExternalLink class="size-4" /></a>
        </div>
    </div>
</template>
