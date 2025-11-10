import { defineConfig } from 'vite';
import laravel from 'laravel-vite-plugin';
import tailwindcss from '@tailwindcss/vite';
import path from 'path';
import { viteStaticCopy } from 'vite-plugin-static-copy';

export default defineConfig({
    plugins: [
        tailwindcss(),
        laravel({
            input: ['resources/css/app.css', 'resources/js/app.js'],
            refresh: true,

        }),
        viteStaticCopy({
            targets: [
                {
                    src: 'resources/svg/*', // 3. Your source directory
                    dest: 'svg'           // 4. Your destination directory (relative to public/build)
                }
            ]
        })
    ],
    resolve: {
        alias: {
            '@': path.resolve(__dirname, 'resources'),
        }
    },
    build: {
        emptyOutDir: true,
    },
});
