import vue from '@vitejs/plugin-vue';
import laravel from 'laravel-vite-plugin';
import tailwindcss from '@tailwindcss/vite';
import { defineConfig, loadEnv } from 'vite';

export default defineConfig(({ mode }) => {
    // Load env file based on mode to make it available to the config
    const env = loadEnv(mode, process.cwd(), '');

    return {
    server: {
        // Listen on all interfaces but do NOT browse to 0.0.0.0
        host: '0.0.0.0',
        // Pin the port so Laravel's @vite (default 5173) matches
        port: Number(env.VITE_DEV_PORT || 5173),
        strictPort: true,
        // Helpful when accessing from another device or container
        cors: true,
        // Public origin used in the generated public/hot file and client imports
                origin:
            env.VITE_DEV_SERVER_URL ||
            `http://${env.VITE_HMR_HOST || 'localhost'}:${env.VITE_HMR_CLIENT_PORT || env.VITE_DEV_PORT || 5173}`,
        hmr: {
            host: env.VITE_HMR_HOST || 'localhost',
            clientPort: Number(env.VITE_HMR_CLIENT_PORT || env.VITE_DEV_PORT || 5173),
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
    };
});
