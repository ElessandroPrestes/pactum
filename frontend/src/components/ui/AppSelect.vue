<script setup lang="ts" generic="T extends string | number">
import { computed, useId } from 'vue'
import AppLabel from './AppLabel.vue'

interface Option {
    value: T
    label: string
    disabled?: boolean
}

const props = withDefaults(
    defineProps<{
        modelValue: T | null
        options: Option[]
        label?: string
        id?: string
        placeholder?: string
        hint?: string
        error?: string
        required?: boolean
        disabled?: boolean
        name?: string
    }>(),
    {
        label: undefined,
        id: undefined,
        placeholder: 'Selecione',
        hint: undefined,
        error: undefined,
        required: false,
        disabled: false,
        name: undefined,
    },
)

const emit = defineEmits<{
    (e: 'update:modelValue', value: T | null): void
}>()

const generatedId = useId()
const selectId = computed(() => props.id ?? `select-${generatedId}`)
const hintId = computed(() => `${selectId.value}-hint`)
const errorId = computed(() => `${selectId.value}-error`)

const selectClass = computed(() => {
    const base =
        'block w-full appearance-none rounded-md border bg-surface px-3 py-2 pr-9 text-sm text-ink shadow-xs disabled:cursor-not-allowed disabled:bg-surface-muted disabled:text-ink-subtle'
    return props.error
        ? `${base} border-danger-500 focus:border-danger-600`
        : `${base} border-border focus:border-brand-500`
})

const describedBy = computed(() => {
    const ids: string[] = []
    if (props.hint) {
        ids.push(hintId.value)
    }
    if (props.error) {
        ids.push(errorId.value)
    }
    return ids.length > 0 ? ids.join(' ') : undefined
})

function onChange(event: Event): void {
    const value = (event.target as HTMLSelectElement).value
    if (value === '') {
        emit('update:modelValue', null)
        return
    }
    const match = props.options.find((option) => String(option.value) === value)
    emit('update:modelValue', match ? match.value : (value as T))
}
</script>

<template>
    <div class="space-y-1.5">
        <AppLabel v-if="label" :for="selectId" :required="required">
            {{ label }}
        </AppLabel>
        <div class="relative">
            <select
                :id="selectId"
                :value="modelValue ?? ''"
                :disabled="disabled"
                :required="required"
                :name="name"
                :aria-invalid="error ? true : undefined"
                :aria-describedby="describedBy"
                :class="selectClass"
                @change="onChange"
            >
                <option value="" disabled>
                    {{ placeholder }}
                </option>
                <option
                    v-for="option in options"
                    :key="String(option.value)"
                    :value="option.value"
                    :disabled="option.disabled"
                >
                    {{ option.label }}
                </option>
            </select>
            <span
                aria-hidden="true"
                class="pointer-events-none absolute inset-y-0 right-3 flex items-center text-ink-subtle"
            >
                <svg width="14" height="14" viewBox="0 0 20 20" fill="currentColor">
                    <path d="M5 7l5 6 5-6H5z" />
                </svg>
            </span>
        </div>
        <p v-if="hint && !error" :id="hintId" class="text-xs text-ink-subtle">
            {{ hint }}
        </p>
        <p v-if="error" :id="errorId" class="text-xs text-danger-600" role="alert">
            {{ error }}
        </p>
    </div>
</template>
