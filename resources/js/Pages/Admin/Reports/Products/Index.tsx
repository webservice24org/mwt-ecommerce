import { Link, router } from '@inertiajs/react'
import {
    type FormEvent,
    type ReactNode,
    useState,
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

interface ProductPerformanceFilters {
    range: ReportRange
    statuses: string[]
    payment_statuses: string[]
    search: string | null
    per_page: number
}

interface ProductPerformanceQuery {
    preset: string
    from?: string
    to?: string
    timezone?: string
    status?: string[]
    payment_status?: string[]
    search?: string
    per_page: number
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

interface ProductPerformanceOptions {
    presets: Option[]
    statuses: Option[]
    payment_statuses: Option[]
    per_page: number[]
}

interface ProductPerformanceRow {
    product_id: number | null
    product_name: string
    sku: string | null
    quantity_sold: number
    orders_count: number
    gross_sales: number
}

interface ProductPerformanceMeta {
    current_page: number
    per_page: number
    total: number
    last_page: number
}

interface ProductPerformanceSummary {
    product_groups_count: number
    quantity_sold: number
    orders_count: number
    gross_sales: number
}

interface ProductPerformanceReport {
    data: ProductPerformanceRow[]
    meta: ProductPerformanceMeta
    summary: ProductPerformanceSummary
}

interface Props {
    filters: ProductPerformanceFilters
    query: ProductPerformanceQuery
    options: ProductPerformanceOptions
    report: ProductPerformanceReport
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

            maximumFractionDigits:
                2,
        },
    ).format(amount)}`
}

function withoutKey(
    query:
        ProductPerformanceQuery,
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
    query:
        ProductPerformanceQuery,
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

export default function ProductPerformanceReportIndex({
    filters,
    query,
    options,
    report,
}: Props) {
    const exportUrl =
    buildExportUrl(
        route(
            'admin.reports.products.export',
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
                'admin.reports.products.index',
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
         * Do not carry page into a new
         * filter submission.
         *
         * New filters always start from
         * page 1.
         */
        visitReport(
            nextQuery,
        )
    }

    const resetFilters = () => {
        setSelectedPreset(
            'this_month',
        )

        setSelectedStatuses(
            [],
        )

        setSelectedPaymentStatuses(
            [],
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

        const next:
            FilterQuery = {
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

        const next:
            FilterQuery = {
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
            title="Product Performance"
            description="Product and SKU sales performance reporting."
        >
            <div className="space-y-6">
                <ReportHeader
                    title="Product Performance"
                    description="Product and SKU sales performance reporting."
                    activeReport="products"
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
                                    htmlFor="product-report-preset"
                                    className="mb-1.5 block text-sm font-medium text-neutral-700"
                                >
                                    Date range
                                </label>

                                <select
                                    id="product-report-preset"
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
                                            htmlFor="product-report-from"
                                            className="mb-1.5 block text-sm font-medium text-neutral-700"
                                        >
                                            From
                                        </label>

                                        <input
                                            id="product-report-from"
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
                                            htmlFor="product-report-to"
                                            className="mb-1.5 block text-sm font-medium text-neutral-700"
                                        >
                                            To
                                        </label>

                                        <input
                                            id="product-report-to"
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
                                    htmlFor="product-report-search"
                                    className="mb-1.5 block text-sm font-medium text-neutral-700"
                                >
                                    Search
                                </label>

                                <input
                                    id="product-report-search"
                                    name="search"
                                    type="search"
                                    defaultValue={
                                        filters.search ??
                                        ''
                                    }
                                    placeholder="Product name or SKU..."
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
                                    htmlFor="product-report-per-page"
                                    className="mb-1.5 block text-sm font-medium text-neutral-700"
                                >
                                    Rows per page
                                </label>

                                <select
                                    id="product-report-per-page"
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
                        label="Product groups"
                        value={formatNumber(
                            report
                                .summary
                                .product_groups_count,
                        )}
                        helper="Historical product/SKU groups"
                    />

                    <SummaryCard
                        label="Quantity sold"
                        value={formatNumber(
                            report
                                .summary
                                .quantity_sold,
                        )}
                    />

                    <SummaryCard
                        label="Orders"
                        value={formatNumber(
                            report
                                .summary
                                .orders_count,
                        )}
                        helper="Distinct matching orders"
                    />

                    <SummaryCard
                        label="Gross sales"
                        value={formatMoney(
                            report
                                .summary
                                .gross_sales,
                        )}
                        helper="Merchandise line totals"
                    />
                </section>

                <section className="overflow-hidden rounded-xl border border-neutral-200 bg-white shadow-sm">
                    <div className="flex flex-col gap-2 border-b border-neutral-200 px-4 py-4 sm:flex-row sm:items-center sm:justify-between sm:px-5">
                        <div>
                            <h2 className="text-base font-semibold text-neutral-900">
                                Product performance
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
                                    ? 'product group'
                                    : 'product groups'}
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
                                    No product performance
                                    data matches the current
                                    filters.
                                </p>

                                <p className="mx-auto mt-2 max-w-md text-sm leading-6 text-neutral-500">
                                    Try changing the date
                                    range, product or SKU
                                    search, order status, or
                                    payment status.
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
                                    aria-label="Product performance report"
                                >
                                    <thead className="bg-neutral-50">
                                        <tr>
                                            <TableHead>
                                                Product
                                            </TableHead>

                                            <TableHead>
                                                SKU
                                            </TableHead>

                                            <TableHead>
                                                State
                                            </TableHead>

                                            <TableHead>
                                                Qty sold
                                            </TableHead>

                                            <TableHead>
                                                Orders
                                            </TableHead>

                                            <TableHead>
                                                Gross sales
                                            </TableHead>

                                            <TableHead>
                                                Avg / unit
                                            </TableHead>

                                            <TableHead>
                                                Action
                                            </TableHead>
                                        </tr>
                                    </thead>

                                    <tbody className="divide-y divide-neutral-100">
                                        {report.data.map(
                                            (
                                                row,
                                                index,
                                            ) => {
                                                const averageUnit =
                                                    row.quantity_sold >
                                                    0
                                                        ? Math.trunc(
                                                              row.gross_sales /
                                                                  row.quantity_sold,
                                                          )
                                                        : 0

                                                return (
                                                    <tr
                                                        key={`${row.product_id ?? 'historical'}-${row.product_name}-${row.sku ?? 'no-sku'}-${index}`}
                                                        className="hover:bg-neutral-50/70"
                                                    >
                                                        <TableCell>
                                                            <div className="max-w-xs">
                                                                <p className="font-semibold text-neutral-900">
                                                                    {
                                                                        row.product_name
                                                                    }
                                                                </p>
                                                            </div>
                                                        </TableCell>

                                                        <TableCell>
                                                            <span className="font-mono text-xs text-neutral-600">
                                                                {row.sku ??
                                                                    '—'}
                                                            </span>
                                                        </TableCell>

                                                        <TableCell>
                                                            <ProductStateBadge
                                                                historical={
                                                                    row.product_id ===
                                                                    null
                                                                }
                                                            />
                                                        </TableCell>

                                                        <TableCell>
                                                            {formatNumber(
                                                                row.quantity_sold,
                                                            )}
                                                        </TableCell>

                                                        <TableCell>
                                                            {formatNumber(
                                                                row.orders_count,
                                                            )}
                                                        </TableCell>

                                                        <TableCell>
                                                            <span className="font-semibold text-neutral-900">
                                                                {formatMoney(
                                                                    row.gross_sales,
                                                                )}
                                                            </span>
                                                        </TableCell>

                                                        <TableCell>
                                                            {formatMoney(
                                                                averageUnit,
                                                            )}
                                                        </TableCell>

                                                        <TableCell>
                                                            {row.product_id !==
                                                            null ? (
                                                                <Link
                                                                    href={route(
                                                                        'admin.products.edit',
                                                                        row.product_id,
                                                                    )}
                                                                    className="font-semibold text-neutral-700 hover:text-neutral-950 hover:underline"
                                                                >
                                                                    Open
                                                                    product
                                                                </Link>
                                                            ) : (
                                                                <span className="text-xs text-neutral-400">
                                                                    Historical
                                                                    only
                                                                </span>
                                                            )}
                                                        </TableCell>
                                                    </tr>
                                                )
                                            },
                                        )}
                                    </tbody>
                                </table>
                            </div>

                            <div className="divide-y divide-neutral-100 lg:hidden">
                                {report.data.map(
                                    (
                                        row,
                                        index,
                                    ) => {
                                        const averageUnit =
                                            row.quantity_sold >
                                            0
                                                ? Math.trunc(
                                                      row.gross_sales /
                                                          row.quantity_sold,
                                                  )
                                                : 0

                                        return (
                                            <article
                                                key={`${row.product_id ?? 'historical'}-${row.product_name}-${row.sku ?? 'no-sku'}-${index}`}
                                                className="space-y-4 p-4"
                                            >
                                                <div className="flex items-start justify-between gap-4">
                                                    <div className="min-w-0">
                                                        <p className="font-semibold text-neutral-900">
                                                            {
                                                                row.product_name
                                                            }
                                                        </p>

                                                        <p className="mt-1 break-all font-mono text-xs text-neutral-500">
                                                            SKU:{' '}
                                                            {row.sku ??
                                                                '—'}
                                                        </p>
                                                    </div>

                                                    <ProductStateBadge
                                                        historical={
                                                            row.product_id ===
                                                            null
                                                        }
                                                    />
                                                </div>

                                                <dl className="grid grid-cols-2 gap-3 text-sm">
                                                    <SmallDetail
                                                        label="Quantity sold"
                                                        value={formatNumber(
                                                            row.quantity_sold,
                                                        )}
                                                    />

                                                    <SmallDetail
                                                        label="Orders"
                                                        value={formatNumber(
                                                            row.orders_count,
                                                        )}
                                                    />

                                                    <SmallDetail
                                                        label="Gross sales"
                                                        value={formatMoney(
                                                            row.gross_sales,
                                                        )}
                                                    />

                                                    <SmallDetail
                                                        label="Average / unit"
                                                        value={formatMoney(
                                                            averageUnit,
                                                        )}
                                                    />
                                                </dl>

                                                <div className="border-t border-neutral-100 pt-3">
                                                    {row.product_id !==
                                                    null ? (
                                                        <Link
                                                            href={route(
                                                                'admin.products.edit',
                                                                row.product_id,
                                                            )}
                                                            className="text-sm font-semibold text-neutral-700 hover:text-neutral-950 hover:underline"
                                                        >
                                                            Open
                                                            product
                                                        </Link>
                                                    ) : (
                                                        <p className="text-xs leading-5 text-neutral-500">
                                                            This
                                                            product
                                                            no longer
                                                            exists in
                                                            the current
                                                            catalog.
                                                            The row is
                                                            preserved
                                                            from the
                                                            historical
                                                            order
                                                            snapshot.
                                                        </p>
                                                    )}
                                                </div>
                                            </article>
                                        )
                                    },
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
        ProductPerformanceFilters

    options:
        ProductPerformanceOptions

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
                        resetting the entire
                        report.
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
                <p className="mt-2 text-xs leading-5 text-neutral-500">
                    {helper}
                </p>
            )}
        </div>
    )
}

function ProductStateBadge({
    historical,
}: {
    historical: boolean
}) {
    if (
        historical
    ) {
        return (
            <span className="inline-flex rounded-full bg-amber-50 px-2.5 py-1 text-xs font-semibold text-amber-700 ring-1 ring-inset ring-amber-200">
                Historical
            </span>
        )
    }

    return (
        <span className="inline-flex rounded-full bg-emerald-50 px-2.5 py-1 text-xs font-semibold text-emerald-700 ring-1 ring-inset ring-emerald-200">
            Current
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