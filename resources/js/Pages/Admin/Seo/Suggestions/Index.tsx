import AdminLayout from '@/Layouts/Admin/AdminLayout'
import { Head, Link, router } from '@inertiajs/react'
import {
    AlertTriangle,
    Bot,
    Check,
    CheckCircle2,
    Clock3,
    Filter,
    RefreshCw,
    Search,
    ShieldCheck,
    Sparkles,
    ThumbsDown,
    ThumbsUp,
    XCircle,
} from 'lucide-react'
import {
    FormEvent,
    useState,
} from 'react'

type Option = {
    value: string
    label: string
}

type Freshness = {
    is_stale: boolean
    state: string
    source_snapshot_hash: string | null
    current_snapshot_hash: string | null
}

type SuggestionItem = {
    id: number
    request_id: number
    entity_type: string
    entity_type_label: string
    entity_id: number
    entity_name: string
    entity_slug: string | null
    field: string
    field_label: string
    existing_value: string | null
    suggested_value: string
    rationale: string | null
    review_status: string
    reviewed_at: string | null
    review_note: string | null
    freshness: Freshness
    can_approve: boolean
    can_reject: boolean
    provider: string | null
    model_name: string | null
    prompt_version: string | null
    generated_at: string | null
    requested_by_admin_id: number | null
}

type Props = {
    inbox: {
        summary: {
            total: number
            pending: number
            approved: number
            rejected: number
            stale_on_page: number
        }
        items: SuggestionItem[]
        pagination: {
            current_page: number
            last_page: number
            per_page: number
            total: number
            from: number | null
            to: number | null
            previous_page_url: string | null
            next_page_url: string | null
        }
        filters: {
            q: string
            entity_type: string
            field: string
            provider: string
            review_status: string
        }
        options: {
            entity_types: Option[]
            fields: Option[]
            providers: Option[]
            review_statuses: Option[]
        }
    }
}

export default function Index({
    inbox,
}: Props) {
    const [q, setQ] =
        useState(
            inbox.filters.q,
        )

    const [
        entityType,
        setEntityType,
    ] = useState(
        inbox.filters.entity_type,
    )

    const [field, setField] =
        useState(
            inbox.filters.field,
        )

    const [
        provider,
        setProvider,
    ] = useState(
        inbox.filters.provider,
    )

    const [
        reviewStatus,
        setReviewStatus,
    ] = useState(
        inbox.filters.review_status,
    )

    const submitFilters = (
        event: FormEvent<HTMLFormElement>,
    ) => {
        event.preventDefault()

        router.get(
            route(
                'admin.seo.suggestions.index',
            ),
            {
                q:
                    q.trim() ||
                    undefined,
                entity_type:
                    entityType ||
                    undefined,
                field:
                    field ||
                    undefined,
                provider:
                    provider ||
                    undefined,
                review_status:
                    reviewStatus ||
                    undefined,
            },
            {
                preserveScroll: true,
                preserveState: true,
                replace: true,
            },
        )
    }

    const clearFilters = () => {
        setQ('')
        setEntityType('')
        setField('')
        setProvider('')
        setReviewStatus('')

        router.get(
            route(
                'admin.seo.suggestions.index',
            ),
            {},
            {
                preserveScroll: true,
                replace: true,
            },
        )
    }

    const review = (
        item: SuggestionItem,
        decision:
            | 'approve'
            | 'reject',
    ) => {
        router.post(
            route(
                decision ===
                    'approve'
                    ? 'admin.seo.suggestions.approve'
                    : 'admin.seo.suggestions.reject',
                {
                    suggestionId:
                        item.id,
                },
            ),
            {},
            {
                preserveScroll: true,
            },
        )
    }

    return (
        <AdminLayout title="">
            <Head title="AI Suggestions" />

            <div className="space-y-6">
                <PageHeader />

                <SafetyNotice />

                <SummaryCards
                    summary={
                        inbox.summary
                    }
                />

                <FilterPanel
                    q={q}
                    setQ={setQ}
                    entityType={
                        entityType
                    }
                    setEntityType={
                        setEntityType
                    }
                    field={field}
                    setField={setField}
                    provider={provider}
                    setProvider={
                        setProvider
                    }
                    reviewStatus={
                        reviewStatus
                    }
                    setReviewStatus={
                        setReviewStatus
                    }
                    options={
                        inbox.options
                    }
                    onSubmit={
                        submitFilters
                    }
                    onClear={
                        clearFilters
                    }
                />

                <div className="grid gap-6 xl:grid-cols-[minmax(0,1fr)_320px]">
                    <main className="min-w-0 space-y-4">
                        {inbox.items
                            .length ===
                        0 ? (
                            <EmptyInbox />
                        ) : (
                            inbox.items.map(
                                (
                                    item,
                                ) => (
                                    <SuggestionCard
                                        key={
                                            item.id
                                        }
                                        item={
                                            item
                                        }
                                        onReview={
                                            review
                                        }
                                    />
                                ),
                            )
                        )}

                        <Pagination
                            pagination={
                                inbox.pagination
                            }
                        />
                    </main>

                    <aside className="space-y-6 xl:sticky xl:top-6 xl:self-start">
                        <ReviewPolicyCard />

                        <InboxStatusCard
                            summary={
                                inbox.summary
                            }
                            pagination={
                                inbox.pagination
                            }
                        />
                    </aside>
                </div>
            </div>
        </AdminLayout>
    )
}

