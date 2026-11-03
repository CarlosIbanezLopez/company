<script setup lang="ts">
defineProps<{ redes: Record<string, string> }>();

const etiquetas: Record<string, [string, string]> = {
    facebook: ['f', 'Facebook'],
    instagram: ['IG', 'Instagram'],
    youtube: ['YT', 'YouTube'],
    tiktok: ['TT', 'TikTok'],
    linkedin: ['in', 'LinkedIn'],
};
</script>

<template>
    <div class="flex flex-wrap gap-2">
        <a
            v-for="(url, red) in redes"
            :key="red"
            :href="url"
            target="_blank"
            rel="noopener"
            :title="etiquetas[red]?.[1] ?? red"
            :aria-label="etiquetas[red]?.[1] ?? red"
            class="inline-flex size-9 items-center justify-center border border-current/30 text-xs font-bold transition-colors hover:border-marca hover:bg-marca hover:text-white"
        >
            {{ etiquetas[red]?.[0] ?? red.slice(0, 2) }}
        </a>
    </div>
</template>
