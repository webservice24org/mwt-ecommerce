import { Head, router } from '@inertiajs/react'
import { Activity, BarChart3, CalendarDays, Search, UsersRound } from 'lucide-react'
import type { FormEvent, ReactNode } from 'react'
import { useMemo, useState } from 'react'

import AdminLayout from '@/Layouts/Admin/AdminLayout'
import type {
    GoogleAnalyticsLandingPageRow,
    GoogleAnalyticsMetricSet,
    GoogleAnalyticsPerformanceWorkspace,
    GoogleAnalyticsSyncRunSummary,
} from '@/types/seo-google-analytics-report'

type Props = {
    workspace: GoogleAnalyticsPerformanceWorkspace
}

export default function GoogleAnalyticsPerformanceReport({ workspace }: Props) {
    const [filters, setFilters] = useState(workspace.filters)
    const selected = workspace.selected_run

    const maxDailySessions = useMemo(
        () => Math.max(1, ...workspace.daily.map((row) => row.sessions)),
        [workspace.daily],
    )

    const navigate = (next: Record<string, string | number>) => {
        router.get(
            '/admin/seo/performance/google-analytics',
            { ...filters, ...next },
            {
                preserveScroll: true,
                preserveState: true,
                replace: true,
            },
        )
    }

    const submitDates = (event: FormEvent) => {
        event.preventDefault()
        navigate({
            from: filters.from,
            to: filters.to,
            pages_page: 1,
        })
    }

    const selectRun = (run: GoogleAnalyticsSyncRunSummary) => {
        router.get(
            '/admin/seo/performance/google-analytics',
            { run: run.public_id },
            { preserveScroll: true, preserveState: false },
        )
    }

    return (
        <AdminLayout
            title="Google Analytics Performance"
            description="Read-only reporting from completed GA4 landing-page synchronization snapshots."
        >
            <Head title="Google Analytics Performance" />

            <div className="grid gap-6 xl:grid-cols-[310px_minmax(0,1fr)]">
                <aside className="min-w-0 rounded-2xl border border-neutral-200 bg-white p-4 shadow-sm dark:border-neutral-800 dark:bg-neutral-950">
                    <div className="flex items-center justify-between gap-3">
                        <div>
                            <h2 className="font-semibold text-neutral-950 dark:text-neutral-100">Completed snapshots</h2>
                            <p className="mt-1 text-xs text-neutral-500 dark:text-neutral-400">Each report uses one GA4 sync run only.</p>
                        </div>
                        <BarChart3 className="h-5 w-5 text-neutral-400" aria-hidden="true" />
                    </div>

                    <div className="mt-4 space-y-2">
                        {workspace.runs.length === 0 && (
                            <EmptyState text="No completed Google Analytics sync is available yet." />
                        )}

                        {workspace.runs.map((run) => (
                            <button
                                key={run.public_id}
                                type="button"
                                onClick={() => selectRun(run)}
                                className={[
                                    'w-full rounded-xl border p-3 text-left transition',
                                    selected?.public_id === run.public_id
                                        ? 'border-indigo-400 bg-indigo-50 dark:border-indigo-700 dark:bg-indigo-950/30'
                                        : 'border-neutral-200 hover:bg-neutral-50 dark:border-neutral-800 dark:hover:bg-neutral-900',
                                ].join(' ')}
                            >
                                <p className="truncate text-sm font-semibold text-neutral-950 dark:text-neutral-100">
                                    {run.connection?.display_name ?? 'Google Analytics'}
                                </p>
                                <p className="mt-1 truncate text-xs text-neutral-500 dark:text-neutral-400">
                                    Property {run.connection?.property_identifier ?? 'unavailable'}
                                </p>
                                <p className="mt-2 text-xs text-neutral-500 dark:text-neutral-400">
                                    {run.date_from} → {run.date_to}
                                </p>
                                <div className="mt-2 flex items-center justify-between gap-2 text-[11px] text-neutral-500 dark:text-neutral-400">
                                    <span>{formatNumber(run.rows_persisted)} rows</span>
                                    {run.truncated && <span className="font-semibold text-amber-700 dark:text-amber-300">Truncated</span>}
                                </div>
                            </button>
                        ))}
                    </div>

                    <Pager
                        page={workspace.runs_pagination.page}
                        lastPage={workspace.runs_pagination.last_page}
                        onPrevious={() => navigate({ runs_page: workspace.runs_pagination.page - 1 })}
                        onNext={() => navigate({ runs_page: workspace.runs_pagination.page + 1 })}
                    />
                </aside>

                <main className="min-w-0 space-y-6">
                    {selected === null ? (
                        <EmptyState text="Run a Google Analytics synchronization before opening this report." />
                    ) : (
                        <>
                            <section className="rounded-2xl border border-neutral-200 bg-white p-5 shadow-sm dark:border-neutral-800 dark:bg-neutral-950">
                                <div className="flex flex-col gap-4 lg:flex-row lg:items-start lg:justify-between">
                                    <div>
                                        <p className="text-xs font-semibold uppercase tracking-wide text-neutral-500">Selected GA4 snapshot</p>
                                        <h2 className="mt-1 text-xl font-semibold text-neutral-950 dark:text-neutral-100">
                                            {selected.connection?.display_name ?? 'Google Analytics'}
                                        </h2>
                                        <p className="mt-1 text-sm text-neutral-500 dark:text-neutral-400">
                                            Property {selected.connection?.property_identifier ?? 'unavailable'} · {selected.date_from} → {selected.date_to}
                                        </p>
                                    </div>
                                    <div className="text-sm text-neutral-500 dark:text-neutral-400">
                                        <p>{formatNumber(selected.rows_persisted)} persisted rows</p>
                                        <p>{formatNumber(selected.queue_passes)} queue passes</p>
                                    </div>
                                </div>

                                {selected.truncated && (
                                    <div className="mt-4 rounded-xl border border-amber-200 bg-amber-50 px-4 py-3 text-sm text-amber-800 dark:border-amber-900/60 dark:bg-amber-950/30 dark:text-amber-200">
                                        This synchronization hit the configured row bound. Reporting below is limited to the persisted snapshot.
                                    </div>
                                )}

                                <form onSubmit={submitDates} className="mt-5 grid gap-3 md:grid-cols-[1fr_1fr_auto]">
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
                                    <button
                                        type="submit"
                                        className="mt-auto inline-flex min-h-10 items-center justify-center rounded-lg bg-neutral-950 px-4 text-sm font-semibold text-white hover:bg-neutral-800 dark:bg-white dark:text-neutral-950 dark:hover:bg-neutral-200"
                                    >
                                        Apply dates
                                    </button>
                                </form>
                            </section>

                            <section className="grid gap-4 sm:grid-cols-2 xl:grid-cols-5">
                                <MetricCard label="Sessions" value={formatNumber(workspace.summary.sessions)} icon={<Activity className="h-4 w-4" />} />
                                <MetricCard label="Engaged sessions" value={formatNumber(workspace.summary.engaged_sessions)} icon={<UsersRound className="h-4 w-4" />} />
                                <MetricCard label="Engagement rate" value={formatPercent(workspace.summary.engagement_rate)} icon={<BarChart3 className="h-4 w-4" />} />
                                <MetricCard label="Key events" value={formatNumber(workspace.summary.key_events)} icon={<CalendarDays className="h-4 w-4" />} />
                                <MetricCard label="Revenue" value={formatDecimal(workspace.summary.total_revenue)} icon={<BarChart3 className="h-4 w-4" />} />
                            </section>

                            <section className="rounded-2xl border border-neutral-200 bg-white p-5 shadow-sm dark:border-neutral-800 dark:bg-neutral-950">
                                <h2 className="font-semibold text-neutral-950 dark:text-neutral-100">Daily sessions</h2>
                                <p className="mt-1 text-sm text-neutral-500 dark:text-neutral-400">Additive landing-page session metrics from the selected snapshot.</p>
                                <div className="mt-5 space-y-3">
                                    {workspace.daily.length === 0 && <EmptyState text="No GA4 rows match the selected dates." />}
                                    {workspace.daily.map((row) => (
                                        <div key={row.date} className="grid grid-cols-[95px_minmax(0,1fr)_90px] items-center gap-3 text-sm">
                                            <span className="text-neutral-600 dark:text-neutral-300">{row.date}</span>
                                            <div className="h-2 overflow-hidden rounded-full bg-neutral-100 dark:bg-neutral-800">
                                                <div
                                                    className="h-full rounded-full bg-indigo-500"
                                                    style={{ width: `${Math.max(2, (row.sessions / maxDailySessions) * 100)}%` }}
                                                />
                                            </div>
                                            <span className="text-right font-medium text-neutral-900 dark:text-neutral-100">{formatNumber(row.sessions)}</span>
                                        </div>
                                    ))}
                                </div>
                            </section>

                            <section className="rounded-2xl border border-neutral-200 bg-white shadow-sm dark:border-neutral-800 dark:bg-neutral-950">
                                <div className="flex flex-col gap-3 border-b border-neutral-200 p-5 dark:border-neutral-800 md:flex-row md:items-end md:justify-between">
                                    <div>
                                        <h2 className="font-semibold text-neutral-950 dark:text-neutral-100">Landing pages</h2>
                                        <p className="mt-1 text-sm text-neutral-500 dark:text-neutral-400">Ranked by sessions within the selected snapshot and date range.</p>
                                    </div>
                                    <div className="flex w-full gap-2 md:w-auto">
                                        <div className="relative min-w-0 flex-1 md:w-80">
                                            <Search className="pointer-events-none absolute left-3 top-1/2 h-4 w-4 -translate-y-1/2 text-neutral-400" />
                                            <input
                                                value={filters.page_search}
                                                onChange={(event) => setFilters((current) => ({ ...current, page_search: event.target.value }))}
                                                onKeyDown={(event) => {
                                                    if (event.key === 'Enter') {
                                                        navigate({ page_search: filters.page_search, pages_page: 1 })
                                                    }
                                                }}
                                                className="min-h-10 w-full rounded-lg border border-neutral-300 bg-white pl-9 pr-3 text-sm text-neutral-900 dark:border-neutral-700 dark:bg-neutral-900 dark:text-neutral-100"
                                                placeholder="Search landing page"
                                            />
                                        </div>
                                        <button
                                            type="button"
                                            onClick={() => navigate({ page_search: filters.page_search, pages_page: 1 })}
                                            className="rounded-lg border border-neutral-300 px-4 text-sm font-medium hover:bg-neutral-50 dark:border-neutral-700 dark:hover:bg-neutral-900"
                                        >
                                            Search
                                        </button>
                                    </div>
                                </div>

                                <LandingPagesTable rows={workspace.pages} />

                                <div className="border-t border-neutral-200 p-4 dark:border-neutral-800">
                                    <Pager
                                        page={workspace.pages_pagination.page}
                                        lastPage={workspace.pages_pagination.last_page}
                                        onPrevious={() => navigate({ pages_page: workspace.pages_pagination.page - 1 })}
                                        onNext={() => navigate({ pages_page: workspace.pages_pagination.page + 1 })}
                                    />
                                </div>
                            </section>

                            <p className="rounded-xl border border-neutral-200 bg-neutral-50 px-4 py-3 text-xs leading-5 text-neutral-600 dark:border-neutral-800 dark:bg-neutral-900 dark:text-neutral-300">
                                {workspace.dataset_note}
                            </p>
                        </>
                    )}
                </main>
            </div>
        </AdminLayout>
    )
}

