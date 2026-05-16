<script setup lang="ts">
import { computed } from 'vue'
import { useToastStore, type ToastVariant } from '@/stores/toast'

const store = useToastStore()

const toasts = computed(() => store.toasts)

function styles(variant: ToastVariant): string {
    switch (variant) {
        case 'success':
            return 'border-success-500/40 bg-success-50 text-success-700'
        case 'error':
            return 'border-danger-500/40 bg-danger-50 text-danger-700'
        case 'warning':
            return 'border-warning-500/40 bg-warning-50 text-warning-700'
        case 'info':
        default:
            return 'border-info-500/40 bg-info-50 text-info-700'
    }
}

function ariaRole(variant: ToastVariant): 'alert' | 'status' {
    return variant === 'error' || variant === 'warning' ? 'alert' : 'status'
}
</script>

<template>
    <Teleport to="body">
        <div
            class="pointer-events-none fixed inset-x-0 top-4 z-toast flex justify-center px-4 sm:left-auto sm:right-4 sm:top-4 sm:justify-end"
            aria-live="polite"
            aria-atomic="false"
        >
            <ul class="flex w-full max-w-sm flex-col gap-2">
                <li
                    v-for="toast in toasts"
                    :key="toast.id"
                    :role="ariaRole(toast.variant)"
                    :class="[
                        'pointer-events-auto rounded-md border bg-surface p-3 shadow-md',
                        styles(toast.variant),
                    ]"
                >
                    <div class="flex items-start gap-3">
                        <div class="flex-1">
                            <p class="text-sm font-medium leading-5">
                                {{ toast.title }}
                            </p>
                            <p
                                v-if="toast.description"
                                class="mt-0.5 text-xs leading-5 text-ink-muted"
                            >
                                {{ toast.description }}
                            </p>
                        </div>
                        <button
                            type="button"
                            class="rounded p-1 text-current hover:bg-black/5"
                            aria-label="Dispensar notificacao"
                            @click="store.dismiss(toast.id)"
                        >
                            <svg
                                width="14"
                                height="14"
                                viewBox="0 0 20 20"
                                fill="currentColor"
                                aria-hidden="true"
                            >
                                <path
                                    fill-rule="evenodd"
                                    d="M4.293 4.293a1 1 0 011.414 0L10 8.586l4.293-4.293a1 1 0 111.414 1.414L11.414 10l4.293 4.293a1 1 0 01-1.414 1.414L10 11.414l-4.293 4.293a1 1 0 01-1.414-1.414L8.586 10 4.293 5.707a1 1 0 010-1.414z"
                                    clip-rule="evenodd"
                                />
                            </svg>
                        </button>
                    </div>
                </li>
            </ul>
        </div>
    </Teleport>
</template>
