<script setup lang="ts">
defineProps<{
    etiqueta: string;
    error?: string;
    para?: string;
    requerido?: boolean;
    ayuda?: string;
}>();
</script>

<template>
    <div>
        <label :for="para" class="etiqueta">{{ etiqueta }} <span v-if="requerido" class="text-marca">*</span></label>
        <slot />
        <p v-if="ayuda && !error" class="mt-1 text-xs text-muted-foreground">{{ ayuda }}</p>
        <p v-if="error" class="mt-1 text-xs font-medium text-destructive">{{ error }}</p>
    </div>
</template>
