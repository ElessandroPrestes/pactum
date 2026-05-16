<script setup lang="ts">
import { computed } from 'vue'
import { RouterLink, useRoute } from 'vue-router'
import AppIcon from './AppIcon.vue'

interface Crumb {
    label: string
    to?: string
}

const route = useRoute()

const labelByResource: Record<string, { label: string; path: string }> = {
    clientes: { label: 'Clientes', path: '/clientes' },
    servicos: { label: 'Servicos', path: '/servicos' },
    contratos: { label: 'Contratos', path: '/contratos' },
}

function leafLabel(): string {
    if (typeof route.meta.title === 'string') {
        return route.meta.title
    }
    return ''
}

const crumbs = computed<Crumb[]>(() => {
    const segments = route.path.split('/').filter(Boolean)
    if (segments.length === 0) {
        return []
    }

    const trail: Crumb[] = [{ label: 'Visao geral', to: '/' }]
    const head = segments[0]
    if (head && labelByResource[head]) {
        const resource = labelByResource[head]
        const isResourceRoot = segments.length === 1
        trail.push({
            label: resource.label,
            to: isResourceRoot ? undefined : resource.path,
        })

        if (!isResourceRoot) {
            const tail = leafLabel() || segments.slice(1).join(' / ')
            trail.push({ label: tail })
        }
        return trail
    }

    trail.push({ label: leafLabel() || (head ?? '') })
    return trail
})

const visible = computed(() => crumbs.value.length > 1)
</script>

<template>
    <nav v-if="visible" aria-label="Trilha de navegacao" class="mb-4">
        <ol class="flex flex-wrap items-center gap-1 text-xs text-ink-subtle">
            <li
                v-for="(crumb, index) in crumbs"
                :key="`${crumb.label}-${index}`"
                class="flex items-center gap-1"
            >
                <RouterLink
                    v-if="crumb.to"
                    :to="crumb.to"
                    class="rounded px-1 py-0.5 hover:bg-surface-muted hover:text-ink"
                >
                    {{ crumb.label }}
                </RouterLink>
                <span v-else class="rounded px-1 py-0.5 font-medium text-ink" aria-current="page">
                    {{ crumb.label }}
                </span>
                <AppIcon
                    v-if="index < crumbs.length - 1"
                    name="chevron-right"
                    :size="12"
                    class="text-ink-subtle"
                />
            </li>
        </ol>
    </nav>
</template>
