export type ProductReviewStatus =
    | 'pending'
    | 'approved'
    | 'rejected'

export interface StorefrontProductReview {
    id: number
    rating: number
    title: string | null
    body: string
    status: ProductReviewStatus
    status_label: string
    is_verified_purchase: boolean
}

export interface ProductReviewEligibility {
    eligible: boolean
    reason: string | null
}

export interface StorefrontProductReviewState {
    authenticated: boolean
    eligibility: ProductReviewEligibility | null
    review: StorefrontProductReview | null
}

export interface ProductReviewFormData {
    rating: number
    title: string
    body: string
}

export interface PublicProductReview {
    id: number
    rating: number
    title: string | null
    body: string
    is_verified_purchase: boolean
    author_name: string
    created_at: string | null
}

export interface ProductRatingDistribution {
    rating: number
    count: number
    percentage: number
}

export interface ProductReviewSummary {
    average_rating: number
    review_count: number
    distribution: ProductRatingDistribution[]
}

export interface ProductReviewPaginator {
    data: PublicProductReview[]
    current_page: number
    last_page: number
    per_page: number
    total: number
}

export interface StorefrontProductReviews {
    summary: ProductReviewSummary
    reviews: ProductReviewPaginator
}