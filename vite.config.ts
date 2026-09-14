import { existsSync, readFileSync } from 'node:fs';
import { fileURLToPath, URL } from 'node:url';

import laravel from 'laravel-vite-plugin';
import vue from '@vitejs/plugin-vue';
import { defineConfig } from 'vite';

// Dev-сервер ходит по тем же самоподписанным сертификатам, что и nginx:
// приложение отдаётся по https, и без https на стороне vite браузер
// блокирует hot-скрипты как mixed content. Если сертификаты ещё не
// сгенерированы (первый `docker compose up` не запускался) — http без https.
const viteHttps = (() => {
    const certDir = fileURLToPath(new URL('./docker/nginx/certs', import.meta.url));
    const key = `${certDir}/key.pem`;
    const cert = `${certDir}/cert.pem`;

    return existsSync(key) && existsSync(cert)
        ? { key: readFileSync(key), cert: readFileSync(cert) }
        : undefined;
})();

export default defineConfig({
    plugins: [
        laravel({
            input: ['resources/js/app/main.ts'],
            refresh: true,
        }),
        vue(),
    ],
    resolve: {
        alias: {
            '@': fileURLToPath(new URL('./resources/js', import.meta.url)),
        },
    },
    server: {
        host: 'localhost',
        port: 5173,
        strictPort: true,
        cors: true,
        https: viteHttps,
    },
});
