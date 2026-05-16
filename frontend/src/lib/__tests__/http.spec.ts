import { afterEach, beforeEach, describe, expect, it, vi } from 'vitest'
import type { AxiosAdapter, AxiosRequestConfig, AxiosResponse } from 'axios'
import { ApiError, http, setAuthTokenProvider, setUnauthorizedHandler } from '../http'

const originalAdapter = http.defaults.adapter

type AdapterImpl = (config: AxiosRequestConfig) => Promise<AxiosResponse>

function makeAdapter(impl: AdapterImpl): AxiosAdapter {
    return ((config) => impl(config)) as AxiosAdapter
}

function ok(data: unknown, config: AxiosRequestConfig): AxiosResponse {
    return {
        data,
        status: 200,
        statusText: 'OK',
        headers: {},
        config: config as AxiosResponse['config'],
    }
}

function fail(status: number, data: unknown, config: AxiosRequestConfig): Promise<never> {
    const response: AxiosResponse = {
        data,
        status,
        statusText: 'ERR',
        headers: {},
        config: config as AxiosResponse['config'],
    }
    const error = new Error(`Request failed with status ${status}`) as Error & {
        response?: AxiosResponse
        isAxiosError?: boolean
        config?: AxiosRequestConfig
    }
    error.response = response
    error.isAxiosError = true
    error.config = config
    return Promise.reject(error)
}

describe('http client', () => {
    beforeEach(() => {
        setAuthTokenProvider(() => null)
        setUnauthorizedHandler(() => undefined)
    })

    afterEach(() => {
        http.defaults.adapter = originalAdapter
    })

    it('injeta Authorization Bearer quando ha token', async () => {
        setAuthTokenProvider(() => 'tk-abc')
        const seen = vi.fn<(config: AxiosRequestConfig) => void>()
        http.defaults.adapter = makeAdapter((config) => {
            seen(config)
            return Promise.resolve(ok({ ok: true }, config))
        })

        await http.get('/qualquer')

        const config = seen.mock.calls[0]?.[0]
        const auth = (config?.headers as Record<string, string> | undefined)?.Authorization
        expect(auth).toBe('Bearer tk-abc')
    })

    it('nao injeta Authorization quando token e nulo', async () => {
        const seen = vi.fn<(config: AxiosRequestConfig) => void>()
        http.defaults.adapter = makeAdapter((config) => {
            seen(config)
            return Promise.resolve(ok({}, config))
        })

        await http.get('/anon')

        const config = seen.mock.calls[0]?.[0]
        const auth = (config?.headers as Record<string, string> | undefined)?.Authorization
        expect(auth).toBeUndefined()
    })

    it('mapeia erro 422 para ApiError com isValidation e errors', async () => {
        http.defaults.adapter = makeAdapter((config) =>
            fail(
                422,
                {
                    message: 'O e-mail e obrigatorio.',
                    errors: { email: ['O e-mail e obrigatorio.'] },
                    trace_id: 'tr-1',
                },
                config,
            ),
        )

        await expect(http.post('/auth/login', {})).rejects.toMatchObject({
            status: 422,
            traceId: 'tr-1',
        })

        try {
            await http.post('/auth/login', {})
        } catch (error) {
            expect(error).toBeInstanceOf(ApiError)
            const api = error as ApiError
            expect(api.isValidation).toBe(true)
            expect(api.firstError('email')).toBe('O e-mail e obrigatorio.')
        }
    })

    it('mapeia erro 409 para ApiError com isConflict', async () => {
        http.defaults.adapter = makeAdapter((config) =>
            fail(409, { message: 'Conflito de versao' }, config),
        )

        const promise = http.put('/contracts/1', {})
        await expect(promise).rejects.toBeInstanceOf(ApiError)
        await promise.catch((error: ApiError) => {
            expect(error.isConflict).toBe(true)
            expect(error.message).toBe('Conflito de versao')
        })
    })

    it('mapeia erro 5xx para ApiError com isServer', async () => {
        http.defaults.adapter = makeAdapter((config) =>
            fail(500, { message: 'falha interna' }, config),
        )

        await http.get('/qualquer').catch((error: ApiError) => {
            expect(error.isServer).toBe(true)
            expect(error.status).toBe(500)
        })
    })

    it('dispara unauthorized handler em 401', async () => {
        const handler = vi.fn()
        setUnauthorizedHandler(handler)
        http.defaults.adapter = makeAdapter((config) =>
            fail(401, { message: 'nao autenticado' }, config),
        )

        await http.get('/auth/me').catch(() => undefined)

        expect(handler).toHaveBeenCalledTimes(1)
    })
})
