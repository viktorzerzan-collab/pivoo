import { fileURLToPath, URL } from 'node:url'

import { defineConfig } from 'vite'
import vue from '@vitejs/plugin-vue'
import vueDevTools from 'vite-plugin-vue-devtools'

// https://vite.dev/config/
export default defineConfig({
  plugins: [
    vue(),
    vueDevTools(),
  ],
  resolve: {
    alias: {
      '@': fileURLToPath(new URL('./src', import.meta.url))
    },
  },
  server: {
    proxy: {
      // Změněno z '/backend/api' na '/backend', aby proxy zachytila i /backend/uploads
      '/backend': {
        target: 'https://www.pivoo.cz',
        changeOrigin: true,
        secure: false
      }
    }
  }
})