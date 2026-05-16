<script setup lang="ts">
import { computed, onBeforeUnmount, onMounted, reactive, ref } from 'vue'
import { RouterLink, useRoute, useRouter } from 'vue-router'
import { AppBadge, AppButton, AppInput, AppModal, AppSelect, AppSkeleton } from '@/components/ui'
import { useContractsStore } from '@/stores/contracts'
import { useToastStore } from '@/stores/toast'
import { ApiError } from '@/lib/http'
import { listServices } from '@/api/services'
import { formatBRL, formatCurrencyInput, parseCurrencyInput } from '@/utils/currency'
import { formatDate, formatDateTime } from '@/utils/date'
import type { Service } from '@/types/service'
import type { ContractItem } from '@/types/contract'
import type { ContractHistoryEntry } from '@/types/contractHistory'

const route = useRoute()
const router = useRouter()
const store = useContractsStore()
const toasts = useToastStore()

const contractId = computed(() => {
    const raw = route.params.id
    if (typeof raw !== 'string') {
        return null
    }
    const parsed = Number(raw)
    return Number.isFinite(parsed) ? parsed : null
})

const services = ref<Service[]>([])
const newItem = reactive({
    service_id: '',
    quantidade: '1',
    valor_unitario: '0.00',
})
const newItemErrors = ref<Record<string, string>>({})
const addingItem = ref(false)

const removing = ref<Record<number, boolean>>({})
const cancelConfirmOpen = ref(false)
const cancelling = ref(false)
const cancelError = ref<string | null>(null)
const conflictMessage = ref<string | null>(null)

const serviceOptions = computed(() =>
    services.value.map((service) => ({
        value: String(service.id),
        label: `${service.nome} (${formatBRL(service.valor_base_mensal)})`,
    })),
)

const valorUnitarioFormatado = computed({
    get: () => formatCurrencyInput(newItem.valor_unitario),
    set: (value: string) => {
        newItem.valor_unitario = parseCurrencyInput(value)
    },
})

const loading = computed(() => store.currentStatus === 'loading')
const isReady = computed(() => store.currentStatus === 'ready' && store.current !== null)
const isCancelled = computed(() => store.current?.status === 'cancelado')
const canMutate = computed(() => isReady.value && !isCancelled.value)

async function loadContract(): Promise<void> {
    if (contractId.value === null) {
        return
    }
    try {
        await store.loadOne(contractId.value)
    } catch (error) {
        if (error instanceof ApiError && error.status === 404) {
            toasts.error('Contrato nao encontrado')
            await router.replace({ name: 'contracts-list' })
        }
    }
}

async function loadServices(): Promise<void> {
    try {
        const response = await listServices({ ativo: true, per_page: 100 })
        services.value = response.data
    } catch {
        services.value = []
    }
}

async function loadHistory(): Promise<void> {
    if (contractId.value === null) return
    try {
        await store.loadHistory(contractId.value)
    } catch {
        toasts.error('Falha ao carregar historico')
    }
}

function onServiceChange(): void {
    const service = services.value.find((item) => String(item.id) === newItem.service_id)
    if (service && (newItem.valor_unitario === '0.00' || newItem.valor_unitario === '')) {
        newItem.valor_unitario = service.valor_base_mensal
    }
}

function resetNewItem(): void {
    newItem.service_id = ''
    newItem.quantidade = '1'
    newItem.valor_unitario = '0.00'
    newItemErrors.value = {}
}

function validateNewItem(): boolean {
    const errors: Record<string, string> = {}
    if (!newItem.service_id) {
        errors.service_id = 'Selecione o servico.'
    }
    const qtd = Number(newItem.quantidade)
    if (!Number.isInteger(qtd) || qtd < 1) {
        errors.quantidade = 'Quantidade deve ser >= 1.'
    }
    if (Number(newItem.valor_unitario) <= 0) {
        errors.valor_unitario = 'Valor deve ser maior que zero.'
    }
    newItemErrors.value = errors
    return Object.keys(errors).length === 0
}

