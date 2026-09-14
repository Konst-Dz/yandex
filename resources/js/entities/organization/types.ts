export type OrganizationStatus = 'pending' | 'parsing' | 'ready' | 'error'

export type ParsingStatus = 'idle' | OrganizationStatus

export interface Organization {
    id: number
    url: string
    status: OrganizationStatus
    rating: number | null
    ratingsCount: number
    reviewsCount: number
}

export interface ParsingStatusPayload {
    status: ParsingStatus
    reason: string | null
}

export interface Review {
    id: number
    externalId: string
    author: string
    text: string
    rating: number | null
    reviewedAt: string | null
}

export interface PaginatedReviews {
    data: Review[]
    meta: {
        current_page: number
        last_page: number
        per_page: number
        total: number
    }
}
