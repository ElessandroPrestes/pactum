import { describe, expect, it } from 'vitest'
import { mount } from '@vue/test-utils'
import { createMemoryHistory, createRouter } from 'vue-router'
import ServerErrorView from '../ServerErrorView.vue'

function buildRouter() {
    return createRouter({
        history: createMemoryHistory(),
        routes: [
            { path: '/', name: 'home', component: { template: '<div />' } },
            { path: '/erro', name: 'server-error', component: ServerErrorView },
        ],
    })
}

describe('ServerErrorView', () => {
    it('mostra titulo e botoes de acao', async () => {
        const router = buildRouter()
        await router.push('/erro')
        await router.isReady()

        const wrapper = mount(ServerErrorView, {
            global: { plugins: [router] },
        })

        expect(wrapper.text()).toContain('Algo deu errado')
        expect(wrapper.findAll('button')).toHaveLength(2)
    })

    it('exibe trace id quando presente na query', async () => {
        const router = buildRouter()
        await router.push({ name: 'server-error', query: { trace: 'abc-123' } })
        await router.isReady()

        const wrapper = mount(ServerErrorView, {
            global: { plugins: [router] },
        })

        expect(wrapper.text()).toContain('abc-123')
    })
})