function LandingPagesTable({ rows }: { rows: GoogleAnalyticsLandingPageRow[] }) {
    if (rows.length === 0) {
        return <div className="p-5"><EmptyState text="No landing pages match the current filters." /></div>
    }

    return (
        <div className="overflow-x-auto">
            <table className="min-w-full text-left text-sm">
                <thead className="bg-neutral-50 text-xs uppercase tracking-wide text-neutral-500 dark:bg-neutral-900 dark:text-neutral-400">
                    <tr>
                        <th className="px-5 py-3">Landing page</th>
                        <th className="px-4 py-3 text-right">Sessions</th>
                        <th className="px-4 py-3 text-right">Engaged</th>
                        <th className="px-4 py-3 text-right">Engagement</th>
                        <th className="px-4 py-3 text-right">Active users*</th>
                        <th className="px-4 py-3 text-right">New users*</th>
                        <th className="px-4 py-3 text-right">Key events</th>
                        <th className="px-5 py-3 text-right">Revenue</th>
                    </tr>
                </thead>
                <tbody className="divide-y divide-neutral-200 dark:divide-neutral-800">
                    {rows.map((row) => (
                        <tr key={row.landing_page}>
                            <td className="max-w-[420px] px-5 py-3 font-medium text-neutral-900 dark:text-neutral-100">
                                <span className="block truncate" title={row.landing_page}>{row.landing_page}</span>
                            </td>
                            <MetricCell value={formatNumber(row.sessions)} />
                            <MetricCell value={formatNumber(row.engaged_sessions)} />
                            <MetricCell value={formatPercent(row.engagement_rate)} />
                            <MetricCell value={formatNumber(row.active_users_row_sum)} />
                            <MetricCell value={formatNumber(row.new_users_row_sum)} />
                            <MetricCell value={formatNumber(row.key_events)} />
                            <MetricCell value={formatDecimal(row.total_revenue)} wide />
                        </tr>
                    ))}
                </tbody>
            </table>
        </div>
    )
}

