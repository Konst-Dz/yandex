import { apiFetch } from '@/shared/api'

import type { Organization } from './types'

export function getOrganization(): Promise<{ data: Organization | null }> {
    return apiFetch('/api/organization')
}

export function saveOrganizationLink(url: string): Promise<{ data: Organization }> {
    return apiFetch('/api/organization/link', {
        method: 'POST',
        body: JSON.stringify({ url }),
    })
}
