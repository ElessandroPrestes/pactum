<script setup lang="ts">
import { computed } from 'vue'
import AppButton from './AppButton.vue'

const props = defineProps<{
    currentPage: number
    lastPage: number
    total: number
    from: number | null
    to: number | null
}>()

const emit = defineEmits<{
    (e: 'change', page: number): void
}>()

const prevDisabled = computed(() => props.currentPage <= 1)
const nextDisabled = computed(() => props.currentPage >= props.lastPage)

const summary = computed(() => {
    if (props.total === 0) {
        return 'Nenhum registro'
    }
    if (props.from && props.to) {
        return `Mostrando ${props.from}-${props.to} de ${props.total}`
    }
    return `Total: ${props.total}`
})
</script>

<template>
    <nav
        v-if="total > 0"
        class="flex flex-col items-center justify-between gap-3 border-t border-border bg-surface px-4 py-3 sm:flex-row"
        aria-label="Paginacao"
    >
        <p class="text-xs text-ink-muted">{{ summary }}</p>
        <div class="flex items-center gap-2">
            <AppButton
                variant="secondary"
                size="sm"
                :disabled="prevDisabled"
                aria-label="Pagina anterior"
                @click="emit('change', currentPage - 1)"
            >
                Anterior
            </AppButton>
            <span class="text-xs text-ink-muted"> Pagina {{ currentPage }} de {{ lastPage }} </span>
            <AppButton
                variant="secondary"
                size="sm"
                :disabled="nextDisabled"
                aria-label="Proxima pagina"
                @click="emit('change', currentPage + 1)"
            >
                Proxima
            </AppButton>
        </div>
    </nav>
</template>
