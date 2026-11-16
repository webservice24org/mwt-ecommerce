import { Link, router } from '@inertiajs/react'
import {
    FormEvent,
    useMemo,
    useState,
} from 'react'

import AdminLayout from '@/Layouts/Admin/AdminLayout'

type Option = {
    value: string
    label: string
}

type Filters = {
    sources: string[]
    severities: string[]
    requires_action: boolean | null
    search: string | null
    per_page: number
}

type Item = {
    source: string
    source_label: string

    entity_type: string
    entity_id: number

    reference: string
    title: string

    priority: number
    severity: string

    reason: string
    requires_action: boolean

    age_hours: number
    age_days: number

    action_url: string
    action_label: string
}

type Pagination = {
    current_page: number
    last_page: number
    per_page: number
    total: number
    next_page_url: string | null
    previous_page_url: string | null
}

type Props = {
    filters: Filters
    items: Item[]
    pagination: Pagination

    options: {
        sources: Option[]
        severities: Option[]
    }
}

export default function Index({
    filters,
    items,
    pagination,
    options,
}: Props) {
    const [search, setSearch] =
        useState(
            filters.search ?? '',
        )

    const [
        sources,
        setSources,
    ] = useState<string[]>(
        filters.sources,
    )

    const [
        severities,
        setSeverities,
    ] = useState<string[]>(
        filters.severities,
    )

    const [
        requiresAction,
        setRequiresAction,
    ] = useState<
        '' | '1' | '0'
    >(
        filters.requires_action ===
            true
            ? '1'
            : filters.requires_action ===
                  false
              ? '0'
              : '',
    )

    const [
        perPage,
        setPerPage,
    ] = useState(
        filters.per_page,
    )

    const query = useMemo(
        () => ({
            source:
                sources,

            severity:
                severities,

            requires_action:
                requiresAction ===
                ''
                    ? undefined
                    : requiresAction,

            search:
                search || undefined,

            per_page:
                perPage,
        }),
        [
            sources,
            severities,
            requiresAction,
            search,
            perPage,
        ],
    )

    function visitReport(
        overrides: {
            sources?: string[]
            severities?: string[]
            requiresAction?: '' | '1' | '0'
            search?: string
            perPage?: number
        } = {},
    ) {
        const nextSources =
            overrides.sources ??
            sources

        const nextSeverities =
            overrides.severities ??
            severities

        const nextRequiresAction =
            overrides.requiresAction ??
            requiresAction

        const nextSearch =
            overrides.search ??
            search

        const nextPerPage =
            overrides.perPage ??
            perPage

        router.get(
            route(
                'admin.reports.operations.action-queue',
            ),
            {
                source:
                    nextSources,

                severity:
                    nextSeverities,

                requires_action:
                    nextRequiresAction ===
                    ''
                        ? undefined
                        : nextRequiresAction,

                search:
                    nextSearch ||
                    undefined,

                per_page:
                    nextPerPage,
            },
            {
                preserveState: true,
                preserveScroll: true,
                replace: true,
            },
        )
    }

    function submit(
        event: FormEvent,
    ) {
        event.preventDefault()

        visitReport()
    }

    function applyWorkflow(
        next: {
            sources?: string[]
            severities?: string[]
            requiresAction?: '' | '1' | '0'
        },
    ) {
        if (
            next.sources !==
            undefined
        ) {
            setSources(
                next.sources,
            )
        }

        if (
            next.severities !==
            undefined
        ) {
            setSeverities(
                next.severities,
            )
        }

        if (
            next.requiresAction !==
            undefined
        ) {
            setRequiresAction(
                next.requiresAction,
            )
        }

        visitReport(
            next,
        )
    }

    function clearSearch() {
        setSearch('')

        visitReport({
            search:
                '',
        })
    }

    function removeSource(
        value: string,
    ) {
        const nextSources =
            sources.filter(
                (
                    source,
                ) =>
                    source !==
                    value,
            )

        setSources(
            nextSources,
        )

        visitReport({
            sources:
                nextSources,
        })
    }

    function removeSeverity(
        value: string,
    ) {
        const nextSeverities =
            severities.filter(
                (
                    severity,
                ) =>
                    severity !==
                    value,
            )

        setSeverities(
            nextSeverities,
        )

        visitReport({
            severities:
                nextSeverities,
        })
    }

    function clearRequiresAction() {
        setRequiresAction('')

        visitReport({
            requiresAction:
                '',
        })
    }

    function resetPerPage() {
        setPerPage(
            25,
        )

        visitReport({
            perPage:
                25,
        })
    }

    function clearFilters() {
        setSearch('')
        setSources([])
        setSeverities([])
        setRequiresAction('')
        setPerPage(
            25,
        )

        router.get(
            route(
                'admin.reports.operations.action-queue',
            ),
            {
                per_page:
                    25,
            },
            {
                preserveState: true,
                preserveScroll: true,
                replace: true,
            },
        )
    }

    const hasActiveFilters =
        search !== '' ||
        sources.length >
            0 ||
        severities.length >
            0 ||
        requiresAction !==
            '' ||
        perPage !==
            25

    return (
        <AdminLayout
            title="Admin Action Queue"
            description="One prioritized operational queue across orders, COD collection, and inventory alerts."
        >
            <div className="space-y-6">
                <section className="grid gap-4 sm:grid-cols-2 lg:grid-cols-4">
                    <MetricCard
                        label="Queue items"
                        value={
                            pagination.total
                        }
                    />

                    <MetricCard
                        label="On this page"
                        value={
                            items.length
                        }
                    />

                    <MetricCard
                        label="Critical"
                        value={
                            items.filter(
                                (
                                    item,
                                ) =>
                                    item.severity ===
                                    'critical',
                            ).length
                        }
                    />

                    <MetricCard
                        label="High"
                        value={
                            items.filter(
                                (
                                    item,
                                ) =>
                                    item.severity ===
                                    'high',
                            ).length
                        }
                    />
                </section>

                <section className="rounded-xl border border-neutral-200 bg-white p-4">
                    <div className="flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">
                        <div>
                            <h2 className="text-sm font-semibold text-neutral-900">
                                Quick workflows
                            </h2>

                            <p className="mt-1 text-xs text-neutral-500">
                                Jump directly to the highest-value operational work.
                            </p>
                        </div>

                        <div className="text-xs text-neutral-500">
                            {
                                pagination.total
                            }{' '}
                            queue items
                        </div>
                    </div>

                    <div className="mt-4 flex flex-wrap gap-2">
                        <WorkflowButton
                            label="Needs Action"
                            active={
                                requiresAction ===
                                '1'
                            }
                            onClick={() =>
                                applyWorkflow({
                                    requiresAction:
                                        '1',
                                })
                            }
                        />

                        <WorkflowButton
                            label="Critical"
                            active={
                                sameValues(
                                    severities,
                                    [
                                        'critical',
                                    ],
                                )
                            }
                            onClick={() =>
                                applyWorkflow({
                                    severities: [
                                        'critical',
                                    ],
                                })
                            }
                        />

                        <WorkflowButton
                            label="High Priority"
                            active={
                                sameValues(
                                    severities,
                                    [
                                        'critical',
                                        'high',
                                    ],
                                )
                            }
                            onClick={() =>
                                applyWorkflow({
                                    severities: [
                                        'critical',
                                        'high',
                                    ],
                                })
                            }
                        />

                        <WorkflowButton
                            label="Orders"
                            active={
                                sameValues(
                                    sources,
                                    [
                                        'operational_orders',
                                    ],
                                )
                            }
                            onClick={() =>
                                applyWorkflow({
                                    sources: [
                                        'operational_orders',
                                    ],
                                })
                            }
                        />

                        <WorkflowButton
                            label="COD Collection"
                            active={
                                sameValues(
                                    sources,
                                    [
                                        'cod_operations',
                                    ],
                                )
                            }
                            onClick={() =>
                                applyWorkflow({
                                    sources: [
                                        'cod_operations',
                                    ],
                                })
                            }
                        />

                        <WorkflowButton
                            label="Inventory"
                            active={
                                sameValues(
                                    sources,
                                    [
                                        'inventory_operations',
                                    ],
                                )
                            }
                            onClick={() =>
                                applyWorkflow({
                                    sources: [
                                        'inventory_operations',
                                    ],
                                })
                            }
                        />
                        <a
                            href={route(
                                'admin.reports.operations.action-queue.csv',
                                query,
                            )}
                            className="inline-flex items-center justify-center rounded-lg border border-neutral-300 bg-white px-4 py-2 text-sm font-semibold text-neutral-700 transition hover:bg-neutral-50"
                        >
                            Export CSV
                        </a>
                    </div>
                </section>

                <form
                    onSubmit={submit}
                    className="rounded-xl border border-neutral-200 bg-white p-4"
                >
                    <div className="grid gap-4 lg:grid-cols-4">
                        <div>
                            <label className="mb-1 block text-sm font-medium text-neutral-700">
                                Search
                            </label>

                            <div className="flex gap-2">
                                <input
                                    value={search}
                                    onChange={(
                                        event,
                                    ) =>
                                        setSearch(
                                            event
                                                .target
                                                .value,
                                        )
                                    }
                                    placeholder="Reference, title, reason..."
                                    className="min-w-0 flex-1 rounded-lg border border-neutral-300 px-3 py-2 text-sm"
                                />

                                {search !==
                                    '' && (
                                    <button
                                        type="button"
                                        onClick={
                                            clearSearch
                                        }
                                        className="shrink-0 rounded-lg border border-neutral-300 px-3 py-2 text-xs font-semibold text-neutral-600 transition hover:bg-neutral-50"
                                    >
                                        Clear search
                                    </button>
                                )}
                            </div>
                        </div>

                        <MultiSelect
                            label="Source"
                            options={
                                options.sources
                            }
                            values={
                                sources
                            }
                            onChange={
                                setSources
                            }
                        />

                        <MultiSelect
                            label="Severity"
                            options={
                                options.severities
                            }
                            values={
                                severities
                            }
                            onChange={
                                setSeverities
                            }
                        />

                        <div>
                            <label className="mb-1 block text-sm font-medium text-neutral-700">
                                Requires action
                            </label>

                            <select
                                value={
                                    requiresAction
                                }
                                onChange={(
                                    event,
                                ) =>
                                    setRequiresAction(
                                        event
                                            .target
                                            .value as
                                            | ''
                                            | '1'
                                            | '0',
                                    )
                                }
                                className="w-full rounded-lg border border-neutral-300 px-3 py-2 text-sm"
                            >
                                <option value="">
                                    All
                                </option>

                                <option value="1">
                                    Yes
                                </option>

                                <option value="0">
                                    No
                                </option>
                            </select>
                        </div>
                    </div>

                    <div className="mt-4 flex flex-wrap items-center gap-3">
                        <select
                            value={
                                perPage
                            }
                            onChange={(
                                event,
                            ) =>
                                setPerPage(
                                    Number(
                                        event
                                            .target
                                            .value,
                                    ),
                                )
                            }
                            className="rounded-lg border border-neutral-300 px-3 py-2 text-sm"
                        >
                            {[
                                10,
                                25,
                                50,
                                100,
                            ].map(
                                (
                                    value,
                                ) => (
                                    <option
                                        key={
                                            value
                                        }
                                        value={
                                            value
                                        }
                                    >
                                        {
                                            value
                                        }{' '}
                                        per page
                                    </option>
                                ),
                            )}
                        </select>

                        <button
                            type="submit"
                            className="rounded-lg bg-neutral-900 px-4 py-2 text-sm font-semibold text-white"
                        >
                            Apply filters
                        </button>

                        <button
                            type="button"
                            onClick={
                                clearFilters
                            }
                            className="rounded-lg border border-neutral-300 px-4 py-2 text-sm font-semibold text-neutral-700"
                        >
                            Clear filters
                        </button>
                    </div>
                </form>

                {hasActiveFilters && (
                    <section className="rounded-xl border border-neutral-200 bg-white p-4">
                        <div className="flex flex-wrap items-center justify-between gap-3">
                            <div>
                                <h2 className="text-sm font-semibold text-neutral-900">
                                    Active filters
                                </h2>

                                <p className="mt-1 text-xs text-neutral-500">
                                    Remove individual filters without resetting the rest of the queue.
                                </p>
                            </div>

                            <button
                                type="button"
                                onClick={
                                    clearFilters
                                }
                                className="text-sm font-semibold text-neutral-700 underline-offset-4 hover:underline"
                            >
                                Clear all
                            </button>
                        </div>

                        <div className="mt-3 flex flex-wrap gap-2">
                            {search !==
                                '' && (
                                <ActiveFilterChip
                                    label={`Search: ${search}`}
                                    onRemove={
                                        clearSearch
                                    }
                                />
                            )}

                            {sources.map(
                                (
                                    value,
                                ) => (
                                    <ActiveFilterChip
                                        key={`source-${value}`}
                                        label={`Source: ${optionLabel(
                                            options.sources,
                                            value,
                                        )}`}
                                        onRemove={() =>
                                            removeSource(
                                                value,
                                            )
                                        }
                                    />
                                ),
                            )}

                            {severities.map(
                                (
                                    value,
                                ) => (
                                    <ActiveFilterChip
                                        key={`severity-${value}`}
                                        label={`Severity: ${optionLabel(
                                            options.severities,
                                            value,
                                        )}`}
                                        onRemove={() =>
                                            removeSeverity(
                                                value,
                                            )
                                        }
                                    />
                                ),
                            )}

                            {requiresAction !==
                                '' && (
                                <ActiveFilterChip
                                    label={`Requires action: ${
                                        requiresAction ===
                                        '1'
                                            ? 'Yes'
                                            : 'No'
                                    }`}
                                    onRemove={
                                        clearRequiresAction
                                    }
                                />
                            )}

                            {perPage !==
                                25 && (
                                <ActiveFilterChip
                                    label={`${perPage} per page`}
                                    onRemove={
                                        resetPerPage
                                    }
                                />
                            )}
                        </div>
                    </section>
                )}

                <div className="overflow-hidden rounded-xl border border-neutral-200 bg-white">
                    <div className="hidden overflow-x-auto md:block">
                        <table className="min-w-full divide-y divide-neutral-200">
                            <thead className="bg-neutral-50">
                                <tr>
                                    <Th>
                                        Priority
                                    </Th>

                                    <Th>
                                        Item
                                    </Th>

                                    <Th>
                                        Source
                                    </Th>

                                    <Th>
                                        Severity
                                    </Th>

                                    <Th>
                                        Reason
                                    </Th>

                                    <Th>
                                        Age
                                    </Th>

                                    <Th>
                                        Action
                                    </Th>
                                </tr>
                            </thead>

                            <tbody className="divide-y divide-neutral-100">
                                {items.length ===
                                0 ? (
                                    <tr>
                                        <td
                                            colSpan={
                                                7
                                            }
                                            className="px-4 py-10 text-center text-sm text-neutral-500"
                                        >
                                            No action queue items found.
                                        </td>
                                    </tr>
                                ) : (
                                    items.map(
                                        (
                                            item,
                                        ) => (
                                            <tr
                                                key={`${item.source}-${item.entity_type}-${item.entity_id}`}
                                                className={
                                                    rowClass(
                                                        item,
                                                    )
                                                }
                                            >
                                                <Td>
                                                    <span className="font-bold text-neutral-900">
                                                        {
                                                            item.priority
                                                        }
                                                    </span>
                                                </Td>

                                                <Td>
                                                    <div className="font-semibold text-neutral-900">
                                                        {
                                                            item.title
                                                        }
                                                    </div>

                                                    <div className="mt-1 text-xs text-neutral-500">
                                                        {
                                                            item.reference
                                                        }
                                                    </div>
                                                </Td>

                                                <Td>
                                                    {
                                                        item.source_label
                                                    }
                                                </Td>

                                                <Td>
                                                    <SeverityBadge
                                                        value={
                                                            item.severity
                                                        }
                                                    />
                                                </Td>

                                                <Td>
                                                    <div className="max-w-md whitespace-normal">
                                                        {
                                                            item.reason
                                                        }
                                                    </div>
                                                </Td>

                                                <Td>
                                                    {item.age_hours >
                                                    0 ? (
                                                        <>
                                                            <div>
                                                                {
                                                                    item.age_days
                                                                }{' '}
                                                                days
                                                            </div>

                                                            <div className="text-xs text-neutral-500">
                                                                {
                                                                    item.age_hours
                                                                }{' '}
                                                                hours
                                                            </div>
                                                        </>
                                                    ) : (
                                                        '—'
                                                    )}
                                                </Td>

                                                <Td>
                                                    <Link
                                                        href={
                                                            item.action_url
                                                        }
                                                        className="font-semibold text-neutral-900 underline-offset-4 hover:underline"
                                                    >
                                                        {
                                                            item.action_label
                                                        }
                                                    </Link>
                                                </Td>
                                            </tr>
                                        ),
                                    )
                                )}
                            </tbody>
                        </table>
                    </div>

                    <div className="divide-y divide-neutral-100 md:hidden">
                        {items.length ===
                        0 ? (
                            <div className="px-4 py-10 text-center text-sm text-neutral-500">
                                No action queue items found.
                            </div>
                        ) : (
                            items.map(
                                (
                                    item,
                                ) => (
                                    <MobileQueueCard
                                        key={`${item.source}-${item.entity_type}-${item.entity_id}`}
                                        item={
                                            item
                                        }
                                    />
                                ),
                            )
                        )}
                    </div>

                    <PaginationControls
                        pagination={
                            pagination
                        }
                        query={query}
                    />
                </div>
            </div>
        </AdminLayout>
    )
}

