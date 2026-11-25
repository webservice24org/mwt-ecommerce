export type MerchantReadinessStatus =
    | 'ready'
    | 'needs_work'
    | 'blocked'

export type MerchantFindingSeverity =
    | 'blocker'
    | 'warning'
    | 'info'

export type MerchantFinding = {
    id: string
    severity: MerchantFindingSeverity
    category: string
    message: string
    action: string
}

export type MerchantReadinessItem = {
    product_id: number
    product_name: string
    variant_id: number | null
    variant_label: string | null
    feed_id: string | null
    status: MerchantReadinessStatus
    feed_eligible: boolean
    availability: string | null
    identifier_state: string | null
    findings: MerchantFinding[]
}

export type MerchantReadinessSummary = {
    products_considered: number
    item_candidates: number
    ready: number
    needs_work: number
    blocked: number
    feed_eligible: number
    excluded: number
    out_of_stock: number
    finding_counts: Record<string, number>
    severity_counts: {
        blocker: number
        warning: number
        info: number
    }
    truncated: boolean
    max_items: number
}

export type MerchantPolicyReadiness = {
    structured_data_enabled: boolean
    shipping_markup_enabled: boolean
    shipping_published: boolean
    shipping_omission_reasons: string[]
    return_policy_enabled: boolean
    return_policy_published: boolean
    policy_version: number
}

export type MerchantReadinessFilters = {
    search: string
    status: string | null
    feed: string | null
    availability: string | null
    identifier_state: string | null
    severity: string | null
}

export type MerchantReadinessOption = {
    value: string
    label: string
}

export type MerchantReadinessOptions = {
    statuses: MerchantReadinessOption[]
    feed_states: MerchantReadinessOption[]
    availabilities: MerchantReadinessOption[]
    identifier_states: MerchantReadinessOption[]
    severities: MerchantReadinessOption[]
}

export type MerchantReadinessPagination = {
    current_page: number
    per_page: number
    total: number
    last_page: number
    from: number | null
    to: number | null
    previous_page: number | null
    next_page: number | null
}

export type MerchantReadinessWorkspace = {
    policy_version: string
    summary: MerchantReadinessSummary
    policy: MerchantPolicyReadiness
    filters: MerchantReadinessFilters
    options: MerchantReadinessOptions
    items: MerchantReadinessItem[]
    pagination: MerchantReadinessPagination
}
