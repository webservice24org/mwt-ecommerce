import AdminLayout from '@/Layouts/Admin/AdminLayout'
import { Head, Link, router } from '@inertiajs/react'
import {
    ArrowLeft,
    CheckCircle2,
    Clock3,
    FileCheck2,
    RefreshCw,
    ShieldCheck,
    Sparkles,
    ThumbsDown,
    ThumbsUp,
    XCircle,
} from 'lucide-react'
import {
    useMemo,
    useState,
} from 'react'

type Suggestion = {
    id: number
    bulk_item_id: number
    request_id: number
    entity_type: string
    entity_id: number
    entity_name: string
    entity_slug: string
    field: string
    field_label: string
    existing_value: string | null
    current_value: string | null
    suggested_value: string
    rationale: string | null
    review_status: 'pending' | 'approved' | 'rejected'
    reviewed_at: string | null
    freshness: {
        state: string
        is_stale: boolean
    }
    can_approve: boolean
    can_reject: boolean
}

type RecentBatch = {
    id: number
    decision: string
    status: string
    summary: {
        total?: number
        approved?: number
        rejected?: number
        already_decided?: number
        blocked_stale?: number
        failed?: number
    }
    reviewed_by_admin_id: number | null
    note: string | null
    reviewed_at: string
}

type Props = {
    workspace: {
        plan: {
            id: number
            reference: string
            status: string
            plan_hash: string
        }
        summary: {
            total: number
            pending: number
            approved: number
            rejected: number
            fresh: number
            stale: number
            fresh_pending: number
        }
        suggestions: Suggestion[]
        recent_batches: RecentBatch[]
    }
}

type Decision = 'approved' | 'rejected'

export default function Review({
    workspace,
}: Props) {
    const [selected, setSelected] =
        useState<Set<number>>(
            new Set(),
        )

    const [note, setNote] =
        useState('')

    const selectedRows =
        useMemo(
            () =>
                workspace.suggestions.filter(
                    (
                        suggestion,
                    ) =>
                        selected.has(
                            suggestion.id,
                        ),
                ),
            [
                selected,
                workspace.suggestions,
            ],
        )

    const selectedStale =
        selectedRows.filter(
            (
                suggestion,
            ) =>
                suggestion.freshness
                    .is_stale,
        ).length

    const toggle = (
        id: number,
    ) => {
        setSelected(
            (
                current,
            ) => {
                const next =
                    new Set(
                        current,
                    )

                if (next.has(id)) {
                    next.delete(id)
                } else {
                    next.add(id)
                }

                return next
            },
        )
    }

    const selectFreshPending = () => {
        setSelected(
            new Set(
                workspace.suggestions
                    .filter(
                        (
                            suggestion,
                        ) =>
                            suggestion.review_status ===
                                'pending' &&
                            !suggestion
                                .freshness
                                .is_stale,
                    )
                    .map(
                        (
                            suggestion,
                        ) =>
                            suggestion.id,
                    ),
            ),
        )
    }

    const selectAllPending = () => {
        setSelected(
            new Set(
                workspace.suggestions
                    .filter(
                        (
                            suggestion,
                        ) =>
                            suggestion.review_status ===
                            'pending',
                    )
                    .map(
                        (
                            suggestion,
                        ) =>
                            suggestion.id,
                    ),
            ),
        )
    }

    const clear = () => {
        setSelected(
            new Set(),
        )
    }

    const review = (
        decision: Decision,
    ) => {
        if (
            selected.size ===
            0
        ) {
            return
        }

        router.post(
            route(
                'admin.seo.bulk.plans.review.store',
                {
                    bulkJobId:
                        workspace.plan
                            .id,
                },
            ),
            {
                suggestion_ids:
                    Array.from(
                        selected,
                    ),
                decision,
                note:
                    note.trim() ||
                    null,
            },
            {
                preserveScroll: true,
                onSuccess: () => {
                    clear()
                    setNote('')
                },
            },
        )
    }

    return (
        <AdminLayout title="">
            <Head
                title={`Bulk Review — ${workspace.plan.reference}`}
            />

            <div className="space-y-6">
                <Header
                    plan={
                        workspace.plan
                    }
                />

                <SafetyNotice />

                <SummaryGrid
                    summary={
                        workspace.summary
                    }
                />

                <div className="grid gap-6 xl:grid-cols-[minmax(0,1fr)_330px]">
                    <main className="min-w-0 space-y-4">
                        <ReviewToolbar
                            selected={
                                selected.size
                            }
                            selectedStale={
                                selectedStale
                            }
                            note={note}
                            setNote={
                                setNote
                            }
                            onSelectFreshPending={
                                selectFreshPending
                            }
                            onSelectAllPending={
                                selectAllPending
                            }
                            onClear={
                                clear
                            }
                            onApprove={() =>
                                review(
                                    'approved',
                                )
                            }
                            onReject={() =>
                                review(
                                    'rejected',
                                )
                            }
                        />

                        <SuggestionTable
                            suggestions={
                                workspace.suggestions
                            }
                            selected={
                                selected
                            }
                            onToggle={
                                toggle
                            }
                        />
                    </main>

                    <aside className="space-y-6 xl:sticky xl:top-6 xl:self-start">
                        <ReviewPolicy />

                        <RecentBatches
                            batches={
                                workspace.recent_batches
                            }
                        />
                    </aside>
                </div>
            </div>
        </AdminLayout>
    )
}

