<script setup lang="ts">
import { computed, onMounted, reactive, ref } from 'vue'
import { RouterLink, useRouter } from 'vue-router'
import { AppButton, AppInput, AppSelect } from '@/components/ui'
import { useContractsStore } from '@/stores/contracts'
import { useToastStore } from '@/stores/toast'
import { ApiError } from '@/lib/http'
import { listClients } from '@/api/clients'
import { listServices } from '@/api/services'
import { formatBRL, formatCurrencyInput, parseCurrencyInput } from '@/utils/currency'
import type { Client } from '@/types/client'
import type { Service } from '@/types/service'

const router = useRouter()
const store = useContractsStore()
const toasts = useToastStore()

interface ItemRow {
    service_id: string
    quantidade: string
    valor_unitario: string
}

interface FormState {
    client_id: string
    data_inicio: string
    data_fim: string
    itens: ItemRow[]
}

const form = reactive<FormState>({
    client_id: '',
    data_inicio: new Date().toISOString().slice(0, 10),
    data_fim: '',
    itens: [emptyItem()],
})

const clientOptions = ref<{ value: string; label: string }[]>([])
const services = ref<Service[]>([])
const loadingOptions = ref(false)

const fieldErrors = ref<Record<string, string>>({})
const generalError = ref<string | null>(null)
const submitting = ref(false)

function emptyItem(): ItemRow {
    return { service_id: '', quantidade: '1', valor_unitario: '0.00' }
}

const serviceOptions = computed(() =>
    services.value.map((service) => ({
        value: String(service.id),
        label: `${service.nome} (${formatBRL(service.valor_base_mensal)})`,
    })),
)

async function loadOptions(): Promise<void> {
    loadingOptions.value = true
    try {
        const [clientsRes, servicesRes] = await Promise.all([
            listClients({ status: 'ativo', per_page: 100 }),
            listServices({ ativo: true, per_page: 100 }),
        ])
        clientOptions.value = clientsRes.data.map((client: Client) => ({
            value: String(client.id),
            label: client.nome,
        }))
        services.value = servicesRes.data
    } catch {
        toasts.error('Falha ao carregar clientes ou servicos')
    } finally {
        loadingOptions.value = false
    }
}

function onServiceChange(index: number): void {
    const row = form.itens[index]
    if (!row) return
    const service = services.value.find((item) => String(item.id) === row.service_id)
    if (service && (row.valor_unitario === '0.00' || row.valor_unitario === '')) {
        row.valor_unitario = service.valor_base_mensal
    }
}

function addItem(): void {
    form.itens.push(emptyItem())
}

function removeItem(index: number): void {
    if (form.itens.length === 1) {
        form.itens[0] = emptyItem()
        return
    }
    form.itens.splice(index, 1)
}

function subtotalDoItem(row: ItemRow): number {
    const qtd = Number(row.quantidade) || 0
    const valor = Number(row.valor_unitario) || 0
    return qtd * valor
}

const totalEstimado = computed(() =>
    form.itens.reduce((accumulator, row) => accumulator + subtotalDoItem(row), 0),
)

function valorFormatado(row: ItemRow): string {
    return formatCurrencyInput(row.valor_unitario)
}

function onValorInput(index: number, raw: string): void {
    const row = form.itens[index]
    if (row) {
        row.valor_unitario = parseCurrencyInput(raw)
    }
}

function localValidation(): boolean {
    const errors: Record<string, string> = {}
    if (!form.client_id) {
        errors.client_id = 'Selecione um cliente.'
    }
    if (!form.data_inicio) {
        errors.data_inicio = 'Informe a data de inicio.'
    }
    if (form.data_fim && form.data_fim < form.data_inicio) {
        errors.data_fim = 'Data fim nao pode ser anterior ao inicio.'
    }
    form.itens.forEach((row, index) => {
        if (!row.service_id) {
            errors[`itens.${index}.service_id`] = 'Selecione o servico.'
        }
        const qtd = Number(row.quantidade)
        if (!Number.isInteger(qtd) || qtd < 1) {
            errors[`itens.${index}.quantidade`] = 'Quantidade deve ser >= 1.'
        }
        if (Number(row.valor_unitario) <= 0) {
            errors[`itens.${index}.valor_unitario`] = 'Valor deve ser maior que zero.'
        }
    })
    fieldErrors.value = errors
    return Object.keys(errors).length === 0
}

function applyApiErrors(error: ApiError): void {
    const next: Record<string, string> = {}
    for (const [key, messages] of Object.entries(error.errors)) {
        if (messages && messages.length > 0) {
            next[key] = messages[0] ?? ''
        }
    }
    fieldErrors.value = next
    generalError.value = error.message
}

async function submit(): Promise<void> {
    generalError.value = null
    if (!localValidation()) {
        return
    }

    submitting.value = true
    try {
        const created = await store.create({
            client_id: Number(form.client_id),
            data_inicio: form.data_inicio,
            data_fim: form.data_fim || null,
            itens: form.itens.map((row) => ({
                service_id: Number(row.service_id),
                quantidade: Number(row.quantidade),
                valor_unitario: row.valor_unitario,
            })),
        })
        toasts.success('Contrato cadastrado', `Contrato #${created.id} criado com sucesso.`)
        await router.push({ name: 'contracts-show', params: { id: String(created.id) } })
    } catch (error) {
        if (error instanceof ApiError && error.isValidation) {
            applyApiErrors(error)
        } else if (error instanceof ApiError) {
            generalError.value = error.message
        } else {
            generalError.value = 'Erro inesperado ao salvar.'
        }
    } finally {
        submitting.value = false
    }
}

