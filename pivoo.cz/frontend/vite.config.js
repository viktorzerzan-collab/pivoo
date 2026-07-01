import { fileURLToPath, URL } from 'node:url'

import { defineConfig } from 'vite'
import vue from '@vitejs/plugin-vue'
import vueDevTools from 'vite-plugin-vue-devtools'
import dns from 'node:dns'

// Vynutíme, aby Node.js (Vite) preferoval IPv4 adresy před IPv6.
// Toto řeší problém "AggregateError [ETIMEDOUT]: internalConnectMultiple"
dns.setDefaultResultOrder('ipv4first')

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
        secure: false,
        // Následující blok zajistí podstrčení správných hlaviček serveru
        configure: (proxy, _options) => {
          proxy.on('error', (err, _req, _res) => {
            console.log('Proxy chyba:', err);
          });
          proxy.on('proxyReq', (proxyReq, req, _res) => {
            // Aby si server Českého hostingu myslel, že požadavek jde přímo z domény
            proxyReq.setHeader('Origin', 'https://www.pivoo.cz');
            proxyReq.setHeader('Host', 'www.pivoo.cz');
          });
        }
      }
    }
  }
})