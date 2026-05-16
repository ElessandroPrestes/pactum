import { defineStore } from 'pinia'
import { ref } from 'vue'

export type ToastVariant = 'success' | 'error' | 'info' | 'warning'

export interface Toast {
    id: string
    variant: ToastVariant
    title: string
    description?: string
    timeoutMs: number
}

interface PushOptions {
    title: string
    description?: string
    variant?: ToastVariant
    timeoutMs?: number
}

const DEFAULT_TIMEOUT = 5000

export const useToastStore = defineStore('toast', () => {
    const toasts = ref<Toast[]>([])

    function dismiss(id: string): void {
        toasts.value = toasts.value.filter((toast) => toast.id !== id)
    }

    function push(options: PushOptions): string {
        const id =
            typeof crypto !== 'undefined' && 'randomUUID' in crypto
                ? crypto.randomUUID()
                : `toast-${Date.now()}-${Math.random().toString(36).slice(2)}`

        const toast: Toast = {
            id,
            variant: options.variant ?? 'info',
            title: options.title,
            description: options.description,
            timeoutMs: options.timeoutMs ?? DEFAULT_TIMEOUT,
        }

        toasts.value = [...toasts.value, toast]

        if (toast.timeoutMs > 0) {
            window.setTimeout(() => dismiss(id), toast.timeoutMs)
        }

        return id
    }

    function success(title: string, description?: string): string {
        return push({ title, description, variant: 'success' })
    }

    function error(title: string, description?: string): string {
        return push({ title, description, variant: 'error', timeoutMs: 7000 })
    }

    function info(title: string, description?: string): string {
        return push({ title, description, variant: 'info' })
    }

    function warning(title: string, description?: string): string {
        return push({ title, description, variant: 'warning' })
    }

    return { toasts, push, dismiss, success, error, info, warning }
})
