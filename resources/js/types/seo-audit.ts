export type SeoAuditRunStatus =
    | 'requested'
    | 'running'
    | 'completed'
    | 'failed'

export type SeoAuditSeverity =
    | 'critical'
    | 'error'
    | 'warning'
    | 'info'

export type SeoAuditOption = {
    value: string
    label: string
}

export type SeoAuditRunRow = {
    public_id: string
    status: SeoAuditRunStatus
    rules_version: string
    orchestration_version: string | null
    queue_passes: number
    urls_scanned: number
    findings_total: number
    critical_count: number
    error_count: number
    warning_count: number
    info_count: number
    failure_code: string | null
    failure_message: string | null
    requested_by_name: string
    requested_at: string
    started_at: string | null
    completed_at: string | null
    failed_at: string | null
    catalog_seeded_at: string | null
    crawl_settled_at: string | null
    image_health_enriched_at: string | null
    evaluated_at: string | null
    last_progress_at: string | null
}

export type SeoAuditFindingRow = {
    fingerprint: string
    rule: string
    rule_label: string
    severity: SeoAuditSeverity
    subject_type: string
    subject_key: string | null
    url: string | null
    source_url: string | null
    message: string
    evidence: Record<string, unknown>
    remediation: string | null
}

export type SeoAuditPagination = {
    page: number
    per_page: number
    total: number
    last_page: number
}

export type SeoAuditWorkspace = {
    runs: SeoAuditRunRow[]
    runs_pagination: SeoAuditPagination
    selected_run: SeoAuditRunRow | null
    findings: SeoAuditFindingRow[]
    findings_pagination: SeoAuditPagination
    filters: {
        run: string
        severity: string
        rule: string
        search: string
    }
    options: {
        severities: SeoAuditOption[]
        rules: SeoAuditOption[]
    }
    active_run: {
        public_id: string
        status: SeoAuditRunStatus
    } | null
    can_start: boolean
}
