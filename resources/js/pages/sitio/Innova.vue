<script setup lang="ts">
import { Head, useForm } from '@inertiajs/vue3';
import { Briefcase, Send, UserPlus } from '@lucide/vue';
import { ref } from 'vue';
import Antispam from '@/components/sitio/Antispam.vue';
import Campo from '@/components/sitio/Campo.vue';
import Imagen from '@/components/sitio/Imagen.vue';
import Encabezado from '@/components/sitio/Encabezado.vue';
import Seccion from '@/components/sitio/Seccion.vue';
import { fecha, u } from '@/lib/sitio';

const props = defineProps<{
    descripcion: { titulo: string; html: string } | null;
    servicios: { id: number; nombre: string; descripcion: string | null }[];
    integrantes: { id: number; nombre: string; cargo: string | null; semestre: string | null; foto_url: string | null }[];
    proyectos: { id: number; titulo: string; cliente: string | null; descripcion: string | null; fecha: string | null; imagen_url: string | null }[];
    antispam: { token: string };
}>();

const pestana = ref<'solicitud' | 'postulacion'>('solicitud');

const solicitud = useForm({ empresa: '', contacto: '', correo: '', telefono: '', innova_servicio_id: '', mensaje: '', sitio_web: '', antispam_token: props.antispam.token });
const postulacion = useForm({ nombre: '', registro: '', correo: '', telefono: '', semestre: '', motivacion: '', sitio_web: '', antispam_token: props.antispam.token });

function enviarSolicitud() {
    solicitud.post(u('/innova/solicitudes'), { preserveScroll: true, onSuccess: () => solicitud.reset('empresa', 'contacto', 'correo', 'telefono', 'innova_servicio_id', 'mensaje') });
}

function enviarPostulacion() {
    postulacion.post(u('/innova/postulaciones'), { preserveScroll: true, onSuccess: () => postulacion.reset('nombre', 'registro', 'correo', 'telefono', 'semestre', 'motivacion') });
}
</script>

