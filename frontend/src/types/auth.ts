export interface AuthUser {
    id: number
    name: string
    email: string
    created_at: string | null
}

export interface LoginPayload {
    email: string
    password: string
    device_name?: string
}

export interface LoginResponse {
    token: string
    user: AuthUser
}
