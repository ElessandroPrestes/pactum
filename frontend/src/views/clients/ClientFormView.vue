<script setup lang="ts">
import { RouterLink } from 'vue-router'
import { AppButton } from '@/components/ui'
</script>

<template>
    <section class="mx-auto flex max-w-2xl flex-col gap-4 py-12 text-center">
        <h2 class="text-xl font-semibold text-ink">Formulario em construcao</h2>
        <p class="text-sm text-ink-muted">
            Esta tela sera entregue na proxima etapa do fluxo de clientes.
        </p>
        <RouterLink :to="{ name: 'clients-list' }" class="contents">
            <AppButton variant="secondary" size="sm">Voltar para a lista</AppButton>
        </RouterLink>
    </section>
</template>
