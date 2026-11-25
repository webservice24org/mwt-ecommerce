import { Head, router } from '@inertiajs/react'
import { BarChart3, CalendarDays, ExternalLink, MousePointerClick, Search, TrendingUp } from 'lucide-react'
import type { FormEvent, ReactNode } from 'react'
import { useMemo, useState } from 'react'

import AdminLayout from '@/Layouts/Admin/AdminLayout'
import type {
    SearchConsoleDimensionRow,
    SearchConsoleMetricSet,
    SearchConsolePerformanceWorkspace,
    SearchConsoleSyncRunSummary,
} from '@/types/seo-performance-report'

type Props = {
    workspace: SearchConsolePerformanceWorkspace
}

export default function SearchConsolePerformanceReport({ workspace }: Props) {
    const [filters, setFilters] = useState(workspace.filters)
    const selected = workspace.selected_run

    const maxDailyImpressions = useMemo(
        () => Math.max(1, ...workspace.daily.map((row) => row.impressions)),
        [workspace.daily],
    )

    const navigate = (next: Record<string, string | number>) => {
        router.get(
            '/admin/seo/performance/search-console',
            {
                ...filters,
                ...next,
            },
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
            queries_page: 1,
            pages_page: 1,
        })
    }

    const selectRun = (run: SearchConsoleSyncRunSummary) => {
        router.get(
            '/admin/seo/performance/search-console',
            { run: run.public_id },
            { preserveScroll: true, preserveState: false },
        )
    }

    return (
        <AdminLayout
            title="Search Console Performance"
            description="Read-only reporting from completed Google Search Console synchronization snapshots."
        >
            <Head title="Search Console Performance" />

            <div className="grid gap-6 xl:grid-cols-[310px_minmax(0,1fr)]">
                <aside className="min-w-0 rounded-2xl border border-neutral-200 bg-white p-4 shadow-sm dark:border-neutral-800 dark:bg-neutral-950">
                    <div className="flex items-center justify-between gap-3">
                        <div>
                            <h2 className="font-semibold text-neutral-950 dark:text-neutral-100">Completed snapshots</h2>
                            <p className="mt-1 text-xs text-neutral-500 dark:text-neutral-400">Each report uses one sync run only.</p>
                        </div>
                        <BarChart3 className="h-5 w-5 text-neutral-400" aria-hidden="true" />
                    </div>

                    <div className="mt-4 space-y-2">
                        {workspace.runs.length === 0 && (
                            <EmptyState text="No completed Search Console sync is available yet." />
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
                                    {run.connection?.display_name ?? 'Search Console'}
                                </p>
                                <p className="mt-1 truncate text-xs text-neutral-500 dark:text-neutral-400">
                                    {run.connection?.property_identifier ?? 'Property unavailable'}
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
                        <section className="rounded-2xl border border-dashed border-neutral-300 bg-white p-10 text-center dark:border-neutral-700 dark:bg-neutral-950">
                            <TrendingUp className="mx-auto h-8 w-8 text-neutral-400" aria-hidden="true" />
                            <h2 className="mt-4 font-semibold text-neutral-950 dark:text-neutral-100">No completed Search Console snapshot</h2>
                            <p className="mt-2 text-sm text-neutral-500 dark:text-neutral-400">Complete an O.4 synchronization before opening performance reports.</p>
                        </section>
                    ) : (
                        <>
                            <section className="space-y-4">
                                <div className="flex flex-col gap-3 lg:flex-row lg:items-start lg:justify-between">
                                    <div>
                                        <h2 className="text-lg font-semibold text-neutral-950 dark:text-neutral-100">
                                            {selected.connection?.display_name ?? 'Search Console snapshot'}
                                        </h2>
                                        <p className="mt-1 text-sm text-neutral-500 dark:text-neutral-400">
                                            {selected.connection?.property_identifier ?? 'Property unavailable'} · completed {formatDate(selected.completed_at)}
                                        </p>
                                    </div>
                                    <div className="text-xs text-neutral-500 dark:text-neutral-400 lg:text-right">
                                        <p>{formatNumber(selected.rows_persisted)} persisted rows · {formatNumber(selected.queue_passes)} queue passes</p>
                                        <p className="mt-1"><code>{selected.public_id}</code></p>
                                    </div>
                                </div>

                                {selected.truncated && (
                                    <div className="rounded-xl border border-amber-300 bg-amber-50 p-4 text-sm text-amber-950 dark:border-amber-800 dark:bg-amber-950/20 dark:text-amber-100">
                                        This snapshot reached the configured Search Console import bound. Rankings and totals represent the persisted bounded snapshot, not an exhaustive export.
                                    </div>
                                )}

                                <form onSubmit={submitDates} className="grid gap-3 rounded-xl border border-neutral-200 bg-white p-4 dark:border-neutral-800 dark:bg-neutral-950 sm:grid-cols-[1fr_1fr_auto]">
                                    <DateField label="From" value={filters.from} onChange={(value) => setFilters((current) => ({ ...current, from: value }))} />
                                    <DateField label="To" value={filters.to} onChange={(value) => setFilters((current) => ({ ...current, to: value }))} />
                                    <button type="submit" className="min-h-10 self-end rounded-lg bg-neutral-950 px-4 text-sm font-semibold text-white hover:bg-neutral-800 dark:bg-white dark:text-neutral-950 dark:hover:bg-neutral-200">
                                        Apply dates
                                    </button>
                                </form>

                                <div className="grid gap-3 sm:grid-cols-2 xl:grid-cols-4">
                                    <MetricCard label="Clicks" value={formatNumber(workspace.summary.clicks)} icon={<MousePointerClick className="h-4 w-4" />} />
                                    <MetricCard label="Impressions" value={formatNumber(workspace.summary.impressions)} icon={<TrendingUp className="h-4 w-4" />} />
                                    <MetricCard label="CTR" value={formatPercent(workspace.summary.ctr)} icon={<BarChart3 className="h-4 w-4" />} />
                                    <MetricCard label="Avg. position" value={formatPosition(workspace.summary.position)} icon={<Search className="h-4 w-4" />} />
                                </div>

                                <p className="text-xs text-neutral-500 dark:text-neutral-400">{workspace.dataset_note}</p>
                            </section>

                            <section className="rounded-2xl border border-neutral-200 bg-white p-4 shadow-sm dark:border-neutral-800 dark:bg-neutral-950 sm:p-5">
                                <div className="flex items-center gap-2">
                                    <CalendarDays className="h-5 w-5 text-neutral-400" aria-hidden="true" />
                                    <h2 className="font-semibold text-neutral-950 dark:text-neutral-100">Daily performance</h2>
                                </div>

                                <div className="mt-4 overflow-x-auto">
                                    <table className="min-w-full text-left text-sm">
                                        <thead className="text-xs uppercase tracking-wide text-neutral-500 dark:text-neutral-400">
                                            <tr>
                                                <th className="px-3 py-2">Date</th>
                                                <th className="px-3 py-2 text-right">Clicks</th>
                                                <th className="px-3 py-2 text-right">Impressions</th>
                                                <th className="px-3 py-2 text-right">CTR</th>
                                                <th className="px-3 py-2 text-right">Position</th>
                                                <th className="min-w-40 px-3 py-2">Relative impressions</th>
                                            </tr>
                                        </thead>
                                        <tbody className="divide-y divide-neutral-200 dark:divide-neutral-800">
                                            {workspace.daily.map((row) => (
                                                <tr key={row.date}>
                                                    <td className="px-3 py-3 font-medium">{row.date}</td>
                                                    <td className="px-3 py-3 text-right">{formatNumber(row.clicks)}</td>
                                                    <td className="px-3 py-3 text-right">{formatNumber(row.impressions)}</td>
                                                    <td className="px-3 py-3 text-right">{formatPercent(row.ctr)}</td>
                                                    <td className="px-3 py-3 text-right">{formatPosition(row.position)}</td>
                                                    <td className="px-3 py-3">
                                                        <div className="h-2 overflow-hidden rounded-full bg-neutral-100 dark:bg-neutral-800">
                                                            <div
                                                                className="h-full rounded-full bg-neutral-500"
                                                                style={{ width: `${Math.max(2, (row.impressions / maxDailyImpressions) * 100)}%` }}
                                                            />
                                                        </div>
                                                    </td>
                                                </tr>
                                            ))}
                                        </tbody>
                                    </table>
                                </div>
                                {workspace.daily.length === 0 && <EmptyState text="No landing-page metrics match this date range." />}
                            </section>

                            <DimensionSection
                                title="Top queries"
                                placeholder="Search queries"
                                value={filters.query_search}
                                onValueChange={(value) => setFilters((current) => ({ ...current, query_search: value }))}
                                rows={workspace.queries}
                                page={workspace.queries_pagination.page}
                                lastPage={workspace.queries_pagination.last_page}
                                onSubmit={() => navigate({ query_search: filters.query_search, queries_page: 1 })}
                                onPrevious={() => navigate({ queries_page: workspace.queries_pagination.page - 1 })}
                                onNext={() => navigate({ queries_page: workspace.queries_pagination.page + 1 })}
                            />

                            <DimensionSection
                                title="Top landing pages"
                                placeholder="Search page URL"
                                value={filters.page_search}
                                onValueChange={(value) => setFilters((current) => ({ ...current, page_search: value }))}
                                rows={workspace.pages}
                                page={workspace.pages_pagination.page}
                                lastPage={workspace.pages_pagination.last_page}
                                linkValues
                                onSubmit={() => navigate({ page_search: filters.page_search, pages_page: 1 })}
                                onPrevious={() => navigate({ pages_page: workspace.pages_pagination.page - 1 })}
                                onNext={() => navigate({ pages_page: workspace.pages_pagination.page + 1 })}
                            />
                        </>
                    )}
                </main>
            </div>
        </AdminLayout>
    )
}

