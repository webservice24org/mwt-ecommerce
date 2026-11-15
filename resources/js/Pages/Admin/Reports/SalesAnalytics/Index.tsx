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
    orders_count: number
    items_quantity: number
    subtotal: number
    discount_total: number
    delivery_total: number
    tax_total: number
    grand_total: number
    paid_revenue: number
    average_order_value: number
}

interface Report {
    summary: Summary
}

interface Charts {
    revenue_trend: ReportChart
    orders_trend: ReportChart
    status_revenue: ReportChart
    payment_status_revenue: ReportChart
    payment_method_revenue: ReportChart
}

interface Props {
    filters: Filters
    query: Query

    options: {
        presets: Option[]
        statuses: Option[]
        payment_statuses: Option[]
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
    ] = useState(
        filters.range
            .preset,
    )

    const [
        from,
        setFrom,
    ] = useState(
        filters.range.from,
    )

    const [
        to,
        setTo,
    ] = useState(
        filters.range.to,
    )

    const [
        search,
        setSearch,
    ] = useState(
        filters.search ??
            '',
    )

    const [
        statuses,
        setStatuses,
    ] = useState<
        string[]
    >(
        filters.statuses,
    )

    const [
        paymentStatuses,
        setPaymentStatuses,
    ] = useState<
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
                    'admin.reports.sales.analytics',
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
                        filters.range
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
                    'admin.reports.sales.analytics',
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
        const hasActiveFilters =
            preset !== 'last_30_days' ||
            statuses.length > 0 ||
            paymentStatuses.length > 0 ||
            search.trim() !== ''

        const pdfExportUrl =
        buildPdfExportUrl(
            route(
                'admin.reports.sales.analytics.pdf',
            ),
            query,
        )

    return (
        <AdminLayout
            title="Sales Analytics"
            description="Visual sales performance, revenue trends, and operational breakdowns."
        >
            <div className="space-y-6">
                <ReportHeader
                    title="Sales Analytics"
                    description="Visual sales performance, revenue trends, and operational breakdowns."
                    activeReport="sales-analytics"
                />
                <div className="flex justify-end">
                    <a
                        href={
                            pdfExportUrl
                        }
                        className="inline-flex min-h-10 items-center justify-center rounded-lg border border-neutral-300 bg-white px-4 py-2.5 text-sm font-semibold text-neutral-700 transition hover:bg-neutral-50"
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
                                    Date
                                    preset
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
                                    Search
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
                                    placeholder="Order or customer"
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
                                    options.payment_statuses
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

                    {hasActiveFilters && (
                        <div className="flex flex-wrap items-center gap-2">
                            <span className="text-xs font-semibold uppercase tracking-wide text-neutral-500">
                                Active filters
                            </span>

                            {preset && (
                                <FilterChip
                                    label={`Date: ${presetLabel(
                                        preset,
                                        options.presets,
                                    )}`}
                                />
                            )}

                            {statuses.map(
                                (
                                    status,
                                ) => (
                                    <FilterChip
                                        key={`status-${status}`}
                                        label={`Status: ${optionLabel(
                                            status,
                                            options.statuses,
                                        )}`}
                                    />
                                ),
                            )}

                            {paymentStatuses.map(
                                (
                                    status,
                                ) => (
                                    <FilterChip
                                        key={`payment-${status}`}
                                        label={`Payment: ${optionLabel(
                                            status,
                                            options.payment_statuses,
                                        )}`}
                                    />
                                ),
                            )}

                            {search.trim() !==
                                '' && (
                                <FilterChip
                                    label={`Search: ${search.trim()}`}
                                />
                            )}
                        </div>
                    )}
                </section>

                {report.summary.orders_count ===
                0 ? (
                    <section
                        role="status"
                        aria-live="polite"
                        className="rounded-xl border border-neutral-200 bg-white px-6 py-14 text-center shadow-sm"
                    >
                        <p className="text-base font-semibold text-neutral-900">
                            No sales analytics
                            data matches the
                            current filters.
                        </p>

                        <p className="mx-auto mt-2 max-w-lg text-sm leading-6 text-neutral-500">
                            Try changing the date
                            range, order status,
                            payment status, or
                            search term.
                        </p>

                        <button
                            type="button"
                            onClick={
                                resetFilters
                            }
                            className="mt-5 inline-flex min-h-10 items-center justify-center rounded-lg border border-neutral-300 bg-white px-4 py-2.5 text-sm font-semibold text-neutral-700 transition hover:bg-neutral-50 focus:outline-none focus:ring-2 focus:ring-neutral-300 focus:ring-offset-2"
                        >
                            Reset analytics filters
                        </button>
                    </section>
                ) : (
                    <>
                        <div className="grid gap-4 sm:grid-cols-2 lg:grid-cols-3 2xl:grid-cols-4">
                            <KpiCard
                                label="Total Revenue"
                                value={formatMoney(
                                    report
                                        .summary
                                        .grand_total,
                                )}
                                supporting={`${report.summary.orders_count.toLocaleString()} orders`}
                            />

                            <KpiCard
                                label="Paid Revenue"
                                value={formatMoney(
                                    report
                                        .summary
                                        .paid_revenue,
                                )}
                                supporting="Captured revenue"
                            />

                            <KpiCard
                                label="Average Order Value"
                                value={formatMoney(
                                    report
                                        .summary
                                        .average_order_value,
                                )}
                                supporting={`${report.summary.items_quantity.toLocaleString()} items sold`}
                            />

                            <KpiCard
                                label="Discounts"
                                value={formatMoney(
                                    report
                                        .summary
                                        .discount_total,
                                )}
                                supporting="Total discount applied"
                            />

                            <KpiCard
                                label="Subtotal"
                                value={formatMoney(
                                    report
                                        .summary
                                        .subtotal,
                                )}
                                supporting="Before adjustments"
                            />

                            <KpiCard
                                label="Delivery Revenue"
                                value={formatMoney(
                                    report
                                        .summary
                                        .delivery_total,
                                )}
                                supporting="Shipping collected"
                            />

                            <KpiCard
                                label="Tax"
                                value={formatMoney(
                                    report
                                        .summary
                                        .tax_total,
                                )}
                                supporting="Tax collected"
                            />

                            <KpiCard
                                label="Orders"
                                value={report
                                    .summary
                                    .orders_count
                                    .toLocaleString()}
                                supporting={`${report.summary.items_quantity.toLocaleString()} items`}
                            />
                        </div>

                        <div className="grid gap-6 xl:grid-cols-2">
                            <AnalyticsChart
                                chart={
                                    charts.revenue_trend
                                }
                            />

                            <AnalyticsChart
                                chart={
                                    charts.orders_trend
                                }
                            />
                        </div>

                        <div className="grid gap-6 xl:grid-cols-3">
                            <AnalyticsChart
                                chart={
                                    charts.status_revenue
                                }
                            />

                            <AnalyticsChart
                                chart={
                                    charts.payment_status_revenue
                                }
                            />

                            <AnalyticsChart
                                chart={
                                    charts.payment_method_revenue
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
                {
                    supporting
                }
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

function FilterChip({
    label,
}: {
    label: string
}) {
    return (
        <span className="inline-flex items-center rounded-full border border-neutral-200 bg-neutral-50 px-2.5 py-1 text-xs font-medium text-neutral-700">
            {label}
        </span>
    )
}

function optionLabel(
    value: string,
    options: Option[],
): string {
    return (
        options.find(
            (
                option,
            ) =>
                option.value ===
                value,
        )?.label ??
        value
    )
}

function presetLabel(
    value: string,
    options: Option[],
): string {
    return optionLabel(
        value,
        options,
    )
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