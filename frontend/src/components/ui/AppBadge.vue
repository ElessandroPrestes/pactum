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
            return 'bg-success-50 text-success-700 border-success-500/30'
        case 'danger':
            return 'bg-danger-50 text-danger-700 border-danger-500/30'
        case 'warning':
            return 'bg-warning-50 text-warning-700 border-warning-500/30'
        case 'info':
            return 'bg-info-50 text-info-700 border-info-500/30'
        case 'brand':
            return 'bg-brand-50 text-brand-700 border-brand-500/30'
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
