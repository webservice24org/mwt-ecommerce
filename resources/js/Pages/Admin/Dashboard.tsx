import { Link, router } from '@inertiajs/react'
import {
    FormEvent,
    useState,
    ReactNode,
} from 'react'

import AdminLayout from '@/Layouts/Admin/AdminLayout'

interface DatePresetOption {
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

interface DashboardFilters {
    presets: DatePresetOption[]
    range: ReportRange
}

interface DashboardMetrics {
    orders_count: number

    gross_sales: number
    discount_total: number
    shipping_total: number
    tax_total: number
    net_sales: number

    paid_orders_count: number
    paid_total: number

    due_orders_count: number
    due_total: number

    unpaid_orders_count: number
    unpaid_total: number

    products_sold: number
    customers_count: number

    average_order_value: number
}

interface TrendPoint {
    date: string
    orders_count: number
    net_sales: number
    products_sold: number
}

interface BreakdownItem {
    value: string
    label: string
    orders_count: number
    total: number
}

interface DashboardBreakdowns {
    payment: BreakdownItem[]
    fulfilment: BreakdownItem[]
}

interface RecentOrder {
    id: number
    order_number: string
    customer_name: string

    status: string
    status_label: string

    payment_status: string
    payment_status_label: string

    fulfilment_status: string
    fulfilment_status_label: string

    currency_code: string
    grand_total: number
    placed_at: string
}

interface OperationalSummary {
    pending_orders_count: number
    processing_orders_count: number
    completed_orders_count: number

    unfulfilled_orders_count: number
    fulfilment_processing_orders_count: number
    shipped_orders_count: number
    delivered_orders_count: number
    returned_orders_count: number

    due_payment_orders_count: number
    due_payment_total: number

    unpaid_payment_orders_count: number
    unpaid_payment_total: number
}

interface DashboardProps {
    filters: DashboardFilters
    metrics: DashboardMetrics
    trend: TrendPoint[]
    breakdowns: DashboardBreakdowns
    recentOrders: RecentOrder[]
    operationalSummary: OperationalSummary

    topProducts: TopProduct[]
    inventorySummary: InventorySummary
    inventoryAlerts: InventoryAlert[]
}

interface TopProduct {
    product_id: number | null
    product_name: string
    sku: string | null
    quantity_sold: number
    orders_count: number
    gross_sales: number
}

interface InventorySummary {
    tracked_products_count: number
    in_stock_products_count: number
    low_stock_products_count: number
    out_of_stock_products_count: number
    untracked_products_count: number
    total_units_in_stock: number
    low_stock_threshold: number
}

interface InventoryAlert {
    product_id: number
    name: string
    slug: string
    sku: string | null
    status: string
    stock_quantity: number
    low_stock_threshold: number
    out_of_stock: boolean
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

        case 'unpaid':
        case 'failed':
        case 'returned':
        case 'refunded':
            return 'bg-rose-50 text-rose-700 ring-rose-200'

        default:
            return 'bg-neutral-50 text-neutral-700 ring-neutral-200'
    }
}

