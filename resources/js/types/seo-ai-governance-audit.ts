export type SeoAiGovernancePagination = {
    page: number
    per_page: number
    total: number
    last_page: number
}

export type SeoAiGovernanceRunSummary = {
    public_id: string
    purpose: string
    subject_type: string | null
    subject_key: string | null
    provider: string
    model_name: string
    prompt_version: string
    status: string
    approval_status: string
    attempt_count: number
    total_tokens: number | null
    cost_amount_minor: number | null
    cost_currency: string | null
    failure_code: string | null
    requested_by_name: string | null
    reviewed_by_name: string | null
    published: boolean
    created_at: string | null
}

export type SeoAiGovernanceRunDetail = SeoAiGovernanceRunSummary & {
    input_snapshot_hash: string
    input_field_names: string[]
    generated_output: Record<string, unknown> | unknown[] | null
    generated_at: string | null
    requested_by_email: string | null
    reviewed_by_email: string | null
    reviewed_at: string | null
    published_at: string | null
    input_tokens: number | null
    output_tokens: number | null
    failure_message: string | null
    updated_at: string | null
}

export type SeoAiGovernanceAttemptRow = {
    attempt_number: number
    outcome: string
    input_tokens: number | null
    output_tokens: number | null
    total_tokens: number | null
    cost_amount_minor: number | null
    cost_currency: string | null
    failure_code: string | null
    recorded_at: string | null
}

export type SeoAiGovernanceEventRow = {
    event_type: string
    actor_name: string | null
    actor_email: string | null
    metadata: Record<string, unknown> | unknown[] | null
    occurred_at: string | null
}

export type SeoAiGovernanceSummary = {
    total: number
    queued: number
    running: number
    succeeded: number
    failed: number
    pending_review: number
    approved: number
    rejected: number
    published: number
}

export type SeoAiGovernanceAuditWorkspace = {
    summary: SeoAiGovernanceSummary
    runs: SeoAiGovernanceRunSummary[]
    pagination: SeoAiGovernancePagination
    selected_run: SeoAiGovernanceRunDetail | null
    attempts: SeoAiGovernanceAttemptRow[]
    events: SeoAiGovernanceEventRow[]
    filters: {
        run: string
        status: string
        approval: string
        purpose: string
        provider: string
        search: string
        from: string
        to: string
    }
    options: {
        statuses: string[]
        approval_statuses: string[]
        purposes: string[]
        providers: string[]
    }
    limits: {
        attempts: number
        events: number
    }
    workspace_note: string
}
