import { fileURLToPath, URL } from 'node:url'

import { defineConfig, loadEnv } from 'vite'
import vue from '@vitejs/plugin-vue'

// O Vite roda dentro do container `frontend` e e exposto ao navegador
// atraves do nginx (porta publica APP_PORT, default 8000). Como o HMR
// abre um WebSocket direto do navegador, ele precisa apontar para a
// porta publica do nginx — nao para 5173, que so existe na rede docker.
export default defineConfig(({ mode }) => {
  const env = loadEnv(mode, process.cwd(), '')
  const publicPort = Number(env.VITE_PUBLIC_PORT ?? env.APP_PORT ?? 8000)

  return {
    plugins: [vue()],
    resolve: {
      alias: {
        '@': fileURLToPath(new URL('./src', import.meta.url)),
      },
    },
    server: {
      host: '0.0.0.0',
      port: 5173,
      strictPort: true,
      hmr: {
        clientPort: publicPort,
      },
    },
    preview: {
      host: '0.0.0.0',
      port: 4173,
    },
  }
})
