<script setup lang="ts">
import { Link, router, usePage } from '@inertiajs/vue3';
import { ChevronDown, LogIn, Mail, MapPin, Menu, Phone, Search, X } from '@lucide/vue';
import { computed, onBeforeUnmount, onMounted, ref } from 'vue';
import ContadorVisitas from '@/components/ContadorVisitas.vue';
import RedesSociales from '@/components/sitio/RedesSociales.vue';
import TemaToggle from '@/components/TemaToggle.vue';
import { Toaster } from '@/components/ui/sonner';
import { u } from '@/lib/sitio';
import type { DatosSitio } from '@/types';

type Item = { titulo: string; url: string };
type Grupo = { titulo: string; url?: string; items?: Item[] };

const page = usePage();
const sitio = computed(() => page.props.sitio as DatosSitio);
const usuario = computed(() => page.props.auth?.user ?? null);

const menu = computed<Grupo[]>(() => [
    { titulo: 'Inicio', url: u('/') },
    {
        titulo: 'La Carrera',
        items: [
            ...sitio.value.menu.institucional.map((p) => ({ titulo: p.titulo, url: u(`/institucional/${p.slug}`) })),
            { titulo: 'Autoridades y docentes', url: u('/plantel-docente') },
        ],
    },
    {
        titulo: 'Académica',
        items: [
            { titulo: 'Malla curricular', url: u('/malla-curricular') },
            ...sitio.value.menu.academico.map((p) => ({ titulo: p.titulo, url: u(`/academico/${p.slug}`) })),
            { titulo: 'Calendario académico', url: u('/calendario-academico') },
            { titulo: 'Preguntas frecuentes', url: u('/preguntas-frecuentes') },
        ],
    },
    {
        titulo: 'Investigación',
        items: [
            { titulo: 'Líneas y proyectos', url: u('/investigacion') },
            { titulo: 'Proyectos de Investigación en Aula (PIA)', url: u('/investigacion?tipo=pia') },
            { titulo: 'Semilleros de investigación', url: u('/investigacion?tipo=semillero') },
            { titulo: 'Producción académica', url: u('/investigacion?tipo=produccion') },
            { titulo: 'Biblioteca digital', url: u('/biblioteca') },
        ],
    },
    {
        titulo: 'Vinculación',
        items: [
            { titulo: 'Convenios y relaciones', url: u('/vinculacion') },
            { titulo: 'Empresa Junior INNOVA', url: u('/innova') },
        ],
    },
    {
        titulo: 'INNOVAR',
        items: [
            { titulo: 'Revista INNOVAR', url: u('/revista-innovar') },
            { titulo: 'Normas para autores', url: u('/revista-innovar/normas-para-autores') },
        ],
    },
    { titulo: 'Alumni', url: u('/graduados') },
    {
        titulo: 'Noticias',
        items: [
            { titulo: 'Noticias y comunicados', url: u('/noticias') },
            { titulo: 'Eventos académicos', url: u('/eventos') },
            { titulo: 'Galería multimedia', url: u('/galeria') },
        ],
    },
    { titulo: 'Contacto', url: u('/contacto') },
]);

function activo(url: string): boolean {
    const ruta = url.split('?')[0];
    const actual = page.url.split('?')[0];

    return ruta === actual || (ruta !== u('/') && actual.startsWith(`${ruta}/`));
}

const movilAbierto = ref(false);
const grupoMovil = ref<string | null>(null);
const buscando = ref(false);
const texto = ref('');

function buscar() {
    if (texto.value.trim().length < 3) {
        return;
    }

    buscando.value = false;
    movilAbierto.value = false;
    router.get(u('/buscar'), { q: texto.value.trim() });
}

let quitarListener: (() => void) | undefined;

onMounted(() => {
    quitarListener = router.on('navigate', () => {
        movilAbierto.value = false;
        grupoMovil.value = null;
    });
});

onBeforeUnmount(() => quitarListener?.());

const anio = new Date().getFullYear();
</script>

