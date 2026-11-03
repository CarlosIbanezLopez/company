<script setup lang="ts">
import { ImageOff } from '@lucide/vue';
import { ref } from 'vue';

defineProps<{
    src?: string | null;
    alt: string;
    clase?: string;
}>();

const fallo = ref(false);
</script>

<template>
    <img v-if="src && !fallo" :src="src" :alt="alt" loading="lazy" :class="['h-full w-full object-cover', clase]" @error="fallo = true" />
    <div v-else :class="['flex h-full w-full items-center justify-center bg-muted text-muted-foreground', clase]" role="img" :aria-label="alt">
        <ImageOff class="size-8 opacity-50" />
    </div>
</template>