async function addItem(): Promise<void> {
    if (contractId.value === null || !validateNewItem()) {
        return
    }
    addingItem.value = true
    try {
        await store.addItem(contractId.value, {
            service_id: Number(newItem.service_id),
            quantidade: Number(newItem.quantidade),
            valor_unitario: newItem.valor_unitario,
        })
        await store.loadOne(contractId.value)
        await loadHistory()
        toasts.success('Item adicionado')
        resetNewItem()
    } catch (error) {
        if (error instanceof ApiError && error.isValidation) {
            const next: Record<string, string> = {}
            for (const [key, messages] of Object.entries(error.errors)) {
                if (messages && messages.length > 0) {
                    next[key] = messages[0] ?? ''
                }
            }
            newItemErrors.value = next
        } else if (error instanceof ApiError) {
            toasts.error('Falha ao adicionar item', error.message)
        } else {
            toasts.error('Falha ao adicionar item')
        }
    } finally {
        addingItem.value = false
    }
}

async function removeItem(item: ContractItem): Promise<void> {
    if (contractId.value === null) return
    removing.value = { ...removing.value, [item.id]: true }
    const snapshot = store.current?.itens ? [...store.current.itens] : []
    try {
        await store.removeItem(contractId.value, item.id)
        await store.loadOne(contractId.value)
        await loadHistory()
        toasts.success('Item removido')
    } catch (error) {
        if (store.current) {
            store.current = { ...store.current, itens: snapshot }
        }
        if (error instanceof ApiError) {
            toasts.error('Falha ao remover item', error.message)
        } else {
            toasts.error('Falha ao remover item')
        }
    } finally {
        const next = { ...removing.value }
        delete next[item.id]
        removing.value = next
    }
}

function askCancel(): void {
    cancelError.value = null
    conflictMessage.value = null
    cancelConfirmOpen.value = true
}

function closeCancelModal(): void {
    if (cancelling.value) return
    cancelConfirmOpen.value = false
}

async function confirmCancel(): Promise<void> {
    if (contractId.value === null || !store.current) {
        return
    }
    cancelling.value = true
    cancelError.value = null
    try {
        await store.cancel(contractId.value, store.current.version)
        toasts.success('Contrato cancelado')
        cancelConfirmOpen.value = false
        await loadHistory()
    } catch (error) {
        if (error instanceof ApiError && error.isConflict) {
            conflictMessage.value =
                'Outra sessao alterou este contrato. A versao foi atualizada — tente novamente.'
            await store.loadOne(contractId.value)
        } else if (error instanceof ApiError) {
            cancelError.value = error.message
        } else {
            cancelError.value = 'Erro inesperado ao cancelar.'
        }
    } finally {
        cancelling.value = false
    }
}

function nomeServico(item: ContractItem): string {
    if (item.service?.nome) {
        return item.service.nome
    }
    const match = services.value.find((service) => service.id === item.service_id)
    return match?.nome ?? `Servico #${item.service_id}`
}

function subtotalItem(item: ContractItem): string {
    const total = Number(item.valor_unitario) * item.quantidade
    return formatBRL(Number.isFinite(total) ? total : 0)
}

function payloadResumo(entry: ContractHistoryEntry): string {
    if (!entry.payload) return ''
    try {
        return JSON.stringify(entry.payload)
    } catch {
        return ''
    }
}

onMounted(async () => {
    await Promise.all([loadContract(), loadServices(), loadHistory()])
})

onBeforeUnmount(() => {
    store.clearCurrent()
})
</script>

