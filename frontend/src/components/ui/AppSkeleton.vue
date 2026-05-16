<script setup lang="ts">
import { computed } from 'vue'

type Shape = 'text' | 'rect' | 'circle'

const props = withDefaults(
    defineProps<{
        shape?: Shape
        width?: string
        height?: string
        rounded?: string
    }>(),
    {
        shape: 'rect',
        width: undefined,
        height: undefined,
        rounded: undefined,
    },
)

const shapeClass = computed(() => {
    switch (props.shape) {
        case 'text':
            return 'h-3 rounded'
        case 'circle':
            return 'rounded-full'
        case 'rect':
        default:
            return 'rounded-md'
    }
})

const inlineStyle = computed(() => {
    const style: Record<string, string> = {}
    if (props.width) style.width = props.width
    if (props.height) style.height = props.height
    if (props.rounded) style.borderRadius = props.rounded
    return style
})
</script>

<template>
    <span
        :class="['block animate-pulse bg-surface-muted dark:bg-surface-subtle', shapeClass]"
        :style="inlineStyle"
        aria-hidden="true"
    />
</template>
