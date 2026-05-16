import { beforeEach, describe, expect, it, vi } from 'vitest'
import { createPinia, setActivePinia } from 'pinia'

vi.mock('@/lib/http', () => ({
    http: {
        post: vi.fn(),
        get: vi.fn(),
    },
}))

import { http } from '@/lib/http'
import { useAuthStore } from '../auth'

const STORAGE_KEY = 'pactum.auth'

function loginResponse() {
    return {
        data: {
            token: 'tk-1',
            user: {
                id: 1,
                name: 'Maria',
                email: 'maria@empresa.com',
                created_at: '2026-05-16T00:00:00+00:00',
            },
        },
    }
}

describe('useAuthStore', () => {
    beforeEach(() => {
        setActivePinia(createPinia())
        window.localStorage.clear()
        vi.clearAllMocks()
    })

    it('login persiste token e user em localStorage', async () => {
        vi.mocked(http.post).mockResolvedValueOnce(loginResponse())
        const store = useAuthStore()

        const user = await store.login({ email: 'maria@empresa.com', password: 'segredo' })

        expect(user.email).toBe('maria@empresa.com')
        expect(store.token).toBe('tk-1')
        expect(store.isAuthenticated).toBe(true)
        expect(window.localStorage.getItem(STORAGE_KEY)).toContain('tk-1')
    })

    it('login com erro limpa estado e propaga excecao', async () => {
        vi.mocked(http.post).mockRejectedValueOnce(new Error('boom'))
        const store = useAuthStore()

        await expect(store.login({ email: 'x@x.com', password: 'y' })).rejects.toThrow('boom')

        expect(store.token).toBeNull()
        expect(store.isAuthenticated).toBe(false)
        expect(window.localStorage.getItem(STORAGE_KEY)).toBeNull()
    })

    it('logout limpa sessao mesmo se chamada remota falhar', async () => {
        vi.mocked(http.post).mockResolvedValueOnce(loginResponse())
        vi.mocked(http.post).mockRejectedValueOnce(new Error('rede'))

        const store = useAuthStore()
        await store.login({ email: 'maria@empresa.com', password: 'segredo' })
        await store.logout()

        expect(store.token).toBeNull()
        expect(store.user).toBeNull()
        expect(window.localStorage.getItem(STORAGE_KEY)).toBeNull()
    })

    it('hidrata estado a partir do localStorage na inicializacao', () => {
        window.localStorage.setItem(
            STORAGE_KEY,
            JSON.stringify({
                token: 'persistido',
                user: { id: 7, name: 'Joao', email: 'j@e.com', created_at: null },
            }),
        )

        const store = useAuthStore()

        expect(store.token).toBe('persistido')
        expect(store.user?.id).toBe(7)
        expect(store.isAuthenticated).toBe(true)
    })

    it('fetchMe sem token retorna null sem fazer requisicao', async () => {
        const store = useAuthStore()
        const result = await store.fetchMe()

        expect(result).toBeNull()
        expect(http.get).not.toHaveBeenCalled()
    })

    it('fetchMe atualiza user e tolera resposta wrappeada em data', async () => {
        vi.mocked(http.post).mockResolvedValueOnce(loginResponse())
        const store = useAuthStore()
        await store.login({ email: 'maria@empresa.com', password: 'segredo' })

        vi.mocked(http.get).mockResolvedValueOnce({
            data: {
                data: {
                    id: 1,
                    name: 'Maria Atualizada',
                    email: 'maria@empresa.com',
                    created_at: null,
                },
            },
        })

        const user = await store.fetchMe()

        expect(user?.name).toBe('Maria Atualizada')
        expect(store.user?.name).toBe('Maria Atualizada')
    })
})
