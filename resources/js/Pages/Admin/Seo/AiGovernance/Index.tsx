import { Head, router } from '@inertiajs/react'
import {
    Activity,
    BadgeCheck,
    Bot,
    CircleAlert,
    Clock3,
    Coins,
    FileCheck2,
    Search,
    ShieldCheck,
} from 'lucide-react'
import type { FormEvent, ReactNode } from 'react'
import { useState } from 'react'

import AdminLayout from '@/Layouts/Admin/AdminLayout'
import type {
    SeoAiGovernanceAttemptRow,
    SeoAiGovernanceAuditWorkspace,
    SeoAiGovernanceEventRow,
    SeoAiGovernanceRunSummary,
} from '@/types/seo-ai-governance-audit'

type Props = {
    workspace: SeoAiGovernanceAuditWorkspace
}

export default function SeoAiGovernanceAudit({ workspace }: Props) {
    const [filters, setFilters] = useState(workspace.filters)
    const selected = workspace.selected_run

    const navigate = (next: Record<string, string | number>) => {
        router.get(
            '/admin/seo/ai-governance',
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
        navigate({ page: 1, run: '' })
    }

    const clearFilters = () => {
        router.get(
            '/admin/seo/ai-governance',
            {},
            { preserveScroll: true, preserveState: false, replace: true },
        )
    }

    const selectRun = (run: SeoAiGovernanceRunSummary) => {
        router.get(
            '/admin/seo/ai-governance',
            { ...filters, run: run.public_id },
            { preserveScroll: true, preserveState: false },
        )
    }

    return (
        <AdminLayout
            title="AI Governance Audit"
            description="Read-only operations and audit trail for governed AI SEO executions."
        >
            <Head title="AI Governance Audit" />

            <div className="space-y-6">
                <section className="grid gap-3 sm:grid-cols-2 xl:grid-cols-5">
                    <MetricCard label="Runs" value={workspace.summary.total} icon={<Bot className="h-4 w-4" />} />
                    <MetricCard label="Succeeded" value={workspace.summary.succeeded} icon={<BadgeCheck className="h-4 w-4" />} />
                    <MetricCard label="Failed" value={workspace.summary.failed} icon={<CircleAlert className="h-4 w-4" />} />
                    <MetricCard label="Pending review" value={workspace.summary.pending_review} icon={<Clock3 className="h-4 w-4" />} />
                    <MetricCard label="Published" value={workspace.summary.published} icon={<FileCheck2 className="h-4 w-4" />} />
                </section>

                <section className="rounded-2xl border border-neutral-200 bg-white p-4 shadow-sm dark:border-neutral-800 dark:bg-neutral-950">
                    <form onSubmit={submitFilters} className="grid gap-3 md:grid-cols-2 xl:grid-cols-4">
                        <FilterSelect
                            label="Status"
                            value={filters.status}
                            options={workspace.options.statuses}
                            onChange={(value) => setFilters((current) => ({ ...current, status: value }))}
                        />
                        <FilterSelect
                            label="Approval"
                            value={filters.approval}
                            options={workspace.options.approval_statuses}
                            onChange={(value) => setFilters((current) => ({ ...current, approval: value }))}
                        />
                        <FilterSelect
                            label="Purpose"
                            value={filters.purpose}
                            options={workspace.options.purposes}
                            onChange={(value) => setFilters((current) => ({ ...current, purpose: value }))}
                        />
                        <FilterSelect
                            label="Provider"
                            value={filters.provider}
                            options={workspace.options.providers}
                            onChange={(value) => setFilters((current) => ({ ...current, provider: value }))}
                        />
                        <DateField label="From" value={filters.from} onChange={(value) => setFilters((current) => ({ ...current, from: value }))} />
                        <DateField label="To" value={filters.to} onChange={(value) => setFilters((current) => ({ ...current, to: value }))} />
                        <label className="block md:col-span-2">
                            <span className="mb-1 block text-xs font-medium text-neutral-600 dark:text-neutral-300">Search</span>
                            <div className="relative">
                                <Search className="pointer-events-none absolute left-3 top-1/2 h-4 w-4 -translate-y-1/2 text-neutral-400" />
                                <input
                                    value={filters.search}
                                    onChange={(event) => setFilters((current) => ({ ...current, search: event.target.value }))}
                                    className="min-h-10 w-full rounded-lg border border-neutral-300 bg-white pl-9 pr-3 text-sm text-neutral-900 dark:border-neutral-700 dark:bg-neutral-900 dark:text-neutral-100"
                                    placeholder="Run ID, subject, provider, model, admin, failure code"
                                />
                            </div>
                        </label>
                        <div className="flex gap-2 md:col-span-2 xl:col-span-4">
                            <button type="submit" className="rounded-lg bg-neutral-950 px-4 py-2 text-sm font-semibold text-white hover:bg-neutral-800 dark:bg-white dark:text-neutral-950 dark:hover:bg-neutral-200">
                                Apply filters
                            </button>
                            <button type="button" onClick={clearFilters} className="rounded-lg border border-neutral-300 px-4 py-2 text-sm font-medium hover:bg-neutral-50 dark:border-neutral-700 dark:hover:bg-neutral-900">
                                Clear
                            </button>
                        </div>
                    </form>
                </section>

                <div className="grid min-w-0 gap-6 xl:grid-cols-[330px_minmax(0,1fr)]">
                    <aside className="min-w-0 rounded-2xl border border-neutral-200 bg-white p-4 shadow-sm dark:border-neutral-800 dark:bg-neutral-950">
                        <div className="flex items-center justify-between gap-3">
                            <div>
                                <h2 className="font-semibold text-neutral-950 dark:text-neutral-100">Governed runs</h2>
                                <p className="mt-1 text-xs text-neutral-500 dark:text-neutral-400">Newest matching executions first.</p>
                            </div>
                            <ShieldCheck className="h-5 w-5 text-neutral-400" />
                        </div>

                        <div className="mt-4 space-y-2">
                            {workspace.runs.length === 0 && <EmptyState text="No AI governance runs match the current filters." />}
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
                                    <div className="flex items-start justify-between gap-2">
                                        <p className="min-w-0 truncate text-sm font-semibold text-neutral-950 dark:text-neutral-100">
                                            {run.subject_type ?? 'AI'}{run.subject_key ? ` · ${run.subject_key}` : ''}
                                        </p>
                                        <StatusBadge value={run.status} />
                                    </div>
                                    <p className="mt-1 truncate text-xs text-neutral-500 dark:text-neutral-400">
                                        {run.provider} · {run.model_name}
                                    </p>
                                    <div className="mt-2 flex flex-wrap gap-x-3 gap-y-1 text-[11px] text-neutral-500 dark:text-neutral-400">
                                        <span>{humanize(run.purpose)}</span>
                                        <span>{run.attempt_count} attempt{run.attempt_count === 1 ? '' : 's'}</span>
                                        <span>{humanize(run.approval_status)}</span>
                                    </div>
                                    <p className="mt-2 truncate font-mono text-[10px] text-neutral-400">{run.public_id}</p>
                                </button>
                            ))}
                        </div>

                        <Pager
                            page={workspace.pagination.page}
                            lastPage={workspace.pagination.last_page}
                            onPrevious={() => navigate({ page: workspace.pagination.page - 1, run: '' })}
                            onNext={() => navigate({ page: workspace.pagination.page + 1, run: '' })}
                        />
                    </aside>

                    <main className="min-w-0 space-y-6">
                        {selected === null ? (
                            <EmptyState text="No governed AI run is available for inspection." />
                        ) : (
                            <>
                                <section className="rounded-2xl border border-neutral-200 bg-white p-5 shadow-sm dark:border-neutral-800 dark:bg-neutral-950">
                                    <div className="flex flex-col gap-4 lg:flex-row lg:items-start lg:justify-between">
                                        <div className="min-w-0">
                                            <p className="text-xs font-semibold uppercase tracking-wide text-neutral-500">Selected governance run</p>
                                            <h2 className="mt-1 break-words text-xl font-semibold text-neutral-950 dark:text-neutral-100">
                                                {selected.subject_type ?? 'AI execution'}{selected.subject_key ? ` · ${selected.subject_key}` : ''}
                                            </h2>
                                            <p className="mt-1 break-all font-mono text-xs text-neutral-500 dark:text-neutral-400">{selected.public_id}</p>
                                        </div>
                                        <div className="flex flex-wrap gap-2">
                                            <StatusBadge value={selected.status} />
                                            <NeutralBadge value={humanize(selected.approval_status)} />
                                            {selected.published && <NeutralBadge value="Published" />}
                                        </div>
                                    </div>

                                    <div className="mt-5 grid gap-4 sm:grid-cols-2 xl:grid-cols-4">
                                        <Detail label="Purpose" value={humanize(selected.purpose)} />
                                        <Detail label="Provider" value={selected.provider} />
                                        <Detail label="Model" value={selected.model_name} />
                                        <Detail label="Prompt version" value={selected.prompt_version} />
                                        <Detail label="Requested by" value={identity(selected.requested_by_name, selected.requested_by_email)} />
                                        <Detail label="Reviewed by" value={identity(selected.reviewed_by_name, selected.reviewed_by_email)} />
                                        <Detail label="Created" value={formatDate(selected.created_at)} />
                                        <Detail label="Published" value={formatDate(selected.published_at)} />
                                    </div>

                                    <div className="mt-5 rounded-xl border border-neutral-200 bg-neutral-50 p-4 dark:border-neutral-800 dark:bg-neutral-900">
                                        <p className="text-xs font-semibold uppercase tracking-wide text-neutral-500">Input snapshot</p>
                                        <p className="mt-2 break-all font-mono text-xs text-neutral-700 dark:text-neutral-300">SHA-256 {selected.input_snapshot_hash}</p>
                                        <div className="mt-3 flex flex-wrap gap-2">
                                            {selected.input_field_names.map((field) => <NeutralBadge key={field} value={field} />)}
                                            {selected.input_field_names.length === 0 && <span className="text-xs text-neutral-500">No field inventory recorded.</span>}
                                        </div>
                                        <p className="mt-3 text-xs text-neutral-500 dark:text-neutral-400">Raw AI input is intentionally not stored by the governance ledger.</p>
                                    </div>

                                    {selected.failure_code && (
                                        <div className="mt-4 rounded-xl border border-rose-200 bg-rose-50 px-4 py-3 text-sm text-rose-800 dark:border-rose-900/60 dark:bg-rose-950/30 dark:text-rose-200">
                                            <strong>{selected.failure_code}</strong>{selected.failure_message ? ` — ${selected.failure_message}` : ''}
                                        </div>
                                    )}
                                </section>

                                <section className="grid gap-3 sm:grid-cols-2 xl:grid-cols-4">
                                    <InfoCard label="Attempts" value={String(selected.attempt_count)} icon={<Activity className="h-4 w-4" />} />
                                    <InfoCard label="Input tokens" value={numberOrDash(selected.input_tokens)} icon={<Bot className="h-4 w-4" />} />
                                    <InfoCard label="Output tokens" value={numberOrDash(selected.output_tokens)} icon={<Bot className="h-4 w-4" />} />
                                    <InfoCard label="Reported cost" value={formatCost(selected.cost_amount_minor, selected.cost_currency)} icon={<Coins className="h-4 w-4" />} />
                                </section>

                                <section className="rounded-2xl border border-neutral-200 bg-white shadow-sm dark:border-neutral-800 dark:bg-neutral-950">
                                    <SectionHeader title="Provider attempts" description={`Latest ${workspace.limits.attempts} terminal attempts at most.`} />
                                    <AttemptsTable rows={workspace.attempts} />
                                </section>

                                <section className="rounded-2xl border border-neutral-200 bg-white shadow-sm dark:border-neutral-800 dark:bg-neutral-950">
                                    <SectionHeader title="Governance events" description={`Latest ${workspace.limits.events} immutable audit events at most.`} />
                                    <EventsTable rows={workspace.events} />
                                </section>

                                <section className="rounded-2xl border border-neutral-200 bg-white shadow-sm dark:border-neutral-800 dark:bg-neutral-950">
                                    <SectionHeader title="Validated generated output" description="Stored governed output only; never the raw provider response." />
                                    <div className="p-5">
                                        {selected.generated_output === null ? (
                                            <EmptyState text="No successful governed output is stored for this run." />
                                        ) : (
                                            <pre className="max-h-[480px] overflow-auto rounded-xl bg-neutral-950 p-4 text-xs leading-5 text-neutral-100">
                                                {JSON.stringify(selected.generated_output, null, 2)}
                                            </pre>
                                        )}
                                    </div>
                                </section>
                            </>
                        )}
                    </main>
                </div>

                <p className="rounded-xl border border-neutral-200 bg-neutral-50 px-4 py-3 text-xs leading-5 text-neutral-600 dark:border-neutral-800 dark:bg-neutral-900 dark:text-neutral-300">
                    {workspace.workspace_note}
                </p>
            </div>
        </AdminLayout>
    )
}

