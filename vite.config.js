import { defineConfig } from 'vite';
import laravel from 'laravel-vite-plugin';
import tailwindcss from '@tailwindcss/vite';

export default defineConfig({
    plugins: [
        laravel({
            input: ['resources/css/app.css', 'resources/js/app.js', 'resources/js/admin.js', 'resources/js/chat.js', 'resources/js/admin/analytics-chart.js'],
            refresh: true,
        }),
        tailwindcss(),
    ],
    build: {
        // light-dark() stays native (Lightning CSS would otherwise polyfill it and pre-compute colour mixes).
        cssTarget: ['chrome123', 'safari17.5', 'firefox120'],
    },
    server: {
        watch: {
            ignored: ['**/storage/framework/views/**'],
        },
    },
});
