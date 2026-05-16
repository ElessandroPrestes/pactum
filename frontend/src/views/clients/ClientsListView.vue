<script setup lang="ts">
import { computed, onMounted, reactive, ref, watch } from 'vue'
import { RouterLink, useRouter } from 'vue-router'
import {
    AppBadge,
    AppButton,
    AppInput,
    AppModal,
    AppPagination,
    AppSelect,
    AppSkeleton,
} from '@/components/ui'
import { useClientsStore } from '@/stores/clients'
import { useToastStore } from '@/stores/toast'
import { ApiError } from '@/lib/http'
import { formatDocumento } from '@/utils/documento'
import type { Client, ClientStatus } from '@/types/client'

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

const targetToDelete = ref<Client | null>(null)
const deleting = ref(false)

function askDelete(client: Client): void {
    targetToDelete.value = client
}

function cancelDelete(): void {
    if (deleting.value) {
        return
    }
    targetToDelete.value = null
}

async function confirmDelete(): Promise<void> {
    const target = targetToDelete.value
    if (!target) {
        return
    }
    deleting.value = true
    try {
        await store.remove(target.id)
        toasts.success('Cliente excluido', `${target.nome} foi removido com sucesso.`)
        targetToDelete.value = null
        if (store.items.length === 0 && store.meta.current_page > 1) {
            await store.changePage(store.meta.current_page - 1)
        }
    } catch (error) {
        if (error instanceof ApiError) {
            toasts.error('Falha ao excluir', error.message)
        } else {
            toasts.error('Falha ao excluir', 'Tente novamente em instantes.')
        }
    } finally {
        deleting.value = false
    }
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
                class="flex flex-col gap-3 px-6 py-6"
                role="status"
                aria-live="polite"
                aria-busy="true"
            >
                <span class="sr-only">Carregando clientes...</span>
                <div v-for="row in 4" :key="row" class="grid grid-cols-12 items-center gap-3">
                    <AppSkeleton class="col-span-4 h-4" />
                    <AppSkeleton class="col-span-3 h-3" />
                    <AppSkeleton class="col-span-3 h-3" />
                    <AppSkeleton class="col-span-2 h-5" rounded="9999px" />
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
                                <div class="flex justify-end gap-1">
                                    <AppButton
                                        variant="ghost"
                                        size="sm"
                                        :aria-label="`Editar cliente ${client.nome}`"
                                        @click="goToEdit(client.id)"
                                    >
                                        Editar
                                    </AppButton>
                                    <AppButton
                                        variant="ghost"
                                        size="sm"
                                        :aria-label="`Excluir cliente ${client.nome}`"
                                        @click="askDelete(client)"
                                    >
                                        Excluir
                                    </AppButton>
                                </div>
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

        <AppModal
            :open="targetToDelete !== null"
            title="Excluir cliente"
            :description="
                targetToDelete
                    ? `Confirmar a exclusao de ${targetToDelete.nome}? Esta acao nao pode ser desfeita pela interface.`
                    : ''
            "
            size="sm"
            :close-on-backdrop="!deleting"
            @close="cancelDelete"
        >
            <p class="text-sm text-ink-muted">
                O cliente sera removido logicamente (soft delete) e contratos vinculados continuam
                inalterados.
            </p>
            <template #footer>
                <AppButton variant="secondary" :disabled="deleting" @click="cancelDelete">
                    Cancelar
                </AppButton>
                <AppButton variant="danger" :loading="deleting" @click="confirmDelete">
                    Excluir
                </AppButton>
            </template>
        </AppModal>
    </section>
</template>
