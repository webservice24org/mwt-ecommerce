import AdminLayout from '@/Layouts/Admin/AdminLayout'
import type {
    SeoAuditFindingRow,
    SeoAuditRunRow,
    SeoAuditWorkspace,
} from '@/types/seo-audit'
import {
    Head,
    Link,
    router,
} from '@inertiajs/react'
import {
    AlertCircle,
    ArrowLeft,
    ArrowRight,
    CheckCircle2,
    Clock3,
    FileSearch,
    LoaderCircle,
    Play,
    Search,
    ShieldAlert,
    TriangleAlert,
    XCircle,
} from 'lucide-react'
import type {
    FormEvent,
    ReactNode,
} from 'react'
import {
    useMemo,
    useState,
} from 'react'

type Props = {
    workspace: SeoAuditWorkspace
}

type FilterState = {
    severity: string
    rule: string
    search: string
}

export default function Index({
    workspace,
}: Props) {
    const [filters, setFilters] =
        useState<FilterState>(() => ({
            severity: workspace.filters.severity,
            rule: workspace.filters.rule,
            search: workspace.filters.search,
        }))
    const [starting, setStarting] = useState(false)
    const [startError, setStartError] = useState('')

    const selected = workspace.selected_run

    const stages = useMemo<Array<[string, string | null]>>(
        () => selected === null
            ? []
            : [
                ['Catalog seeded', selected.catalog_seeded_at],
                ['Crawl settled', selected.crawl_settled_at],
                ['Image health', selected.image_health_enriched_at],
                ['Rules evaluated', selected.evaluated_at],
            ],
        [selected],
    )

    const startAudit = () => {
        setStarting(true)
        setStartError('')

        router.post(
            route('admin.seo.audits.runs.store'),
            {},
            {
                preserveScroll: true,
                onError: (errors) => {
                    setStartError(
                        typeof errors.audit === 'string'
                            ? errors.audit
                            : 'The SEO audit could not be started.',
                    )
                },
                onFinish: () => setStarting(false),
            },
        )
    }

    const submitFilters = (
        event: FormEvent<HTMLFormElement>,
    ) => {
        event.preventDefault()
        navigateFindings(filters, 1)
    }

    const navigateFindings = (
        nextFilters: FilterState,
        findingsPage: number,
    ) => {
        router.get(
            route('admin.seo.audits.index'),
            compactQuery({
                run: selected?.public_id ?? '',
                severity: nextFilters.severity,
                rule: nextFilters.rule,
                search: nextFilters.search,
                findings_page: findingsPage,
                runs_page: workspace.runs_pagination.page,
            }),
            {
                preserveState: true,
                preserveScroll: true,
                replace: true,
            },
        )
    }

    const navigateRuns = (runsPage: number) => {
        router.get(
            route('admin.seo.audits.index'),
            compactQuery({
                run: selected?.public_id ?? '',
                severity: filters.severity,
                rule: filters.rule,
                search: filters.search,
                findings_page: workspace.findings_pagination.page,
                runs_page: runsPage,
            }),
            {
                preserveState: true,
                preserveScroll: true,
                replace: true,
            },
        )
    }

    return (
        <AdminLayout title="">
            <Head title="SEO audit" />

            <div className="mx-auto max-w-[1600px] space-y-6">
                <header className="flex flex-col gap-4 lg:flex-row lg:items-start lg:justify-between">
                    <div>
                        <p className="text-xs font-semibold uppercase tracking-[0.18em] text-neutral-500 dark:text-neutral-400">
                            SEO Audit & Internal Linking Engine
                        </p>
                        <h1 className="mt-2 text-2xl font-bold tracking-tight text-neutral-950 dark:text-neutral-100 sm:text-3xl">
                            SEO audit workspace
                        </h1>
                        <p className="mt-2 max-w-3xl text-sm leading-6 text-neutral-500 dark:text-neutral-400">
                            Run the bounded server-side audit pipeline and review deterministic technical SEO findings. This workspace does not apply SEO changes automatically.
                        </p>
                    </div>

                    <button
                        type="button"
                        onClick={startAudit}
                        disabled={!workspace.can_start || starting}
                        className="inline-flex min-h-10 items-center justify-center gap-2 rounded-lg bg-neutral-950 px-4 text-sm font-semibold text-white transition hover:bg-neutral-800 disabled:cursor-not-allowed disabled:opacity-50 dark:bg-white dark:text-neutral-950 dark:hover:bg-neutral-200"
                    >
                        {starting ? (
                            <LoaderCircle className="h-4 w-4 animate-spin" aria-hidden="true" />
                        ) : (
                            <Play className="h-4 w-4" aria-hidden="true" />
                        )}
                        {workspace.can_start ? 'Run SEO audit' : 'Audit already active'}
                    </button>
                </header>

                {startError !== '' && (
                    <div className="rounded-xl border border-red-300 bg-red-50 p-4 text-sm text-red-900 dark:border-red-900/60 dark:bg-red-950/20 dark:text-red-200">
                        {startError}
                    </div>
                )}

                {workspace.active_run !== null && (
                    <div className="rounded-xl border border-blue-300 bg-blue-50 p-4 text-sm text-blue-900 dark:border-blue-900/60 dark:bg-blue-950/20 dark:text-blue-200">
                        <div className="flex items-start gap-3">
                            <LoaderCircle className="mt-0.5 h-5 w-5 animate-spin" aria-hidden="true" />
                            <div>
                                <p className="font-semibold">SEO audit is {workspace.active_run.status}</p>
                                <p className="mt-1">A second site-wide audit is blocked until this run finishes or fails.</p>
                            </div>
                        </div>
                    </div>
                )}

                <div className="grid gap-6 xl:grid-cols-[380px_minmax(0,1fr)]">
                    <section className="rounded-2xl border border-neutral-200 bg-white p-4 shadow-sm dark:border-neutral-800 dark:bg-neutral-950">
                        <div className="flex items-center justify-between gap-3">
                            <div>
                                <h2 className="font-semibold text-neutral-950 dark:text-neutral-100">Audit runs</h2>
                                <p className="mt-1 text-xs text-neutral-500 dark:text-neutral-400">
                                    {formatNumber(workspace.runs_pagination.total)} recorded runs
                                </p>
                            </div>
                            <FileSearch className="h-5 w-5 text-neutral-400" aria-hidden="true" />
                        </div>

                        <div className="mt-4 space-y-2">
                            {workspace.runs.length === 0 && (
                                <EmptyState text="No SEO audits have been requested yet." />
                            )}

                            {workspace.runs.map((run) => (
                                <RunLink
                                    key={run.public_id}
                                    run={run}
                                    selected={run.public_id === selected?.public_id}
                                />
                            ))}
                        </div>

                        <Pager
                            page={workspace.runs_pagination.page}
                            lastPage={workspace.runs_pagination.last_page}
                            onPrevious={() => navigateRuns(workspace.runs_pagination.page - 1)}
                            onNext={() => navigateRuns(workspace.runs_pagination.page + 1)}
                        />
                    </section>

                    <main className="min-w-0 space-y-6">
                        {selected === null ? (
                            <section className="rounded-2xl border border-dashed border-neutral-300 bg-white p-10 text-center dark:border-neutral-700 dark:bg-neutral-950">
                                <FileSearch className="mx-auto h-8 w-8 text-neutral-400" aria-hidden="true" />
                                <h2 className="mt-4 font-semibold text-neutral-950 dark:text-neutral-100">No audit selected</h2>
                                <p className="mt-2 text-sm text-neutral-500 dark:text-neutral-400">Start an audit to populate technical SEO findings.</p>
                            </section>
                        ) : (
                            <>
                                <section className="space-y-4">
                                    <div className="flex flex-col gap-2 sm:flex-row sm:items-center sm:justify-between">
                                        <div>
                                            <div className="flex items-center gap-2">
                                                <RunStatusBadge status={selected.status} />
                                                <code className="text-xs text-neutral-500 dark:text-neutral-400">
                                                    {selected.public_id}
                                                </code>
                                            </div>
                                            <p className="mt-2 text-sm text-neutral-500 dark:text-neutral-400">
                                                Requested by {selected.requested_by_name} · {formatDate(selected.requested_at)}
                                            </p>
                                        </div>
                                        <p className="text-xs text-neutral-500 dark:text-neutral-400">
                                            Queue passes: {formatNumber(selected.queue_passes)}
                                        </p>
                                    </div>

                                    <div className="grid gap-3 sm:grid-cols-2 xl:grid-cols-6">
                                        <MetricCard label="URLs scanned" value={selected.urls_scanned} icon={<Search className="h-4 w-4" />} />
                                        <MetricCard label="Findings" value={selected.findings_total} icon={<FileSearch className="h-4 w-4" />} />
                                        <MetricCard label="Critical" value={selected.critical_count} icon={<ShieldAlert className="h-4 w-4" />} />
                                        <MetricCard label="Errors" value={selected.error_count} icon={<XCircle className="h-4 w-4" />} />
                                        <MetricCard label="Warnings" value={selected.warning_count} icon={<TriangleAlert className="h-4 w-4" />} />
                                        <MetricCard label="Info" value={selected.info_count} icon={<AlertCircle className="h-4 w-4" />} />
                                    </div>

                                    <div className="grid gap-3 sm:grid-cols-2 xl:grid-cols-4">
                                        {stages.map(([label, timestamp]) => (
                                            <StageCard
                                                key={label}
                                                label={label}
                                                timestamp={timestamp}
                                            />
                                        ))}
                                    </div>

                                    {selected.status === 'failed' && (
                                        <div className="rounded-xl border border-red-300 bg-red-50 p-4 text-sm text-red-900 dark:border-red-900/60 dark:bg-red-950/20 dark:text-red-200">
                                            <p className="font-semibold">Audit failed{selected.failure_code ? ` · ${selected.failure_code}` : ''}</p>
                                            {selected.failure_message && <p className="mt-1">{selected.failure_message}</p>}
                                        </div>
                                    )}
                                </section>

                                <section className="rounded-2xl border border-neutral-200 bg-white p-4 shadow-sm dark:border-neutral-800 dark:bg-neutral-950 sm:p-5">
                                    <form onSubmit={submitFilters} className="grid gap-3 lg:grid-cols-[minmax(220px,1fr)_190px_280px_auto]">
                                        <label className="relative">
                                            <span className="sr-only">Search findings</span>
                                            <Search className="pointer-events-none absolute left-3 top-3 h-4 w-4 text-neutral-400" aria-hidden="true" />
                                            <input
                                                value={filters.search}
                                                onChange={(event) => setFilters((current) => ({ ...current, search: event.target.value }))}
                                                placeholder="Search message or URL"
                                                className="min-h-10 w-full rounded-lg border border-neutral-300 bg-white pl-9 pr-3 text-sm dark:border-neutral-700 dark:bg-neutral-950"
                                            />
                                        </label>

                                        <select
                                            value={filters.severity}
                                            onChange={(event) => setFilters((current) => ({ ...current, severity: event.target.value }))}
                                            className="min-h-10 rounded-lg border border-neutral-300 bg-white px-3 text-sm dark:border-neutral-700 dark:bg-neutral-950"
                                        >
                                            <option value="">All severities</option>
                                            {workspace.options.severities.map((option) => (
                                                <option key={option.value} value={option.value}>{option.label}</option>
                                            ))}
                                        </select>

                                        <select
                                            value={filters.rule}
                                            onChange={(event) => setFilters((current) => ({ ...current, rule: event.target.value }))}
                                            className="min-h-10 rounded-lg border border-neutral-300 bg-white px-3 text-sm dark:border-neutral-700 dark:bg-neutral-950"
                                        >
                                            <option value="">All rules</option>
                                            {workspace.options.rules.map((option) => (
                                                <option key={option.value} value={option.value}>{option.label}</option>
                                            ))}
                                        </select>

                                        <button type="submit" className="min-h-10 rounded-lg border border-neutral-300 px-4 text-sm font-semibold hover:bg-neutral-50 dark:border-neutral-700 dark:hover:bg-neutral-900">
                                            Filter
                                        </button>
                                    </form>

                                    <div className="mt-5 space-y-3">
                                        {workspace.findings.length === 0 && (
                                            <EmptyState text="No findings match this run and filter." />
                                        )}

                                        {workspace.findings.map((finding) => (
                                            <FindingCard
                                                key={finding.fingerprint}
                                                finding={finding}
                                            />
                                        ))}
                                    </div>

                                    <Pager
                                        page={workspace.findings_pagination.page}
                                        lastPage={workspace.findings_pagination.last_page}
                                        onPrevious={() => navigateFindings(filters, workspace.findings_pagination.page - 1)}
                                        onNext={() => navigateFindings(filters, workspace.findings_pagination.page + 1)}
                                    />
                                </section>
                            </>
                        )}
                    </main>
                </div>
            </div>
        </AdminLayout>
    )
}