function AttemptsTable({ rows }: { rows: SeoAiGovernanceAttemptRow[] }) {
    if (rows.length === 0) {
        return <div className="p-5"><EmptyState text="No terminal provider attempt has been recorded yet." /></div>
    }

    return (
        <div className="overflow-x-auto">
            <table className="min-w-full text-left text-sm">
                <thead className="bg-neutral-50 text-xs uppercase tracking-wide text-neutral-500 dark:bg-neutral-900 dark:text-neutral-400">
                    <tr>
                        <th className="px-5 py-3">Attempt</th>
                        <th className="px-4 py-3">Outcome</th>
                        <th className="px-4 py-3 text-right">Input</th>
                        <th className="px-4 py-3 text-right">Output</th>
                        <th className="px-4 py-3 text-right">Total</th>
                        <th className="px-4 py-3 text-right">Cost</th>
                        <th className="px-4 py-3">Failure</th>
                        <th className="px-5 py-3">Recorded</th>
                    </tr>
                </thead>
                <tbody className="divide-y divide-neutral-200 dark:divide-neutral-800">
                    {rows.map((row) => (
                        <tr key={row.attempt_number}>
                            <td className="px-5 py-3 font-medium">#{row.attempt_number}</td>
                            <td className="px-4 py-3"><StatusBadge value={row.outcome} /></td>
                            <td className="px-4 py-3 text-right tabular-nums">{numberOrDash(row.input_tokens)}</td>
                            <td className="px-4 py-3 text-right tabular-nums">{numberOrDash(row.output_tokens)}</td>
                            <td className="px-4 py-3 text-right tabular-nums">{numberOrDash(row.total_tokens)}</td>
                            <td className="px-4 py-3 text-right tabular-nums">{formatCost(row.cost_amount_minor, row.cost_currency)}</td>
                            <td className="px-4 py-3">{row.failure_code ?? '—'}</td>
                            <td className="whitespace-nowrap px-5 py-3">{formatDate(row.recorded_at)}</td>
                        </tr>
                    ))}
                </tbody>
            </table>
        </div>
    )
}