<template>
    <div class="flex min-h-svh flex-col bg-background">
        <a href="#contenido" class="sr-only focus:not-sr-only focus:absolute focus:z-50 focus:bg-primary focus:p-3 focus:text-primary-foreground">Saltar al contenido</a>

        <!-- Barra superior institucional -->
        <div class="border-t-4 border-marca bg-[oklch(0.25_0.08_262)] text-xs text-white dark:bg-[oklch(0.13_0.02_262)]">
            <div class="mx-auto flex max-w-7xl items-center justify-between gap-4 px-4 py-2 sm:px-6">
                <p class="hidden truncate opacity-85 md:block">{{ sitio.universidad }} · {{ sitio.facultad }}</p>
                <div class="ml-auto flex items-center gap-4">
                    <span v-if="sitio.contacto.telefonos" class="hidden items-center gap-1.5 opacity-85 lg:inline-flex"><Phone class="size-3.5" />{{ sitio.contacto.telefonos }}</span>
                    <span v-if="sitio.contacto.correo" class="hidden items-center gap-1.5 opacity-85 sm:inline-flex"><Mail class="size-3.5" />{{ sitio.contacto.correo }}</span>
                    <Link v-if="usuario" :href="usuario.panel ? u('/admin') : u('/graduados/mi-ficha')" class="inline-flex items-center gap-1.5 border border-white/30 px-2.5 py-1 font-medium hover:bg-white/10">
                        {{ usuario.panel ? 'Panel de administración' : 'Mi ficha de graduado' }}
                    </Link>
                    <Link v-else :href="u('/login')" class="inline-flex items-center gap-1.5 border border-white/30 px-2.5 py-1 font-medium hover:bg-white/10">
                        <LogIn class="size-3.5" /> Ingresar
                    </Link>
                    <TemaToggle class="size-7 border-white/30 text-white" />
                </div>
            </div>
        </div>

        <!-- Encabezado y navegación principal -->
        <header class="sticky top-0 z-40 border-b bg-card/95 backdrop-blur supports-[backdrop-filter]:bg-card/85">
            <div class="mx-auto flex max-w-7xl items-center gap-4 px-4 sm:px-6">
                <Link :href="u('/')" class="flex min-w-0 items-center gap-3 py-3">
                    <span class="relative flex size-11 items-center justify-center bg-primary text-sm font-black tracking-tight text-primary-foreground">
                        AE
                        <span class="absolute inset-x-0 bottom-0 h-1 bg-marca" />
                    </span>
                    <span class="min-w-0 leading-tight">
                        <span class="block text-[11px] font-semibold uppercase tracking-[0.16em] text-muted-foreground">Carrera de</span>
                        <span class="block truncate text-sm font-bold sm:text-base">{{ sitio.carrera }}</span>
                    </span>
                </Link>

                <nav class="ml-auto hidden items-stretch xl:flex" aria-label="Navegación principal">
                    <div v-for="grupo in menu" :key="grupo.titulo" class="group relative">
                        <Link
                            v-if="grupo.url"
                            :href="grupo.url"
                            class="flex h-16 items-center border-b-2 border-transparent px-2.5 text-sm font-medium whitespace-nowrap transition-colors hover:border-marca hover:text-primary"
                            :class="{ 'border-marca text-primary': activo(grupo.url) }"
                        >
                            {{ grupo.titulo }}
                        </Link>
                        <template v-else>
                            <button type="button" class="flex h-16 items-center gap-1 border-b-2 border-transparent px-2.5 text-sm font-medium whitespace-nowrap transition-colors group-hover:border-marca group-hover:text-primary group-focus-within:border-marca">
                                {{ grupo.titulo }} <ChevronDown class="size-3.5 transition-transform group-hover:rotate-180" />
                            </button>
                            <div class="invisible absolute top-full left-0 z-50 min-w-64 translate-y-1 border border-t-2 border-t-marca bg-popover py-2 opacity-0 shadow-lg transition-all group-focus-within:visible group-focus-within:translate-y-0 group-focus-within:opacity-100 group-hover:visible group-hover:translate-y-0 group-hover:opacity-100">
                                <Link v-for="item in grupo.items" :key="item.url" :href="item.url" class="block border-l-2 border-transparent px-4 py-2 text-sm hover:border-primary hover:bg-accent">
                                    {{ item.titulo }}
                                </Link>
                            </div>
                        </template>
                    </div>
                </nav>

                <div class="ml-auto flex shrink-0 items-center gap-2 xl:ml-2">
                    <button type="button" class="inline-flex size-10 items-center justify-center border hover:border-primary hover:text-primary" aria-label="Buscar en el sitio" @click="buscando = !buscando">
                        <X v-if="buscando" class="size-4" />
                        <Search v-else class="size-4" />
                    </button>
                    <button type="button" class="inline-flex size-10 items-center justify-center border hover:border-primary xl:hidden" aria-label="Abrir menú" @click="movilAbierto = !movilAbierto">
                        <X v-if="movilAbierto" class="size-5" />
                        <Menu v-else class="size-5" />
                    </button>
                </div>
            </div>

            <!-- Buscador interno -->
            <div v-if="buscando" class="border-t bg-muted/60">
                <form class="mx-auto flex max-w-7xl gap-2 px-4 py-3 sm:px-6" role="search" @submit.prevent="buscar">
                    <input
                        v-model="texto"
                        v-focus
                        type="search"
                        minlength="3"
                        placeholder="Buscar noticias, eventos, documentos, materias, proyectos o artículos..."
                        class="h-11 w-full border border-input bg-background px-4 text-sm outline-none focus:border-primary"
                    />
                    <button type="submit" class="h-11 shrink-0 bg-primary px-5 text-sm font-semibold text-primary-foreground hover:opacity-90">Buscar</button>
                </form>
            </div>

            <!-- Menú móvil -->
            <nav v-if="movilAbierto" class="max-h-[75vh] overflow-y-auto border-t bg-card xl:hidden" aria-label="Navegación móvil">
                <div v-for="grupo in menu" :key="grupo.titulo" class="border-b">
                    <Link v-if="grupo.url" :href="grupo.url" class="block px-4 py-3 text-sm font-semibold sm:px-6">{{ grupo.titulo }}</Link>
                    <template v-else>
                        <button type="button" class="flex w-full items-center justify-between px-4 py-3 text-sm font-semibold sm:px-6" @click="grupoMovil = grupoMovil === grupo.titulo ? null : grupo.titulo">
                            {{ grupo.titulo }}
                            <ChevronDown class="size-4 transition-transform" :class="{ 'rotate-180': grupoMovil === grupo.titulo }" />
                        </button>
                        <div v-if="grupoMovil === grupo.titulo" class="bg-muted/50 pb-2">
                            <Link v-for="item in grupo.items" :key="item.url" :href="item.url" class="block border-l-2 border-marca px-6 py-2 text-sm sm:px-8">{{ item.titulo }}</Link>
                        </div>
                    </template>
                </div>
            </nav>
        </header>

        <main id="contenido" class="flex-1">
            <slot />
        </main>

        <!-- Pie de página con contacto, redes y contador de visitas -->
        <footer class="mt-16 border-t-4 border-marca bg-[oklch(0.22_0.06_262)] text-white/85 dark:bg-[oklch(0.13_0.02_262)]">
            <div class="mx-auto grid max-w-7xl gap-10 px-4 py-12 sm:px-6 md:grid-cols-2 lg:grid-cols-4">
                <div>
                    <p class="text-xs font-semibold uppercase tracking-[0.16em] text-white/60">Carrera de</p>
                    <p class="mt-1 text-xl font-bold text-white">{{ sitio.carrera }}</p>
                    <p class="mt-3 text-sm">{{ sitio.facultad }}</p>
                    <p class="text-sm">{{ sitio.universidad }}</p>
                    <RedesSociales class="mt-5" :redes="sitio.redes" />
                </div>
                <div>
                    <h3 class="mb-4 text-sm font-bold uppercase tracking-wider text-white">Contacto</h3>
                    <ul class="space-y-3 text-sm">
                        <li v-if="sitio.contacto.direccion" class="flex gap-2"><MapPin class="mt-0.5 size-4 shrink-0" />{{ sitio.contacto.direccion }}</li>
                        <li v-if="sitio.contacto.telefonos" class="flex gap-2"><Phone class="mt-0.5 size-4 shrink-0" />{{ sitio.contacto.telefonos }}</li>
                        <li v-if="sitio.contacto.correo" class="flex gap-2"><Mail class="mt-0.5 size-4 shrink-0" /><a :href="`mailto:${sitio.contacto.correo}`" class="hover:underline">{{ sitio.contacto.correo }}</a></li>
                        <li v-if="sitio.contacto.horario" class="text-white/70">{{ sitio.contacto.horario }}</li>
                    </ul>
                </div>
                <div>
                    <h3 class="mb-4 text-sm font-bold uppercase tracking-wider text-white">La Carrera</h3>
                    <ul class="space-y-2 text-sm">
                        <li v-for="p in sitio.menu.institucional.slice(0, 5)" :key="p.slug"><Link :href="u(`/institucional/${p.slug}`)" class="hover:text-white hover:underline">{{ p.titulo }}</Link></li>
                        <li><Link :href="u('/plantel-docente')" class="hover:text-white hover:underline">Autoridades y docentes</Link></li>
                        <li><Link :href="u('/malla-curricular')" class="hover:text-white hover:underline">Malla curricular</Link></li>
                    </ul>
                </div>
                <div>
                    <h3 class="mb-4 text-sm font-bold uppercase tracking-wider text-white">Accesos rápidos</h3>
                    <ul class="space-y-2 text-sm">
                        <li><Link :href="u('/biblioteca')" class="hover:text-white hover:underline">Biblioteca digital</Link></li>
                        <li><Link :href="u('/revista-innovar')" class="hover:text-white hover:underline">Revista INNOVAR</Link></li>
                        <li><Link :href="u('/innova')" class="hover:text-white hover:underline">Empresa Junior INNOVA</Link></li>
                        <li><Link :href="u('/graduados')" class="hover:text-white hover:underline">Programa Alumni</Link></li>
                        <li><Link :href="u('/calendario-academico')" class="hover:text-white hover:underline">Calendario académico</Link></li>
                        <li><Link :href="u('/contacto')" class="hover:text-white hover:underline">Formulario de contacto</Link></li>
                    </ul>
                </div>
            </div>
            <div class="border-t border-white/10">
                <div class="mx-auto flex max-w-7xl flex-col items-start justify-between gap-2 px-4 py-4 text-xs text-white/70 sm:flex-row sm:items-center sm:px-6">
                    <p>© {{ anio }} Carrera de {{ sitio.carrera }} · FCEE · UAGRM</p>
                    <ContadorVisitas />
                </div>
            </div>
        </footer>

        <Toaster />
    </div>
</template>
