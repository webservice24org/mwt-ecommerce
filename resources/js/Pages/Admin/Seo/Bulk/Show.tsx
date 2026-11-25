import AdminLayout from '@/Layouts/Admin/AdminLayout'
import { Head, Link, router } from '@inertiajs/react'
import {
    AlertTriangle,
    ArrowLeft,
    Bot,
    FileCheck2,
    CheckCircle2,
    Clock3,
    DatabaseZap,
    Play,
    RefreshCw,
    RotateCcw,
    ShieldCheck,
    SkipForward,
    Sparkles,
    XCircle,
} from 'lucide-react'

type ExecutionSummary = {
    total: number
    planned: number
    queued: number
    processing: number
    completed: number
    skipped: number
    drifted: number
    failed: number
}

type PlanItem = {
    id: number
    position: number
    entity_type: string
    entity_id: number
    entity_name: string
    entity_slug: string
    state: string
    planned_action: string
    execution_status: string
    attempt_count: number
    ai_seo_request_id: number | null
    reason: string
    execution_error_code: string | null
    execution_error_message: string | null
    queued_at: string | null
    started_at: string | null
    last_attempt_at: string | null
    completed_at: string | null
}

type Props = {
    plan: {
        id: number
        reference: string
        status: string
        plan_hash: string
        entity_types: string[]
        filters: Record<string, unknown>
        plan_summary: {
            total?: number
            generate?: number
            skip?: number
            ready?: number
            stale?: number
            fresh?: number
        }
        execution_summary: ExecutionSummary
        planned_by_admin_id: number | null
        planned_at: string
        started_at: string | null
        last_dispatched_at: string | null
        completed_at: string | null
        actions: {
            can_execute: boolean
            can_retry_failed: boolean
            can_apply: boolean
        }
        queue: {
            name: string
            max_attempts: number
        }
        items: PlanItem[]
    }
}

export default function Show({
    plan,
}: Props) {
    const execute = () => {
        router.post(
            route(
                'admin.seo.bulk.plans.execute',
                {
                    bulkJobId:
                        plan.id,
                },
            ),
            {},
            {
                preserveScroll: true,
            },
        )
    }

    const retryFailed = () => {
        router.post(
            route(
                'admin.seo.bulk.plans.retry-failed',
                {
                    bulkJobId:
                        plan.id,
                },
            ),
            {},
            {
                preserveScroll: true,
            },
        )
    }

    const refresh = () => {
        router.reload({
            only: [
                'plan',
            ],
        })
    }

    return (
        <AdminLayout title="">
            <Head
                title={`Bulk AI — ${plan.reference}`}
            />

            <div className="space-y-6">
                <Header
                    plan={plan}
                    onExecute={
                        execute
                    }
                    onRetryFailed={
                        retryFailed
                    }
                    onRefresh={
                        refresh
                    }
                />

                <ExecutionNotice
                    plan={plan}
                />

                <SummaryGrid
                    plan={plan}
                />

                <div className="grid gap-6 xl:grid-cols-[minmax(0,1fr)_330px]">
                    <main className="min-w-0">
                        <ItemsTable
                            items={
                                plan.items
                            }
                        />
                    </main>

                    <aside className="space-y-6 xl:sticky xl:top-6 xl:self-start">
                        <QueueCard
                            plan={plan}
                        />

                        <PlanIntegrityCard
                            plan={plan}
                        />

                        <WorkspaceLinks />
                    </aside>
                </div>
            </div>
        </AdminLayout>
    )
}