function Header({
    plan,
}: {
    plan: Props['workspace']['plan']
}) {
    return (
        <div>
            <Link
                href={route(
                    'admin.seo.bulk.plans.show',
                    {
                        bulkJobId:
                            plan.id,
                    },
                )}
                className="inline-flex items-center gap-2 text-sm font-medium text-indigo-600 hover:text-indigo-700 dark:text-indigo-400"
            >
                <ArrowLeft className="h-4 w-4" />
                Back to execution plan
            </Link>

            <div className="mt-3 flex flex-wrap items-center gap-2">
                <span className="rounded-full border border-neutral-200 bg-neutral-50 px-2.5 py-1 text-xs font-semibold capitalize text-neutral-700 dark:border-neutral-700 dark:bg-neutral-900 dark:text-neutral-200">
                    {plan.status.replaceAll(
                        '_',
                        ' ',
                    )}
                </span>

                <span className="text-xs text-neutral-500 dark:text-neutral-400">
                    {plan.reference}
                </span>
            </div>

            <div className="mt-3 flex flex-col gap-3 sm:flex-row sm:items-start sm:justify-between">
                <div>
                    <h1 className="text-2xl font-bold tracking-tight text-neutral-950 dark:text-neutral-100">
                        Bulk Review & Approval
                    </h1>

                    <p className="mt-2 max-w-3xl text-sm leading-6 text-neutral-500 dark:text-neutral-400">
                        Review the draft metadata generated by this bulk plan,
                        re-check freshness, and append human approval or rejection
                        decisions before guarded live application.
                    </p>
                </div>

                <Link
                    href={route(
                        'admin.seo.bulk.plans.apply',
                        {
                            bulkJobId:
                                plan.id,
                        },
                    )}
                    className="inline-flex h-10 shrink-0 items-center justify-center gap-2 rounded-lg border border-emerald-200 bg-emerald-50 px-4 text-sm font-semibold text-emerald-700 hover:bg-emerald-100 dark:border-emerald-900/60 dark:bg-emerald-950/20 dark:text-emerald-300"
                >
                    <FileCheck2 className="h-4 w-4" />
                    Apply approved
                </Link>
            </div>
        </div>
    )
}

