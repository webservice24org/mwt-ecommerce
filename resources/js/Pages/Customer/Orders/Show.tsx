import { Link, usePage } from '@inertiajs/react'
import {
    ArrowLeft,
    CalendarDays,
    Package,
    ReceiptText,
    ShoppingBag,
    MapPin,
    Phone,
    CircleDollarSign,
    Truck,
} from 'lucide-react'

import CustomerLayout from '@/Layouts/Customer/CustomerLayout'
import { formatMoney } from '@/lib/money'
import type { PageProps } from '@/types'
import type { ReactNode } from 'react'

interface CustomerOrderStatus {
    value: string
    label: string
}

interface CustomerOrderPayment {
    method: CustomerOrderStatus
    status: CustomerOrderStatus
}

interface CustomerOrderItem {
    id: number
    product_id: number | null
    variant_id: number | null
    product_name: string
    sku: string | null
    variant_description: string | null
    unit_price: number
    quantity: number
    line_total: number
}

interface CustomerOrderDetails {
    id: number
    order_number: string
    status: CustomerOrderStatus
    payment: CustomerOrderPayment
    fulfilment_status: CustomerOrderStatus
    currency_code: string
    subtotal: number
    discount_total: number
    delivery_total: number
    tax_total: number
    grand_total: number
    placed_at: string | null

    delivery_address: CustomerOrderDeliveryAddress

    items: CustomerOrderItem[]
}

interface CustomerOrderDeliveryAddress {
    recipient_name: string
    phone: string
    address_line_1: string
    address_line_2: string | null
    city: string
    state_or_region: string | null
    postal_code: string | null
    country_code: string
}

type CustomerOrderShowPageProps = {
    order: CustomerOrderDetails

    flash?: {
        success?: string | null
        error?: string | null
    }
}

type OrderShowPageProps = PageProps<CustomerOrderShowPageProps>

