import ConfirmDialog from '@/Components/Admin/ConfirmDialog'
import AdminLayout from '@/Layouts/Admin/AdminLayout'
import { Head, Link, router } from '@inertiajs/react'
import {
    AlertTriangle,
    ArrowLeft,
    CheckCircle2,
    FileCheck2,
    History,
    RefreshCw,
    ShieldCheck,
    Sparkles,
    XCircle,
} from 'lucide-react'
import {
    useMemo,
    useState,
} from 'react'

type ApplicationStatus =
    | 'ready'
    | 'already_applied'
    | 'conflict'
    | 'stale'
    | 'not_approved'

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
    suggested_value: string
    rationale: string | null
    review_status: string
    application_status: ApplicationStatus
    can_apply: boolean
    baseline_value: string | null
    current_value: string | null
    source_snapshot_hash: string | null
    current_snapshot_hash: string | null
    application_id: number | null
    applied_at: string | null
    reason: string
}

type RecentBatch = {
    id: number
    status: string
    summary: {
        total?: number
        applied?: number
        already_applied?: number
        conflict?: number
        stale?: number
        not_approved?: number
        failed?: number
    }
    applied_by_admin_id: number | null
    note: string | null
    applied_at: string
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
            ready: number
            already_applied: number
            conflict: number
            stale: number
            not_approved: number
        }
        suggestions: Suggestion[]
        recent_batches: RecentBatch[]
    }
}

