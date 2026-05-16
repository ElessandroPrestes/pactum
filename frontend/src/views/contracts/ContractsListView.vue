<script setup lang="ts">
import { computed, onMounted, reactive, ref, watch } from 'vue'
import { RouterLink, useRouter } from 'vue-router'
import {
    AppBadge,
    AppButton,
    AppInput,
    AppPagination,
    AppSelect,
    AppSkeleton,
} from '@/components/ui'
import { useContractsStore } from '@/stores/contracts'
import { useToastStore } from '@/stores/toast'
import { ApiError } from '@/lib/http'
import { listClients } from '@/api/clients'
import { formatBRL } from '@/utils/currency'
import type { Client } from '@/types/client'
import type { ContractStatus } from '@/types/contract'

const router = useRouter()
const store = useContractsStore()
const toasts = useToastStore()

interface FilterForm {
    client_id: string
    status: ContractStatus | ''
    data_inicio: string
}

const filterForm = reactive<FilterForm>({
    client_id: store.filters.client_id ? String(store.filters.client_id) : '',
    status: (store.filters.status as ContractStatus | '') ?? '',
    data_inicio: store.filters.data_inicio ?? '',
})

const statusOptions = [
    { value: 'ativo', label: 'Ativo' },
    { value: 'cancelado', label: 'Cancelado' },
] as const

const clientOptions = ref<{ value: string; label: string }[]>([])
const loadingClients = ref(false)

async function loadClientOptions(): Promise<void> {
    loadingClients.value = true
    try {
        const response = await listClients({ per_page: 100 })
        clientOptions.value = response.data.map((client: Client) => ({
            value: String(client.id),
            label: client.nome,
        }))
    } catch {
        clientOptions.value = []
    } finally {
        loadingClients.value = false
    }
}

const loading = computed(() => store.status === 'loading')
const showEmpty = computed(() => store.status === 'ready' && !store.hasResults)
const showError = computed(() => store.status === 'error')

let debounceHandle: ReturnType<typeof setTimeout> | null = null

async function reload(): Promise<void> {
    try {
        await store.fetch()
    } catch (error) {
        toasts.error(
            'Falha ao carregar contratos',
            error instanceof ApiError ? error.message : undefined,
        )
    }
}

function scheduleReload(): void {
    if (debounceHandle) {
        clearTimeout(debounceHandle)
    }
    debounceHandle = setTimeout(() => {
        const clientIdNum = filterForm.client_id ? Number(filterForm.client_id) : undefined
        store.applyFilters({
            client_id: clientIdNum,
            status: filterForm.status || undefined,
            data_inicio: filterForm.data_inicio || undefined,
        })
        void reload()
    }, 300)
}

function statusTone(status: ContractStatus): 'success' | 'neutral' {
    return status === 'ativo' ? 'success' : 'neutral'
}

function goToDetail(id: number): void {
    void router.push({ name: 'contracts-show', params: { id: String(id) } })
}

function clientName(contract: { client_id: number; client?: Client }): string {
    if (contract.client?.nome) {
        return contract.client.nome
    }
    const match = clientOptions.value.find((option) => option.value === String(contract.client_id))
    return match?.label ?? `#${contract.client_id}`
}

function totalForRow(contract: { total_calculado?: string }): string {
    return contract.total_calculado ? formatBRL(contract.total_calculado) : '—'
}

onMounted(() => {
    void loadClientOptions()
    void reload()
})

watch(
    () => [filterForm.client_id, filterForm.status, filterForm.data_inicio] as const,
    () => scheduleReload(),
)
</script>

