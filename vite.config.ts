import { defineConfig } from 'vite'
import laravel from 'laravel-vite-plugin'
import vue from '@vitejs/plugin-vue'
import tailwindcss from '@tailwindcss/vite'
import fs from 'node:fs'
import path from 'node:path'

export default defineConfig({
    plugins: [
        laravel({
            input: [
                'resources/css/app.css',
                'resources/js/app.ts',
            ],
            refresh: true,
        }),
        vue(),
        tailwindcss(),
    ],

    optimizeDeps: {
        force: true
    },
    server: {
        host: '0.0.0.0',
        port: 5173,
        https: {
            key: fs.readFileSync(path.resolve(__dirname, 'docker/nginx/certs/localhost.key')),
            cert: fs.readFileSync(path.resolve(__dirname, 'docker/nginx/certs/localhost.crt')),
        },
        hmr: {
            host: 'localhost',
            protocol: 'wss',
            port: 5173,
        },
        watch: {
            ignored: ['**/storage/framework/views/**'],
        },
    },

    resolve: {
        alias: {
            '@': '/resources/js',
        },
    },
})
