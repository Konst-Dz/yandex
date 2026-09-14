export interface ApiErrorPayload {
    message: string
    code: number
    errors?: Record<string, string[]>
}

export class ApiError extends Error {
    readonly status: number
    readonly errors: Record<string, string[]>

    constructor(status: number, payload: ApiErrorPayload | null) {
        super(payload?.message ?? `Request failed (${status})`)
        this.name = 'ApiError'
        this.status = status
        this.errors = payload?.errors ?? {}
    }

    get fieldMessages(): string[] {
        return Object.values(this.errors).flat()
    }
}

export async function apiFetch<T>(path: string, options: RequestInit = {}): Promise<T> {
    const method = (options.method ?? 'GET').toUpperCase()
    const isMutating = method !== 'GET'

    const headers = new Headers(options.headers)
    headers.set('Accept', 'application/json')

    if (isMutating) {
        headers.set('Content-Type', 'application/json')
        headers.set('X-XSRF-TOKEN', await readCsrfToken())
    }

    const response = await fetch(path, {
        ...options,
        credentials: 'include',
        headers,
    })

    const payload = await response.json().catch(() => null)

    if (!response.ok) {
        throw new ApiError(response.status, payload)
    }

    return payload as T
}

let csrfCookieRequest: Promise<void> | null = null

async function readCsrfToken(): Promise<string> {
    if (!document.cookie.includes('XSRF-TOKEN=')) {
        csrfCookieRequest ??= fetch('/sanctum/csrf-cookie', { credentials: 'include' })
            .then(() => undefined)
            .finally(() => {
                csrfCookieRequest = null
            })

        await csrfCookieRequest
    }

    return readCookie('XSRF-TOKEN') ?? ''
}

function readCookie(name: string): string | null {
    const match = document.cookie.match(new RegExp(`(?:^|;\\s*)${name}=([^;]*)`))

    return match !== null ? decodeURIComponent(match[1] ?? '') : null
}
