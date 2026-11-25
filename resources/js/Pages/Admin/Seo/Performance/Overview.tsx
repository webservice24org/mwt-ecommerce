import { Head, router } from '@inertiajs/react'
import { AlertTriangle, BarChart3, Search, Sparkles, TrendingUp } from 'lucide-react'
import type { FormEvent, ReactNode } from 'react'
import { useMemo, useState } from 'react'

import AdminLayout from '@/Layouts/Admin/AdminLayout'
import type {
    UnifiedInsight,
    UnifiedLandingPageRow,
    UnifiedRunSummary,
    UnifiedSeoPerformanceWorkspace,
} from '@/types/seo-unified-performance-dashboard'

type Props = {
    workspace: UnifiedSeoPerformanceWorkspace
}

export default function UnifiedSeoPerformanceDashboard({ workspace }: Props) {
    const [filters, setFilters] = useState(workspace.filters)
    const searchRun = workspace.selected_runs.search_console
    const analyticsRun = workspace.selected_runs.google_analytics

    const maxDaily = useMemo(
        () =>
            Math.max(
                1,
                ...workspace.daily.map((row) =>
                    Math.max(row.search_clicks, row.analytics_sessions),
                ),
            ),
        [workspace.daily],
    )

    const navigate = (next: Record<string, string | number>) => {
        router.get(
            '/admin/seo/performance',
            { ...filters, ...next },
            {
                preserveScroll: true,
                preserveState: true,
                replace: true,
            },
        )
    }

    const submitFilters = (event: FormEvent) => {
        event.preventDefault()
        navigate({
            from: filters.from,
            to: filters.to,
            page_search: filters.page_search,
            pages_page: 1,
        })
    }

    const selectRun = (source: 'search_run' | 'analytics_run', run: UnifiedRunSummary) => {
        router.get(
            '/admin/seo/performance',
            {
                ...filters,
                [source]: run.public_id,
                pages_page: 1,
            },
            { preserveScroll: true, preserveState: false },
        )
    }

    return (
        <AdminLayout
            title="SEO Performance"
            description="Unified Search Console and GA4 reporting from completed, bounded synchronization snapshots."
        >
            <Head title="SEO Performance" />

            <div className="space-y-6">
                <section className="grid gap-4 xl:grid-cols-2">
                    <SourceCard
                        title="Search Console snapshot"
                        run={searchRun}
                        choices={workspace.run_choices.search_console}
                        onSelect={(run) => selectRun('search_run', run)}
                        detailHref="/admin/seo/performance/search-console"
                    />
                    <SourceCard
                        title="Google Analytics snapshot"
                        run={analyticsRun}
                        choices={workspace.run_choices.google_analytics}
                        onSelect={(run) => selectRun('analytics_run', run)}
                        detailHref="/admin/seo/performance/google-analytics"
                    />
                </section>

                {!workspace.date_range.has_overlap ? (
                    <Notice>
                        Select completed Search Console and GA4 snapshots with overlapping dates before comparing the two sources.
                    </Notice>
                ) : (
                    <>
                        <section className="rounded-2xl border border-neutral-200 bg-white p-5 shadow-sm dark:border-neutral-800 dark:bg-neutral-950">
                            <form onSubmit={submitFilters} className="grid gap-3 lg:grid-cols-[1fr_1fr_minmax(220px,1.4fr)_auto]">
                                <DateField
                                    label="From"
                                    value={filters.from}
                                    onChange={(value) => setFilters((current) => ({ ...current, from: value }))}
                                />
                                <DateField
                                    label="To"
                                    value={filters.to}
                                    onChange={(value) => setFilters((current) => ({ ...current, to: value }))}
                                />
                                <label className="block">
                                    <span className="mb-1 block text-xs font-semibold text-neutral-600 dark:text-neutral-300">
                                        Landing page
                                    </span>
                                    <div className="relative">
                                        <Search className="pointer-events-none absolute left-3 top-1/2 h-4 w-4 -translate-y-1/2 text-neutral-400" />
                                        <input
                                            value={filters.page_search}
                                            onChange={(event) =>
                                                setFilters((current) => ({
                                                    ...current,
                                                    page_search: event.target.value,
                                                }))
                                            }
                                            className="min-h-10 w-full rounded-lg border border-neutral-300 bg-white pl-9 pr-3 text-sm dark:border-neutral-700 dark:bg-neutral-900"
                                            placeholder="Filter normalized path"
                                        />
                                    </div>
                                </label>
                                <button
                                    type="submit"
                                    className="mt-auto min-h-10 rounded-lg bg-neutral-950 px-5 text-sm font-semibold text-white dark:bg-white dark:text-neutral-950"
                                >
                                    Apply
                                </button>
                            </form>
                            <p className="mt-3 text-xs text-neutral-500 dark:text-neutral-400">
                                Available overlap: {workspace.date_range.available_from} → {workspace.date_range.available_to}
                            </p>
                        </section>

                        {(workspace.source_status.search_console.truncated ||
                            workspace.source_status.google_analytics.truncated) && (
                            <Notice>
                                At least one selected synchronization reached its configured row bound. Comparisons below reflect only the persisted snapshots.
                            </Notice>
                        )}

                        <section className="grid gap-4 sm:grid-cols-2 xl:grid-cols-5">
                            <MetricCard label="Search clicks" value={formatNumber(workspace.summary.search_console.clicks)} />
                            <MetricCard label="Search impressions" value={formatNumber(workspace.summary.search_console.impressions)} />
                            <MetricCard label="Search CTR" value={formatPercent(workspace.summary.search_console.ctr)} />
                            <MetricCard label="GA4 sessions" value={formatNumber(workspace.summary.google_analytics.sessions)} />
                            <MetricCard label="GA4 engagement" value={formatPercent(workspace.summary.google_analytics.engagement_rate)} />
                            <MetricCard label="Avg. position" value={formatDecimal(workspace.summary.search_console.position)} />
                            <MetricCard label="Engaged sessions" value={formatNumber(workspace.summary.google_analytics.engaged_sessions)} />
                            <MetricCard label="Key events" value={formatNumber(workspace.summary.google_analytics.key_events)} />
                            <MetricCard label="Revenue" value={formatDecimal(workspace.summary.google_analytics.total_revenue)} />
                            <MetricCard label="Insights" value={formatNumber(workspace.insights.length)} />
                        </section>

                        <section className="rounded-2xl border border-neutral-200 bg-white p-5 shadow-sm dark:border-neutral-800 dark:bg-neutral-950">
                            <div className="flex items-center gap-2">
                                <TrendingUp className="h-5 w-5 text-neutral-500" />
                                <h2 className="font-semibold text-neutral-950 dark:text-neutral-100">Daily comparison</h2>
                            </div>
                            <p className="mt-1 text-sm text-neutral-500 dark:text-neutral-400">
                                Search clicks and GA4 sessions are shown side-by-side, not as an attribution funnel.
                            </p>
                            <div className="mt-5 space-y-4">
                                {workspace.daily.length === 0 && <EmptyState text="No rows match the selected overlap." />}
                                {workspace.daily.map((row) => (
                                    <div key={row.date} className="grid gap-2 md:grid-cols-[95px_1fr_1fr] md:items-center">
                                        <span className="text-sm text-neutral-600 dark:text-neutral-300">{row.date}</span>
                                        <MiniBar
                                            label={`Search clicks ${formatNumber(row.search_clicks)}`}
                                            value={row.search_clicks}
                                            max={maxDaily}
                                        />
                                        <MiniBar
                                            label={`GA4 sessions ${formatNumber(row.analytics_sessions)}`}
                                            value={row.analytics_sessions}
                                            max={maxDaily}
                                        />
                                    </div>
                                ))}
                            </div>
                        </section>

                        <section className="grid gap-6 xl:grid-cols-[minmax(0,1.5fr)_minmax(300px,0.7fr)]">
                            <div className="min-w-0 rounded-2xl border border-neutral-200 bg-white shadow-sm dark:border-neutral-800 dark:bg-neutral-950">
                                <div className="border-b border-neutral-200 p-5 dark:border-neutral-800">
                                    <h2 className="font-semibold text-neutral-950 dark:text-neutral-100">Cross-source landing pages</h2>
                                    <p className="mt-1 text-sm text-neutral-500 dark:text-neutral-400">
                                        Search Console URLs are normalized to path + query before matching GA4 landing pages.
                                    </p>
                                </div>
                                <LandingPageTable rows={workspace.pages} />
                                <div className="border-t border-neutral-200 p-4 dark:border-neutral-800">
                                    <Pager
                                        page={workspace.pages_pagination.page}
                                        lastPage={workspace.pages_pagination.last_page}
                                        onPrevious={() => navigate({ pages_page: workspace.pages_pagination.page - 1 })}
                                        onNext={() => navigate({ pages_page: workspace.pages_pagination.page + 1 })}
                                    />
                                </div>
                            </div>

                            <div className="min-w-0 rounded-2xl border border-neutral-200 bg-white p-5 shadow-sm dark:border-neutral-800 dark:bg-neutral-950">
                                <div className="flex items-center gap-2">
                                    <Sparkles className="h-5 w-5 text-neutral-500" />
                                    <h2 className="font-semibold text-neutral-950 dark:text-neutral-100">Cross-source insights</h2>
                                </div>
                                <p className="mt-1 text-sm text-neutral-500 dark:text-neutral-400">
                                    Deterministic review signals only; no AI and no causal attribution.
                                </p>
                                <div className="mt-4 space-y-3">
                                    {workspace.insights.length === 0 && (
                                        <EmptyState text="No bounded cross-source review signals were triggered." />
                                    )}
                                    {workspace.insights.map((insight, index) => (
                                        <InsightCard key={`${insight.type}-${insight.path}-${index}`} insight={insight} />
                                    ))}
                                </div>
                            </div>
                        </section>

                        <Notice>{workspace.attribution_note}</Notice>
                        <p className="rounded-xl border border-neutral-200 bg-neutral-50 px-4 py-3 text-xs leading-5 text-neutral-600 dark:border-neutral-800 dark:bg-neutral-900 dark:text-neutral-300">
                            {workspace.candidate_note}
                        </p>
                    </>
                )}
            </div>
        </AdminLayout>
    )
}