function SafetyNotice() {
    return (
        <div className="rounded-xl border border-indigo-200 bg-indigo-50/70 p-4 dark:border-indigo-900/60 dark:bg-indigo-950/20">
            <div className="flex items-start gap-3">
                <ShieldCheck className="mt-0.5 h-5 w-5 shrink-0 text-indigo-600 dark:text-indigo-400" />

                <div>
                    <p className="text-sm font-semibold text-indigo-950 dark:text-indigo-100">
                        Approval is still not application
                    </p>

                    <p className="mt-1 text-sm leading-6 text-indigo-800/80 dark:text-indigo-300/80">
                        K.3 appends review history only. Fresh suggestions can
                        be approved or rejected. Stale suggestions can be
                        rejected but are blocked from approval until fresh AI
                        drafts are generated.
                    </p>
                </div>
            </div>
        </div>
    )
}

function SummaryGrid({
    summary,
}: {
    summary: Props['workspace']['summary']
}) {
    const cards = [
        {
            label:
                'Suggestions',
            value:
                summary.total,
            note: `${summary.pending} pending review`,
            icon:
                Sparkles,
        },
        {
            label:
                'Approved',
            value:
                summary.approved,
            note:
                'Human reviewed, not applied',
            icon:
                CheckCircle2,
        },
        {
            label:
                'Rejected',
            value:
                summary.rejected,
            note:
                'Excluded from later application',
            icon:
                XCircle,
        },
        {
            label:
                'Stale',
            value:
                summary.stale,
            note: `${summary.fresh_pending} fresh pending suggestions`,
            icon:
                RefreshCw,
        },
    ]

    return (
        <div className="grid gap-4 md:grid-cols-2 xl:grid-cols-4">
            {cards.map(
                (
                    card,
                ) => (
                    <div
                        key={
                            card.label
                        }
                        className="rounded-xl border border-neutral-200 bg-white p-5 shadow-sm dark:border-neutral-800 dark:bg-neutral-950"
                    >
                        <div className="flex items-start justify-between gap-4">
                            <div>
                                <p className="text-sm font-medium text-neutral-500 dark:text-neutral-400">
                                    {card.label}
                                </p>

                                <p className="mt-2 text-2xl font-bold text-neutral-950 dark:text-neutral-100">
                                    {card.value}
                                </p>

                                <p className="mt-2 text-xs leading-5 text-neutral-500 dark:text-neutral-400">
                                    {card.note}
                                </p>
                            </div>

                            <div className="flex h-10 w-10 items-center justify-center rounded-lg bg-neutral-100 text-neutral-700 dark:bg-neutral-900 dark:text-neutral-300">
                                <card.icon className="h-5 w-5" />
                            </div>
                        </div>
                    </div>
                ),
            )}
        </div>
    )
}