function RunLink({
    run,
    selected,
}: {
    run: SeoAuditRunRow
    selected: boolean
}) {
    return (
        <Link
            href={route('admin.seo.audits.index', { run: run.public_id })}
            className={`block rounded-xl border p-3 transition ${selected ? 'border-neutral-950 bg-neutral-50 dark:border-neutral-100 dark:bg-neutral-900' : 'border-neutral-200 hover:bg-neutral-50 dark:border-neutral-800 dark:hover:bg-neutral-900'}`}
        >
            <div className="flex items-center justify-between gap-3">
                <RunStatusBadge status={run.status} />
                <span className="text-xs text-neutral-500 dark:text-neutral-400">{formatDate(run.requested_at)}</span>
            </div>
            <p className="mt-2 truncate text-sm font-medium text-neutral-900 dark:text-neutral-100">{run.requested_by_name}</p>
            <p className="mt-1 text-xs text-neutral-500 dark:text-neutral-400">
                {formatNumber(run.urls_scanned)} URLs · {formatNumber(run.findings_total)} findings
            </p>
        </Link>
    )
}

function FindingCard({ finding }: { finding: SeoAuditFindingRow }) {
    return (
        <article className="rounded-xl border border-neutral-200 p-4 dark:border-neutral-800">
            <div className="flex flex-col gap-2 sm:flex-row sm:items-start sm:justify-between">
                <div>
                    <div className="flex flex-wrap items-center gap-2">
                        <SeverityBadge severity={finding.severity} />
                        <span className="text-xs font-semibold uppercase tracking-wide text-neutral-500 dark:text-neutral-400">{finding.rule_label}</span>
                    </div>
                    <p className="mt-2 text-sm font-semibold text-neutral-950 dark:text-neutral-100">{finding.message}</p>
                </div>
                <span className="text-xs text-neutral-500 dark:text-neutral-400">{finding.subject_type}</span>
            </div>

            {finding.url && <p className="mt-2 break-all text-xs text-neutral-600 dark:text-neutral-300">URL: {finding.url}</p>}
            {finding.source_url && <p className="mt-1 break-all text-xs text-neutral-500 dark:text-neutral-400">Source: {finding.source_url}</p>}
            {finding.remediation && (
                <div className="mt-3 rounded-lg bg-neutral-50 p-3 text-sm leading-6 text-neutral-700 dark:bg-neutral-900 dark:text-neutral-300">
                    <span className="font-semibold">Remediation:</span> {finding.remediation}
                </div>
            )}

            {Object.keys(finding.evidence).length > 0 && (
                <details className="mt-3">
                    <summary className="cursor-pointer text-xs font-semibold text-neutral-600 dark:text-neutral-300">Evidence</summary>
                    <pre className="mt-2 max-h-56 overflow-auto rounded-lg bg-neutral-950 p-3 text-xs text-neutral-100">{JSON.stringify(finding.evidence, null, 2)}</pre>
                </details>
            )}
        </article>
    )
}

