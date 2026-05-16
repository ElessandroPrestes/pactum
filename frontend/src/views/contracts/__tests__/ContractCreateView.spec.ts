import { beforeEach, describe, expect, it, vi } from 'vitest'
import { mount, flushPromises } from '@vue/test-utils'
import { createPinia, setActivePinia } from 'pinia'
import { createMemoryHistory, createRouter, type Router } from 'vue-router'

vi.mock('@/api/contracts', () => ({
    listContracts: vi.fn(),
    getContract: vi.fn(),
    createContract: vi.fn(),
    updateContract: vi.fn(),
    deleteContract: vi.fn(),
    cancelContract: vi.fn(),
    addContractItem: vi.fn(),
    removeContractItem: vi.fn(),
    listContractHistory: vi.fn(),
}))

vi.mock('@/api/clients', () => ({
    listClients: vi.fn(),
}))

vi.mock('@/api/services', () => ({
    listServices: vi.fn(),
}))

import * as contractsApi from '@/api/contracts'
import * as clientsApi from '@/api/clients'
import * as servicesApi from '@/api/services'
import { ApiError } from '@/lib/http'
import ContractCreateView from '../ContractCreateView.vue'

function buildRouter(): Router {
    return createRouter({
        history: createMemoryHistory(),
        routes: [
            { path: '/contratos', name: 'contracts-list', component: { template: '<div />' } },
            { path: '/contratos/novo', name: 'contracts-create', component: ContractCreateView },
            {
                path: '/contratos/:id(\\d+)',
                name: 'contracts-show',
                component: { template: '<div />' },
            },
        ],
    })
}

async function mountAt(path: string) {
    const router = buildRouter()
    await router.push(path)
    await router.isReady()
    return mount(ContractCreateView, { global: { plugins: [router] } })
}

function clientsPaged() {
    return {
        data: [
            {
                id: 10,
                nome: 'Acme Ltda',
                documento: '12345678000190',
                tipo_documento: 'cnpj' as const,
                email: 'a@a.com',
                status: 'ativo' as const,
                created_at: null,
                updated_at: null,
            },
        ],
        meta: {
            current_page: 1,
            last_page: 1,
            per_page: 100,
            total: 1,
            from: 1,
            to: 1,
        },
    }
}

function servicesPaged() {
    return {
        data: [
            {
                id: 200,
                nome: 'Hosting',
                valor_base_mensal: '199.90',
                ativo: true,
                created_at: null,
                updated_at: null,
            },
        ],
        meta: {
            current_page: 1,
            last_page: 1,
            per_page: 100,
            total: 1,
            from: 1,
            to: 1,
        },
    }
}

describe('ContractCreateView', () => {
    beforeEach(() => {
        setActivePinia(createPinia())
        vi.clearAllMocks()
        vi.mocked(clientsApi.listClients).mockResolvedValue(clientsPaged())
        vi.mocked(servicesApi.listServices).mockResolvedValue(servicesPaged())
    })

    it('bloqueia submit sem cliente e sem servico selecionados', async () => {
        const wrapper = await mountAt('/contratos/novo')
        await flushPromises()

        await wrapper.find('form').trigger('submit.prevent')
        await flushPromises()

        expect(contractsApi.createContract).not.toHaveBeenCalled()
        const messages = wrapper
            .findAll('[role="alert"]')
            .map((el) => el.text())
            .join(' ')
        expect(messages).toContain('Selecione um cliente.')
        expect(messages).toContain('Selecione o servico.')
    })

    it('envia payload normalizado e redireciona ao detalhe', async () => {
        vi.mocked(contractsApi.createContract).mockResolvedValueOnce({
            id: 77,
            client_id: 10,
            data_inicio: '2026-06-01',
            data_fim: null,
            status: 'ativo',
            version: 1,
            itens: [],
            total_calculado: '199.90',
            created_at: null,
            updated_at: null,
        })

        const wrapper = await mountAt('/contratos/novo')
        await flushPromises()

        const selects = wrapper.findAll('select')
        await selects[0]?.setValue('10')

        const dateInputs = wrapper.findAll('input[type="date"]')
        await dateInputs[0]?.setValue('2026-06-01')

        await selects[1]?.setValue('200')

        await wrapper.find('form').trigger('submit.prevent')
        await flushPromises()

        expect(contractsApi.createContract).toHaveBeenCalledTimes(1)
        const call = vi.mocked(contractsApi.createContract).mock.calls[0]?.[0]
        expect(call?.client_id).toBe(10)
        expect(call?.data_inicio).toBe('2026-06-01')
        expect(call?.itens?.[0]?.service_id).toBe(200)
        expect(call?.itens?.[0]?.valor_unitario).toBe('199.90')
    })

    it('mapeia erros 422 da api para os campos', async () => {
        vi.mocked(contractsApi.createContract).mockRejectedValueOnce(
            new ApiError('Falha de validacao', 422, {
                errors: {
                    client_id: ['cliente invalido'],
                    'itens.0.service_id': ['servico invalido'],
                },
            }),
        )

        const wrapper = await mountAt('/contratos/novo')
        await flushPromises()

        const selects = wrapper.findAll('select')
        await selects[0]?.setValue('10')

        const dateInputs = wrapper.findAll('input[type="date"]')
        await dateInputs[0]?.setValue('2026-06-01')

        await selects[1]?.setValue('200')

        await wrapper.find('form').trigger('submit.prevent')
        await flushPromises()

        const messages = wrapper
            .findAll('[role="alert"]')
            .map((el) => el.text())
            .join(' ')
        expect(messages).toContain('cliente invalido')
        expect(messages).toContain('servico invalido')
    })
})
