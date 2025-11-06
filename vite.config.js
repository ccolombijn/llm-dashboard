import { defineConfig } from 'vite';
import laravel from 'laravel-vite-plugin';
import path from 'path';

export default defineConfig({
    plugins: [
        laravel({
            input: ['resources/css/app.css', 'resources/js/app.js'],
            refresh: true,

        }),
    ],
    server: {
        host: '0.0.0.0',
        port: 5173,
        hmr: {
            host: 'llm-dashboard.ddev.site',
        },
    },
    resolve: {
        alias: {
            '~resources': '/resources/',
            '~svg': path.resolve(__dirname, 'resources/svg'),
        }
    },
    build: {
        // Empty the outDir to avoid conflicts with previous builds
        emptyOutDir: true,
    },
});
