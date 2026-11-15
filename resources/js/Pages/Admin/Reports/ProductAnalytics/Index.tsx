'use client'

import {
    router,
} from '@inertiajs/react'
import {
    FormEvent,
    useState,
} from 'react'

import AnalyticsChart, {
    ReportChart,
} from '@/Components/Admin/Reports/AnalyticsChart'
import ReportHeader from '@/Components/Admin/Reports/ReportHeader'
import AdminLayout from '@/Layouts/Admin/AdminLayout'

interface Option {
    value: string
    label: string
}

interface Filters {
    range: {
        preset: string
        preset_label?: string
        from: string
        to: string
        timezone: string
    }

    statuses: string[]
    payment_statuses: string[]
    search: string | null
    per_page: number
}

interface Query {
    preset?: string
    from?: string
    to?: string
    timezone?: string
    status?: string[]
    payment_status?: string[]
    search?: string
    per_page?: number
}

interface Summary {
    product_groups_count: number
    quantity_sold: number
    orders_count: number
    gross_sales: number
    average_revenue_per_order: number
    average_revenue_per_item: number
    current_product_revenue: number
    historical_product_revenue: number
}

interface ProductRow {
    product_id: number | null
    product_name: string
    sku: string | null
    historical: boolean
    quantity_sold: number
    orders_count: number
    gross_sales: number
    average_revenue_per_item: number
}

interface Report {
    summary: Summary
    products: ProductRow[]
}

interface Charts {
    sales_trend: ReportChart

    top_products_by_revenue:
        ReportChart

    top_products_by_quantity:
        ReportChart

    current_vs_historical_revenue:
        ReportChart

    sku_performance:
        ReportChart

    revenue_share_by_product:
        ReportChart
}

interface Props {
    filters: Filters
    query: Query

    options: {
        presets: Option[]
        statuses: Option[]
        payment_statuses: Option[]
        per_page: number[]
    }

    report: Report
    charts: Charts
}

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
                0,

            maximumFractionDigits:
                2,
        },
    ).format(
        value / 100,
    )
}

