import type { PaginationMeta } from './client'

export interface ContractHistoryEntry {
    id: number
    contract_id: number
    evento: string
    payload: Record<string, unknown> | null
    usuario_id: number | null
    created_at: string
}

export interface ContractHistoryFilters {
    page?: number
    per_page?: number
}

export type ContractHistoryPaginationMeta = PaginationMeta
