import { http } from '@/lib/http'
import { generateIdempotencyKey } from '@/utils/idempotency'
import type {
    Contract,
    ContractCreatePayload,
    ContractItem,
    ContractItemPayload,
    ContractListFilters,
    ContractPaginationMeta,
    ContractUpdatePayload,
} from '@/types/contract'
import type {
    ContractHistoryEntry,
    ContractHistoryFilters,
    ContractHistoryPaginationMeta,
} from '@/types/contractHistory'

interface Envelope<T> {
    data: T
}

export interface ContractListResponse {
    data: Contract[]
    meta: ContractPaginationMeta
}

export interface ContractHistoryResponse {
    data: ContractHistoryEntry[]
    meta: ContractHistoryPaginationMeta
}

function buildListParams(filters: ContractListFilters): Record<string, string | number> {
    const params: Record<string, string | number> = {}
    if (filters.client_id) {
        params.client_id = filters.client_id
    }
    if (filters.status) {
        params.status = filters.status
    }
    if (filters.data_inicio) {
        params.data_inicio = filters.data_inicio
    }
    if (filters.page) {
        params.page = filters.page
    }
    if (filters.per_page) {
        params.per_page = filters.per_page
    }
    return params
}

export async function listContracts(filters: ContractListFilters): Promise<ContractListResponse> {
    const response = await http.get<ContractListResponse>('/contracts', {
        params: buildListParams(filters),
    })
    return response.data
}

export async function getContract(id: number): Promise<Contract> {
    const response = await http.get<Envelope<Contract>>(`/contracts/${id}`)
    return response.data.data
}

export async function createContract(payload: ContractCreatePayload): Promise<Contract> {
    const response = await http.post<Envelope<Contract>>('/contracts', payload, {
        headers: { 'Idempotency-Key': generateIdempotencyKey() },
    })
    return response.data.data
}

export async function updateContract(
    id: number,
    payload: ContractUpdatePayload,
): Promise<Contract> {
    const response = await http.put<Envelope<Contract>>(`/contracts/${id}`, payload)
    return response.data.data
}

export async function deleteContract(id: number): Promise<void> {
    await http.delete(`/contracts/${id}`)
}

export async function cancelContract(id: number, version: number): Promise<Contract> {
    const response = await http.post<Envelope<Contract>>(`/contracts/${id}/cancel`, { version })
    return response.data.data
}

export async function addContractItem(
    contractId: number,
    payload: ContractItemPayload,
): Promise<ContractItem> {
    const response = await http.post<Envelope<ContractItem>>(
        `/contracts/${contractId}/items`,
        payload,
        { headers: { 'Idempotency-Key': generateIdempotencyKey() } },
    )
    return response.data.data
}

export async function removeContractItem(contractId: number, itemId: number): Promise<void> {
    await http.delete(`/contracts/${contractId}/items/${itemId}`)
}

export async function listContractHistory(
    contractId: number,
    filters: ContractHistoryFilters,
): Promise<ContractHistoryResponse> {
    const params: Record<string, number> = {}
    if (filters.page) {
        params.page = filters.page
    }
    if (filters.per_page) {
        params.per_page = filters.per_page
    }
    const response = await http.get<ContractHistoryResponse>(`/contracts/${contractId}/history`, {
        params,
    })
    return response.data
}