function PageHeader() {
    return (
        <div>
            <div className="flex items-center gap-2 text-sm font-medium text-indigo-600 dark:text-indigo-400">
                <Bot className="h-4 w-4" />
                AI SEO workspace
            </div>

            <h1 className="mt-2 text-2xl font-bold tracking-tight text-neutral-950 dark:text-neutral-100">
                AI Suggestions Inbox
            </h1>

            <p className="mt-2 max-w-3xl text-sm leading-6 text-neutral-500 dark:text-neutral-400">
                Review AI-generated SEO
                metadata drafts before they
                become eligible for any later
                application workflow.
            </p>
        </div>
    )
}

function SafetyNotice() {
    return (
        <div className="rounded-xl border border-violet-200 bg-violet-50/70 p-4 dark:border-violet-900/60 dark:bg-violet-950/20">
            <div className="flex items-start gap-3">
                <ShieldCheck className="mt-0.5 h-5 w-5 shrink-0 text-violet-600 dark:text-violet-400" />

                <div>
                    <p className="text-sm font-semibold text-violet-950 dark:text-violet-100">
                        Human review remains
                        authoritative
                    </p>

                    <p className="mt-1 text-sm leading-6 text-violet-800/80 dark:text-violet-300/80">
                        Approving a suggestion
                        only records a review
                        decision. It does not
                        update live SEO. If the
                        entity changed after
                        generation, approval is
                        blocked until fresh
                        suggestions are generated.
                    </p>
                </div>
            </div>
        </div>
    )
}

