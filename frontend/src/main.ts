import { createApp } from 'vue'
import { createPinia } from 'pinia'

import App from './App.vue'
import { router } from './router'
import { setAuthTokenProvider, setUnauthorizedHandler } from './lib/http'
import { useAuthStore } from './stores/auth'
import './styles/main.css'

const app = createApp(App)
const pinia = createPinia()
app.use(pinia)

const auth = useAuthStore(pinia)
setAuthTokenProvider(() => auth.token)
setUnauthorizedHandler(async () => {
    if (!auth.isAuthenticated) {
        return
    }
    auth.clearSession()
    if (router.currentRoute.value.name !== 'login') {
        await router.replace({
            name: 'login',
            query: { redirect: router.currentRoute.value.fullPath },
        })
    }
})

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