function ReviewToolbar({
    selected,
    selectedStale,
    note,
    setNote,
    onSelectFreshPending,
    onSelectAllPending,
    onClear,
    onApprove,
    onReject,
}: {
    selected: number
    selectedStale: number
    note: string
    setNote: (
        value: string,
    ) => void
    onSelectFreshPending: () => void
    onSelectAllPending: () => void
    onClear: () => void
    onApprove: () => void
    onReject: () => void
}) {
    return (
        <section className="rounded-xl border border-neutral-200 bg-white p-5 shadow-sm dark:border-neutral-800 dark:bg-neutral-950">
            <div className="flex flex-col gap-4 lg:flex-row lg:items-start lg:justify-between">
                <div>
                    <p className="font-semibold text-neutral-950 dark:text-neutral-100">
                        {selected} selected
                    </p>

                    <p className="mt-1 text-xs text-neutral-500 dark:text-neutral-400">
                        {selectedStale} stale selected; stale approvals will be
                        reported as blocked, not silently accepted.
                    </p>
                </div>

                <div className="flex flex-wrap gap-2">
                    <button
                        type="button"
                        onClick={
                            onSelectFreshPending
                        }
                        className={secondaryButton}
                    >
                        Fresh pending
                    </button>

                    <button
                        type="button"
                        onClick={
                            onSelectAllPending
                        }
                        className={secondaryButton}
                    >
                        All pending
                    </button>

                    <button
                        type="button"
                        onClick={
                            onClear
                        }
                        className={secondaryButton}
                    >
                        Clear
                    </button>
                </div>
            </div>

            <textarea
                value={note}
                onChange={(
                    event,
                ) =>
                    setNote(
                        event.target
                            .value,
                    )
                }
                maxLength={1000}
                rows={3}
                placeholder="Optional review note applied to each newly appended decision..."
                className="mt-4 block w-full rounded-lg border border-neutral-300 bg-white px-3 py-2 text-sm text-neutral-900 shadow-sm outline-none focus:border-indigo-500 focus:ring-2 focus:ring-indigo-500/20 dark:border-neutral-700 dark:bg-neutral-950 dark:text-neutral-100"
            />

            <div className="mt-4 flex flex-wrap justify-end gap-2">
                <button
                    type="button"
                    disabled={
                        selected ===
                        0
                    }
                    onClick={
                        onReject
                    }
                    className="inline-flex h-10 items-center gap-2 rounded-lg border border-red-200 px-4 text-sm font-semibold text-red-700 hover:bg-red-50 disabled:cursor-not-allowed disabled:opacity-40 dark:border-red-900/60 dark:text-red-400 dark:hover:bg-red-950/20"
                >
                    <ThumbsDown className="h-4 w-4" />
                    Reject selected
                </button>

                <button
                    type="button"
                    disabled={
                        selected ===
                        0
                    }
                    onClick={
                        onApprove
                    }
                    className="inline-flex h-10 items-center gap-2 rounded-lg bg-neutral-950 px-4 text-sm font-semibold text-white hover:bg-neutral-800 disabled:cursor-not-allowed disabled:opacity-40 dark:bg-white dark:text-neutral-950 dark:hover:bg-neutral-200"
                >
                    <ThumbsUp className="h-4 w-4" />
                    Approve selected
                </button>
            </div>
        </section>
    )
}

