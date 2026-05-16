import { http } from '@/lib/http'
import { generateIdempotencyKey } from '@/utils/idempotency'
import type {
    Client,
    ClientCreatePayload,
    ClientListFilters,
    ClientUpdatePayload,
    PaginationMeta,
} from '@/types/client'

interface Envelope<T> {
    data: T
}

export interface ClientListResponse {
    data: Client[]
    meta: PaginationMeta
}

function buildParams(filters: ClientListFilters): Record<string, string | number> {
    const params: Record<string, string | number> = {}
    if (filters.nome) {
        params.nome = filters.nome
    }
    if (filters.documento) {
        params.documento = filters.documento
    }
    if (filters.status) {
        params.status = filters.status
    }
    if (filters.page) {
        params.page = filters.page
    }
    if (filters.per_page) {
        params.per_page = filters.per_page
    }
    return params
}

export async function listClients(filters: ClientListFilters): Promise<ClientListResponse> {
    const response = await http.get<ClientListResponse>('/clients', {
        params: buildParams(filters),
    })
    return response.data
}

export async function getClient(id: number): Promise<Client> {
    const response = await http.get<Envelope<Client>>(`/clients/${id}`)
    return response.data.data
}

export async function createClient(payload: ClientCreatePayload): Promise<Client> {
    const response = await http.post<Envelope<Client>>('/clients', payload, {
        headers: { 'Idempotency-Key': generateIdempotencyKey() },
    })
    return response.data.data
}

export async function updateClient(id: number, payload: ClientUpdatePayload): Promise<Client> {
    const response = await http.put<Envelope<Client>>(`/clients/${id}`, payload)
    return response.data.data
}

export async function deleteClient(id: number): Promise<void> {
    await http.delete(`/clients/${id}`)
}
