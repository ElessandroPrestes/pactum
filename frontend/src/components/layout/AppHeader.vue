<script setup lang="ts">
import { computed } from 'vue'
import { useRoute } from 'vue-router'
import { primaryNavigation } from '@/config/navigation'
import AppIcon from './AppIcon.vue'

defineEmits<{
    (e: 'open-menu'): void
}>()

const route = useRoute()

const pageTitle = computed(() => {
    const match = primaryNavigation.find((item) => item.to === route.path)
    if (match) {
        return match.label
    }
    if (typeof route.meta.title === 'string') {
        return route.meta.title
    }
    return 'Pactum'
})
</script>

<template>
    <header
        class="sticky top-0 z-sticky flex h-16 items-center gap-3 border-b border-border bg-surface/95 px-4 backdrop-blur sm:px-6"
    >
        <button
            type="button"
            class="inline-flex h-9 w-9 items-center justify-center rounded-md border border-border text-ink-muted hover:bg-surface-muted lg:hidden"
            aria-label="Abrir menu de navegacao"
            @click="$emit('open-menu')"
        >
            <AppIcon name="menu" />
        </button>

        <div class="flex flex-1 flex-col">
            <p class="text-xs uppercase tracking-wider text-ink-subtle">Painel</p>
            <h1 class="truncate text-base font-semibold text-ink">{{ pageTitle }}</h1>
        </div>

        <button
            type="button"
            class="hidden h-9 w-9 items-center justify-center rounded-md border border-border text-ink-muted hover:bg-surface-muted sm:inline-flex"
            aria-label="Notificacoes"
        >
            <AppIcon name="bell" />
        </button>

        <div
            class="hidden h-9 items-center gap-2 rounded-md border border-border bg-surface px-2 text-sm text-ink-muted sm:flex"
            aria-label="Sessao em desenvolvimento"
        >
            <span
                class="inline-flex h-6 w-6 items-center justify-center rounded-full bg-brand-100 text-xs font-semibold text-brand-700"
                aria-hidden="true"
                >P</span
            >
            <span class="pr-1">dev local</span>
        </div>
    </header>
</template>
