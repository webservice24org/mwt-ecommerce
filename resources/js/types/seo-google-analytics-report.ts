export type GoogleAnalyticsPagination = {
    page: number
    per_page: number
    total: number
    last_page: number
}

export type GoogleAnalyticsConnectionSummary = {
    public_id: string
    display_name: string
    property_identifier: string
    property_url: string | null
    timezone: string
}

export type GoogleAnalyticsSyncRunSummary = {
    public_id: string
    date_from: string
    date_to: string
    rows_received: number
    rows_persisted: number
    truncated: boolean
    queue_passes: number
    completed_at: string | null
    connection: GoogleAnalyticsConnectionSummary | null
}

export type GoogleAnalyticsMetricSet = {
    sessions: number
    engaged_sessions: number
    engagement_rate: number
    key_events: number
    total_revenue: number
}

export type GoogleAnalyticsDailyRow = GoogleAnalyticsMetricSet & {
    date: string
}

export type GoogleAnalyticsLandingPageRow = GoogleAnalyticsMetricSet & {
    landing_page: string
    active_users_row_sum: number
    new_users_row_sum: number
}

export type GoogleAnalyticsPerformanceWorkspace = {
    runs: GoogleAnalyticsSyncRunSummary[]
    runs_pagination: GoogleAnalyticsPagination
    selected_run: GoogleAnalyticsSyncRunSummary | null
    summary: GoogleAnalyticsMetricSet
    daily: GoogleAnalyticsDailyRow[]
    pages: GoogleAnalyticsLandingPageRow[]
    pages_pagination: GoogleAnalyticsPagination
    filters: {
        run: string
        from: string
        to: string
        page_search: string
    }
    dataset_note: string
}
