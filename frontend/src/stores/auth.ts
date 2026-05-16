import { defineStore } from 'pinia'
import { computed, ref } from 'vue'
import { http } from '@/lib/http'
import type { AuthUser, LoginPayload, LoginResponse } from '@/types/auth'

const STORAGE_KEY = 'pactum.auth'

interface PersistedAuth {
    token: string | null
    user: AuthUser | null
}

function readPersisted(): PersistedAuth {
    if (typeof window === 'undefined') {
        return { token: null, user: null }
    }
    try {
        const raw = window.localStorage.getItem(STORAGE_KEY)
        if (!raw) {
            return { token: null, user: null }
        }
        const parsed = JSON.parse(raw) as Partial<PersistedAuth>
        return {
            token: typeof parsed.token === 'string' ? parsed.token : null,
            user: (parsed.user as AuthUser | null) ?? null,
        }
    } catch {
        return { token: null, user: null }
    }
}

function writePersisted(data: PersistedAuth): void {
    if (typeof window === 'undefined') {
        return
    }
    if (!data.token) {
        window.localStorage.removeItem(STORAGE_KEY)
        return
    }
    window.localStorage.setItem(STORAGE_KEY, JSON.stringify(data))
}

export const useAuthStore = defineStore('auth', () => {
    const persisted = readPersisted()

    const token = ref<string | null>(persisted.token)
    const user = ref<AuthUser | null>(persisted.user)
    const status = ref<'idle' | 'loading' | 'ready'>('idle')

    const isAuthenticated = computed(() => Boolean(token.value))

    function persist(): void {
        writePersisted({ token: token.value, user: user.value })
    }

    function setSession(payload: LoginResponse): void {
        token.value = payload.token
        user.value = payload.user
        status.value = 'ready'
        persist()
    }

    function clearSession(): void {
        token.value = null
        user.value = null
        status.value = 'idle'
        persist()
    }

    async function login(payload: LoginPayload): Promise<AuthUser> {
        status.value = 'loading'
        try {
            const response = await http.post<LoginResponse>('/auth/login', payload)
            setSession(response.data)
            return response.data.user
        } catch (error) {
            clearSession()
            throw error
        }
    }

    async function logout(): Promise<void> {
        if (!token.value) {
            clearSession()
            return
        }
        try {
            await http.post('/auth/logout')
        } catch {
            // mesmo se falhar, derruba a sessao local
        } finally {
            clearSession()
        }
    }

    async function fetchMe(): Promise<AuthUser | null> {
        if (!token.value) {
            return null
        }
        status.value = 'loading'
        try {
            const response = await http.get<{ data: AuthUser } | AuthUser>('/auth/me')
            const payload = 'data' in response.data ? response.data.data : response.data
            user.value = payload
            status.value = 'ready'
            persist()
            return payload
        } catch (error) {
            clearSession()
            throw error
        }
    }

    return {
        token,
        user,
        status,
        isAuthenticated,
        login,
        logout,
        fetchMe,
        setSession,
        clearSession,
    }
})
