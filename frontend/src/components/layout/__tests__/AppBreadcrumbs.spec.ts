import { describe, expect, it } from 'vitest'
import { mount } from '@vue/test-utils'
import { createMemoryHistory, createRouter, type Router } from 'vue-router'
import AppBreadcrumbs from '../AppBreadcrumbs.vue'

function buildRouter(): Router {
    return createRouter({
        history: createMemoryHistory(),
        routes: [
            {
                path: '/',
                name: 'home',
                component: AppBreadcrumbs,
                meta: { title: 'Visao geral' },
            },
            {
                path: '/clientes',
                name: 'clients-list',
                component: AppBreadcrumbs,
                meta: { title: 'Clientes' },
            },
            {
                path: '/clientes/novo',
                name: 'clients-create',
                component: AppBreadcrumbs,
                meta: { title: 'Novo cliente' },
            },
            {
                path: '/contratos',
                name: 'contracts-list',
                component: AppBreadcrumbs,
                meta: { title: 'Contratos' },
            },
            {
                path: '/contratos/:id(\\d+)',
                name: 'contracts-show',
                component: AppBreadcrumbs,
                meta: { title: 'Detalhe do contrato' },
            },
        ],
    })
}

async function mountAt(path: string) {
    const router = buildRouter()
    await router.push(path)
    await router.isReady()
    return mount(AppBreadcrumbs, { global: { plugins: [router] } })
}

describe('AppBreadcrumbs', () => {
    it('nao renderiza na home (somente uma raiz)', async () => {
        const wrapper = await mountAt('/')
        expect(wrapper.find('nav').exists()).toBe(false)
    })

    it('renderiza dois niveis em listagem de cliente', async () => {
        const wrapper = await mountAt('/clientes')
        const items = wrapper.findAll('li')
        expect(items).toHaveLength(2)
        expect(items[0]?.text()).toContain('Visao geral')
        expect(items[1]?.text()).toContain('Clientes')
    })

    it('renderiza tres niveis em criacao de cliente', async () => {
        const wrapper = await mountAt('/clientes/novo')
        const items = wrapper.findAll('li')
        expect(items).toHaveLength(3)
        expect(items[2]?.text()).toContain('Novo cliente')
        expect(wrapper.find('[aria-current="page"]').text()).toContain('Novo cliente')
    })

    it('renderiza detalhe de contrato com aria-current na folha', async () => {
        const wrapper = await mountAt('/contratos/42')
        const items = wrapper.findAll('li')
        expect(items).toHaveLength(3)
        expect(items[1]?.text()).toContain('Contratos')
        expect(wrapper.find('[aria-current="page"]').text()).toContain('Detalhe do contrato')
    })
})
