<script setup lang="ts">
import { computed, onMounted, reactive, ref } from 'vue'
import { useRoute, useRouter } from 'vue-router'
import { AppButton, AppInput } from '@/components/ui'
import { useAuthStore } from '@/stores/auth'
import { useToastStore } from '@/stores/toast'
import { ApiError } from '@/lib/http'

const router = useRouter()
const route = useRoute()
const auth = useAuthStore()
const toasts = useToastStore()

interface LoginForm {
    email: string
    password: string
}

const form = reactive<LoginForm>({ email: '', password: '' })
const fieldErrors = ref<Partial<Record<keyof LoginForm, string>>>({})
const generalError = ref<string | null>(null)
const submitting = ref(false)

const redirectTo = computed(() => {
    const value = route.query.redirect
    if (typeof value !== 'string' || !value.startsWith('/')) {
        return { name: 'home' as const }
    }
    return value
})

onMounted(() => {
    if (auth.isAuthenticated) {
        void router.replace(redirectTo.value)
    }
})

function clearErrors(): void {
    fieldErrors.value = {}
    generalError.value = null
}

async function submit(): Promise<void> {
    clearErrors()

    const errors: Partial<Record<keyof LoginForm, string>> = {}
    if (!form.email.trim()) {
        errors.email = 'Informe seu email.'
    }
    if (!form.password) {
        errors.password = 'Informe sua senha.'
    }
    if (Object.keys(errors).length > 0) {
        fieldErrors.value = errors
        return
    }

    submitting.value = true
    try {
        await auth.login({
            email: form.email.trim(),
            password: form.password,
            device_name: 'pactum-web',
        })
        toasts.success('Login realizado', 'Bem-vindo de volta.')
        await router.replace(redirectTo.value)
    } catch (error) {
        if (error instanceof ApiError && error.isValidation) {
            fieldErrors.value = {
                email: error.firstError('email'),
                password: error.firstError('password'),
            }
            generalError.value = error.message
        } else if (error instanceof ApiError) {
            generalError.value = error.message
        } else {
            generalError.value = 'Nao foi possivel concluir o login.'
        }
    } finally {
        submitting.value = false
    }
}
</script>

<template>
    <main
        class="flex min-h-screen items-center justify-center bg-surface-muted px-4 py-12"
        aria-labelledby="login-titulo"
    >
        <section
            class="w-full max-w-md rounded-xl border border-border bg-surface p-6 shadow-md sm:p-8"
        >
            <header class="mb-6 flex flex-col items-center text-center">
                <span
                    class="mb-3 inline-flex h-10 w-10 items-center justify-center rounded-md bg-brand-600 text-base font-semibold text-white"
                    aria-hidden="true"
                    >P</span
                >
                <h1 id="login-titulo" class="text-xl font-semibold text-ink">Entrar no Pactum</h1>
                <p class="mt-1 text-sm text-ink-muted">
                    Informe suas credenciais para acessar o painel.
                </p>
            </header>

            <form class="flex flex-col gap-4" novalidate @submit.prevent="submit">
                <AppInput
                    v-model="form.email"
                    label="Email"
                    type="email"
                    autocomplete="email"
                    inputmode="email"
                    required
                    placeholder="voce@empresa.com"
                    :error="fieldErrors.email"
                />
                <AppInput
                    v-model="form.password"
                    label="Senha"
                    type="password"
                    autocomplete="current-password"
                    required
                    placeholder="Sua senha"
                    :error="fieldErrors.password"
                />

                <p
                    v-if="generalError"
                    class="rounded-md border border-danger-500/40 bg-danger-50 px-3 py-2 text-sm text-danger-700"
                    role="alert"
                >
                    {{ generalError }}
                </p>

                <AppButton type="submit" :loading="submitting" block>
                    {{ submitting ? 'Entrando' : 'Entrar' }}
                </AppButton>
            </form>

            <p class="mt-6 text-center text-xs text-ink-subtle">
                Ambiente local de desenvolvimento.
            </p>
        </section>
    </main>
</template>
