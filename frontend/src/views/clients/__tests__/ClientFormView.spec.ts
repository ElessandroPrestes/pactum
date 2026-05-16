import { beforeEach, describe, expect, it, vi } from 'vitest'
import { mount, flushPromises } from '@vue/test-utils'
import { createPinia, setActivePinia } from 'pinia'
import { createMemoryHistory, createRouter, type Router } from 'vue-router'

vi.mock('@/api/clients', () => ({
    listClients: vi.fn(),
    getClient: vi.fn(),
    createClient: vi.fn(),
    updateClient: vi.fn(),
    deleteClient: vi.fn(),
}))

import * as api from '@/api/clients'
import { ApiError } from '@/lib/http'
import ClientFormView from '../ClientFormView.vue'

function buildRouter(): Router {
    return createRouter({
        history: createMemoryHistory(),
        routes: [
            { path: '/clientes', name: 'clients-list', component: { template: '<div />' } },
            { path: '/clientes/novo', name: 'clients-create', component: ClientFormView },
            {
                path: '/clientes/:id(\\d+)/editar',
                name: 'clients-edit',
                component: ClientFormView,
            },
        ],
    })
}

async function mountAt(path: string) {
    const router = buildRouter()
    await router.push(path)
    await router.isReady()

    return mount(ClientFormView, {
        global: { plugins: [router] },
    })
}

describe('ClientFormView', () => {
    beforeEach(() => {
        setActivePinia(createPinia())
        vi.clearAllMocks()
    })

    it('exige nome, documento e email no submit', async () => {
        const wrapper = await mountAt('/clientes/novo')

        await wrapper.find('form').trigger('submit.prevent')
        await flushPromises()

        expect(api.createClient).not.toHaveBeenCalled()
        const alerts = wrapper.findAll('[role="alert"]').map((el) => el.text())
        expect(alerts.join(' ')).toContain('Informe o nome.')
        expect(alerts.join(' ')).toContain('Documento incompleto')
        expect(alerts.join(' ')).toContain('Informe o email.')
    })

    it('envia payload normalizado quando dados sao validos', async () => {
        vi.mocked(api.createClient).mockResolvedValueOnce({
            id: 42,
            nome: 'Acme',
            documento: '12345678000190',
            tipo_documento: 'cnpj',
            email: 'a@b.com',
            status: 'ativo',
            created_at: null,
            updated_at: null,
        })

        const wrapper = await mountAt('/clientes/novo')

        const inputs = wrapper.findAll('input')
        await inputs[0]?.setValue('Acme')
        const tipo = wrapper.find('select')
        await tipo.setValue('cnpj')
        await inputs[1]?.setValue('12.345.678/0001-90')
        await inputs[2]?.setValue('contato@acme.com')

        await wrapper.find('form').trigger('submit.prevent')
        await flushPromises()

        expect(api.createClient).toHaveBeenCalledWith({
            nome: 'Acme',
            documento: '12345678000190',
            tipo_documento: 'cnpj',
            email: 'contato@acme.com',
        })
    })

    it('mapeia erros 422 da API por campo', async () => {
        vi.mocked(api.createClient).mockRejectedValueOnce(
            new ApiError('Falha de validacao', 422, {
                errors: {
                    email: ['email ja cadastrado'],
                    documento: ['documento invalido'],
                },
            }),
        )

        const wrapper = await mountAt('/clientes/novo')
        const inputs = wrapper.findAll('input')
        await inputs[0]?.setValue('Acme')
        await wrapper.find('select').setValue('cpf')
        await inputs[1]?.setValue('123.456.789-09')
        await inputs[2]?.setValue('a@b.com')

        await wrapper.find('form').trigger('submit.prevent')
        await flushPromises()

        const messages = wrapper
            .findAll('[role="alert"]')
            .map((el) => el.text())
            .join(' ')
        expect(messages).toContain('email ja cadastrado')
        expect(messages).toContain('documento invalido')
    })
})
