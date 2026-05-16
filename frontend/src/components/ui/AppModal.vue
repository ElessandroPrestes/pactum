<script setup lang="ts">
import { computed, nextTick, onBeforeUnmount, ref, useId, watch } from 'vue'

const props = withDefaults(
    defineProps<{
        open: boolean
        title: string
        description?: string
        size?: 'sm' | 'md' | 'lg'
        closeOnBackdrop?: boolean
        closeLabel?: string
    }>(),
    {
        description: undefined,
        size: 'md',
        closeOnBackdrop: true,
        closeLabel: 'Fechar',
    },
)

const emit = defineEmits<{
    (e: 'close'): void
}>()

const dialogRef = ref<HTMLElement | null>(null)
const generatedId = useId()
const titleId = computed(() => `modal-${generatedId}-title`)
const descriptionId = computed(() => `modal-${generatedId}-description`)

const sizeClass = computed(() => {
    switch (props.size) {
        case 'sm':
            return 'max-w-sm'
        case 'lg':
            return 'max-w-2xl'
        default:
            return 'max-w-md'
    }
})

function onKeydown(event: KeyboardEvent): void {
    if (event.key === 'Escape') {
        event.stopPropagation()
        emit('close')
    }
}

function onBackdrop(): void {
    if (props.closeOnBackdrop) {
        emit('close')
    }
}

watch(
    () => props.open,
    async (open) => {
        if (open) {
            document.addEventListener('keydown', onKeydown)
            await nextTick()
            dialogRef.value?.focus()
        } else {
            document.removeEventListener('keydown', onKeydown)
        }
    },
    { immediate: true },
)

onBeforeUnmount(() => {
    document.removeEventListener('keydown', onKeydown)
})
</script>

<template>
    <Teleport to="body">
        <Transition
            enter-active-class="transition-opacity duration-150"
            enter-from-class="opacity-0"
            leave-active-class="transition-opacity duration-100"
            leave-to-class="opacity-0"
        >
            <div
                v-if="open"
                class="fixed inset-0 z-modal flex items-end justify-center bg-ink/40 p-4 sm:items-center"
                @click.self="onBackdrop"
            >
                <div
                    ref="dialogRef"
                    role="dialog"
                    aria-modal="true"
                    :aria-labelledby="titleId"
                    :aria-describedby="description ? descriptionId : undefined"
                    tabindex="-1"
                    :class="['w-full rounded-lg bg-surface shadow-lg outline-none', sizeClass]"
                >
                    <header
                        class="flex items-start justify-between gap-4 border-b border-border px-5 py-4"
                    >
                        <div>
                            <h2 :id="titleId" class="text-lg font-semibold text-ink">
                                {{ title }}
                            </h2>
                            <p
                                v-if="description"
                                :id="descriptionId"
                                class="mt-1 text-sm text-ink-muted"
                            >
                                {{ description }}
                            </p>
                        </div>
                        <button
                            type="button"
                            class="rounded-md p-1 text-ink-subtle hover:bg-surface-muted"
                            :aria-label="closeLabel"
                            @click="emit('close')"
                        >
                            <svg
                                width="18"
                                height="18"
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
                    </header>
                    <div class="px-5 py-4 text-sm text-ink">
                        <slot />
                    </div>
                    <footer
                        v-if="$slots.footer"
                        class="flex flex-wrap justify-end gap-2 border-t border-border px-5 py-3"
                    >
                        <slot name="footer" />
                    </footer>
                </div>
            </div>
        </Transition>
    </Teleport>
</template>
