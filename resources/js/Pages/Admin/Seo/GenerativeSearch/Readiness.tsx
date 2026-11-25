import AdminLayout from '@/Layouts/Admin/AdminLayout'
import {
    Head,
    Link,
    router,
} from '@inertiajs/react'
import {
    AlertTriangle,
    Bot,
    CheckCircle2,
    ExternalLink,
    Filter,
    Search,
    ShieldAlert,
} from 'lucide-react'
import {
    FormEvent,
    useMemo,
    useState,
} from 'react'

type Option = {
    value: string
    label: string
}

type Finding = {
    id: string
    severity: 'blocker' | 'warning' | 'info'
    category: string
    message: string
    action: string
}

type Signals = {
    entity_type: string
    content_characters: number
    context_text_items: number
    canonical_present: boolean
    robots_index: boolean
    robots_follow: boolean
    structured_data_types: string[]
    primary_image_present: boolean
}

type Row = {
    entity_type: string
    entity_id: number
    name: string
    slug: string
    public_path: string
    status: 'ready' | 'needs_work' | 'blocked'
    eligible_for_indexing: boolean
    signals: Signals
    findings: Finding[]
}

type Props = {
    filters: {
        entity_type: string
        status: string | null
        search: string | null
        page: number
        per_page: number
    }
    options: {
        entity_types: Option[]
        statuses: Option[]
    }
    summary: {
        scanned: number
        ready: number
        needs_work: number
        blocked: number
        truncated: boolean
        scan_limit: number
    }
    rows: Row[]
    pagination: {
        current_page: number
        last_page: number
        per_page: number
        total: number
    }
}

