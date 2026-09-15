import { onUnmounted, shallowRef } from 'vue'

import type { Ref } from 'vue'

import { getParsingStatus } from '../api'

import type { ParsingStatus } from '../types'

const BASE_DELAY_MS = 2000

const MAX_DELAY_MS = 15000

const MAX_FAILURES = 5

export function useParsingStatus(organizationId: number, onFinished?: () => void): ParsingStatusState {
    const status = shallowRef<ParsingStatus>('idle')
    const reason = shallowRef<string | null>(null)

    let timer: ReturnType<typeof setTimeout> | null = null
    let failures = 0

    function stop(): void {
        if (timer !== null) {
            clearTimeout(timer)
            timer = null
        }
    }

    function schedule(): void {
        const delay = Math.min(BASE_DELAY_MS * (failures + 1), MAX_DELAY_MS)
        timer = setTimeout(run, delay)
    }

    async function run(): Promise<void> {
        try {
            const response = await getParsingStatus(organizationId)
            failures = 0
            status.value = response.data.status
            reason.value = response.data.reason

            if (response.data.status === 'pending' || response.data.status === 'parsing') {
                schedule()
                return
            }

            if (response.data.status === 'ready') {
                onFinished?.()
            }
        } catch {
            failures += 1

            if (failures <= MAX_FAILURES) {
                schedule()
                return
            }

            status.value = 'error'
            reason.value = 'Status polling failed'
        }
    }

    onUnmounted(stop)

    return { status, reason, start: run, stop }
}

export interface ParsingStatusState {
    status: Ref<ParsingStatus>
    reason: Ref<string | null>
    start: () => void
    stop: () => void
}