export default function Index({
    filters,
    query,
    options,
    report,
    charts,
}: Props) {
    const [
        preset,
        setPreset,
    ] =
        useState(
            filters.range
                .preset,
        )

    const [
        from,
        setFrom,
    ] =
        useState(
            filters.range.from,
        )

    const [
        to,
        setTo,
    ] =
        useState(
            filters.range.to,
        )

    const [
        search,
        setSearch,
    ] =
        useState(
            filters.search ??
                '',
        )

    const [
        statuses,
        setStatuses,
    ] =
        useState<
            string[]
        >(
            filters.statuses,
        )

    const [
        paymentStatuses,
        setPaymentStatuses,
    ] =
        useState<
            string[]
        >(
            filters.payment_statuses,
        )

    const submit =
        (
            event:
                FormEvent,
        ) => {
            event.preventDefault()

            router.get(
                route(
                    'admin.reports.products.analytics',
                ),
                {
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

                    timezone:
                        filters
                            .range
                            .timezone,

                    status:
                        statuses,

                    payment_status:
                        paymentStatuses,

                    search:
                        search.trim() ||
                        undefined,
                },
                {
                    preserveScroll:
                        true,

                    preserveState:
                        true,

                    replace:
                        true,
                },
            )
        }

    const resetFilters =
        () => {
            router.get(
                route(
                    'admin.reports.products.analytics',
                ),
                {},
                {
                    preserveScroll:
                        true,

                    replace:
                        true,
                },
            )
        }

        const pdfExportUrl =
    buildPdfExportUrl(
        route(
            'admin.reports.products.analytics.pdf',
        ),
        query,
    )

    return (
        <AdminLayout
            title="Product Analytics"
            description="Visual product performance, sales trends, revenue contribution, and historical product analysis."
        >
            <div className="space-y-6">
                <ReportHeader
                    title="Product Analytics"
                    description="Visual product performance, sales trends, revenue contribution, and historical product analysis."
                    activeReport="product-analytics"
                />
                <div className="flex justify-end">
                    <a
                        href={
                            pdfExportUrl
                        }
                        className="inline-flex min-h-10 items-center justify-center rounded-lg border border-neutral-300 bg-white px-4 py-2.5 text-sm font-semibold text-neutral-700 transition hover:bg-neutral-50 focus:outline-none focus:ring-2 focus:ring-neutral-300 focus:ring-offset-2"
                    >
                        Download PDF
                    </a>
                </div>

                <section className="rounded-xl border border-neutral-200 bg-white p-4 shadow-sm sm:p-5">
                    <form
                        onSubmit={
                            submit
                        }
                        className="space-y-5"
                    >
                        <div className="grid gap-4 md:grid-cols-2 xl:grid-cols-4">
                            <label className="block">
                                <span className="text-sm font-medium text-neutral-700">
                                    Date preset
                                </span>

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
                                    className="mt-1 block w-full rounded-lg border-neutral-300 text-sm"
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
                            </label>

                            {preset ===
                                'custom' && (
                                <>
                                    <label className="block">
                                        <span className="text-sm font-medium text-neutral-700">
                                            From
                                        </span>

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
                                            className="mt-1 block w-full rounded-lg border-neutral-300 text-sm"
                                        />
                                    </label>

                                    <label className="block">
                                        <span className="text-sm font-medium text-neutral-700">
                                            To
                                        </span>

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
                                            className="mt-1 block w-full rounded-lg border-neutral-300 text-sm"
                                        />
                                    </label>
                                </>
                            )}

                            <label className="block">
                                <span className="text-sm font-medium text-neutral-700">
                                    Product / SKU
                                </span>

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
                                    placeholder="Product name or SKU"
                                    className="mt-1 block w-full rounded-lg border-neutral-300 text-sm"
                                />
                            </label>
                        </div>

                        <div className="grid gap-4 lg:grid-cols-2">
                            <FilterChecklist
                                title="Order Status"
                                options={
                                    options.statuses
                                }
                                selected={
                                    statuses
                                }
                                onToggle={(
                                    value,
                                ) =>
                                    setStatuses(
                                        toggleValue(
                                            statuses,
                                            value,
                                        ),
                                    )
                                }
                            />

                            <FilterChecklist
                                title="Payment Status"
                                options={
                                    options
                                        .payment_statuses
                                }
                                selected={
                                    paymentStatuses
                                }
                                onToggle={(
                                    value,
                                ) =>
                                    setPaymentStatuses(
                                        toggleValue(
                                            paymentStatuses,
                                            value,
                                        ),
                                    )
                                }
                            />
                        </div>

                        <div className="flex flex-wrap justify-end gap-2">
                            <button
                                type="button"
                                onClick={
                                    resetFilters
                                }
                                className="inline-flex min-h-10 items-center justify-center rounded-lg border border-neutral-300 bg-white px-4 py-2 text-sm font-semibold text-neutral-700 hover:bg-neutral-50"
                            >
                                Reset
                            </button>

                            <button
                                type="submit"
                                className="inline-flex min-h-10 items-center justify-center rounded-lg bg-neutral-900 px-4 py-2 text-sm font-semibold text-white hover:bg-neutral-800"
                            >
                                Apply filters
                            </button>
                        </div>
                    </form>
                </section>

                {report
                    .summary
                    .product_groups_count ===
                0 ? (
                    <section
                        role="status"
                        aria-live="polite"
                        className="rounded-xl border border-neutral-200 bg-white px-6 py-14 text-center shadow-sm"
                    >
                        <p className="text-base font-semibold text-neutral-900">
                            No product analytics
                            data matches the
                            current filters.
                        </p>

                        <p className="mx-auto mt-2 max-w-lg text-sm leading-6 text-neutral-500">
                            Try changing the date
                            range, order status,
                            payment status, or
                            product search.
                        </p>

                        <button
                            type="button"
                            onClick={
                                resetFilters
                            }
                            className="mt-5 inline-flex min-h-10 items-center justify-center rounded-lg border border-neutral-300 bg-white px-4 py-2.5 text-sm font-semibold text-neutral-700 hover:bg-neutral-50"
                        >
                            Reset analytics filters
                        </button>
                    </section>
                ) : (
                    <>
                        <div className="grid gap-4 sm:grid-cols-2 lg:grid-cols-3 2xl:grid-cols-4">
                            <KpiCard
                                label="Product Groups"
                                value={report
                                    .summary
                                    .product_groups_count
                                    .toLocaleString()}
                                supporting="Distinct report product groups"
                            />

                            <KpiCard
                                label="Quantity Sold"
                                value={report
                                    .summary
                                    .quantity_sold
                                    .toLocaleString()}
                                supporting="Total units sold"
                            />

                            <KpiCard
                                label="Orders"
                                value={report
                                    .summary
                                    .orders_count
                                    .toLocaleString()}
                                supporting="Distinct orders"
                            />

                            <KpiCard
                                label="Gross Sales"
                                value={formatMoney(
                                    report
                                        .summary
                                        .gross_sales,
                                )}
                                supporting="Product line revenue"
                            />

                            <KpiCard
                                label="Avg. Revenue / Order"
                                value={formatMoney(
                                    report
                                        .summary
                                        .average_revenue_per_order,
                                )}
                                supporting="Gross sales per order"
                            />

                            <KpiCard
                                label="Avg. Revenue / Item"
                                value={formatMoney(
                                    report
                                        .summary
                                        .average_revenue_per_item,
                                )}
                                supporting="Gross sales per unit"
                            />

                            <KpiCard
                                label="Current Product Revenue"
                                value={formatMoney(
                                    report
                                        .summary
                                        .current_product_revenue,
                                )}
                                supporting="Linked product revenue"
                            />

                            <KpiCard
                                label="Historical Product Revenue"
                                value={formatMoney(
                                    report
                                        .summary
                                        .historical_product_revenue,
                                )}
                                supporting="Deleted/unlinked product snapshots"
                            />
                        </div>

                        <div className="grid gap-6 xl:grid-cols-2">
                            <AnalyticsChart
                                chart={
                                    charts.sales_trend
                                }
                            />

                            <AnalyticsChart
                                chart={
                                    charts.current_vs_historical_revenue
                                }
                            />
                        </div>

                        <div className="grid gap-6 xl:grid-cols-2">
                            <AnalyticsChart
                                chart={
                                    charts.top_products_by_revenue
                                }
                            />

                            <AnalyticsChart
                                chart={
                                    charts.top_products_by_quantity
                                }
                            />
                        </div>

                        <div className="grid gap-6 xl:grid-cols-2">
                            <AnalyticsChart
                                chart={
                                    charts.sku_performance
                                }
                            />

                            <AnalyticsChart
                                chart={
                                    charts.revenue_share_by_product
                                }
                            />
                        </div>
                    </>
                )}
            </div>
        </AdminLayout>
    )
}

