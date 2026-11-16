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
    statuses: string[]
    stock_states: string[]
    search: string | null
    per_page: number
}

type Summary = {
    products_count: number
    tracked_products_count: number

    in_stock_count: number
    low_stock_count: number
    out_of_stock_count: number

    total_stock_units: number
    low_stock_threshold: number

    attention_required_count: number
    critical_alert_count: number
    high_alert_count: number
    negative_stock_count: number
}

type InventoryAlert = {
    state: string
    label: string
    severity: string
    priority: number
    requires_attention: boolean
}

type Row = {
    product_id: number
    name: string
    slug: string
    sku: string | null
    status: string

    track_inventory: boolean
    stock_quantity: number

    low_stock_threshold: number
    stock_state: string

    alert: InventoryAlert

    updated_at: string | null
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
    summary: Summary
    rows: Row[]
    pagination: Pagination

    options: {
        statuses: Option[]
        stock_states: Option[]
    }
}

export default function Index({
    filters,
    summary,
    rows,
    pagination,
    options,
}: Props) {
    const [search, setSearch] =
        useState(
            filters.search ?? '',
        )

    const [
        statuses,
        setStatuses,
    ] = useState<string[]>(
        filters.statuses,
    )

    const [
        stockStates,
        setStockStates,
    ] = useState<string[]>(
        filters.stock_states,
    )

    const [
        perPage,
        setPerPage,
    ] = useState(
        filters.per_page,
    )

    const query = useMemo(
        () => ({
            status:
                statuses,

            stock_state:
                stockStates,

            search:
                search || undefined,

            per_page:
                perPage,
        }),
        [
            statuses,
            stockStates,
            search,
            perPage,
        ],
    )

    function visitReport(
        next: {
            statuses?: string[]
            stockStates?: string[]
            search?: string
            perPage?: number
        } = {},
    ) {
        const nextStatuses =
            next.statuses ?? statuses

        const nextStockStates =
            next.stockStates ?? stockStates

        const nextSearch =
            next.search ?? search

        const nextPerPage =
            next.perPage ?? perPage

        router.get(
            route(
                'admin.reports.operations.inventory',
            ),
            {
                status:
                    nextStatuses,

                stock_state:
                    nextStockStates,

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
        states: string[],
    ) {
        setStockStates(
            states,
        )

        visitReport({
            stockStates:
                states,
        })
    }

    function clearSearch() {
        setSearch('')

        visitReport({
            search:
                '',
        })
    }

    function removeStatus(
        value: string,
    ) {
        const nextStatuses =
            statuses.filter(
                (
                    status,
                ) =>
                    status !==
                    value,
            )

        setStatuses(
            nextStatuses,
        )

        visitReport({
            statuses:
                nextStatuses,
        })
    }

    function removeStockState(
        value: string,
    ) {
        const nextStockStates =
            stockStates.filter(
                (
                    stockState,
                ) =>
                    stockState !==
                    value,
            )

        setStockStates(
            nextStockStates,
        )

        visitReport({
            stockStates:
                nextStockStates,
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
        setStatuses([])
        setStockStates([])
        setPerPage(
            25,
        )

        router.get(
            route(
                'admin.reports.operations.inventory',
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
        statuses.length >
            0 ||
        stockStates.length >
            0 ||
        perPage !==
            25

    return (
        <AdminLayout
            title="Inventory Operations Report"
            description="Monitor tracked inventory, low-stock exposure, out-of-stock products, and stock alerts."
        >
            <div className="space-y-6">
                <SummaryCards
                    summary={summary}
                />

                <section className="rounded-xl border border-neutral-200 bg-white p-4">
                    <div className="flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">
                        <div>
                            <h2 className="text-sm font-semibold text-neutral-900">
                                Quick workflows
                            </h2>

                            <p className="mt-1 text-xs text-neutral-500">
                                Jump directly to the stock states that need operational attention.
                            </p>
                        </div>

                        <div className="text-xs text-neutral-500">
                            {
                                summary.attention_required_count
                            }{' '}
                            products need attention
                        </div>
                    </div>

                    <div className="mt-4 flex flex-wrap gap-2">
                        <WorkflowButton
                            label="Needs Attention"
                            active={
                                sameValues(
                                    stockStates,
                                    [
                                        'low_stock',
                                        'out_of_stock',
                                        'negative_stock',
                                    ],
                                )
                            }
                            onClick={() =>
                                applyWorkflow(
                                    [
                                        'low_stock',
                                        'out_of_stock',
                                        'negative_stock',
                                    ],
                                )
                            }
                        />

                        <WorkflowButton
                            label="Negative Stock"
                            active={
                                sameValues(
                                    stockStates,
                                    [
                                        'negative_stock',
                                    ],
                                )
                            }
                            onClick={() =>
                                applyWorkflow(
                                    [
                                        'negative_stock',
                                    ],
                                )
                            }
                        />

                        <WorkflowButton
                            label="Out of Stock"
                            active={
                                sameValues(
                                    stockStates,
                                    [
                                        'out_of_stock',
                                    ],
                                )
                            }
                            onClick={() =>
                                applyWorkflow(
                                    [
                                        'out_of_stock',
                                    ],
                                )
                            }
                        />

                        <WorkflowButton
                            label="Low Stock"
                            active={
                                sameValues(
                                    stockStates,
                                    [
                                        'low_stock',
                                    ],
                                )
                            }
                            onClick={() =>
                                applyWorkflow(
                                    [
                                        'low_stock',
                                    ],
                                )
                            }
                        />

                        <WorkflowButton
                            label="Healthy"
                            active={
                                sameValues(
                                    stockStates,
                                    [
                                        'in_stock',
                                    ],
                                )
                            }
                            onClick={() =>
                                applyWorkflow(
                                    [
                                        'in_stock',
                                    ],
                                )
                            }
                        />
                        <a
                            href={route(
                                'admin.reports.operations.inventory.csv',
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
                    <div className="grid gap-4 lg:grid-cols-3">
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
                                    placeholder="Product name, SKU, or slug"
                                    autoComplete="off"
                                    className="min-w-0 flex-1 rounded-lg border border-neutral-300 px-3 py-2 text-sm"
                                />

                                {search !==
                                    '' && (
                                    <button
                                        type="button"
                                        onClick={
                                            clearSearch
                                        }
                                        className="shrink-0 rounded-lg border border-neutral-300 px-3 py-2 text-xs font-semibold text-neutral-600 hover:bg-neutral-50"
                                    >
                                        Clear search
                                    </button>
                                )}
                            </div>
                        </div>

                        <MultiSelect
                            label="Product status"
                            options={
                                options.statuses
                            }
                            values={
                                statuses
                            }
                            onChange={
                                setStatuses
                            }
                        />

                        <MultiSelect
                            label="Stock state"
                            options={
                                options.stock_states
                            }
                            values={
                                stockStates
                            }
                            onChange={
                                setStockStates
                            }
                        />
                    </div>

                    <div className="mt-4 flex flex-wrap items-center gap-3">
                        <select
                            value={perPage}
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
                                    Remove individual filters without resetting the rest of the workflow.
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

                            {statuses.map(
                                (
                                    value,
                                ) => (
                                    <ActiveFilterChip
                                        key={`status-${value}`}
                                        label={`Status: ${optionLabel(
                                            options.statuses,
                                            value,
                                        )}`}
                                        onRemove={() =>
                                            removeStatus(
                                                value,
                                            )
                                        }
                                    />
                                ),
                            )}

                            {stockStates.map(
                                (
                                    value,
                                ) => (
                                    <ActiveFilterChip
                                        key={`stock-${value}`}
                                        label={`Stock: ${optionLabel(
                                            options.stock_states,
                                            value,
                                        )}`}
                                        onRemove={() =>
                                            removeStockState(
                                                value,
                                            )
                                        }
                                    />
                                ),
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
                                        Product
                                    </Th>

                                    <Th>
                                        Status
                                    </Th>

                                    <Th>
                                        Tracking
                                    </Th>

                                    <Th>
                                        Stock
                                    </Th>

                                    <Th>
                                        Stock state
                                    </Th>

                                    <Th>
                                        Alert
                                    </Th>

                                    <Th>
                                        Updated
                                    </Th>

                                    <Th>
                                        Action
                                    </Th>
                                </tr>
                            </thead>

                            <tbody className="divide-y divide-neutral-100">
                                {rows.length ===
                                0 ? (
                                    <tr>
                                        <td
                                            colSpan={
                                                8
                                            }
                                            className="px-4 py-10 text-center text-sm text-neutral-500"
                                        >
                                            No inventory products found for the selected filters.
                                        </td>
                                    </tr>
                                ) : (
                                    rows.map(
                                        (
                                            row,
                                        ) => (
                                            <tr
                                                key={
                                                    row.product_id
                                                }
                                                className={
                                                    rowClass(
                                                        row,
                                                    )
                                                }
                                            >
                                                <Td>
                                                    <div className="font-semibold text-neutral-900">
                                                        {
                                                            row.name
                                                        }
                                                    </div>

                                                    <div className="mt-1 text-xs text-neutral-500">
                                                        SKU:{' '}
                                                        {
                                                            row.sku ??
                                                            '—'
                                                        }
                                                    </div>
                                                </Td>

                                                <Td>
                                                    <StatusBadge
                                                        value={
                                                            row.status
                                                        }
                                                    />
                                                </Td>

                                                <Td>
                                                    {row.track_inventory
                                                        ? 'Tracked'
                                                        : 'Not tracked'}
                                                </Td>

                                                <Td>
                                                    <span className="font-semibold text-neutral-900">
                                                        {
                                                            row.stock_quantity
                                                        }
                                                    </span>
                                                </Td>

                                                <Td>
                                                    <StatusBadge
                                                        value={
                                                            row.stock_state
                                                        }
                                                    />
                                                </Td>

                                                <Td>
                                                    <AlertBadge
                                                        alert={
                                                            row.alert
                                                        }
                                                    />
                                                </Td>

                                                <Td>
                                                    {row.updated_at
                                                        ? formatDate(
                                                              row.updated_at,
                                                          )
                                                        : '—'}
                                                </Td>

                                                <Td>
                                                    <Link
                                                        href={route(
                                                            'admin.products.edit',
                                                            row.product_id,
                                                        )}
                                                        className="font-semibold text-neutral-900 underline-offset-4 hover:underline"
                                                    >
                                                        Review
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
                        {rows.length ===
                        0 ? (
                            <div className="px-4 py-10 text-center text-sm text-neutral-500">
                                No inventory products found for the selected filters.
                            </div>
                        ) : (
                            rows.map(
                                (
                                    row,
                                ) => (
                                    <MobileProductCard
                                        key={
                                            row.product_id
                                        }
                                        row={
                                            row
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
                className="rounded-full text-neutral-500 hover:text-neutral-900"
            >
                ×
            </button>
        </span>
    )
}

function MobileProductCard({
    row,
}: {
    row: Row
}) {
    return (
        <article
            className={[
                'p-4',
                mobileCardClass(
                    row,
                ),
            ].join(
                ' ',
            )}
        >
            <div className="flex items-start justify-between gap-3">
                <div className="min-w-0">
                    <div className="font-semibold text-neutral-900">
                        {
                            row.name
                        }
                    </div>

                    <div className="mt-1 text-xs text-neutral-500">
                        SKU:{' '}
                        {
                            row.sku ??
                            '—'
                        }
                    </div>
                </div>

                <AlertBadge
                    alert={
                        row.alert
                    }
                />
            </div>

            <dl className="mt-4 grid grid-cols-2 gap-x-4 gap-y-3 text-sm">
                <div>
                    <dt className="text-xs text-neutral-500">
                        Status
                    </dt>

                    <dd className="mt-1">
                        <StatusBadge
                            value={
                                row.status
                            }
                        />
                    </dd>
                </div>

                <div>
                    <dt className="text-xs text-neutral-500">
                        Tracking
                    </dt>

                    <dd className="mt-1 font-medium text-neutral-800">
                        {row.track_inventory
                            ? 'Tracked'
                            : 'Not tracked'}
                    </dd>
                </div>

                <div>
                    <dt className="text-xs text-neutral-500">
                        Stock
                    </dt>

                    <dd className="mt-1 font-semibold text-neutral-900">
                        {
                            row.stock_quantity
                        }
                    </dd>
                </div>

                <div>
                    <dt className="text-xs text-neutral-500">
                        Stock state
                    </dt>

                    <dd className="mt-1">
                        <StatusBadge
                            value={
                                row.stock_state
                            }
                        />
                    </dd>
                </div>

                <div className="col-span-2">
                    <dt className="text-xs text-neutral-500">
                        Updated
                    </dt>

                    <dd className="mt-1 text-neutral-700">
                        {row.updated_at
                            ? formatDate(
                                  row.updated_at,
                              )
                            : '—'}
                    </dd>
                </div>
            </dl>

            <div className="mt-4 border-t border-neutral-200 pt-3">
                <Link
                    href={route(
                        'admin.products.edit',
                        row.product_id,
                    )}
                    className="inline-flex font-semibold text-neutral-900 underline-offset-4 hover:underline"
                >
                    Review Product
                </Link>
            </div>
        </article>
    )
}

function SummaryCards({
    summary,
}: {
    summary: Summary
}) {
    const cards = [
        [
            'Products',
            summary.products_count,
        ],

        [
            'Tracked',
            summary.tracked_products_count,
        ],

        [
            'In stock',
            summary.in_stock_count,
        ],

        [
            'Low stock',
            summary.low_stock_count,
        ],

        [
            'Out of stock',
            summary.out_of_stock_count,
        ],

        [
            'Negative stock',
            summary.negative_stock_count,
        ],

        [
            'Needs attention',
            summary.attention_required_count,
        ],

        [
            'Critical alerts',
            summary.critical_alert_count,
        ],

        [
            'High alerts',
            summary.high_alert_count,
        ],

        [
            'Stock units',
            summary.total_stock_units,
        ],
    ] as const

    return (
        <>
            <div className="grid gap-4 sm:grid-cols-2 xl:grid-cols-5">
                {cards.map(
                    ([
                        label,
                        value,
                    ]) => (
                        <div
                            key={
                                label
                            }
                            className="rounded-xl border border-neutral-200 bg-white p-4"
                        >
                            <div className="text-sm text-neutral-500">
                                {
                                    label
                                }
                            </div>

                            <div className="mt-2 text-2xl font-bold text-neutral-900">
                                {
                                    value
                                }
                            </div>
                        </div>
                    ),
                )}
            </div>

            <div className="text-sm text-neutral-500">
                Low-stock threshold:{' '}
                <span className="font-semibold text-neutral-700">
                    {
                        summary.low_stock_threshold
                    }{' '}
                    units
                </span>
            </div>
        </>
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

function AlertBadge({
    alert,
}: {
    alert: InventoryAlert
}) {
    const classes =
        alert.severity ===
        'critical'
            ? 'bg-red-100 text-red-700'
            : alert.severity ===
                'high'
              ? 'bg-orange-100 text-orange-700'
              : alert.severity ===
                  'warning'
                ? 'bg-amber-100 text-amber-700'
                : 'bg-neutral-100 text-neutral-700'

    return (
        <span
            className={[
                'inline-flex rounded-full px-2.5 py-1 text-xs font-semibold',
                classes,
            ].join(
                ' ',
            )}
        >
            {alert.label}
        </span>
    )
}

function StatusBadge({
    value,
}: {
    value: string
}) {
    return (
        <span className="inline-flex rounded-full bg-neutral-100 px-2.5 py-1 text-xs font-semibold capitalize text-neutral-700">
            {value.replace(
                /_/g,
                ' ',
            )}
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
                'admin.reports.operations.inventory',
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
                products
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
                    className="rounded-lg border border-neutral-300 px-3 py-2 text-sm disabled:cursor-not-allowed disabled:opacity-50"
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
                    className="rounded-lg border border-neutral-300 px-3 py-2 text-sm disabled:cursor-not-allowed disabled:opacity-50"
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
    row: Row,
): string {
    if (
        row.alert.severity ===
        'critical'
    ) {
        return 'bg-red-50/70'
    }

    if (
        row.alert.severity ===
        'high'
    ) {
        return 'bg-orange-50/60'
    }

    if (
        row.alert.requires_attention
    ) {
        return 'bg-amber-50/40'
    }

    return 'bg-white'
}

function rowClass(
    row: Row,
): string {
    if (
        row.alert.severity ===
        'critical'
    ) {
        return 'bg-red-50/70 hover:bg-red-50'
    }

    if (
        row.alert.severity ===
        'high'
    ) {
        return 'bg-orange-50/60 hover:bg-orange-50'
    }

    if (
        row.alert.requires_attention
    ) {
        return 'bg-amber-50/40 hover:bg-amber-50'
    }

    return 'hover:bg-neutral-50'
}

function formatDate(
    value: string,
): string {
    return new Intl.DateTimeFormat(
        'en-BD',
        {
            dateStyle:
                'medium',

            timeStyle:
                'short',
        },
    ).format(
        new Date(
            value,
        ),
    )
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