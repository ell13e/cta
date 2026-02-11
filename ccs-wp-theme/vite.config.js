import { defineConfig } from 'vite';
import { resolve } from 'path';
import legacy from '@vitejs/plugin-legacy';

export default defineConfig({
  plugins: [
    legacy({
      targets: ['defaults', 'not IE 11']
    })
  ],
  build: {
    outDir: 'assets/dist',
    rollupOptions: {
      input: {
        // Add source files here when they exist
        // main: resolve(__dirname, 'assets/js/src/main.js'),
        // contact: resolve(__dirname, 'assets/js/src/contact.js'),
      },
      output: {
        entryFileNames: 'js/[name].min.js',
        chunkFileNames: 'js/[name]-[hash].js',
        assetFileNames: (assetInfo) => {
          if (assetInfo.name.endsWith('.css')) {
            return 'css/[name].min.css';
          }
          return 'assets/[name]-[hash][extname]';
        }
      }
    },
    minify: 'terser',
    sourcemap: true,
    manifest: true
  },
  server: {
    watch: {
      usePolling: true
    }
  }
});