function DimensionSection({
    title,
    placeholder,
    value,
    onValueChange,
    rows,
    page,
    lastPage,
    linkValues = false,
    onSubmit,
    onPrevious,
    onNext,
}: {
    title: string
    placeholder: string
    value: string
    onValueChange: (value: string) => void
    rows: SearchConsoleDimensionRow[]
    page: number
    lastPage: number
    linkValues?: boolean
    onSubmit: () => void
    onPrevious: () => void
    onNext: () => void
}) {
    return (
        <section className="rounded-2xl border border-neutral-200 bg-white p-4 shadow-sm dark:border-neutral-800 dark:bg-neutral-950 sm:p-5">
            <div className="flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">
                <h2 className="font-semibold text-neutral-950 dark:text-neutral-100">{title}</h2>
                <form
                    onSubmit={(event) => {
                        event.preventDefault()
                        onSubmit()
                    }}
                    className="flex gap-2"
                >
                    <input
                        value={value}
                        onChange={(event) => onValueChange(event.target.value)}
                        placeholder={placeholder}
                        className="min-h-10 min-w-0 flex-1 rounded-lg border border-neutral-300 bg-white px-3 text-sm dark:border-neutral-700 dark:bg-neutral-950 sm:w-72"
                    />
                    <button type="submit" className="min-h-10 rounded-lg border border-neutral-300 px-4 text-sm font-semibold hover:bg-neutral-50 dark:border-neutral-700 dark:hover:bg-neutral-900">
                        Filter
                    </button>
                </form>
            </div>

            <div className="mt-4 overflow-x-auto">
                <table className="min-w-full text-left text-sm">
                    <thead className="text-xs uppercase tracking-wide text-neutral-500 dark:text-neutral-400">
                        <tr>
                            <th className="px-3 py-2">{linkValues ? 'Landing page' : 'Query'}</th>
                            <th className="px-3 py-2 text-right">Clicks</th>
                            <th className="px-3 py-2 text-right">Impressions</th>
                            <th className="px-3 py-2 text-right">CTR</th>
                            <th className="px-3 py-2 text-right">Position</th>
                        </tr>
                    </thead>
                    <tbody className="divide-y divide-neutral-200 dark:divide-neutral-800">
                        {rows.map((row) => (
                            <tr key={row.value}>
                                <td className="max-w-xl px-3 py-3 font-medium text-neutral-950 dark:text-neutral-100">
                                    {linkValues ? (
                                        <a href={row.value} target="_blank" rel="noreferrer" className="inline-flex max-w-full items-center gap-1 hover:underline">
                                            <span className="truncate">{row.value}</span>
                                            <ExternalLink className="h-3.5 w-3.5 shrink-0" aria-hidden="true" />
                                        </a>
                                    ) : row.value}
                                </td>
                                <td className="px-3 py-3 text-right">{formatNumber(row.clicks)}</td>
                                <td className="px-3 py-3 text-right">{formatNumber(row.impressions)}</td>
                                <td className="px-3 py-3 text-right">{formatPercent(row.ctr)}</td>
                                <td className="px-3 py-3 text-right">{formatPosition(row.position)}</td>
                            </tr>
                        ))}
                    </tbody>
                </table>
            </div>

            {rows.length === 0 && <EmptyState text="No rows match this filter." />}
            <Pager page={page} lastPage={lastPage} onPrevious={onPrevious} onNext={onNext} />
        </section>
    )
}

