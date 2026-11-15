import {
    Link,
    router,
} from '@inertiajs/react'
import {
    FormEvent,
    useMemo,
    useState,
} from 'react'

import AdminLayout from '@/Layouts/Admin/AdminLayout'

interface SelectOption {
    value: string
    label: string
}

interface Filters {
    preset: string
    from: string
    to: string
    statuses: string[]
    fulfilment_statuses: string[]
    payment_statuses: string[]
    payment_methods: string[]
    search: string | null
    per_page: number
}

interface Summary {
    orders_count: number

    pending_orders: number
    processing_orders: number
    completed_orders: number

    unfulfilled_orders: number
    fulfilment_processing_orders: number
    shipped_orders: number
    delivered_orders: number
    returned_orders: number

    due_payment_orders: number
    due_payment_total: number

    unpaid_payment_orders: number
    unpaid_payment_total: number

    attention_required_orders: number
    critical_attention_orders: number
}

interface Attention {
    state: string
    label: string
    severity: string
    priority: number
    requires_attention: boolean
}

interface OperationalOrderRow {
    order_id: number
    order_number: string
    customer_id: number | null
    customer_name: string

    status: string
    payment_status: string
    payment_method: string
    fulfilment_status: string

    currency_code: string

    grand_total: number
    amount_due: number

    payment_due_at: string | null
    paid_at: string | null
    placed_at: string

    age_hours: number
    age_days: number

    attention: Attention
}

interface Pagination {
    current_page: number
    last_page: number
    per_page: number
    total: number
    next_page_url: string | null
    previous_page_url: string | null
}

interface Options {
    presets: SelectOption[]
    statuses: SelectOption[]
    fulfilment_statuses: SelectOption[]
    payment_statuses: SelectOption[]
    payment_methods: SelectOption[]
}

interface Props {
    filters: Filters
    summary: Summary
    rows: OperationalOrderRow[]
    pagination: Pagination
    options: Options
}

