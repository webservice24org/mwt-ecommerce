export type SeoInternalLinkReviewState =
    | 'pending'
    | 'accepted'
    | 'dismissed'

export type SeoInternalLinkRunOption = {
    public_id: string
    requested_at: string
    completed_at: string | null
    opportunities: number
}

export type SeoInternalLinkSelectedRun = {
    public_id: string
    requested_at: string
    completed_at: string | null
    findings_total: number
}

export type SeoInternalLinkOpportunityRow = {
    fingerprint: string
    review_state: SeoInternalLinkReviewState
    score: number
    target_subject_type: string
    target_subject_key: string | null
    source_url: string
    target_url: string
    suggested_anchor: string | null
    reason: string
    reviewed_by_name: string | null
    reviewed_by_email: string | null
    reviewed_at: string | null
    review_note: string | null
    created_at: string | null
}

export type SeoInternalLinkWorkspace = {
    runs: SeoInternalLinkRunOption[]
    selected_run: SeoInternalLinkSelectedRun | null
    summary: {
        total: number
        pending: number
        accepted: number
        dismissed: number
    }
    opportunities: SeoInternalLinkOpportunityRow[]
    pagination: {
        page: number
        per_page: number
        total: number
        last_page: number
    }
    filters: {
        run: string
        state: string
        search: string
    }
    options: {
        states: Array<{
            value: SeoInternalLinkReviewState
            label: string
        }>
    }
    can_review: boolean
}