onMounted(() => {
    void loadOptions()
})
</script>

<template>
    <section class="mx-auto flex max-w-4xl flex-col gap-6">
        <header class="flex flex-col gap-1">
            <p class="text-xs font-medium uppercase tracking-wider text-brand-700">Contratos</p>
            <h2 class="text-2xl font-semibold text-ink">Novo contrato</h2>
            <p class="text-sm text-ink-muted">
                Selecione o cliente, defina as datas e adicione os itens contratados.
            </p>
        </header>

        <div
            v-if="loadingOptions"
            class="pactum-card flex items-center justify-center px-6 py-12 text-sm text-ink-muted"
            role="status"
            aria-live="polite"
        >
            Carregando clientes e servicos...
        </div>

        <form
            v-else
            class="pactum-card flex flex-col gap-5 p-5"
            novalidate
            @submit.prevent="submit"
        >
            <div class="grid gap-4 sm:grid-cols-2">
                <AppSelect
                    v-model="form.client_id"
                    label="Cliente"
                    required
                    placeholder="Selecione o cliente"
                    :options="clientOptions"
                    :error="fieldErrors.client_id"
                />
                <div class="grid gap-4 sm:grid-cols-2">
                    <AppInput
                        v-model="form.data_inicio"
                        label="Inicio"
                        type="date"
                        required
                        :error="fieldErrors.data_inicio"
                    />
                    <AppInput
                        v-model="form.data_fim"
                        label="Fim"
                        type="date"
                        hint="Opcional"
                        :error="fieldErrors.data_fim"
                    />
                </div>
            </div>

            <section class="flex flex-col gap-3">
                <header class="flex items-center justify-between">
                    <div>
                        <h3 class="text-base font-semibold text-ink">Itens do contrato</h3>
                        <p class="text-xs text-ink-muted">
                            Cada linha corresponde a um servico recorrente mensal.
                        </p>
                    </div>
                    <AppButton type="button" variant="secondary" size="sm" @click="addItem">
                        Adicionar item
                    </AppButton>
                </header>

                <ol class="flex flex-col gap-3">
                    <li
                        v-for="(row, index) in form.itens"
                        :key="index"
                        class="rounded-md border border-border bg-surface-muted/40 p-3"
                    >
                        <div class="grid gap-3 md:grid-cols-12">
                            <div class="md:col-span-6">
                                <AppSelect
                                    v-model="row.service_id"
                                    label="Servico"
                                    required
                                    placeholder="Selecione"
                                    :options="serviceOptions"
                                    :error="fieldErrors[`itens.${index}.service_id`]"
                                    @update:model-value="onServiceChange(index)"
                                />
                            </div>
                            <div class="md:col-span-2">
                                <AppInput
                                    v-model="row.quantidade"
                                    label="Qtd"
                                    type="number"
                                    inputmode="numeric"
                                    required
                                    :error="fieldErrors[`itens.${index}.quantidade`]"
                                />
                            </div>
                            <div class="md:col-span-3">
                                <AppInput
                                    :model-value="valorFormatado(row)"
                                    label="Valor unitario"
                                    inputmode="numeric"
                                    required
                                    placeholder="0,00"
                                    :error="fieldErrors[`itens.${index}.valor_unitario`]"
                                    @update:model-value="onValorInput(index, $event)"
                                />
                            </div>
                            <div class="flex items-end justify-end md:col-span-1">
                                <AppButton
                                    type="button"
                                    variant="ghost"
                                    size="sm"
                                    :aria-label="`Remover item ${index + 1}`"
                                    @click="removeItem(index)"
                                >
                                    Remover
                                </AppButton>
                            </div>
                        </div>
                        <p class="mt-2 text-right text-xs text-ink-muted">
                            Subtotal:
                            <span class="font-mono">{{ formatBRL(subtotalDoItem(row)) }}</span>
                        </p>
                    </li>
                </ol>
            </section>

            <div
                class="flex items-center justify-between rounded-md border border-border bg-surface px-4 py-3"
            >
                <span class="text-sm font-medium text-ink-muted">Total estimado</span>
                <span class="text-lg font-semibold text-ink">{{ formatBRL(totalEstimado) }}</span>
            </div>

            <p
                v-if="generalError"
                class="rounded-md border border-danger-500/40 bg-danger-50 px-3 py-2 text-sm text-danger-700"
                role="alert"
            >
                {{ generalError }}
            </p>

            <footer class="flex flex-wrap justify-end gap-2 pt-2">
                <RouterLink :to="{ name: 'contracts-list' }" class="contents">
                    <AppButton variant="secondary" type="button">Cancelar</AppButton>
                </RouterLink>
                <AppButton type="submit" :loading="submitting">Cadastrar contrato</AppButton>
            </footer>
        </form>
    </section>
</template>