function SourceCard({
    title,
    run,
    choices,
    onSelect,
    detailHref,
}: {
    title: string
    run: UnifiedRunSummary | null
    choices: UnifiedRunSummary[]
    onSelect: (run: UnifiedRunSummary) => void
    detailHref: string
}) {
    return (
        <section className="rounded-2xl border border-neutral-200 bg-white p-5 shadow-sm dark:border-neutral-800 dark:bg-neutral-950">
            <div className="flex items-start justify-between gap-4">
                <div>
                    <p className="text-xs font-semibold uppercase tracking-wide text-neutral-500">{title}</p>
                    <h2 className="mt-1 font-semibold text-neutral-950 dark:text-neutral-100">
                        {run?.connection?.display_name ?? 'No completed snapshot'}
                    </h2>
                    {run && (
                        <p className="mt-1 text-sm text-neutral-500 dark:text-neutral-400">
                            {run.date_from} → {run.date_to} · {formatNumber(run.rows_persisted)} rows
                        </p>
                    )}
                </div>
                <a href={detailHref} className="text-xs font-semibold text-indigo-600 hover:underline dark:text-indigo-300">
                    Detailed report
                </a>
            </div>
            {choices.length > 0 && (
                <select
                    value={run?.public_id ?? ''}
                    onChange={(event) => {
                        const selected = choices.find((choice) => choice.public_id === event.target.value)
                        if (selected) onSelect(selected)
                    }}
                    className="mt-4 min-h-10 w-full rounded-lg border border-neutral-300 bg-white px-3 text-sm dark:border-neutral-700 dark:bg-neutral-900"
                >
                    {choices.map((choice) => (
                        <option key={choice.public_id} value={choice.public_id}>
                            {choice.date_from} → {choice.date_to} · {choice.connection?.display_name ?? 'Connection'}
                        </option>
                    ))}
                </select>
            )}
        </section>
    )
}

