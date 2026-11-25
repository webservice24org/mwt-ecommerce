import AdminLayout from '@/Layouts/Admin/AdminLayout'
import type {
    SeoInternalLinkOpportunityRow,
    SeoInternalLinkReviewState,
    SeoInternalLinkWorkspace,
} from '@/types/seo-internal-links'
import {
    Head,
    Link,
    router,
} from '@inertiajs/react'
import {
    ArrowLeft,
    ArrowRight,
    Check,
    ExternalLink,
    Link2,
    Search,
    ShieldCheck,
    X,
} from 'lucide-react'
import type { FormEvent } from 'react'
import { useState } from 'react'

type Props = {
    workspace: SeoInternalLinkWorkspace
}

type Filters = {
    run: string
    state: string
    search: string
}

export default function Index({ workspace }: Props) {
    const [filters, setFilters] = useState<Filters>(() => ({
        run: workspace.filters.run,
        state: workspace.filters.state,
        search: workspace.filters.search,
    }))
    const [notes, setNotes] = useState<Record<string, string>>({})
    const [busy, setBusy] = useState<string | null>(null)
    const [reviewError, setReviewError] = useState('')

    const submitFilters = (event: FormEvent<HTMLFormElement>) => {
        event.preventDefault()
        navigate(filters, 1)
    }

    const navigate = (next: Filters, page: number) => {
        router.get(
            route('admin.seo.internal-links.index'),
            compactQuery({
                run: next.run,
                state: next.state,
                search: next.search,
                page,
            }),
            {
                preserveState: true,
                preserveScroll: true,
                replace: true,
            },
        )
    }

    const review = (
        opportunity: SeoInternalLinkOpportunityRow,
        action: Exclude<SeoInternalLinkReviewState, 'pending'>,
    ) => {
        const selected = workspace.selected_run

        if (selected === null) {
            return
        }

        setBusy(opportunity.fingerprint)
        setReviewError('')

        router.patch(
            route('admin.seo.internal-links.review', {
                run: selected.public_id,
                fingerprint: opportunity.fingerprint,
            }),
            {
                action,
                note: notes[opportunity.fingerprint] ?? '',
            },
            {
                preserveScroll: true,
                onError: (errors) => {
                    setReviewError(
                        typeof errors.review === 'string'
                            ? errors.review
                            : 'The review decision could not be saved.',
                    )
                },
                onFinish: () => setBusy(null),
            },
        )
    }

    return (
        <AdminLayout title="">
            <Head title="Internal linking review" />

            <div className="mx-auto max-w-[1500px] space-y-6">
                <header className="flex flex-col gap-4 lg:flex-row lg:items-start lg:justify-between">
                    <div>
                        <p className="text-xs font-semibold uppercase tracking-[0.18em] text-neutral-500 dark:text-neutral-400">
                            SEO Audit & Internal Linking Engine
                        </p>
                        <h1 className="mt-2 text-2xl font-bold tracking-tight text-neutral-950 dark:text-neutral-100 sm:text-3xl">
                            Internal linking review
                        </h1>
                        <p className="mt-2 max-w-3xl text-sm leading-6 text-neutral-500 dark:text-neutral-400">
                            Review deterministic linking opportunities from completed audits. Accepting a recommendation records approval only and never edits storefront content automatically.
                        </p>
                    </div>

                    <Link
                        href={route('admin.seo.audits.index')}
                        className="inline-flex min-h-10 items-center justify-center gap-2 rounded-lg border border-neutral-300 px-4 text-sm font-semibold text-neutral-800 hover:bg-neutral-50 dark:border-neutral-700 dark:text-neutral-200 dark:hover:bg-neutral-900"
                    >
                        <ArrowLeft className="h-4 w-4" aria-hidden="true" />
                        Audit workspace
                    </Link>
                </header>

                <div className="rounded-xl border border-blue-300 bg-blue-50 p-4 text-sm text-blue-900 dark:border-blue-900/60 dark:bg-blue-950/20 dark:text-blue-200">
                    <div className="flex items-start gap-3">
                        <ShieldCheck className="mt-0.5 h-5 w-5 shrink-0" aria-hidden="true" />
                        <div>
                            <p className="font-semibold">Review-only workflow</p>
                            <p className="mt-1">Accepted opportunities are editorial approvals. N.9 does not insert links, rewrite content, or publish changes.</p>
                        </div>
                    </div>
                </div>

                {reviewError !== '' && (
                    <div className="rounded-xl border border-red-300 bg-red-50 p-4 text-sm text-red-900 dark:border-red-900/60 dark:bg-red-950/20 dark:text-red-200">
                        {reviewError}
                    </div>
                )}

                <form
                    onSubmit={submitFilters}
                    className="grid gap-3 rounded-2xl border border-neutral-200 bg-white p-4 shadow-sm dark:border-neutral-800 dark:bg-neutral-950 lg:grid-cols-[minmax(220px,1fr)_180px_minmax(220px,1fr)_auto]"
                >
                    <select
                        value={filters.run}
                        onChange={(event) => setFilters((current) => ({ ...current, run: event.target.value }))}
                        className="min-h-10 rounded-lg border border-neutral-300 bg-white px-3 text-sm dark:border-neutral-700 dark:bg-neutral-950"
                    >
                        <option value="">Latest completed audit</option>
                        {workspace.runs.map((run) => (
                            <option key={run.public_id} value={run.public_id}>
                                {formatDate(run.requested_at)} · {formatNumber(run.opportunities)} opportunities
                            </option>
                        ))}
                    </select>

                    <select
                        value={filters.state}
                        onChange={(event) => setFilters((current) => ({ ...current, state: event.target.value }))}
                        className="min-h-10 rounded-lg border border-neutral-300 bg-white px-3 text-sm dark:border-neutral-700 dark:bg-neutral-950"
                    >
                        <option value="">All review states</option>
                        {workspace.options.states.map((state) => (
                            <option key={state.value} value={state.value}>{state.label}</option>
                        ))}
                    </select>

                    <label className="relative">
                        <Search className="pointer-events-none absolute left-3 top-3 h-4 w-4 text-neutral-400" aria-hidden="true" />
                        <input
                            value={filters.search}
                            onChange={(event) => setFilters((current) => ({ ...current, search: event.target.value }))}
                            placeholder="Search source, target, anchor..."
                            className="min-h-10 w-full rounded-lg border border-neutral-300 bg-white pl-9 pr-3 text-sm dark:border-neutral-700 dark:bg-neutral-950"
                        />
                    </label>

                    <button
                        type="submit"
                        className="min-h-10 rounded-lg bg-neutral-950 px-4 text-sm font-semibold text-white hover:bg-neutral-800 dark:bg-white dark:text-neutral-950 dark:hover:bg-neutral-200"
                    >
                        Filter
                    </button>
                </form>

                <section className="grid gap-3 sm:grid-cols-2 xl:grid-cols-4">
                    <Metric label="Total" value={workspace.summary.total} />
                    <Metric label="Pending" value={workspace.summary.pending} />
                    <Metric label="Accepted" value={workspace.summary.accepted} />
                    <Metric label="Dismissed" value={workspace.summary.dismissed} />
                </section>

                {workspace.selected_run === null ? (
                    <EmptyState text="No completed SEO audit is available yet." />
                ) : (
                    <section className="space-y-4">
                        <div className="flex flex-col gap-2 sm:flex-row sm:items-center sm:justify-between">
                            <div>
                                <h2 className="font-semibold text-neutral-950 dark:text-neutral-100">Review queue</h2>
                                <p className="mt-1 text-xs text-neutral-500 dark:text-neutral-400">
                                    Audit {workspace.selected_run.public_id} · {formatNumber(workspace.pagination.total)} matching opportunities
                                </p>
                            </div>
                        </div>

                        {workspace.opportunities.length === 0 && (
                            <EmptyState text="No internal linking opportunities match this audit and filter." />
                        )}

                        <div className="space-y-4">
                            {workspace.opportunities.map((opportunity) => (
                                <OpportunityCard
                                    key={opportunity.fingerprint}
                                    opportunity={opportunity}
                                    note={notes[opportunity.fingerprint] ?? ''}
                                    busy={busy === opportunity.fingerprint}
                                    onNote={(value) => setNotes((current) => ({ ...current, [opportunity.fingerprint]: value }))}
                                    onReview={(action) => review(opportunity, action)}
                                />
                            ))}
                        </div>

                        <Pager
                            page={workspace.pagination.page}
                            lastPage={workspace.pagination.last_page}
                            onPrevious={() => navigate(filters, workspace.pagination.page - 1)}
                            onNext={() => navigate(filters, workspace.pagination.page + 1)}
                        />
                    </section>
                )}
            </div>
        </AdminLayout>
    )
}