<template>
    <section class="mx-auto flex max-w-6xl flex-col gap-6">
        <header class="flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">
            <div class="flex flex-col gap-1">
                <h2 class="text-2xl font-semibold text-ink">Contratos</h2>
                <p class="text-sm text-ink-muted">
                    Contratos firmados com clientes e seus itens recorrentes.
                </p>
            </div>
            <RouterLink :to="{ name: 'contracts-create' }" class="contents">
                <AppButton>Novo contrato</AppButton>
            </RouterLink>
        </header>

        <div class="pactum-card p-4">
            <div class="grid gap-3 md:grid-cols-3">
                <AppSelect
                    v-model="filterForm.client_id"
                    label="Cliente"
                    placeholder="Todos os clientes"
                    :options="clientOptions"
                    :disabled="loadingClients"
                />
                <AppSelect
                    v-model="filterForm.status"
                    label="Status"
                    placeholder="Todos"
                    :options="statusOptions"
                />
                <AppInput
                    v-model="filterForm.data_inicio"
                    label="Inicio a partir de"
                    type="date"
                    autocomplete="off"
                />
            </div>
        </div>

        <div class="pactum-card overflow-hidden">
            <div
                v-if="loading"
                class="flex flex-col gap-3 px-6 py-6"
                role="status"
                aria-live="polite"
                aria-busy="true"
            >
                <span class="sr-only">Carregando contratos...</span>
                <div v-for="row in 4" :key="row" class="grid grid-cols-12 items-center gap-3">
                    <AppSkeleton class="col-span-2 h-3" />
                    <AppSkeleton class="col-span-3 h-4" />
                    <AppSkeleton class="col-span-2 h-3" />
                    <AppSkeleton class="col-span-2 h-3" />
                    <AppSkeleton class="col-span-3 h-5" rounded="9999px" />
                </div>
            </div>

            <div
                v-else-if="showError"
                class="px-6 py-12 text-center text-sm text-danger-700"
                role="alert"
            >
                <p class="font-medium">Nao foi possivel carregar a lista.</p>
                <p class="mt-1 text-ink-muted">{{ store.errorMessage }}</p>
                <AppButton class="mt-4" variant="secondary" size="sm" @click="reload">
                    Tentar novamente
                </AppButton>
            </div>

            <div
                v-else-if="showEmpty"
                class="flex flex-col items-center gap-3 px-6 py-12 text-center"
            >
                <p class="text-base font-medium text-ink">Nenhum contrato encontrado</p>
                <p class="max-w-md text-sm text-ink-muted">
                    Ajuste os filtros ou cadastre um novo contrato para comecar.
                </p>
                <RouterLink :to="{ name: 'contracts-create' }" class="contents">
                    <AppButton size="sm">Cadastrar contrato</AppButton>
                </RouterLink>
            </div>

            <div v-else class="overflow-x-auto">
                <table class="min-w-full divide-y divide-border text-sm">
                    <thead
                        class="bg-surface-muted text-left text-xs uppercase tracking-wider text-ink-subtle"
                    >
                        <tr>
                            <th scope="col" class="px-4 py-3 font-medium">Contrato</th>
                            <th scope="col" class="px-4 py-3 font-medium">Cliente</th>
                            <th scope="col" class="px-4 py-3 font-medium">Inicio</th>
                            <th scope="col" class="px-4 py-3 font-medium">Fim</th>
                            <th scope="col" class="px-4 py-3 text-right font-medium">Total</th>
                            <th scope="col" class="px-4 py-3 font-medium">Status</th>
                            <th scope="col" class="px-4 py-3 text-right font-medium">Acoes</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-border bg-surface">
                        <tr
                            v-for="contract in store.items"
                            :key="contract.id"
                            class="hover:bg-surface-muted/60"
                        >
                            <td class="px-4 py-3 font-mono text-xs text-ink-muted">
                                #{{ contract.id }}
                            </td>
                            <td class="px-4 py-3 font-medium text-ink">
                                {{ clientName(contract) }}
                            </td>
                            <td class="px-4 py-3 text-ink-muted">
                                {{ contract.data_inicio }}
                            </td>
                            <td class="px-4 py-3 text-ink-muted">
                                {{ contract.data_fim ?? '—' }}
                            </td>
                            <td class="px-4 py-3 text-right font-mono text-ink-muted">
                                {{ totalForRow(contract) }}
                            </td>
                            <td class="px-4 py-3">
                                <AppBadge :tone="statusTone(contract.status)">
                                    {{ contract.status }}
                                </AppBadge>
                            </td>
                            <td class="px-4 py-3 text-right">
                                <AppButton
                                    variant="ghost"
                                    size="sm"
                                    :aria-label="`Abrir contrato ${contract.id}`"
                                    @click="goToDetail(contract.id)"
                                >
                                    Abrir
                                </AppButton>
                            </td>
                        </tr>
                    </tbody>
                </table>

                <AppPagination
                    :current-page="store.meta.current_page"
                    :last-page="store.meta.last_page"
                    :total="store.meta.total"
                    :from="store.meta.from"
                    :to="store.meta.to"
                    @change="store.changePage"
                />
            </div>
        </div>
    </section>
</template>