function MetricCard({ label, value, icon }: { label: string; value: number; icon: ReactNode }) {
    return (
        <div className="rounded-xl border border-neutral-200 bg-white p-4 dark:border-neutral-800 dark:bg-neutral-950">
            <div className="flex items-center justify-between text-neutral-500 dark:text-neutral-400">
                <span className="text-xs font-semibold uppercase tracking-wide">{label}</span>
                {icon}
            </div>
            <p className="mt-2 text-2xl font-bold text-neutral-950 dark:text-neutral-100">{formatNumber(value)}</p>
        </div>
    )
}

function StageCard({ label, timestamp }: { label: string; timestamp: string | null }) {
    return (
        <div className="rounded-xl border border-neutral-200 p-3 dark:border-neutral-800">
            <div className="flex items-center gap-2">
                {timestamp ? <CheckCircle2 className="h-4 w-4 text-emerald-600" /> : <Clock3 className="h-4 w-4 text-neutral-400" />}
                <span className="text-xs font-semibold text-neutral-700 dark:text-neutral-300">{label}</span>
            </div>
            <p className="mt-2 text-xs text-neutral-500 dark:text-neutral-400">{timestamp ? formatDate(timestamp) : 'Pending'}</p>
        </div>
    )
}

function RunStatusBadge({ status }: { status: SeoAuditRunRow['status'] }) {
    const classes = status === 'completed'
        ? 'bg-emerald-100 text-emerald-800 dark:bg-emerald-950 dark:text-emerald-200'
        : status === 'failed'
            ? 'bg-red-100 text-red-800 dark:bg-red-950 dark:text-red-200'
            : 'bg-blue-100 text-blue-800 dark:bg-blue-950 dark:text-blue-200'

    return <span className={`rounded-full px-2.5 py-1 text-xs font-semibold ${classes}`}>{status.replace('_', ' ')}</span>
}

