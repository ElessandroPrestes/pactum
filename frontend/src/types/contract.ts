import type { Client, PaginationMeta } from './client'
import type { Service } from './service'

export type ContractStatus = 'ativo' | 'cancelado'

export interface ContractItem {
    id: number
    contract_id: number
    service_id: number
    quantidade: number
    valor_unitario: string
    service?: Service
    created_at: string | null
    updated_at: string | null
}

export interface Contract {
    id: number
    client_id: number
    data_inicio: string
    data_fim: string | null
    status: ContractStatus
    version: number
    client?: Client
    itens?: ContractItem[]
    total_calculado?: string
    created_at: string | null
    updated_at: string | null
}

export interface ContractListFilters {
    client_id?: number | ''
    status?: ContractStatus | ''
    data_inicio?: string
    page?: number
    per_page?: number
}

export interface ContractItemPayload {
    service_id: number
    quantidade: number
    valor_unitario: string
}

export interface ContractCreatePayload {
    client_id: number
    data_inicio: string
    data_fim?: string | null
    itens?: ContractItemPayload[]
}

export interface ContractUpdatePayload {
    version: number
    data_inicio?: string
    data_fim?: string | null
}

export type ContractPaginationMeta = PaginationMeta
