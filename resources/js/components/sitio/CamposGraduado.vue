<script setup lang="ts">
import Campo from '@/components/sitio/Campo.vue';

/** Campos compartidos del registro y de la ficha del graduado (RF-11). */
defineProps<{
    form: Record<string, any>;
    errores: Record<string, string>;
    situaciones: Record<string, string>;
    modalidades: Record<string, string>;
}>();

const anioActual = new Date().getFullYear();
</script>

<template>
    <Campo etiqueta="Nombre completo" requerido :error="errores.nombre"><input v-model="form.nombre" class="campo" required maxlength="150" /></Campo>
    <Campo etiqueta="Carnet de identidad" requerido :error="errores.ci"><input v-model="form.ci" class="campo" required maxlength="20" /></Campo>
    <Campo etiqueta="Registro universitario" :error="errores.registro"><input v-model="form.registro" class="campo" maxlength="20" /></Campo>
    <Campo etiqueta="Año de graduación" requerido :error="errores.anio_graduacion"><input v-model="form.anio_graduacion" type="number" min="1970" :max="anioActual + 1" class="campo" required /></Campo>
    <Campo etiqueta="Modalidad de titulación" :error="errores.modalidad_titulacion">
        <select v-model="form.modalidad_titulacion" class="campo">
            <option value="">Seleccione...</option>
            <option v-for="(n, c) in modalidades" :key="c" :value="c">{{ n }}</option>
        </select>
    </Campo>
    <Campo etiqueta="Situación laboral actual" requerido :error="errores.situacion_laboral">
        <select v-model="form.situacion_laboral" class="campo" required>
            <option value="">Seleccione...</option>
            <option v-for="(n, c) in situaciones" :key="c" :value="c">{{ n }}</option>
        </select>
    </Campo>
    <Campo etiqueta="Empresa / institución donde trabaja" :error="errores.empresa"><input v-model="form.empresa" class="campo" maxlength="150" /></Campo>
    <Campo etiqueta="Cargo" :error="errores.cargo"><input v-model="form.cargo" class="campo" maxlength="120" /></Campo>
    <Campo etiqueta="Ciudad" :error="errores.ciudad"><input v-model="form.ciudad" class="campo" maxlength="80" /></Campo>
    <Campo etiqueta="Teléfono" :error="errores.telefono"><input v-model="form.telefono" class="campo" maxlength="30" /></Campo>
    <Campo etiqueta="Perfil de LinkedIn (URL)" :error="errores.linkedin" class="md:col-span-2"><input v-model="form.linkedin" type="url" class="campo" placeholder="https://www.linkedin.com/in/..." /></Campo>
</template>
