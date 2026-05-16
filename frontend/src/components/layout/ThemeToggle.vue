<script setup lang="ts">
import { computed, onMounted } from 'vue'
import { useThemeStore, type ThemePreference } from '@/stores/theme'
import AppIcon from './AppIcon.vue'

const theme = useThemeStore()

onMounted(() => {
    theme.bind()
})

const labelByPreference: Record<ThemePreference, string> = {
    system: 'Tema do sistema',
    light: 'Tema claro',
    dark: 'Tema escuro',
}

const nextByPreference: Record<ThemePreference, ThemePreference> = {
    system: 'light',
    light: 'dark',
    dark: 'system',
}

const iconName = computed<'sun' | 'moon' | 'monitor'>(() => {
    switch (theme.preference) {
        case 'light':
            return 'sun'
        case 'dark':
            return 'moon'
        case 'system':
        default:
            return 'monitor'
    }
})

const ariaLabel = computed(
    () =>
        `${labelByPreference[theme.preference]}. Alternar para ${labelByPreference[nextByPreference[theme.preference]]}.`,
)
</script>

<template>
    <button
        type="button"
        class="inline-flex h-9 w-9 items-center justify-center rounded-md border border-border text-ink-muted transition-colors hover:bg-surface-muted hover:text-ink"
        :aria-label="ariaLabel"
        :title="labelByPreference[theme.preference]"
        @click="theme.cycle()"
    >
        <AppIcon :name="iconName" />
    </button>
</template>
