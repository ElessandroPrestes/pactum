<script setup lang="ts">
import { computed, nextTick, onBeforeUnmount, ref, watch } from 'vue'
import { useRoute, useRouter } from 'vue-router'
import { useAuthStore } from '@/stores/auth'
import { useToastStore } from '@/stores/toast'
import AppIcon from './AppIcon.vue'

const auth = useAuthStore()
const toasts = useToastStore()
const router = useRouter()
const route = useRoute()

const open = ref(false)
const loggingOut = ref(false)
const menuId = `user-menu-${Math.random().toString(36).slice(2, 9)}`
const triggerRef = ref<HTMLButtonElement | null>(null)
const menuRef = ref<HTMLElement | null>(null)

const displayName = computed(() => auth.user?.name ?? 'Sessao')
const email = computed(() => auth.user?.email ?? '')
const initials = computed(() => {
    const name = auth.user?.name?.trim()
    if (!name) return 'P'
    const parts = name.split(/\s+/).filter(Boolean)
    if (parts.length === 1) {
        return parts[0].slice(0, 2).toUpperCase()
    }
    return (parts[0][0] + parts[parts.length - 1][0]).toUpperCase()
})

function close(): void {
    if (!open.value) return
    open.value = false
    nextTick(() => triggerRef.value?.focus())
}

async function toggle(): Promise<void> {
    open.value = !open.value
    if (open.value) {
        await nextTick()
        const firstItem = menuRef.value?.querySelector<HTMLElement>('[role="menuitem"]')
        firstItem?.focus()
    }
}

function onClickOutside(event: MouseEvent): void {
    if (!open.value) return
    const target = event.target as Node
    if (menuRef.value?.contains(target) || triggerRef.value?.contains(target)) {
        return
    }
    open.value = false
}

function onKeydown(event: KeyboardEvent): void {
    if (event.key === 'Escape' && open.value) {
        event.stopPropagation()
        close()
    }
}

async function handleLogout(): Promise<void> {
    if (loggingOut.value) return
    loggingOut.value = true
    try {
        await auth.logout()
        toasts.push({ variant: 'success', title: 'Sessao encerrada com sucesso.' })
        await router.push({ name: 'login' })
    } catch {
        toasts.push({
            variant: 'error',
            title: 'Nao foi possivel encerrar a sessao.',
            description: 'Voce foi deslogado localmente. Faca login novamente.',
        })
        await router.push({ name: 'login' })
    } finally {
        loggingOut.value = false
        open.value = false
    }
}

watch(
    () => route.fullPath,
    () => {
        open.value = false
    },
)

if (typeof document !== 'undefined') {
    document.addEventListener('click', onClickOutside)
    document.addEventListener('keydown', onKeydown)
    onBeforeUnmount(() => {
        document.removeEventListener('click', onClickOutside)
        document.removeEventListener('keydown', onKeydown)
    })
}
</script>

<template>
    <div class="relative">
        <button
            :id="`${menuId}-trigger`"
            ref="triggerRef"
            type="button"
            class="inline-flex h-9 items-center gap-2 rounded-md border border-border bg-surface px-2 text-sm text-ink transition-colors hover:bg-surface-muted focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-brand-500"
            aria-haspopup="menu"
            :aria-expanded="open"
            :aria-controls="menuId"
            @click="toggle"
        >
            <span
                class="inline-flex h-6 w-6 items-center justify-center rounded-full bg-brand-100 text-xs font-semibold text-brand-700 dark:bg-brand-500/20 dark:text-brand-300"
                aria-hidden="true"
                >{{ initials }}</span
            >
            <span class="hidden max-w-[10rem] truncate sm:inline">{{ displayName }}</span>
            <AppIcon
                name="chevron-down"
                :size="14"
                class="text-ink-muted transition-transform"
                :class="{ 'rotate-180': open }"
            />
        </button>

        <Transition
            enter-active-class="transition duration-150 ease-emphasized"
            enter-from-class="opacity-0 -translate-y-1"
            enter-to-class="opacity-100 translate-y-0"
            leave-active-class="transition duration-100 ease-out"
            leave-from-class="opacity-100"
            leave-to-class="opacity-0"
        >
            <div
                v-if="open"
                :id="menuId"
                ref="menuRef"
                class="absolute right-0 top-full z-dropdown mt-2 w-64 origin-top-right rounded-lg border border-border bg-surface p-1 shadow-lg"
                role="menu"
                :aria-labelledby="`${menuId}-trigger`"
            >
                <div
                    class="flex items-start gap-3 rounded-md px-3 py-2.5"
                    role="presentation"
                >
                    <span
                        class="inline-flex h-9 w-9 shrink-0 items-center justify-center rounded-full bg-brand-100 text-sm font-semibold text-brand-700 dark:bg-brand-500/20 dark:text-brand-300"
                        aria-hidden="true"
                        >{{ initials }}</span
                    >
                    <div class="min-w-0 flex-1">
                        <p class="truncate text-sm font-medium text-ink">{{ displayName }}</p>
                        <p v-if="email" class="truncate text-xs text-ink-muted">{{ email }}</p>
                    </div>
                </div>

                <div class="my-1 h-px bg-border" role="separator" />

                <button
                    type="button"
                    role="menuitem"
                    class="flex w-full items-center gap-2 rounded-md px-3 py-2 text-left text-sm text-ink transition-colors hover:bg-danger-50 hover:text-danger-700 focus-visible:bg-danger-50 focus-visible:text-danger-700 focus-visible:outline-none disabled:cursor-not-allowed disabled:opacity-60 dark:hover:bg-danger-500/10 dark:hover:text-danger-500 dark:focus-visible:bg-danger-500/10 dark:focus-visible:text-danger-500"
                    :disabled="loggingOut"
                    @click="handleLogout"
                >
                    <AppIcon name="log-out" :size="16" />
                    <span>{{ loggingOut ? 'Saindo...' : 'Sair' }}</span>
                </button>
            </div>
        </Transition>
    </div>
</template>
