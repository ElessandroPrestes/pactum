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

vi.mock('@/api/services', () => ({
    listServices: vi.fn(),
}))

import * as contractsApi from '@/api/contracts'
import * as servicesApi from '@/api/services'
import { ApiError } from '@/lib/http'
import ContractDetailView from '../ContractDetailView.vue'
import type { Contract } from '@/types/contract'

function buildRouter(): Router {
    return createRouter({
        history: createMemoryHistory(),
        routes: [
            { path: '/contratos', name: 'contracts-list', component: { template: '<div />' } },
            {
                path: '/contratos/:id(\\d+)',
                name: 'contracts-show',
                component: ContractDetailView,
            },
        ],
    })
}

async function mountAt(path: string) {
    const router = buildRouter()
    await router.push(path)
    await router.isReady()
    return mount(ContractDetailView, { global: { plugins: [router] } })
}

function makeContract(overrides: Partial<Contract> = {}): Contract {
    return {
        id: 7,
        client_id: 10,
        data_inicio: '2026-01-01',
        data_fim: null,
        status: 'ativo',
        version: 1,
        client: {
            id: 10,
            nome: 'Acme Ltda',
            documento: '12345678000190',
            tipo_documento: 'cnpj',
            email: 'a@a.com',
            status: 'ativo',
            created_at: null,
            updated_at: null,
        },
        itens: [],
        total_calculado: '0.00',
        created_at: null,
        updated_at: null,
        ...overrides,
    }
}

function emptyHistory() {
    return {
        data: [],
        meta: {
            current_page: 1,
            last_page: 1,
            per_page: 15,
            total: 0,
            from: null,
            to: null,
        },
    }
}

describe('ContractDetailView', () => {
    beforeEach(() => {
        setActivePinia(createPinia())
        vi.clearAllMocks()
        vi.mocked(servicesApi.listServices).mockResolvedValue({
            data: [],
            meta: {
                current_page: 1,
                last_page: 1,
                per_page: 100,
                total: 0,
                from: null,
                to: null,
            },
        })
        vi.mocked(contractsApi.listContractHistory).mockResolvedValue(emptyHistory())
    })

    it('renderiza dados principais do contrato', async () => {
        vi.mocked(contractsApi.getContract).mockResolvedValueOnce(makeContract())

        const wrapper = await mountAt('/contratos/7')
        await flushPromises()

        expect(wrapper.text()).toContain('Acme Ltda')
        expect(wrapper.text()).toContain('2026-01-01')
        expect(wrapper.text()).toContain('ativo')
    })

    it('confirma cancelamento e atualiza status', async () => {
        vi.mocked(contractsApi.getContract).mockResolvedValueOnce(makeContract())
        vi.mocked(contractsApi.cancelContract).mockResolvedValueOnce(
            makeContract({ status: 'cancelado', version: 2 }),
        )

        const wrapper = await mountAt('/contratos/7')
        await flushPromises()

        const buttons = wrapper.findAll('button')
        const cancelButton = buttons.find((btn) => btn.text().includes('Cancelar contrato'))
        expect(cancelButton).toBeTruthy()
        await cancelButton?.trigger('click')
        await flushPromises()

        const modalButtons = document.body.querySelectorAll('button')
        const confirmButton = Array.from(modalButtons).find(
            (btn) => btn.textContent?.trim() === 'Cancelar contrato',
        )
        expect(confirmButton).toBeTruthy()
        confirmButton?.dispatchEvent(new MouseEvent('click', { bubbles: true }))
        await flushPromises()

        expect(contractsApi.cancelContract).toHaveBeenCalledWith(7, 1)
    })

    it('trata 409 no cancel mostrando mensagem de conflito', async () => {
        vi.mocked(contractsApi.getContract).mockResolvedValueOnce(makeContract())
        vi.mocked(contractsApi.cancelContract).mockRejectedValueOnce(
            new ApiError('Conflito de versao', 409),
        )
        vi.mocked(contractsApi.getContract).mockResolvedValueOnce(makeContract({ version: 2 }))

        const wrapper = await mountAt('/contratos/7')
        await flushPromises()

        const buttons = wrapper.findAll('button')
        const cancelButton = buttons.find((btn) => btn.text().includes('Cancelar contrato'))
        await cancelButton?.trigger('click')
        await flushPromises()

        const modalButtons = document.body.querySelectorAll('button')
        const confirmButton = Array.from(modalButtons).find(
            (btn) => btn.textContent?.trim() === 'Cancelar contrato',
        )
        confirmButton?.dispatchEvent(new MouseEvent('click', { bubbles: true }))
        await flushPromises()

        const messages = Array.from(document.body.querySelectorAll('[role="alert"]'))
            .map((el) => el.textContent ?? '')
            .join(' ')
        expect(messages).toContain('Outra sessao alterou')
    })
})