function EventsTable({ rows }: { rows: SeoAiGovernanceEventRow[] }) {
    if (rows.length === 0) {
        return <div className="p-5"><EmptyState text="No governance events were recorded." /></div>
    }

    return (
        <div className="overflow-x-auto">
            <table className="min-w-full text-left text-sm">
                <thead className="bg-neutral-50 text-xs uppercase tracking-wide text-neutral-500 dark:bg-neutral-900 dark:text-neutral-400">
                    <tr>
                        <th className="px-5 py-3">Event</th>
                        <th className="px-4 py-3">Actor</th>
                        <th className="px-4 py-3">Metadata</th>
                        <th className="px-5 py-3">Occurred</th>
                    </tr>
                </thead>
                <tbody className="divide-y divide-neutral-200 dark:divide-neutral-800">
                    {rows.map((row, index) => (
                        <tr key={`${row.event_type}-${row.occurred_at ?? 'unknown'}-${index}`}>
                            <td className="px-5 py-3"><NeutralBadge value={humanize(row.event_type)} /></td>
                            <td className="px-4 py-3">{identity(row.actor_name, row.actor_email)}</td>
                            <td className="max-w-[420px] px-4 py-3">
                                {row.metadata === null ? '—' : (
                                    <code className="block max-w-[420px] overflow-hidden text-ellipsis whitespace-nowrap text-xs" title={JSON.stringify(row.metadata)}>
                                        {JSON.stringify(row.metadata)}
                                    </code>
                                )}
                            </td>
                            <td className="whitespace-nowrap px-5 py-3">{formatDate(row.occurred_at)}</td>
                        </tr>
                    ))}
                </tbody>
            </table>
        </div>
    )
}

