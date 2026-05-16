<script setup lang="ts">
import { computed } from 'vue'

type Tone = 'neutral' | 'success' | 'danger' | 'warning' | 'info' | 'brand'

const props = withDefaults(
    defineProps<{
        tone?: Tone
    }>(),
    {
        tone: 'neutral',
    },
)

const toneClass = computed(() => {
    switch (props.tone) {
        case 'success':
            return 'bg-success-50 text-success-700 border-success-500/30 dark:bg-success-500/10 dark:text-success-500 dark:border-success-500/40'
        case 'danger':
            return 'bg-danger-50 text-danger-700 border-danger-500/30 dark:bg-danger-500/10 dark:text-danger-500 dark:border-danger-500/40'
        case 'warning':
            return 'bg-warning-50 text-warning-700 border-warning-500/30 dark:bg-warning-500/10 dark:text-warning-500 dark:border-warning-500/40'
        case 'info':
            return 'bg-info-50 text-info-700 border-info-500/30 dark:bg-info-500/10 dark:text-info-500 dark:border-info-500/40'
        case 'brand':
            return 'bg-brand-50 text-brand-700 border-brand-500/30 dark:bg-brand-500/15 dark:text-brand-300 dark:border-brand-500/40'
        case 'neutral':
        default:
            return 'bg-surface-muted text-ink-muted border-border'
    }
})
</script>

<template>
    <span
        :class="[
            'inline-flex items-center gap-1 rounded-full border px-2 py-0.5 text-xs font-medium',
            toneClass,
        ]"
    >
        <slot />
    </span>
</template>
