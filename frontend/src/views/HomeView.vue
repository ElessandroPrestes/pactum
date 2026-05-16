<script setup lang="ts">
const appName = import.meta.env.VITE_APP_NAME ?? 'Pactum'

const cards = [
    {
        title: 'Clientes',
        description: 'Cadastro centralizado com validacao de CPF e CNPJ.',
    },
    {
        title: 'Servicos',
        description: 'Catalogo de servicos com valor base e status de atividade.',
    },
    {
        title: 'Contratos',
        description: 'Itens dinamicos, calculo automatico e historico auditavel.',
    },
]
</script>

<template>
    <section class="mx-auto flex max-w-5xl flex-col gap-6">
        <header class="flex flex-col gap-2">
            <p class="text-xs font-medium uppercase tracking-wider text-brand-700">Bem-vindo</p>
            <h2 class="text-3xl font-semibold tracking-tight text-ink">
                {{ appName }}
            </h2>
            <p class="max-w-2xl text-sm text-ink-muted">
                ERP simplificado para gestao de contratos e servicos recorrentes mensais. Use o menu
                lateral para navegar entre os modulos.
            </p>
        </header>

        <div class="grid gap-4 sm:grid-cols-2 lg:grid-cols-3">
            <article v-for="card in cards" :key="card.title" class="pactum-card p-5">
                <h3 class="text-base font-semibold text-ink">
                    {{ card.title }}
                </h3>
                <p class="mt-2 text-sm text-ink-muted">
                    {{ card.description }}
                </p>
            </article>
        </div>
    </section>
</template>
