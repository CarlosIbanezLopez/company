<script setup lang="ts">
import { Link } from '@inertiajs/vue3';

defineProps<{
    links: { url: string | null; label: string; active: boolean }[];
}>();

function texto(label: string) {
    return label.replace('&laquo; Previous', '‹ Anterior').replace('Next &raquo;', 'Siguiente ›').replace('&laquo; Anterior', '‹ Anterior').replace('Siguiente &raquo;', 'Siguiente ›').replace('&laquo;', '‹').replace('&raquo;', '›');
}
</script>

<template>
    <nav v-if="links.length > 3" class="flex flex-wrap items-center gap-1" aria-label="Paginación">
        <template v-for="(link, i) in links" :key="i">
            <Link
                v-if="link.url"
                :href="link.url"
                preserve-scroll
                class="inline-flex h-9 min-w-9 items-center justify-center border px-3 text-sm transition-colors"
                :class="link.active ? 'border-primary bg-primary text-primary-foreground' : 'bg-card hover:border-primary hover:text-primary'"
            >
                {{ texto(link.label) }}
            </Link>
            <span v-else class="inline-flex h-9 min-w-9 items-center justify-center border px-3 text-sm text-muted-foreground opacity-60">{{ texto(link.label) }}</span>
        </template>
    </nav>
</template>
