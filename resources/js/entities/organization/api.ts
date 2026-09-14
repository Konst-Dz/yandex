import { apiFetch } from '@/shared/api'

import type { Organization, PaginatedReviews, ParsingStatusPayload } from './types'

export function getOrganization(): Promise<{ data: Organization | null }> {
    return apiFetch('/api/organization')
}

export function saveOrganizationLink(url: string): Promise<{ data: Organization }> {
    return apiFetch('/api/organization/link', {
        method: 'POST',
        body: JSON.stringify({ url }),
    })
}

export function getParsingStatus(): Promise<{ data: ParsingStatusPayload }> {
    return apiFetch('/api/organization/parsing-status')
}

export function getReviews(page: number): Promise<PaginatedReviews> {
    return apiFetch(`/api/organization/reviews?page=${page}`)
}
