import { afterEach, beforeEach, describe, expect, it, vi } from 'vitest'
import { mount, flushPromises } from '@vue/test-utils'
import { createPinia, setActivePinia } from 'pinia'
import { createMemoryHistory, createRouter, type Router } from 'vue-router'
import { defineComponent, h } from 'vue'
import UserMenu from '../UserMenu.vue'
import { useAuthStore } from '@/stores/auth'
import { useToastStore } from '@/stores/toast'

vi.mock('@/lib/http', () => ({
    http: {
        post: vi.fn().mockResolvedValue({ data: {} }),
        get: vi.fn(),
    },
}))

const Stub = defineComponent({ render: () => h('div') })

function buildRouter(): Router {
    return createRouter({
        history: createMemoryHistory(),
        routes: [
            { path: '/', name: 'home', component: Stub },
            { path: '/login', name: 'login', component: Stub },
        ],
    })
}

async function mountAt(): Promise<ReturnType<typeof mount>> {
    const router = buildRouter()
    await router.push('/')
    await router.isReady()
    return mount(UserMenu, { global: { plugins: [router] } })
}

describe('UserMenu', () => {
    beforeEach(() => {
        setActivePinia(createPinia())
        const auth = useAuthStore()
        auth.setSession({
            token: 'tok',
            user: { id: 1, name: 'Maria Silva', email: 'maria@pactum.local', created_at: null },
        })
    })

    afterEach(() => {
        window.localStorage.clear()
    })

    it('mostra iniciais derivadas do nome quando fechado', async () => {
        const wrapper = await mountAt()
        expect(wrapper.text()).toContain('MS')
        expect(wrapper.text()).toContain('Maria Silva')
    })

    it('abre o menu com nome, email e acao sair', async () => {
        const wrapper = await mountAt()
        await wrapper.find('button[aria-haspopup="menu"]').trigger('click')
        await flushPromises()

        const menu = wrapper.find('[role="menu"]')
        expect(menu.exists()).toBe(true)
        expect(menu.text()).toContain('Maria Silva')
        expect(menu.text()).toContain('maria@pactum.local')
        expect(menu.text()).toContain('Sair')
    })

    it('desloga, limpa sessao e redireciona para login', async () => {
        const wrapper = await mountAt()
        await wrapper.find('button[aria-haspopup="menu"]').trigger('click')
        await flushPromises()

        await wrapper.find('button[role="menuitem"]').trigger('click')
        await flushPromises()

        const auth = useAuthStore()
        const toasts = useToastStore()
        expect(auth.isAuthenticated).toBe(false)
        expect(toasts.toasts[0]?.variant).toBe('success')
    })
})
