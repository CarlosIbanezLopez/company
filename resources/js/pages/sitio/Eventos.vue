<script setup lang="ts">
import { Head, Link } from '@inertiajs/vue3';
import { Clock, MapPin } from '@lucide/vue';
import Paginacion from '@/components/Paginacion.vue';
import Imagen from '@/components/sitio/Imagen.vue';
import Encabezado from '@/components/sitio/Encabezado.vue';
import Seccion from '@/components/sitio/Seccion.vue';
import { fecha, hora, u } from '@/lib/sitio';
import type { Paginado } from '@/types';

type Evento = { id: number; titulo: string; slug: string; tipo: string; fecha: string; hora_inicio: string | null; hora_fin: string | null; lugar: string | null; afiche_url: string | null; enlace_inscripcion: string | null };

defineProps<{ proximos: Evento[]; historial: Paginado<Evento>; tipos: Record<string, string> }>();

const meses = ['ENE', 'FEB', 'MAR', 'ABR', 'MAY', 'JUN', 'JUL', 'AGO', 'SEP', 'OCT', 'NOV', 'DIC'];
</script>

<template>
    <Head title="Eventos académicos" />
    <Encabezado titulo="Eventos académicos" descripcion="Conferencias, seminarios, talleres, ferias y otras actividades de la Carrera." :migas="[{ titulo: 'Noticias' }, { titulo: 'Eventos' }]" />

    <Seccion subtitulo="Agenda" titulo="Próximos eventos">
        <div v-if="proximos.length" class="space-y-4">
            <Link v-for="e in proximos" :key="e.id" :href="u(`/eventos/${e.slug}`)" class="group grid border bg-card hover:border-primary sm:grid-cols-[110px_160px_1fr]">
                <div class="flex flex-row items-center justify-center gap-3 bg-primary p-4 text-primary-foreground sm:flex-col sm:gap-0">
                    <span class="text-4xl leading-none font-black">{{ e.fecha.slice(8, 10) }}</span>
                    <span class="text-xs font-bold tracking-widest">{{ meses[Number(e.fecha.slice(5, 7)) - 1] }} {{ e.fecha.slice(0, 4) }}</span>
                </div>
                <div class="hidden aspect-[4/5] sm:block"><Imagen :src="e.afiche_url" :alt="e.titulo" /></div>
                <div class="p-5">
                    <p class="text-xs font-bold uppercase tracking-wider text-marca">{{ tipos[e.tipo] ?? e.tipo }}</p>
                    <h3 class="mt-1 text-xl font-bold group-hover:text-primary">{{ e.titulo }}</h3>
                    <p class="mt-3 flex flex-wrap gap-4 text-sm text-muted-foreground">
                        <span v-if="e.hora_inicio" class="inline-flex items-center gap-1.5"><Clock class="size-4" /> {{ hora(e.hora_inicio) }}<template v-if="e.hora_fin"> - {{ hora(e.hora_fin) }}</template></span>
                        <span v-if="e.lugar" class="inline-flex items-center gap-1.5"><MapPin class="size-4" /> {{ e.lugar }}</span>
                    </p>
                    <span v-if="e.enlace_inscripcion" class="mt-4 inline-block border border-primary px-3 py-1 text-xs font-semibold text-primary">Inscripciones abiertas</span>
                </div>
            </Link>
        </div>
        <p v-else class="border bg-card p-8 text-center text-muted-foreground">No hay eventos programados por el momento.</p>
    </Seccion>

    <div class="border-t bg-muted/40">
        <Seccion subtitulo="Archivo" titulo="Historial de eventos">
            <div v-if="historial.data.length" class="grid gap-6 sm:grid-cols-2 lg:grid-cols-3">
                <Link v-for="e in historial.data" :key="e.id" :href="u(`/eventos/${e.slug}`)" class="group flex border bg-card hover:border-primary">
                    <div class="w-24 shrink-0"><Imagen :src="e.afiche_url" :alt="e.titulo" clase="grayscale group-hover:grayscale-0" /></div>
                    <div class="p-4">
                        <p class="text-xs text-muted-foreground">{{ fecha(e.fecha) }} · {{ tipos[e.tipo] }}</p>
                        <p class="mt-1 font-semibold leading-snug group-hover:text-primary">{{ e.titulo }}</p>
                    </div>
                </Link>
            </div>
            <p v-else class="text-muted-foreground">Todavía no hay eventos finalizados.</p>
            <Paginacion class="mt-8" :links="historial.links" />
        </Seccion>
    </div>
</template>
