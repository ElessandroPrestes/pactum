import { createApp } from 'vue'
import { createPinia } from 'pinia'

import App from './App.vue'
import { router } from './router'
import './styles/main.css'

const app = createApp(App)
app.use(createPinia())
app.use(router)

app.config.errorHandler = (err) => {
    console.error('[pactum] erro nao tratado', err)
    void router.replace({ name: 'server-error' })
}

if (typeof window !== 'undefined') {
    window.addEventListener('unhandledrejection', (event) => {
        console.error('[pactum] rejeicao nao tratada', event.reason)
        void router.replace({ name: 'server-error' })
    })
}

app.mount('#app')
