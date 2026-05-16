<script setup lang="ts">
import { primaryNavigation } from '@/config/navigation'
import AppIcon from './AppIcon.vue'

defineProps<{
    onNavigate?: () => void
}>()
</script>

<template>
    <nav aria-label="Navegacao principal" class="flex h-full flex-col gap-6 px-4 py-6">
        <div class="flex items-center gap-2 px-2">
            <span
                class="inline-flex h-9 w-9 items-center justify-center rounded-md bg-brand-600 text-sm font-semibold text-white"
                aria-hidden="true"
                >P</span
            >
            <div class="flex flex-col">
                <span class="text-sm font-semibold text-ink">Pactum</span>
                <span class="text-xs text-ink-subtle">ERP de contratos</span>
            </div>
        </div>

        <ul class="flex flex-1 flex-col gap-1">
            <li v-for="item in primaryNavigation" :key="item.to">
                <RouterLink
                    :to="item.to"
                    class="group flex items-center gap-3 rounded-md px-3 py-2 text-sm font-medium text-ink-muted transition-colors hover:bg-surface-muted hover:text-ink"
                    active-class="bg-brand-50 text-brand-700 hover:bg-brand-50 hover:text-brand-700"
                    @click="onNavigate?.()"
                >
                    <AppIcon :name="item.icon" :size="18" />
                    <span>{{ item.label }}</span>
                </RouterLink>
            </li>
        </ul>

        <footer class="rounded-md bg-surface-muted p-3 text-xs text-ink-subtle">
            <p class="font-medium text-ink">Pactum {{ new Date().getFullYear() }}</p>
            <p class="mt-0.5">Ambiente local de desenvolvimento</p>
        </footer>
    </nav>
</template>