function Header({
    plan,
    onExecute,
    onRetryFailed,
    onRefresh,
}: {
    plan: Props['plan']
    onExecute: () => void
    onRetryFailed: () => void
    onRefresh: () => void
}) {
    return (
        <div className="flex flex-col gap-5 xl:flex-row xl:items-start xl:justify-between">
            <div>
                <Link
                    href={route(
                        'admin.seo.bulk.index',
                    )}
                    className="inline-flex items-center gap-2 text-sm font-medium text-indigo-600 hover:text-indigo-700 dark:text-indigo-400"
                >
                    <ArrowLeft className="h-4 w-4" />
                    Bulk AI Optimization
                </Link>

                <div className="mt-3 flex flex-wrap items-center gap-2">
                    <JobStatusBadge
                        status={
                            plan.status
                        }
                    />

                    <span className="rounded-full border border-neutral-200 bg-neutral-50 px-2.5 py-1 text-xs font-semibold text-neutral-700 dark:border-neutral-700 dark:bg-neutral-900 dark:text-neutral-200">
                        {plan.entity_types.join(
                            ', ',
                        )}
                    </span>
                </div>

                <h1 className="mt-3 text-2xl font-bold tracking-tight text-neutral-950 dark:text-neutral-100">
                    {plan.reference}
                </h1>

                <p className="mt-2 max-w-3xl text-sm leading-6 text-neutral-500 dark:text-neutral-400">
                    Persisted K.1 plan with queued K.2 item execution,
                    retries, request traceability and snapshot-drift
                    protection.
                </p>
            </div>

            <div className="flex flex-wrap gap-2">
                <Link
                    href={route(
                        'admin.seo.bulk.plans.review',
                        {
                            bulkJobId:
                                plan.id,
                        },
                    )}
                    className="inline-flex h-10 items-center gap-2 rounded-lg border border-indigo-200 bg-indigo-50 px-4 text-sm font-semibold text-indigo-700 hover:bg-indigo-100 dark:border-indigo-900/60 dark:bg-indigo-950/20 dark:text-indigo-300"
                >
                    <Sparkles className="h-4 w-4" />
                    Review suggestions
                </Link>

                {plan.actions
                    .can_apply && (
                    <Link
                        href={route(
                            'admin.seo.bulk.plans.apply',
                            {
                                bulkJobId:
                                    plan.id,
                            },
                        )}
                        className="inline-flex h-10 items-center gap-2 rounded-lg border border-emerald-200 bg-emerald-50 px-4 text-sm font-semibold text-emerald-700 hover:bg-emerald-100 dark:border-emerald-900/60 dark:bg-emerald-950/20 dark:text-emerald-300"
                    >
                        <FileCheck2 className="h-4 w-4" />
                        Apply approved
                    </Link>
                )}

                <button
                    type="button"
                    onClick={onRefresh}
                    className="inline-flex h-10 items-center gap-2 rounded-lg border border-neutral-300 bg-white px-4 text-sm font-semibold text-neutral-700 hover:bg-neutral-50 dark:border-neutral-700 dark:bg-neutral-950 dark:text-neutral-200 dark:hover:bg-neutral-900"
                >
                    <RefreshCw className="h-4 w-4" />
                    Refresh
                </button>

                {plan.actions
                    .can_retry_failed && (
                    <button
                        type="button"
                        onClick={
                            onRetryFailed
                        }
                        className="inline-flex h-10 items-center gap-2 rounded-lg border border-amber-300 bg-amber-50 px-4 text-sm font-semibold text-amber-800 hover:bg-amber-100 dark:border-amber-900/60 dark:bg-amber-950/20 dark:text-amber-300"
                    >
                        <RotateCcw className="h-4 w-4" />
                        Retry failed
                    </button>
                )}

                {plan.actions
                    .can_execute && (
                    <button
                        type="button"
                        onClick={
                            onExecute
                        }
                        className="inline-flex h-10 items-center gap-2 rounded-lg bg-neutral-950 px-4 text-sm font-semibold text-white hover:bg-neutral-800 dark:bg-white dark:text-neutral-950 dark:hover:bg-neutral-200"
                    >
                        <Play className="h-4 w-4" />
                        Queue execution
                    </button>
                )}
            </div>
        </div>
    )
}

function ExecutionNotice({
    plan,
}: {
    plan: Props['plan']
}) {
    return (
        <div className="rounded-xl border border-violet-200 bg-violet-50/70 p-4 dark:border-violet-900/60 dark:bg-violet-950/20">
            <div className="flex items-start gap-3">
                <ShieldCheck className="mt-0.5 h-5 w-5 shrink-0 text-violet-600 dark:text-violet-400" />

                <div>
                    <p className="text-sm font-semibold text-violet-950 dark:text-violet-100">
                        Execution is generation-only
                    </p>

                    <p className="mt-1 text-sm leading-6 text-violet-800/80 dark:text-violet-300/80">
                        K.2 may create H.1 AI requests and draft metadata
                        suggestions, but it cannot approve or apply them.
                        Each worker revalidates the immutable plan snapshot
                        before provider execution. Drifted entities require a
                        new plan.
                    </p>

                    <p className="mt-2 text-xs font-medium text-violet-700 dark:text-violet-300">
                        Queue: {plan.queue.name} • maximum automatic
                        attempts: {plan.queue.max_attempts}
                    </p>
                </div>
            </div>
        </div>
    )
}