function MetricCell({ value, wide = false }: { value: string; wide?: boolean }) {
    return <td className={`${wide ? 'px-5' : 'px-4'} py-3 text-right tabular-nums text-neutral-700 dark:text-neutral-300`}>{value}</td>
}

function MetricCard({ label, value, icon }: { label: string; value: string; icon: ReactNode }) {
    return (
        <div className="rounded-2xl border border-neutral-200 bg-white p-4 shadow-sm dark:border-neutral-800 dark:bg-neutral-950">
            <div className="flex items-center gap-2 text-neutral-500 dark:text-neutral-400">
                {icon}
                <span className="text-xs font-semibold uppercase tracking-wide">{label}</span>
            </div>
            <p className="mt-3 text-2xl font-semibold tabular-nums text-neutral-950 dark:text-neutral-100">{value}</p>
        </div>
    )
}

function DateField({ label, value, onChange }: { label: string; value: string; onChange: (value: string) => void }) {
    return (
        <label className="block">
            <span className="mb-1 block text-xs font-medium text-neutral-600 dark:text-neutral-300">{label}</span>
            <input
                type="date"
                value={value}
                onChange={(event) => onChange(event.target.value)}
                className="min-h-10 w-full rounded-lg border border-neutral-300 bg-white px-3 text-sm text-neutral-900 dark:border-neutral-700 dark:bg-neutral-900 dark:text-neutral-100"
            />
        </label>
    )
}