export default function Readiness({
    filters,
    options,
    summary,
    rows,
    pagination,
}: Props) {
    const [entityType, setEntityType] =
        useState(filters.entity_type)

    const [status, setStatus] =
        useState(filters.status ?? '')

    const [search, setSearch] =
        useState(filters.search ?? '')

    const [perPage, setPerPage] =
        useState(filters.per_page)

    const query = useMemo(
        () => ({
            entity_type:
                entityType,
            status:
                status || undefined,
            search:
                search.trim() || undefined,
            per_page:
                perPage,
        }),
        [
            entityType,
            status,
            search,
            perPage,
        ],
    )

    function submit(
        event: FormEvent,
    ) {
        event.preventDefault()

        router.get(
            route(
                'admin.seo.generative-search.readiness',
            ),
            query,
            {
                preserveScroll: true,
                preserveState: true,
                replace: true,
            },
        )
    }

    function reset() {
        setEntityType('product')
        setStatus('')
        setSearch('')
        setPerPage(25)

        router.get(
            route(
                'admin.seo.generative-search.readiness',
            ),
            {
                entity_type:
                    'product',
            },
            {
                preserveScroll: true,
                replace: true,
            },
        )
    }

    function changePage(
        page: number,
    ) {
        router.get(
            route(
                'admin.seo.generative-search.readiness',
            ),
            {
                ...query,
                page,
            },
            {
                preserveScroll: true,
                preserveState: true,
                replace: true,
            },
        )
    }

    return (
        <AdminLayout title="">
            <Head title="Generative Search Readiness" />

            <div className="space-y-6">
                <div className="flex flex-col gap-4 lg:flex-row lg:items-start lg:justify-between">
                    <div>
                        <h1 className="text-2xl font-bold tracking-tight text-neutral-950 dark:text-neutral-100">
                            Generative Search Readiness
                        </h1>

                        <p className="mt-2 max-w-3xl text-sm leading-6 text-neutral-500 dark:text-neutral-400">
                            Review deterministic indexability, content, context, canonical, structured-data and image signals. This workspace does not estimate AI ranking or citation probability.
                        </p>
                    </div>

                    <div className="flex flex-wrap gap-2">
                        <Link
                            href={route(
                                'admin.seo.generative-search.crawlers.index',
                            )}
                            className="inline-flex h-10 items-center gap-2 rounded-lg border border-neutral-300 bg-white px-4 text-sm font-semibold text-neutral-700 hover:bg-neutral-50 dark:border-neutral-700 dark:bg-neutral-950 dark:text-neutral-200"
                        >
                            <Bot className="h-4 w-4" />
                            Crawler policy
                        </Link>
                    </div>
                </div>

                {summary.truncated && (
                    <div className="rounded-xl border border-amber-200 bg-amber-50 p-4 text-sm text-amber-900 dark:border-amber-900/60 dark:bg-amber-950/20 dark:text-amber-200">
                        This review is bounded to the newest {summary.scan_limit} matching entities. Narrow the search or entity type for a more targeted review.
                    </div>
                )}

                <div className="grid gap-4 sm:grid-cols-2 xl:grid-cols-4">
                    <SummaryCard
                        label="Scanned"
                        value={summary.scanned}
                        icon={Filter}
                    />

                    <SummaryCard
                        label="Ready"
                        value={summary.ready}
                        icon={CheckCircle2}
                    />

                    <SummaryCard
                        label="Needs work"
                        value={summary.needs_work}
                        icon={AlertTriangle}
                    />

                    <SummaryCard
                        label="Blocked"
                        value={summary.blocked}
                        icon={ShieldAlert}
                    />
                </div>

                <form
                    onSubmit={submit}
                    className="grid gap-4 rounded-xl border border-neutral-200 bg-white p-5 shadow-sm dark:border-neutral-800 dark:bg-neutral-950 lg:grid-cols-[1fr_1fr_2fr_auto_auto]"
                >
                    <label className="space-y-1.5 text-sm">
                        <span className="font-medium text-neutral-700 dark:text-neutral-300">
                            Entity type
                        </span>

                        <select
                            value={entityType}
                            onChange={(
                                event,
                            ) =>
                                setEntityType(
                                    event.target.value,
                                )
                            }
                            className="h-10 w-full rounded-lg border border-neutral-300 bg-white px-3 dark:border-neutral-700 dark:bg-neutral-950"
                        >
                            {options.entity_types.map(
                                (
                                    option,
                                ) => (
                                    <option
                                        key={option.value}
                                        value={option.value}
                                    >
                                        {option.label}
                                    </option>
                                ),
                            )}
                        </select>
                    </label>

                    <label className="space-y-1.5 text-sm">
                        <span className="font-medium text-neutral-700 dark:text-neutral-300">
                            Readiness
                        </span>

                        <select
                            value={status}
                            onChange={(
                                event,
                            ) =>
                                setStatus(
                                    event.target.value,
                                )
                            }
                            className="h-10 w-full rounded-lg border border-neutral-300 bg-white px-3 dark:border-neutral-700 dark:bg-neutral-950"
                        >
                            <option value="">
                                All statuses
                            </option>

                            {options.statuses.map(
                                (
                                    option,
                                ) => (
                                    <option
                                        key={option.value}
                                        value={option.value}
                                    >
                                        {option.label}
                                    </option>
                                ),
                            )}
                        </select>
                    </label>

                    <label className="space-y-1.5 text-sm">
                        <span className="font-medium text-neutral-700 dark:text-neutral-300">
                            Search
                        </span>

                        <div className="relative">
                            <Search className="pointer-events-none absolute left-3 top-1/2 h-4 w-4 -translate-y-1/2 text-neutral-400" />

                            <input
                                value={search}
                                onChange={(
                                    event,
                                ) =>
                                    setSearch(
                                        event.target.value,
                                    )
                                }
                                placeholder="Name, slug or product SKU"
                                className="h-10 w-full rounded-lg border border-neutral-300 bg-white pl-9 pr-3 dark:border-neutral-700 dark:bg-neutral-950"
                            />
                        </div>
                    </label>

                    <label className="space-y-1.5 text-sm">
                        <span className="font-medium text-neutral-700 dark:text-neutral-300">
                            Rows
                        </span>

                        <select
                            value={perPage}
                            onChange={(
                                event,
                            ) =>
                                setPerPage(
                                    Number(
                                        event.target.value,
                                    ),
                                )
                            }
                            className="h-10 rounded-lg border border-neutral-300 bg-white px-3 dark:border-neutral-700 dark:bg-neutral-950"
                        >
                            {[10, 25, 50].map(
                                (
                                    value,
                                ) => (
                                    <option
                                        key={value}
                                        value={value}
                                    >
                                        {value}
                                    </option>
                                ),
                            )}
                        </select>
                    </label>

                    <div className="flex items-end gap-2">
                        <button
                            type="submit"
                            className="h-10 rounded-lg bg-neutral-950 px-4 text-sm font-semibold text-white dark:bg-white dark:text-neutral-950"
                        >
                            Review
                        </button>

                        <button
                            type="button"
                            onClick={reset}
                            className="h-10 rounded-lg border border-neutral-300 px-4 text-sm font-semibold text-neutral-700 dark:border-neutral-700 dark:text-neutral-200"
                        >
                            Reset
                        </button>
                    </div>
                </form>

                <section className="space-y-4">
                    {rows.length === 0 ? (
                        <div className="rounded-xl border border-neutral-200 bg-white p-10 text-center text-sm text-neutral-500 dark:border-neutral-800 dark:bg-neutral-950 dark:text-neutral-400">
                            No entities matched the current readiness review.
                        </div>
                    ) : (
                        rows.map(
                            (
                                row,
                            ) => (
                                <ReadinessRow
                                    key={`${row.entity_type}-${row.entity_id}`}
                                    row={row}
                                />
                            ),
                        )
                    )}
                </section>

                <div className="flex flex-col gap-3 rounded-xl border border-neutral-200 bg-white p-4 text-sm dark:border-neutral-800 dark:bg-neutral-950 sm:flex-row sm:items-center sm:justify-between">
                    <span className="text-neutral-500 dark:text-neutral-400">
                        {pagination.total} matching rows · page {pagination.current_page} of {pagination.last_page}
                    </span>

                    <div className="flex gap-2">
                        <button
                            type="button"
                            disabled={
                                pagination.current_page <=
                                1
                            }
                            onClick={() =>
                                changePage(
                                    pagination.current_page -
                                        1,
                                )
                            }
                            className="rounded-lg border border-neutral-300 px-3 py-2 font-medium disabled:opacity-40 dark:border-neutral-700"
                        >
                            Previous
                        </button>

                        <button
                            type="button"
                            disabled={
                                pagination.current_page >=
                                pagination.last_page
                            }
                            onClick={() =>
                                changePage(
                                    pagination.current_page +
                                        1,
                                )
                            }
                            className="rounded-lg border border-neutral-300 px-3 py-2 font-medium disabled:opacity-40 dark:border-neutral-700"
                        >
                            Next
                        </button>
                    </div>
                </div>
            </div>
        </AdminLayout>
    )
}

