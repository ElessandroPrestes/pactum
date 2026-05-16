<script setup lang="ts">
import { computed } from 'vue'

type Variant = 'primary' | 'secondary' | 'ghost' | 'danger'
type Size = 'sm' | 'md' | 'lg'

const props = withDefaults(
    defineProps<{
        variant?: Variant
        size?: Size
        type?: 'button' | 'submit' | 'reset'
        disabled?: boolean
        loading?: boolean
        block?: boolean
        ariaLabel?: string
    }>(),
    {
        variant: 'primary',
        size: 'md',
        type: 'button',
        disabled: false,
        loading: false,
        block: false,
        ariaLabel: undefined,
    },
)

defineEmits<{
    (e: 'click', event: MouseEvent): void
}>()

const variantClass = computed(() => {
    switch (props.variant) {
        case 'primary':
            return 'bg-brand-600 text-white hover:bg-brand-700 active:bg-brand-800 disabled:bg-brand-300'
        case 'secondary':
            return 'bg-surface text-ink border border-border hover:bg-surface-muted disabled:text-ink-subtle'
        case 'ghost':
            return 'bg-transparent text-ink hover:bg-surface-muted disabled:text-ink-subtle'
        case 'danger':
            return 'bg-danger-600 text-white hover:bg-danger-700 active:bg-danger-700 disabled:bg-danger-500/60'
        default:
            return ''
    }
})

const sizeClass = computed(() => {
    switch (props.size) {
        case 'sm':
            return 'h-8 px-3 text-sm gap-1.5'
        case 'lg':
            return 'h-11 px-5 text-base gap-2'
        case 'md':
        default:
            return 'h-10 px-4 text-sm gap-2'
    }
})
</script>

<template>
    <button
        :type="type"
        :disabled="disabled || loading"
        :aria-label="ariaLabel"
        :aria-busy="loading || undefined"
        :class="[
            'inline-flex items-center justify-center rounded-md font-medium transition-colors duration-150 ease-emphasized',
            'disabled:cursor-not-allowed',
            variantClass,
            sizeClass,
            block ? 'w-full' : '',
        ]"
        @click="(event) => $emit('click', event)"
    >
        <span
            v-if="loading"
            class="inline-block size-4 animate-spin rounded-full border-2 border-current border-r-transparent"
            aria-hidden="true"
        />
        <slot v-if="!loading" name="leading" />
        <slot />
        <slot v-if="!loading" name="trailing" />
    </button>
</template>