export default function Show() {
    const { order } = usePage<OrderShowPageProps>().props

    return (
        <CustomerLayout
            title={`Order ${order.order_number}`}
            description="Review the details of your order."
        >
            <div className="space-y-6">
                <h2 className="text-lg font-bold text-emerald-950">Order placed successfully</h2>
                <div>
                    <Link
                        href={route('customer.orders.index')}
                        className="inline-flex min-h-11 items-center gap-2 rounded-xl border border-neutral-300 bg-white px-4 text-sm font-semibold text-neutral-700 transition hover:bg-neutral-50 focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-neutral-950"
                    >
                        <ArrowLeft className="h-4 w-4" aria-hidden="true" />
                        Back to orders
                    </Link>
                </div>

                <section className="overflow-hidden rounded-2xl border border-neutral-200 bg-white shadow-sm">
                    <div className="flex flex-col gap-5 p-5 sm:p-6">
                        <div className="flex flex-col gap-4 sm:flex-row sm:items-start sm:justify-between">
                            <div className="flex min-w-0 items-start gap-4">
                                <div className="flex h-12 w-12 shrink-0 items-center justify-center rounded-2xl bg-neutral-100 text-neutral-700">
                                    <Package className="h-5 w-5" aria-hidden="true" />
                                </div>

                                <div className="min-w-0">
                                    <p className="text-xs font-semibold uppercase tracking-wide text-neutral-500">
                                        Order number
                                    </p>

                                    <h2 className="mt-1 break-all text-xl font-bold text-neutral-950">
                                        {order.order_number}
                                    </h2>

                                    <div className="mt-2 flex items-center gap-2 text-sm text-neutral-500">
                                        <CalendarDays className="h-4 w-4" aria-hidden="true" />

                                        {formatPlacedAt(order.placed_at)}
                                    </div>
                                </div>
                            </div>

                            <StatusBadge label={order.status.label} value={order.status.value} />
                        </div>
                    </div>
                </section>

                <section
                    aria-labelledby="order-status-heading"
                    className="overflow-hidden rounded-2xl border border-neutral-200 bg-white shadow-sm"
                >
                    <div className="border-b border-neutral-100 px-5 py-4 sm:px-6">
                        <h2 id="order-status-heading" className="font-bold text-neutral-950">
                            Order status
                        </h2>

                        <p className="mt-1 text-sm text-neutral-500">
                            Current order, payment, and delivery progress.
                        </p>
                    </div>

                    <div className="grid gap-4 p-5 sm:grid-cols-3 sm:p-6">
                        <StatusCard
                            icon={<Package className="h-5 w-5" aria-hidden="true" />}
                            label="Order"
                            value={order.status.value}
                            text={order.status.label}
                            type="order"
                        />

                        <StatusCard
                            icon={<CircleDollarSign className="h-5 w-5" aria-hidden="true" />}
                            label={order.payment.method.label}
                            value={order.payment.status.value}
                            text={order.payment.status.label}
                            type="payment"
                        />

                        <StatusCard
                            icon={<Truck className="h-5 w-5" aria-hidden="true" />}
                            label="Fulfilment"
                            value={order.fulfilment_status.value}
                            text={order.fulfilment_status.label}
                            type="fulfilment"
                        />
                    </div>
                </section>

                <section
                    aria-labelledby="ordered-items-heading"
                    className="overflow-hidden rounded-2xl border border-neutral-200 bg-white shadow-sm"
                >
                    <div className="border-b border-neutral-100 px-5 py-4 sm:px-6">
                        <div className="flex items-center gap-3">
                            <div className="flex h-10 w-10 items-center justify-center rounded-xl bg-neutral-100 text-neutral-700">
                                <ShoppingBag className="h-5 w-5" aria-hidden="true" />
                            </div>

                            <div>
                                <h2
                                    id="ordered-items-heading"
                                    className="font-bold text-neutral-950"
                                >
                                    Ordered items
                                </h2>

                                <p className="mt-0.5 text-sm text-neutral-500">
                                    {order.items.length === 1
                                        ? '1 item in this order'
                                        : `${order.items.length} items in this order`}
                                </p>
                            </div>
                        </div>
                    </div>

                    {order.items.length === 0 ? (
                        <div className="px-5 py-10 text-center sm:px-6">
                            <p className="text-sm text-neutral-500">
                                No order items are available.
                            </p>
                        </div>
                    ) : (
                        <ul className="divide-y divide-neutral-100">
                            {order.items.map((item) => (
                                <OrderItemRow key={item.id} item={item} />
                            ))}
                        </ul>
                    )}
                </section>

                <section
                    aria-labelledby="delivery-address-heading"
                    className="overflow-hidden rounded-2xl border border-neutral-200 bg-white shadow-sm"
                >
                    <div className="border-b border-neutral-100 px-5 py-4 sm:px-6">
                        <div className="flex items-center gap-3">
                            <div className="flex h-10 w-10 items-center justify-center rounded-xl bg-neutral-100 text-neutral-700">
                                <MapPin className="h-5 w-5" aria-hidden="true" />
                            </div>

                            <div>
                                <h2
                                    id="delivery-address-heading"
                                    className="font-bold text-neutral-950"
                                >
                                    Delivery address
                                </h2>

                                <p className="mt-0.5 text-sm text-neutral-500">
                                    Address captured when this order was placed.
                                </p>
                            </div>
                        </div>
                    </div>

                    <div className="p-5 sm:p-6">
                        <div className="max-w-xl">
                            <p className="font-semibold text-neutral-950">
                                {order.delivery_address.recipient_name}
                            </p>

                            <address className="mt-2 not-italic text-sm leading-6 text-neutral-600">
                                <span className="block">
                                    {order.delivery_address.address_line_1}
                                </span>

                                {order.delivery_address.address_line_2 && (
                                    <span className="block">
                                        {order.delivery_address.address_line_2}
                                    </span>
                                )}

                                <span className="block">
                                    {order.delivery_address.city}

                                    {order.delivery_address.state_or_region
                                        ? `, ${order.delivery_address.state_or_region}`
                                        : ''}
                                </span>

                                {order.delivery_address.postal_code && (
                                    <span className="block">
                                        {order.delivery_address.postal_code}
                                    </span>
                                )}

                                <span className="block">{order.delivery_address.country_code}</span>
                            </address>

                            <div className="mt-4 flex items-center gap-2 text-sm text-neutral-700">
                                <Phone className="h-4 w-4 text-neutral-500" aria-hidden="true" />

                                <span>{order.delivery_address.phone}</span>
                            </div>
                        </div>
                    </div>
                </section>

                <section
                    aria-labelledby="order-summary-heading"
                    className="overflow-hidden rounded-2xl border border-neutral-200 bg-white shadow-sm"
                >
                    <div className="border-b border-neutral-100 px-5 py-4 sm:px-6">
                        <div className="flex items-center gap-3">
                            <div className="flex h-10 w-10 items-center justify-center rounded-xl bg-neutral-100 text-neutral-700">
                                <ReceiptText className="h-5 w-5" aria-hidden="true" />
                            </div>

                            <div>
                                <h2
                                    id="order-summary-heading"
                                    className="font-bold text-neutral-950"
                                >
                                    Order summary
                                </h2>

                                <p className="mt-0.5 text-sm text-neutral-500">
                                    Server-calculated order totals.
                                </p>
                            </div>
                        </div>
                    </div>

                    <dl className="divide-y divide-neutral-100 px-5 sm:px-6">
                        <MoneyRow label="Subtotal" value={order.subtotal} />

                        {order.discount_total > 0 && (
                            <MoneyRow label="Discount" value={-order.discount_total} />
                        )}

                        <MoneyRow label="Delivery" value={order.delivery_total} />

                        {order.tax_total > 0 && <MoneyRow label="Tax" value={order.tax_total} />}

                        <div className="flex items-center justify-between gap-4 py-5">
                            <dt className="font-bold text-neutral-950">Total</dt>

                            <dd className="text-xl font-bold text-neutral-950">
                                {formatMoney(order.grand_total)}
                            </dd>
                        </div>
                    </dl>
                </section>

                <section className="rounded-2xl border border-dashed border-neutral-300 bg-neutral-50 p-5 sm:p-6">
                    <p className="text-sm leading-6 text-neutral-600">
                        Payment status and fulfilment status are added in the next customer-order
                        step.
                    </p>
                </section>
            </div>
        </CustomerLayout>
    )
}

