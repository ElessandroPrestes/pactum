<script setup lang="ts">
withDefaults(
    defineProps<{
        for?: string
        required?: boolean
    }>(),
    {
        for: undefined,
        required: false,
    },
)
</script>

<template>
    <label :for="$props.for" class="block text-sm font-medium text-ink">
        <slot />
        <span v-if="required" aria-hidden="true" class="ml-0.5 text-danger-600">*</span>
        <span v-if="required" class="sr-only">(obrigatorio)</span>
    </label>
</template>
