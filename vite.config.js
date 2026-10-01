import { defineConfig, loadEnv } from 'vite';
import laravel from 'laravel-vite-plugin';

export default defineConfig(({ mode }) => {
    const env = loadEnv(mode, process.cwd(), '');
    const applicationBase = (env.VITE_BASE_PATH || '/').replace(/^\/+|\/+$/g, '');
    const buildBase = applicationBase ? `/${applicationBase}/build/` : '/build/';

    return {
        base: buildBase,
        plugins: [
            laravel({
                input: ['resources/css/app.css', 'resources/js/app.js', 'resources/css/admin.css', 'resources/js/admin.js'],
                refresh: true,
            }),
        ],
    };
});