export default function Apply({
    workspace,
}: Props) {
    const [selected, setSelected] =
        useState<Set<number>>(
            new Set(),
        )

    const [note, setNote] =
        useState('')

    const [
        confirmOpen,
        setConfirmOpen,
    ] = useState(false)

    const [applying, setApplying] =
        useState(false)

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

    const selectedReady =
        selectedRows.filter(
            (
                suggestion,
            ) =>
                suggestion.can_apply,
        ).length

    const toggle = (
        suggestion: Suggestion,
    ) => {
        if (
            !suggestion.can_apply
            || applying
        ) {
            return
        }

        setSelected(
            (
                current,
            ) => {
                const next =
                    new Set(
                        current,
                    )

                if (
                    next.has(
                        suggestion.id,
                    )
                ) {
                    next.delete(
                        suggestion.id,
                    )
                } else {
                    next.add(
                        suggestion.id,
                    )
                }

                return next
            },
        )
    }

    const selectReady = () => {
        if (applying) {
            return
        }

        setSelected(
            new Set(
                workspace.suggestions
                    .filter(
                        (
                            suggestion,
                        ) =>
                            suggestion.can_apply,
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
        if (applying) {
            return
        }

        setSelected(
            new Set(),
        )
    }

    const requestApply = () => {
        if (
            selectedReady ===
                0
            || applying
        ) {
            return
        }

        setConfirmOpen(
            true,
        )
    }

    const applySelected = () => {
        if (
            selectedReady ===
                0
            || applying
        ) {
            return
        }

        setConfirmOpen(
            false,
        )

        setApplying(
            true,
        )

        router.post(
            route(
                'admin.seo.bulk.plans.apply.store',
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
                note:
                    note.trim() ||
                    null,
            },
            {
                preserveScroll: true,

                onSuccess: () => {
                    setSelected(
                        new Set(),
                    )
                    setNote('')
                },

                onFinish: () => {
                    setApplying(
                        false,
                    )
                },
            },
        )
    }

    return (
        <AdminLayout title="">
            <Head
                title={`Apply AI SEO — ${workspace.plan.reference}`}
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
                        <ApplyToolbar
                            selected={
                                selected.size
                            }
                            selectedReady={
                                selectedReady
                            }
                            note={note}
                            setNote={
                                setNote
                            }
                            applying={
                                applying
                            }
                            onSelectReady={
                                selectReady
                            }
                            onClear={
                                clear
                            }
                            onApply={
                                requestApply
                            }
                        />

                        <SuggestionTable
                            suggestions={
                                workspace.suggestions
                            }
                            selected={
                                selected
                            }
                            applying={
                                applying
                            }
                            onToggle={
                                toggle
                            }
                        />
                    </main>

                    <aside className="space-y-6 xl:sticky xl:top-6 xl:self-start">
                        <ApplicationPolicy />

                        <RecentBatches
                            batches={
                                workspace.recent_batches
                            }
                        />

                        <PlanIntegrity
                            hash={
                                workspace.plan
                                    .plan_hash
                            }
                        />
                    </aside>
                </div>
            </div>

            <ConfirmDialog
                open={
                    confirmOpen
                }
                title="Apply approved AI SEO?"
                description={`This will write ${selectedReady} approved, fresh, conflict-free metadata field${selectedReady === 1 ? '' : 's'} to live SEO. Each field is revalidated transactionally before it is changed. Conflicts and stale suggestions are never force-overwritten.`}
                confirmLabel="Apply SEO"
                cancelLabel="Cancel"
                onConfirm={
                    applySelected
                }
                onCancel={() =>
                    setConfirmOpen(
                        false,
                    )
                }
            />
        </AdminLayout>
    )
}

function Header({
    plan,
}: {
    plan: Props['workspace']['plan']
}) {
    return (
        <div className="flex flex-col gap-4 lg:flex-row lg:items-start lg:justify-between">
            <div>
                <Link
                    href={route(
                        'admin.seo.bulk.plans.review',
                        {
                            bulkJobId:
                                plan.id,
                        },
                    )}
                    className="inline-flex items-center gap-2 text-sm font-medium text-indigo-600 hover:text-indigo-700 dark:text-indigo-400"
                >
                    <ArrowLeft className="h-4 w-4" />
                    Back to Bulk Review
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

                <h1 className="mt-3 text-2xl font-bold tracking-tight text-neutral-950 dark:text-neutral-100">
                    Guarded Bulk Application
                </h1>

                <p className="mt-2 max-w-3xl text-sm leading-6 text-neutral-500 dark:text-neutral-400">
                    Apply only approved metadata suggestions that still
                    match their generation baseline. Manual edits and
                    stale entities are preserved instead of overwritten.
                </p>
            </div>

            <Link
                href={route(
                    'admin.seo.bulk.plans.show',
                    {
                        bulkJobId:
                            plan.id,
                    },
                )}
                className="inline-flex h-10 items-center justify-center rounded-lg border border-neutral-300 bg-white px-4 text-sm font-semibold text-neutral-700 hover:bg-neutral-50 dark:border-neutral-700 dark:bg-neutral-950 dark:text-neutral-200 dark:hover:bg-neutral-900"
            >
                Execution plan
            </Link>
        </div>
    )
}

function SafetyNotice() {
    return (
        <div className="rounded-xl border border-red-200 bg-red-50/70 p-4 dark:border-red-900/60 dark:bg-red-950/20">
            <div className="flex items-start gap-3">
                <ShieldCheck className="mt-0.5 h-5 w-5 shrink-0 text-red-600 dark:text-red-400" />

                <div>
                    <p className="text-sm font-semibold text-red-950 dark:text-red-100">
                        Live SEO write boundary
                    </p>

                    <p className="mt-1 text-sm leading-6 text-red-800/80 dark:text-red-300/80">
                        K.4 can change only six approved text metadata
                        fields. Canonical URLs, robots, social images,
                        structured data and business/catalog values are
                        never part of this operation. There is no force
                        overwrite path for conflicts.
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
                'Ready to apply',
            value:
                summary.ready,
            note:
                'Approved + fresh + conflict-free',
            icon:
                FileCheck2,
        },
        {
            label:
                'Already applied',
            value:
                summary.already_applied,
            note:
                'Protected by unique application ledger',
            icon:
                CheckCircle2,
        },
        {
            label:
                'Conflicts',
            value:
                summary.conflict,
            note:
                'Live field changed after generation',
            icon:
                AlertTriangle,
        },
        {
            label:
                'Stale / not approved',
            value:
                summary.stale +
                summary.not_approved,
            note: `${summary.stale} stale • ${summary.not_approved} not approved`,
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

function ApplyToolbar({
    selected,
    selectedReady,
    note,
    setNote,
    applying,
    onSelectReady,
    onClear,
    onApply,
}: {
    selected: number
    selectedReady: number
    note: string
    setNote: (
        value: string,
    ) => void
    applying: boolean
    onSelectReady: () => void
    onClear: () => void
    onApply: () => void
}) {
    return (
        <section className="rounded-xl border border-neutral-200 bg-white p-4 shadow-sm dark:border-neutral-800 dark:bg-neutral-950">
            <div className="flex flex-col gap-4 xl:flex-row xl:items-end xl:justify-between">
                <div className="min-w-0 flex-1">
                    <p className="text-sm font-semibold text-neutral-900 dark:text-neutral-100">
                        {selected} selected • {selectedReady} ready
                    </p>

                    <label className="mt-3 block">
                        <span className="text-xs font-medium text-neutral-500 dark:text-neutral-400">
                            Application note (optional)
                        </span>

                        <textarea
                            value={note}
                            disabled={
                                applying
                            }
                            onChange={(
                                event,
                            ) =>
                                setNote(
                                    event
                                        .target
                                        .value,
                                )
                            }
                            maxLength={
                                1000
                            }
                            rows={2}
                            placeholder="Why these approved suggestions are being applied..."
                            className="mt-1.5 block w-full rounded-lg border border-neutral-300 bg-white px-3 py-2 text-sm text-neutral-900 outline-none focus:border-indigo-500 focus:ring-2 focus:ring-indigo-500/20 disabled:opacity-60 dark:border-neutral-700 dark:bg-neutral-950 dark:text-neutral-100"
                        />
                    </label>
                </div>

                <div className="flex flex-wrap gap-2">
                    <button
                        type="button"
                        disabled={
                            applying
                        }
                        onClick={
                            onSelectReady
                        }
                        className="inline-flex h-9 items-center gap-2 rounded-lg border border-neutral-300 px-3 text-xs font-semibold text-neutral-700 hover:bg-neutral-50 disabled:opacity-50 dark:border-neutral-700 dark:text-neutral-200 dark:hover:bg-neutral-900"
                    >
                        <CheckCircle2 className="h-3.5 w-3.5" />
                        Select ready
                    </button>

                    <button
                        type="button"
                        disabled={
                            applying
                        }
                        onClick={
                            onClear
                        }
                        className="inline-flex h-9 items-center rounded-lg border border-neutral-300 px-3 text-xs font-semibold text-neutral-700 hover:bg-neutral-50 disabled:opacity-50 dark:border-neutral-700 dark:text-neutral-200 dark:hover:bg-neutral-900"
                    >
                        Clear
                    </button>

                    <button
                        type="button"
                        disabled={
                            selectedReady ===
                                0 ||
                            applying
                        }
                        onClick={
                            onApply
                        }
                        className="inline-flex h-9 items-center gap-2 rounded-lg bg-red-600 px-3 text-xs font-semibold text-white hover:bg-red-700 disabled:cursor-not-allowed disabled:opacity-40"
                    >
                        <FileCheck2 className="h-3.5 w-3.5" />
                        {applying
                            ? 'Applying...'
                            : 'Apply selected'}
                    </button>
                </div>
            </div>
        </section>
    )
}

function SuggestionTable({
    suggestions,
    selected,
    applying,
    onToggle,
}: {
    suggestions: Suggestion[]
    selected: Set<number>
    applying: boolean
    onToggle: (
        suggestion: Suggestion,
    ) => void
}) {
    if (
        suggestions.length ===
        0
    ) {
        return (
            <div className="rounded-xl border border-dashed border-neutral-300 bg-white p-10 text-center text-sm text-neutral-500 dark:border-neutral-700 dark:bg-neutral-950 dark:text-neutral-400">
                No generated suggestions are available for this bulk plan.
            </div>
        )
    }

    return (
        <div className="space-y-4">
            {suggestions.map(
                (
                    suggestion,
                ) => (
                    <article
                        key={
                            suggestion.id
                        }
                        className="rounded-xl border border-neutral-200 bg-white p-5 shadow-sm dark:border-neutral-800 dark:bg-neutral-950"
                    >
                        <div className="flex flex-col gap-4 sm:flex-row sm:items-start sm:justify-between">
                            <div className="flex min-w-0 items-start gap-3">
                                <input
                                    type="checkbox"
                                    disabled={
                                        !suggestion.can_apply ||
                                        applying
                                    }
                                    checked={
                                        selected.has(
                                            suggestion.id,
                                        )
                                    }
                                    onChange={() =>
                                        onToggle(
                                            suggestion,
                                        )
                                    }
                                    className="mt-1 h-4 w-4 rounded border-neutral-300 disabled:opacity-40"
                                />

                                <div className="min-w-0">
                                    <div className="flex flex-wrap items-center gap-2">
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
                                            className="font-semibold text-neutral-950 hover:text-indigo-600 dark:text-neutral-100 dark:hover:text-indigo-400"
                                        >
                                            {suggestion.entity_name}
                                        </Link>

                                        <StatusBadge
                                            status={
                                                suggestion.application_status
                                            }
                                        />

                                        <span className="rounded-full border border-neutral-200 bg-neutral-50 px-2 py-1 text-[11px] font-semibold text-neutral-600 dark:border-neutral-700 dark:bg-neutral-900 dark:text-neutral-300">
                                            {suggestion.field_label}
                                        </span>
                                    </div>

                                    <p className="mt-1 text-xs text-neutral-500 dark:text-neutral-400">
                                        {suggestion.entity_type} #{suggestion.entity_id} • request #{suggestion.request_id}
                                    </p>
                                </div>
                            </div>

                            <span className="text-xs font-semibold capitalize text-neutral-500 dark:text-neutral-400">
                                review: {suggestion.review_status}
                            </span>
                        </div>

                        <div className="mt-4 grid gap-3 xl:grid-cols-3">
                            <ValueCard
                                label="Generation baseline"
                                value={
                                    suggestion.baseline_value
                                }
                                muted
                            />

                            <ValueCard
                                label="Current live value"
                                value={
                                    suggestion.current_value
                                }
                                muted={
                                    suggestion.application_status !==
                                    'conflict'
                                }
                                warning={
                                    suggestion.application_status ===
                                    'conflict'
                                }
                            />

                            <ValueCard
                                label="Approved AI value"
                                value={
                                    suggestion.suggested_value
                                }
                            />
                        </div>

                        <p className="mt-3 text-sm leading-6 text-neutral-500 dark:text-neutral-400">
                            {suggestion.reason}
                        </p>

                        {suggestion.rationale && (
                            <p className="mt-2 text-xs leading-5 text-neutral-400">
                                AI rationale: {suggestion.rationale}
                            </p>
                        )}

                        {suggestion.applied_at && (
                            <p className="mt-3 text-xs font-medium text-emerald-700 dark:text-emerald-400">
                                Applied {formatDateTime(suggestion.applied_at)}
                            </p>
                        )}
                    </article>
                ),
            )}
        </div>
    )
}

function ValueCard({
    label,
    value,
    muted = false,
    warning = false,
}: {
    label: string
    value: string | null
    muted?: boolean
    warning?: boolean
}) {
    const classes =
        warning
            ? 'border-amber-300 bg-amber-50 dark:border-amber-900/60 dark:bg-amber-950/20'
            : muted
              ? 'border-neutral-200 bg-neutral-50 dark:border-neutral-800 dark:bg-neutral-900/60'
              : 'border-indigo-200 bg-indigo-50/50 dark:border-indigo-900/60 dark:bg-indigo-950/20'

    return (
        <div
            className={`rounded-xl border p-4 ${classes}`}
        >
            <p className="text-xs font-semibold uppercase tracking-wide text-neutral-500 dark:text-neutral-400">
                {label}
            </p>

            <p className="mt-2 whitespace-pre-wrap break-words text-sm leading-6 text-neutral-800 dark:text-neutral-200">
                {value ??
                    'No explicit value.'}
            </p>
        </div>
    )
}

function StatusBadge({
    status,
}: {
    status: ApplicationStatus
}) {
    const Icon =
        status ===
        'ready'
            ? FileCheck2
            : status ===
                'already_applied'
              ? CheckCircle2
              : status ===
                  'conflict'
                ? AlertTriangle
                : status ===
                    'stale'
                  ? RefreshCw
                  : XCircle

    return (
        <span className="inline-flex items-center gap-1.5 rounded-full border border-neutral-200 bg-neutral-50 px-2.5 py-1 text-xs font-semibold capitalize text-neutral-700 dark:border-neutral-700 dark:bg-neutral-900 dark:text-neutral-200">
            <Icon className="h-3.5 w-3.5" />
            {status.replaceAll(
                '_',
                ' ',
            )}
        </span>
    )
}

function ApplicationPolicy() {
    return (
        <section className="rounded-xl border border-neutral-200 bg-white p-5 shadow-sm dark:border-neutral-800 dark:bg-neutral-950">
            <div className="flex items-center gap-2">
                <ShieldCheck className="h-5 w-5 text-neutral-600 dark:text-neutral-300" />

                <h2 className="font-semibold text-neutral-950 dark:text-neutral-100">
                    Apply policy
                </h2>
            </div>

            <div className="mt-4 space-y-3 text-sm leading-6 text-neutral-600 dark:text-neutral-300">
                <p>
                    Latest review must still be approved.
                </p>

                <p>
                    Current field must equal the generation baseline.
                </p>

                <p>
                    Full H.2 snapshot must still be fresh.
                </p>

                <p>
                    Successful suggestions can be applied only once.
                </p>

                <p>
                    Conflicts have no force-overwrite action.
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
                <History className="h-5 w-5 text-neutral-600 dark:text-neutral-300" />

                <h2 className="font-semibold text-neutral-950 dark:text-neutral-100">
                    Recent applications
                </h2>
            </div>

            {batches.length ===
            0 ? (
                <p className="mt-4 text-sm text-neutral-500 dark:text-neutral-400">
                    No application batches yet.
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
                                        {batch.status.replaceAll(
                                            '_',
                                            ' ',
                                        )}
                                    </span>

                                    <span className="text-[11px] text-neutral-400">
                                        #{batch.id}
                                    </span>
                                </div>

                                <p className="mt-2 text-xs leading-5 text-neutral-500 dark:text-neutral-400">
                                    {batch.summary.applied ?? 0} applied • {batch.summary.already_applied ?? 0} already • {batch.summary.conflict ?? 0} conflict • {batch.summary.stale ?? 0} stale • {batch.summary.failed ?? 0} failed
                                </p>

                                {batch.note && (
                                    <p className="mt-2 text-xs leading-5 text-neutral-500 dark:text-neutral-400">
                                        {batch.note}
                                    </p>
                                )}

                                <p className="mt-2 text-[11px] text-neutral-400">
                                    {formatDateTime(
                                        batch.applied_at,
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

function PlanIntegrity({
    hash,
}: {
    hash: string
}) {
    return (
        <section className="rounded-xl border border-indigo-200 bg-indigo-50/60 p-5 dark:border-indigo-900/60 dark:bg-indigo-950/20">
            <p className="text-sm font-semibold text-indigo-950 dark:text-indigo-100">
                Immutable plan
            </p>

            <p className="mt-3 break-all text-xs leading-5 text-indigo-800/80 dark:text-indigo-300/80">
                SHA-256: {hash}
            </p>
        </section>
    )
}

function formatDateTime(
    value: string | null,
) {
    if (!value) {
        return '—'
    }

    const date =
        new Date(
            value,
        )

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
    ).format(
        date,
    )
}