function OpportunityCard({
    opportunity,
    note,
    busy,
    onNote,
    onReview,
}: {
    opportunity: SeoInternalLinkOpportunityRow
    note: string
    busy: boolean
    onNote: (value: string) => void
    onReview: (action: 'accepted' | 'dismissed') => void
}) {
    const pending = opportunity.review_state === 'pending'

    return (
        <article className="rounded-2xl border border-neutral-200 bg-white p-5 shadow-sm dark:border-neutral-800 dark:bg-neutral-950">
            <div className="flex flex-col gap-3 lg:flex-row lg:items-start lg:justify-between">
                <div className="min-w-0">
                    <div className="flex flex-wrap items-center gap-2">
                        <ReviewBadge state={opportunity.review_state} />
                        <span className="rounded-full bg-neutral-100 px-2.5 py-1 text-xs font-semibold text-neutral-700 dark:bg-neutral-900 dark:text-neutral-300">
                            Score {opportunity.score}
                        </span>
                        <span className="text-xs text-neutral-500 dark:text-neutral-400">{opportunity.target_subject_type}</span>
                    </div>
                    <p className="mt-3 text-sm leading-6 text-neutral-700 dark:text-neutral-300">{opportunity.reason}</p>
                </div>

                {opportunity.target_subject_key && (
                    <code className="shrink-0 text-xs text-neutral-500 dark:text-neutral-400">{opportunity.target_subject_key}</code>
                )}
            </div>

            <div className="mt-4 grid gap-3 lg:grid-cols-2">
                <UrlCard label="Source page" url={opportunity.source_url} />
                <UrlCard label="Target page" url={opportunity.target_url} />
            </div>

            <div className="mt-4 rounded-xl bg-neutral-50 p-4 dark:bg-neutral-900">
                <p className="text-xs font-semibold uppercase tracking-wide text-neutral-500 dark:text-neutral-400">Suggested anchor</p>
                <p className="mt-1 text-sm font-semibold text-neutral-950 dark:text-neutral-100">
                    {opportunity.suggested_anchor ?? 'No deterministic anchor suggested'}
                </p>
            </div>

            {pending ? (
                <div className="mt-4 space-y-3 border-t border-neutral-200 pt-4 dark:border-neutral-800">
                    <label className="block">
                        <span className="text-xs font-semibold text-neutral-600 dark:text-neutral-300">Optional review note</span>
                        <textarea
                            value={note}
                            onChange={(event) => onNote(event.target.value)}
                            maxLength={500}
                            rows={2}
                            className="mt-2 w-full rounded-lg border border-neutral-300 bg-white px-3 py-2 text-sm dark:border-neutral-700 dark:bg-neutral-950"
                            placeholder="Editorial context, placement note, or dismissal reason..."
                        />
                    </label>

                    <div className="flex flex-col gap-2 sm:flex-row">
                        <button
                            type="button"
                            disabled={busy}
                            onClick={() => onReview('accepted')}
                            className="inline-flex min-h-10 items-center justify-center gap-2 rounded-lg bg-emerald-700 px-4 text-sm font-semibold text-white hover:bg-emerald-600 disabled:opacity-50"
                        >
                            <Check className="h-4 w-4" aria-hidden="true" />
                            Accept recommendation
                        </button>
                        <button
                            type="button"
                            disabled={busy}
                            onClick={() => onReview('dismissed')}
                            className="inline-flex min-h-10 items-center justify-center gap-2 rounded-lg border border-neutral-300 px-4 text-sm font-semibold text-neutral-800 hover:bg-neutral-50 disabled:opacity-50 dark:border-neutral-700 dark:text-neutral-200 dark:hover:bg-neutral-900"
                        >
                            <X className="h-4 w-4" aria-hidden="true" />
                            Dismiss
                        </button>
                    </div>
                </div>
            ) : (
                <div className="mt-4 border-t border-neutral-200 pt-4 text-sm text-neutral-600 dark:border-neutral-800 dark:text-neutral-300">
                    <p>
                        Reviewed{opportunity.reviewed_by_name ? ` by ${opportunity.reviewed_by_name}` : ''}
                        {opportunity.reviewed_at ? ` on ${formatDate(opportunity.reviewed_at)}` : ''}.
                    </p>
                    {opportunity.review_note && (
                        <p className="mt-2 rounded-lg bg-neutral-50 p-3 dark:bg-neutral-900">{opportunity.review_note}</p>
                    )}
                </div>
            )}
        </article>
    )
}