function MetricCard({ label, value, icon }: { label: string; value: number; icon: ReactNode }) {
    return (
        <div className="rounded-2xl border border-neutral-200 bg-white p-4 shadow-sm dark:border-neutral-800 dark:bg-neutral-950">
            <div className="flex items-center gap-2 text-neutral-500 dark:text-neutral-400">
                {icon}
                <span className="text-xs font-semibold uppercase tracking-wide">{label}</span>
            </div>
            <p className="mt-3 text-2xl font-semibold tabular-nums text-neutral-950 dark:text-neutral-100">{value.toLocaleString()}</p>
        </div>
    )
}

function InfoCard({ label, value, icon }: { label: string; value: string; icon: ReactNode }) {
    return (
        <div className="rounded-2xl border border-neutral-200 bg-white p-4 shadow-sm dark:border-neutral-800 dark:bg-neutral-950">
            <div className="flex items-center gap-2 text-neutral-500 dark:text-neutral-400">{icon}<span className="text-xs font-semibold uppercase tracking-wide">{label}</span></div>
            <p className="mt-3 break-words text-lg font-semibold text-neutral-950 dark:text-neutral-100">{value}</p>
        </div>
    )
}

function SectionHeader({ title, description }: { title: string; description: string }) {
    return (
        <div className="border-b border-neutral-200 p-5 dark:border-neutral-800">
            <h2 className="font-semibold text-neutral-950 dark:text-neutral-100">{title}</h2>
            <p className="mt-1 text-sm text-neutral-500 dark:text-neutral-400">{description}</p>
        </div>
    )
}

