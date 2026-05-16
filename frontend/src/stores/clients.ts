import { defineStore } from 'pinia'
import { computed, ref } from 'vue'
import * as api from '@/api/clients'
import type {
    Client,
    ClientCreatePayload,
    ClientListFilters,
    ClientUpdatePayload,
    PaginationMeta,
} from '@/types/client'

type Status = 'idle' | 'loading' | 'ready' | 'error'

const DEFAULT_PER_PAGE = 15

const EMPTY_META: PaginationMeta = {
    current_page: 1,
    last_page: 1,
    per_page: DEFAULT_PER_PAGE,
    total: 0,
    from: null,
    to: null,
}

export const useClientsStore = defineStore('clients', () => {
    const items = ref<Client[]>([])
    const meta = ref<PaginationMeta>({ ...EMPTY_META })
    const filters = ref<ClientListFilters>({
        nome: '',
        documento: '',
        status: '',
        page: 1,
        per_page: DEFAULT_PER_PAGE,
    })
    const status = ref<Status>('idle')
    const errorMessage = ref<string | null>(null)

    const hasResults = computed(() => items.value.length > 0)
    const totalPages = computed(() => meta.value.last_page)

    function applyFilters(partial: Partial<ClientListFilters>, resetPage = true): void {
        filters.value = {
            ...filters.value,
            ...partial,
            page: resetPage ? 1 : (partial.page ?? filters.value.page),
        }
    }

    async function fetch(): Promise<void> {
        status.value = 'loading'
        errorMessage.value = null
        try {
            const response = await api.listClients(filters.value)
            items.value = response.data
            meta.value = response.meta
            status.value = 'ready'
        } catch (error) {
            status.value = 'error'
            errorMessage.value =
                error instanceof Error ? error.message : 'Erro ao carregar clientes'
            throw error
        }
    }

    async function changePage(page: number): Promise<void> {
        if (page < 1 || page > meta.value.last_page) {
            return
        }
        filters.value = { ...filters.value, page }
        await fetch()
    }

    async function create(payload: ClientCreatePayload): Promise<Client> {
        const created = await api.createClient(payload)
        items.value = [created, ...items.value]
        meta.value = { ...meta.value, total: meta.value.total + 1 }
        return created
    }

    async function update(id: number, payload: ClientUpdatePayload): Promise<Client> {
        const updated = await api.updateClient(id, payload)
        items.value = items.value.map((client) => (client.id === id ? updated : client))
        return updated
    }

    async function remove(id: number): Promise<void> {
        await api.deleteClient(id)
        items.value = items.value.filter((client) => client.id !== id)
        meta.value = {
            ...meta.value,
            total: Math.max(0, meta.value.total - 1),
        }
    }

    async function getOne(id: number): Promise<Client> {
        return api.getClient(id)
    }

    function reset(): void {
        items.value = []
        meta.value = { ...EMPTY_META }
        filters.value = {
            nome: '',
            documento: '',
            status: '',
            page: 1,
            per_page: DEFAULT_PER_PAGE,
        }
        status.value = 'idle'
        errorMessage.value = null
    }

    return {
        items,
        meta,
        filters,
        status,
        errorMessage,
        hasResults,
        totalPages,
        applyFilters,
        fetch,
        changePage,
        create,
        update,
        remove,
        getOne,
        reset,
    }
})