export default function Index({
    filters,
    summary,
    rows,
    pagination,
    options,
}: Props) {
    const [preset, setPreset] =
        useState(
            filters.preset,
        )

    const [from, setFrom] =
        useState(
            filters.from,
        )

    const [to, setTo] =
        useState(
            filters.to,
        )

    const [search, setSearch] =
        useState(
            filters.search ?? '',
        )

    const [
        statuses,
        setStatuses,
    ] =
        useState<string[]>(
            filters.statuses,
        )

    const [
        fulfilmentStatuses,
        setFulfilmentStatuses,
    ] =
        useState<string[]>(
            filters.fulfilment_statuses,
        )

    const [
        paymentStatuses,
        setPaymentStatuses,
    ] =
        useState<string[]>(
            filters.payment_statuses,
        )

    const [
        paymentMethods,
        setPaymentMethods,
    ] =
        useState<string[]>(
            filters.payment_methods,
        )

    const [perPage, setPerPage] =
        useState(
            String(
                filters.per_page,
            ),
        )

    const query =
        useMemo(
            () => ({
                preset,
                from:
                    preset ===
                    'custom'
                        ? from
                        : undefined,

                to:
                    preset ===
                    'custom'
                        ? to
                        : undefined,

                status:
                    statuses.length >
                    0
                        ? statuses
                        : undefined,

                fulfilment_status:
                    fulfilmentStatuses.length >
                    0
                        ? fulfilmentStatuses
                        : undefined,

                payment_status:
                    paymentStatuses.length >
                    0
                        ? paymentStatuses
                        : undefined,

                payment_method:
                    paymentMethods.length >
                    0
                        ? paymentMethods
                        : undefined,

                search:
                    search.trim() !==
                    ''
                        ? search.trim()
                        : undefined,

                per_page:
                    Number(
                        perPage,
                    ),
            }),
            [
                preset,
                from,
                to,
                statuses,
                fulfilmentStatuses,
                paymentStatuses,
                paymentMethods,
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
                'admin.reports.operations.orders',
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
        router.get(
            route(
                'admin.reports.operations.orders',
            ),
        )
    }

    function goToPage(
        page: number,
    ) {
        router.get(
            route(
                'admin.reports.operations.orders',
            ),
            {
                ...query,
                page,
            },
            {
                preserveState: true,
                preserveScroll: true,
                replace: true,
            },
        )
    }

    return (
        <AdminLayout title="Operational Orders Report" description="Monitor order processing, fulfilment, payment collection, ageing, and orders requiring operational attention.">

            <div className="space-y-6">
                <header className="flex flex-col gap-4 lg:flex-row lg:items-start lg:justify-between">
                    

                    <div className="flex flex-wrap gap-2">
                        <a
                            href={buildCsvExportUrl(
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
                </header>

                <SummaryCards
                    summary={
                        summary
                    }
                />

                <OperationalQuickFilters />

                <form
                    onSubmit={
                        submit
                    }
                    className="rounded-xl border border-neutral-200 bg-white p-4 shadow-sm sm:p-5"
                >
                    <div className="grid gap-4 md:grid-cols-2 xl:grid-cols-4">
                        <Field
                            label="Date range"
                        >
                            <select
                                value={
                                    preset
                                }
                                onChange={(
                                    event,
                                ) =>
                                    setPreset(
                                        event
                                            .target
                                            .value,
                                    )
                                }
                                className={inputClass}
                            >
                                {options.presets.map(
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
                        </Field>

                        {preset ===
                            'custom' && (
                            <>
                                <Field label="From">
                                    <input
                                        type="date"
                                        value={
                                            from
                                        }
                                        onChange={(
                                            event,
                                        ) =>
                                            setFrom(
                                                event
                                                    .target
                                                    .value,
                                            )
                                        }
                                        className={
                                            inputClass
                                        }
                                    />
                                </Field>

                                <Field label="To">
                                    <input
                                        type="date"
                                        value={
                                            to
                                        }
                                        onChange={(
                                            event,
                                        ) =>
                                            setTo(
                                                event
                                                    .target
                                                    .value,
                                            )
                                        }
                                        className={
                                            inputClass
                                        }
                                    />
                                </Field>
                            </>
                        )}

                        <Field label="Search">
                            <input
                                type="search"
                                value={
                                    search
                                }
                                onChange={(
                                    event,
                                ) =>
                                    setSearch(
                                        event
                                            .target
                                            .value,
                                    )
                                }
                                placeholder="Order number or customer"
                                autoComplete="off"
                                className={
                                    inputClass
                                }
                            />

                            {search !==
                                '' && (
                                <button
                                    type="button"
                                    onClick={() =>
                                        setSearch(
                                            '',
                                        )
                                    }
                                    className="mt-2 text-xs font-semibold text-neutral-500 transition hover:text-neutral-900"
                                >
                                    Clear search
                                </button>
                            )}
                        </Field>

                        <Field label="Per page">
                            <select
                                value={
                                    perPage
                                }
                                onChange={(
                                    event,
                                ) =>
                                    setPerPage(
                                        event
                                            .target
                                            .value,
                                    )
                                }
                                className={
                                    inputClass
                                }
                            >
                                {[10, 25, 50, 100].map(
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
                                            }
                                        </option>
                                    ),
                                )}
                            </select>
                        </Field>
                    </div>

                    <div className="mt-5 grid gap-5 lg:grid-cols-2 xl:grid-cols-4">
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

                        <MultiSelect
                            label="Payment method"
                            options={
                                options.payment_methods
                            }
                            values={
                                paymentMethods
                            }
                            onChange={
                                setPaymentMethods
                            }
                        />
                    </div>

                    <div className="mt-5 flex flex-wrap gap-3">
                        <button
                            type="submit"
                            className="rounded-lg bg-neutral-900 px-4 py-2 text-sm font-semibold text-white transition hover:bg-neutral-800"
                        >
                            Apply filters
                        </button>

                        <button
                            type="button"
                            onClick={
                                clearFilters
                            }
                            className="rounded-lg border border-neutral-300 bg-white px-4 py-2 text-sm font-semibold text-neutral-700 transition hover:bg-neutral-50"
                        >
                            Clear
                        </button>
                    </div>
                </form>

                <ActiveFilters
                    filters={
                        filters
                    }
                    options={
                        options
                    }
                />

                <div className="overflow-hidden rounded-xl border border-neutral-200 bg-white shadow-sm">
                    <div className="border-b border-neutral-200 px-4 py-4 sm:px-5">
                        <div className="flex flex-col gap-1 sm:flex-row sm:items-center sm:justify-between">
                            <div>
                                <h2 className="font-semibold text-neutral-950">
                                    Orders requiring operational review
                                </h2>

                                <p className="mt-1 text-sm text-neutral-500">
                                    {
                                        pagination.total
                                    }{' '}
                                    matching orders
                                </p>
                            </div>
                        </div>
                    </div>

                    {rows.length >
                    0 ? (
                        <>
                            <div className="hidden overflow-x-auto lg:block">
                                <table className="min-w-full divide-y divide-neutral-200 text-sm">
                                    <thead className="bg-neutral-50">
                                        <tr>
                                            <TableHead>
                                                Order
                                            </TableHead>
                                            <TableHead>
                                                Customer
                                            </TableHead>
                                            <TableHead>
                                                Status
                                            </TableHead>
                                            <TableHead>
                                                Fulfilment
                                            </TableHead>
                                            <TableHead>
                                                Payment
                                            </TableHead>
                                            <TableHead>
                                                Age
                                            </TableHead>
                                            <TableHead>
                                                Attention
                                            </TableHead>
                                            <TableHead align="right">
                                                Total
                                            </TableHead>
                                        </tr>
                                    </thead>

                                    <tbody className="divide-y divide-neutral-100">
                                        {rows.map(
                                            (
                                                row,
                                            ) => (
                                                <OrderTableRow
                                                    key={
                                                        row.order_id
                                                    }
                                                    row={
                                                        row
                                                    }
                                                />
                                            ),
                                        )}
                                    </tbody>
                                </table>
                            </div>

                            <div className="divide-y divide-neutral-100 lg:hidden">
                                {rows.map(
                                    (
                                        row,
                                    ) => (
                                        <OrderMobileCard
                                            key={
                                                row.order_id
                                            }
                                            row={
                                                row
                                            }
                                        />
                                    ),
                                )}
                            </div>
                        </>
                    ) : (
                        <div className="px-5 py-14 text-center">
                            <h3 className="font-semibold text-neutral-900">
                                No matching orders
                            </h3>

                            <p className="mt-1 text-sm text-neutral-500">
                                Try changing the selected
                                date range or operational
                                filters.
                            </p>
                        </div>
                    )}

                    <PaginationControls
                        pagination={
                            pagination
                        }
                        onPageChange={
                            goToPage
                        }
                    />
                </div>
            </div>
        </AdminLayout>
    )
}

function SummaryCards({
    summary,
}: {
    summary: Summary
}) {
    const cards = [
        {
            label:
                'Orders',
            value:
                formatNumber(
                    summary.orders_count,
                ),
            helper:
                'Matching current filters',
        },
        {
            label:
                'Attention required',
            value:
                formatNumber(
                    summary.attention_required_orders,
                ),
            helper:
                `${formatNumber(
                    summary.critical_attention_orders,
                )} critical`,
        },
        {
            label:
                'Pending',
            value:
                formatNumber(
                    summary.pending_orders,
                ),
            helper:
                `${formatNumber(
                    summary.processing_orders,
                )} processing`,
        },
        {
            label:
                'Unfulfilled',
            value:
                formatNumber(
                    summary.unfulfilled_orders,
                ),
            helper:
                `${formatNumber(
                    summary.shipped_orders,
                )} shipped`,
        },
        {
            label:
                'Delivered',
            value:
                formatNumber(
                    summary.delivered_orders,
                ),
            helper:
                `${formatNumber(
                    summary.returned_orders,
                )} returned`,
        },
        {
            label:
                'Due payments',
            value:
                formatMoney(
                    summary.due_payment_total,
                ),
            helper:
                `${formatNumber(
                    summary.due_payment_orders,
                )} orders`,
        },
        {
            label:
                'Unpaid',
            value:
                formatMoney(
                    summary.unpaid_payment_total,
                ),
            helper:
                `${formatNumber(
                    summary.unpaid_payment_orders,
                )} orders`,
        },
        {
            label:
                'Completed',
            value:
                formatNumber(
                    summary.completed_orders,
                ),
            helper:
                `${formatNumber(
                    summary.fulfilment_processing_orders,
                )} fulfilment processing`,
        },
    ]

    return (
        <section className="grid gap-4 sm:grid-cols-2 xl:grid-cols-4">
            {cards.map(
                (
                    card,
                ) => (
                    <div
                        key={
                            card.label
                        }
                        className="rounded-xl border border-neutral-200 bg-white p-4 shadow-sm"
                    >
                        <p className="text-sm font-medium text-neutral-500">
                            {
                                card.label
                            }
                        </p>

                        <p className="mt-2 text-2xl font-bold tracking-tight text-neutral-950">
                            {
                                card.value
                            }
                        </p>

                        <p className="mt-1 text-xs text-neutral-500">
                            {
                                card.helper
                            }
                        </p>
                    </div>
                ),
            )}
        </section>
    )
}

function OrderTableRow({
    row,
}: {
    row: OperationalOrderRow
}) {
    return (
        <tr
            className={[
                'align-top transition',
                rowClass(
                    row,
                ),
            ].join(
                ' ',
            )}
        >
            <td className="px-4 py-4">
                <Link
                    href={route(
                        'admin.orders.show',
                        row.order_id,
                    )}
                    className="font-semibold text-neutral-950 hover:underline"
                >
                    {
                        row.order_number
                    }
                </Link>

                <div className="mt-1 flex flex-wrap items-center gap-2">
                    <p className="text-xs text-neutral-500">
                        {
                            formatDateTime(
                                row.placed_at,
                            )
                        }
                    </p>

                    <Link
                        href={route(
                            'admin.orders.show',
                            row.order_id,
                        )}
                        className="text-xs font-semibold text-neutral-500 transition hover:text-neutral-900"
                    >
                        Review
                    </Link>
                </div>
            </td>

            <td className="px-4 py-4 text-neutral-700">
                {
                    row.customer_name
                }
            </td>

            <td className="px-4 py-4">
                <Badge
                    value={
                        row.status
                    }
                />
            </td>

            <td className="px-4 py-4">
                <Badge
                    value={
                        row.fulfilment_status
                    }
                />
            </td>

            <td className="px-4 py-4">
                <div className="space-y-1">
                    <Badge
                        value={
                            row.payment_status
                        }
                    />

                    {row.amount_due >
                        0 && (
                        <p className="text-xs font-medium text-amber-700">
                            Due{' '}
                            {formatMoney(
                                row.amount_due,
                            )}
                        </p>
                    )}
                </div>
            </td>

            <td className="px-4 py-4 text-neutral-700">
                <p className="font-medium">
                    {
                        formatAge(
                            row,
                        )
                    }
                </p>

                <p className="mt-1 text-xs text-neutral-500">
                    {
                        row.age_hours
                    }{' '}
                    hours
                </p>
            </td>

            <td className="px-4 py-4">
                <AttentionBadge
                    attention={
                        row.attention
                    }
                />
            </td>

            <td className="px-4 py-4 text-right font-semibold text-neutral-950">
                {
                    formatMoney(
                        row.grand_total,
                    )
                }
            </td>
        </tr>
    )
}

function OrderMobileCard({
    row,
}: {
    row: OperationalOrderRow
}) {
    return (
        <article
            className={[
                'space-y-4 p-4 transition',
                rowClass(
                    row,
                ),
            ].join(
                ' ',
            )}
        >
            <div className="flex items-start justify-between gap-4">
                <div className="min-w-0">
                    <Link
                        href={route(
                            'admin.orders.show',
                            row.order_id,
                        )}
                        className="font-semibold text-neutral-950 hover:underline"
                    >
                        {
                            row.order_number
                        }
                    </Link>

                    <p className="mt-1 truncate text-sm text-neutral-600">
                        {
                            row.customer_name
                        }
                    </p>
                </div>

                <p className="shrink-0 font-semibold text-neutral-950">
                    {
                        formatMoney(
                            row.grand_total,
                        )
                    }
                </p>
            </div>

            <AttentionBadge
                attention={
                    row.attention
                }
            />

            <div className="grid grid-cols-2 gap-3 text-sm">
                <MobileValue
                    label="Order"
                    value={
                        row.status
                    }
                />

                <MobileValue
                    label="Fulfilment"
                    value={
                        row.fulfilment_status
                    }
                />

                <MobileValue
                    label="Payment"
                    value={
                        row.payment_status
                    }
                />

                <MobileValue
                    label="Age"
                    value={
                        formatAge(
                            row,
                        )
                    }
                />
            </div>

            <p className="text-xs text-neutral-500">
                Placed{' '}
                {formatDateTime(
                    row.placed_at,
                )}
            </p>
        </article>
    )
}

function AttentionBadge({
    attention,
}: {
    attention: Attention
}) {
    const classes =
        attention.severity ===
        'critical'
            ? 'border-red-200 bg-red-50 text-red-700'
            : attention.severity ===
                'high'
              ? 'border-orange-200 bg-orange-50 text-orange-700'
              : attention.severity ===
                  'warning'
                ? 'border-amber-200 bg-amber-50 text-amber-700'
                : 'border-neutral-200 bg-neutral-50 text-neutral-600'

    return (
        <span
            className={[
                'inline-flex rounded-full border px-2.5 py-1 text-xs font-semibold',
                classes,
            ].join(
                ' ',
            )}
        >
            {
                attention.label
            }
        </span>
    )
}

function Badge({
    value,
}: {
    value: string
}) {
    return (
        <span className="inline-flex rounded-full border border-neutral-200 bg-neutral-50 px-2.5 py-1 text-xs font-semibold capitalize text-neutral-700">
            {humanize(
                value,
            )}
        </span>
    )
}

function MultiSelect({
    label,
    options,
    values,
    onChange,
}: {
    label: string
    options: SelectOption[]
    values: string[]
    onChange: (
        values: string[],
    ) => void
}) {
    function toggle(
        value: string,
    ) {
        onChange(
            values.includes(
                value,
            )
                ? values.filter(
                      (
                          current,
                      ) =>
                          current !==
                          value,
                  )
                : [
                      ...values,
                      value,
                  ],
        )
    }

    return (
        <fieldset>
            <legend className="text-sm font-semibold text-neutral-800">
                {
                    label
                }
            </legend>

            <div className="mt-2 flex flex-wrap gap-2">
                {options.map(
                    (
                        option,
                    ) => {
                        const active =
                            values.includes(
                                option.value,
                            )

                        return (
                            <button
                                key={
                                    option.value
                                }
                                type="button"
                                aria-pressed={
                                    active
                                }
                                onClick={() =>
                                    toggle(
                                        option.value,
                                    )
                                }
                                className={[
                                    'rounded-full border px-3 py-1.5 text-xs font-semibold transition',
                                    active
                                        ? 'border-neutral-900 bg-neutral-900 text-white'
                                        : 'border-neutral-300 bg-white text-neutral-700 hover:bg-neutral-50',
                                ].join(
                                    ' ',
                                )}
                            >
                                {
                                    option.label
                                }
                            </button>
                        )
                    },
                )}
            </div>
        </fieldset>
    )
}

function PaginationControls({
    pagination,
    onPageChange,
}: {
    pagination: Pagination
    onPageChange: (
        page: number,
    ) => void
}) {
    if (
        pagination.last_page <=
        1
    ) {
        return null
    }

    return (
        <div className="flex flex-col gap-3 border-t border-neutral-200 px-4 py-4 sm:flex-row sm:items-center sm:justify-between sm:px-5">
            <p className="text-sm text-neutral-500">
                Page{' '}
                {
                    pagination.current_page
                }{' '}
                of{' '}
                {
                    pagination.last_page
                }{' '}
                ·{' '}
                {
                    pagination.total
                }{' '}
                total
            </p>

            <div className="flex gap-2">
                <button
                    type="button"
                    disabled={
                        pagination.current_page <=
                        1
                    }
                    onClick={() =>
                        onPageChange(
                            pagination.current_page -
                                1,
                        )
                    }
                    className="rounded-lg border border-neutral-300 bg-white px-3 py-2 text-sm font-semibold text-neutral-700 transition hover:bg-neutral-50 disabled:cursor-not-allowed disabled:border-neutral-200 disabled:bg-neutral-50 disabled:text-neutral-400"
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
                        onPageChange(
                            pagination.current_page +
                                1,
                        )
                    }
                    className="rounded-lg border border-neutral-300 bg-white px-3 py-2 text-sm font-semibold text-neutral-700 transition hover:bg-neutral-50 disabled:cursor-not-allowed disabled:border-neutral-200 disabled:bg-neutral-50 disabled:text-neutral-400"
                >
                    Next
                </button>
            </div>
        </div>
    )
}

function Field({
    label,
    children,
}: {
    label: string
    children: React.ReactNode
}) {
    return (
        <label className="block">
            <span className="mb-1.5 block text-sm font-semibold text-neutral-800">
                {
                    label
                }
            </span>

            {
                children
            }
        </label>
    )
}

function TableHead({
    children,
    align = 'left',
}: {
    children: React.ReactNode
    align?:
        | 'left'
        | 'right'
}) {
    return (
        <th
            className={[
                'px-4 py-3 text-xs font-semibold uppercase tracking-wide text-neutral-500',
                align ===
                'right'
                    ? 'text-right'
                    : 'text-left',
            ].join(
                ' ',
            )}
        >
            {
                children
            }
        </th>
    )
}

function MobileValue({
    label,
    value,
}: {
    label: string
    value: string
}) {
    return (
        <div>
            <p className="text-xs font-medium uppercase tracking-wide text-neutral-400">
                {
                    label
                }
            </p>

            <p className="mt-1 font-medium capitalize text-neutral-800">
                {
                    humanize(
                        value,
                    )
                }
            </p>
        </div>
    )
}

const inputClass =
    'w-full rounded-lg border border-neutral-300 bg-white px-3 py-2 text-sm text-neutral-900 outline-none transition focus:border-neutral-500 focus:ring-2 focus:ring-neutral-200'

function formatMoney(
    value: number,
): string {
    return new Intl.NumberFormat(
        'en-BD',
        {
            style:
                'currency',
            currency:
                'BDT',
            minimumFractionDigits:
                2,
        },
    ).format(
        value / 100,
    )
}

function formatNumber(
    value: number,
): string {
    return new Intl.NumberFormat(
        'en-BD',
    ).format(
        value,
    )
}

function formatDateTime(
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

function formatAge(
    row: OperationalOrderRow,
): string {
    if (
        row.age_days >
        0
    ) {
        return `${row.age_days} day${
            row.age_days ===
            1
                ? ''
                : 's'
        }`
    }

    return `${row.age_hours} hour${
        row.age_hours ===
        1
            ? ''
            : 's'
    }`
}

function humanize(
    value: string,
): string {
    return value
        .replace(
            /[_-]+/g,
            ' ',
        )
        .replace(
            /\b\w/g,
            (
                character,
            ) =>
                character.toUpperCase(),
        )
}



function OperationalQuickFilters() {
    const items = [
        {
            label:
                'Pending',
            query: {
                status: [
                    'pending',
                ],
            },
        },
        {
            label:
                'Processing',
            query: {
                status: [
                    'processing',
                ],
            },
        },
        {
            label:
                'Unfulfilled',
            query: {
                fulfilment_status: [
                    'unfulfilled',
                ],
            },
        },
        {
            label:
                'Shipped',
            query: {
                fulfilment_status: [
                    'shipped',
                ],
            },
        },
        {
            label:
                'Delivered',
            query: {
                fulfilment_status: [
                    'delivered',
                ],
            },
        },
        {
            label:
                'Returned',
            query: {
                fulfilment_status: [
                    'returned',
                ],
            },
        },
        {
            label:
                'Due',
            query: {
                payment_status: [
                    'due',
                ],
            },
        },
        {
            label:
                'Unpaid',
            query: {
                payment_status: [
                    'unpaid',
                ],
            },
        },
    ]

    return (
        <section className="rounded-xl border border-neutral-200 bg-white p-4 shadow-sm">
            <div className="flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">
                <div>
                    <h2 className="text-sm font-semibold text-neutral-900">
                        Quick workflows
                    </h2>

                    <p className="mt-1 text-xs text-neutral-500">
                        Jump directly to common operational queues.
                    </p>
                </div>

                <div className="flex flex-wrap gap-2">
                    {items.map(
                        (
                            item,
                        ) => (
                            <Link
                                key={
                                    item.label
                                }
                                href={route(
                                    'admin.reports.operations.orders',
                                    item.query,
                                )}
                                className="rounded-full border border-neutral-300 bg-white px-3 py-1.5 text-xs font-semibold text-neutral-700 transition hover:border-neutral-400 hover:bg-neutral-50"
                            >
                                {
                                    item.label
                                }
                            </Link>
                        ),
                    )}
                </div>
            </div>
        </section>
    )
}

function rowClass(
    row: OperationalOrderRow,
): string {
    if (
        row.attention.severity ===
        'critical'
    ) {
        return 'bg-red-50/50 hover:bg-red-50/70'
    }

    if (
        row.attention.requires_attention
    ) {
        return 'bg-amber-50/40 hover:bg-amber-50/70'
    }

    return 'hover:bg-neutral-50/70'
}

function optionLabel(
    options: SelectOption[],
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
        humanize(
            value,
        )
    )
}

function ActiveFilters({
    filters,
    options,
}: {
    filters: Filters
    options: Options
}) {
    const chips: string[] = []

    if (
        filters.search
    ) {
        chips.push(
            `Search: ${filters.search}`,
        )
    }

    for (
        const value
        of filters.statuses
    ) {
        chips.push(
            `Order: ${optionLabel(
                options.statuses,
                value,
            )}`,
        )
    }

    for (
        const value
        of filters.fulfilment_statuses
    ) {
        chips.push(
            `Fulfilment: ${optionLabel(
                options.fulfilment_statuses,
                value,
            )}`,
        )
    }

    for (
        const value
        of filters.payment_statuses
    ) {
        chips.push(
            `Payment: ${optionLabel(
                options.payment_statuses,
                value,
            )}`,
        )
    }

    for (
        const value
        of filters.payment_methods
    ) {
        chips.push(
            `Method: ${optionLabel(
                options.payment_methods,
                value,
            )}`,
        )
    }

    if (
        chips.length ===
        0
    ) {
        return null
    }

    return (
        <section
            aria-label="Active filters"
            className="rounded-xl border border-neutral-200 bg-white p-4 shadow-sm"
        >
            <div className="flex flex-wrap items-center gap-2">
                <span className="text-sm font-semibold text-neutral-700">
                    Active filters
                </span>

                {chips.map(
                    (
                        chip,
                    ) => (
                        <span
                            key={
                                chip
                            }
                            className="rounded-full border border-neutral-200 bg-neutral-50 px-3 py-1 text-xs font-medium text-neutral-700"
                        >
                            {
                                chip
                            }
                        </span>
                    ),
                )}
            </div>
        </section>
    )
}

function buildCsvExportUrl(
    query: Record<
        string,
        unknown
    >,
): string {
    return route(
        'admin.reports.operations.orders.csv',
        query,
    )
}