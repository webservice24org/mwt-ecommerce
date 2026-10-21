import { Link, usePage } from '@inertiajs/react'
import { ArrowLeft, ArrowRight, Package, ShoppingBag } from 'lucide-react'

import CustomerLayout from '@/Layouts/Customer/CustomerLayout'
import { formatMoney } from '@/lib/money'
import type { PageProps } from '@/types'

interface CustomerOrderStatus {
    value: string
    label: string
}

interface CustomerOrderListItem {
    id: number
    order_number: string
    status: CustomerOrderStatus
    currency_code: string
    grand_total: number
    items_count: number
    placed_at: string | null
}

interface PaginationLink {
    url: string | null
    label: string
    active: boolean
}

interface CustomerOrdersPaginator {
    current_page: number
    data: CustomerOrderListItem[]
    first_page_url: string
    from: number | null
    last_page: number
    last_page_url: string
    links: PaginationLink[]
    next_page_url: string | null
    path: string
    per_page: number
    prev_page_url: string | null
    to: number | null
    total: number
}

type CustomerOrdersPageProps = {
    orders: CustomerOrdersPaginator
}

type OrdersPageProps = PageProps<CustomerOrdersPageProps>

export default function Index() {
    const { orders } = usePage<OrdersPageProps>().props

    return (
        <CustomerLayout title="My Orders" description="Review the orders placed from your account.">
            <div className="space-y-6">
                <div className="flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">
                    <Link
                        href={route('customer.dashboard')}
                        className="inline-flex min-h-11 w-fit items-center gap-2 rounded-xl border border-neutral-300 bg-white px-4 text-sm font-semibold text-neutral-700 transition hover:bg-neutral-50 focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-neutral-950"
                    >
                        <ArrowLeft className="h-4 w-4" aria-hidden="true" />
                        Account
                    </Link>

                    <p className="text-sm text-neutral-500">
                        {orders.total === 1 ? '1 order' : `${orders.total} orders`}
                    </p>
                </div>

                {orders.data.length === 0 ? (
                    <EmptyOrders />
                ) : (
                    <>
                        <section aria-labelledby="orders-heading" className="space-y-3">
                            <h2 id="orders-heading" className="sr-only">
                                Order history
                            </h2>

                            {orders.data.map((order) => (
                                <OrderCard key={order.id} order={order} />
                            ))}
                        </section>

                        <Pagination links={orders.links} />
                    </>
                )}
            </div>
        </CustomerLayout>
    )
}

function OrderCard({ order }: { order: CustomerOrderListItem }) {
    return (
        <article className="overflow-hidden rounded-2xl border border-neutral-200 bg-white shadow-sm">
            <div className="flex flex-col gap-4 p-5 sm:p-6">
                <div className="flex flex-col gap-4 sm:flex-row sm:items-start sm:justify-between">
                    <div className="flex min-w-0 items-start gap-3">
                        <div className="flex h-11 w-11 shrink-0 items-center justify-center rounded-xl bg-neutral-100 text-neutral-700">
                            <Package className="h-5 w-5" aria-hidden="true" />
                        </div>

                        <div className="min-w-0">
                            <p className="text-xs font-semibold uppercase tracking-wide text-neutral-500">
                                Order
                            </p>

                            <h3 className="mt-1 break-all text-base font-bold text-neutral-950">
                                {order.order_number}
                            </h3>

                            <p className="mt-1 text-sm text-neutral-500">
                                {formatPlacedAt(order.placed_at)}
                            </p>
                        </div>
                    </div>

                    <StatusBadge label={order.status.label} value={order.status.value} />
                </div>

                <div className="grid gap-3 border-t border-neutral-100 pt-4 sm:grid-cols-2">
                    <div>
                        <p className="text-xs font-medium uppercase tracking-wide text-neutral-500">
                            Items
                        </p>

                        <p className="mt-1 font-semibold text-neutral-950">
                            {order.items_count} {order.items_count === 1 ? 'item' : 'items'}
                        </p>
                    </div>

                    <div className="sm:text-right">
                        <p className="text-xs font-medium uppercase tracking-wide text-neutral-500">
                            Total
                        </p>

                        <p className="mt-1 text-lg font-bold text-neutral-950">
                            {formatMoney(order.grand_total)}
                        </p>
                    </div>
                </div>

                <div className="border-t border-neutral-100 pt-4">
                    <Link
                        href={route('customer.orders.show', order.id)}
                        className="inline-flex min-h-10 items-center justify-center gap-2 rounded-xl border border-neutral-300 bg-white px-4 text-sm font-semibold text-neutral-700 transition hover:bg-neutral-50 focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-neutral-950"
                    >
                        View order
                        <ArrowRight className="h-4 w-4" aria-hidden="true" />
                    </Link>
                </div>
            </div>
        </article>
    )
}

