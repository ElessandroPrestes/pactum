import axios, {
    AxiosError,
    AxiosHeaders,
    type AxiosInstance,
    type AxiosResponse,
    type InternalAxiosRequestConfig,
} from 'axios'

const baseURL = import.meta.env.VITE_API_BASE_URL ?? '/api/v1'

export type ApiValidationErrors = Record<string, string[]>

export interface ApiErrorPayload {
    message: string
    errors?: ApiValidationErrors
    trace_id?: string
}

export class ApiError extends Error {
    readonly status: number
    readonly errors: ApiValidationErrors
    readonly traceId: string | null
    readonly raw: unknown

    constructor(
        message: string,
        status: number,
        options: Partial<ApiErrorPayload> & { raw?: unknown } = {},
    ) {
        super(message)
        this.name = 'ApiError'
        this.status = status
        this.errors = options.errors ?? {}
        this.traceId = options.trace_id ?? null
        this.raw = options.raw
    }

    get isValidation(): boolean {
        return this.status === 422
    }

    get isConflict(): boolean {
        return this.status === 409
    }

    get isUnauthorized(): boolean {
        return this.status === 401
    }

    get isServer(): boolean {
        return this.status >= 500
    }

    firstError(field: string): string | undefined {
        return this.errors[field]?.[0]
    }
}

type TokenProvider = () => string | null
type UnauthorizedHandler = () => void | Promise<void>

let tokenProvider: TokenProvider = () => null
let unauthorizedHandler: UnauthorizedHandler = () => undefined

export function setAuthTokenProvider(provider: TokenProvider): void {
    tokenProvider = provider
}

export function setUnauthorizedHandler(handler: UnauthorizedHandler): void {
    unauthorizedHandler = handler
}

function attachToken(config: InternalAxiosRequestConfig): InternalAxiosRequestConfig {
    const token = tokenProvider()
    if (!token) {
        return config
    }
    if (!config.headers) {
        config.headers = new AxiosHeaders()
    }
    if (config.headers instanceof AxiosHeaders) {
        config.headers.set('Authorization', `Bearer ${token}`)
    } else {
        ;(config.headers as Record<string, string>).Authorization = `Bearer ${token}`
    }
    return config
}

function toApiError(error: AxiosError): ApiError {
    if (!error.response) {
        return new ApiError(error.message || 'Falha de rede ao contatar a API', 0, { raw: error })
    }
    const status = error.response.status
    const data = error.response.data as Partial<ApiErrorPayload> | undefined
    const message = data?.message ?? error.message ?? 'Erro inesperado'
    return new ApiError(message, status, {
        errors: data?.errors,
        trace_id: data?.trace_id,
        raw: error.response.data,
    })
}

export const http: AxiosInstance = axios.create({
    baseURL,
    timeout: 15000,
    headers: {
        Accept: 'application/json',
        'Content-Type': 'application/json',
    },
})

http.interceptors.request.use(attachToken)

http.interceptors.response.use(
    (response: AxiosResponse) => response,
    async (error: AxiosError) => {
        const apiError = toApiError(error)
        if (apiError.isUnauthorized) {
            await unauthorizedHandler()
        }
        return Promise.reject(apiError)
    },
)
