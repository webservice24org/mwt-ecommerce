import { Link, router } from '@inertiajs/react'
import { FormEvent, useMemo, useState } from 'react'

import AdminLayout from '@/Layouts/Admin/AdminLayout'

type Option = {
    value: string
    label: string
}

type Filters = {
    preset: string
    from: string
    to: string
    statuses: string[]
    fulfilment_statuses: string[]
    payment_statuses: string[]
    search: string | null
    per_page: number
}

type Summary = {
    orders_count: number
    cod_orders_count: number

    paid_orders_count: number
    paid_total: number

    due_orders_count: number
    due_total: number

    unpaid_orders_count: number
    unpaid_total: number

    delivered_due_orders_count: number
    delivered_due_total: number

    delivered_unpaid_orders_count: number
    delivered_unpaid_total: number

    follow_up_orders_count: number
    critical_risk_orders_count: number
    high_risk_orders_count: number
}

type CollectionRisk = {
    state: string
    label: string
    severity: string
    priority: number
    requires_follow_up: boolean
}

type Row = {
    order_id: number
    order_number: string

    customer_id: number | null
    customer_name: string

    status: string
    fulfilment_status: string

    payment_method: string
    payment_status: string

    currency_code: string

    grand_total: number
    amount_due: number

    payment_due_at: string | null
    paid_at: string | null
    placed_at: string

    age_hours: number
    age_days: number

    collection_risk: CollectionRisk
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
        fulfilment_statuses: Option[]
        payment_statuses: Option[]
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
        useState(filters.search ?? '')

    const [statuses, setStatuses] =
        useState<string[]>(
            filters.statuses,
        )

    const [
        fulfilmentStatuses,
        setFulfilmentStatuses,
    ] = useState<string[]>(
        filters.fulfilment_statuses,
    )

    const [
        paymentStatuses,
        setPaymentStatuses,
    ] = useState<string[]>(
        filters.payment_statuses,
    )

    const [perPage, setPerPage] =
        useState(
            filters.per_page,
        )

    const query = useMemo(
        () => ({
            preset:
                filters.preset,

            from:
                filters.from,

            to:
                filters.to,

            status:
                statuses,

            fulfilment_status:
                fulfilmentStatuses,

            payment_status:
                paymentStatuses,

            search:
                search || undefined,

            per_page:
                perPage,
        }),
        [
            filters.from,
            filters.preset,
            filters.to,
            fulfilmentStatuses,
            paymentStatuses,
            perPage,
            search,
            statuses,
        ],
    )

    function submit(
        event: FormEvent,
    ) {
        event.preventDefault()

        router.get(
            route(
                'admin.reports.operations.cod',
            ),
            query,
            {
                preserveState: true,
                preserveScroll: true,
                replace: true,
            },
        )
    }

    function clearFilters() {
        setSearch('')
        setStatuses([])
        setFulfilmentStatuses([])
        setPaymentStatuses([])
        setPerPage(25)

        router.get(
            route(
                'admin.reports.operations.cod',
            ),
            {
                preset:
                    filters.preset,

                from:
                    filters.from,

                to:
                    filters.to,

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


    function navigate(
        overrides: Record<
            string,
            unknown
        > = {},
    ) {
        router.get(
            route(
                'admin.reports.operations.cod',
            ),
            {
                ...query,
                ...overrides,
                page: undefined,
            },
            {
                preserveState: true,
                preserveScroll: true,
                replace: true,
            },
        )
    }

    function applyQuickWorkflow(
        nextPaymentStatuses: string[],
        nextFulfilmentStatuses?: string[],
    ) {
        const fulfilment =
            nextFulfilmentStatuses ??
            []

        setPaymentStatuses(
            nextPaymentStatuses,
        )
        setFulfilmentStatuses(
            fulfilment,
        )

        navigate({
            payment_status:
                nextPaymentStatuses,

            fulfilment_status:
                fulfilment,
        })
    }

    function removeStatus(
        value: string,
    ) {
        const next =
            statuses.filter(
                (status) =>
                    status !== value,
            )

        setStatuses(next)

        navigate({
            status:
                next,
        })
    }

    function removeFulfilmentStatus(
        value: string,
    ) {
        const next =
            fulfilmentStatuses.filter(
                (status) =>
                    status !== value,
            )

        setFulfilmentStatuses(
            next,
        )

        navigate({
            fulfilment_status:
                next,
        })
    }

    function removePaymentStatus(
        value: string,
    ) {
        const next =
            paymentStatuses.filter(
                (status) =>
                    status !== value,
            )

        setPaymentStatuses(
            next,
        )

        navigate({
            payment_status:
                next,
        })
    }

    function clearSearch() {
        setSearch('')

        navigate({
            search:
                undefined,
        })
    }

    return (
        <AdminLayout
            title="COD Operations Report"
            description="Monitor COD collection exposure, payment status, ageing, and collection-risk follow-up."
        >
            <div className="space-y-6">
                <SummaryCards
                    summary={summary}
                />

                <QuickWorkflows
                    onApply={
                        applyQuickWorkflow
                    }
                />

                <form
                    onSubmit={submit}
                    className="rounded-xl border border-neutral-200 bg-white p-4"
                >
                    <div className="grid gap-4 lg:grid-cols-4">
                        <div>
                            <label className="mb-1 block text-sm font-medium text-neutral-700">
                                Search
                            </label>

                            <input
                                value={search}
                                onChange={(event) =>
                                    setSearch(
                                        event.target.value,
                                    )
                                }
                                placeholder="Order number or customer"
                                autoComplete="off"
                                className="w-full rounded-lg border border-neutral-300 px-3 py-2 text-sm"
                            />

                            {search !== '' && (
                                <button
                                    type="button"
                                    onClick={
                                        clearSearch
                                    }
                                    className="mt-2 text-xs font-semibold text-neutral-600 underline-offset-4 hover:underline"
                                >
                                    Clear search
                                </button>
                            )}
                        </div>

                        <MultiSelect
                            label="Order status"
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
                            label="Fulfilment"
                            options={
                                options.fulfilment_statuses
                            }
                            values={
                                fulfilmentStatuses
                            }
                            onChange={
                                setFulfilmentStatuses
                            }
                        />

                        <MultiSelect
                            label="Payment status"
                            options={
                                options.payment_statuses
                            }
                            values={
                                paymentStatuses
                            }
                            onChange={
                                setPaymentStatuses
                            }
                        />
                    </div>

                    <div className="mt-4 flex flex-wrap items-center gap-3">
                        <select
                            value={perPage}
                            onChange={(event) =>
                                setPerPage(
                                    Number(
                                        event.target.value,
                                    ),
                                )
                            }
                            className="rounded-lg border border-neutral-300 px-3 py-2 text-sm"
                        >
                            {[10, 25, 50, 100].map(
                                (value) => (
                                    <option
                                        key={value}
                                        value={value}
                                    >
                                        {value} per page
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
                <div className="flex flex-wrap items-center gap-2">
                    <a
                        href={route(
                            'admin.reports.operations.cod.csv',
                            query,
                        )}
                        className="inline-flex items-center justify-center rounded-lg border border-neutral-300 bg-white px-4 py-2 text-sm font-semibold text-neutral-700 transition hover:bg-neutral-50"
                    >
                        Export CSV
                    </a>

                    <Link
                        href={route(
                            'admin.orders.index',
                        )}
                        className="inline-flex items-center justify-center rounded-lg border border-neutral-300 bg-white px-4 py-2 text-sm font-semibold text-neutral-700 transition hover:bg-neutral-50"
                    >
                        Open Orders
                    </Link>
                </div>

                <ActiveFilters
                    search={search}
                    statuses={statuses}
                    fulfilmentStatuses={
                        fulfilmentStatuses
                    }
                    paymentStatuses={
                        paymentStatuses
                    }
                    perPage={perPage}
                    options={options}
                    onRemoveStatus={
                        removeStatus
                    }
                    onRemoveFulfilmentStatus={
                        removeFulfilmentStatus
                    }
                    onRemovePaymentStatus={
                        removePaymentStatus
                    }
                    onClearSearch={
                        clearSearch
                    }
                    onClearAll={
                        clearFilters
                    }
                />

                <div className="overflow-hidden rounded-xl border border-neutral-200 bg-white">
                    <div className="divide-y divide-neutral-100 lg:hidden">
                        {rows.length ===
                        0 ? (
                            <div className="px-4 py-10 text-center text-sm text-neutral-500">
                                No COD orders found for the selected filters.
                            </div>
                        ) : (
                            rows.map(
                                (
                                    row,
                                ) => (
                                    <MobileOrderCard
                                        key={
                                            row.order_id
                                        }
                                        row={
                                            row
                                        }
                                    />
                                ),
                            )
                        )}
                    </div>

                    <div className="hidden overflow-x-auto lg:block">
                        <table className="min-w-full divide-y divide-neutral-200">
                            <thead className="bg-neutral-50">
                                <tr>
                                    <Th>
                                        Order
                                    </Th>

                                    <Th>
                                        Customer
                                    </Th>

                                    <Th>
                                        Payment
                                    </Th>

                                    <Th>
                                        Fulfilment
                                    </Th>

                                    <Th>
                                        Total
                                    </Th>

                                    <Th>
                                        Amount due
                                    </Th>

                                    <Th>
                                        Age
                                    </Th>

                                    <Th>
                                        Collection risk
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
                                                9
                                            }
                                            className="px-4 py-10 text-center text-sm text-neutral-500"
                                        >
                                            No COD orders found for the selected filters.
                                        </td>
                                    </tr>
                                ) : (
                                    rows.map(
                                        (
                                            row,
                                        ) => (
                                            <tr
                                                key={
                                                    row.order_id
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
                                                            row.order_number
                                                        }
                                                    </div>

                                                    <div className="mt-1 text-xs text-neutral-500">
                                                        {formatDate(
                                                            row.placed_at,
                                                        )}
                                                    </div>
                                                </Td>

                                                <Td>
                                                    {
                                                        row.customer_name
                                                    }
                                                </Td>

                                                <Td>
                                                    <StatusBadge
                                                        value={
                                                            row.payment_status
                                                        }
                                                    />
                                                </Td>

                                                <Td>
                                                    <StatusBadge
                                                        value={
                                                            row.fulfilment_status
                                                        }
                                                    />
                                                </Td>

                                                <Td>
                                                    {formatMoney(
                                                        row.grand_total,
                                                        row.currency_code,
                                                    )}
                                                </Td>

                                                <Td>
                                                    <span className="font-semibold">
                                                        {formatMoney(
                                                            row.amount_due,
                                                            row.currency_code,
                                                        )}
                                                    </span>
                                                </Td>

                                                <Td>
                                                    <div>
                                                        {
                                                            row.age_days
                                                        }{' '}
                                                        days
                                                    </div>

                                                    <div className="text-xs text-neutral-500">
                                                        {
                                                            row.age_hours
                                                        }{' '}
                                                        hours
                                                    </div>
                                                </Td>

                                                <Td>
                                                    <RiskBadge
                                                        risk={
                                                            row.collection_risk
                                                        }
                                                    />
                                                </Td>

                                                <Td>
                                                    <Link
                                                        href={route(
                                                            'admin.orders.show',
                                                            row.order_id,
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

function QuickWorkflows({
    onApply,
}: {
    onApply: (
        paymentStatuses: string[],
        fulfilmentStatuses?: string[],
    ) => void
}) {
    const workflows = [
        {
            label: 'Due',
            paymentStatuses: [
                'due',
            ],
        },
        {
            label: 'Unpaid',
            paymentStatuses: [
                'unpaid',
            ],
        },
        {
            label:
                'Delivered + Due',
            paymentStatuses: [
                'due',
            ],
            fulfilmentStatuses: [
                'delivered',
            ],
        },
        {
            label:
                'Delivered + Unpaid',
            paymentStatuses: [
                'unpaid',
            ],
            fulfilmentStatuses: [
                'delivered',
            ],
        },
        {
            label: 'Paid',
            paymentStatuses: [
                'paid',
            ],
        },
    ] as const

    return (
        <div className="rounded-xl border border-neutral-200 bg-white p-4">
            <div className="mb-3">
                <div className="text-sm font-semibold text-neutral-900">
                    Quick workflows
                </div>

                <div className="mt-1 text-xs text-neutral-500">
                    Jump directly to common COD collection queues.
                </div>
            </div>

            <div className="flex flex-wrap gap-2">
                {workflows.map(
                    (workflow) => (
                        <button
                            key={
                                workflow.label
                            }
                            type="button"
                            onClick={() =>
                                onApply(
                                    [
                                        ...workflow.paymentStatuses,
                                    ],
                                    'fulfilmentStatuses' in
                                    workflow
                                        ? [
                                              ...workflow.fulfilmentStatuses,
                                          ]
                                        : undefined,
                                )
                            }
                            className="rounded-lg border border-neutral-300 bg-white px-3 py-2 text-sm font-semibold text-neutral-700 transition hover:border-neutral-400 hover:bg-neutral-50"
                        >
                            {
                                workflow.label
                            }
                        </button>
                    ),
                )}
            </div>
        </div>
    )
}

function ActiveFilters({
    search,
    statuses,
    fulfilmentStatuses,
    paymentStatuses,
    perPage,
    options,
    onRemoveStatus,
    onRemoveFulfilmentStatus,
    onRemovePaymentStatus,
    onClearSearch,
    onClearAll,
}: {
    search: string
    statuses: string[]
    fulfilmentStatuses: string[]
    paymentStatuses: string[]
    perPage: number
    options: Props['options']
    onRemoveStatus: (
        value: string,
    ) => void
    onRemoveFulfilmentStatus: (
        value: string,
    ) => void
    onRemovePaymentStatus: (
        value: string,
    ) => void
    onClearSearch: () => void
    onClearAll: () => void
}) {
    const hasFilters =
        search !== '' ||
        statuses.length > 0 ||
        fulfilmentStatuses.length >
            0 ||
        paymentStatuses.length > 0 ||
        perPage !== 25

    if (!hasFilters) {
        return null
    }

    return (
        <div className="rounded-xl border border-neutral-200 bg-white p-4">
            <div className="flex flex-wrap items-center justify-between gap-3">
                <div>
                    <div className="text-sm font-semibold text-neutral-900">
                        Active filters
                    </div>

                    <div className="mt-1 text-xs text-neutral-500">
                        Remove individual filters without losing the rest of the collection workflow.
                    </div>
                </div>

                <button
                    type="button"
                    onClick={
                        onClearAll
                    }
                    className="text-xs font-semibold text-neutral-600 underline-offset-4 hover:underline"
                >
                    Clear all
                </button>
            </div>

            <div className="mt-3 flex flex-wrap gap-2">
                {search !== '' && (
                    <FilterChip
                        label={`Search: ${search}`}
                        onRemove={
                            onClearSearch
                        }
                    />
                )}

                {statuses.map(
                    (value) => (
                        <FilterChip
                            key={`order-${value}`}
                            label={`Order: ${optionLabel(
                                options.statuses,
                                value,
                            )}`}
                            onRemove={() =>
                                onRemoveStatus(
                                    value,
                                )
                            }
                        />
                    ),
                )}

                {fulfilmentStatuses.map(
                    (value) => (
                        <FilterChip
                            key={`fulfilment-${value}`}
                            label={`Fulfilment: ${optionLabel(
                                options.fulfilment_statuses,
                                value,
                            )}`}
                            onRemove={() =>
                                onRemoveFulfilmentStatus(
                                    value,
                                )
                            }
                        />
                    ),
                )}

                {paymentStatuses.map(
                    (value) => (
                        <FilterChip
                            key={`payment-${value}`}
                            label={`Payment: ${optionLabel(
                                options.payment_statuses,
                                value,
                            )}`}
                            onRemove={() =>
                                onRemovePaymentStatus(
                                    value,
                                )
                            }
                        />
                    ),
                )}

                {perPage !== 25 && (
                    <span className="inline-flex items-center rounded-full bg-neutral-100 px-3 py-1 text-xs font-semibold text-neutral-700">
                        {perPage} per page
                    </span>
                )}
            </div>
        </div>
    )
}

function FilterChip({
    label,
    onRemove,
}: {
    label: string
    onRemove: () => void
}) {
    return (
        <span className="inline-flex items-center gap-2 rounded-full bg-neutral-100 px-3 py-1 text-xs font-semibold text-neutral-700">
            <span>
                {label}
            </span>

            <button
                type="button"
                onClick={onRemove}
                aria-label={`Remove ${label}`}
                className="rounded-full text-neutral-500 transition hover:text-neutral-900"
            >
                ×
            </button>
        </span>
    )
}

function MobileOrderCard({
    row,
}: {
    row: Row
}) {
    return (
        <div
            className={[
                'p-4',
                rowClass(row),
            ].join(' ')}
        >
            <div className="flex items-start justify-between gap-3">
                <div className="min-w-0">
                    <div className="truncate font-semibold text-neutral-900">
                        {
                            row.order_number
                        }
                    </div>

                    <div className="mt-1 text-xs text-neutral-500">
                        {
                            formatDate(
                                row.placed_at,
                            )
                        }
                    </div>
                </div>

                <RiskBadge
                    risk={
                        row.collection_risk
                    }
                />
            </div>

            <div className="mt-4 grid grid-cols-2 gap-3 text-sm">
                <MobileField
                    label="Customer"
                    value={
                        row.customer_name
                    }
                />

                <MobileField
                    label="Age"
                    value={`${row.age_days} days`}
                />

                <MobileField
                    label="Payment"
                    value={
                        row.payment_status.replace(
                            /_/g,
                            ' ',
                        )
                    }
                />

                <MobileField
                    label="Fulfilment"
                    value={
                        row.fulfilment_status.replace(
                            /_/g,
                            ' ',
                        )
                    }
                />

                <MobileField
                    label="Total"
                    value={
                        formatMoney(
                            row.grand_total,
                            row.currency_code,
                        )
                    }
                />

                <MobileField
                    label="Amount due"
                    value={
                        formatMoney(
                            row.amount_due,
                            row.currency_code,
                        )
                    }
                />
            </div>

            <div className="mt-4">
                <Link
                    href={route(
                        'admin.orders.show',
                        row.order_id,
                    )}
                    className="inline-flex rounded-lg border border-neutral-300 px-3 py-2 text-sm font-semibold text-neutral-800 transition hover:bg-neutral-50"
                >
                    Review order
                </Link>
            </div>
        </div>
    )
}

function MobileField({
    label,
    value,
}: {
    label: string
    value: string
}) {
    return (
        <div>
            <div className="text-xs text-neutral-500">
                {label}
            </div>

            <div className="mt-1 break-words font-medium capitalize text-neutral-800">
                {value}
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
            (option) =>
                option.value === value,
        )?.label ??
        value.replace(
            /_/g,
            ' ',
        )
    )
}

function SummaryCards({
    summary,
}: {
    summary: Summary
}) {
    const cards = [
        [
            'COD orders',
            summary.cod_orders_count,
        ],

        [
            'Paid',
            summary.paid_orders_count,
        ],

        [
            'Due',
            summary.due_orders_count,
        ],

        [
            'Unpaid',
            summary.unpaid_orders_count,
        ],

        [
            'Follow-up',
            summary.follow_up_orders_count,
        ],

        [
            'Critical risk',
            summary.critical_risk_orders_count,
        ],

        [
            'High risk',
            summary.high_risk_orders_count,
        ],
    ] as const

    return (
        <div className="grid gap-4 sm:grid-cols-2 xl:grid-cols-4">
            {cards.map(
                ([label, value]) => (
                    <div
                        key={label}
                        className="rounded-xl border border-neutral-200 bg-white p-4"
                    >
                        <div className="text-sm text-neutral-500">
                            {label}
                        </div>

                        <div className="mt-2 text-2xl font-bold text-neutral-900">
                            {value}
                        </div>
                    </div>
                ),
            )}

            <MoneyCard
                label="Paid total"
                value={
                    summary.paid_total
                }
            />

            <MoneyCard
                label="Due total"
                value={
                    summary.due_total
                }
            />

            <MoneyCard
                label="Unpaid total"
                value={
                    summary.unpaid_total
                }
            />

            <MoneyCard
                label="Delivered due"
                value={
                    summary.delivered_due_total
                }
            />
        </div>
    )
}

function MoneyCard({
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
                {formatMoney(
                    value,
                    'BDT',
                )}
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
                onChange={(event) =>
                    onChange(
                        Array.from(
                            event
                                .currentTarget
                                .selectedOptions,
                        ).map(
                            (option) =>
                                option.value,
                        ),
                    )
                }
                className="min-h-24 w-full rounded-lg border border-neutral-300 px-3 py-2 text-sm"
            >
                {options.map(
                    (option) => (
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

function RiskBadge({
    risk,
}: {
    risk: CollectionRisk
}) {
    const classes =
        risk.severity ===
        'critical'
            ? 'bg-red-100 text-red-700'
            : risk.severity ===
                'high'
              ? 'bg-orange-100 text-orange-700'
              : risk.severity ===
                  'warning'
                ? 'bg-amber-100 text-amber-700'
                : risk.severity ===
                    'info'
                  ? 'bg-blue-100 text-blue-700'
                  : 'bg-neutral-100 text-neutral-700'

    return (
        <span
            className={[
                'inline-flex rounded-full px-2.5 py-1 text-xs font-semibold',
                classes,
            ].join(' ')}
        >
            {risk.label}
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
                'admin.reports.operations.cod',
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

function rowClass(
    row: Row,
): string {
    if (
        row.collection_risk.severity ===
        'critical'
    ) {
        return 'bg-red-50/70 hover:bg-red-50'
    }

    if (
        row.collection_risk.severity ===
        'high'
    ) {
        return 'bg-orange-50/60 hover:bg-orange-50'
    }

    if (
        row.collection_risk.requires_follow_up
    ) {
        return 'bg-amber-50/40 hover:bg-amber-50'
    }

    return 'hover:bg-neutral-50'
}

function formatMoney(
    value: number,
    currency: string,
): string {
    return new Intl.NumberFormat(
        'en-BD',
        {
            style: 'currency',
            currency,
            minimumFractionDigits: 2,
        },
    ).format(
        value / 100,
    )
}

function formatDate(
    value: string,
): string {
    return new Intl.DateTimeFormat(
        'en-BD',
        {
            dateStyle: 'medium',
            timeStyle: 'short',
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