import { describe, expect, it } from 'vitest'
import { mount } from '@vue/test-utils'
import { createMemoryHistory, createRouter } from 'vue-router'
import NotFoundView from '../NotFoundView.vue'

describe('NotFoundView', () => {
    it('renderiza titulo de pagina nao encontrada e link para home', async () => {
        const router = createRouter({
            history: createMemoryHistory(),
            routes: [
                { path: '/', name: 'home', component: { template: '<div />' } },
                { path: '/404', name: 'not-found', component: NotFoundView },
            ],
        })
        await router.push('/404')
        await router.isReady()

        const wrapper = mount(NotFoundView, {
            global: { plugins: [router] },
        })

        expect(wrapper.text()).toContain('Pagina nao encontrada')
        const link = wrapper.find('a')
        expect(link.attributes('href')).toBe('/')
    })
})
