export type DocumentType = 'cpf' | 'cnpj'
export type ClientStatus = 'ativo' | 'inativo'

export interface Client {
    id: number
    nome: string
    documento: string
    tipo_documento: DocumentType
    email: string
    status: ClientStatus
    created_at: string | null
    updated_at: string | null
}

export interface ClientListFilters {
    nome?: string
    documento?: string
    status?: ClientStatus | ''
    page?: number
    per_page?: number
}

export interface PaginationMeta {
    current_page: number
    last_page: number
    per_page: number
    total: number
    from: number | null
    to: number | null
}

export interface ClientCreatePayload {
    nome: string
    documento: string
    tipo_documento: DocumentType
    email: string
}

export interface ClientUpdatePayload {
    nome?: string
    documento?: string
    tipo_documento?: DocumentType
    email?: string
    status?: ClientStatus
}