function Pager({ page, lastPage, onPrevious, onNext }: { page: number; lastPage: number; onPrevious: () => void; onNext: () => void }) {
    return (
        <div className="mt-4 flex items-center justify-between gap-3 text-xs text-neutral-500 dark:text-neutral-400">
            <button
                type="button"
                disabled={page <= 1}
                onClick={onPrevious}
                className="rounded-lg border border-neutral-300 px-3 py-2 font-medium disabled:cursor-not-allowed disabled:opacity-40 dark:border-neutral-700"
            >
                Previous
            </button>
            <span>Page {page} of {lastPage}</span>
            <button
                type="button"
                disabled={page >= lastPage}
                onClick={onNext}
                className="rounded-lg border border-neutral-300 px-3 py-2 font-medium disabled:cursor-not-allowed disabled:opacity-40 dark:border-neutral-700"
            >
                Next
            </button>
        </div>
    )
}

function EmptyState({ text }: { text: string }) {
    return <p className="rounded-xl border border-dashed border-neutral-300 px-4 py-5 text-center text-sm text-neutral-500 dark:border-neutral-700 dark:text-neutral-400">{text}</p>
}

function formatNumber(value: number): string {
    return new Intl.NumberFormat(undefined, { maximumFractionDigits: 2 }).format(value)
}

function formatPercent(value: number): string {
    return new Intl.NumberFormat(undefined, { style: 'percent', maximumFractionDigits: 1 }).format(value)
}

function formatDecimal(value: number): string {
    return new Intl.NumberFormat(undefined, { minimumFractionDigits: 2, maximumFractionDigits: 2 }).format(value)
}
