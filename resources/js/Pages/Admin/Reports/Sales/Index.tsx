import { Link, router } from '@inertiajs/react'
import {
    type FormEvent,
    useState,
    type ReactNode,
} from 'react'

import AdminLayout from '@/Layouts/Admin/AdminLayout'
import ReportHeader from '@/Components/Admin/Reports/ReportHeader'

interface Option {
    value: string
    label: string
}

interface ReportRange {
    preset: string
    preset_label: string
    from: string
    to: string
    timezone: string
}

interface SalesReportFilters {
    range: ReportRange
    statuses: string[]
    payment_statuses: string[]
    search: string | null
    per_page: number
}

interface SalesReportQuery {
    preset: string
    from?: string
    to?: string
    timezone?: string
    status?: string[]
    payment_status?: string[]
    search?: string
    per_page: number
}

interface SalesReportOptions {
    presets: Option[]
    statuses: Option[]
    payment_statuses: Option[]
    per_page: number[]
}

interface SalesReportRow {
    id: number
    order_number: string
    customer_id: number
    customer_name: string

    status: string
    status_label: string

    payment_status: string
    payment_status_label: string

    fulfilment_status: string
    fulfilment_status_label: string

    currency_code: string

    subtotal: number
    discount_total: number
    delivery_total: number
    tax_total: number
    grand_total: number

    items_quantity: number

    placed_at: string
}

interface SalesReportMeta {
    current_page: number
    per_page: number
    total: number
    last_page: number
}

interface SalesReportSummary {
    subtotal: number
    discount_total: number
    delivery_total: number
    tax_total: number
    grand_total: number
    items_quantity: number
}

interface SalesReport {
    data: SalesReportRow[]
    meta: SalesReportMeta
    summary: SalesReportSummary
}

interface Props {
    filters: SalesReportFilters
    query: SalesReportQuery
    options: SalesReportOptions
    report: SalesReport
}

type FilterQuery = {
    preset?: string
    from?: string
    to?: string
    timezone?: string
    status?: string[]
    payment_status?: string[]
    search?: string
    per_page?: number
    page?: number
}

function formatNumber(
    value: number,
): string {
    return new Intl.NumberFormat(
        'en-BD',
    ).format(value)
}

function formatMoney(
    value: number,
): string {
    const amount =
        value / 100

    return `৳${new Intl.NumberFormat(
        'en-BD',
        {
            minimumFractionDigits:
                value % 100 === 0
                    ? 0
                    : 2,
            maximumFractionDigits: 2,
        },
    ).format(amount)}`
}

function formatDateTime(
    value: string,
): string {
    const date =
        new Date(value)

    if (
        Number.isNaN(
            date.getTime(),
        )
    ) {
        return value
    }

    return new Intl.DateTimeFormat(
        'en-BD',
        {
            dateStyle: 'medium',
            timeStyle: 'short',
        },
    ).format(date)
}

function statusTone(
    value: string,
): string {
    switch (value) {
        case 'paid':
        case 'completed':
        case 'delivered':
            return 'bg-emerald-50 text-emerald-700 ring-emerald-200'

        case 'due':
        case 'pending':
        case 'processing':
        case 'shipped':
            return 'bg-amber-50 text-amber-700 ring-amber-200'

        case 'cancelled':
        case 'failed':
        case 'returned':
        case 'refunded':
            return 'bg-rose-50 text-rose-700 ring-rose-200'

        default:
            return 'bg-neutral-50 text-neutral-700 ring-neutral-200'
    }
}

function withoutKey(
    query: SalesReportQuery,
    key:
        | 'status'
        | 'payment_status'
        | 'search',
): FilterQuery {
    const next:
        FilterQuery = {
            ...query,
        }

    delete next[key]

    return next
}
function buildExportUrl(
    baseUrl: string,
    query: SalesReportQuery,
): string {
    const params =
        new URLSearchParams()

    params.set(
        'preset',
        query.preset,
    )

    params.set(
        'per_page',
        String(
            query.per_page,
        ),
    )

    if (
        query.from
    ) {
        params.set(
            'from',
            query.from,
        )
    }

    if (
        query.to
    ) {
        params.set(
            'to',
            query.to,
        )
    }

    if (
        query.timezone
    ) {
        params.set(
            'timezone',
            query.timezone,
        )
    }

    if (
        query.search
    ) {
        params.set(
            'search',
            query.search,
        )
    }

    query.status?.forEach(
        (
            status,
        ) => {
            params.append(
                'status[]',
                status,
            )
        },
    )

    query.payment_status?.forEach(
        (
            status,
        ) => {
            params.append(
                'payment_status[]',
                status,
            )
        },
    )

    const queryString =
        params.toString()

    return queryString !== ''
        ? `${baseUrl}?${queryString}`
        : baseUrl
}

