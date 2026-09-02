import { defineConfig } from 'vite';
import laravel from 'laravel-vite-plugin';
import { bunny } from 'laravel-vite-plugin/fonts';
import tailwindcss from '@tailwindcss/vite';

export default defineConfig({
    plugins: [
        laravel({
            input: ['resources/css/app.css', 'resources/js/app.js'],
            refresh: true,
            /*
             * Plus Jakarta Sans: geométrica, contemporânea e com pesos altos
             * que aguentam títulos grandes sem parecerem institucionais.
             * Auto-alojada — nenhum pedido a terceiros no carregamento.
             */
            fonts: [
                bunny('Plus Jakarta Sans', {
                    weights: [400, 500, 600, 700, 800],
                }),
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
