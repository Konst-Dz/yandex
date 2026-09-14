import { shallowRef } from 'vue'

import type { Ref } from 'vue'

export type AsyncStatus = 'idle' | 'loading' | 'success' | 'error'

export function useAsync<T>(fn: () => Promise<T>): AsyncState<T> {
    const data: Ref<T | null> = shallowRef(null)
    const status: Ref<AsyncStatus> = shallowRef('idle')
    const error: Ref<string | null> = shallowRef(null)

    let runId = 0

    async function run(): Promise<T | null> {
        const id = ++runId

        status.value = 'loading'
        error.value = null

        try {
            const result = await fn()

            if (id !== runId) {
                return null
            }

            data.value = result
            status.value = 'success'

            return result
        } catch (e) {
            if (id !== runId) {
                return null
            }

            error.value = e instanceof Error ? e.message : 'Unknown error'
            status.value = 'error'

            return null
        }
    }

    return { data, status, error, run }
}

export interface AsyncState<T> {
    data: Ref<T | null>
    status: Ref<AsyncStatus>
    error: Ref<string | null>
    run: () => Promise<T | null>
}
