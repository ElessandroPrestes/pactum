import { defineStore } from 'pinia'
import { computed, ref } from 'vue'
import * as api from '@/api/contracts'
import type {
    Contract,
    ContractCreatePayload,
    ContractItem,
    ContractItemPayload,
    ContractListFilters,
    ContractPaginationMeta,
    ContractUpdatePayload,
} from '@/types/contract'
import type { ContractHistoryEntry, ContractHistoryPaginationMeta } from '@/types/contractHistory'

type Status = 'idle' | 'loading' | 'ready' | 'error'

const DEFAULT_PER_PAGE = 15

const EMPTY_META: ContractPaginationMeta = {
    current_page: 1,
    last_page: 1,
    per_page: DEFAULT_PER_PAGE,
    total: 0,
    from: null,
    to: null,
}

const EMPTY_HISTORY_META: ContractHistoryPaginationMeta = { ...EMPTY_META }

export const useContractsStore = defineStore('contracts', () => {
    const items = ref<Contract[]>([])
    const meta = ref<ContractPaginationMeta>({ ...EMPTY_META })
    const filters = ref<ContractListFilters>({
        client_id: '',
        status: '',
        data_inicio: '',
        page: 1,
        per_page: DEFAULT_PER_PAGE,
    })
    const status = ref<Status>('idle')
    const errorMessage = ref<string | null>(null)

    const current = ref<Contract | null>(null)
    const currentStatus = ref<Status>('idle')
    const currentError = ref<string | null>(null)

    const history = ref<ContractHistoryEntry[]>([])
    const historyMeta = ref<ContractHistoryPaginationMeta>({ ...EMPTY_HISTORY_META })
    const historyStatus = ref<Status>('idle')

    const hasResults = computed(() => items.value.length > 0)

    function applyFilters(partial: Partial<ContractListFilters>, resetPage = true): void {
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
            const response = await api.listContracts(filters.value)
            items.value = response.data
            meta.value = response.meta
            status.value = 'ready'
        } catch (error) {
            status.value = 'error'
            errorMessage.value =
                error instanceof Error ? error.message : 'Erro ao carregar contratos'
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

    async function loadOne(id: number): Promise<Contract> {
        currentStatus.value = 'loading'
        currentError.value = null
        try {
            const contract = await api.getContract(id)
            current.value = contract
            currentStatus.value = 'ready'
            return contract
        } catch (error) {
            currentStatus.value = 'error'
            currentError.value =
                error instanceof Error ? error.message : 'Erro ao carregar contrato'
            throw error
        }
    }

    async function create(payload: ContractCreatePayload): Promise<Contract> {
        const created = await api.createContract(payload)
        items.value = [created, ...items.value]
        meta.value = { ...meta.value, total: meta.value.total + 1 }
        return created
    }

    async function update(id: number, payload: ContractUpdatePayload): Promise<Contract> {
        const updated = await api.updateContract(id, payload)
        items.value = items.value.map((contract) => (contract.id === id ? updated : contract))
        if (current.value?.id === id) {
            current.value = updated
        }
        return updated
    }

    async function remove(id: number): Promise<void> {
        await api.deleteContract(id)
        items.value = items.value.filter((contract) => contract.id !== id)
        meta.value = {
            ...meta.value,
            total: Math.max(0, meta.value.total - 1),
        }
        if (current.value?.id === id) {
            current.value = null
        }
    }

    async function cancel(id: number, version: number): Promise<Contract> {
        const cancelled = await api.cancelContract(id, version)
        items.value = items.value.map((contract) => (contract.id === id ? cancelled : contract))
        if (current.value?.id === id) {
            current.value = cancelled
        }
        return cancelled
    }

    async function addItem(
        contractId: number,
        payload: ContractItemPayload,
    ): Promise<ContractItem> {
        const item = await api.addContractItem(contractId, payload)
        if (current.value?.id === contractId) {
            const itens = current.value.itens ? [...current.value.itens, item] : [item]
            current.value = { ...current.value, itens }
        }
        return item
    }

    async function removeItem(contractId: number, itemId: number): Promise<void> {
        await api.removeContractItem(contractId, itemId)
        if (current.value?.id === contractId && current.value.itens) {
            current.value = {
                ...current.value,
                itens: current.value.itens.filter((item) => item.id !== itemId),
            }
        }
    }

    async function loadHistory(contractId: number, page = 1): Promise<void> {
        historyStatus.value = 'loading'
        try {
            const response = await api.listContractHistory(contractId, {
                page,
                per_page: DEFAULT_PER_PAGE,
            })
            history.value = response.data
            historyMeta.value = response.meta
            historyStatus.value = 'ready'
        } catch (error) {
            historyStatus.value = 'error'
            throw error
        }
    }

    function clearCurrent(): void {
        current.value = null
        currentStatus.value = 'idle'
        currentError.value = null
        history.value = []
        historyMeta.value = { ...EMPTY_HISTORY_META }
        historyStatus.value = 'idle'
    }

    function reset(): void {
        items.value = []
        meta.value = { ...EMPTY_META }
        filters.value = {
            client_id: '',
            status: '',
            data_inicio: '',
            page: 1,
            per_page: DEFAULT_PER_PAGE,
        }
        status.value = 'idle'
        errorMessage.value = null
        clearCurrent()
    }

    return {
        items,
        meta,
        filters,
        status,
        errorMessage,
        hasResults,
        current,
        currentStatus,
        currentError,
        history,
        historyMeta,
        historyStatus,
        applyFilters,
        fetch,
        changePage,
        loadOne,
        create,
        update,
        remove,
        cancel,
        addItem,
        removeItem,
        loadHistory,
        clearCurrent,
        reset,
    }
})