function WorkflowButton({
    label,
    active,
    onClick,
}: {
    label: string
    active: boolean
    onClick: () => void
}) {
    return (
        <button
            type="button"
            onClick={
                onClick
            }
            className={[
                'rounded-lg border px-3 py-2 text-sm font-semibold transition',
                active
                    ? 'border-neutral-900 bg-neutral-900 text-white'
                    : 'border-neutral-300 bg-white text-neutral-700 hover:bg-neutral-50',
            ].join(
                ' ',
            )}
        >
            {label}
        </button>
    )
}

function ActiveFilterChip({
    label,
    onRemove,
}: {
    label: string
    onRemove: () => void
}) {
    return (
        <span className="inline-flex items-center gap-2 rounded-full bg-neutral-100 px-3 py-1.5 text-xs font-semibold text-neutral-700">
            {label}

            <button
                type="button"
                onClick={
                    onRemove
                }
                aria-label={`Remove ${label}`}
                className="rounded-full text-neutral-500 transition hover:text-neutral-900"
            >
                ×
            </button>
        </span>
    )
}

function MobileQueueCard({
    item,
}: {
    item: Item
}) {
    return (
        <article
            className={[
                'p-4',
                mobileCardClass(
                    item,
                ),
            ].join(
                ' ',
            )}
        >
            <div className="flex items-start justify-between gap-3">
                <div className="min-w-0">
                    <div className="font-semibold text-neutral-900">
                        {
                            item.title
                        }
                    </div>

                    <div className="mt-1 text-xs text-neutral-500">
                        {
                            item.reference
                        }
                    </div>
                </div>

                <SeverityBadge
                    value={
                        item.severity
                    }
                />
            </div>

            <dl className="mt-4 grid grid-cols-2 gap-x-4 gap-y-3 text-sm">
                <div>
                    <dt className="text-xs text-neutral-500">
                        Source
                    </dt>

                    <dd className="mt-1 font-medium text-neutral-800">
                        {
                            item.source_label
                        }
                    </dd>
                </div>

                <div>
                    <dt className="text-xs text-neutral-500">
                        Priority
                    </dt>

                    <dd className="mt-1 font-bold text-neutral-900">
                        {
                            item.priority
                        }
                    </dd>
                </div>

                <div className="col-span-2">
                    <dt className="text-xs text-neutral-500">
                        Reason
                    </dt>

                    <dd className="mt-1 text-neutral-700">
                        {
                            item.reason
                        }
                    </dd>
                </div>

                <div>
                    <dt className="text-xs text-neutral-500">
                        Age
                    </dt>

                    <dd className="mt-1 text-neutral-700">
                        {item.age_hours >
                        0
                            ? `${item.age_days} days · ${item.age_hours} hours`
                            : '—'}
                    </dd>
                </div>

                <div>
                    <dt className="text-xs text-neutral-500">
                        Requires action
                    </dt>

                    <dd className="mt-1 font-medium text-neutral-800">
                        {item.requires_action
                            ? 'Yes'
                            : 'No'}
                    </dd>
                </div>
            </dl>

            <div className="mt-4 border-t border-neutral-200 pt-3">
                <Link
                    href={
                        item.action_url
                    }
                    className="inline-flex font-semibold text-neutral-900 underline-offset-4 hover:underline"
                >
                    {
                        item.action_label
                    }
                </Link>
            </div>
        </article>
    )
}

