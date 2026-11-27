import path from 'node:path';
import tailwindcss from '@tailwindcss/vite';
import react from '@vitejs/plugin-react';
import laravel from 'laravel-vite-plugin';
import { defineConfig } from 'vite';

/*
 * Capa 1 - Presentación: SPA React compilada con Vite.
 * El resultado (public/build) lo sirve Laravel desde resources/views/app.blade.php.
 */
export default defineConfig({
    plugins: [
        laravel({
            input: ['resources/css/app.css', 'resources/js/main.tsx'],
            refresh: true,
        }),
        react(),
        tailwindcss(),
    ],
    resolve: {
        alias: {
            '@': path.resolve(import.meta.dirname, 'resources/js'),
        },
    },
    build: {
        chunkSizeWarningLimit: 900,
    },
    server: {
        watch: {
            ignored: ['**/vendor/**', '**/storage/**', '**/.claude/**'],
        },
    },
});