function KpiCard({
    label,
    value,
    supporting,
}: {
    label: string
    value: string
    supporting: string
}) {
    return (
        <section className="min-w-0 rounded-xl border border-neutral-200 bg-white p-4 shadow-sm sm:p-5">
            <p className="text-xs font-semibold uppercase tracking-wide text-neutral-500">
                {label}
            </p>

            <p className="mt-2 break-words text-2xl font-bold tracking-tight text-neutral-950">
                {value}
            </p>

            <p className="mt-2 text-sm text-neutral-500">
                {supporting}
            </p>
        </section>
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
            <legend className="px-1 text-sm font-semibold text-neutral-700">
                {title}
            </legend>

            <div className="mt-2 grid gap-2 sm:grid-cols-2 xl:grid-cols-3">
                {options.map(
                    (
                        option,
                    ) => (
                        <label
                            key={
                                option.value
                            }
                            className="flex items-center gap-2 text-sm text-neutral-700"
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

function toggleValue(
    values: string[],
    value: string,
): string[] {
    return values.includes(
        value,
    )
        ? values.filter(
              (
                  item,
              ) =>
                  item !==
                  value,
          )
        : [
              ...values,
              value,
          ]
}

function buildPdfExportUrl(
    baseUrl: string,
    query: Query,
): string {
    const url =
        new URL(
            baseUrl,
            window.location.origin,
        )

    if (
        query.preset
    ) {
        url.searchParams.set(
            'preset',
            query.preset,
        )
    }

    if (
        query.from
    ) {
        url.searchParams.set(
            'from',
            query.from,
        )
    }

    if (
        query.to
    ) {
        url.searchParams.set(
            'to',
            query.to,
        )
    }

    if (
        query.timezone
    ) {
        url.searchParams.set(
            'timezone',
            query.timezone,
        )
    }

    if (
        query.search
    ) {
        url.searchParams.set(
            'search',
            query.search,
        )
    }

    query.status?.forEach(
        (
            status,
        ) => {
            url.searchParams.append(
                'status[]',
                status,
            )
        },
    )

    query.payment_status?.forEach(
        (
            status,
        ) => {
            url.searchParams.append(
                'payment_status[]',
                status,
            )
        },
    )

    return (
        url.pathname +
        url.search
    )
}