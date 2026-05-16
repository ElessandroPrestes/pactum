<script setup lang="ts">
import { computed, useId } from 'vue'
import AppLabel from './AppLabel.vue'

type Variant = 'default' | 'invalid'

const props = withDefaults(
    defineProps<{
        modelValue?: string | number | null
        label?: string
        id?: string
        type?: string
        placeholder?: string
        hint?: string
        error?: string
        required?: boolean
        disabled?: boolean
        readonly?: boolean
        autocomplete?: string
        inputmode?: 'text' | 'email' | 'numeric' | 'tel' | 'search' | 'url'
        name?: string
    }>(),
    {
        modelValue: '',
        label: undefined,
        id: undefined,
        type: 'text',
        placeholder: undefined,
        hint: undefined,
        error: undefined,
        required: false,
        disabled: false,
        readonly: false,
        autocomplete: undefined,
        inputmode: undefined,
        name: undefined,
    },
)

const emit = defineEmits<{
    (e: 'update:modelValue', value: string): void
    (e: 'blur', event: FocusEvent): void
}>()

const generatedId = useId()
const inputId = computed(() => props.id ?? `input-${generatedId}`)
const hintId = computed(() => `${inputId.value}-hint`)
const errorId = computed(() => `${inputId.value}-error`)

const variant = computed<Variant>(() => (props.error ? 'invalid' : 'default'))

const inputClass = computed(() => {
    const base =
        'block w-full rounded-md border bg-surface px-3 py-2 text-sm text-ink shadow-xs placeholder:text-ink-subtle disabled:cursor-not-allowed disabled:bg-surface-muted disabled:text-ink-subtle'
    return variant.value === 'invalid'
        ? `${base} border-danger-500 focus:border-danger-600 focus-visible:ring-danger-500`
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
</script>

<template>
    <div class="space-y-1.5">
        <AppLabel v-if="label" :for="inputId" :required="required">
            {{ label }}
        </AppLabel>
        <input
            :id="inputId"
            :value="modelValue ?? ''"
            :type="type"
            :placeholder="placeholder"
            :disabled="disabled"
            :readonly="readonly"
            :required="required"
            :autocomplete="autocomplete"
            :inputmode="inputmode"
            :name="name"
            :aria-invalid="variant === 'invalid' || undefined"
            :aria-describedby="describedBy"
            :class="inputClass"
            @input="emit('update:modelValue', ($event.target as HTMLInputElement).value)"
            @blur="(event) => emit('blur', event)"
        />
        <p v-if="hint && !error" :id="hintId" class="text-xs text-ink-subtle">
            {{ hint }}
        </p>
        <p v-if="error" :id="errorId" class="text-xs text-danger-600" role="alert">
            {{ error }}
        </p>
    </div>
</template>
