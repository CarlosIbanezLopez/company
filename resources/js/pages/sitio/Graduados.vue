<script setup lang="ts">
import { Head, Link, useForm, usePage } from '@inertiajs/vue3';
import { GraduationCap, UserPlus } from '@lucide/vue';
import { computed, ref } from 'vue';
import Antispam from '@/components/sitio/Antispam.vue';
import Campo from '@/components/sitio/Campo.vue';
import CamposGraduado from '@/components/sitio/CamposGraduado.vue';
import Encabezado from '@/components/sitio/Encabezado.vue';
import { numero, u } from '@/lib/sitio';

const props = defineProps<{
    paginas: { slug: string; titulo: string; resumen: string | null; html: string }[];
    situaciones: Record<string, string>;
    modalidades: Record<string, string>;
    tieneFicha: boolean;
    antispam: { token: string };
    total: number;
}>();

const page = usePage();
const usuario = computed(() => page.props.auth?.user ?? null);
const pestana = ref(props.paginas[0]?.slug ?? '');

const form = useForm({
    nombre: '', ci: '', registro: '', anio_graduacion: '', modalidad_titulacion: '', situacion_laboral: '', empresa: '', cargo: '', ciudad: '',
    telefono: '', linkedin: '', email: '', password: '', password_confirmation: '', sitio_web: '', antispam_token: props.antispam.token,
});

function registrar() {
    form.post(u('/graduados'), { preserveScroll: true, onFinish: () => form.reset('password', 'password_confirmation') });
}
</script>

<template>
    <Head title="Graduados - Alumni" />
    <Encabezado titulo="Graduados – Programa Alumni" descripcion="Información para graduados, oportunidades de vinculación y seguimiento a titulados." :migas="[{ titulo: 'Alumni' }]">
        <p class="mt-6 inline-flex items-center gap-2 border border-current/30 px-3 py-1.5 text-sm"><GraduationCap class="size-4" /> {{ numero(total) }} graduados registrados</p>
    </Encabezado>

    <div class="mx-auto max-w-7xl px-4 py-12 sm:px-6">
        <div v-if="paginas.length" class="mb-12 border bg-card">
            <div class="flex flex-wrap border-b">
                <button v-for="p in paginas" :key="p.slug" type="button" class="border-b-4 px-5 py-3 text-sm font-semibold" :class="pestana === p.slug ? 'border-marca text-primary' : 'border-transparent text-muted-foreground hover:text-foreground'" @click="pestana = p.slug">{{ p.titulo }}</button>
            </div>
            <template v-for="p in paginas" :key="p.slug">
                <div v-if="pestana === p.slug" class="contenido p-6" v-html="p.html" />
            </template>
        </div>

        <!-- Registro de graduados (RF-11) -->
        <section class="grid gap-8 lg:grid-cols-3">
            <div>
                <p class="text-xs font-semibold uppercase tracking-[0.18em] text-marca">Seguimiento a titulados</p>
                <h2 class="mt-1 text-2xl font-bold">Registre sus datos</h2>
                <p class="mt-3 text-sm text-muted-foreground">Cree su cuenta de graduado para registrar su año de graduación, situación laboral y datos de contacto. Podrá actualizarlos cuando lo necesite ingresando con su correo y contraseña.</p>
            </div>
            <div class="lg:col-span-2">
                <div v-if="usuario" class="border-l-4 border-primary bg-card p-6">
                    <p class="font-semibold">Ya inició sesión como {{ usuario.name }}.</p>
                    <Link :href="u('/graduados/mi-ficha')" class="boton mt-4">{{ tieneFicha ? 'Actualizar mis datos' : 'Completar mi ficha' }}</Link>
                </div>
                <form v-else class="grid gap-5 border bg-card p-6 md:grid-cols-2" @submit.prevent="registrar">
                    <CamposGraduado :form="form" :errores="form.errors" :situaciones="situaciones" :modalidades="modalidades" />
                    <div class="border-t pt-5 md:col-span-2"><p class="text-sm font-semibold">Datos de acceso</p></div>
                    <Campo etiqueta="Correo electrónico" requerido :error="form.errors.email" class="md:col-span-2"><input v-model="form.email" type="email" class="campo" required autocomplete="email" /></Campo>
                    <Campo etiqueta="Contraseña" requerido :error="form.errors.password"><input v-model="form.password" type="password" class="campo" required minlength="8" autocomplete="new-password" /></Campo>
                    <Campo etiqueta="Confirmar contraseña" requerido><input v-model="form.password_confirmation" type="password" class="campo" required autocomplete="new-password" /></Campo>
                    <Antispam v-model:trampa="form.sitio_web" :error="(form.errors as Record<string, string>).antispam" class="md:col-span-2" />
                    <div class="flex flex-wrap items-center gap-4 md:col-span-2">
                        <button class="boton" :disabled="form.processing"><UserPlus class="size-4" /> Registrarme como graduado</button>
                        <Link :href="u('/login')" class="text-sm text-primary hover:underline">¿Ya tiene cuenta? Ingrese aquí</Link>
                    </div>
                </form>
            </div>
        </section>
    </div>
</template>
