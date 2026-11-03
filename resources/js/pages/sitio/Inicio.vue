<script setup lang="ts">
import { Head, Link } from '@inertiajs/vue3';
import { ArrowRight, BookOpen, CalendarDays, ChevronLeft, ChevronRight, ExternalLink, FlaskConical, GraduationCap, Handshake, Library, MapPin, Network, Users } from '@lucide/vue';
import { computed, onBeforeUnmount, onMounted, ref } from 'vue';
import Imagen from '@/components/sitio/Imagen.vue';
import Seccion from '@/components/sitio/Seccion.vue';
import { fecha, hora, u, youtubeEmbed } from '@/lib/sitio';

type Banner = { id: number; titulo: string; subtitulo: string | null; imagen_url: string | null; enlace: string | null; texto_boton: string | null };
type Noticia = { id: number; titulo: string; slug: string; resumen: string | null; imagen_url: string | null; fecha_publicacion: string; destacado: boolean; categoria: { nombre: string } | null };
type Evento = { id: number; titulo: string; slug: string; tipo: string; fecha: string; hora_inicio: string | null; lugar: string | null };

const props = defineProps<{
    banners: Banner[];
    presentacion: { titulo: string | null; html: string; autor: string | null; video: string | null };
    destacados: { id: number; titulo: string; descripcion: string | null; valor: string | null; enlace: string | null }[];
    noticias: Noticia[];
    eventos: Evento[];
    enlaces: { id: number; nombre: string; url: string; descripcion: string | null }[];
}>();

const actual = ref(0);
let temporizador: ReturnType<typeof setInterval> | undefined;

function ir(i: number) {
    const total = props.banners.length;
    actual.value = (i + total) % total;
}

function reiniciar() {
    clearInterval(temporizador);
    if (props.banners.length > 1) {
        temporizador = setInterval(() => ir(actual.value + 1), 6500);
    }
}

onMounted(reiniciar);
onBeforeUnmount(() => clearInterval(temporizador));

const video = computed(() => (props.presentacion.video ? youtubeEmbed(props.presentacion.video) : null));

function enlace(url: string | null) {
    return url ? (url.startsWith('http') ? url : u(url)) : null;
}

const accesos = [
    { titulo: 'Malla curricular', texto: 'Materias por semestre y prerrequisitos', url: '/malla-curricular', icono: Network },
    { titulo: 'Autoridades y docentes', texto: 'Plantel de la Carrera', url: '/plantel-docente', icono: Users },
    { titulo: 'Investigación', texto: 'Líneas, PIA y semilleros', url: '/investigacion', icono: FlaskConical },
    { titulo: 'Revista INNOVAR', texto: 'Ediciones y artículos', url: '/revista-innovar', icono: BookOpen },
    { titulo: 'Biblioteca digital', texto: 'Reglamentos y formularios', url: '/biblioteca', icono: Library },
    { titulo: 'Vinculación', texto: 'Convenios y empresa junior', url: '/vinculacion', icono: Handshake },
    { titulo: 'Programa Alumni', texto: 'Registro de graduados', url: '/graduados', icono: GraduationCap },
    { titulo: 'Eventos', texto: 'Conferencias y seminarios', url: '/eventos', icono: CalendarDays },
];

const meses = ['ENE', 'FEB', 'MAR', 'ABR', 'MAY', 'JUN', 'JUL', 'AGO', 'SEP', 'OCT', 'NOV', 'DIC'];
const dia = (f: string) => f.slice(8, 10);
const mes = (f: string) => meses[Number(f.slice(5, 7)) - 1];
</script>