function SummaryCard({
    label,
    value,
    icon: Icon,
}: {
    label: string
    value: number
    icon: typeof Filter
}) {
    return (
        <div className="rounded-xl border border-neutral-200 bg-white p-5 shadow-sm dark:border-neutral-800 dark:bg-neutral-950">
            <div className="flex items-center justify-between gap-3">
                <div>
                    <p className="text-sm text-neutral-500 dark:text-neutral-400">
                        {label}
                    </p>

                    <p className="mt-1 text-2xl font-bold text-neutral-950 dark:text-neutral-100">
                        {value}
                    </p>
                </div>

                <Icon className="h-5 w-5 text-neutral-400" />
            </div>
        </div>
    )
}

function ReadinessRow({
    row,
}: {
    row: Row
}) {
    return (
        <article className="rounded-xl border border-neutral-200 bg-white p-5 shadow-sm dark:border-neutral-800 dark:bg-neutral-950">
            <div className="flex flex-col gap-4 xl:flex-row xl:items-start xl:justify-between">
                <div className="min-w-0">
                    <div className="flex flex-wrap items-center gap-2">
                        <StatusBadge
                            status={
                                row.status
                            }
                        />

                        <span className="text-xs font-medium uppercase tracking-wide text-neutral-400">
                            {row.entity_type} #{row.entity_id}
                        </span>
                    </div>

                    <h2 className="mt-2 break-words text-lg font-semibold text-neutral-950 dark:text-neutral-100">
                        {row.name}
                    </h2>

                    <p className="mt-1 break-all text-xs text-neutral-500 dark:text-neutral-400">
                        {row.public_path}
                    </p>
                </div>

                <div className="flex flex-wrap gap-2">
                    <Link
                        href={route(
                            'admin.seo.entities.show',
                            {
                                entityType:
                                    row.entity_type,
                                entityId:
                                    row.entity_id,
                            },
                        )}
                        className="inline-flex items-center rounded-lg border border-neutral-300 px-3 py-2 text-sm font-semibold text-neutral-700 dark:border-neutral-700 dark:text-neutral-200"
                    >
                        Entity SEO
                    </Link>

                    <a
                        href={row.public_path}
                        target="_blank"
                        rel="noreferrer"
                        className="inline-flex items-center gap-2 rounded-lg border border-neutral-300 px-3 py-2 text-sm font-semibold text-neutral-700 dark:border-neutral-700 dark:text-neutral-200"
                    >
                        View page
                        <ExternalLink className="h-4 w-4" />
                    </a>
                </div>
            </div>

            <dl className="mt-5 grid gap-3 sm:grid-cols-2 lg:grid-cols-4 xl:grid-cols-8">
                <Signal
                    label="Indexable"
                    value={
                        row.eligible_for_indexing
                            ? 'Yes'
                            : 'No'
                    }
                />

                <Signal
                    label="Canonical"
                    value={
                        row.signals.canonical_present
                            ? 'Present'
                            : 'Missing'
                    }
                />

                <Signal
                    label="Robots follow"
                    value={
                        row.signals.robots_follow
                            ? 'Yes'
                            : 'No'
                    }
                />

                <Signal
                    label="Content chars"
                    value={String(
                        row.signals.content_characters,
                    )}
                />

                <Signal
                    label="Context items"
                    value={String(
                        row.signals.context_text_items,
                    )}
                />

                <Signal
                    label="Primary image"
                    value={
                        row.signals.primary_image_present
                            ? 'Yes'
                            : 'No'
                    }
                />

                <Signal
                    label="Structured types"
                    value={
                        row.signals.structured_data_types.length >
                        0
                            ? row.signals.structured_data_types.join(
                                  ', ',
                              )
                            : 'None reported'
                    }
                />

                <Signal
                    label="Findings"
                    value={String(
                        row.findings.length,
                    )}
                />
            </dl>

            {row.findings.length > 0 && (
                <div className="mt-5 space-y-3">
                    {row.findings.map(
                        (
                            finding,
                        ) => (
                            <div
                                key={
                                    finding.id
                                }
                                className="rounded-lg border border-neutral-200 p-4 dark:border-neutral-800"
                            >
                                <div className="flex flex-wrap items-center gap-2">
                                    <span className="text-xs font-semibold uppercase tracking-wide text-neutral-500">
                                        {finding.severity}
                                    </span>

                                    <span className="text-xs text-neutral-400">
                                        {finding.category}
                                    </span>
                                </div>

                                <p className="mt-2 text-sm font-medium text-neutral-900 dark:text-neutral-100">
                                    {finding.message}
                                </p>

                                <p className="mt-1 text-sm leading-6 text-neutral-500 dark:text-neutral-400">
                                    {finding.action}
                                </p>
                            </div>
                        ),
                    )}
                </div>
            )}
        </article>
    )
}

