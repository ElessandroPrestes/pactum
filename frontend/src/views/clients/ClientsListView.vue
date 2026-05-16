<script setup lang="ts">
import { computed, onMounted, reactive, watch } from 'vue'
import { RouterLink, useRouter } from 'vue-router'
import { AppBadge, AppButton, AppInput, AppPagination, AppSelect } from '@/components/ui'
import { useClientsStore } from '@/stores/clients'
import { useToastStore } from '@/stores/toast'
import { ApiError } from '@/lib/http'
import { formatDocumento } from '@/utils/documento'
import type { ClientStatus } from '@/types/client'

const router = useRouter()
const store = useClientsStore()
const toasts = useToastStore()

interface FilterForm {
    nome: string
    documento: string
    status: ClientStatus | ''
}

const filterForm = reactive<FilterForm>({
    nome: store.filters.nome ?? '',
    documento: store.filters.documento ?? '',
    status: (store.filters.status as ClientStatus | '') ?? '',
})

const statusOptions = [
    { value: 'ativo', label: 'Ativo' },
    { value: 'inativo', label: 'Inativo' },
] as const

const loading = computed(() => store.status === 'loading')
const showEmpty = computed(() => store.status === 'ready' && !store.hasResults)
const showError = computed(() => store.status === 'error')

let debounceHandle: ReturnType<typeof setTimeout> | null = null

async function reload(): Promise<void> {
    try {
        await store.fetch()
    } catch (error) {
        toasts.error(
            'Falha ao carregar clientes',
            error instanceof ApiError ? error.message : undefined,
        )
    }
}

function scheduleReload(): void {
    if (debounceHandle) {
        clearTimeout(debounceHandle)
    }
    debounceHandle = setTimeout(() => {
        store.applyFilters({
            nome: filterForm.nome.trim() || undefined,
            documento: filterForm.documento.trim() || undefined,
            status: filterForm.status || undefined,
        })
        void reload()
    }, 300)
}

function statusTone(status: ClientStatus): 'success' | 'neutral' {
    return status === 'ativo' ? 'success' : 'neutral'
}

function goToEdit(id: number): void {
    void router.push({ name: 'clients-edit', params: { id: String(id) } })
}

onMounted(() => {
    void reload()
})

watch(
    () => [filterForm.nome, filterForm.documento, filterForm.status] as const,
    () => scheduleReload(),
)
</script>

<template>
    <section class="mx-auto flex max-w-6xl flex-col gap-6">
        <header class="flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">
            <div class="flex flex-col gap-1">
                <h2 class="text-2xl font-semibold text-ink">Clientes</h2>
                <p class="text-sm text-ink-muted">
                    Lista de clientes cadastrados. Use os filtros para refinar a busca.
                </p>
            </div>
            <RouterLink :to="{ name: 'clients-create' }" class="contents">
                <AppButton>Novo cliente</AppButton>
            </RouterLink>
        </header>

        <div class="pactum-card p-4">
            <div class="grid gap-3 md:grid-cols-3">
                <AppInput
                    v-model="filterForm.nome"
                    label="Nome"
                    placeholder="Buscar por nome"
                    autocomplete="off"
                />
                <AppInput
                    v-model="filterForm.documento"
                    label="Documento"
                    placeholder="Apenas digitos"
                    inputmode="numeric"
                    autocomplete="off"
                />
                <AppSelect
                    v-model="filterForm.status"
                    label="Status"
                    placeholder="Todos"
                    :options="statusOptions"
                />
            </div>
        </div>

        <div class="pactum-card overflow-hidden">
            <div
                v-if="loading"
                class="flex items-center justify-center px-6 py-12 text-sm text-ink-muted"
                role="status"
                aria-live="polite"
            >
                Carregando clientes...
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
                <p class="text-base font-medium text-ink">Nenhum cliente encontrado</p>
                <p class="max-w-md text-sm text-ink-muted">
                    Ajuste os filtros ou cadastre um novo cliente para comecar.
                </p>
                <RouterLink :to="{ name: 'clients-create' }" class="contents">
                    <AppButton size="sm">Cadastrar cliente</AppButton>
                </RouterLink>
            </div>

            <div v-else class="overflow-x-auto">
                <table class="min-w-full divide-y divide-border text-sm">
                    <thead
                        class="bg-surface-muted text-left text-xs uppercase tracking-wider text-ink-subtle"
                    >
                        <tr>
                            <th scope="col" class="px-4 py-3 font-medium">Nome</th>
                            <th scope="col" class="px-4 py-3 font-medium">Documento</th>
                            <th scope="col" class="px-4 py-3 font-medium">Email</th>
                            <th scope="col" class="px-4 py-3 font-medium">Status</th>
                            <th scope="col" class="px-4 py-3 text-right font-medium">Acoes</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-border bg-surface">
                        <tr
                            v-for="client in store.items"
                            :key="client.id"
                            class="hover:bg-surface-muted/60"
                        >
                            <td class="px-4 py-3 font-medium text-ink">
                                {{ client.nome }}
                            </td>
                            <td class="px-4 py-3 font-mono text-xs text-ink-muted">
                                {{ formatDocumento(client.documento, client.tipo_documento) }}
                            </td>
                            <td class="px-4 py-3 text-ink-muted">
                                {{ client.email }}
                            </td>
                            <td class="px-4 py-3">
                                <AppBadge :tone="statusTone(client.status)">
                                    {{ client.status }}
                                </AppBadge>
                            </td>
                            <td class="px-4 py-3 text-right">
                                <AppButton
                                    variant="ghost"
                                    size="sm"
                                    :aria-label="`Editar cliente ${client.nome}`"
                                    @click="goToEdit(client.id)"
                                >
                                    Editar
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
