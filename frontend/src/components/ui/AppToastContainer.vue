<script setup lang="ts">
import { computed } from 'vue'
import { useToastStore, type ToastVariant } from '@/stores/toast'

const store = useToastStore()

const toasts = computed(() => store.toasts)

interface VariantStyle {
    accent: string
    icon: string
    iconPath: string
}

const VARIANT_STYLES: Record<ToastVariant, VariantStyle> = {
    success: {
        accent: 'before:bg-success-500',
        icon: 'text-success-600 dark:text-success-500',
        iconPath:
            'M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 10-1.414 1.414l2 2a1 1 0 001.414 0l4-4z',
    },
    error: {
        accent: 'before:bg-danger-500',
        icon: 'text-danger-600 dark:text-danger-500',
        iconPath:
            'M18 10a8 8 0 11-16 0 8 8 0 0116 0zm-7 4a1 1 0 11-2 0 1 1 0 012 0zm-1-9a1 1 0 00-1 1v4a1 1 0 102 0V6a1 1 0 00-1-1z',
    },
    warning: {
        accent: 'before:bg-warning-500',
        icon: 'text-warning-600 dark:text-warning-500',
        iconPath:
            'M8.257 3.099c.765-1.36 2.722-1.36 3.486 0l6.518 11.59c.75 1.334-.213 2.99-1.742 2.99H3.482c-1.53 0-2.493-1.656-1.743-2.99L8.257 3.1zM11 13a1 1 0 10-2 0 1 1 0 002 0zm-1-8a1 1 0 00-1 1v3a1 1 0 002 0V6a1 1 0 00-1-1z',
    },
    info: {
        accent: 'before:bg-info-500',
        icon: 'text-info-600 dark:text-info-500',
        iconPath:
            'M18 10a8 8 0 11-16 0 8 8 0 0116 0zm-7-4a1 1 0 11-2 0 1 1 0 012 0zM9 9a1 1 0 000 2v3a1 1 0 001 1h1a1 1 0 100-2v-3a1 1 0 00-1-1H9z',
    },
}

function variantStyle(variant: ToastVariant): VariantStyle {
    return VARIANT_STYLES[variant]
}

function ariaRole(variant: ToastVariant): 'alert' | 'status' {
    return variant === 'error' || variant === 'warning' ? 'alert' : 'status'
}
</script>

<template>
    <Teleport to="body">
        <div
            class="pointer-events-none fixed inset-x-0 top-6 z-toast flex justify-center px-4 sm:top-8"
            aria-live="polite"
            aria-atomic="false"
        >
            <TransitionGroup
                tag="ul"
                class="flex w-full max-w-md flex-col items-stretch gap-3"
                enter-active-class="transition duration-200 ease-emphasized"
                enter-from-class="-translate-y-3 opacity-0"
                enter-to-class="translate-y-0 opacity-100"
                leave-active-class="absolute inset-x-0 transition duration-150 ease-out"
                leave-from-class="opacity-100 scale-100"
                leave-to-class="opacity-0 scale-95"
                move-class="transition duration-200 ease-emphasized"
            >
                <li
                    v-for="toast in toasts"
                    :key="toast.id"
                    :role="ariaRole(toast.variant)"
                    class="pointer-events-auto relative overflow-hidden rounded-lg border border-border bg-surface py-3 pl-5 pr-3 shadow-lg before:absolute before:inset-y-0 before:left-0 before:w-1 before:content-['']"
                    :class="variantStyle(toast.variant).accent"
                >
                    <div class="flex items-start gap-3">
                        <svg
                            class="mt-0.5 size-5 shrink-0"
                            :class="variantStyle(toast.variant).icon"
                            viewBox="0 0 20 20"
                            fill="currentColor"
                            aria-hidden="true"
                        >
                            <path
                                fill-rule="evenodd"
                                :d="variantStyle(toast.variant).iconPath"
                                clip-rule="evenodd"
                            />
                        </svg>
                        <div class="min-w-0 flex-1">
                            <p class="text-sm font-medium leading-5 text-ink">
                                {{ toast.title }}
                            </p>
                            <p
                                v-if="toast.description"
                                class="mt-1 text-xs leading-5 text-ink-muted"
                            >
                                {{ toast.description }}
                            </p>
                        </div>
                        <button
                            type="button"
                            class="-mr-1 -mt-1 shrink-0 rounded-md p-1.5 text-ink-muted transition-colors hover:bg-surface-muted hover:text-ink focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-brand-500"
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
            </TransitionGroup>
        </div>
    </Teleport>
</template>
