import { shallowRef } from 'vue'

import { fetchMe, loginRequest, logoutRequest } from '../api'

import type { AuthUser } from '../types'

const user = shallowRef<AuthUser | null>(null)
let sessionChecked = false

export function useAuth() {
    function ensureLoaded(): Promise<AuthUser | null> {
        if (sessionChecked) {
            return Promise.resolve(user.value)
        }

        return fetchMe()
            .then((response) => {
                user.value = response.data
            })
            .catch(() => {
                user.value = null
            })
            .finally(() => {
                sessionChecked = true
            })
            .then(() => user.value)
    }

    async function login(email: string, password: string): Promise<AuthUser> {
        const response = await loginRequest(email, password)
        user.value = response.data
        sessionChecked = true

        return response.data
    }

    async function logout(): Promise<void> {
        try {
            await logoutRequest()
        } finally {
            user.value = null
        }
    }

    return { user, ensureLoaded, login, logout }
}
