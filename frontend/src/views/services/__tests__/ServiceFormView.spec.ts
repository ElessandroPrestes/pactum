import { beforeEach, describe, expect, it, vi } from 'vitest'
import { mount, flushPromises } from '@vue/test-utils'
import { createPinia, setActivePinia } from 'pinia'
import { createMemoryHistory, createRouter, type Router } from 'vue-router'

vi.mock('@/api/services', () => ({
    listServices: vi.fn(),
    getService: vi.fn(),
    createService: vi.fn(),
    updateService: vi.fn(),
    deleteService: vi.fn(),
}))

import * as api from '@/api/services'
import { ApiError } from '@/lib/http'
import ServiceFormView from '../ServiceFormView.vue'

function buildRouter(): Router {
    return createRouter({
        history: createMemoryHistory(),
        routes: [
            { path: '/servicos', name: 'services-list', component: { template: '<div />' } },
            { path: '/servicos/novo', name: 'services-create', component: ServiceFormView },
            {
                path: '/servicos/:id(\\d+)/editar',
                name: 'services-edit',
                component: ServiceFormView,
            },
        ],
    })
}

async function mountAt(path: string) {
    const router = buildRouter()
    await router.push(path)
    await router.isReady()
    return mount(ServiceFormView, { global: { plugins: [router] } })
}

describe('ServiceFormView', () => {
    beforeEach(() => {
        setActivePinia(createPinia())
        vi.clearAllMocks()
    })

    it('bloqueia submit quando nome e valor estao vazios', async () => {
        const wrapper = await mountAt('/servicos/novo')

        await wrapper.find('form').trigger('submit.prevent')
        await flushPromises()

        expect(api.createService).not.toHaveBeenCalled()
        const messages = wrapper
            .findAll('[role="alert"]')
            .map((el) => el.text())
            .join(' ')
        expect(messages).toContain('Informe o nome.')
        expect(messages).toContain('Valor deve ser maior que zero.')
    })

    it('envia payload com valor decimal a partir do input em centavos', async () => {
        vi.mocked(api.createService).mockResolvedValueOnce({
            id: 7,
            nome: 'Hosting',
            valor_base_mensal: '199.90',
            ativo: true,
            created_at: null,
            updated_at: null,
        })

        const wrapper = await mountAt('/servicos/novo')
        const inputs = wrapper.findAll('input')
        await inputs[0]?.setValue('Hosting')
        await inputs[1]?.setValue('19990')

        await wrapper.find('form').trigger('submit.prevent')
        await flushPromises()

        expect(api.createService).toHaveBeenCalledWith({
            nome: 'Hosting',
            valor_base_mensal: '199.90',
            ativo: true,
        })
    })

    it('mapeia erros 422 por campo', async () => {
        vi.mocked(api.createService).mockRejectedValueOnce(
            new ApiError('Falha de validacao', 422, {
                errors: {
                    nome: ['nome ja em uso'],
                    valor_base_mensal: ['valor invalido'],
                },
            }),
        )

        const wrapper = await mountAt('/servicos/novo')
        const inputs = wrapper.findAll('input')
        await inputs[0]?.setValue('Hosting')
        await inputs[1]?.setValue('19990')

        await wrapper.find('form').trigger('submit.prevent')
        await flushPromises()

        const messages = wrapper
            .findAll('[role="alert"]')
            .map((el) => el.text())
            .join(' ')
        expect(messages).toContain('nome ja em uso')
        expect(messages).toContain('valor invalido')
    })
})
