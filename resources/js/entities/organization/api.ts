import { apiFetch } from '@/shared/api'

import type { Organization, PaginatedReviews, ParsingStatusPayload } from './types'

export function listOrganizations(): Promise<{ data: Organization[] }> {
    return apiFetch('/api/organizations')
}

export function getOrganization(id: number): Promise<{ data: Organization }> {
    return apiFetch(`/api/organizations/${id}`)
}

export function saveOrganizationLink(url: string): Promise<{ data: Organization }> {
    return apiFetch('/api/organizations', {
        method: 'POST',
        body: JSON.stringify({ url }),
    })
}

export function updateOrganizationLink(id: number, url: string): Promise<{ data: Organization }> {
    return apiFetch(`/api/organizations/${id}`, {
        method: 'PATCH',
        body: JSON.stringify({ url }),
    })
}

export function deleteOrganization(id: number): Promise<void> {
    return apiFetch(`/api/organizations/${id}`, { method: 'DELETE' })
}

export function getParsingStatus(id: number): Promise<{ data: ParsingStatusPayload }> {
    return apiFetch(`/api/organizations/${id}/parsing-status`)
}

export function getReviews(id: number, page: number): Promise<PaginatedReviews> {
    return apiFetch(`/api/organizations/${id}/reviews?page=${page}`)
}
