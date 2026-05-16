import { defineStore } from 'pinia'
import { computed, ref, watch } from 'vue'

export type ThemePreference = 'system' | 'light' | 'dark'
export type EffectiveTheme = 'light' | 'dark'

const STORAGE_KEY = 'pactum:theme'

function readStored(): ThemePreference {
    if (typeof localStorage === 'undefined') {
        return 'system'
    }
    const value = localStorage.getItem(STORAGE_KEY)
    return value === 'light' || value === 'dark' ? value : 'system'
}

function systemEffective(): EffectiveTheme {
    if (typeof window === 'undefined' || typeof window.matchMedia !== 'function') {
        return 'light'
    }
    return window.matchMedia('(prefers-color-scheme: dark)').matches ? 'dark' : 'light'
}

function applyToDocument(theme: EffectiveTheme): void {
    if (typeof document === 'undefined') {
        return
    }
    const root = document.documentElement
    if (theme === 'dark') {
        root.classList.add('dark')
    } else {
        root.classList.remove('dark')
    }
}

export const useThemeStore = defineStore('theme', () => {
    const preference = ref<ThemePreference>(readStored())
    const systemPreference = ref<EffectiveTheme>(systemEffective())

    const effective = computed<EffectiveTheme>(() =>
        preference.value === 'system' ? systemPreference.value : preference.value,
    )

    function setPreference(next: ThemePreference): void {
        preference.value = next
        try {
            if (typeof localStorage !== 'undefined') {
                if (next === 'system') {
                    localStorage.removeItem(STORAGE_KEY)
                } else {
                    localStorage.setItem(STORAGE_KEY, next)
                }
            }
        } catch {
            /* ignora persistencia bloqueada */
        }
    }

    function cycle(): void {
        const order: ThemePreference[] = ['system', 'light', 'dark']
        const index = order.indexOf(preference.value)
        const next = order[(index + 1) % order.length] ?? 'system'
        setPreference(next)
    }

    function bind(): void {
        if (typeof window === 'undefined' || typeof window.matchMedia !== 'function') {
            return
        }
        const media = window.matchMedia('(prefers-color-scheme: dark)')
        const handler = (event: MediaQueryListEvent): void => {
            systemPreference.value = event.matches ? 'dark' : 'light'
        }
        if (typeof media.addEventListener === 'function') {
            media.addEventListener('change', handler)
        }
    }

    watch(effective, (next) => applyToDocument(next), { immediate: true, flush: 'sync' })

    return {
        preference,
        systemPreference,
        effective,
        setPreference,
        cycle,
        bind,
    }
})