export default function Dashboard({
    filters,
    metrics,
    trend,
    breakdowns,
    recentOrders,
    operationalSummary,
    topProducts,
    inventorySummary,
    inventoryAlerts,
}: DashboardProps) {
    const [
        selectedPreset,
        setSelectedPreset,
    ] = useState(
        filters.range.preset,
    )

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

        const query: Record<
            string,
            string
        > = {
            preset,
        }

        if (
            preset === 'custom'
        ) {
            query.from =
                from

            query.to =
                to
        }

        router.get(
            route(
                'admin.dashboard',
            ),
            query,
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

    return (
        <AdminLayout
            title="Dashboard"
            description="Store performance and operational overview."
        >
            <div className="space-y-6">
                <section className="rounded-xl border border-neutral-200 bg-white p-4 shadow-sm sm:p-5">
                    <form
                        onSubmit={
                            submitFilters
                        }
                        className="flex flex-col gap-4 lg:flex-row lg:items-end"
                    >
                        <div className="min-w-0 flex-1">
                            <label
                                htmlFor="dashboard-preset"
                                className="mb-1.5 block text-sm font-medium text-neutral-700"
                            >
                                Date range
                            </label>

                            <select
                                id="dashboard-preset"
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
                                className="w-full rounded-lg border border-neutral-300 bg-white px-3 py-2.5 text-sm text-neutral-900 shadow-sm outline-none transition focus:border-neutral-500 focus:ring-2 focus:ring-neutral-200"
                            >
                                {filters.presets.map(
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
                                <div className="min-w-0 flex-1">
                                    <label
                                        htmlFor="dashboard-from"
                                        className="mb-1.5 block text-sm font-medium text-neutral-700"
                                    >
                                        From
                                    </label>

                                    <input
                                        id="dashboard-from"
                                        name="from"
                                        type="date"
                                        defaultValue={
                                            filters
                                                .range
                                                .from
                                        }
                                        required
                                        className="w-full rounded-lg border border-neutral-300 bg-white px-3 py-2.5 text-sm text-neutral-900 shadow-sm outline-none transition focus:border-neutral-500 focus:ring-2 focus:ring-neutral-200"
                                    />
                                </div>

                                <div className="min-w-0 flex-1">
                                    <label
                                        htmlFor="dashboard-to"
                                        className="mb-1.5 block text-sm font-medium text-neutral-700"
                                    >
                                        To
                                    </label>

                                    <input
                                        id="dashboard-to"
                                        name="to"
                                        type="date"
                                        defaultValue={
                                            filters
                                                .range
                                                .to
                                        }
                                        required
                                        className="w-full rounded-lg border border-neutral-300 bg-white px-3 py-2.5 text-sm text-neutral-900 shadow-sm outline-none transition focus:border-neutral-500 focus:ring-2 focus:ring-neutral-200"
                                    />
                                </div>
                            </>
                        )}

                        <button
                            type="submit"
                            className="inline-flex min-h-10 items-center justify-center rounded-lg bg-neutral-900 px-5 py-2.5 text-sm font-semibold text-white transition hover:bg-neutral-800"
                        >
                            Apply
                        </button>
                    </form>

                    <div className="mt-4 flex flex-wrap items-center gap-x-4 gap-y-1 border-t border-neutral-100 pt-4 text-xs text-neutral-500">
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

                <section className="grid gap-4 sm:grid-cols-2 xl:grid-cols-4">
                    <MetricCard
                        label="Orders"
                        value={formatNumber(
                            metrics.orders_count,
                        )}
                        helper={`${formatNumber(
                            metrics.products_sold,
                        )} products sold`}
                    />

                    <MetricCard
                        label="Net sales"
                        value={formatMoney(
                            metrics.net_sales,
                        )}
                        helper={`Gross ${formatMoney(
                            metrics.gross_sales,
                        )}`}
                    />

                    <MetricCard
                        label="Customers"
                        value={formatNumber(
                            metrics.customers_count,
                        )}
                        helper="Distinct customers"
                    />

                    <MetricCard
                        label="Average order value"
                        value={formatMoney(
                            metrics.average_order_value,
                        )}
                        helper="Non-cancelled orders"
                    />
                </section>

                <section className="grid gap-6 xl:grid-cols-3">
                    <div className="rounded-xl border border-neutral-200 bg-white p-5 shadow-sm xl:col-span-2">
                        <div>
                            <h2 className="text-base font-semibold text-neutral-900">
                                Sales trend
                            </h2>

                            <p className="mt-1 text-sm text-neutral-500">
                                Daily net sales
                                in the selected
                                range.
                            </p>
                        </div>

                        <TrendBars
                            trend={
                                trend
                            }
                        />
                    </div>

                    <div className="rounded-xl border border-neutral-200 bg-white p-5 shadow-sm">
                        <h2 className="text-base font-semibold text-neutral-900">
                            Operational
                            summary
                        </h2>

                        <div className="mt-4 space-y-3">
                            <SummaryRow
                                label="Pending orders"
                                value={
                                    operationalSummary.pending_orders_count
                                }
                            />

                            <SummaryRow
                                label="Processing orders"
                                value={
                                    operationalSummary.processing_orders_count
                                }
                            />

                            <SummaryRow
                                label="Completed orders"
                                value={
                                    operationalSummary.completed_orders_count
                                }
                            />

                            <SummaryRow
                                label="Unfulfilled"
                                value={
                                    operationalSummary.unfulfilled_orders_count
                                }
                            />

                            <SummaryRow
                                label="Ready / processing fulfilment"
                                value={
                                    operationalSummary.fulfilment_processing_orders_count
                                }
                            />

                            <SummaryRow
                                label="Shipped"
                                value={
                                    operationalSummary.shipped_orders_count
                                }
                            />

                            <SummaryRow
                                label="Delivered"
                                value={
                                    operationalSummary.delivered_orders_count
                                }
                            />

                            <SummaryRow
                                label="Returned"
                                value={
                                    operationalSummary.returned_orders_count
                                }
                            />
                        </div>
                    </div>
                </section>

                <section className="grid gap-6 xl:grid-cols-2">
                    <BreakdownCard
                        title="Payment breakdown"
                        items={
                            breakdowns.payment
                        }
                    />

                    <BreakdownCard
                        title="Fulfilment breakdown"
                        items={
                            breakdowns.fulfilment
                        }
                    />
                </section>

                <section className="grid gap-4 md:grid-cols-2">
                    <AlertCard
                        title="Due payments"
                        count={
                            operationalSummary.due_payment_orders_count
                        }
                        total={
                            operationalSummary.due_payment_total
                        }
                    />

                    <AlertCard
                        title="Unpaid orders"
                        count={
                            operationalSummary.unpaid_payment_orders_count
                        }
                        total={
                            operationalSummary.unpaid_payment_total
                        }
                    />
                </section>


                <section>
                    <div className="mb-3">
                        <h2 className="text-base font-semibold text-neutral-900">
                            Inventory overview
                        </h2>
                        <p className="mt-1 text-sm text-neutral-500">
                            Current inventory state across tracked products.
                        </p>
                    </div>

                    <div className="grid gap-4 sm:grid-cols-2 xl:grid-cols-4">
                        <MetricCard
                            label="Tracked products"
                            value={formatNumber(inventorySummary.tracked_products_count)}
                            helper={`${formatNumber(inventorySummary.total_units_in_stock)} units currently in stock`}
                        />
                        <MetricCard
                            label="Healthy stock"
                            value={formatNumber(inventorySummary.in_stock_products_count)}
                            helper={`More than ${formatNumber(inventorySummary.low_stock_threshold)} units`}
                        />
                        <MetricCard
                            label="Low stock"
                            value={formatNumber(inventorySummary.low_stock_products_count)}
                            helper={`1–${formatNumber(inventorySummary.low_stock_threshold)} units remaining`}
                        />
                        <MetricCard
                            label="Out of stock"
                            value={formatNumber(inventorySummary.out_of_stock_products_count)}
                            helper={`${formatNumber(inventorySummary.untracked_products_count)} products do not track stock`}
                        />
                    </div>
                </section>

                <section className="grid gap-6 xl:grid-cols-2">
                    <TopProductsCard products={topProducts} />
                    <InventoryAlertsCard
                        alerts={inventoryAlerts}
                        threshold={inventorySummary.low_stock_threshold}
                    />
                </section>

                <section className="rounded-xl border border-neutral-200 bg-white shadow-sm">
                    <div className="flex flex-col gap-2 border-b border-neutral-200 px-4 py-4 sm:flex-row sm:items-center sm:justify-between sm:px-5">
                        <div>
                            <h2 className="text-base font-semibold text-neutral-900">
                                Recent orders
                            </h2>

                            <p className="mt-1 text-sm text-neutral-500">
                                Latest
                                non-cancelled
                                orders in the
                                selected range.
                            </p>
                        </div>

                        <Link
                            href={route(
                                'admin.orders.index',
                            )}
                            className="text-sm font-semibold text-neutral-700 hover:text-neutral-950"
                        >
                            View all orders
                        </Link>
                    </div>

                    {recentOrders.length ===
                    0 ? (
                        <div className="px-5 py-10 text-center text-sm text-neutral-500">
                            No orders found
                            for this range.
                        </div>
                    ) : (
                        <>
                            <div className="hidden overflow-x-auto md:block">
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
                                                Payment
                                            </TableHead>

                                            <TableHead>
                                                Fulfilment
                                            </TableHead>

                                            <TableHead>
                                                Total
                                            </TableHead>

                                            <TableHead>
                                                Placed
                                            </TableHead>
                                        </tr>
                                    </thead>

                                    <tbody className="divide-y divide-neutral-100 bg-white">
                                        {recentOrders.map(
                                            (
                                                order,
                                            ) => (
                                                <tr
                                                    key={
                                                        order.id
                                                    }
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

                            <div className="divide-y divide-neutral-100 md:hidden">
                                {recentOrders.map(
                                    (
                                        order,
                                    ) => (
                                        <div
                                            key={
                                                order.id
                                            }
                                            className="space-y-3 p-4"
                                        >
                                            <div className="flex items-start justify-between gap-4">
                                                <div>
                                                    <Link
                                                        href={route(
                                                            'admin.orders.show',
                                                            order.id,
                                                        )}
                                                        className="font-semibold text-neutral-900"
                                                    >
                                                        {
                                                            order.order_number
                                                        }
                                                    </Link>

                                                    <p className="mt-1 text-sm text-neutral-500">
                                                        {
                                                            order.customer_name
                                                        }
                                                    </p>
                                                </div>

                                                <span className="font-bold text-neutral-900">
                                                    {formatMoney(
                                                        order.grand_total,
                                                    )}
                                                </span>
                                            </div>

                                            <div className="flex flex-wrap gap-2">
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

                                            <p className="text-xs text-neutral-500">
                                                {formatDateTime(
                                                    order.placed_at,
                                                )}
                                            </p>
                                        </div>
                                    ),
                                )}
                            </div>
                        </>
                    )}
                </section>
            </div>
        </AdminLayout>
    )
}

function MetricCard({
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

            <p className="mt-2 break-words text-2xl font-bold text-neutral-900 sm:text-3xl">
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

function TrendBars({
    trend,
}: {
    trend: TrendPoint[]
}) {
    const max =
        Math.max(
            ...trend.map(
                (point) =>
                    point.net_sales,
            ),
            1,
        )

    return (
        <div className="mt-6">
            <div className="flex h-52 items-end gap-1 overflow-x-auto border-b border-neutral-200 pb-2">
                {trend.map(
                    (
                        point,
                    ) => {
                        const height =
                            Math.max(
                                3,
                                Math.round(
                                    (
                                        point.net_sales /
                                        max
                                    ) *
                                        100,
                                ),
                            )

                        return (
                            <div
                                key={
                                    point.date
                                }
                                className="flex min-w-8 flex-1 flex-col items-center justify-end gap-2"
                                title={`${point.date}: ${formatMoney(
                                    point.net_sales,
                                )}`}
                            >
                                <div className="flex h-40 w-full items-end">
                                    <div
                                        className="w-full rounded-t bg-neutral-900"
                                        style={{
                                            height: `${height}%`,
                                        }}
                                    />
                                </div>

                                <span className="whitespace-nowrap text-[10px] text-neutral-500">
                                    {point.date.slice(
                                        5,
                                    )}
                                </span>
                            </div>
                        )
                    },
                )}
            </div>
        </div>
    )
}

function BreakdownCard({
    title,
    items,
}: {
    title: string
    items: BreakdownItem[]
}) {
    return (
        <div className="rounded-xl border border-neutral-200 bg-white p-5 shadow-sm">
            <h2 className="text-base font-semibold text-neutral-900">
                {title}
            </h2>

            <div className="mt-4 space-y-3">
                {items.map(
                    (
                        item,
                    ) => (
                        <div
                            key={
                                item.value
                            }
                            className="flex items-center justify-between gap-4 rounded-lg border border-neutral-100 px-3 py-3"
                        >
                            <div>
                                <p className="text-sm font-medium text-neutral-900">
                                    {
                                        item.label
                                    }
                                </p>

                                <p className="mt-0.5 text-xs text-neutral-500">
                                    {formatNumber(
                                        item.orders_count,
                                    )}{' '}
                                    {item.orders_count ===
                                    1
                                        ? 'order'
                                        : 'orders'}
                                </p>
                            </div>

                            <span className="text-sm font-semibold text-neutral-900">
                                {formatMoney(
                                    item.total,
                                )}
                            </span>
                        </div>
                    ),
                )}
            </div>
        </div>
    )
}

function AlertCard({
    title,
    count,
    total,
}: {
    title: string
    count: number
    total: number
}) {
    return (
        <div className="rounded-xl border border-amber-200 bg-amber-50 p-5">
            <p className="text-sm font-semibold text-amber-900">
                {title}
            </p>

            <p className="mt-2 text-2xl font-bold text-amber-950">
                {formatMoney(
                    total,
                )}
            </p>

            <p className="mt-1 text-sm text-amber-800">
                {formatNumber(
                    count,
                )}{' '}
                {count === 1
                    ? 'order'
                    : 'orders'}
            </p>
        </div>
    )
}

function SummaryRow({
    label,
    value,
}: {
    label: string
    value: number
}) {
    return (
        <div className="flex items-center justify-between gap-4 border-b border-neutral-100 pb-3 last:border-0 last:pb-0">
            <span className="text-sm text-neutral-600">
                {label}
            </span>

            <span className="font-semibold text-neutral-900">
                {formatNumber(
                    value,
                )}
            </span>
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

function TableHead({
    children,
}: {
    children: ReactNode
}) {
    return (
        <th className="px-4 py-3 text-left text-xs font-semibold uppercase tracking-wide text-neutral-500">
            {children}
        </th>
    )
}

function TableCell({
    children,
}: {
    children: ReactNode
}) {
    return (
        <td className="whitespace-nowrap px-4 py-3 text-neutral-600">
            {children}
        </td>
    )
}


function TopProductsCard({
    products,
}: {
    products: TopProduct[]
}) {
    return (
        <div className="rounded-xl border border-neutral-200 bg-white shadow-sm">
            <div className="border-b border-neutral-200 px-4 py-4 sm:px-5">
                <h2 className="text-base font-semibold text-neutral-900">
                    Top products
                </h2>
                <p className="mt-1 text-sm text-neutral-500">
                    Best-selling products in the selected reporting range.
                </p>
            </div>

            {products.length === 0 ? (
                <div className="px-5 py-10 text-center text-sm text-neutral-500">
                    No product sales found for this range.
                </div>
            ) : (
                <div className="divide-y divide-neutral-100">
                    {products.map((product, index) => (
                        <div
                            key={`${product.product_id ?? 'historical'}-${product.product_name}-${product.sku ?? index}`}
                            className="flex items-start gap-4 px-4 py-4 sm:px-5"
                        >
                            <div className="flex h-8 w-8 shrink-0 items-center justify-center rounded-full bg-neutral-100 text-xs font-bold text-neutral-700">
                                {index + 1}
                            </div>

                            <div className="min-w-0 flex-1">
                                <div className="flex flex-col gap-2 sm:flex-row sm:items-start sm:justify-between">
                                    <div className="min-w-0">
                                        <p className="truncate text-sm font-semibold text-neutral-900">
                                            {product.product_name}
                                        </p>
                                        {product.sku && (
                                            <p className="mt-1 truncate text-xs text-neutral-500">
                                                SKU: {product.sku}
                                            </p>
                                        )}
                                        {product.product_id === null && (
                                            <p className="mt-1 text-xs font-medium text-amber-700">
                                                Historical product
                                            </p>
                                        )}
                                    </div>
                                    <p className="shrink-0 text-sm font-bold text-neutral-900">
                                        {formatMoney(product.gross_sales)}
                                    </p>
                                </div>

                                <div className="mt-3 flex flex-wrap gap-x-5 gap-y-1 text-xs text-neutral-500">
                                    <span>{formatNumber(product.quantity_sold)} units sold</span>
                                    <span>
                                        {formatNumber(product.orders_count)}{' '}
                                        {product.orders_count === 1 ? 'order' : 'orders'}
                                    </span>
                                </div>
                            </div>
                        </div>
                    ))}
                </div>
            )}
        </div>
    )
}

function InventoryAlertsCard({
    alerts,
    threshold,
}: {
    alerts: InventoryAlert[]
    threshold: number
}) {
    return (
        <div className="rounded-xl border border-neutral-200 bg-white shadow-sm">
            <div className="border-b border-neutral-200 px-4 py-4 sm:px-5">
                <h2 className="text-base font-semibold text-neutral-900">
                    Inventory alerts
                </h2>
                <p className="mt-1 text-sm text-neutral-500">
                    Products with {formatNumber(threshold)} units or fewer.
                </p>
            </div>

            {alerts.length === 0 ? (
                <div className="px-5 py-10 text-center text-sm text-neutral-500">
                    No low-stock products.
                </div>
            ) : (
                <div className="divide-y divide-neutral-100">
                    {alerts.map((alert) => (
                        <div
                            key={alert.product_id}
                            className="flex flex-col gap-3 px-4 py-4 sm:flex-row sm:items-center sm:justify-between sm:px-5"
                        >
                            <div className="min-w-0">
                                <div className="flex flex-wrap items-center gap-2">
                                    <p className="truncate text-sm font-semibold text-neutral-900">
                                        {alert.name}
                                    </p>
                                    <InventoryBadge outOfStock={alert.out_of_stock} />
                                </div>
                                <div className="mt-1 flex flex-wrap gap-x-3 gap-y-1 text-xs text-neutral-500">
                                    {alert.sku && <span>SKU: {alert.sku}</span>}
                                    <span className="capitalize">{alert.status}</span>
                                </div>
                            </div>

                            <div className="shrink-0 sm:text-right">
                                <p
                                    className={
                                        alert.out_of_stock
                                            ? 'text-lg font-bold text-rose-700'
                                            : 'text-lg font-bold text-amber-700'
                                    }
                                >
                                    {formatNumber(alert.stock_quantity)}
                                </p>
                                <p className="text-xs text-neutral-500">
                                    units remaining
                                </p>
                            </div>
                        </div>
                    ))}
                </div>
            )}
        </div>
    )
}

function InventoryBadge({
    outOfStock,
}: {
    outOfStock: boolean
}) {
    return (
        <span
            className={
                outOfStock
                    ? 'inline-flex rounded-full bg-rose-50 px-2 py-0.5 text-xs font-semibold text-rose-700 ring-1 ring-inset ring-rose-200'
                    : 'inline-flex rounded-full bg-amber-50 px-2 py-0.5 text-xs font-semibold text-amber-700 ring-1 ring-inset ring-amber-200'
            }
        >
            {outOfStock ? 'Out of stock' : 'Low stock'}
        </span>
    )
}