function OrderItemRow({ item }: { item: CustomerOrderItem }) {
    return (
        <li className="p-5 sm:p-6">
            <div className="flex flex-col gap-4 sm:flex-row sm:items-start sm:justify-between">
                <div className="min-w-0">
                    <h3 className="break-words font-semibold text-neutral-950">
                        {item.product_name}
                    </h3>

                    {item.variant_description && (
                        <p className="mt-1 text-sm text-neutral-600">{item.variant_description}</p>
                    )}

                    {item.sku && (
                        <p className="mt-1 break-all text-xs text-neutral-500">
                            SKU: <span className="font-medium text-neutral-700">{item.sku}</span>
                        </p>
                    )}
                </div>

                <div className="grid shrink-0 grid-cols-2 gap-x-6 gap-y-3 sm:min-w-64 sm:text-right">
                    <div>
                        <p className="text-xs font-medium uppercase tracking-wide text-neutral-500">
                            Unit price
                        </p>

                        <p className="mt-1 text-sm font-semibold text-neutral-950">
                            {formatMoney(item.unit_price)}
                        </p>
                    </div>

                    <div>
                        <p className="text-xs font-medium uppercase tracking-wide text-neutral-500">
                            Quantity
                        </p>

                        <p className="mt-1 text-sm font-semibold text-neutral-950">
                            {item.quantity}
                        </p>
                    </div>

                    <div className="col-span-2 border-t border-neutral-100 pt-3">
                        <p className="text-xs font-medium uppercase tracking-wide text-neutral-500">
                            Line total
                        </p>

                        <p className="mt-1 font-bold text-neutral-950">
                            {formatMoney(item.line_total)}
                        </p>
                    </div>
                </div>
            </div>
        </li>
    )
}