function LandingPageTable({ rows }: { rows: UnifiedLandingPageRow[] }) {
    if (rows.length === 0) {
        return <div className="p-5"><EmptyState text="No landing pages match the current filters." /></div>
    }

    return (
        <div className="overflow-x-auto">
            <table className="min-w-[1050px] w-full text-left text-sm">
                <thead className="bg-neutral-50 text-xs uppercase tracking-wide text-neutral-500 dark:bg-neutral-900 dark:text-neutral-400">
                    <tr>
                        <th className="px-5 py-3">Landing page</th>
                        <th className="px-4 py-3 text-right">Clicks</th>
                        <th className="px-4 py-3 text-right">Impressions</th>
                        <th className="px-4 py-3 text-right">CTR</th>
                        <th className="px-4 py-3 text-right">Position</th>
                        <th className="px-4 py-3 text-right">Sessions</th>
                        <th className="px-4 py-3 text-right">Engagement</th>
                        <th className="px-4 py-3 text-right">Key events</th>
                        <th className="px-4 py-3 text-right">Revenue</th>
                    </tr>
                </thead>
                <tbody className="divide-y divide-neutral-100 dark:divide-neutral-900">
                    {rows.map((row) => (
                        <tr key={row.path}>
                            <td className="max-w-[320px] px-5 py-3">
                                <p className="truncate font-medium text-neutral-900 dark:text-neutral-100">{row.path}</p>
                                <p className="mt-1 text-[11px] text-neutral-500">
                                    {row.has_search_console ? 'GSC' : '—'} · {row.has_google_analytics ? 'GA4' : '—'}
                                </p>
                            </td>
                            <td className="px-4 py-3 text-right">{formatNumber(row.search_clicks)}</td>
                            <td className="px-4 py-3 text-right">{formatNumber(row.search_impressions)}</td>
                            <td className="px-4 py-3 text-right">{formatPercent(row.search_ctr)}</td>
                            <td className="px-4 py-3 text-right">{formatDecimal(row.search_position)}</td>
                            <td className="px-4 py-3 text-right">{formatNumber(row.analytics_sessions)}</td>
                            <td className="px-4 py-3 text-right">{formatPercent(row.analytics_engagement_rate)}</td>
                            <td className="px-4 py-3 text-right">{formatNumber(row.analytics_key_events)}</td>
                            <td className="px-4 py-3 text-right">{formatDecimal(row.analytics_total_revenue)}</td>
                        </tr>
                    ))}
                </tbody>
            </table>
        </div>
    )
}

