import { defineStore } from 'pinia'
import { computed, ref } from 'vue'
import * as api from '@/api/services'
import type {
    Service,
    ServiceCreatePayload,
    ServiceListFilters,
    ServicePaginationMeta,
    ServiceUpdatePayload,
} from '@/types/service'

type Status = 'idle' | 'loading' | 'ready' | 'error'

const DEFAULT_PER_PAGE = 15

const EMPTY_META: ServicePaginationMeta = {
    current_page: 1,
    last_page: 1,
    per_page: DEFAULT_PER_PAGE,
    total: 0,
    from: null,
    to: null,
}

export const useServicesStore = defineStore('services', () => {
    const items = ref<Service[]>([])
    const meta = ref<ServicePaginationMeta>({ ...EMPTY_META })
    const filters = ref<ServiceListFilters>({
        nome: '',
        ativo: undefined,
        page: 1,
        per_page: DEFAULT_PER_PAGE,
    })
    const status = ref<Status>('idle')
    const errorMessage = ref<string | null>(null)

    const hasResults = computed(() => items.value.length > 0)

    function applyFilters(partial: Partial<ServiceListFilters>, resetPage = true): void {
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
            const response = await api.listServices(filters.value)
            items.value = response.data
            meta.value = response.meta
            status.value = 'ready'
        } catch (error) {
            status.value = 'error'
            errorMessage.value =
                error instanceof Error ? error.message : 'Erro ao carregar servicos'
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

    async function create(payload: ServiceCreatePayload): Promise<Service> {
        const created = await api.createService(payload)
        items.value = [created, ...items.value]
        meta.value = { ...meta.value, total: meta.value.total + 1 }
        return created
    }

    async function update(id: number, payload: ServiceUpdatePayload): Promise<Service> {
        const updated = await api.updateService(id, payload)
        items.value = items.value.map((service) => (service.id === id ? updated : service))
        return updated
    }

    async function remove(id: number): Promise<void> {
        await api.deleteService(id)
        items.value = items.value.filter((service) => service.id !== id)
        meta.value = {
            ...meta.value,
            total: Math.max(0, meta.value.total - 1),
        }
    }

    async function getOne(id: number): Promise<Service> {
        return api.getService(id)
    }

    function reset(): void {
        items.value = []
        meta.value = { ...EMPTY_META }
        filters.value = {
            nome: '',
            ativo: undefined,
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
