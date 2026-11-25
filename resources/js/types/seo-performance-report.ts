export type SeoPerformancePagination = {
    page: number
    per_page: number
    total: number
    last_page: number
}

export type SearchConsoleConnectionSummary = {
    public_id: string
    display_name: string
    property_identifier: string
    property_url: string | null
    timezone: string
}

export type SearchConsoleSyncRunSummary = {
    public_id: string
    date_from: string
    date_to: string
    rows_received: number
    rows_persisted: number
    truncated: boolean
    queue_passes: number
    completed_at: string | null
    connection: SearchConsoleConnectionSummary | null
}

export type SearchConsoleMetricSet = {
    clicks: number
    impressions: number
    ctr: number
    position: number
}

export type SearchConsoleDailyRow = SearchConsoleMetricSet & {
    date: string
}

export type SearchConsoleDimensionRow = SearchConsoleMetricSet & {
    value: string
}

export type SearchConsolePerformanceWorkspace = {
    runs: SearchConsoleSyncRunSummary[]
    runs_pagination: SeoPerformancePagination
    selected_run: SearchConsoleSyncRunSummary | null
    summary: SearchConsoleMetricSet
    daily: SearchConsoleDailyRow[]
    queries: SearchConsoleDimensionRow[]
    queries_pagination: SeoPerformancePagination
    pages: SearchConsoleDimensionRow[]
    pages_pagination: SeoPerformancePagination
    filters: {
        run: string
        from: string
        to: string
        query_search: string
        page_search: string
    }
    dataset_note: string
}