function InsightCard({ insight }: { insight: UnifiedInsight }) {
    return (
        <div className={[
            'rounded-xl border p-3 text-sm',
            insight.level === 'warning'
                ? 'border-amber-200 bg-amber-50 dark:border-amber-900/60 dark:bg-amber-950/30'
                : 'border-neutral-200 bg-neutral-50 dark:border-neutral-800 dark:bg-neutral-900',
        ].join(' ')}>
            <div className="flex items-start gap-2">
                {insight.level === 'warning' ? (
                    <AlertTriangle className="mt-0.5 h-4 w-4 shrink-0 text-amber-600" />
                ) : (
                    <BarChart3 className="mt-0.5 h-4 w-4 shrink-0 text-neutral-500" />
                )}
                <div className="min-w-0">
                    <p className="truncate font-semibold text-neutral-900 dark:text-neutral-100">{insight.path}</p>
                    <p className="mt-1 leading-5 text-neutral-600 dark:text-neutral-300">{insight.message}</p>
                </div>
            </div>
        </div>
    )
}

function MetricCard({ label, value }: { label: string; value: string }) {
    return (
        <div className="rounded-2xl border border-neutral-200 bg-white p-4 shadow-sm dark:border-neutral-800 dark:bg-neutral-950">
            <p className="text-xs font-semibold uppercase tracking-wide text-neutral-500">{label}</p>
            <p className="mt-2 text-2xl font-semibold text-neutral-950 dark:text-neutral-100">{value}</p>
        </div>
    )
}

function MiniBar({ label, value, max }: { label: string; value: number; max: number }) {
    return (
        <div>
            <div className="mb-1 flex items-center justify-between text-xs text-neutral-500">
                <span>{label}</span>
            </div>
            <div className="h-2 overflow-hidden rounded-full bg-neutral-100 dark:bg-neutral-800">
                <div
                    className="h-full rounded-full bg-indigo-500"
                    style={{ width: `${Math.max(value > 0 ? 2 : 0, (value / max) * 100)}%` }}
                />
            </div>
        </div>
    )
}

function DateField({
    label,
    value,
    onChange,
}: {
    label: string
    value: string
    onChange: (value: string) => void
}) {
    return (
        <label className="block">
            <span className="mb-1 block text-xs font-semibold text-neutral-600 dark:text-neutral-300">{label}</span>
            <input
                type="date"
                value={value}
                onChange={(event) => onChange(event.target.value)}
                className="min-h-10 w-full rounded-lg border border-neutral-300 bg-white px-3 text-sm dark:border-neutral-700 dark:bg-neutral-900"
            />
        </label>
    )
}

function Pager({
    page,
    lastPage,
    onPrevious,
    onNext,
}: {
    page: number
    lastPage: number
    onPrevious: () => void
    onNext: () => void
}) {
    if (lastPage <= 1) return null

    return (
        <div className="flex items-center justify-between gap-3">
            <button
                type="button"
                disabled={page <= 1}
                onClick={onPrevious}
                className="rounded-lg border border-neutral-300 px-3 py-2 text-sm disabled:opacity-40 dark:border-neutral-700"
            >
                Previous
            </button>
            <span className="text-xs text-neutral-500">Page {page} of {lastPage}</span>
            <button
                type="button"
                disabled={page >= lastPage}
                onClick={onNext}
                className="rounded-lg border border-neutral-300 px-3 py-2 text-sm disabled:opacity-40 dark:border-neutral-700"
            >
                Next
            </button>
        </div>
    )
}

function EmptyState({ text }: { text: string }) {
    return <p className="rounded-xl bg-neutral-50 p-4 text-sm text-neutral-500 dark:bg-neutral-900 dark:text-neutral-400">{text}</p>
}

function Notice({ children }: { children: ReactNode }) {
    return (
        <div className="rounded-xl border border-neutral-200 bg-neutral-50 px-4 py-3 text-sm leading-6 text-neutral-600 dark:border-neutral-800 dark:bg-neutral-900 dark:text-neutral-300">
            {children}
        </div>
    )
}

function formatNumber(value: number): string {
    return new Intl.NumberFormat(undefined, { maximumFractionDigits: 0 }).format(value)
}

function formatDecimal(value: number): string {
    return new Intl.NumberFormat(undefined, { maximumFractionDigits: 2 }).format(value)
}

function formatPercent(value: number): string {
    return new Intl.NumberFormat(undefined, {
        style: 'percent',
        maximumFractionDigits: 2,
    }).format(value)
}
