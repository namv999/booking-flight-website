import { defineConfig } from 'vite';
import laravel from 'laravel-vite-plugin';

export default defineConfig({
    plugins: [
        laravel({
            input: [
                'resources/css/app.css',
                'resources/js/app.js',
                'resources/css/home.css',
                'resources/css/account.css',
                'resources/css/admin.css',
                'resources/js/layout.js',
                'resources/js/home.js',
                'resources/js/auth.js',
                'resources/js/dashboard.js',
            ],
            refresh: true,
        }),
    ],
});
