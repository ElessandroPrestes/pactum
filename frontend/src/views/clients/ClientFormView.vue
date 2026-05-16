<script setup lang="ts">
import { computed, onMounted, reactive, ref, watch } from 'vue'
import { RouterLink, useRoute, useRouter } from 'vue-router'
import { AppButton, AppInput, AppSelect } from '@/components/ui'
import { useClientsStore } from '@/stores/clients'
import { useToastStore } from '@/stores/toast'
import { ApiError } from '@/lib/http'
import { formatDocumento, inferDocumentType, maxLengthFor, onlyDigits } from '@/utils/documento'
import type { Client, ClientStatus, DocumentType } from '@/types/client'

const route = useRoute()
const router = useRouter()
const store = useClientsStore()
const toasts = useToastStore()

const documentOptions = [
    { value: 'cpf', label: 'CPF' },
    { value: 'cnpj', label: 'CNPJ' },
] as const

const statusOptions = [
    { value: 'ativo', label: 'Ativo' },
    { value: 'inativo', label: 'Inativo' },
] as const

interface FormState {
    nome: string
    documento: string
    tipo_documento: DocumentType
    email: string
    status: ClientStatus
}

const clientId = computed(() => {
    const raw = route.params.id
    if (typeof raw === 'string' && raw.length > 0) {
        const parsed = Number(raw)
        return Number.isFinite(parsed) ? parsed : null
    }
    return null
})

const isEditing = computed(() => clientId.value !== null)

const form = reactive<FormState>({
    nome: '',
    documento: '',
    tipo_documento: 'cpf',
    email: '',
    status: 'ativo',
})

const fieldErrors = ref<Partial<Record<keyof FormState, string>>>({})
const generalError = ref<string | null>(null)
const submitting = ref(false)
const loading = ref(false)

const documentoFormatado = computed({
    get: () => formatDocumento(form.documento, form.tipo_documento),
    set: (value: string) => {
        form.documento = onlyDigits(value).slice(0, form.tipo_documento === 'cpf' ? 11 : 14)
    },
})

const documentoMaxLength = computed(() => maxLengthFor(form.tipo_documento))

watch(
    () => form.tipo_documento,
    (next) => {
        const max = next === 'cpf' ? 11 : 14
        form.documento = form.documento.slice(0, max)
    },
)

watch(
    () => form.documento,
    (next) => {
        const inferred = inferDocumentType(next)
        if (onlyDigits(next).length > 11 && form.tipo_documento === 'cpf') {
            form.tipo_documento = inferred
        }
    },
)

function hydrate(client: Client): void {
    form.nome = client.nome
    form.documento = client.documento
    form.tipo_documento = client.tipo_documento
    form.email = client.email
    form.status = client.status
}

async function loadClient(): Promise<void> {
    if (clientId.value === null) {
        return
    }
    loading.value = true
    try {
        const client = await store.getOne(clientId.value)
        hydrate(client)
    } catch (error) {
        if (error instanceof ApiError && error.status === 404) {
            toasts.error('Cliente nao encontrado')
            await router.replace({ name: 'clients-list' })
            return
        }
        generalError.value = error instanceof ApiError ? error.message : 'Erro ao carregar cliente.'
    } finally {
        loading.value = false
    }
}

function localValidation(): boolean {
    const errors: Partial<Record<keyof FormState, string>> = {}
    if (!form.nome.trim()) {
        errors.nome = 'Informe o nome.'
    }
    const digitos = onlyDigits(form.documento)
    const minLen = form.tipo_documento === 'cpf' ? 11 : 14
    if (digitos.length < minLen) {
        errors.documento = `Documento incompleto (${minLen} digitos).`
    }
    if (!form.email.trim()) {
        errors.email = 'Informe o email.'
    }
    fieldErrors.value = errors
    return Object.keys(errors).length === 0
}

function applyApiErrors(error: ApiError): void {
    fieldErrors.value = {
        nome: error.firstError('nome'),
        documento: error.firstError('documento'),
        tipo_documento: error.firstError('tipo_documento'),
        email: error.firstError('email'),
        status: error.firstError('status'),
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
        if (isEditing.value && clientId.value !== null) {
            await store.update(clientId.value, {
                nome: form.nome.trim(),
                documento: onlyDigits(form.documento),
                tipo_documento: form.tipo_documento,
                email: form.email.trim(),
                status: form.status,
            })
            toasts.success('Cliente atualizado')
        } else {
            await store.create({
                nome: form.nome.trim(),
                documento: onlyDigits(form.documento),
                tipo_documento: form.tipo_documento,
                email: form.email.trim(),
            })
            toasts.success('Cliente cadastrado')
        }
        await router.push({ name: 'clients-list' })
    } catch (error) {
        if (error instanceof ApiError && error.isValidation) {
            applyApiErrors(error)
        } else if (error instanceof ApiError && error.isConflict) {
            generalError.value = 'Conflito de versao. Recarregue a pagina e tente novamente.'
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
    void loadClient()
})
</script>

<template>
    <section class="mx-auto flex max-w-3xl flex-col gap-6">
        <header class="flex flex-col gap-1">
            <p class="text-xs font-medium uppercase tracking-wider text-brand-700">Clientes</p>
            <h2 class="text-2xl font-semibold text-ink">
                {{ isEditing ? 'Editar cliente' : 'Novo cliente' }}
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
            Carregando dados do cliente...
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
                autocomplete="name"
                :error="fieldErrors.nome"
            />

            <div class="grid gap-4 sm:grid-cols-3">
                <AppSelect
                    v-model="form.tipo_documento"
                    label="Tipo"
                    required
                    :options="documentOptions"
                    :error="fieldErrors.tipo_documento"
                />
                <div class="sm:col-span-2">
                    <AppInput
                        v-model="documentoFormatado"
                        label="Documento"
                        required
                        inputmode="numeric"
                        autocomplete="off"
                        :placeholder="
                            form.tipo_documento === 'cpf' ? '000.000.000-00' : '00.000.000/0000-00'
                        "
                        :hint="`Digite o ${form.tipo_documento.toUpperCase()} (somente numeros)`"
                        :error="fieldErrors.documento"
                        :maxlength="documentoMaxLength"
                    />
                </div>
            </div>

            <AppInput
                v-model="form.email"
                label="Email"
                type="email"
                required
                autocomplete="email"
                inputmode="email"
                :error="fieldErrors.email"
            />

            <AppSelect
                v-if="isEditing"
                v-model="form.status"
                label="Status"
                :options="statusOptions"
                :error="fieldErrors.status"
            />

            <p v-if="generalError" class="pactum-alert-danger" role="alert">
                {{ generalError }}
            </p>

            <footer class="flex flex-wrap justify-end gap-2 pt-2">
                <RouterLink :to="{ name: 'clients-list' }" class="contents">
                    <AppButton variant="secondary" type="button">Cancelar</AppButton>
                </RouterLink>
                <AppButton type="submit" :loading="submitting">
                    {{ isEditing ? 'Salvar alteracoes' : 'Cadastrar cliente' }}
                </AppButton>
            </footer>
        </form>
    </section>
</template>