function SummaryGrid({
    plan,
}: {
    plan: Props['plan']
}) {
    const execution =
        plan.execution_summary

    const cards = [
        {
            label:
                'Plan items',
            value:
                execution.total,
            note: `${plan.plan_summary.generate ?? 0} planned generation • ${plan.plan_summary.skip ?? 0} planned skip`,
            icon:
                DatabaseZap,
        },
        {
            label:
                'Queued / processing',
            value:
                execution.queued +
                execution.processing,
            note: `${execution.queued} queued • ${execution.processing} processing`,
            icon:
                Clock3,
        },
        {
            label:
                'Completed',
            value:
                execution.completed,
            note: `${execution.skipped} skipped • ${execution.drifted} drifted`,
            icon:
                CheckCircle2,
        },
        {
            label:
                'Failed',
            value:
                execution.failed,
            note:
                execution.failed >
                0
                    ? 'Failed items can be retried independently'
                    : 'No failed items',
            icon:
                XCircle,
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

function ItemsTable({
    items,
}: {
    items: PlanItem[]
}) {
    return (
        <div className="overflow-hidden rounded-xl border border-neutral-200 bg-white shadow-sm dark:border-neutral-800 dark:bg-neutral-950">
            <div className="border-b border-neutral-200 px-5 py-4 dark:border-neutral-800">
                <h2 className="font-semibold text-neutral-950 dark:text-neutral-100">
                    Execution items
                </h2>

                <p className="mt-1 text-sm text-neutral-500 dark:text-neutral-400">
                    Item-level queue state and H.1 request traceability.
                </p>
            </div>

            <div className="overflow-x-auto">
                <table className="min-w-[1100px] w-full divide-y divide-neutral-200 text-sm dark:divide-neutral-800">
                    <thead>
                        <tr className="text-left text-xs uppercase tracking-wide text-neutral-500 dark:text-neutral-400">
                            <th className="px-5 py-3 font-medium">
                                Entity
                            </th>
                            <th className="px-5 py-3 font-medium">
                                Plan
                            </th>
                            <th className="px-5 py-3 font-medium">
                                Execution
                            </th>
                            <th className="px-5 py-3 font-medium">
                                Attempts
                            </th>
                            <th className="px-5 py-3 font-medium">
                                AI request
                            </th>
                            <th className="px-5 py-3 font-medium">
                                Last activity
                            </th>
                        </tr>
                    </thead>

                    <tbody className="divide-y divide-neutral-100 dark:divide-neutral-900">
                        {items.map(
                            (
                                item,
                            ) => (
                                <tr
                                    key={
                                        item.id
                                    }
                                >
                                    <td className="px-5 py-4 align-top">
                                        <Link
                                            href={route(
                                                'admin.seo.entities.show',
                                                {
                                                    entityType:
                                                        item.entity_type,
                                                    entityId:
                                                        item.entity_id,
                                                },
                                            )}
                                            className="font-semibold text-neutral-900 hover:text-indigo-600 dark:text-neutral-100 dark:hover:text-indigo-400"
                                        >
                                            {item.entity_name}
                                        </Link>

                                        <p className="mt-1 text-xs text-neutral-500 dark:text-neutral-400">
                                            {item.entity_type} #{item.entity_id}
                                        </p>

                                        {(item.execution_error_code ||
                                            item.execution_error_message) && (
                                            <div className="mt-2 max-w-xl rounded-lg border border-amber-200 bg-amber-50 p-2.5 text-xs leading-5 text-amber-800 dark:border-amber-900/50 dark:bg-amber-950/20 dark:text-amber-300">
                                                <p className="font-semibold">
                                                    {item.execution_error_code ??
                                                        'execution_note'}
                                                </p>

                                                {item.execution_error_message && (
                                                    <p className="mt-1">
                                                        {
                                                            item.execution_error_message
                                                        }
                                                    </p>
                                                )}
                                            </div>
                                        )}
                                    </td>

                                    <td className="px-5 py-4 align-top">
                                        <div className="space-y-2">
                                            <StateBadge
                                                value={
                                                    item.state
                                                }
                                            />

                                            <p className="text-xs font-semibold uppercase tracking-wide text-neutral-500">
                                                {
                                                    item.planned_action
                                                }
                                            </p>
                                        </div>
                                    </td>

                                    <td className="px-5 py-4 align-top">
                                        <ExecutionBadge
                                            status={
                                                item.execution_status
                                            }
                                        />
                                    </td>

                                    <td className="px-5 py-4 align-top font-semibold text-neutral-900 dark:text-neutral-100">
                                        {item.attempt_count}
                                    </td>

                                    <td className="px-5 py-4 align-top text-neutral-600 dark:text-neutral-300">
                                        {item.ai_seo_request_id
                                            ? `#${item.ai_seo_request_id}`
                                            : '—'}
                                    </td>

                                    <td className="px-5 py-4 align-top text-xs leading-5 text-neutral-500 dark:text-neutral-400">
                                        {formatDateTime(
                                            item.completed_at ??
                                                item.last_attempt_at ??
                                                item.queued_at,
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

function QueueCard({
    plan,
}: {
    plan: Props['plan']
}) {
    return (
        <section className="rounded-xl border border-neutral-200 bg-white p-5 shadow-sm dark:border-neutral-800 dark:bg-neutral-950">
            <div className="flex items-center gap-2">
                <Bot className="h-5 w-5 text-neutral-600 dark:text-neutral-300" />

                <h2 className="font-semibold text-neutral-950 dark:text-neutral-100">
                    Queue worker
                </h2>
            </div>

            <p className="mt-3 text-sm leading-6 text-neutral-500 dark:text-neutral-400">
                Production workers must consume the dedicated queue:
            </p>

            <code className="mt-3 block overflow-x-auto rounded-lg bg-neutral-950 p-3 text-xs text-neutral-100">
                php artisan queue:work --queue={plan.queue.name}
            </code>

            <div className="mt-4 space-y-2 text-xs text-neutral-500 dark:text-neutral-400">
                <p>
                    Started: {formatDateTime(plan.started_at)}
                </p>

                <p>
                    Last dispatch:{' '}
                    {formatDateTime(plan.last_dispatched_at)}
                </p>

                <p>
                    Completed: {formatDateTime(plan.completed_at)}
                </p>
            </div>
        </section>
    )
}

function PlanIntegrityCard({
    plan,
}: {
    plan: Props['plan']
}) {
    return (
        <section className="rounded-xl border border-indigo-200 bg-indigo-50/60 p-5 dark:border-indigo-900/60 dark:bg-indigo-950/20">
            <div className="flex items-center gap-2">
                <ShieldCheck className="h-5 w-5 text-indigo-600 dark:text-indigo-400" />

                <h2 className="font-semibold text-indigo-950 dark:text-indigo-100">
                    Plan integrity
                </h2>
            </div>

            <p className="mt-3 break-all text-xs leading-5 text-indigo-800/80 dark:text-indigo-300/80">
                SHA-256: {plan.plan_hash}
            </p>

            <div className="mt-4 space-y-2 text-sm leading-6 text-indigo-900/80 dark:text-indigo-200/80">
                <p>
                    Drifted items are never regenerated from this plan.
                </p>

                <p>
                    Completed requests are reconciled idempotently.
                </p>

                <p>
                    Live SEO application remains unavailable in K.2.
                </p>
            </div>
        </section>
    )
}

function WorkspaceLinks() {
    return (
        <section className="rounded-xl border border-neutral-200 bg-white p-5 shadow-sm dark:border-neutral-800 dark:bg-neutral-950">
            <p className="font-semibold text-neutral-950 dark:text-neutral-100">
                Workspace
            </p>

            <div className="mt-3 space-y-2 text-sm">
                <Link
                    href={route(
                        'admin.seo.bulk.index',
                    )}
                    className="block font-medium text-indigo-600 hover:text-indigo-700 dark:text-indigo-400"
                >
                    Bulk AI Optimization
                </Link>

                <Link
                    href={route(
                        'admin.seo.suggestions.index',
                    )}
                    className="block font-medium text-indigo-600 hover:text-indigo-700 dark:text-indigo-400"
                >
                    AI Suggestions Inbox
                </Link>

                <Link
                    href={route(
                        'admin.seo.overview',
                    )}
                    className="block font-medium text-indigo-600 hover:text-indigo-700 dark:text-indigo-400"
                >
                    SEO Overview
                </Link>
            </div>
        </section>
    )
}

function JobStatusBadge({
    status,
}: {
    status: string
}) {
    return (
        <span className="inline-flex items-center gap-1.5 rounded-full border border-neutral-200 bg-neutral-50 px-2.5 py-1 text-xs font-semibold capitalize text-neutral-700 dark:border-neutral-700 dark:bg-neutral-900 dark:text-neutral-200">
            {status ===
            'completed' ? (
                <CheckCircle2 className="h-3.5 w-3.5" />
            ) : status ===
              'completed_with_errors' ? (
                <AlertTriangle className="h-3.5 w-3.5" />
            ) : status ===
              'running' ? (
                <RefreshCw className="h-3.5 w-3.5" />
            ) : (
                <Clock3 className="h-3.5 w-3.5" />
            )}

            {status.replaceAll(
                '_',
                ' ',
            )}
        </span>
    )
}

function ExecutionBadge({
    status,
}: {
    status: string
}) {
    const Icon =
        status ===
        'completed'
            ? CheckCircle2
            : status ===
                'failed'
              ? XCircle
              : status ===
                  'drifted'
                ? AlertTriangle
                : status ===
                    'skipped'
                  ? SkipForward
                  : status ===
                      'processing'
                    ? RefreshCw
                    : Clock3

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

function StateBadge({
    value,
}: {
    value: string
}) {
    return (
        <span className="inline-flex items-center gap-1 rounded-full border border-neutral-200 bg-neutral-50 px-2 py-1 text-[11px] font-semibold capitalize text-neutral-700 dark:border-neutral-700 dark:bg-neutral-900 dark:text-neutral-200">
            <Sparkles className="h-3 w-3" />
            {value}
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
