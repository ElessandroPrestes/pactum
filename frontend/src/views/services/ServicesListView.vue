<script setup lang="ts">
import { computed, onMounted, reactive, ref, watch } from 'vue'
import { RouterLink, useRouter } from 'vue-router'
import { AppBadge, AppButton, AppInput, AppModal, AppPagination, AppSelect } from '@/components/ui'
import { useServicesStore } from '@/stores/services'
import { useToastStore } from '@/stores/toast'
import { ApiError } from '@/lib/http'
import { formatBRL } from '@/utils/currency'
import type { Service } from '@/types/service'

const router = useRouter()
const store = useServicesStore()
const toasts = useToastStore()

type AtivoFilter = '' | 'true' | 'false'

interface FilterForm {
    nome: string
    ativo: AtivoFilter
}

const filterForm = reactive<FilterForm>({
    nome: store.filters.nome ?? '',
    ativo: store.filters.ativo === true ? 'true' : store.filters.ativo === false ? 'false' : '',
})

const ativoOptions = [
    { value: 'true', label: 'Ativos' },
    { value: 'false', label: 'Inativos' },
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
            'Falha ao carregar servicos',
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
            ativo: filterForm.ativo === '' ? undefined : filterForm.ativo === 'true',
        })
        void reload()
    }, 300)
}

function goToEdit(id: number): void {
    void router.push({ name: 'services-edit', params: { id: String(id) } })
}

const targetToDelete = ref<Service | null>(null)
const deleting = ref(false)

function askDelete(service: Service): void {
    targetToDelete.value = service
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
        toasts.success('Servico excluido', `${target.nome} foi removido com sucesso.`)
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
    () => [filterForm.nome, filterForm.ativo] as const,
    () => scheduleReload(),
)
</script>

<template>
    <section class="mx-auto flex max-w-6xl flex-col gap-6">
        <header class="flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">
            <div class="flex flex-col gap-1">
                <h2 class="text-2xl font-semibold text-ink">Servicos</h2>
                <p class="text-sm text-ink-muted">
                    Catalogo de servicos com valor base e status de atividade.
                </p>
            </div>
            <RouterLink :to="{ name: 'services-create' }" class="contents">
                <AppButton>Novo servico</AppButton>
            </RouterLink>
        </header>

        <div class="pactum-card p-4">
            <div class="grid gap-3 md:grid-cols-2">
                <AppInput
                    v-model="filterForm.nome"
                    label="Nome"
                    placeholder="Buscar por nome"
                    autocomplete="off"
                />
                <AppSelect
                    v-model="filterForm.ativo"
                    label="Status"
                    placeholder="Todos"
                    :options="ativoOptions"
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
                Carregando servicos...
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
                <p class="text-base font-medium text-ink">Nenhum servico encontrado</p>
                <p class="max-w-md text-sm text-ink-muted">
                    Ajuste os filtros ou cadastre um novo servico para comecar.
                </p>
                <RouterLink :to="{ name: 'services-create' }" class="contents">
                    <AppButton size="sm">Cadastrar servico</AppButton>
                </RouterLink>
            </div>

            <div v-else class="overflow-x-auto">
                <table class="min-w-full divide-y divide-border text-sm">
                    <thead
                        class="bg-surface-muted text-left text-xs uppercase tracking-wider text-ink-subtle"
                    >
                        <tr>
                            <th scope="col" class="px-4 py-3 font-medium">Nome</th>
                            <th scope="col" class="px-4 py-3 text-right font-medium">Valor base</th>
                            <th scope="col" class="px-4 py-3 font-medium">Status</th>
                            <th scope="col" class="px-4 py-3 text-right font-medium">Acoes</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-border bg-surface">
                        <tr
                            v-for="service in store.items"
                            :key="service.id"
                            class="hover:bg-surface-muted/60"
                        >
                            <td class="px-4 py-3 font-medium text-ink">
                                {{ service.nome }}
                            </td>
                            <td class="px-4 py-3 text-right font-mono text-ink-muted">
                                {{ formatBRL(service.valor_base_mensal) }}
                            </td>
                            <td class="px-4 py-3">
                                <AppBadge :tone="service.ativo ? 'success' : 'neutral'">
                                    {{ service.ativo ? 'ativo' : 'inativo' }}
                                </AppBadge>
                            </td>
                            <td class="px-4 py-3 text-right">
                                <div class="flex justify-end gap-1">
                                    <AppButton
                                        variant="ghost"
                                        size="sm"
                                        :aria-label="`Editar servico ${service.nome}`"
                                        @click="goToEdit(service.id)"
                                    >
                                        Editar
                                    </AppButton>
                                    <AppButton
                                        variant="ghost"
                                        size="sm"
                                        :aria-label="`Excluir servico ${service.nome}`"
                                        @click="askDelete(service)"
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
            title="Excluir servico"
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
                O servico sera removido logicamente (soft delete) e contratos vinculados continuam
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
