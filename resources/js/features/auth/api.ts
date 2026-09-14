import { apiFetch } from '@/shared/api'

import type { AuthUser } from './types'

export function fetchMe(): Promise<{ data: AuthUser | null }> {
    return apiFetch('/api/auth/me')
}

export function loginRequest(email: string, password: string): Promise<{ data: AuthUser }> {
    return apiFetch('/api/auth/login', {
        method: 'POST',
        body: JSON.stringify({ email, password }),
    })
}

export function logoutRequest(): Promise<void> {
    return apiFetch('/api/auth/logout', { method: 'POST' })
}