function MetricCard({
    label,
    value,
}: {
    label: string
    value: number
}) {
    return (
        <div className="rounded-xl border border-neutral-200 bg-white p-4">
            <div className="text-sm text-neutral-500">
                {label}
            </div>

            <div className="mt-2 text-2xl font-bold text-neutral-900">
                {value}
            </div>
        </div>
    )
}

function MultiSelect({
    label,
    options,
    values,
    onChange,
}: {
    label: string
    options: Option[]
    values: string[]
    onChange: (
        values: string[],
    ) => void
}) {
    return (
        <div>
            <label className="mb-1 block text-sm font-medium text-neutral-700">
                {label}
            </label>

            <select
                multiple
                value={values}
                onChange={(
                    event,
                ) =>
                    onChange(
                        Array.from(
                            event
                                .currentTarget
                                .selectedOptions,
                        ).map(
                            (
                                option,
                            ) =>
                                option.value,
                        ),
                    )
                }
                className="min-h-24 w-full rounded-lg border border-neutral-300 px-3 py-2 text-sm"
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
        </div>
    )
}

function SeverityBadge({
    value,
}: {
    value: string
}) {
    const classes =
        value ===
        'critical'
            ? 'bg-red-100 text-red-700'
            : value ===
                'high'
              ? 'bg-orange-100 text-orange-700'
              : value ===
                  'warning'
                ? 'bg-amber-100 text-amber-700'
                : value ===
                    'info'
                  ? 'bg-blue-100 text-blue-700'
                  : 'bg-neutral-100 text-neutral-700'

    return (
        <span
            className={[
                'inline-flex rounded-full px-2.5 py-1 text-xs font-semibold capitalize',
                classes,
            ].join(
                ' ',
            )}
        >
            {value}
        </span>
    )
}