function UrlCard({ label, url }: { label: string; url: string }) {
    return (
        <div className="rounded-xl border border-neutral-200 p-3 dark:border-neutral-800">
            <div className="flex items-center gap-2 text-xs font-semibold uppercase tracking-wide text-neutral-500 dark:text-neutral-400">
                <Link2 className="h-4 w-4" aria-hidden="true" />
                {label}
            </div>
            <a
                href={url}
                target="_blank"
                rel="noreferrer"
                className="mt-2 flex items-start gap-2 break-all text-sm text-blue-700 hover:underline dark:text-blue-300"
            >
                <span className="min-w-0">{url}</span>
                <ExternalLink className="mt-0.5 h-3.5 w-3.5 shrink-0" aria-hidden="true" />
            </a>
        </div>
    )
}

function ReviewBadge({ state }: { state: SeoInternalLinkReviewState }) {
    const classes = state === 'accepted'
        ? 'bg-emerald-100 text-emerald-800 dark:bg-emerald-950 dark:text-emerald-200'
        : state === 'dismissed'
            ? 'bg-neutral-200 text-neutral-800 dark:bg-neutral-800 dark:text-neutral-200'
            : 'bg-amber-100 text-amber-800 dark:bg-amber-950 dark:text-amber-200'

    return <span className={`rounded-full px-2.5 py-1 text-xs font-semibold ${classes}`}>{state}</span>
}