function SeverityBadge({ severity }: { severity: SeoAuditFindingRow['severity'] }) {
    const classes = severity === 'critical' || severity === 'error'
        ? 'bg-red-100 text-red-800 dark:bg-red-950 dark:text-red-200'
        : severity === 'warning'
            ? 'bg-amber-100 text-amber-800 dark:bg-amber-950 dark:text-amber-200'
            : 'bg-blue-100 text-blue-800 dark:bg-blue-950 dark:text-blue-200'

    return <span className={`rounded-full px-2.5 py-1 text-xs font-semibold ${classes}`}>{severity}</span>
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
    if (lastPage <= 1) {
        return null
    }

    return (
        <div className="mt-4 flex items-center justify-between gap-3 border-t border-neutral-200 pt-4 dark:border-neutral-800">
            <button type="button" onClick={onPrevious} disabled={page <= 1} className="inline-flex min-h-9 items-center gap-2 rounded-lg border border-neutral-300 px-3 text-sm disabled:opacity-40 dark:border-neutral-700">
                <ArrowLeft className="h-4 w-4" /> Previous
            </button>
            <span className="text-xs text-neutral-500 dark:text-neutral-400">Page {page} of {lastPage}</span>
            <button type="button" onClick={onNext} disabled={page >= lastPage} className="inline-flex min-h-9 items-center gap-2 rounded-lg border border-neutral-300 px-3 text-sm disabled:opacity-40 dark:border-neutral-700">
                Next <ArrowRight className="h-4 w-4" />
            </button>
        </div>
    )
}

function EmptyState({ text }: { text: string }) {
    return <div className="rounded-xl border border-dashed border-neutral-300 p-6 text-center text-sm text-neutral-500 dark:border-neutral-700 dark:text-neutral-400">{text}</div>
}

function compactQuery(values: Record<string, string | number>): Record<string, string | number> {
    return Object.fromEntries(
        Object.entries(values).filter(([, value]) => value !== '' && value !== 1),
    )
}

function formatNumber(value: number): string {
    return new Intl.NumberFormat().format(value)
}

function formatDate(value: string): string {
    return new Intl.DateTimeFormat(undefined, {
        dateStyle: 'medium',
        timeStyle: 'short',
    }).format(new Date(value))
}