<template>
    <section class="mx-auto flex max-w-5xl flex-col gap-6">
        <header class="flex flex-col gap-2">
            <RouterLink
                :to="{ name: 'contracts-list' }"
                class="text-xs font-medium uppercase tracking-wider text-brand-700 hover:underline"
            >
                &larr; Contratos
            </RouterLink>
            <div class="flex flex-wrap items-center gap-3">
                <h2 class="text-2xl font-semibold text-ink">
                    Contrato <span class="font-mono text-ink-muted">#{{ contractId }}</span>
                </h2>
                <AppBadge v-if="store.current" :tone="isCancelled ? 'neutral' : 'success'">
                    {{ store.current.status }}
                </AppBadge>
                <AppBadge v-if="store.current" tone="info">
                    versao {{ store.current.version }}
                </AppBadge>
            </div>
        </header>

        <div
            v-if="loading"
            class="pactum-card p-5"
            role="status"
            aria-live="polite"
            aria-busy="true"
        >
            <span class="sr-only">Carregando contrato...</span>
            <div class="grid gap-4 sm:grid-cols-2 lg:grid-cols-4">
                <div v-for="block in 4" :key="block" class="flex flex-col gap-2">
                    <AppSkeleton class="h-3 w-16" />
                    <AppSkeleton class="h-5 w-32" />
                </div>
            </div>
            <div class="mt-6 space-y-3">
                <AppSkeleton class="h-4 w-full" />
                <AppSkeleton class="h-4 w-5/6" />
                <AppSkeleton class="h-4 w-2/3" />
            </div>
        </div>

        <div
            v-else-if="store.currentStatus === 'error'"
            class="pactum-card px-6 py-12 text-center text-sm text-danger-700"
            role="alert"
        >
            <p class="font-medium">Nao foi possivel carregar o contrato.</p>
            <p class="mt-1 text-ink-muted">{{ store.currentError }}</p>
            <AppButton class="mt-4" variant="secondary" size="sm" @click="loadContract">
                Tentar novamente
            </AppButton>
        </div>

        <template v-else-if="isReady && store.current">
            <div class="pactum-card p-5">
                <dl class="grid gap-4 text-sm sm:grid-cols-2 lg:grid-cols-4">
                    <div>
                        <dt class="text-xs font-medium uppercase text-ink-subtle">Cliente</dt>
                        <dd class="mt-1 text-ink">
                            {{ store.current.client?.nome ?? `#${store.current.client_id}` }}
                        </dd>
                    </div>
                    <div>
                        <dt class="text-xs font-medium uppercase text-ink-subtle">Inicio</dt>
                        <dd class="mt-1 text-ink">{{ formatDate(store.current.data_inicio) }}</dd>
                    </div>
                    <div>
                        <dt class="text-xs font-medium uppercase text-ink-subtle">Fim</dt>
                        <dd class="mt-1 text-ink">{{ formatDate(store.current.data_fim) }}</dd>
                    </div>
                    <div>
                        <dt class="text-xs font-medium uppercase text-ink-subtle">Total mensal</dt>
                        <dd class="mt-1 font-mono text-lg font-semibold text-ink">
                            {{
                                store.current.total_calculado
                                    ? formatBRL(store.current.total_calculado)
                                    : '—'
                            }}
                        </dd>
                    </div>
                </dl>

                <div v-if="canMutate" class="mt-4 flex justify-end">
                    <AppButton variant="danger" size="sm" @click="askCancel">
                        Cancelar contrato
                    </AppButton>
                </div>
                <p
                    v-else-if="isCancelled"
                    class="mt-4 rounded-md border border-border bg-surface-muted px-3 py-2 text-xs text-ink-muted"
                >
                    Este contrato foi cancelado e nao aceita mais alteracoes.
                </p>
            </div>

            <div class="pactum-card p-5">
                <header class="mb-4 flex items-center justify-between">
                    <h3 class="text-base font-semibold text-ink">Itens contratados</h3>
                    <span class="text-xs text-ink-muted">
                        {{ store.current.itens?.length ?? 0 }} item(s)
                    </span>
                </header>

                <div
                    v-if="!store.current.itens?.length"
                    class="py-6 text-center text-sm text-ink-muted"
                >
                    Nenhum item adicionado.
                </div>

                <ul v-else class="divide-y divide-border">
                    <li
                        v-for="item in store.current.itens"
                        :key="item.id"
                        class="flex items-center justify-between gap-3 py-3"
                    >
                        <div>
                            <p class="font-medium text-ink">{{ nomeServico(item) }}</p>
                            <p class="text-xs text-ink-muted">
                                {{ item.quantidade }} ×
                                <span class="font-mono">{{ formatBRL(item.valor_unitario) }}</span>
                            </p>
                        </div>
                        <div class="flex items-center gap-3">
                            <span class="font-mono text-sm text-ink">{{ subtotalItem(item) }}</span>
                            <AppButton
                                v-if="canMutate"
                                variant="ghost"
                                size="sm"
                                :loading="removing[item.id] === true"
                                :aria-label="`Remover ${nomeServico(item)}`"
                                @click="removeItem(item)"
                            >
                                Remover
                            </AppButton>
                        </div>
                    </li>
                </ul>

                <section
                    v-if="canMutate"
                    class="mt-5 rounded-md border border-border bg-surface-muted/40 p-4"
                >
                    <h4 class="mb-3 text-sm font-semibold text-ink">Adicionar item</h4>
                    <div class="grid gap-3 md:grid-cols-12">
                        <div class="md:col-span-6">
                            <AppSelect
                                v-model="newItem.service_id"
                                label="Servico"
                                placeholder="Selecione"
                                required
                                :options="serviceOptions"
                                :error="newItemErrors.service_id"
                                @update:model-value="onServiceChange"
                            />
                        </div>
                        <div class="md:col-span-2">
                            <AppInput
                                v-model="newItem.quantidade"
                                label="Qtd"
                                type="number"
                                inputmode="numeric"
                                required
                                :error="newItemErrors.quantidade"
                            />
                        </div>
                        <div class="md:col-span-3">
                            <AppInput
                                v-model="valorUnitarioFormatado"
                                label="Valor unitario"
                                inputmode="numeric"
                                required
                                placeholder="0,00"
                                :error="newItemErrors.valor_unitario"
                            />
                        </div>
                        <div class="flex items-end md:col-span-1">
                            <AppButton :loading="addingItem" block size="md" @click="addItem">
                                +
                            </AppButton>
                        </div>
                    </div>
                </section>
            </div>

            <div class="pactum-card p-5">
                <header class="mb-4 flex items-center justify-between">
                    <h3 class="text-base font-semibold text-ink">Historico</h3>
                    <span class="text-xs text-ink-muted">
                        {{ store.historyMeta.total }} evento(s)
                    </span>
                </header>

                <div
                    v-if="store.historyStatus === 'loading'"
                    class="space-y-3 py-2"
                    role="status"
                    aria-live="polite"
                    aria-busy="true"
                >
                    <span class="sr-only">Carregando historico...</span>
                    <div v-for="row in 3" :key="row" class="flex flex-col gap-2">
                        <AppSkeleton class="h-3 w-40" />
                        <AppSkeleton class="h-3 w-24" />
                    </div>
                </div>
                <div
                    v-else-if="store.historyStatus === 'error'"
                    class="py-6 text-center text-sm text-danger-700"
                    role="alert"
                >
                    Falha ao carregar historico.
                </div>
                <ol
                    v-else-if="store.history.length > 0"
                    class="relative ml-3 border-l border-border pl-5"
                >
                    <li
                        v-for="entry in store.history"
                        :key="entry.id"
                        class="relative pb-5 last:pb-0"
                    >
                        <span
                            class="absolute -left-[27px] top-1 inline-block size-3 rounded-full bg-brand-500 ring-4 ring-surface"
                            aria-hidden="true"
                        />
                        <p class="text-sm font-medium text-ink">{{ entry.evento }}</p>
                        <p class="text-xs text-ink-subtle">
                            {{ formatDateTime(entry.created_at) }}
                        </p>
                        <pre
                            v-if="entry.payload"
                            class="mt-1 max-w-full overflow-x-auto rounded-md bg-surface-muted px-2 py-1 text-xs text-ink-muted"
                            >{{ payloadResumo(entry) }}</pre
                        >
                    </li>
                </ol>
                <p v-else class="py-6 text-center text-sm text-ink-muted">
                    Sem eventos registrados.
                </p>
            </div>
        </template>

        <AppModal
            :open="cancelConfirmOpen"
            title="Cancelar contrato"
            description="Esta acao marca o contrato como cancelado e impede novas alteracoes."
            size="sm"
            :close-on-backdrop="!cancelling"
            @close="closeCancelModal"
        >
            <p class="text-sm text-ink-muted">
                Tem certeza? O cancelamento e definitivo pela interface e sera registrado no
                historico.
            </p>
            <p v-if="conflictMessage" class="pactum-alert-warning mt-3 text-xs" role="alert">
                {{ conflictMessage }}
            </p>
            <p v-if="cancelError" class="pactum-alert-danger mt-3 text-xs" role="alert">
                {{ cancelError }}
            </p>
            <template #footer>
                <AppButton variant="secondary" :disabled="cancelling" @click="closeCancelModal">
                    Manter ativo
                </AppButton>
                <AppButton variant="danger" :loading="cancelling" @click="confirmCancel">
                    Cancelar contrato
                </AppButton>
            </template>
        </AppModal>
    </section>
</template>
