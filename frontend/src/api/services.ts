import { http } from '@/lib/http'
import type {
    Service,
    ServiceCreatePayload,
    ServiceListFilters,
    ServicePaginationMeta,
    ServiceUpdatePayload,
} from '@/types/service'

interface Envelope<T> {
    data: T
}

export interface ServiceListResponse {
    data: Service[]
    meta: ServicePaginationMeta
}

function buildParams(filters: ServiceListFilters): Record<string, string | number> {
    const params: Record<string, string | number> = {}
    if (filters.nome) {
        params.nome = filters.nome
    }
    if (typeof filters.ativo === 'boolean') {
        params.ativo = filters.ativo ? 1 : 0
    }
    if (filters.page) {
        params.page = filters.page
    }
    if (filters.per_page) {
        params.per_page = filters.per_page
    }
    return params
}

export async function listServices(filters: ServiceListFilters): Promise<ServiceListResponse> {
    const response = await http.get<ServiceListResponse>('/services', {
        params: buildParams(filters),
    })
    return response.data
}

export async function getService(id: number): Promise<Service> {
    const response = await http.get<Envelope<Service>>(`/services/${id}`)
    return response.data.data
}

export async function createService(payload: ServiceCreatePayload): Promise<Service> {
    const response = await http.post<Envelope<Service>>('/services', payload)
    return response.data.data
}

export async function updateService(id: number, payload: ServiceUpdatePayload): Promise<Service> {
    const response = await http.put<Envelope<Service>>(`/services/${id}`, payload)
    return response.data.data
}

export async function deleteService(id: number): Promise<void> {
    await http.delete(`/services/${id}`)
}
