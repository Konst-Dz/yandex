export type OrganizationStatus = 'pending' | 'parsing' | 'ready' | 'error'

export interface Organization {
    id: number
    url: string
    status: OrganizationStatus
    rating: number | null
    ratingsCount: number
    reviewsCount: number
}