function SuggestionTable({
    suggestions,
    selected,
    onToggle,
}: {
    suggestions: Suggestion[]
    selected: Set<number>
    onToggle: (
        id: number,
    ) => void
}) {
    if (
        suggestions.length ===
        0
    ) {
        return (
            <div className="rounded-xl border border-dashed border-neutral-300 bg-white p-10 text-center text-sm text-neutral-500 dark:border-neutral-700 dark:bg-neutral-950 dark:text-neutral-400">
                This plan has no completed AI metadata suggestions available
                for bulk review yet.
            </div>
        )
    }

    return (
        <div className="overflow-hidden rounded-xl border border-neutral-200 bg-white shadow-sm dark:border-neutral-800 dark:bg-neutral-950">
            <div className="overflow-x-auto">
                <table className="min-w-[1400px] w-full divide-y divide-neutral-200 text-sm dark:divide-neutral-800">
                    <thead>
                        <tr className="text-left text-xs uppercase tracking-wide text-neutral-500 dark:text-neutral-400">
                            <th className="px-5 py-3 font-medium">
                                Select
                            </th>
                            <th className="px-5 py-3 font-medium">
                                Entity / field
                            </th>
                            <th className="px-5 py-3 font-medium">
                                Existing at generation
                            </th>
                            <th className="px-5 py-3 font-medium">
                                Current
                            </th>
                            <th className="px-5 py-3 font-medium">
                                AI suggestion
                            </th>
                            <th className="px-5 py-3 font-medium">
                                Review
                            </th>
                        </tr>
                    </thead>

                    <tbody className="divide-y divide-neutral-100 dark:divide-neutral-900">
                        {suggestions.map(
                            (
                                suggestion,
                            ) => (
                                <tr
                                    key={
                                        suggestion.id
                                    }
                                >
                                    <td className="px-5 py-4 align-top">
                                        <input
                                            type="checkbox"
                                            checked={selected.has(
                                                suggestion.id,
                                            )}
                                            onChange={() =>
                                                onToggle(
                                                    suggestion.id,
                                                )
                                            }
                                            className="h-4 w-4 rounded border-neutral-300"
                                        />
                                    </td>

                                    <td className="px-5 py-4 align-top">
                                        <Link
                                            href={route(
                                                'admin.seo.entities.show',
                                                {
                                                    entityType:
                                                        suggestion.entity_type,
                                                    entityId:
                                                        suggestion.entity_id,
                                                },
                                            )}
                                            className="font-semibold text-neutral-900 hover:text-indigo-600 dark:text-neutral-100 dark:hover:text-indigo-400"
                                        >
                                            {suggestion.entity_name}
                                        </Link>

                                        <p className="mt-1 text-xs text-neutral-500 dark:text-neutral-400">
                                            {suggestion.entity_type} #{suggestion.entity_id} • request #{suggestion.request_id}
                                        </p>

                                        <div className="mt-2 flex flex-wrap gap-2">
                                            <span className="rounded-full border border-indigo-200 bg-indigo-50 px-2 py-1 text-[11px] font-semibold text-indigo-700 dark:border-indigo-900 dark:bg-indigo-950/20 dark:text-indigo-300">
                                                {suggestion.field_label}
                                            </span>

                                            <FreshnessBadge
                                                stale={
                                                    suggestion.freshness
                                                        .is_stale
                                                }
                                                state={
                                                    suggestion.freshness
                                                        .state
                                                }
                                            />
                                        </div>
                                    </td>

                                    <td className="px-5 py-4 align-top">
                                        <Value
                                            value={
                                                suggestion.existing_value
                                            }
                                        />
                                    </td>

                                    <td className="px-5 py-4 align-top">
                                        <Value
                                            value={
                                                suggestion.current_value
                                            }
                                        />
                                    </td>

                                    <td className="px-5 py-4 align-top">
                                        <p className="max-w-sm whitespace-pre-wrap break-words leading-6 text-neutral-900 dark:text-neutral-100">
                                            {suggestion.suggested_value}
                                        </p>

                                        {suggestion.rationale && (
                                            <p className="mt-2 max-w-sm text-xs leading-5 text-neutral-500 dark:text-neutral-400">
                                                {suggestion.rationale}
                                            </p>
                                        )}
                                    </td>

                                    <td className="px-5 py-4 align-top">
                                        <ReviewBadge
                                            status={
                                                suggestion.review_status
                                            }
                                        />

                                        {suggestion.reviewed_at && (
                                            <p className="mt-2 text-xs text-neutral-400">
                                                {formatDateTime(
                                                    suggestion.reviewed_at,
                                                )}
                                            </p>
                                        )}
                                    </td>
                                </tr>
                            ),
                        )}
                    </tbody>
                </table>
            </div>
        </div>
    )
}

function ReviewPolicy() {
    return (
        <section className="rounded-xl border border-neutral-200 bg-white p-5 shadow-sm dark:border-neutral-800 dark:bg-neutral-950">
            <div className="flex items-center gap-2">
                <ShieldCheck className="h-5 w-5 text-emerald-600" />

                <h2 className="font-semibold text-neutral-950 dark:text-neutral-100">
                    Review policy
                </h2>
            </div>

            <div className="mt-4 space-y-3 text-sm leading-6 text-neutral-600 dark:text-neutral-300">
                <p>
                    Same-decision repeats are idempotent and do not append a
                    duplicate review row.
                </p>

                <p>
                    Decision changes append new review history and preserve the
                    previous decision.
                </p>

                <p>
                    Stale suggestions can be rejected but not approved.
                </p>

                <p>
                    K.3 contains no live SEO application path.
                </p>
            </div>
        </section>
    )
}

