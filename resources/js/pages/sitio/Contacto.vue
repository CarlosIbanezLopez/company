<script setup lang="ts">
import { Head, useForm, usePage } from '@inertiajs/vue3';
import { Clock, Mail, MapPin, MessageCircle, Phone, Send } from '@lucide/vue';
import { computed } from 'vue';
import Antispam from '@/components/sitio/Antispam.vue';
import Campo from '@/components/sitio/Campo.vue';
import RedesSociales from '@/components/sitio/RedesSociales.vue';
import Encabezado from '@/components/sitio/Encabezado.vue';
import { u } from '@/lib/sitio';
import type { DatosSitio } from '@/types';

const props = defineProps<{
    categorias: { id: number; nombre: string }[];
    mapa: string | null;
    antispam: { token: string; pregunta: string };
}>();

const page = usePage();
const sitio = computed(() => page.props.sitio as DatosSitio);

const form = useForm({
    nombre: '', correo: '', telefono: '', categoria_consulta_id: '', mensaje: '',
    sitio_web: '', antispam_token: props.antispam.token, antispam_respuesta: '',
});

function enviar() {
    form.post(u('/contacto'), {
        preserveScroll: true,
        onSuccess: () => form.reset(),
        onError: () => form.reset('antispam_respuesta'),
    });
}
</script>

<template>
    <Head title="Contacto" />
    <Encabezado titulo="Contacto" descripcion="Ubicación, datos de contacto, redes sociales y formulario de consultas." :migas="[{ titulo: 'Contacto' }]" />

    <div class="mx-auto grid max-w-7xl gap-10 px-4 py-12 sm:px-6 lg:grid-cols-5">
        <aside class="space-y-6 lg:col-span-2">
            <div class="divide-y border bg-card">
                <div v-if="sitio.contacto.direccion" class="flex gap-3 p-5"><MapPin class="size-5 shrink-0 text-marca" /><div><p class="text-xs font-bold uppercase text-muted-foreground">Dirección</p><p class="mt-1">{{ sitio.contacto.direccion }}</p></div></div>
                <div v-if="sitio.contacto.telefonos" class="flex gap-3 p-5"><Phone class="size-5 shrink-0 text-marca" /><div><p class="text-xs font-bold uppercase text-muted-foreground">Teléfonos</p><p class="mt-1">{{ sitio.contacto.telefonos }}</p></div></div>
                <div v-if="sitio.contacto.correo" class="flex gap-3 p-5"><Mail class="size-5 shrink-0 text-marca" /><div><p class="text-xs font-bold uppercase text-muted-foreground">Correo</p><a :href="`mailto:${sitio.contacto.correo}`" class="mt-1 block text-primary hover:underline">{{ sitio.contacto.correo }}</a></div></div>
                <div v-if="sitio.contacto.horario" class="flex gap-3 p-5"><Clock class="size-5 shrink-0 text-marca" /><div><p class="text-xs font-bold uppercase text-muted-foreground">Horario de atención</p><p class="mt-1">{{ sitio.contacto.horario }}</p></div></div>
                <div v-if="sitio.contacto.whatsapp" class="flex gap-3 p-5"><MessageCircle class="size-5 shrink-0 text-marca" /><div><p class="text-xs font-bold uppercase text-muted-foreground">WhatsApp</p><a :href="`https://wa.me/${sitio.contacto.whatsapp}`" target="_blank" rel="noopener" class="mt-1 block text-primary hover:underline">Escribir por WhatsApp</a></div></div>
                <div v-if="Object.keys(sitio.redes).length" class="p-5"><p class="mb-3 text-xs font-bold uppercase text-muted-foreground">Redes sociales</p><RedesSociales :redes="sitio.redes" /></div>
            </div>
            <div v-if="mapa" class="aspect-[4/3] border">
                <iframe :src="mapa" title="Ubicación de la Carrera en el mapa" class="h-full w-full" loading="lazy" referrerpolicy="no-referrer-when-downgrade" />
            </div>
        </aside>

        <section class="lg:col-span-3">
            <h2 class="text-2xl font-bold">Envíenos su consulta</h2>
            <p class="mt-1 text-sm text-muted-foreground">Responderemos a su correo electrónico. Los campos con * son obligatorios.</p>
            <form class="mt-6 grid gap-5 border bg-card p-6 md:grid-cols-2" novalidate @submit.prevent="enviar">
                <Campo etiqueta="Nombre completo" para="nombre" requerido :error="form.errors.nombre"><input id="nombre" v-model="form.nombre" class="campo" required minlength="3" maxlength="150" :aria-invalid="!!form.errors.nombre" /></Campo>
                <Campo etiqueta="Correo electrónico" para="correo" requerido :error="form.errors.correo"><input id="correo" v-model="form.correo" type="email" class="campo" required :aria-invalid="!!form.errors.correo" /></Campo>
                <Campo etiqueta="Teléfono" para="telefono" :error="form.errors.telefono"><input id="telefono" v-model="form.telefono" class="campo" maxlength="30" :aria-invalid="!!form.errors.telefono" /></Campo>
                <Campo etiqueta="Categoría de la consulta" para="categoria" requerido :error="form.errors.categoria_consulta_id">
                    <select id="categoria" v-model="form.categoria_consulta_id" class="campo" required :aria-invalid="!!form.errors.categoria_consulta_id">
                        <option value="">Seleccione...</option>
                        <option v-for="c in categorias" :key="c.id" :value="c.id">{{ c.nombre }}</option>
                    </select>
                </Campo>
                <Campo etiqueta="Mensaje" para="mensaje" requerido :error="form.errors.mensaje" class="md:col-span-2"><textarea id="mensaje" v-model="form.mensaje" class="campo" rows="6" required minlength="10" maxlength="3000" :aria-invalid="!!form.errors.mensaje" /></Campo>
                <Antispam
                    v-model:trampa="form.sitio_web"
                    v-model:respuesta="form.antispam_respuesta"
                    :pregunta="antispam.pregunta"
                    :error="(form.errors as Record<string, string>).antispam"
                    :error-respuesta="(form.errors as Record<string, string>).antispam_respuesta"
                    class="md:col-span-2"
                />
                <div class="md:col-span-2"><button class="boton" :disabled="form.processing"><Send class="size-4" /> Enviar consulta</button></div>
            </form>
        </section>
    </div>
</template>
