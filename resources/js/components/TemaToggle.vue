<script setup lang="ts">
import { Moon, Sun } from '@lucide/vue';
import { useAppearance } from '@/composables/useAppearance';

const { resolvedAppearance, updateAppearance } = useAppearance();

function alternar() {
    updateAppearance(resolvedAppearance.value === 'dark' ? 'light' : 'dark');
}
</script>

<template>
    <button
        type="button"
        class="inline-flex size-9 items-center justify-center border border-current/25 transition-colors hover:bg-white/10"
        :title="resolvedAppearance === 'dark' ? 'Cambiar a tema claro' : 'Cambiar a tema oscuro'"
        :aria-label="resolvedAppearance === 'dark' ? 'Cambiar a tema claro' : 'Cambiar a tema oscuro'"
        @click="alternar"
    >
        <Sun v-if="resolvedAppearance === 'dark'" class="size-4" />
        <Moon v-else class="size-4" />
    </button>
</template>