function RecentBatches({
    batches,
}: {
    batches: RecentBatch[]
}) {
    return (
        <section className="rounded-xl border border-neutral-200 bg-white p-5 shadow-sm dark:border-neutral-800 dark:bg-neutral-950">
            <div className="flex items-center gap-2">
                <FileCheck2 className="h-5 w-5 text-neutral-600 dark:text-neutral-300" />

                <h2 className="font-semibold text-neutral-950 dark:text-neutral-100">
                    Recent review batches
                </h2>
            </div>

            {batches.length ===
            0 ? (
                <p className="mt-4 text-sm text-neutral-500 dark:text-neutral-400">
                    No bulk review batches yet.
                </p>
            ) : (
                <div className="mt-4 space-y-3">
                    {batches.map(
                        (
                            batch,
                        ) => (
                            <div
                                key={
                                    batch.id
                                }
                                className="rounded-lg border border-neutral-200 p-3 dark:border-neutral-800"
                            >
                                <div className="flex items-center justify-between gap-3">
                                    <span className="text-xs font-semibold capitalize text-neutral-900 dark:text-neutral-100">
                                        {batch.decision}
                                    </span>

                                    <span className="text-[11px] font-semibold uppercase tracking-wide text-neutral-500">
                                        {batch.status.replaceAll(
                                            '_',
                                            ' ',
                                        )}
                                    </span>
                                </div>

                                <p className="mt-2 text-xs leading-5 text-neutral-500 dark:text-neutral-400">
                                    {batch.summary.approved ?? 0} approved • {batch.summary.rejected ?? 0} rejected • {batch.summary.blocked_stale ?? 0} stale blocked • {batch.summary.failed ?? 0} failed
                                </p>

                                <p className="mt-1 text-[11px] text-neutral-400">
                                    {formatDateTime(
                                        batch.reviewed_at,
                                    )}
                                </p>
                            </div>
                        ),
                    )}
                </div>
            )}
        </section>
    )
}

function Value({
    value,
}: {
    value: string | null
}) {
    return (
        <p className="max-w-xs whitespace-pre-wrap break-words leading-6 text-neutral-600 dark:text-neutral-300">
            {value ?? '—'}
        </p>
    )
}

function ReviewBadge({
    status,
}: {
    status: Suggestion['review_status']
}) {
    const Icon =
        status ===
        'approved'
            ? CheckCircle2
            : status ===
                'rejected'
              ? XCircle
              : Clock3

    return (
        <span className="inline-flex items-center gap-1.5 rounded-full border border-neutral-200 bg-neutral-50 px-2.5 py-1 text-xs font-semibold capitalize text-neutral-700 dark:border-neutral-700 dark:bg-neutral-900 dark:text-neutral-200">
            <Icon className="h-3.5 w-3.5" />
            {status}
        </span>
    )
}

function FreshnessBadge({
    stale,
    state,
}: {
    stale: boolean
    state: string
}) {
    return stale ? (
        <span className="inline-flex items-center gap-1 rounded-full border border-amber-200 bg-amber-50 px-2 py-1 text-[11px] font-semibold capitalize text-amber-700 dark:border-amber-900/60 dark:bg-amber-950/20 dark:text-amber-300">
            <RefreshCw className="h-3 w-3" />
            {state.replaceAll(
                '_',
                ' ',
            )}
        </span>
    ) : (
        <span className="inline-flex items-center gap-1 rounded-full border border-emerald-200 bg-emerald-50 px-2 py-1 text-[11px] font-semibold text-emerald-700 dark:border-emerald-900/60 dark:bg-emerald-950/20 dark:text-emerald-300">
            <CheckCircle2 className="h-3 w-3" />
            Fresh
        </span>
    )
}

function formatDateTime(
    value: string | null,
) {
    if (!value) {
        return '—'
    }

    const date =
        new Date(value)

    if (
        Number.isNaN(
            date.getTime(),
        )
    ) {
        return '—'
    }

    return new Intl.DateTimeFormat(
        undefined,
        {
            year:
                'numeric',
            month:
                'short',
            day:
                'numeric',
            hour:
                'numeric',
            minute:
                '2-digit',
        },
    ).format(date)
}

const secondaryButton =
    'inline-flex h-9 items-center rounded-lg border border-neutral-300 px-3 text-xs font-semibold text-neutral-700 hover:bg-neutral-50 dark:border-neutral-700 dark:text-neutral-200 dark:hover:bg-neutral-900'