function StatusCard({
    icon,
    label,
    text,
    value,
    type,
}: {
    icon: ReactNode
    label: string
    text: string
    value: string
    type: 'order' | 'payment' | 'fulfilment'
}) {
    return (
        <div className="rounded-xl border border-neutral-200 bg-neutral-50 p-4">
            <div className="flex items-start gap-3">
                <div className="flex h-10 w-10 shrink-0 items-center justify-center rounded-xl bg-white text-neutral-700 shadow-sm">
                    {icon}
                </div>

                <div className="min-w-0">
                    <p className="text-xs font-semibold uppercase tracking-wide text-neutral-500">
                        {label}
                    </p>

                    <div className="mt-2">
                        <StateBadge value={value} label={text} type={type} />
                    </div>
                </div>
            </div>
        </div>
    )
}

function StateBadge({
    label,
    value,
    type,
}: {
    label: string
    value: string
    type: 'order' | 'payment' | 'fulfilment'
}) {
    const className = stateBadgeClass(type, value)

    return (
        <span
            className={`inline-flex w-fit items-center rounded-full border px-3 py-1 text-xs font-semibold ${className}`}
        >
            {label}
        </span>
    )
}

function stateBadgeClass(type: 'order' | 'payment' | 'fulfilment', value: string): string {
    if (type === 'payment') {
        switch (value) {
            case 'paid':
                return 'border-emerald-200 bg-emerald-50 text-emerald-700'

            case 'failed':
                return 'border-red-200 bg-red-50 text-red-700'

            case 'refunded':
            case 'partially_refunded':
                return 'border-violet-200 bg-violet-50 text-violet-700'

            case 'pending':
                return 'border-blue-200 bg-blue-50 text-blue-700'

            case 'unpaid':
            default:
                return 'border-amber-200 bg-amber-50 text-amber-700'
        }
    }

    if (type === 'fulfilment') {
        switch (value) {
            case 'delivered':
                return 'border-emerald-200 bg-emerald-50 text-emerald-700'

            case 'shipped':
                return 'border-blue-200 bg-blue-50 text-blue-700'

            case 'returned':
                return 'border-violet-200 bg-violet-50 text-violet-700'

            case 'processing':
                return 'border-sky-200 bg-sky-50 text-sky-700'

            case 'unfulfilled':
            default:
                return 'border-neutral-200 bg-neutral-100 text-neutral-700'
        }
    }

    switch (value) {
        case 'completed':
            return 'border-emerald-200 bg-emerald-50 text-emerald-700'

        case 'cancelled':
            return 'border-red-200 bg-red-50 text-red-700'

        case 'processing':
            return 'border-blue-200 bg-blue-50 text-blue-700'

        case 'confirmed':
            return 'border-sky-200 bg-sky-50 text-sky-700'

        case 'pending':
        default:
            return 'border-amber-200 bg-amber-50 text-amber-700'
    }
}

function MoneyRow({ label, value }: { label: string; value: number }) {
    return (
        <div className="flex items-center justify-between gap-4 py-4">
            <dt className="text-sm text-neutral-600">{label}</dt>

            <dd className="text-sm font-semibold text-neutral-950">{formatMoney(value)}</dd>
        </div>
    )
}

function StatusBadge({ label, value }: { label: string; value: string }) {
    const className =
        value === 'cancelled'
            ? 'border-red-200 bg-red-50 text-red-700'
            : value === 'completed'
              ? 'border-emerald-200 bg-emerald-50 text-emerald-700'
              : value === 'processing'
                ? 'border-blue-200 bg-blue-50 text-blue-700'
                : 'border-amber-200 bg-amber-50 text-amber-700'

    return (
        <span
            className={`inline-flex w-fit items-center rounded-full border px-3 py-1 text-xs font-semibold ${className}`}
        >
            {label}
        </span>
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
        timeStyle: 'short',
    }).format(date)
}
