<script setup lang="ts">
/**
 * Campos de protección contra envíos automatizados: campo trampa oculto,
 * marca de tiempo cifrada y (opcional) una pregunta de verificación.
 */
defineProps<{
    pregunta?: string;
    error?: string;
    errorRespuesta?: string;
}>();

const respuesta = defineModel<string>('respuesta', { default: '' });
const trampa = defineModel<string>('trampa', { default: '' });
</script>

<template>
    <div>
        <div class="absolute -left-[9999px] h-0 overflow-hidden" aria-hidden="true">
            <label>No completar este campo <input v-model="trampa" type="text" name="sitio_web" tabindex="-1" autocomplete="off" /></label>
        </div>
        <div v-if="pregunta" class="max-w-xs">
            <label for="antispam_respuesta" class="etiqueta">Verificación: {{ pregunta }} <span class="text-marca">*</span></label>
            <input id="antispam_respuesta" v-model="respuesta" type="number" inputmode="numeric" required class="campo" :aria-invalid="!!errorRespuesta" />
            <p v-if="errorRespuesta" class="mt-1 text-xs font-medium text-destructive">{{ errorRespuesta }}</p>
        </div>
        <p v-if="error" class="mt-2 border-l-4 border-destructive bg-destructive/10 px-3 py-2 text-sm text-destructive">{{ error }}</p>
    </div>
</template>
