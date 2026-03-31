import { defineConfig } from 'vite';
import laravel from 'laravel-vite-plugin';
import tailwindcss from '@tailwindcss/vite';

export default defineConfig({
    plugins: [
        laravel({
            input: ['resources/css/app.css', 'resources/js/app.js'],
            refresh: true,
        }),
        tailwindcss(),
    ],
    build: {
        rollupOptions: {
            output: {
                manualChunks(id) {
                    if (!id.includes('node_modules')) {
                        return;
                    }

                    if (id.includes('chart.js')) {
                        return 'vendor-charts';
                    }

                    if (
                        id.includes('filepond')
                    ) {
                        return 'vendor-inputs';
                    }

                    if (
                        id.includes('toastr')
                        || id.includes('tippy.js')
                    ) {
                        return 'vendor-feedback';
                    }

                    if (id.includes('alpinejs')) {
                        return 'vendor-alpine';
                    }

                    return 'vendor-misc';
                },
            },
        },
    },
    server: {
        watch: {
            ignored: ['**/storage/framework/views/**'],
        },
    },
});