export default function SalesReportIndex({
    filters,
    query,
    options,
    report,
}: Props) {

    const exportUrl =
    buildExportUrl(
        route(
            'admin.reports.sales.export',
        ),
        query,
    )

    const [
        selectedPreset,
        setSelectedPreset,
    ] = useState(
        filters.range.preset,
    )

    const [
        selectedStatuses,
        setSelectedStatuses,
    ] = useState<string[]>(
        filters.statuses,
    )

    const [
        selectedPaymentStatuses,
        setSelectedPaymentStatuses,
    ] = useState<string[]>(
        filters.payment_statuses,
    )

    const toggleValue = (
        value: string,
        current: string[],
        setter: (
            values: string[],
        ) => void,
    ) => {
        if (
            current.includes(
                value,
            )
        ) {
            setter(
                current.filter(
                    (
                        item,
                    ) =>
                        item !==
                        value,
                ),
            )

            return
        }

        setter([
            ...current,
            value,
        ])
    }

    const visitReport = (
        nextQuery:
            FilterQuery,
    ) => {
        router.get(
            route(
                'admin.reports.sales.index',
            ),
            nextQuery,
            {
                preserveScroll:
                    true,

                preserveState:
                    false,

                replace:
                    true,
            },
        )
    }

    const submitFilters = (
        event:
            FormEvent<HTMLFormElement>,
    ) => {
        event.preventDefault()

        const form =
            new FormData(
                event.currentTarget,
            )

        const preset =
            String(
                form.get(
                    'preset',
                ) ?? '',
            )

        const search =
            String(
                form.get(
                    'search',
                ) ?? '',
            ).trim()

        const perPage =
            Number(
                form.get(
                    'per_page',
                ) ?? 25,
            )

        const nextQuery:
            FilterQuery = {
                preset,
                per_page:
                    perPage,
            }

        if (
            preset === 'custom'
        ) {
            const from =
                String(
                    form.get(
                        'from',
                    ) ?? '',
                )

            const to =
                String(
                    form.get(
                        'to',
                    ) ?? '',
                )

            nextQuery.from =
                from

            nextQuery.to =
                to
        }

        if (
            filters.range
                .timezone
        ) {
            nextQuery.timezone =
                filters.range
                    .timezone
        }

        if (
            selectedStatuses.length >
            0
        ) {
            nextQuery.status =
                selectedStatuses
        }

        if (
            selectedPaymentStatuses.length >
            0
        ) {
            nextQuery.payment_status =
                selectedPaymentStatuses
        }

        if (
            search !== ''
        ) {
            nextQuery.search =
                search
        }

        /*
         * Applying new filters deliberately
         * omits "page", so every new filter
         * operation returns to page 1.
         */
        visitReport(
            nextQuery,
        )
    }

    const resetFilters = () => {
        setSelectedStatuses(
            [],
        )

        setSelectedPaymentStatuses(
            [],
        )

        setSelectedPreset(
            'this_month',
        )

        visitReport({})
    }

    const clearSearch = () => {
        visitReport(
            withoutKey(
                query,
                'search',
            ),
        )
    }

    const clearStatuses = () => {
        setSelectedStatuses(
            [],
        )

        visitReport(
            withoutKey(
                query,
                'status',
            ),
        )
    }

    const clearPaymentStatuses =
        () => {
            setSelectedPaymentStatuses(
                [],
            )

            visitReport(
                withoutKey(
                    query,
                    'payment_status',
                ),
            )
        }

    const clearStatus = (
        value: string,
    ) => {
        const statuses =
            (
                query.status ??
                []
            ).filter(
                (
                    status,
                ) =>
                    status !==
                    value,
            )

        const next = {
            ...query,
        }

        if (
            statuses.length ===
            0
        ) {
            delete next.status
        } else {
            next.status =
                statuses
        }

        setSelectedStatuses(
            statuses,
        )

        visitReport(
            next,
        )
    }

    const clearPaymentStatus = (
        value: string,
    ) => {
        const statuses =
            (
                query.payment_status ??
                []
            ).filter(
                (
                    status,
                ) =>
                    status !==
                    value,
            )

        const next = {
            ...query,
        }

        if (
            statuses.length ===
            0
        ) {
            delete next
                .payment_status
        } else {
            next.payment_status =
                statuses
        }

        setSelectedPaymentStatuses(
            statuses,
        )

        visitReport(
            next,
        )
    }

    const goToPage = (
        requestedPage: number,
    ) => {
        const page =
            Math.min(
                Math.max(
                    requestedPage,
                    1,
                ),
                Math.max(
                    report.meta
                        .last_page,
                    1,
                ),
            )

        if (
            page ===
            report.meta
                .current_page
        ) {
            return
        }

        visitReport({
            ...query,
            page,
        })
    }

    return (
        <AdminLayout
            title="Sales Report"
            description="Detailed sales reporting and order analysis."
        >
            <div className="space-y-6">
                <ReportHeader
                    title="Sales Report"
                    description="Detailed sales reporting and order analysis."
                    activeReport="sales"
                    exportUrl={
                        exportUrl
                    }
                />
                <section className="rounded-xl border border-neutral-200 bg-white p-4 shadow-sm sm:p-5">
                    <form
                        onSubmit={
                            submitFilters
                        }
                        className="space-y-5"
                    >
                        <div className="grid gap-4 lg:grid-cols-4">
                            <div>
                                <label
                                    htmlFor="sales-report-preset"
                                    className="mb-1.5 block text-sm font-medium text-neutral-700"
                                >
                                    Date range
                                </label>

                                <select
                                    id="sales-report-preset"
                                    name="preset"
                                    value={
                                        selectedPreset
                                    }
                                    onChange={(
                                        event,
                                    ) =>
                                        setSelectedPreset(
                                            event
                                                .target
                                                .value,
                                        )
                                    }
                                    className="w-full rounded-lg border border-neutral-300 bg-white px-3 py-2.5 text-sm text-neutral-900 shadow-sm outline-none focus:border-neutral-500 focus:ring-2 focus:ring-neutral-200"
                                >
                                    {options.presets.map(
                                        (
                                            preset,
                                        ) => (
                                            <option
                                                key={
                                                    preset.value
                                                }
                                                value={
                                                    preset.value
                                                }
                                            >
                                                {
                                                    preset.label
                                                }
                                            </option>
                                        ),
                                    )}
                                </select>
                            </div>

                            {selectedPreset ===
                                'custom' && (
                                <>
                                    <div>
                                        <label
                                            htmlFor="sales-report-from"
                                            className="mb-1.5 block text-sm font-medium text-neutral-700"
                                        >
                                            From
                                        </label>

                                        <input
                                            id="sales-report-from"
                                            name="from"
                                            type="date"
                                            defaultValue={
                                                filters
                                                    .range
                                                    .from
                                            }
                                            required
                                            className="w-full rounded-lg border border-neutral-300 px-3 py-2.5 text-sm shadow-sm outline-none focus:border-neutral-500 focus:ring-2 focus:ring-neutral-200"
                                        />
                                    </div>

                                    <div>
                                        <label
                                            htmlFor="sales-report-to"
                                            className="mb-1.5 block text-sm font-medium text-neutral-700"
                                        >
                                            To
                                        </label>

                                        <input
                                            id="sales-report-to"
                                            name="to"
                                            type="date"
                                            defaultValue={
                                                filters
                                                    .range
                                                    .to
                                            }
                                            required
                                            className="w-full rounded-lg border border-neutral-300 px-3 py-2.5 text-sm shadow-sm outline-none focus:border-neutral-500 focus:ring-2 focus:ring-neutral-200"
                                        />
                                    </div>
                                </>
                            )}

                            <div>
                                <label
                                    htmlFor="sales-report-search"
                                    className="mb-1.5 block text-sm font-medium text-neutral-700"
                                >
                                    Search
                                </label>

                                <input
                                    id="sales-report-search"
                                    name="search"
                                    type="search"
                                    defaultValue={
                                        filters.search ??
                                        ''
                                    }
                                    placeholder="Order number or customer..."
                                    className="w-full rounded-lg border border-neutral-300 px-3 py-2.5 text-sm shadow-sm outline-none focus:border-neutral-500 focus:ring-2 focus:ring-neutral-200"
                                />
                            </div>
                        </div>

                        <div className="grid gap-5 lg:grid-cols-2">
                            <FilterChecklist
                                title="Order status"
                                options={
                                    options.statuses
                                }
                                selected={
                                    selectedStatuses
                                }
                                onToggle={(
                                    value,
                                ) =>
                                    toggleValue(
                                        value,
                                        selectedStatuses,
                                        setSelectedStatuses,
                                    )
                                }
                            />

                            <FilterChecklist
                                title="Payment status"
                                options={
                                    options.payment_statuses
                                }
                                selected={
                                    selectedPaymentStatuses
                                }
                                onToggle={(
                                    value,
                                ) =>
                                    toggleValue(
                                        value,
                                        selectedPaymentStatuses,
                                        setSelectedPaymentStatuses,
                                    )
                                }
                            />
                        </div>

                        <div className="flex flex-col gap-3 border-t border-neutral-100 pt-4 sm:flex-row sm:items-end sm:justify-between">
                            <div className="w-full sm:w-40">
                                <label
                                    htmlFor="sales-report-per-page"
                                    className="mb-1.5 block text-sm font-medium text-neutral-700"
                                >
                                    Rows per page
                                </label>

                                <select
                                    id="sales-report-per-page"
                                    name="per_page"
                                    defaultValue={
                                        filters.per_page
                                    }
                                    className="w-full rounded-lg border border-neutral-300 bg-white px-3 py-2.5 text-sm shadow-sm outline-none focus:border-neutral-500 focus:ring-2 focus:ring-neutral-200"
                                >
                                    {options.per_page.map(
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
                            </div>

                            <div className="flex flex-col gap-2 sm:flex-row">
                                

                                <button
                                    type="button"
                                    onClick={
                                        resetFilters
                                    }
                                    className="inline-flex min-h-10 items-center justify-center rounded-lg border border-neutral-300 bg-white px-4 py-2.5 text-sm font-semibold text-neutral-700 hover:bg-neutral-50"
                                >
                                    Reset
                                </button>

                                <button
                                    type="submit"
                                    className="inline-flex min-h-10 items-center justify-center rounded-lg bg-neutral-900 px-5 py-2.5 text-sm font-semibold text-white hover:bg-neutral-800"
                                >
                                    Apply filters
                                </button>
                            </div>
                        </div>
                    </form>

                    <div className="mt-4 flex flex-wrap gap-x-4 gap-y-1 border-t border-neutral-100 pt-4 text-xs text-neutral-500">
                        <span>
                            {
                                filters
                                    .range
                                    .preset_label
                            }
                        </span>

                        <span>
                            {
                                filters
                                    .range
                                    .from
                            }{' '}
                            –{' '}
                            {
                                filters
                                    .range
                                    .to
                            }
                        </span>

                        <span>
                            Timezone:{' '}
                            {
                                filters
                                    .range
                                    .timezone
                            }
                        </span>
                    </div>
                </section>

                <ActiveFilters
                    filters={
                        filters
                    }
                    options={
                        options
                    }
                    onClearSearch={
                        clearSearch
                    }
                    onClearStatus={
                        clearStatus
                    }
                    onClearStatuses={
                        clearStatuses
                    }
                    onClearPaymentStatus={
                        clearPaymentStatus
                    }
                    onClearPaymentStatuses={
                        clearPaymentStatuses
                    }
                    onReset={
                        resetFilters
                    }
                />

                <section className="grid gap-4 sm:grid-cols-2 xl:grid-cols-4">
                    <SummaryCard
                        label="Orders"
                        value={formatNumber(
                            report.meta
                                .total,
                        )}
                        helper={`${formatNumber(
                            report.summary
                                .items_quantity,
                        )} items`}
                    />

                    <SummaryCard
                        label="Gross sales"
                        value={formatMoney(
                            report.summary
                                .subtotal,
                        )}
                    />

                    <SummaryCard
                        label="Discounts"
                        value={formatMoney(
                            report.summary
                                .discount_total,
                        )}
                    />

                    <SummaryCard
                        label="Grand total"
                        value={formatMoney(
                            report.summary
                                .grand_total,
                        )}
                    />
                </section>

                <section className="grid gap-4 sm:grid-cols-2 lg:grid-cols-3">
                    <SummaryCard
                        label="Shipping"
                        value={formatMoney(
                            report.summary
                                .delivery_total,
                        )}
                    />

                    <SummaryCard
                        label="Tax"
                        value={formatMoney(
                            report.summary
                                .tax_total,
                        )}
                    />

                    <SummaryCard
                        label="Current page"
                        value={`${formatNumber(
                            report.meta
                                .current_page,
                        )} / ${formatNumber(
                            report.meta
                                .last_page,
                        )}`}
                    />
                </section>

                <section className="overflow-hidden rounded-xl border border-neutral-200 bg-white shadow-sm">
                    <div className="flex flex-col gap-2 border-b border-neutral-200 px-4 py-4 sm:flex-row sm:items-center sm:justify-between sm:px-5">
                        <div>
                            <h2 className="text-base font-semibold text-neutral-900">
                                Orders
                            </h2>

                            <p className="mt-1 text-sm text-neutral-500">
                                {formatNumber(
                                    report.meta
                                        .total,
                                )}{' '}
                                matching{' '}
                                {report.meta
                                    .total ===
                                1
                                    ? 'order'
                                    : 'orders'}
                            </p>
                        </div>

                        <span className="text-xs text-neutral-500">
                            Page{' '}
                            {
                                report.meta
                                    .current_page
                            }{' '}
                            of{' '}
                            {
                                report.meta
                                    .last_page
                            }
                        </span>
                    </div>

                    {report.data.length ===
                        0 ? (
                            <div
                                role="status"
                                aria-live="polite"
                                className="px-5 py-12 text-center"
                            >
                                <p className="text-sm font-semibold text-neutral-800">
                                    No orders match the
                                    current report filters.
                                </p>

                                <p className="mx-auto mt-2 max-w-md text-sm leading-6 text-neutral-500">
                                    Try changing the date
                                    range, status, payment
                                    status, or search term.
                                </p>

                                <button
                                    type="button"
                                    onClick={
                                        resetFilters
                                    }
                                    className="mt-4 inline-flex min-h-10 items-center justify-center rounded-lg border border-neutral-300 bg-white px-4 py-2.5 text-sm font-semibold text-neutral-700 transition hover:bg-neutral-50 focus:outline-none focus:ring-2 focus:ring-neutral-300 focus:ring-offset-2"
                                >
                                    Reset report filters
                                </button>
                            </div>
                        ) : (
                        <>
                            <div className="hidden overflow-x-auto lg:block">
                                <table
                                    className="min-w-full divide-y divide-neutral-200 text-sm"
                                    aria-label="Sales report orders"
                                >
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
                                                Payment
                                            </TableHead>

                                            <TableHead>
                                                Fulfilment
                                            </TableHead>

                                            <TableHead>
                                                Items
                                            </TableHead>

                                            <TableHead>
                                                Subtotal
                                            </TableHead>

                                            <TableHead>
                                                Shipping
                                            </TableHead>

                                            <TableHead>
                                                Total
                                            </TableHead>

                                            <TableHead>
                                                Placed
                                            </TableHead>
                                        </tr>
                                    </thead>

                                    <tbody className="divide-y divide-neutral-100">
                                        {report.data.map(
                                            (
                                                order,
                                            ) => (
                                                <tr
                                                    key={
                                                        order.id
                                                    }
                                                    className="hover:bg-neutral-50/70"
                                                >
                                                    <TableCell>
                                                        <Link
                                                            href={route(
                                                                'admin.orders.show',
                                                                order.id,
                                                            )}
                                                            className="font-semibold text-neutral-900 hover:underline"
                                                        >
                                                            {
                                                                order.order_number
                                                            }
                                                        </Link>
                                                    </TableCell>

                                                    <TableCell>
                                                        {
                                                            order.customer_name
                                                        }
                                                    </TableCell>

                                                    <TableCell>
                                                        <StatusBadge
                                                            value={
                                                                order.status
                                                            }
                                                            label={
                                                                order.status_label
                                                            }
                                                        />
                                                    </TableCell>

                                                    <TableCell>
                                                        <StatusBadge
                                                            value={
                                                                order.payment_status
                                                            }
                                                            label={
                                                                order.payment_status_label
                                                            }
                                                        />
                                                    </TableCell>

                                                    <TableCell>
                                                        <StatusBadge
                                                            value={
                                                                order.fulfilment_status
                                                            }
                                                            label={
                                                                order.fulfilment_status_label
                                                            }
                                                        />
                                                    </TableCell>

                                                    <TableCell>
                                                        {formatNumber(
                                                            order.items_quantity,
                                                        )}
                                                    </TableCell>

                                                    <TableCell>
                                                        {formatMoney(
                                                            order.subtotal,
                                                        )}
                                                    </TableCell>

                                                    <TableCell>
                                                        {formatMoney(
                                                            order.delivery_total,
                                                        )}
                                                    </TableCell>

                                                    <TableCell>
                                                        <span className="font-semibold text-neutral-900">
                                                            {formatMoney(
                                                                order.grand_total,
                                                            )}
                                                        </span>
                                                    </TableCell>

                                                    <TableCell>
                                                        {formatDateTime(
                                                            order.placed_at,
                                                        )}
                                                    </TableCell>
                                                </tr>
                                            ),
                                        )}
                                    </tbody>
                                </table>
                            </div>

                            <div className="divide-y divide-neutral-100 lg:hidden">
                                {report.data.map(
                                    (
                                        order,
                                    ) => (
                                        <article
                                            key={
                                                order.id
                                            }
                                            className="space-y-4 p-4"
                                        >
                                            <div className="flex items-start justify-between gap-4">
                                                <div className="min-w-0">
                                                    <Link
                                                        href={route(
                                                            'admin.orders.show',
                                                            order.id,
                                                        )}
                                                        className="font-semibold text-neutral-900 hover:underline"
                                                    >
                                                        {
                                                            order.order_number
                                                        }
                                                    </Link>

                                                    <p className="mt-1 truncate text-sm text-neutral-500">
                                                        {
                                                            order.customer_name
                                                        }
                                                    </p>
                                                </div>

                                                <p className="shrink-0 text-base font-bold text-neutral-900">
                                                    {formatMoney(
                                                        order.grand_total,
                                                    )}
                                                </p>
                                            </div>

                                            <div className="flex flex-wrap gap-2">
                                                <StatusBadge
                                                    value={
                                                        order.status
                                                    }
                                                    label={
                                                        order.status_label
                                                    }
                                                />

                                                <StatusBadge
                                                    value={
                                                        order.payment_status
                                                    }
                                                    label={
                                                        order.payment_status_label
                                                    }
                                                />

                                                <StatusBadge
                                                    value={
                                                        order.fulfilment_status
                                                    }
                                                    label={
                                                        order.fulfilment_status_label
                                                    }
                                                />
                                            </div>

                                            <dl className="grid grid-cols-2 gap-3 text-sm">
                                                <SmallDetail
                                                    label="Items"
                                                    value={formatNumber(
                                                        order.items_quantity,
                                                    )}
                                                />

                                                <SmallDetail
                                                    label="Subtotal"
                                                    value={formatMoney(
                                                        order.subtotal,
                                                    )}
                                                />

                                                <SmallDetail
                                                    label="Discount"
                                                    value={formatMoney(
                                                        order.discount_total,
                                                    )}
                                                />

                                                <SmallDetail
                                                    label="Shipping"
                                                    value={formatMoney(
                                                        order.delivery_total,
                                                    )}
                                                />
                                            </dl>

                                            <p className="text-xs text-neutral-500">
                                                {formatDateTime(
                                                    order.placed_at,
                                                )}
                                            </p>
                                        </article>
                                    ),
                                )}
                            </div>
                        </>
                    )}

                    <Pagination
                        currentPage={
                            report.meta
                                .current_page
                        }
                        lastPage={
                            report.meta
                                .last_page
                        }
                        total={
                            report.meta
                                .total
                        }
                        perPage={
                            report.meta
                                .per_page
                        }
                        onPageChange={
                            goToPage
                        }
                    />
                </section>
            </div>
        </AdminLayout>
    )
}

function FilterChecklist({
    title,
    options,
    selected,
    onToggle,
}: {
    title: string
    options: Option[]
    selected: string[]
    onToggle: (
        value: string,
    ) => void
}) {
    return (
        <fieldset className="rounded-lg border border-neutral-200 p-4">
            <legend className="px-1 text-sm font-semibold text-neutral-800">
                {title}
            </legend>

            <div className="mt-1 grid gap-2 sm:grid-cols-2">
                {options.map(
                    (
                        option,
                    ) => (
                        <label
                            key={
                                option.value
                            }
                            className="flex cursor-pointer items-center gap-2 rounded-md px-2 py-1.5 text-sm text-neutral-700 hover:bg-neutral-50"
                        >
                            <input
                                type="checkbox"
                                checked={selected.includes(
                                    option.value,
                                )}
                                onChange={() =>
                                    onToggle(
                                        option.value,
                                    )
                                }
                                aria-label={`${title}: ${option.label}`}
                                className="rounded border-neutral-300"
                            />

                            <span>
                                {
                                    option.label
                                }
                            </span>
                        </label>
                    ),
                )}
            </div>
        </fieldset>
    )
}

function ActiveFilters({
    filters,
    options,
    onClearSearch,
    onClearStatus,
    onClearStatuses,
    onClearPaymentStatus,
    onClearPaymentStatuses,
    onReset,
}: {
    filters:
        SalesReportFilters

    options:
        SalesReportOptions

    onClearSearch:
        () => void

    onClearStatus:
        (
            value: string,
        ) => void

    onClearStatuses:
        () => void

    onClearPaymentStatus:
        (
            value: string,
        ) => void

    onClearPaymentStatuses:
        () => void

    onReset:
        () => void
}) {
    const hasFilters =
        filters.search !==
            null ||
        filters.statuses.length >
            0 ||
        filters
            .payment_statuses
            .length > 0 ||
        filters.range.preset !==
            'this_month'

    if (
        !hasFilters
    ) {
        return null
    }

    const labelFor = (
        values: Option[],
        value: string,
    ): string =>
        values.find(
            (
                option,
            ) =>
                option.value ===
                value,
        )?.label ??
        value

    return (
        <section className="rounded-xl border border-neutral-200 bg-white p-4 shadow-sm">
            <div className="flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">
                <div>
                    <h2 className="text-sm font-semibold text-neutral-900">
                        Active filters
                    </h2>

                    <p className="mt-1 text-xs text-neutral-500">
                        Remove individual
                        filters without
                        resetting the
                        entire report.
                    </p>
                </div>

                <button
                    type="button"
                    onClick={
                        onReset
                    }
                    className="self-start text-sm font-semibold text-neutral-600 transition hover:text-neutral-950"
                >
                    Clear all
                </button>
            </div>

            <div className="mt-4 flex flex-wrap gap-2">
                {filters.range
                    .preset !==
                    'this_month' && (
                    <FilterChip
                        label={
                            filters
                                .range
                                .preset_label
                        }
                        onRemove={
                            onReset
                        }
                    />
                )}

                {filters.search && (
                    <FilterChip
                        label={`Search: ${filters.search}`}
                        onRemove={
                            onClearSearch
                        }
                    />
                )}

                {filters.statuses.map(
                    (
                        status,
                    ) => (
                        <FilterChip
                            key={`status-${status}`}
                            label={`Status: ${labelFor(
                                options.statuses,
                                status,
                            )}`}
                            onRemove={() =>
                                onClearStatus(
                                    status,
                                )
                            }
                        />
                    ),
                )}

                {filters
                    .payment_statuses
                    .map(
                        (
                            status,
                        ) => (
                            <FilterChip
                                key={`payment-${status}`}
                                label={`Payment: ${labelFor(
                                    options.payment_statuses,
                                    status,
                                )}`}
                                onRemove={() =>
                                    onClearPaymentStatus(
                                        status,
                                    )
                                }
                            />
                        ),
                    )}
            </div>

            {(filters.statuses
                .length > 1 ||
                filters
                    .payment_statuses
                    .length >
                    1) && (
                <div className="mt-3 flex flex-wrap gap-3 text-xs">
                    {filters.statuses
                        .length >
                        1 && (
                        <button
                            type="button"
                            onClick={
                                onClearStatuses
                            }
                            className="font-semibold text-neutral-500 transition hover:text-neutral-900"
                        >
                            Clear all order
                            statuses
                        </button>
                    )}

                    {filters
                        .payment_statuses
                        .length >
                        1 && (
                        <button
                            type="button"
                            onClick={
                                onClearPaymentStatuses
                            }
                            className="font-semibold text-neutral-500 transition hover:text-neutral-900"
                        >
                            Clear all
                            payment statuses
                        </button>
                    )}
                </div>
            )}
        </section>
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
        <span className="inline-flex items-center gap-1.5 rounded-full border border-neutral-200 bg-neutral-50 py-1 pl-3 pr-1.5 text-xs font-medium text-neutral-700">
            <span>
                {label}
            </span>

            <button
                type="button"
                onClick={
                    onRemove
                }
                aria-label={`Remove ${label} filter`}
                className="flex h-5 w-5 shrink-0 items-center justify-center rounded-full text-neutral-400 transition hover:bg-neutral-200 hover:text-neutral-800"
            >
                ×
            </button>
        </span>
    )
}

function SummaryCard({
    label,
    value,
    helper,
}: {
    label: string
    value: string
    helper?: string
}) {
    return (
        <div className="rounded-xl border border-neutral-200 bg-white p-5 shadow-sm">
            <p className="text-sm font-medium text-neutral-500">
                {label}
            </p>

            <p className="mt-2 break-words text-2xl font-bold text-neutral-900">
                {value}
            </p>

            {helper && (
                <p className="mt-2 text-xs text-neutral-500">
                    {helper}
                </p>
            )}
        </div>
    )
}

function StatusBadge({
    value,
    label,
}: {
    value: string
    label: string
}) {
    return (
        <span
            className={`inline-flex rounded-full px-2.5 py-1 text-xs font-semibold ring-1 ring-inset ${statusTone(
                value,
            )}`}
        >
            {label}
        </span>
    )
}

function SmallDetail({
    label,
    value,
}: {
    label: string
    value: string
}) {
    return (
        <div>
            <dt className="text-xs text-neutral-500">
                {label}
            </dt>

            <dd className="mt-0.5 font-medium text-neutral-900">
                {value}
            </dd>
        </div>
    )
}

function TableHead({
    children,
}: {
    children:
        ReactNode
}) {
    return (
        <th
            scope="col"
            className="whitespace-nowrap px-4 py-3 text-left text-xs font-semibold uppercase tracking-wide text-neutral-500"
        >
            {children}
        </th>
    )
}

function TableCell({
    children,
}: {
    children:
        ReactNode
}) {
    return (
        <td className="whitespace-nowrap px-4 py-3 text-neutral-600">
            {children}
        </td>
    )
}

function Pagination({
    currentPage,
    lastPage,
    total,
    perPage,
    onPageChange,
}: {
    currentPage: number
    lastPage: number
    total: number
    perPage: number
    onPageChange: (
        page: number,
    ) => void
}) {
    if (
        lastPage <= 1
    ) {
        return null
    }

    const start =
        total === 0
            ? 0
            : (currentPage -
                  1) *
                  perPage +
              1

    const end =
        total === 0
            ? 0
            : Math.min(
                  currentPage *
                      perPage,
                  total,
              )

    const pages =
        pageWindow(
            currentPage,
            lastPage,
        )

    return (
        <div className="flex flex-col gap-3 border-t border-neutral-200 px-4 py-4 sm:flex-row sm:items-center sm:justify-between sm:px-5">
            <p className="text-sm text-neutral-500">
                Showing{' '}
                {formatNumber(
                    start,
                )}{' '}
                –{' '}
                {formatNumber(
                    end,
                )}{' '}
                of{' '}
                {formatNumber(
                    total,
                )}
            </p>

            <nav
                aria-label="Report pagination"
                className="flex flex-wrap items-center gap-1"
            >
                <PageButton
                    disabled={
                        currentPage <=
                        1
                    }
                    ariaLabel="Go to first page"
                    onClick={() =>
                        onPageChange(
                            1,
                        )
                    }
                >
                    First
                </PageButton>

                <PageButton
                    disabled={
                        currentPage <=
                        1
                    }
                    ariaLabel="Go to previous page"
                    onClick={() =>
                        onPageChange(
                            currentPage -
                                1,
                        )
                    }
                >
                    Previous
                </PageButton>

                {pages.map(
                    (
                        page,
                    ) => (
                        <PageButton
                            key={
                                page
                            }
                            active={
                                page ===
                                currentPage
                            }
                            ariaLabel={`Go to page ${page}`}
                            onClick={() =>
                                onPageChange(
                                    page,
                                )
                            }
                        >
                            {page}
                        </PageButton>
                    ),
                )}

                <PageButton
                    disabled={
                        currentPage >=
                        lastPage
                    }
                    ariaLabel="Go to next page"
                    onClick={() =>
                        onPageChange(
                            currentPage +
                                1,
                        )
                    }
                >
                    Next
                </PageButton>

                <PageButton
                    disabled={
                        currentPage >=
                        lastPage
                    }
                    ariaLabel="Go to last page"
                    onClick={() =>
                        onPageChange(
                            lastPage,
                        )
                    }
                >
                    Last
                </PageButton>
            </nav>
        </div>
    )
}

function PageButton({
    children,
    active = false,
    disabled = false,
    ariaLabel,
    onClick,
}: {
    children:
        ReactNode
    active?: boolean
    disabled?: boolean
    ariaLabel?: string
    onClick: () => void
}) {
    return (
        <button
            type="button"
            disabled={
                disabled
            }
            onClick={
                onClick
            }
            aria-label={
                ariaLabel
            }
            aria-current={
                active
                    ? 'page'
                    : undefined
            }
            className={
                active
                    ? 'inline-flex min-h-9 min-w-9 items-center justify-center rounded-md bg-neutral-900 px-3 text-sm font-semibold text-white'
                    : 'inline-flex min-h-9 min-w-9 items-center justify-center rounded-md border border-neutral-300 bg-white px-3 text-sm font-medium text-neutral-700 hover:bg-neutral-50 disabled:cursor-not-allowed disabled:opacity-40'
            }
        >
            {children}
        </button>
    )
}

function pageWindow(
    currentPage: number,
    lastPage: number,
): number[] {
    if (
        lastPage <= 5
    ) {
        return Array.from(
            {
                length:
                    lastPage,
            },
            (
                _,
                index,
            ) =>
                index + 1,
        )
    }

    const pages =
        new Set<number>()

    pages.add(
        1,
    )

    pages.add(
        lastPage,
    )

    for (
        let offset = -2;
        offset <= 2;
        offset++
    ) {
        const page =
            currentPage +
            offset

        if (
            page >= 1 &&
            page <=
                lastPage
        ) {
            pages.add(
                page,
            )
        }
    }

    return Array.from(
        pages,
    ).sort(
        (
            first,
            second,
        ) =>
            first -
            second,
    )
}
