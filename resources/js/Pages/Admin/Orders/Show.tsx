import AdminLayout from '@/Layouts/Admin/AdminLayout'
import { formatMoney } from '@/lib/money'
import { Head, Link } from '@inertiajs/react'
import {
    ArrowLeft,
    CircleDollarSign,
    MapPin,
    Package,
    Phone,
    ReceiptText,
    ShoppingBag,
    Truck,
    UserRound,
} from 'lucide-react'
import type { ReactNode } from 'react'

interface StatusValue {
    value: string
    label: string
}

interface CustomerSummary {
    name: string | null
    email: string | null
}

interface DeliveryAddress {
    recipient_name: string
    phone: string
    address_line_1: string
    address_line_2: string | null
    city: string
    state_or_region: string | null
    postal_code: string | null
    country_code: string
}

interface OrderItem {
    id: number
    product_name: string
    sku: string | null
    variant_description: string | null
    unit_price: number
    quantity: number
    line_total: number
}

interface AdminOrderDetails {
    id: number
    order_number: string

    customer: CustomerSummary

    status: StatusValue

    payment: {
        method: StatusValue
        status: StatusValue
    }

    fulfilment_status: StatusValue

    currency_code: string
    subtotal: number
    discount_total: number
    delivery_total: number
    tax_total: number
    grand_total: number
    placed_at: string | null

    delivery_address: DeliveryAddress

    items: OrderItem[]
}

interface Props {
    order: AdminOrderDetails
}