<template>
    <Head title="Empresa Junior INNOVA" />
    <Encabezado titulo="Empresa Junior INNOVA" descripcion="Consultoría empresarial realizada por estudiantes de la Carrera de Administración de Empresas." :migas="[{ titulo: 'Vinculación' }, { titulo: 'INNOVA' }]" />

    <Seccion v-if="descripcion" subtitulo="Quiénes somos" :titulo="descripcion.titulo">
        <div class="contenido max-w-4xl text-lg" v-html="descripcion.html" />
    </Seccion>

    <div class="border-y bg-muted/40">
        <Seccion subtitulo="Lo que ofrecemos" titulo="Servicios">
            <div class="grid gap-px border bg-border sm:grid-cols-2 lg:grid-cols-4">
                <div v-for="(s, i) in servicios" :key="s.id" class="bg-card p-6">
                    <span class="text-sm font-black text-marca">{{ String(i + 1).padStart(2, '0') }}</span>
                    <h3 class="mt-2 text-lg font-bold">{{ s.nombre }}</h3>
                    <p class="mt-2 text-sm text-muted-foreground">{{ s.descripcion }}</p>
                </div>
            </div>
        </Seccion>
    </div>

    <Seccion v-if="integrantes.length" subtitulo="Equipo" titulo="Integrantes">
        <div class="grid grid-cols-2 gap-6 md:grid-cols-3 lg:grid-cols-5">
            <div v-for="m in integrantes" :key="m.id" class="border bg-card">
                <div class="aspect-square"><Imagen :src="m.foto_url" :alt="m.nombre" /></div>
                <div class="border-t-4 border-primary p-3">
                    <p class="text-sm font-bold">{{ m.nombre }}</p>
                    <p class="text-xs text-marca">{{ m.cargo }}</p>
                    <p class="text-xs text-muted-foreground">{{ m.semestre }}</p>
                </div>
            </div>
        </div>
    </Seccion>

    <Seccion v-if="proyectos.length" subtitulo="Experiencia" titulo="Proyectos desarrollados">
        <div class="grid gap-6 md:grid-cols-3">
            <article v-for="p in proyectos" :key="p.id" class="border bg-card">
                <div class="aspect-video"><Imagen :src="p.imagen_url" :alt="p.titulo" /></div>
                <div class="p-5">
                    <p class="text-xs text-muted-foreground">{{ p.cliente }} · {{ fecha(p.fecha) }}</p>
                    <h3 class="mt-1 font-bold">{{ p.titulo }}</h3>
                    <p class="mt-2 text-sm text-muted-foreground">{{ p.descripcion }}</p>
                </div>
            </article>
        </div>
    </Seccion>

    <!-- Formularios de solicitud de servicio y postulación -->
    <div class="border-t bg-muted/40">
        <Seccion subtitulo="Formularios" titulo="Trabaje con INNOVA">
            <div class="mb-0 flex border-b">
                <button type="button" class="flex items-center gap-2 border-b-4 px-5 py-3 text-sm font-semibold" :class="pestana === 'solicitud' ? 'border-marca text-primary' : 'border-transparent text-muted-foreground'" @click="pestana = 'solicitud'"><Briefcase class="size-4" /> Soy empresa: solicitar servicio</button>
                <button type="button" class="flex items-center gap-2 border-b-4 px-5 py-3 text-sm font-semibold" :class="pestana === 'postulacion' ? 'border-marca text-primary' : 'border-transparent text-muted-foreground'" @click="pestana = 'postulacion'"><UserPlus class="size-4" /> Soy estudiante: postular</button>
            </div>

            <form v-if="pestana === 'solicitud'" class="grid gap-5 border border-t-0 bg-card p-6 md:grid-cols-2" @submit.prevent="enviarSolicitud">
                <Campo etiqueta="Empresa" requerido :error="solicitud.errors.empresa"><input v-model="solicitud.empresa" class="campo" required maxlength="150" /></Campo>
                <Campo etiqueta="Persona de contacto" requerido :error="solicitud.errors.contacto"><input v-model="solicitud.contacto" class="campo" required maxlength="150" /></Campo>
                <Campo etiqueta="Correo electrónico" requerido :error="solicitud.errors.correo"><input v-model="solicitud.correo" type="email" class="campo" required /></Campo>
                <Campo etiqueta="Teléfono" :error="solicitud.errors.telefono"><input v-model="solicitud.telefono" class="campo" maxlength="30" /></Campo>
                <Campo etiqueta="Servicio de interés" :error="solicitud.errors.innova_servicio_id" class="md:col-span-2">
                    <select v-model="solicitud.innova_servicio_id" class="campo">
                        <option value="">Otro / no estoy seguro</option>
                        <option v-for="s in servicios" :key="s.id" :value="s.id">{{ s.nombre }}</option>
                    </select>
                </Campo>
                <Campo etiqueta="Describa su necesidad" requerido :error="solicitud.errors.mensaje" class="md:col-span-2"><textarea v-model="solicitud.mensaje" class="campo" required minlength="10" maxlength="3000" /></Campo>
                <Antispam v-model:trampa="solicitud.sitio_web" :error="(solicitud.errors as Record<string, string>).antispam" class="md:col-span-2" />
                <div class="md:col-span-2"><button class="boton" :disabled="solicitud.processing"><Send class="size-4" /> Enviar solicitud</button></div>
            </form>

            <form v-else class="grid gap-5 border border-t-0 bg-card p-6 md:grid-cols-2" @submit.prevent="enviarPostulacion">
                <Campo etiqueta="Nombre completo" requerido :error="postulacion.errors.nombre"><input v-model="postulacion.nombre" class="campo" required maxlength="150" /></Campo>
                <Campo etiqueta="Registro universitario" requerido :error="postulacion.errors.registro"><input v-model="postulacion.registro" class="campo" required maxlength="20" /></Campo>
                <Campo etiqueta="Correo electrónico" requerido :error="postulacion.errors.correo"><input v-model="postulacion.correo" type="email" class="campo" required /></Campo>
                <Campo etiqueta="Teléfono" :error="postulacion.errors.telefono"><input v-model="postulacion.telefono" class="campo" maxlength="30" /></Campo>
                <Campo etiqueta="Semestre que cursa" :error="postulacion.errors.semestre"><input v-model="postulacion.semestre" class="campo" placeholder="Ej. 6° semestre" maxlength="30" /></Campo>
                <Campo etiqueta="¿Por qué desea integrar INNOVA?" requerido :error="postulacion.errors.motivacion" class="md:col-span-2"><textarea v-model="postulacion.motivacion" class="campo" required minlength="20" maxlength="3000" /></Campo>
                <Antispam v-model:trampa="postulacion.sitio_web" :error="(postulacion.errors as Record<string, string>).antispam" class="md:col-span-2" />
                <div class="md:col-span-2"><button class="boton" :disabled="postulacion.processing"><Send class="size-4" /> Enviar postulación</button></div>
            </form>
        </Seccion>
    </div>
</template>
