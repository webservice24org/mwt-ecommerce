export type UnifiedRunSummary = {
    public_id: string
    date_from: string
    date_to: string
    rows_persisted: number
    truncated: boolean
    completed_at: string | null
    connection: {
        display_name: string
        property_identifier: string
        property_url: string | null
        timezone: string
    } | null
}

export type UnifiedSearchMetrics = {
    clicks: number
    impressions: number
    ctr: number
    position: number
}

export type UnifiedAnalyticsMetrics = {
    sessions: number
    engaged_sessions: number
    engagement_rate: number
    key_events: number
    total_revenue: number
}

export type UnifiedDailyRow = {
    date: string
    search_clicks: number
    search_impressions: number
    search_ctr: number
    search_position: number
    analytics_sessions: number
    analytics_engaged_sessions: number
    analytics_engagement_rate: number
    analytics_key_events: number
    analytics_total_revenue: number
}

export type UnifiedLandingPageRow = {
    path: string
    has_search_console: boolean
    has_google_analytics: boolean
    search_clicks: number
    search_impressions: number
    search_ctr: number
    search_position: number
    analytics_sessions: number
    analytics_engaged_sessions: number
    analytics_engagement_rate: number
    analytics_key_events: number
    analytics_total_revenue: number
}

export type UnifiedInsight = {
    type: string
    level: 'warning' | 'info'
    path: string
    message: string
}

export type UnifiedSeoPerformanceWorkspace = {
    run_choices: {
        search_console: UnifiedRunSummary[]
        google_analytics: UnifiedRunSummary[]
    }
    selected_runs: {
        search_console: UnifiedRunSummary | null
        google_analytics: UnifiedRunSummary | null
    }
    source_status: {
        search_console: { available: boolean; truncated: boolean }
        google_analytics: { available: boolean; truncated: boolean }
    }
    date_range: {
        from: string
        to: string
        has_overlap: boolean
        available_from: string
        available_to: string
    }
    summary: {
        search_console: UnifiedSearchMetrics
        google_analytics: UnifiedAnalyticsMetrics
    }
    daily: UnifiedDailyRow[]
    pages: UnifiedLandingPageRow[]
    pages_pagination: {
        page: number
        per_page: number
        total: number
        last_page: number
    }
    insights: UnifiedInsight[]
    filters: {
        search_run: string
        analytics_run: string
        from: string
        to: string
        page_search: string
    }
    attribution_note: string
    candidate_note: string
}
