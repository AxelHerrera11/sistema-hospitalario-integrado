import { defineConfig } from 'vite';
import vue from '@vitejs/plugin-vue';
import tailwindcss from '@tailwindcss/vite';
import { fileURLToPath } from 'node:url';

const aqui = fileURLToPath(new URL('.', import.meta.url));
export default defineConfig({
    root: aqui,
    plugins: [vue(), tailwindcss()],
    resolve: { alias: { '@': fileURLToPath(new URL('../../../../../resources/js', import.meta.url)) } },
    build: { outDir: process.env.CAPTURAS_DIST, emptyOutDir: true },
});
