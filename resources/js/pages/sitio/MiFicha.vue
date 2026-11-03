<script setup lang="ts">
import { Head, Link, useForm } from '@inertiajs/vue3';
import { LogOut, Save } from '@lucide/vue';
import Campo from '@/components/sitio/Campo.vue';
import CamposGraduado from '@/components/sitio/CamposGraduado.vue';
import Encabezado from '@/components/sitio/Encabezado.vue';
import { fecha, u } from '@/lib/sitio';

type Graduado = Record<string, string | number | null> & { updated_at?: string };

const props = defineProps<{
    graduado: Graduado | null;
    situaciones: Record<string, string>;
    modalidades: Record<string, string>;
}>();

const g = props.graduado ?? {};
const form = useForm({
    nombre: g.nombre ?? '', ci: g.ci ?? '', registro: g.registro ?? '', anio_graduacion: g.anio_graduacion ?? '', modalidad_titulacion: g.modalidad_titulacion ?? '',
    situacion_laboral: g.situacion_laboral ?? '', empresa: g.empresa ?? '', cargo: g.cargo ?? '', ciudad: g.ciudad ?? '', telefono: g.telefono ?? '',
    linkedin: g.linkedin ?? '', correo: g.correo ?? '',
});

function guardar() {
    form.put(u('/graduados/mi-ficha'), { preserveScroll: true });
}
</script>

<template>
    <Head title="Mi ficha de graduado" />
    <Encabezado titulo="Mi ficha de graduado" descripcion="Mantenga actualizada su información para el seguimiento a graduados de la Carrera." :migas="[{ titulo: 'Alumni', url: u('/graduados') }, { titulo: 'Mi ficha' }]" />

    <div class="mx-auto max-w-4xl px-4 py-12 sm:px-6">
        <p v-if="graduado?.updated_at" class="mb-4 text-sm text-muted-foreground">Última actualización: {{ fecha(graduado.updated_at as string) }}</p>
        <form class="grid gap-5 border bg-card p-6 md:grid-cols-2" @submit.prevent="guardar">
            <CamposGraduado :form="form" :errores="form.errors" :situaciones="situaciones" :modalidades="modalidades" />
            <Campo etiqueta="Correo de contacto" requerido :error="form.errors.correo" class="md:col-span-2"><input v-model="form.correo" type="email" class="campo" required /></Campo>
            <div class="flex flex-wrap items-center justify-between gap-4 md:col-span-2">
                <button class="boton" :disabled="form.processing"><Save class="size-4" /> Guardar cambios</button>
                <Link :href="u('/logout')" method="post" as="button" class="boton-borde"><LogOut class="size-4" /> Cerrar sesión</Link>
            </div>
        </form>
    </div>
</template>