<template>
    <Head title="Inicio" />

    <!-- Carrusel principal (RF-01) -->
    <section v-if="banners.length" class="relative overflow-hidden bg-primary" aria-roledescription="carrusel">
        <div class="relative h-[420px] md:h-[520px]">
            <div
                v-for="(banner, i) in banners"
                :key="banner.id"
                class="absolute inset-0 transition-opacity duration-700"
                :class="i === actual ? 'opacity-100' : 'pointer-events-none opacity-0'"
                :aria-hidden="i !== actual"
            >
                <Imagen :src="banner.imagen_url" :alt="banner.titulo" />
                <div class="absolute inset-0 bg-gradient-to-r from-[oklch(0.18_0.06_262/0.92)] via-[oklch(0.18_0.06_262/0.6)] to-transparent" />
                <div class="absolute inset-0 mx-auto flex max-w-7xl flex-col justify-center px-4 sm:px-6">
                    <div class="max-w-2xl text-white">
                        <span class="mb-4 block h-1 w-16 bg-marca" />
                        <h2 class="text-3xl font-bold tracking-tight text-balance md:text-5xl">{{ banner.titulo }}</h2>
                        <p v-if="banner.subtitulo" class="mt-4 text-lg text-white/85 md:text-xl">{{ banner.subtitulo }}</p>
                        <Link
                            v-if="banner.enlace && banner.texto_boton && !banner.enlace.startsWith('http')"
                            :href="enlace(banner.enlace)!"
                            class="mt-8 inline-flex items-center gap-2 bg-marca px-6 py-3 text-sm font-semibold text-white hover:opacity-90"
                        >
                            {{ banner.texto_boton }} <ArrowRight class="size-4" />
                        </Link>
                        <a v-else-if="banner.enlace && banner.texto_boton" :href="banner.enlace" target="_blank" rel="noopener" class="mt-8 inline-flex items-center gap-2 bg-marca px-6 py-3 text-sm font-semibold text-white hover:opacity-90">
                            {{ banner.texto_boton }} <ExternalLink class="size-4" />
                        </a>
                    </div>
                </div>
            </div>
        </div>
        <div v-if="banners.length > 1" class="absolute inset-x-0 bottom-0">
            <div class="mx-auto flex max-w-7xl items-center justify-between px-4 pb-6 sm:px-6">
                <div class="flex gap-2">
                    <button
                        v-for="(b, i) in banners"
                        :key="b.id"
                        type="button"
                        class="h-1.5 transition-all"
                        :class="i === actual ? 'w-10 bg-marca' : 'w-5 bg-white/50 hover:bg-white'"
                        :aria-label="`Ir al banner ${i + 1}`"
                        @click="ir(i); reiniciar()"
                    />
                </div>
                <div class="flex gap-1">
                    <button type="button" class="inline-flex size-10 items-center justify-center border border-white/40 text-white hover:bg-white/10" aria-label="Anterior" @click="ir(actual - 1); reiniciar()"><ChevronLeft class="size-5" /></button>
                    <button type="button" class="inline-flex size-10 items-center justify-center border border-white/40 text-white hover:bg-white/10" aria-label="Siguiente" @click="ir(actual + 1); reiniciar()"><ChevronRight class="size-5" /></button>
                </div>
            </div>
        </div>
    </section>

    <!-- Información destacada -->
    <section v-if="destacados.length" class="border-b bg-card">
        <div class="mx-auto grid max-w-7xl grid-cols-2 px-4 sm:px-6 lg:grid-cols-4">
            <component
                :is="d.enlace ? Link : 'div'"
                v-for="d in destacados"
                :key="d.id"
                :href="d.enlace ? enlace(d.enlace) : undefined"
                class="group border-l py-6 pr-4 pl-5 first:border-l-0 odd:border-l-0 lg:odd:border-l lg:first:border-l-0"
            >
                <p class="text-3xl font-black tracking-tight text-primary md:text-4xl">{{ d.valor }}</p>
                <p class="mt-1 text-sm font-semibold uppercase tracking-wide group-hover:text-marca">{{ d.titulo }}</p>
                <p v-if="d.descripcion" class="mt-1 text-xs text-muted-foreground">{{ d.descripcion }}</p>
            </component>
        </div>
    </section>

    <!-- Mensaje de presentación -->
    <Seccion subtitulo="Presentación" :titulo="presentacion.titulo ?? 'Bienvenidos'">
        <div class="grid gap-10 lg:grid-cols-5">
            <div class="lg:col-span-3">
                <div class="contenido text-lg" v-html="presentacion.html" />
                <p v-if="presentacion.autor" class="mt-6 border-l-4 border-marca pl-4 text-sm font-semibold uppercase tracking-wide text-muted-foreground">{{ presentacion.autor }}</p>
            </div>
            <div class="lg:col-span-2">
                <div v-if="video" class="aspect-video border bg-black">
                    <iframe :src="video" title="Video de presentación" class="h-full w-full" allowfullscreen loading="lazy" />
                </div>
                <div v-else class="grid grid-cols-2 gap-px border bg-border">
                    <Link v-for="a in accesos.slice(0, 4)" :key="a.url" :href="u(a.url)" class="group bg-card p-5 hover:bg-accent">
                        <component :is="a.icono" class="size-6 text-primary" />
                        <p class="mt-3 text-sm font-semibold group-hover:text-primary">{{ a.titulo }}</p>
                        <p class="text-xs text-muted-foreground">{{ a.texto }}</p>
                    </Link>
                </div>
            </div>
        </div>
    </Seccion>

    <!-- Noticias -->
    <div class="border-y bg-muted/40">
        <Seccion subtitulo="Actualidad" titulo="Noticias y novedades">
            <template #acciones>
                <Link :href="u('/noticias')" class="inline-flex items-center gap-2 text-sm font-semibold text-primary hover:underline">Ver todas <ArrowRight class="size-4" /></Link>
            </template>
            <div v-if="noticias.length" class="grid gap-6 md:grid-cols-2 lg:grid-cols-4">
                <Link
                    v-for="(n, i) in noticias"
                    :key="n.id"
                    :href="u(`/noticias/${n.slug}`)"
                    class="group flex flex-col border bg-card transition-colors hover:border-primary"
                    :class="{ 'lg:col-span-2 lg:row-span-2': i === 0 }"
                >
                    <div class="overflow-hidden" :class="i === 0 ? 'aspect-[16/10]' : 'aspect-video'">
                        <Imagen :src="n.imagen_url" :alt="n.titulo" clase="transition-transform duration-500 group-hover:scale-105" />
                    </div>
                    <div class="flex flex-1 flex-col p-5">
                        <div class="flex items-center gap-2 text-xs">
                            <span v-if="n.categoria" class="bg-primary px-2 py-0.5 font-semibold text-primary-foreground">{{ n.categoria.nombre }}</span>
                            <span class="text-muted-foreground">{{ fecha(n.fecha_publicacion) }}</span>
                        </div>
                        <h3 class="mt-3 font-bold leading-snug group-hover:text-primary" :class="i === 0 ? 'text-2xl' : 'text-base'">{{ n.titulo }}</h3>
                        <p v-if="n.resumen && i === 0" class="mt-3 text-sm text-muted-foreground">{{ n.resumen }}</p>
                    </div>
                </Link>
            </div>
            <p v-else class="text-muted-foreground">Aún no hay noticias publicadas.</p>
        </Seccion>
    </div>

    <!-- Eventos y accesos -->
    <Seccion>
        <div class="grid gap-12 lg:grid-cols-5">
            <div class="lg:col-span-2">
                <div class="mb-6 flex items-end justify-between border-b pb-4">
                    <div>
                        <p class="text-xs font-semibold uppercase tracking-[0.18em] text-marca">Agenda</p>
                        <h2 class="mt-1 text-2xl font-bold">Próximos eventos</h2>
                    </div>
                    <Link :href="u('/eventos')" class="text-sm font-semibold text-primary hover:underline">Ver agenda</Link>
                </div>
                <ul v-if="eventos.length" class="divide-y border">
                    <li v-for="e in eventos" :key="e.id">
                        <Link :href="u(`/eventos/${e.slug}`)" class="group flex gap-4 bg-card p-4 hover:bg-accent">
                            <div class="flex w-16 shrink-0 flex-col items-center justify-center bg-primary py-2 text-primary-foreground">
                                <span class="text-2xl leading-none font-black">{{ dia(e.fecha) }}</span>
                                <span class="text-[11px] font-semibold tracking-widest">{{ mes(e.fecha) }}</span>
                            </div>
                            <div class="min-w-0">
                                <p class="text-xs font-semibold uppercase tracking-wide text-marca">{{ e.tipo }}</p>
                                <p class="font-semibold leading-snug group-hover:text-primary">{{ e.titulo }}</p>
                                <p class="mt-1 flex items-center gap-1 text-xs text-muted-foreground"><MapPin class="size-3" /> {{ e.lugar }} <span v-if="e.hora_inicio">· {{ hora(e.hora_inicio) }}</span></p>
                            </div>
                        </Link>
                    </li>
                </ul>
                <p v-else class="border bg-card p-6 text-sm text-muted-foreground">No hay eventos programados por el momento.</p>
            </div>

            <div class="lg:col-span-3">
                <div class="mb-6 border-b pb-4">
                    <p class="text-xs font-semibold uppercase tracking-[0.18em] text-marca">Accesos rápidos</p>
                    <h2 class="mt-1 text-2xl font-bold">Explora la Carrera</h2>
                </div>
                <div class="grid grid-cols-2 gap-px border bg-border sm:grid-cols-4">
                    <Link v-for="a in accesos" :key="a.url" :href="u(a.url)" class="group flex flex-col bg-card p-5 transition-colors hover:bg-primary hover:text-primary-foreground">
                        <component :is="a.icono" class="size-6 text-primary group-hover:text-primary-foreground" />
                        <p class="mt-4 text-sm font-semibold">{{ a.titulo }}</p>
                        <p class="mt-1 text-xs text-muted-foreground group-hover:text-primary-foreground/80">{{ a.texto }}</p>
                    </Link>
                </div>
            </div>
        </div>
    </Seccion>

    <!-- Enlaces a plataformas de la Universidad -->
    <section v-if="enlaces.length" class="border-t bg-card">
        <div class="mx-auto max-w-7xl px-4 py-10 sm:px-6">
            <p class="mb-5 text-xs font-semibold uppercase tracking-[0.18em] text-muted-foreground">Plataformas de la Universidad</p>
            <div class="grid gap-3 sm:grid-cols-2 lg:grid-cols-4">
                <a v-for="e in enlaces" :key="e.id" :href="e.url" target="_blank" rel="noopener" class="group flex items-start justify-between gap-3 border p-4 hover:border-primary">
                    <div>
                        <p class="text-sm font-semibold group-hover:text-primary">{{ e.nombre }}</p>
                        <p v-if="e.descripcion" class="mt-1 text-xs text-muted-foreground">{{ e.descripcion }}</p>
                    </div>
                    <ExternalLink class="size-4 shrink-0 text-muted-foreground group-hover:text-primary" />
                </a>
            </div>
        </div>
    </section>
</template>
