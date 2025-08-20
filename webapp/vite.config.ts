import vue from '@vitejs/plugin-vue';
import laravel from 'laravel-vite-plugin';
import tailwindcss from '@tailwindcss/vite';
import { defineConfig } from 'vite';

export default defineConfig({
    server: {
        host: '0.0.0.0',
        hmr: {
            host: process.env.VITE_HMR_HOST || 'localhost',
        },
        watch: {
            // Reduce watcher load to avoid ENOSPC on Linux
            ignored: ['**/vendor/**', '**/node_modules/**', '**/storage/**', '**/bootstrap/cache/**', '**/.git/**'],
        },
    },
    plugins: [
        laravel({
            input: ['resources/js/app.ts'],
            ssr: 'resources/js/ssr.ts',
            // Limit refresh paths to app sources to avoid vendor
            refresh: ['resources/views/**', 'resources/js/**', 'routes/**', 'app/**', 'config/**'],
        }),
        tailwindcss(),
        vue({
            template: {
                transformAssetUrls: {
                    base: null,
                    includeAbsolute: false,
                },
            },
        }),
    ],
});
