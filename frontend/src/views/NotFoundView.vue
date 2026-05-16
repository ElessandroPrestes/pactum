<script setup lang="ts">
import { RouterLink } from 'vue-router'
import { AppButton } from '@/components/ui'
</script>

<template>
    <section
        class="mx-auto flex max-w-xl flex-col items-center gap-4 py-12 text-center"
        role="region"
        aria-labelledby="erro-404-titulo"
    >
        <span
            class="inline-flex h-12 w-12 items-center justify-center rounded-full bg-brand-50 text-base font-semibold text-brand-700"
            aria-hidden="true"
            >404</span
        >
        <h2 id="erro-404-titulo" class="text-2xl font-semibold text-ink">Pagina nao encontrada</h2>
        <p class="text-sm text-ink-muted">
            O recurso solicitado nao existe ou foi movido. Verifique o endereco ou volte para a tela
            inicial.
        </p>
        <RouterLink to="/" class="contents">
            <AppButton variant="primary">Voltar para o inicio</AppButton>
        </RouterLink>
    </section>
</template>
