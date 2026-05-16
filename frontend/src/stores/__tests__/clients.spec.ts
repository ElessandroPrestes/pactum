import { beforeEach, describe, expect, it, vi } from 'vitest'
import { createPinia, setActivePinia } from 'pinia'

vi.mock('@/api/clients', () => ({
    listClients: vi.fn(),
    getClient: vi.fn(),
    createClient: vi.fn(),
    updateClient: vi.fn(),
    deleteClient: vi.fn(),
}))

import * as api from '@/api/clients'
import { useClientsStore } from '../clients'
import type { Client } from '@/types/client'

function makeClient(overrides: Partial<Client> = {}): Client {
    return {
        id: 1,
        nome: 'Acme Ltda',
        documento: '12345678000190',
        tipo_documento: 'cnpj',
        email: 'contato@acme.com',
        status: 'ativo',
        created_at: '2026-05-16T10:00:00+00:00',
        updated_at: '2026-05-16T10:00:00+00:00',
        ...overrides,
    }
}

function pagedResponse(items: Client[], total = items.length) {
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

describe('useClientsStore', () => {
    beforeEach(() => {
        setActivePinia(createPinia())
        vi.clearAllMocks()
    })

    it('fetch carrega items e meta', async () => {
        vi.mocked(api.listClients).mockResolvedValueOnce(pagedResponse([makeClient()]))
        const store = useClientsStore()

        await store.fetch()

        expect(store.items).toHaveLength(1)
        expect(store.meta.total).toBe(1)
        expect(store.hasResults).toBe(true)
        expect(store.status).toBe('ready')
    })

    it('fetch em erro define status error e propaga', async () => {
        vi.mocked(api.listClients).mockRejectedValueOnce(new Error('falha'))
        const store = useClientsStore()

        await expect(store.fetch()).rejects.toThrow('falha')
        expect(store.status).toBe('error')
        expect(store.errorMessage).toBe('falha')
    })

    it('applyFilters reseta page por padrao', () => {
        const store = useClientsStore()
        store.applyFilters({ page: 4, nome: 'x' }, false)
        expect(store.filters.page).toBe(4)

        store.applyFilters({ nome: 'y' })
        expect(store.filters.page).toBe(1)
        expect(store.filters.nome).toBe('y')
    })

    it('create insere no topo da lista e incrementa total', async () => {
        const created = makeClient({ id: 99, nome: 'Novo' })
        vi.mocked(api.createClient).mockResolvedValueOnce(created)
        const store = useClientsStore()
        store.items = [makeClient({ id: 1, nome: 'Existente' })]
        store.meta = { ...store.meta, total: 1 }

        const result = await store.create({
            nome: 'Novo',
            documento: '12345678901',
            tipo_documento: 'cpf',
            email: 'novo@x.com',
        })

        expect(result.id).toBe(99)
        expect(store.items[0]?.id).toBe(99)
        expect(store.meta.total).toBe(2)
    })

    it('update substitui o item correspondente', async () => {
        const original = makeClient({ id: 5, nome: 'Antigo' })
        const updated = makeClient({ id: 5, nome: 'Renomeado' })
        vi.mocked(api.updateClient).mockResolvedValueOnce(updated)
        const store = useClientsStore()
        store.items = [original]

        const result = await store.update(5, { nome: 'Renomeado' })

        expect(result.nome).toBe('Renomeado')
        expect(store.items[0]?.nome).toBe('Renomeado')
    })

    it('remove tira o item da lista e decrementa total', async () => {
        vi.mocked(api.deleteClient).mockResolvedValueOnce()
        const store = useClientsStore()
        store.items = [makeClient({ id: 1 }), makeClient({ id: 2 })]
        store.meta = { ...store.meta, total: 2 }

        await store.remove(1)

        expect(store.items).toHaveLength(1)
        expect(store.items[0]?.id).toBe(2)
        expect(store.meta.total).toBe(1)
    })

    it('changePage ignora valores fora do intervalo', async () => {
        vi.mocked(api.listClients).mockResolvedValue(pagedResponse([makeClient()]))
        const store = useClientsStore()
        store.meta = { ...store.meta, current_page: 1, last_page: 3 }

        await store.changePage(0)
        await store.changePage(99)
        expect(api.listClients).not.toHaveBeenCalled()

        await store.changePage(2)
        expect(api.listClients).toHaveBeenCalledTimes(1)
        expect(store.filters.page).toBe(2)
    })
})
