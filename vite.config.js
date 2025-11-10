import { defineConfig } from 'vite';
import laravel from 'laravel-vite-plugin';
import tailwindcss from '@tailwindcss/vite';
import path from 'path';
import fs from 'fs';
import { viteStaticCopy } from 'vite-plugin-static-copy';

// Determine host name from APP_URL (used by DDEV) or fallback to DDEV_HOSTNAME/localhost
const appHost = process.env.APP_URL ? new URL(process.env.APP_URL).hostname : (process.env.DDEV_HOSTNAME || 'localhost');

// If environment variables point to certificate files, use them; otherwise enable https:true
const httpsConfig = (process.env.VITE_HTTPS_KEY && process.env.VITE_HTTPS_CERT)
    ? { key: fs.readFileSync(process.env.VITE_HTTPS_KEY), cert: fs.readFileSync(process.env.VITE_HTTPS_CERT) }
    : true;

export default defineConfig({
    server: {
        host: '0.0.0.0',
        port: 5173,
        https: httpsConfig,
        // Allow cross-origin requests from the DDEV app host (same host, different port)
        // This ensures the dev server returns Access-Control-Allow-Origin: * so the
        // browser won't block assets loaded from :5173 when the page is served over HTTPS.
        cors: true,
        hmr: false,
    },
    plugins: [
        tailwindcss(),
        laravel({
            input: ['resources/css/app.css', 'resources/js/app.js'],
            refresh: false,

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