function StatusBadge({ label, value }: { label: string; value: string }) {
    const className =
        value === 'cancelled'
            ? 'border-red-200 bg-red-50 text-red-700'
            : value === 'completed'
              ? 'border-emerald-200 bg-emerald-50 text-emerald-700'
              : 'border-amber-200 bg-amber-50 text-amber-700'

    return (
        <span
            className={`inline-flex w-fit items-center rounded-full border px-3 py-1 text-xs font-semibold ${className}`}
        >
            {label}
        </span>
    )
}

function EmptyOrders() {
    return (
        <section className="rounded-2xl border border-dashed border-neutral-300 bg-white px-6 py-14 text-center">
            <div className="mx-auto flex h-12 w-12 items-center justify-center rounded-2xl bg-neutral-100 text-neutral-600">
                <ShoppingBag className="h-5 w-5" aria-hidden="true" />
            </div>

            <h2 className="mt-4 text-lg font-bold text-neutral-950">No orders yet</h2>

            <p className="mx-auto mt-2 max-w-md text-sm leading-6 text-neutral-500">
                Orders placed from your account will appear here.
            </p>

            <Link
                href="/products"
                className="mt-5 inline-flex min-h-11 items-center justify-center gap-2 rounded-xl bg-neutral-950 px-5 text-sm font-semibold text-white transition hover:bg-neutral-800 focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-neutral-950 focus-visible:ring-offset-2"
            >
                Browse products
                <ArrowRight className="h-4 w-4" aria-hidden="true" />
            </Link>
        </section>
    )
}

function Pagination({ links }: { links: PaginationLink[] }) {
    if (links.length <= 3) {
        return null
    }

    return (
        <nav
            aria-label="Order history pagination"
            className="flex flex-wrap items-center justify-center gap-2"
        >
            {links.map((link, index) => {
                const label = decodePaginationLabel(link.label)

                if (link.url === null) {
                    return (
                        <span
                            key={`${link.label}-${index}`}
                            className="inline-flex min-h-10 min-w-10 cursor-not-allowed items-center justify-center rounded-lg border border-neutral-200 px-3 text-sm text-neutral-400"
                        >
                            {label}
                        </span>
                    )
                }

                return (
                    <Link
                        key={`${link.label}-${index}`}
                        href={link.url}
                        preserveScroll
                        className={
                            link.active
                                ? 'inline-flex min-h-10 min-w-10 items-center justify-center rounded-lg bg-neutral-950 px-3 text-sm font-semibold text-white'
                                : 'inline-flex min-h-10 min-w-10 items-center justify-center rounded-lg border border-neutral-300 bg-white px-3 text-sm font-medium text-neutral-700 transition hover:bg-neutral-50 focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-neutral-950'
                        }
                        aria-current={link.active ? 'page' : undefined}
                    >
                        {label}
                    </Link>
                )
            })}
        </nav>
    )
}

function formatPlacedAt(value: string | null): string {
    if (value === null) {
        return 'Order date unavailable'
    }

    const date = new Date(value)

    if (Number.isNaN(date.getTime())) {
        return 'Order date unavailable'
    }

    return new Intl.DateTimeFormat(undefined, {
        dateStyle: 'medium',
    }).format(date)
}

function decodePaginationLabel(label: string): string {
    return label.replace('&laquo;', '«').replace('&raquo;', '»')
}