function MetricCard({ label, value, icon }: { label: string; value: string; icon: ReactNode }) {
    return (
        <div className="rounded-xl border border-neutral-200 bg-white p-4 dark:border-neutral-800 dark:bg-neutral-950">
            <div className="flex items-center justify-between gap-3 text-neutral-500 dark:text-neutral-400">
                <span className="text-xs font-semibold uppercase tracking-wide">{label}</span>
                {icon}
            </div>
            <p className="mt-3 text-2xl font-semibold text-neutral-950 dark:text-neutral-100">{value}</p>
        </div>
    )
}

function DateField({ label, value, onChange }: { label: string; value: string; onChange: (value: string) => void }) {
    return (
        <label className="grid gap-1 text-sm">
            <span className="font-medium text-neutral-700 dark:text-neutral-300">{label}</span>
            <input
                type="date"
                value={value}
                onChange={(event) => onChange(event.target.value)}
                className="min-h-10 rounded-lg border border-neutral-300 bg-white px-3 dark:border-neutral-700 dark:bg-neutral-950"
            />
        </label>
    )
}

function Pager({ page, lastPage, onPrevious, onNext }: { page: number; lastPage: number; onPrevious: () => void; onNext: () => void }) {
    if (lastPage <= 1) return null

    return (
        <div className="mt-4 flex items-center justify-between gap-3 text-sm">
            <button type="button" disabled={page <= 1} onClick={onPrevious} className="rounded-lg border border-neutral-300 px-3 py-2 disabled:cursor-not-allowed disabled:opacity-40 dark:border-neutral-700">
                Previous
            </button>
            <span className="text-neutral-500 dark:text-neutral-400">Page {page} of {lastPage}</span>
            <button type="button" disabled={page >= lastPage} onClick={onNext} className="rounded-lg border border-neutral-300 px-3 py-2 disabled:cursor-not-allowed disabled:opacity-40 dark:border-neutral-700">
                Next
            </button>
        </div>
    )
}

function EmptyState({ text }: { text: string }) {
    return <p className="mt-4 rounded-xl border border-dashed border-neutral-300 p-5 text-center text-sm text-neutral-500 dark:border-neutral-700 dark:text-neutral-400">{text}</p>
}

function formatNumber(value: number): string {
    return new Intl.NumberFormat(undefined, { maximumFractionDigits: 0 }).format(value)
}

function formatPercent(value: number): string {
    return new Intl.NumberFormat(undefined, { style: 'percent', maximumFractionDigits: 2 }).format(value)
}

function formatPosition(value: number): string {
    return value === 0 ? '—' : value.toFixed(2)
}

function formatDate(value: string | null): string {
    if (value === null) return '—'

    return new Intl.DateTimeFormat(undefined, {
        dateStyle: 'medium',
        timeStyle: 'short',
    }).format(new Date(value))
}