function Metric({ label, value }: { label: string; value: number }) {
    return (
        <div className="rounded-xl border border-neutral-200 bg-white p-4 shadow-sm dark:border-neutral-800 dark:bg-neutral-950">
            <p className="text-xs font-semibold uppercase tracking-wide text-neutral-500 dark:text-neutral-400">{label}</p>
            <p className="mt-2 text-2xl font-bold text-neutral-950 dark:text-neutral-100">{formatNumber(value)}</p>
        </div>
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
    if (lastPage <= 1) {
        return null
    }

    return (
        <div className="flex items-center justify-between gap-3 border-t border-neutral-200 pt-4 dark:border-neutral-800">
            <button
                type="button"
                onClick={onPrevious}
                disabled={page <= 1}
                className="inline-flex min-h-9 items-center gap-2 rounded-lg border border-neutral-300 px-3 text-sm disabled:opacity-40 dark:border-neutral-700"
            >
                <ArrowLeft className="h-4 w-4" aria-hidden="true" /> Previous
            </button>
            <span className="text-xs text-neutral-500 dark:text-neutral-400">Page {page} of {lastPage}</span>
            <button
                type="button"
                onClick={onNext}
                disabled={page >= lastPage}
                className="inline-flex min-h-9 items-center gap-2 rounded-lg border border-neutral-300 px-3 text-sm disabled:opacity-40 dark:border-neutral-700"
            >
                Next <ArrowRight className="h-4 w-4" aria-hidden="true" />
            </button>
        </div>
    )
}

function EmptyState({ text }: { text: string }) {
    return (
        <div className="rounded-2xl border border-dashed border-neutral-300 bg-white p-10 text-center text-sm text-neutral-500 dark:border-neutral-700 dark:bg-neutral-950 dark:text-neutral-400">
            {text}
        </div>
    )
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