export default function Show({ order }: Props) {
    return (
        <AdminLayout title="Order Details">
            <Head title={`Order ${order.order_number}`} />

            <div className="mx-auto max-w-7xl space-y-6">
                <div>
                    <Link
                        href={route('admin.orders.index')}
                        className="inline-flex items-center gap-2 text-sm font-medium text-slate-600 transition hover:text-slate-900"
                    >
                        <ArrowLeft className="h-4 w-4" aria-hidden="true" />
                        Back to orders
                    </Link>

                    <div className="mt-4 flex flex-col gap-3 sm:flex-row sm:items-start sm:justify-between">
                        <div>
                            <p className="text-sm font-medium text-slate-500">Customer order</p>

                            <h1 className="mt-1 break-all text-2xl font-bold tracking-tight text-slate-950 sm:text-3xl">
                                {order.order_number}
                            </h1>

                            <p className="mt-2 text-sm text-slate-500">
                                Placed {formatDate(order.placed_at)}
                            </p>
                        </div>

                        <StatusBadge
                            value={order.status.value}
                            label={order.status.label}
                            type="order"
                        />
                    </div>
                </div>

                <section
                    aria-labelledby="order-state-heading"
                    className="overflow-hidden rounded-xl border border-slate-200 bg-white shadow-sm"
                >
                    <div className="border-b border-slate-200 px-5 py-4 sm:px-6">
                        <h2 id="order-state-heading" className="font-semibold text-slate-900">
                            Order state
                        </h2>

                        <p className="mt-1 text-sm text-slate-500">
                            Order, payment, and fulfilment states are kept separate.
                        </p>
                    </div>

                    <div className="grid gap-4 p-5 sm:grid-cols-2 sm:p-6 xl:grid-cols-4">
                        <StateCard
                            icon={<Package className="h-5 w-5" aria-hidden="true" />}
                            title="Order status"
                        >
                            <StatusBadge
                                value={order.status.value}
                                label={order.status.label}
                                type="order"
                            />
                        </StateCard>

                        <StateCard
                            icon={<CircleDollarSign className="h-5 w-5" aria-hidden="true" />}
                            title="Payment method"
                        >
                            <p className="font-semibold text-slate-900">
                                {order.payment.method.label}
                            </p>
                        </StateCard>

                        <StateCard
                            icon={<ReceiptText className="h-5 w-5" aria-hidden="true" />}
                            title="Payment status"
                        >
                            <StatusBadge
                                value={order.payment.status.value}
                                label={order.payment.status.label}
                                type="payment"
                            />
                        </StateCard>

                        <StateCard
                            icon={<Truck className="h-5 w-5" aria-hidden="true" />}
                            title="Fulfilment"
                        >
                            <StatusBadge
                                value={order.fulfilment_status.value}
                                label={order.fulfilment_status.label}
                                type="fulfilment"
                            />
                        </StateCard>
                    </div>
                </section>

                <div className="grid gap-6 xl:grid-cols-[minmax(0,1fr)_360px]">
                    <div className="space-y-6">
                        <section
                            aria-labelledby="ordered-items-heading"
                            className="overflow-hidden rounded-xl border border-slate-200 bg-white shadow-sm"
                        >
                            <div className="border-b border-slate-200 px-5 py-4 sm:px-6">
                                <div className="flex items-center gap-3">
                                    <div className="flex h-10 w-10 items-center justify-center rounded-lg bg-slate-100 text-slate-700">
                                        <ShoppingBag className="h-5 w-5" aria-hidden="true" />
                                    </div>

                                    <div>
                                        <h2
                                            id="ordered-items-heading"
                                            className="font-semibold text-slate-900"
                                        >
                                            Ordered items
                                        </h2>

                                        <p className="mt-1 text-sm text-slate-500">
                                            {order.items.length}{' '}
                                            {order.items.length === 1 ? 'line item' : 'line items'}
                                        </p>
                                    </div>
                                </div>
                            </div>

                            {order.items.length === 0 ? (
                                <div className="px-5 py-8 text-sm text-slate-500 sm:px-6">
                                    No order items are available.
                                </div>
                            ) : (
                                <div className="divide-y divide-slate-100">
                                    {order.items.map((item) => (
                                        <div key={item.id} className="p-5 sm:p-6">
                                            <div className="flex flex-col gap-4 sm:flex-row sm:items-start sm:justify-between">
                                                <div className="min-w-0">
                                                    <h3 className="font-semibold text-slate-900">
                                                        {item.product_name}
                                                    </h3>

                                                    {item.variant_description && (
                                                        <p className="mt-1 text-sm text-slate-500">
                                                            {item.variant_description}
                                                        </p>
                                                    )}

                                                    {item.sku && (
                                                        <p className="mt-1 text-xs text-slate-500">
                                                            SKU: {item.sku}
                                                        </p>
                                                    )}

                                                    <p className="mt-3 text-sm text-slate-600">
                                                        {formatMoney(
                                                            item.unit_price,
                                                            order.currency_code,
                                                        )}{' '}
                                                        × {item.quantity}
                                                    </p>
                                                </div>

                                                <p className="shrink-0 font-semibold text-slate-900">
                                                    {formatMoney(
                                                        item.line_total,
                                                        order.currency_code,
                                                    )}
                                                </p>
                                            </div>
                                        </div>
                                    ))}
                                </div>
                            )}
                        </section>

                        <section
                            aria-labelledby="delivery-heading"
                            className="overflow-hidden rounded-xl border border-slate-200 bg-white shadow-sm"
                        >
                            <div className="border-b border-slate-200 px-5 py-4 sm:px-6">
                                <div className="flex items-center gap-3">
                                    <div className="flex h-10 w-10 items-center justify-center rounded-lg bg-slate-100 text-slate-700">
                                        <MapPin className="h-5 w-5" aria-hidden="true" />
                                    </div>

                                    <div>
                                        <h2
                                            id="delivery-heading"
                                            className="font-semibold text-slate-900"
                                        >
                                            Delivery address
                                        </h2>

                                        <p className="mt-1 text-sm text-slate-500">
                                            Immutable snapshot captured at checkout.
                                        </p>
                                    </div>
                                </div>
                            </div>

                            <div className="p-5 sm:p-6">
                                <p className="font-semibold text-slate-900">
                                    {order.delivery_address.recipient_name}
                                </p>

                                <address className="mt-2 not-italic text-sm leading-6 text-slate-600">
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

                                    <span className="block">
                                        {order.delivery_address.country_code}
                                    </span>
                                </address>

                                <div className="mt-4 flex items-center gap-2 text-sm text-slate-700">
                                    <Phone className="h-4 w-4 text-slate-500" aria-hidden="true" />

                                    {order.delivery_address.phone}
                                </div>
                            </div>
                        </section>
                    </div>

                    <div className="space-y-6">
                        <section
                            aria-labelledby="customer-heading"
                            className="overflow-hidden rounded-xl border border-slate-200 bg-white shadow-sm"
                        >
                            <div className="border-b border-slate-200 px-5 py-4">
                                <div className="flex items-center gap-3">
                                    <UserRound
                                        className="h-5 w-5 text-slate-500"
                                        aria-hidden="true"
                                    />

                                    <h2
                                        id="customer-heading"
                                        className="font-semibold text-slate-900"
                                    >
                                        Customer
                                    </h2>
                                </div>
                            </div>

                            <div className="p-5">
                                <p className="font-semibold text-slate-900">
                                    {order.customer.name ?? 'Unknown customer'}
                                </p>

                                {order.customer.email && (
                                    <p className="mt-1 break-all text-sm text-slate-600">
                                        {order.customer.email}
                                    </p>
                                )}
                            </div>
                        </section>

                        <section
                            aria-labelledby="summary-heading"
                            className="overflow-hidden rounded-xl border border-slate-200 bg-white shadow-sm"
                        >
                            <div className="border-b border-slate-200 px-5 py-4">
                                <h2 id="summary-heading" className="font-semibold text-slate-900">
                                    Order summary
                                </h2>
                            </div>

                            <div className="space-y-3 p-5 text-sm">
                                <SummaryRow
                                    label="Subtotal"
                                    value={formatMoney(order.subtotal, order.currency_code)}
                                />

                                <SummaryRow
                                    label="Discount"
                                    value={formatMoney(order.discount_total, order.currency_code)}
                                />

                                <SummaryRow
                                    label="Delivery"
                                    value={formatMoney(order.delivery_total, order.currency_code)}
                                />

                                <SummaryRow
                                    label="Tax"
                                    value={formatMoney(order.tax_total, order.currency_code)}
                                />

                                <div className="border-t border-slate-200 pt-4">
                                    <SummaryRow
                                        label="Grand total"
                                        value={formatMoney(order.grand_total, order.currency_code)}
                                        strong
                                    />
                                </div>
                            </div>
                        </section>
                    </div>
                </div>
            </div>
        </AdminLayout>
    )
}