function PaginationControls({
    pagination,
    query,
}: {
    pagination: Pagination
    query: Record<
        string,
        unknown
    >
}) {
    if (
        pagination.last_page <=
        1
    ) {
        return null
    }

    function goToPage(
        page: number,
    ) {
        router.get(
            route(
                'admin.reports.operations.action-queue',
            ),
            {
                ...query,
                page,
            },
            {
                preserveState: true,
                preserveScroll: true,
            },
        )
    }

    return (
        <div className="flex items-center justify-between border-t border-neutral-200 px-4 py-3">
            <div className="text-sm text-neutral-500">
                Page{' '}
                {
                    pagination.current_page
                }{' '}
                of{' '}
                {
                    pagination.last_page
                }
                {' · '}
                {
                    pagination.total
                }{' '}
                items
            </div>

            <div className="flex gap-2">
                <button
                    type="button"
                    disabled={
                        pagination.current_page <=
                        1
                    }
                    onClick={() =>
                        goToPage(
                            pagination.current_page -
                                1,
                        )
                    }
                    className="rounded-lg border border-neutral-300 px-3 py-2 text-sm disabled:opacity-50"
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
                        goToPage(
                            pagination.current_page +
                                1,
                        )
                    }
                    className="rounded-lg border border-neutral-300 px-3 py-2 text-sm disabled:opacity-50"
                >
                    Next
                </button>
            </div>
        </div>
    )
}