function Detail({ label, value }: { label: string; value: string }) {
    return <div><p className="text-xs font-medium text-neutral-500">{label}</p><p className="mt-1 break-words text-sm text-neutral-900 dark:text-neutral-100">{value}</p></div>
}

function FilterSelect({ label, value, options, onChange }: { label: string; value: string; options: string[]; onChange: (value: string) => void }) {
    return (
        <label className="block">
            <span className="mb-1 block text-xs font-medium text-neutral-600 dark:text-neutral-300">{label}</span>
            <select value={value} onChange={(event) => onChange(event.target.value)} className="min-h-10 w-full rounded-lg border border-neutral-300 bg-white px-3 text-sm dark:border-neutral-700 dark:bg-neutral-900">
                <option value="">All</option>
                {options.map((option) => <option key={option} value={option}>{humanize(option)}</option>)}
            </select>
        </label>
    )
}

function DateField({ label, value, onChange }: { label: string; value: string; onChange: (value: string) => void }) {
    return (
        <label className="block">
            <span className="mb-1 block text-xs font-medium text-neutral-600 dark:text-neutral-300">{label}</span>
            <input type="date" value={value} onChange={(event) => onChange(event.target.value)} className="min-h-10 w-full rounded-lg border border-neutral-300 bg-white px-3 text-sm dark:border-neutral-700 dark:bg-neutral-900" />
        </label>
    )
}

function Pager({ page, lastPage, onPrevious, onNext }: { page: number; lastPage: number; onPrevious: () => void; onNext: () => void }) {
    return (
        <div className="mt-4 flex items-center justify-between gap-3 text-xs text-neutral-500 dark:text-neutral-400">
            <button type="button" disabled={page <= 1} onClick={onPrevious} className="rounded-lg border border-neutral-300 px-3 py-2 font-medium disabled:cursor-not-allowed disabled:opacity-40 dark:border-neutral-700">Previous</button>
            <span>Page {page} of {lastPage}</span>
            <button type="button" disabled={page >= lastPage} onClick={onNext} className="rounded-lg border border-neutral-300 px-3 py-2 font-medium disabled:cursor-not-allowed disabled:opacity-40 dark:border-neutral-700">Next</button>
        </div>
    )
}

function StatusBadge({ value }: { value: string }) {
    const positive = ['succeeded', 'approved', 'published'].includes(value)
    const negative = ['failed', 'rejected'].includes(value)
    const classes = positive
        ? 'border-emerald-200 bg-emerald-50 text-emerald-700 dark:border-emerald-900/60 dark:bg-emerald-950/30 dark:text-emerald-300'
        : negative
            ? 'border-rose-200 bg-rose-50 text-rose-700 dark:border-rose-900/60 dark:bg-rose-950/30 dark:text-rose-300'
            : 'border-amber-200 bg-amber-50 text-amber-700 dark:border-amber-900/60 dark:bg-amber-950/30 dark:text-amber-300'

    return <span className={`inline-flex rounded-full border px-2 py-0.5 text-[11px] font-semibold ${classes}`}>{humanize(value)}</span>
}

function NeutralBadge({ value }: { value: string }) {
    return <span className="inline-flex rounded-full border border-neutral-200 bg-neutral-50 px-2 py-0.5 text-[11px] font-medium text-neutral-700 dark:border-neutral-700 dark:bg-neutral-900 dark:text-neutral-300">{value}</span>
}

function EmptyState({ text }: { text: string }) {
    return <p className="rounded-xl border border-dashed border-neutral-300 px-4 py-5 text-center text-sm text-neutral-500 dark:border-neutral-700 dark:text-neutral-400">{text}</p>
}

function humanize(value: string): string {
    return value.replaceAll('_', ' ').replace(/\b\w/g, (character) => character.toUpperCase())
}

function identity(name: string | null, email: string | null): string {
    if (name && email) return `${name} (${email})`
    return name ?? email ?? '—'
}

function formatDate(value: string | null): string {
    if (!value) return '—'
    return new Intl.DateTimeFormat(undefined, { dateStyle: 'medium', timeStyle: 'short' }).format(new Date(value))
}

function numberOrDash(value: number | null): string {
    return value === null ? '—' : value.toLocaleString()
}

function formatCost(amountMinor: number | null, currency: string | null): string {
    if (amountMinor === null || currency === null) return '—'
    return `${currency} ${(amountMinor / 100).toFixed(2)}`
}
