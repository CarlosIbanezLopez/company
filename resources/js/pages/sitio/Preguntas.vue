<script setup lang="ts">
import { Head, Link } from '@inertiajs/vue3';
import { Plus } from '@lucide/vue';
import Encabezado from '@/components/sitio/Encabezado.vue';
import { u } from '@/lib/sitio';

defineProps<{ grupos: Record<string, { id: number; pregunta: string; respuesta: string }[]> }>();
</script>

<template>
    <Head title="Preguntas frecuentes" />
    <Encabezado titulo="Preguntas frecuentes" descripcion="Respuestas a las consultas más comunes de los estudiantes." :migas="[{ titulo: 'Académica' }, { titulo: 'Preguntas frecuentes' }]" />

    <div class="mx-auto max-w-4xl px-4 py-12 sm:px-6">
        <section v-for="(preguntas, categoria) in grupos" :key="categoria" class="mb-10">
            <h2 class="mb-4 border-l-4 border-marca pl-3 text-xl font-bold">{{ categoria }}</h2>
            <div class="divide-y border bg-card">
                <details v-for="p in preguntas" :key="p.id" class="group">
                    <summary class="flex cursor-pointer list-none items-center justify-between gap-4 px-5 py-4 font-semibold hover:bg-accent">
                        {{ p.pregunta }}
                        <Plus class="size-4 shrink-0 transition-transform group-open:rotate-45" />
                    </summary>
                    <p class="border-t bg-muted/40 px-5 py-4 text-sm leading-relaxed">{{ p.respuesta }}</p>
                </details>
            </div>
        </section>
        <div class="border bg-card p-6 text-center">
            <p class="font-semibold">¿No encontró lo que buscaba?</p>
            <Link :href="u('/contacto')" class="mt-3 inline-flex bg-primary px-5 py-2.5 text-sm font-semibold text-primary-foreground hover:opacity-90">Envíenos su consulta</Link>
        </div>
    </div>
</template>
