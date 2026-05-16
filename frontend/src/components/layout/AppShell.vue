<script setup lang="ts">
import { ref, watch } from 'vue'
import { useRoute } from 'vue-router'
import AppHeader from './AppHeader.vue'
import AppSidebar from './AppSidebar.vue'
import AppIcon from './AppIcon.vue'
import { AppToastContainer } from '@/components/ui'

const mobileOpen = ref(false)
const route = useRoute()

watch(
    () => route.fullPath,
    () => {
        mobileOpen.value = false
    },
)

function closeMobile(): void {
    mobileOpen.value = false
}
</script>

<template>
    <div class="min-h-screen bg-surface-muted text-ink">
        <a href="#conteudo-principal" class="pactum-skip-link">Pular para o conteudo principal</a>

        <aside
            class="fixed inset-y-0 left-0 z-overlay hidden w-72 border-r border-border bg-surface lg:block"
        >
            <AppSidebar />
        </aside>

        <Transition
            enter-active-class="transition-opacity duration-150"
            enter-from-class="opacity-0"
            leave-active-class="transition-opacity duration-100"
            leave-to-class="opacity-0"
        >
            <div
                v-if="mobileOpen"
                class="fixed inset-0 z-overlay bg-ink/40 lg:hidden"
                aria-hidden="true"
                @click="closeMobile"
            />
        </Transition>

        <Transition
            enter-active-class="transition-transform duration-200 ease-emphasized"
            enter-from-class="-translate-x-full"
            leave-active-class="transition-transform duration-150 ease-emphasized"
            leave-to-class="-translate-x-full"
        >
            <aside
                v-if="mobileOpen"
                class="fixed inset-y-0 left-0 z-overlay flex w-72 flex-col border-r border-border bg-surface shadow-lg lg:hidden"
                role="dialog"
                aria-modal="true"
                aria-label="Menu de navegacao"
            >
                <div class="flex items-center justify-end px-3 pt-3">
                    <button
                        type="button"
                        class="inline-flex h-9 w-9 items-center justify-center rounded-md border border-border text-ink-muted hover:bg-surface-muted"
                        aria-label="Fechar menu de navegacao"
                        @click="closeMobile"
                    >
                        <AppIcon name="close" />
                    </button>
                </div>
                <div class="flex-1 overflow-y-auto">
                    <AppSidebar :on-navigate="closeMobile" />
                </div>
            </aside>
        </Transition>

        <div class="flex min-h-screen flex-col lg:pl-72">
            <AppHeader @open-menu="mobileOpen = true" />
            <main id="conteudo-principal" class="flex-1 px-4 py-6 sm:px-6 lg:px-8" tabindex="-1">
                <slot />
            </main>
        </div>

        <AppToastContainer />
    </div>
</template>
