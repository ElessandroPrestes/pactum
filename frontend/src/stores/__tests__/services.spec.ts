import { beforeEach, describe, expect, it, vi } from 'vitest'
import { createPinia, setActivePinia } from 'pinia'

vi.mock('@/api/services', () => ({
    listServices: vi.fn(),
    getService: vi.fn(),
    createService: vi.fn(),
    updateService: vi.fn(),
    deleteService: vi.fn(),
}))

import * as api from '@/api/services'
import { useServicesStore } from '../services'
import type { Service } from '@/types/service'

function makeService(overrides: Partial<Service> = {}): Service {
    return {
        id: 1,
        nome: 'Sustentacao mensal',
        valor_base_mensal: '199.90',
        ativo: true,
        created_at: '2026-05-16T10:00:00+00:00',
        updated_at: '2026-05-16T10:00:00+00:00',
        ...overrides,
    }
}

function pagedResponse(items: Service[], total = items.length) {
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

describe('useServicesStore', () => {
    beforeEach(() => {
        setActivePinia(createPinia())
        vi.clearAllMocks()
    })

    it('fetch carrega items e meta', async () => {
        vi.mocked(api.listServices).mockResolvedValueOnce(pagedResponse([makeService()]))
        const store = useServicesStore()

        await store.fetch()

        expect(store.items).toHaveLength(1)
        expect(store.meta.total).toBe(1)
        expect(store.hasResults).toBe(true)
        expect(store.status).toBe('ready')
    })

    it('applyFilters propaga ativo booleano', () => {
        const store = useServicesStore()
        store.applyFilters({ ativo: true })
        expect(store.filters.ativo).toBe(true)

        store.applyFilters({ ativo: undefined })
        expect(store.filters.ativo).toBeUndefined()
    })

    it('create insere no topo e incrementa total', async () => {
        const created = makeService({ id: 99, nome: 'Novo servico' })
        vi.mocked(api.createService).mockResolvedValueOnce(created)
        const store = useServicesStore()
        store.items = [makeService({ id: 1, nome: 'Existente' })]
        store.meta = { ...store.meta, total: 1 }

        const result = await store.create({
            nome: 'Novo servico',
            valor_base_mensal: '50.00',
        })

        expect(result.id).toBe(99)
        expect(store.items[0]?.id).toBe(99)
        expect(store.meta.total).toBe(2)
    })

    it('update substitui o item correspondente', async () => {
        const updated = makeService({ id: 5, nome: 'Renomeado' })
        vi.mocked(api.updateService).mockResolvedValueOnce(updated)
        const store = useServicesStore()
        store.items = [makeService({ id: 5, nome: 'Antigo' })]

        const result = await store.update(5, { nome: 'Renomeado' })

        expect(result.nome).toBe('Renomeado')
        expect(store.items[0]?.nome).toBe('Renomeado')
    })

    it('remove decrementa total e tira o item', async () => {
        vi.mocked(api.deleteService).mockResolvedValueOnce()
        const store = useServicesStore()
        store.items = [makeService({ id: 1 }), makeService({ id: 2 })]
        store.meta = { ...store.meta, total: 2 }

        await store.remove(1)

        expect(store.items).toHaveLength(1)
        expect(store.meta.total).toBe(1)
    })

    it('fetch em erro define status error', async () => {
        vi.mocked(api.listServices).mockRejectedValueOnce(new Error('rede'))
        const store = useServicesStore()

        await expect(store.fetch()).rejects.toThrow('rede')
        expect(store.status).toBe('error')
    })
})
