<script setup lang="ts">
import { computed } from 'vue'
import { useRoute, useRouter } from 'vue-router'
import { AppButton } from '@/components/ui'

const route = useRoute()
const router = useRouter()

const traceId = computed(() => {
    const value = route.query.trace
    return typeof value === 'string' ? value : null
})

function reload(): void {
    if (typeof window !== 'undefined') {
        window.location.reload()
    }
}

function goHome(): void {
    void router.replace({ name: 'home' })
}
</script>

<template>
    <section
        class="mx-auto flex max-w-xl flex-col items-center gap-4 py-12 text-center"
        role="alert"
        aria-labelledby="erro-500-titulo"
    >
        <span
            class="inline-flex h-12 w-12 items-center justify-center rounded-full bg-danger-50 text-base font-semibold text-danger-700"
            aria-hidden="true"
            >500</span
        >
        <h2 id="erro-500-titulo" class="text-2xl font-semibold text-ink">Algo deu errado</h2>
        <p class="text-sm text-ink-muted">
            Ocorreu uma falha inesperada ao processar sua solicitacao. Tente novamente em instantes
            ou volte para o inicio.
        </p>
        <p
            v-if="traceId"
            class="rounded-md bg-surface-muted px-3 py-1 font-mono text-xs text-ink-muted"
        >
            trace: {{ traceId }}
        </p>
        <div class="flex flex-wrap items-center justify-center gap-2">
            <AppButton variant="primary" @click="reload"> Tentar novamente </AppButton>
            <AppButton variant="secondary" @click="goHome"> Voltar para o inicio </AppButton>
        </div>
    </section>
</template>