function Signal({
    label,
    value,
}: {
    label: string
    value: string
}) {
    return (
        <div className="min-w-0 rounded-lg bg-neutral-50 p-3 dark:bg-neutral-900/60">
            <dt className="text-[11px] font-semibold uppercase tracking-wide text-neutral-400">
                {label}
            </dt>

            <dd className="mt-1 break-words text-sm font-medium text-neutral-900 dark:text-neutral-100">
                {value}
            </dd>
        </div>
    )
}

function StatusBadge({
    status,
}: {
    status: Row['status']
}) {
    const classes =
        status === 'ready'
            ? 'border-emerald-200 bg-emerald-50 text-emerald-700 dark:border-emerald-900/60 dark:bg-emerald-950/20 dark:text-emerald-300'
            : status === 'blocked'
              ? 'border-red-200 bg-red-50 text-red-700 dark:border-red-900/60 dark:bg-red-950/20 dark:text-red-300'
              : 'border-amber-200 bg-amber-50 text-amber-700 dark:border-amber-900/60 dark:bg-amber-950/20 dark:text-amber-300'

    return (
        <span
            className={`inline-flex rounded-full border px-2.5 py-1 text-xs font-semibold ${classes}`}
        >
            {status === 'needs_work'
                ? 'Needs work'
                : status.charAt(0).toUpperCase() +
                  status.slice(1)}
        </span>
    )
}
