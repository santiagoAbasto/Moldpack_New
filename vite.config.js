import { defineConfig } from 'vite';
import laravel from 'laravel-vite-plugin';
import react from '@vitejs/plugin-react';

export default defineConfig({
  plugins: [
    laravel({
      input: ['resources/css/public.css', 'resources/css/client-shell.css', 'resources/css/client-products.css', 'resources/css/client-cart.css', 'resources/css/client-orders.css', 'resources/css/client-payments.css', 'resources/css/client-account.css', 'resources/css/client-invoices.css', 'resources/css/admin.css', 'resources/css/login.css', 'resources/js/admin.jsx', 'resources/js/stores-map.js'],
      refresh: true,
    }),
    react(),
  ],
  build: {
    target: 'es2024',
    cssMinify: 'lightningcss',
    rolldownOptions: {
      output: {
        codeSplitting: {
          groups: [
            { name: 'react', test: /node_modules[\\/](react|react-dom)[\\/]/, priority: 30 },
            { name: 'editor', test: /node_modules[\\/]@tiptap[\\/]/, priority: 20 },
            { name: 'inertia', test: /node_modules[\\/]@inertiajs[\\/]/, priority: 20 },
            { name: 'icons', test: /node_modules[\\/]lucide-react[\\/]/, priority: 10 },
          ],
        },
      },
    },
  },
});
