<script setup lang="ts">
import { computed, onMounted, reactive, ref } from 'vue'
import { RouterLink, useRoute, useRouter } from 'vue-router'
import { AppButton, AppInput, AppSelect } from '@/components/ui'
import { useServicesStore } from '@/stores/services'
import { useToastStore } from '@/stores/toast'
import { ApiError } from '@/lib/http'
import { formatCurrencyInput, parseCurrencyInput } from '@/utils/currency'
import type { Service } from '@/types/service'

const route = useRoute()
const router = useRouter()
const store = useServicesStore()
const toasts = useToastStore()

interface FormState {
    nome: string
    valor_base_mensal: string
    ativo: 'true' | 'false'
}

const statusOptions = [
    { value: 'true', label: 'Ativo' },
    { value: 'false', label: 'Inativo' },
] as const

const serviceId = computed(() => {
    const raw = route.params.id
    if (typeof raw === 'string' && raw.length > 0) {
        const parsed = Number(raw)
        return Number.isFinite(parsed) ? parsed : null
    }
    return null
})

const isEditing = computed(() => serviceId.value !== null)

const form = reactive<FormState>({
    nome: '',
    valor_base_mensal: '0.00',
    ativo: 'true',
})

const fieldErrors = ref<Partial<Record<keyof FormState, string>>>({})
const generalError = ref<string | null>(null)
const submitting = ref(false)
const loading = ref(false)

const valorFormatado = computed({
    get: () => formatCurrencyInput(form.valor_base_mensal),
    set: (value: string) => {
        form.valor_base_mensal = parseCurrencyInput(value)
    },
})

function hydrate(service: Service): void {
    form.nome = service.nome
    form.valor_base_mensal = service.valor_base_mensal
    form.ativo = service.ativo ? 'true' : 'false'
}

async function loadService(): Promise<void> {
    if (serviceId.value === null) {
        return
    }
    loading.value = true
    try {
        const service = await store.getOne(serviceId.value)
        hydrate(service)
    } catch (error) {
        if (error instanceof ApiError && error.status === 404) {
            toasts.error('Servico nao encontrado')
            await router.replace({ name: 'services-list' })
            return
        }
        generalError.value = error instanceof ApiError ? error.message : 'Erro ao carregar servico.'
    } finally {
        loading.value = false
    }
}

function localValidation(): boolean {
    const errors: Partial<Record<keyof FormState, string>> = {}
    if (!form.nome.trim()) {
        errors.nome = 'Informe o nome.'
    }
    if (Number(form.valor_base_mensal) <= 0) {
        errors.valor_base_mensal = 'Valor deve ser maior que zero.'
    }
    fieldErrors.value = errors
    return Object.keys(errors).length === 0
}

function applyApiErrors(error: ApiError): void {
    fieldErrors.value = {
        nome: error.firstError('nome'),
        valor_base_mensal: error.firstError('valor_base_mensal'),
        ativo: error.firstError('ativo'),
    }
    generalError.value = error.message
}

async function submit(): Promise<void> {
    generalError.value = null
    if (!localValidation()) {
        return
    }

    submitting.value = true
    try {
        if (isEditing.value && serviceId.value !== null) {
            await store.update(serviceId.value, {
                nome: form.nome.trim(),
                valor_base_mensal: form.valor_base_mensal,
                ativo: form.ativo === 'true',
            })
            toasts.success('Servico atualizado')
        } else {
            await store.create({
                nome: form.nome.trim(),
                valor_base_mensal: form.valor_base_mensal,
                ativo: form.ativo === 'true',
            })
            toasts.success('Servico cadastrado')
        }
        await router.push({ name: 'services-list' })
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
    void loadService()
})
</script>

<template>
    <section class="mx-auto flex max-w-3xl flex-col gap-6">
        <header class="flex flex-col gap-1">
            <p class="text-xs font-medium uppercase tracking-wider text-brand-700">Servicos</p>
            <h2 class="text-2xl font-semibold text-ink">
                {{ isEditing ? 'Editar servico' : 'Novo servico' }}
            </h2>
            <p class="text-sm text-ink-muted">
                Preencha os dados abaixo. Campos com asterisco sao obrigatorios.
            </p>
        </header>

        <div
            v-if="loading"
            class="pactum-card flex items-center justify-center px-6 py-12 text-sm text-ink-muted"
            role="status"
            aria-live="polite"
        >
            Carregando dados do servico...
        </div>

        <form
            v-else
            class="pactum-card flex flex-col gap-4 p-5"
            novalidate
            @submit.prevent="submit"
        >
            <AppInput
                v-model="form.nome"
                label="Nome"
                required
                autocomplete="off"
                :error="fieldErrors.nome"
            />

            <div class="grid gap-4 sm:grid-cols-2">
                <AppInput
                    v-model="valorFormatado"
                    label="Valor base mensal (R$)"
                    required
                    inputmode="numeric"
                    autocomplete="off"
                    placeholder="0,00"
                    hint="Digite o valor em centavos. Ex.: 19990 = R$ 199,90"
                    :error="fieldErrors.valor_base_mensal"
                />
                <AppSelect
                    v-model="form.ativo"
                    label="Status"
                    :options="statusOptions"
                    :error="fieldErrors.ativo"
                />
            </div>

            <p v-if="generalError" class="pactum-alert-danger" role="alert">
                {{ generalError }}
            </p>

            <footer class="flex flex-wrap justify-end gap-2 pt-2">
                <RouterLink :to="{ name: 'services-list' }" class="contents">
                    <AppButton variant="secondary" type="button">Cancelar</AppButton>
                </RouterLink>
                <AppButton type="submit" :loading="submitting">
                    {{ isEditing ? 'Salvar alteracoes' : 'Cadastrar servico' }}
                </AppButton>
            </footer>
        </form>
    </section>
</template>
