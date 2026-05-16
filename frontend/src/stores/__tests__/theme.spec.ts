import { beforeEach, describe, expect, it, vi } from 'vitest'
import { createPinia, setActivePinia } from 'pinia'
import { useThemeStore } from '../theme'

function setMatchMedia(prefersDark: boolean): void {
    window.matchMedia = vi.fn().mockImplementation((query: string) => ({
        matches: query.includes('dark') ? prefersDark : false,
        media: query,
        onchange: null,
        addEventListener: vi.fn(),
        removeEventListener: vi.fn(),
        addListener: vi.fn(),
        removeListener: vi.fn(),
        dispatchEvent: vi.fn(),
    }))
}

describe('useThemeStore', () => {
    beforeEach(() => {
        setActivePinia(createPinia())
        localStorage.clear()
        document.documentElement.classList.remove('dark')
        setMatchMedia(false)
    })

    it('inicia com preferencia system quando nada foi salvo', () => {
        const theme = useThemeStore()
        expect(theme.preference).toBe('system')
        expect(theme.effective).toBe('light')
    })

    it('respeita prefers-color-scheme: dark em modo system', () => {
        setMatchMedia(true)
        const theme = useThemeStore()
        expect(theme.effective).toBe('dark')
    })

    it('persiste preferencia explicita em localStorage', () => {
        const theme = useThemeStore()
        theme.setPreference('dark')
        expect(localStorage.getItem('pactum:theme')).toBe('dark')
        expect(theme.effective).toBe('dark')
        expect(document.documentElement.classList.contains('dark')).toBe(true)
    })

    it('removeItem ao voltar para system', () => {
        const theme = useThemeStore()
        theme.setPreference('light')
        expect(localStorage.getItem('pactum:theme')).toBe('light')

        theme.setPreference('system')
        expect(localStorage.getItem('pactum:theme')).toBeNull()
    })

    it('cycle alterna system -> light -> dark -> system', () => {
        const theme = useThemeStore()
        expect(theme.preference).toBe('system')

        theme.cycle()
        expect(theme.preference).toBe('light')

        theme.cycle()
        expect(theme.preference).toBe('dark')

        theme.cycle()
        expect(theme.preference).toBe('system')
    })

    it('aplica classe dark no documento ao mudar para dark', () => {
        const theme = useThemeStore()
        theme.setPreference('dark')
        expect(document.documentElement.classList.contains('dark')).toBe(true)

        theme.setPreference('light')
        expect(document.documentElement.classList.contains('dark')).toBe(false)
    })
})