function optionLabel(
    options: Option[],
    value: string,
): string {
    return (
        options.find(
            (
                option,
            ) =>
                option.value ===
                value,
        )?.label ??
        value.replace(
            /_/g,
            ' ',
        )
    )
}

function sameValues(
    left: string[],
    right: string[],
): boolean {
    if (
        left.length !==
        right.length
    ) {
        return false
    }

    const normalizedLeft =
        [...left].sort()

    const normalizedRight =
        [...right].sort()

    return normalizedLeft.every(
        (
            value,
            index,
        ) =>
            value ===
            normalizedRight[index],
    )
}

function mobileCardClass(
    item: Item,
): string {
    if (
        item.severity ===
        'critical'
    ) {
        return 'bg-red-50/70'
    }

    if (
        item.severity ===
        'high'
    ) {
        return 'bg-orange-50/60'
    }

    if (
        item.severity ===
        'warning'
    ) {
        return 'bg-amber-50/40'
    }

    if (
        item.severity ===
        'info'
    ) {
        return 'bg-blue-50/40'
    }

    return 'bg-white'
}

function rowClass(
    item: Item,
): string {
    if (
        item.severity ===
        'critical'
    ) {
        return 'bg-red-50/70 hover:bg-red-50'
    }

    if (
        item.severity ===
        'high'
    ) {
        return 'bg-orange-50/60 hover:bg-orange-50'
    }

    if (
        item.severity ===
        'warning'
    ) {
        return 'bg-amber-50/40 hover:bg-amber-50'
    }

    return 'hover:bg-neutral-50'
}

function Th({
    children,
}: {
    children: React.ReactNode
}) {
    return (
        <th className="px-4 py-3 text-left text-xs font-semibold uppercase tracking-wide text-neutral-500">
            {children}
        </th>
    )
}

function Td({
    children,
}: {
    children: React.ReactNode
}) {
    return (
        <td className="whitespace-nowrap px-4 py-4 text-sm text-neutral-700">
            {children}
        </td>
    )
}