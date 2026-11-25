export type MerchantFeedExportStatus =
    | 'queued'
    | 'processing'
    | 'completed'
    | 'failed'

export type MerchantFeedExportHistoryItem = {
    public_id: string
    requested_by: {
        name: string
        email: string
    }
    status: MerchantFeedExportStatus
    format: 'tsv'
    schema_version: string
    readiness_policy_version: string
    merchant_policy_version: number
    products_considered: number
    item_candidates: number
    exportable_count: number
    excluded_count: number
    out_of_stock_count: number
    blocker_count: number
    warning_count: number
    info_count: number
    truncated: boolean
    max_items: number
    attempt_count: number
    file: {
        name: string | null
        mime_type: string | null
        size: number | null
        sha256: string | null
    }
    failure: {
        code: string | null
        message: string | null
    }
    can_download: boolean
    can_retry: boolean
    queued_at: string
    started_at: string | null
    completed_at: string | null
    failed_at: string | null
    created_at: string
}

export type MerchantFeedExportHistory = {
    items: MerchantFeedExportHistoryItem[]
    pagination: {
        current_page: number
        per_page: number
        total: number
        last_page: number
        from: number | null
        to: number | null
        previous_page: number | null
        next_page: number | null
    }
}
