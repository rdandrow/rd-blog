/// <reference types="vitest" />
import { wayfinder } from '@laravel/vite-plugin-wayfinder';
import tailwindcss from '@tailwindcss/vite';
import vue from '@vitejs/plugin-vue';
import laravel from 'laravel-vite-plugin';
import path from 'path';
import { defineConfig } from 'vite';

export default defineConfig({
    plugins: [
        laravel({
            input: ['resources/js/app.ts'],
            ssr: 'resources/js/ssr.ts',
            refresh: true,
        }),
        tailwindcss(),
        wayfinder({
            formVariants: true,
        }),
        vue({
            template: {
                transformAssetUrls: {
                    base: null,
                    includeAbsolute: false,
                },
            },
        }),
    ],

    // Test configuration
    test: {
        globals: true,
        environment: 'happy-dom',
        setupFiles: ['./tests/frontend/setup.ts'],
        restoreMocks: true,
        clearMocks: true,
        unstubEnvs: true,
        unstubGlobals: true,
        coverage: {
            provider: 'v8',
            reporter: ['text', 'html', 'json'],
            include: ['resources/js/**/*.{ts,tsx,js,vue}'],
            exclude: [
                '**/*.d.ts',
                '**/*.spec.ts',
                '**/*.test.ts',
                '**/types/**',
                '**/index.ts',
            ],
            all: true,
            thresholds: {
                lines: 85,
                functions: 85,
                branches: 80,
                statements: 85,
            },
            perFile: true,
            thresholdAutoUpdate: false,
        },
    },

    resolve: {
        alias: {
            '@': path.resolve(__dirname, './resources/js'),
        },
    },
});
