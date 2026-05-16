import { beforeEach, describe, expect, it, vi } from 'vitest'
import { createPinia, setActivePinia } from 'pinia'
import { useToastStore } from '../toast'

describe('useToastStore', () => {
    beforeEach(() => {
        setActivePinia(createPinia())
        vi.useFakeTimers()
    })

    it('empilha toast com variante padrao info', () => {
        const store = useToastStore()
        store.push({ title: 'Salvo com sucesso' })

        expect(store.toasts).toHaveLength(1)
        expect(store.toasts[0]?.variant).toBe('info')
    })

    it('descarta automaticamente apos o timeout', () => {
        const store = useToastStore()
        store.success('Cliente atualizado')

        expect(store.toasts).toHaveLength(1)
        vi.advanceTimersByTime(5000)
        expect(store.toasts).toHaveLength(0)
    })

    it('mantem toast persistente quando timeout zerado', () => {
        const store = useToastStore()
        store.push({ title: 'Acao manual', timeoutMs: 0 })

        vi.advanceTimersByTime(60_000)
        expect(store.toasts).toHaveLength(1)
    })

    it('dismiss remove o toast pelo id', () => {
        const store = useToastStore()
        const id = store.error('Falhou')
        store.dismiss(id)
        expect(store.toasts).toHaveLength(0)
    })
})
