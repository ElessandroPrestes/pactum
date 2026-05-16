import type { PaginationMeta } from './client'

export interface Service {
    id: number
    nome: string
    valor_base_mensal: string
    ativo: boolean
    created_at: string | null
    updated_at: string | null
}

export interface ServiceListFilters {
    nome?: string
    ativo?: boolean
    page?: number
    per_page?: number
}

export interface ServiceCreatePayload {
    nome: string
    valor_base_mensal: string
    ativo?: boolean
}

export interface ServiceUpdatePayload {
    nome?: string
    valor_base_mensal?: string
    ativo?: boolean
}

export type ServicePaginationMeta = PaginationMeta