function StateCard({
    icon,
    title,
    children,
}: {
    icon: ReactNode
    title: string
    children: ReactNode
}) {
    return (
        <div className="rounded-xl border border-slate-200 bg-slate-50 p-4">
            <div className="flex items-start gap-3">
                <div className="flex h-10 w-10 shrink-0 items-center justify-center rounded-lg bg-white text-slate-700 shadow-sm">
                    {icon}
                </div>

                <div className="min-w-0">
                    <p className="text-xs font-semibold uppercase tracking-wide text-slate-500">
                        {title}
                    </p>

                    <div className="mt-2">{children}</div>
                </div>
            </div>
        </div>
    )
}

function SummaryRow({
    label,
    value,
    strong = false,
}: {
    label: string
    value: string
    strong?: boolean
}) {
    return (
        <div className="flex items-center justify-between gap-4">
            <span className={strong ? 'font-semibold text-slate-900' : 'text-slate-600'}>
                {label}
            </span>

            <span
                className={
                    strong ? 'text-base font-bold text-slate-950' : 'font-medium text-slate-900'
                }
            >
                {value}
            </span>
        </div>
    )
}

function StatusBadge({
    label,
    value,
    type,
}: {
    label: string
    value: string
    type: 'order' | 'payment' | 'fulfilment'
}) {
    return (
        <span
            className={`inline-flex rounded-full border px-2.5 py-1 text-xs font-semibold ${statusClass(
                type,
                value,
            )}`}
        >
            {label}
        </span>
    )
}

function statusClass(type: 'order' | 'payment' | 'fulfilment', value: string): string {
    if (type === 'payment') {
        switch (value) {
            case 'paid':
                return 'border-emerald-200 bg-emerald-50 text-emerald-700'

            case 'failed':
                return 'border-red-200 bg-red-50 text-red-700'

            case 'pending':
                return 'border-blue-200 bg-blue-50 text-blue-700'

            case 'refunded':
            case 'partially_refunded':
                return 'border-violet-200 bg-violet-50 text-violet-700'

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

            case 'processing':
                return 'border-sky-200 bg-sky-50 text-sky-700'

            case 'returned':
                return 'border-violet-200 bg-violet-50 text-violet-700'

            case 'unfulfilled':
            default:
                return 'border-slate-200 bg-slate-100 text-slate-700'
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

function formatDate(value: string | null): string {
    if (!value) {
        return '—'
    }

    return new Intl.DateTimeFormat(undefined, {
        dateStyle: 'medium',
        timeStyle: 'short',
    }).format(new Date(value))
}
