import { defineConfig } from 'vite';
import laravel from 'laravel-vite-plugin';
import { bunny } from 'laravel-vite-plugin/fonts';
import tailwindcss from '@tailwindcss/vite';

export default defineConfig({
    plugins: [
        laravel({
            input: ['resources/css/app.css', 'resources/js/app.js', 'resources/js/admin.js'],
            refresh: true,
            fonts: [
                // Display, headings and quotes.
                bunny('Cormorant Garamond', { weights: [300], styles: ['normal', 'italic'] }),
                // Body and UI.
                bunny('Hanken Grotesk', { weights: [300, 400, 500], preload: [{ weight: 300, style: 'normal' }] }),
                // Technical specs only.
                bunny('JetBrains Mono', { weights: [400], preload: false }),
            ],
        }),
        tailwindcss(),
    ],
    server: {
        watch: {
            ignored: ['**/storage/framework/views/**'],
        },
    },
});
