import { beforeEach, describe, expect, it, vi } from 'vitest'
import { createPinia, setActivePinia } from 'pinia'

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

import * as api from '@/api/contracts'
import { ApiError } from '@/lib/http'
import { useContractsStore } from '../contracts'
import type { Contract, ContractItem } from '@/types/contract'

function makeContract(overrides: Partial<Contract> = {}): Contract {
    return {
        id: 1,
        client_id: 10,
        data_inicio: '2026-01-01',
        data_fim: null,
        status: 'ativo',
        version: 1,
        itens: [],
        total_calculado: '0.00',
        created_at: '2026-01-01T00:00:00+00:00',
        updated_at: '2026-01-01T00:00:00+00:00',
        ...overrides,
    }
}

function makeItem(overrides: Partial<ContractItem> = {}): ContractItem {
    return {
        id: 100,
        contract_id: 1,
        service_id: 200,
        quantidade: 1,
        valor_unitario: '99.90',
        created_at: '2026-01-01T00:00:00+00:00',
        updated_at: '2026-01-01T00:00:00+00:00',
        ...overrides,
    }
}

function pagedResponse(items: Contract[], total = items.length) {
    return {
        data: items,
        meta: {
            current_page: 1,
            last_page: 1,
            per_page: 15,
            total,
            from: items.length > 0 ? 1 : null,
            to: items.length > 0 ? items.length : null,
        },
    }
}

describe('useContractsStore', () => {
    beforeEach(() => {
        setActivePinia(createPinia())
        vi.clearAllMocks()
    })

    it('fetch carrega items e atualiza meta/status', async () => {
        vi.mocked(api.listContracts).mockResolvedValueOnce(pagedResponse([makeContract()]))
        const store = useContractsStore()

        await store.fetch()

        expect(store.items).toHaveLength(1)
        expect(store.meta.total).toBe(1)
        expect(store.hasResults).toBe(true)
        expect(store.status).toBe('ready')
    })

    it('fetch propaga erro e define status error', async () => {
        vi.mocked(api.listContracts).mockRejectedValueOnce(new Error('rede'))
        const store = useContractsStore()

        await expect(store.fetch()).rejects.toThrow('rede')
        expect(store.status).toBe('error')
    })

    it('applyFilters reseta page por padrao e preserva quando solicitado', () => {
        const store = useContractsStore()
        store.applyFilters({ page: 3 }, false)
        expect(store.filters.page).toBe(3)

        store.applyFilters({ status: 'ativo' })
        expect(store.filters.page).toBe(1)
        expect(store.filters.status).toBe('ativo')
    })

    it('loadOne hidrata current e marca ready', async () => {
        vi.mocked(api.getContract).mockResolvedValueOnce(makeContract({ id: 7 }))
        const store = useContractsStore()

        const contract = await store.loadOne(7)

        expect(contract.id).toBe(7)
        expect(store.current?.id).toBe(7)
        expect(store.currentStatus).toBe('ready')
    })

    it('loadOne em erro marca status error', async () => {
        vi.mocked(api.getContract).mockRejectedValueOnce(new ApiError('boom', 500))
        const store = useContractsStore()

        await expect(store.loadOne(1)).rejects.toThrow('boom')
        expect(store.currentStatus).toBe('error')
    })

    it('create insere no topo da lista e incrementa total', async () => {
        const created = makeContract({ id: 99 })
        vi.mocked(api.createContract).mockResolvedValueOnce(created)
        const store = useContractsStore()
        store.items = [makeContract({ id: 1 })]
        store.meta = { ...store.meta, total: 1 }

        const result = await store.create({
            client_id: 10,
            data_inicio: '2026-01-01',
            itens: [{ service_id: 200, quantidade: 1, valor_unitario: '99.90' }],
        })

        expect(result.id).toBe(99)
        expect(store.items[0]?.id).toBe(99)
        expect(store.meta.total).toBe(2)
    })

    it('addItem atualiza current quando o id confere', async () => {
        const item = makeItem()
        vi.mocked(api.addContractItem).mockResolvedValueOnce(item)
        const store = useContractsStore()
        store.current = makeContract({ id: 1, itens: [] })

        await store.addItem(1, {
            service_id: 200,
            quantidade: 1,
            valor_unitario: '99.90',
        })

        expect(store.current?.itens).toHaveLength(1)
        expect(store.current?.itens?.[0]?.id).toBe(100)
    })

    it('removeItem filtra do current sem mutar referencia original', async () => {
        const item = makeItem({ id: 50 })
        vi.mocked(api.removeContractItem).mockResolvedValueOnce()
        const store = useContractsStore()
        const original = makeContract({ id: 1, itens: [item] })
        store.current = original

        await store.removeItem(1, 50)

        expect(store.current?.itens).toHaveLength(0)
        expect(original.itens).toHaveLength(1)
    })

    it('cancel substitui na lista e em current', async () => {
        const cancelled = makeContract({ id: 1, status: 'cancelado', version: 2 })
        vi.mocked(api.cancelContract).mockResolvedValueOnce(cancelled)
        const store = useContractsStore()
        store.items = [makeContract({ id: 1, version: 1 })]
        store.current = makeContract({ id: 1, version: 1 })

        await store.cancel(1, 1)

        expect(store.items[0]?.status).toBe('cancelado')
        expect(store.current?.status).toBe('cancelado')
        expect(store.current?.version).toBe(2)
    })

    it('cancel propaga ApiError em conflito 409', async () => {
        vi.mocked(api.cancelContract).mockRejectedValueOnce(new ApiError('conflito', 409))
        const store = useContractsStore()

        await expect(store.cancel(1, 1)).rejects.toBeInstanceOf(ApiError)
    })

    it('loadHistory armazena entradas e meta', async () => {
        vi.mocked(api.listContractHistory).mockResolvedValueOnce({
            data: [
                {
                    id: 1,
                    contract_id: 5,
                    evento: 'created',
                    payload: { source: 'test' },
                    usuario_id: null,
                    created_at: '2026-05-16T10:00:00+00:00',
                },
            ],
            meta: {
                current_page: 1,
                last_page: 1,
                per_page: 15,
                total: 1,
                from: 1,
                to: 1,
            },
        })
        const store = useContractsStore()

        await store.loadHistory(5)

        expect(store.history).toHaveLength(1)
        expect(store.historyStatus).toBe('ready')
        expect(store.historyMeta.total).toBe(1)
    })

    it('remove tira da lista e zera current quando coincide', async () => {
        vi.mocked(api.deleteContract).mockResolvedValueOnce()
        const store = useContractsStore()
        store.items = [makeContract({ id: 1 }), makeContract({ id: 2 })]
        store.meta = { ...store.meta, total: 2 }
        store.current = makeContract({ id: 1 })

        await store.remove(1)

        expect(store.items).toHaveLength(1)
        expect(store.current).toBeNull()
        expect(store.meta.total).toBe(1)
    })
})