function SummaryCards({
    summary,
}: {
    summary: Props['inbox']['summary']
}) {
    const cards = [
        {
            label:
                'Total suggestions',
            value:
                summary.total,
            icon:
                Sparkles,
        },
        {
            label:
                'Pending review',
            value:
                summary.pending,
            icon:
                Clock3,
        },
        {
            label:
                'Approved',
            value:
                summary.approved,
            icon:
                CheckCircle2,
        },
        {
            label:
                'Rejected',
            value:
                summary.rejected,
            icon:
                XCircle,
        },
    ]

    return (
        <div className="grid gap-4 sm:grid-cols-2 xl:grid-cols-4">
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
                                    {
                                        card.label
                                    }
                                </p>

                                <p className="mt-2 text-2xl font-bold text-neutral-950 dark:text-neutral-100">
                                    {
                                        card.value
                                    }
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

function FilterPanel({
    q,
    setQ,
    entityType,
    setEntityType,
    field,
    setField,
    provider,
    setProvider,
    reviewStatus,
    setReviewStatus,
    options,
    onSubmit,
    onClear,
}: {
    q: string
    setQ: (
        value: string,
    ) => void
    entityType: string
    setEntityType: (
        value: string,
    ) => void
    field: string
    setField: (
        value: string,
    ) => void
    provider: string
    setProvider: (
        value: string,
    ) => void
    reviewStatus: string
    setReviewStatus: (
        value: string,
    ) => void
    options: Props['inbox']['options']
    onSubmit: (
        event: FormEvent<HTMLFormElement>,
    ) => void
    onClear: () => void
}) {
    return (
        <form
            onSubmit={onSubmit}
            className="rounded-xl border border-neutral-200 bg-white p-5 shadow-sm dark:border-neutral-800 dark:bg-neutral-950 sm:p-6"
        >
            <div className="mb-4 flex items-center gap-3">
                <div className="flex h-9 w-9 items-center justify-center rounded-lg bg-neutral-100 text-neutral-700 dark:bg-neutral-900 dark:text-neutral-300">
                    <Filter className="h-4 w-4" />
                </div>

                <div>
                    <h2 className="text-base font-semibold text-neutral-950 dark:text-neutral-100">
                        Review filters
                    </h2>

                    <p className="mt-1 text-sm text-neutral-500 dark:text-neutral-400">
                        Find drafts by entity,
                        field, provider or review
                        state.
                    </p>
                </div>
            </div>

            <div className="grid gap-3 lg:grid-cols-5">
                <label className="relative lg:col-span-2">
                    <Search className="pointer-events-none absolute left-3 top-1/2 h-4 w-4 -translate-y-1/2 text-neutral-400" />

                    <input
                        value={q}
                        onChange={(
                            event,
                        ) =>
                            setQ(
                                event
                                    .target
                                    .value,
                            )
                        }
                        placeholder="Search entity, slug or suggestion..."
                        className={`${inputClasses} pl-9`}
                    />
                </label>

                <Select
                    value={
                        entityType
                    }
                    onChange={
                        setEntityType
                    }
                    options={
                        options.entity_types
                    }
                />

                <Select
                    value={field}
                    onChange={
                        setField
                    }
                    options={
                        options.fields
                    }
                />

                <Select
                    value={
                        reviewStatus
                    }
                    onChange={
                        setReviewStatus
                    }
                    options={
                        options.review_statuses
                    }
                />
            </div>

            <div className="mt-3 flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">
                <div className="w-full sm:max-w-xs">
                    <Select
                        value={
                            provider
                        }
                        onChange={
                            setProvider
                        }
                        options={
                            options.providers
                        }
                    />
                </div>

                <div className="flex gap-2">
                    <button
                        type="button"
                        onClick={
                            onClear
                        }
                        className="inline-flex h-10 items-center justify-center rounded-lg border border-neutral-300 bg-white px-4 text-sm font-semibold text-neutral-700 transition hover:bg-neutral-50 dark:border-neutral-700 dark:bg-neutral-950 dark:text-neutral-200 dark:hover:bg-neutral-900"
                    >
                        Clear
                    </button>

                    <button
                        type="submit"
                        className="inline-flex h-10 items-center justify-center gap-2 rounded-lg bg-neutral-950 px-4 text-sm font-semibold text-white transition hover:bg-neutral-800 dark:bg-white dark:text-neutral-950 dark:hover:bg-neutral-200"
                    >
                        <Search className="h-4 w-4" />
                        Filter
                    </button>
                </div>
            </div>
        </form>
    )
}

function Select({
    value,
    onChange,
    options,
}: {
    value: string
    onChange: (
        value: string,
    ) => void
    options: Option[]
}) {
    return (
        <select
            value={value}
            onChange={(
                event,
            ) =>
                onChange(
                    event
                        .target
                        .value,
                )
            }
            className={inputClasses}
        >
            {options.map(
                (
                    option,
                ) => (
                    <option
                        key={
                            option.value
                        }
                        value={
                            option.value
                        }
                    >
                        {
                            option.label
                        }
                    </option>
                ),
            )}
        </select>
    )
}

function SuggestionCard({
    item,
    onReview,
}: {
    item: SuggestionItem
    onReview: (
        item: SuggestionItem,
        decision:
            | 'approve'
            | 'reject',
    ) => void
}) {
    return (
        <article className="overflow-hidden rounded-xl border border-neutral-200 bg-white shadow-sm dark:border-neutral-800 dark:bg-neutral-950">
            <div className="flex flex-col gap-4 border-b border-neutral-200 px-5 py-4 dark:border-neutral-800 sm:px-6 lg:flex-row lg:items-start lg:justify-between">
                <div>
                    <div className="flex flex-wrap items-center gap-2">
                        <span className="rounded-full border border-neutral-200 bg-neutral-50 px-2.5 py-1 text-xs font-semibold text-neutral-700 dark:border-neutral-700 dark:bg-neutral-900 dark:text-neutral-200">
                            {
                                item.entity_type_label
                            }
                        </span>

                        <span className="rounded-full border border-indigo-200 bg-indigo-50 px-2.5 py-1 text-xs font-semibold text-indigo-700 dark:border-indigo-900 dark:bg-indigo-950/30 dark:text-indigo-300">
                            {
                                item.field_label
                            }
                        </span>

                        <ReviewBadge
                            status={
                                item.review_status
                            }
                        />

                        <FreshnessBadge
                            freshness={
                                item.freshness
                            }
                        />
                    </div>

                    <h2 className="mt-3 text-base font-semibold text-neutral-950 dark:text-neutral-100">
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
                            className="hover:text-indigo-600 dark:hover:text-indigo-400"
                        >
                            {
                                item.entity_name
                            }
                        </Link>
                    </h2>

                    <p className="mt-1 text-xs text-neutral-500 dark:text-neutral-400">
                        {item.entity_slug
                            ? `${item.entity_slug} • `
                            : ''}
                        Entity #
                        {
                            item.entity_id
                        }{' '}
                        • Request #
                        {
                            item.request_id
                        }
                    </p>
                </div>

                <div className="text-xs leading-5 text-neutral-500 dark:text-neutral-400 lg:text-right">
                    <p>
                        {
                            item.provider ??
                            'provider unavailable'
                        }
                        {item.model_name
                            ? ` • ${item.model_name}`
                            : ''}
                    </p>

                    <p>
                        Generated{' '}
                        {formatDateTime(
                            item.generated_at,
                        )}
                    </p>
                </div>
            </div>

            <div className="space-y-5 p-5 sm:p-6">
                <div className="grid gap-4 lg:grid-cols-2">
                    <ValuePanel
                        title="Existing value at generation"
                        value={
                            item.existing_value
                        }
                        muted
                    />

                    <ValuePanel
                        title="AI suggestion"
                        value={
                            item.suggested_value
                        }
                    />
                </div>

                {item.rationale && (
                    <div className="rounded-xl border border-neutral-200 bg-neutral-50 p-4 dark:border-neutral-800 dark:bg-neutral-900/60">
                        <p className="text-xs font-semibold uppercase tracking-wide text-neutral-500 dark:text-neutral-400">
                            AI rationale
                        </p>

                        <p className="mt-2 text-sm leading-6 text-neutral-700 dark:text-neutral-300">
                            {
                                item.rationale
                            }
                        </p>
                    </div>
                )}

                {item.freshness
                    .is_stale && (
                    <div className="flex items-start gap-3 rounded-xl border border-amber-200 bg-amber-50 p-4 dark:border-amber-900/60 dark:bg-amber-950/20">
                        <AlertTriangle className="mt-0.5 h-5 w-5 shrink-0 text-amber-600 dark:text-amber-400" />

                        <div>
                            <p className="text-sm font-semibold text-amber-950 dark:text-amber-100">
                                Suggestion is
                                stale
                            </p>

                            <p className="mt-1 text-sm leading-6 text-amber-800/80 dark:text-amber-300/80">
                                The entity
                                changed after
                                this suggestion
                                was generated.
                                Approval is
                                blocked until a
                                fresh generation
                                is created.
                            </p>
                        </div>
                    </div>
                )}

                <div className="flex flex-col gap-3 border-t border-neutral-200 pt-5 dark:border-neutral-800 sm:flex-row sm:items-center sm:justify-between">
                    <div className="text-xs leading-5 text-neutral-500 dark:text-neutral-400">
                        {item.reviewed_at ? (
                            <>
                                Last reviewed{' '}
                                {formatDateTime(
                                    item.reviewed_at,
                                )}
                            </>
                        ) : (
                            'Not reviewed yet'
                        )}
                    </div>

                    <div className="flex gap-2">
                        <button
                            type="button"
                            onClick={() =>
                                onReview(
                                    item,
                                    'reject',
                                )
                            }
                            disabled={
                                !item.can_reject
                            }
                            className="inline-flex h-10 items-center justify-center gap-2 rounded-lg border border-red-200 bg-white px-4 text-sm font-semibold text-red-700 transition hover:bg-red-50 disabled:cursor-not-allowed disabled:opacity-50 dark:border-red-900/60 dark:bg-neutral-950 dark:text-red-400 dark:hover:bg-red-950/20"
                        >
                            <ThumbsDown className="h-4 w-4" />
                            Reject
                        </button>

                        <button
                            type="button"
                            onClick={() =>
                                onReview(
                                    item,
                                    'approve',
                                )
                            }
                            disabled={
                                !item.can_approve
                            }
                            className="inline-flex h-10 items-center justify-center gap-2 rounded-lg bg-neutral-950 px-4 text-sm font-semibold text-white transition hover:bg-neutral-800 disabled:cursor-not-allowed disabled:opacity-40 dark:bg-white dark:text-neutral-950 dark:hover:bg-neutral-200"
                        >
                            <ThumbsUp className="h-4 w-4" />
                            Approve
                        </button>
                    </div>
                </div>
            </div>
        </article>
    )
}

function ValuePanel({
    title,
    value,
    muted = false,
}: {
    title: string
    value: string | null
    muted?: boolean
}) {
    return (
        <div
            className={[
                'rounded-xl border p-4',
                muted
                    ? 'border-neutral-200 bg-neutral-50 dark:border-neutral-800 dark:bg-neutral-900/60'
                    : 'border-indigo-200 bg-indigo-50/50 dark:border-indigo-900/60 dark:bg-indigo-950/20',
            ].join(' ')}
        >
            <p className="text-xs font-semibold uppercase tracking-wide text-neutral-500 dark:text-neutral-400">
                {title}
            </p>

            <p className="mt-2 whitespace-pre-wrap break-words text-sm leading-6 text-neutral-800 dark:text-neutral-200">
                {value ??
                    'No explicit value was present.'}
            </p>
        </div>
    )
}

function ReviewBadge({
    status,
}: {
    status: string
}) {
    const label =
        status.replaceAll(
            '_',
            ' ',
        )

    return (
        <span className="inline-flex items-center gap-1.5 rounded-full border border-neutral-200 bg-neutral-50 px-2.5 py-1 text-xs font-semibold capitalize text-neutral-700 dark:border-neutral-700 dark:bg-neutral-900 dark:text-neutral-200">
            {status ===
            'approved' ? (
                <Check className="h-3.5 w-3.5" />
            ) : status ===
              'rejected' ? (
                <XCircle className="h-3.5 w-3.5" />
            ) : (
                <Clock3 className="h-3.5 w-3.5" />
            )}

            {label}
        </span>
    )
}

function FreshnessBadge({
    freshness,
}: {
    freshness: Freshness
}) {
    return freshness.is_stale ? (
        <span className="inline-flex items-center gap-1.5 rounded-full border border-amber-200 bg-amber-50 px-2.5 py-1 text-xs font-semibold text-amber-700 dark:border-amber-900/60 dark:bg-amber-950/20 dark:text-amber-300">
            <RefreshCw className="h-3.5 w-3.5" />
            Stale
        </span>
    ) : (
        <span className="inline-flex items-center gap-1.5 rounded-full border border-emerald-200 bg-emerald-50 px-2.5 py-1 text-xs font-semibold text-emerald-700 dark:border-emerald-900/60 dark:bg-emerald-950/20 dark:text-emerald-300">
            <CheckCircle2 className="h-3.5 w-3.5" />
            Fresh
        </span>
    )
}

function ReviewPolicyCard() {
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
                    Approval records human
                    acceptance only.
                </p>

                <p>
                    Suggestions never change
                    canonical, robots, images or
                    structured data.
                </p>

                <p>
                    Stale suggestions cannot be
                    approved.
                </p>

                <p>
                    Every approve/reject action
                    appends a new audit row.
                </p>
            </div>
        </section>
    )
}

function InboxStatusCard({
    summary,
    pagination,
}: {
    summary: Props['inbox']['summary']
    pagination: Props['inbox']['pagination']
}) {
    return (
        <section className="rounded-xl border border-violet-200 bg-gradient-to-br from-violet-50 to-indigo-50 p-5 dark:border-violet-900/50 dark:from-violet-950/20 dark:to-indigo-950/20">
            <div className="flex items-center gap-2">
                <Bot className="h-5 w-5 text-violet-600 dark:text-violet-400" />

                <h2 className="font-semibold text-violet-950 dark:text-violet-100">
                    Inbox status
                </h2>
            </div>

            <div className="mt-4 space-y-3 text-sm text-violet-800/80 dark:text-violet-300/80">
                <p>
                    Showing{' '}
                    {
                        pagination.from ??
                        0
                    }
                    –
                    {
                        pagination.to ??
                        0
                    }{' '}
                    of{' '}
                    {
                        pagination.total
                    }
                </p>

                <p>
                    {
                        summary.pending
                    }{' '}
                    pending review
                </p>

                <p>
                    {
                        summary.stale_on_page
                    }{' '}
                    stale on this page
                </p>
            </div>
        </section>
    )
}

function Pagination({
    pagination,
}: {
    pagination: Props['inbox']['pagination']
}) {
    if (
        pagination.last_page <=
        1
    ) {
        return null
    }

    const visit = (
        url: string | null,
    ) => {
        if (!url) {
            return
        }

        router.get(
            url,
            {},
            {
                preserveScroll: true,
                preserveState: true,
            },
        )
    }

    return (
        <div className="flex items-center justify-between gap-4 rounded-xl border border-neutral-200 bg-white p-4 shadow-sm dark:border-neutral-800 dark:bg-neutral-950">
            <button
                type="button"
                disabled={
                    pagination.previous_page_url ===
                    null
                }
                onClick={() =>
                    visit(
                        pagination.previous_page_url,
                    )
                }
                className="inline-flex h-9 items-center justify-center rounded-lg border border-neutral-300 px-3 text-sm font-semibold text-neutral-700 transition hover:bg-neutral-50 disabled:cursor-not-allowed disabled:opacity-40 dark:border-neutral-700 dark:text-neutral-200 dark:hover:bg-neutral-900"
            >
                Previous
            </button>

            <span className="text-sm text-neutral-500 dark:text-neutral-400">
                Page{' '}
                {
                    pagination.current_page
                }{' '}
                of{' '}
                {
                    pagination.last_page
                }
            </span>

            <button
                type="button"
                disabled={
                    pagination.next_page_url ===
                    null
                }
                onClick={() =>
                    visit(
                        pagination.next_page_url,
                    )
                }
                className="inline-flex h-9 items-center justify-center rounded-lg border border-neutral-300 px-3 text-sm font-semibold text-neutral-700 transition hover:bg-neutral-50 disabled:cursor-not-allowed disabled:opacity-40 dark:border-neutral-700 dark:text-neutral-200 dark:hover:bg-neutral-900"
            >
                Next
            </button>
        </div>
    )
}

function EmptyInbox() {
    return (
        <div className="rounded-xl border border-dashed border-neutral-300 bg-white px-6 py-12 text-center dark:border-neutral-700 dark:bg-neutral-950">
            <Sparkles className="mx-auto h-8 w-8 text-neutral-400" />

            <h2 className="mt-4 text-base font-semibold text-neutral-900 dark:text-neutral-100">
                No suggestions found
            </h2>

            <p className="mx-auto mt-2 max-w-lg text-sm leading-6 text-neutral-500 dark:text-neutral-400">
                There are no completed AI SEO
                suggestions matching the current
                filters.
            </p>
        </div>
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

const inputClasses =
    'block h-10 w-full rounded-lg border border-neutral-300 bg-white px-3 text-sm text-neutral-900 shadow-sm outline-none transition placeholder:text-neutral-400 focus:border-indigo-500 focus:ring-2 focus:ring-indigo-500/20 dark:border-neutral-700 dark:bg-neutral-950 dark:text-neutral-100 dark:placeholder:text-neutral-600'
