import { Link, useForm, usePage } from '@inertiajs/react'
import {
    ArrowLeft,
    CalendarDays,
    Package,
    ReceiptText,
    ShoppingBag,
    MapPin,
    Phone,
    CircleDollarSign,
    FileText,
    CheckCircle2,
    Clock3,
    History,
    Truck,
    AlertTriangle,
    Ban,
    LoaderCircle,
    X,
} from 'lucide-react'

import CustomerLayout from '@/Layouts/Customer/CustomerLayout'
import { formatMoney } from '@/lib/money'
import type { PageProps } from '@/types'
import { useState, type ReactNode } from 'react'

interface CustomerOrderStatus {
    value: string
    label: string
}

interface CustomerOrderPayment {
    method: CustomerOrderStatus
    status: CustomerOrderStatus
}

interface CustomerOrderTimelineStatus {
    value: string
    label: string
}

interface CustomerOrderTimelineEntry {
    id: number
    type: 'order' | 'payment' | 'fulfilment'
    type_label: string
    from: CustomerOrderTimelineStatus | null
    to: CustomerOrderTimelineStatus
    note: string | null
    changed_at: string
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
    timeline: CustomerOrderTimelineEntry[]

    cancellation: {
        allowed: boolean
        url: string
    }

    invoice: {
        number: string
        url: string
    } | null
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

interface CancellationFormErrors {
    reason?: string
    cancellation?: string
}

export default function Show() {
    const { order } = usePage<OrderShowPageProps>().props

    const cancellationForm = useForm({
        reason: '',
    })

    const cancellationErrors = cancellationForm.errors as CancellationFormErrors

    const [cancelOpen, setCancelOpen] = useState(false)

    const submitCancellation = () => {
        cancellationForm.clearErrors()

        cancellationForm.post(order.cancellation.url, {
            preserveScroll: true,

            onSuccess: () => {
                cancellationForm.reset()
                setCancelOpen(false)
            },
        })
    }

    return (
        <CustomerLayout
            title={`Order ${order.order_number}`}
            description="Review the details of your order."
        >
            <div className="space-y-6">
                <div className="flex flex-col gap-4 sm:flex-row sm:items-center sm:justify-between">
                    <div>
                        <p className="text-sm font-semibold text-neutral-500">My order</p>

                        <h1 className="mt-1 text-2xl font-bold tracking-tight text-neutral-950 sm:text-3xl">
                            Track your order
                        </h1>

                        <p className="mt-1 text-sm text-neutral-500">
                            Follow processing, payment, and delivery updates in one place.
                        </p>
                    </div>

                    <Link
                        href={route('customer.orders.index')}
                        className="inline-flex min-h-11 items-center justify-center gap-2 rounded-xl border border-neutral-300 bg-white px-4 text-sm font-semibold text-neutral-700 transition hover:bg-neutral-50 focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-neutral-950"
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
                    aria-labelledby="order-tracking-heading"
                    className="overflow-hidden rounded-2xl border border-neutral-200 bg-white shadow-sm"
                >
                    <div className="border-b border-neutral-100 bg-gradient-to-r from-neutral-50 via-white to-emerald-50/50 px-5 py-5 sm:px-6">
                        <div className="flex items-start gap-3">
                            <div className="flex h-11 w-11 shrink-0 items-center justify-center rounded-xl bg-emerald-50 text-emerald-700 ring-1 ring-emerald-100">
                                <Truck className="h-5 w-5" aria-hidden="true" />
                            </div>

                            <div className="min-w-0">
                                <h2 id="order-tracking-heading" className="font-bold text-neutral-950">
                                    Order tracking
                                </h2>
                                <p className="mt-1 text-sm leading-6 text-neutral-500">
                                    Current progress and complete status history for this order.
                                </p>
                            </div>
                        </div>
                    </div>

                    <div className="space-y-6 p-5 sm:p-6">
                        <OrderProgress order={order} />

                        <div className="grid gap-3 sm:grid-cols-3">
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

                        <div className="overflow-hidden rounded-xl border border-neutral-200">
                            <div className="flex items-center gap-3 border-b border-neutral-100 bg-neutral-50 px-4 py-3">
                                <History className="h-4 w-4 text-neutral-500" aria-hidden="true" />
                                <div>
                                    <h3 className="text-sm font-bold text-neutral-900">Status history</h3>
                                    <p className="text-xs text-neutral-500">
                                        Every recorded order, payment, and fulfilment update.
                                    </p>
                                </div>
                            </div>

                            {order.timeline.length === 0 ? (
                                <div className="px-4 py-6 text-sm text-neutral-500">
                                    No status history is available yet.
                                </div>
                            ) : (
                                <div className="max-h-96 overflow-y-auto">
                                    <ol className="divide-y divide-neutral-100">
                                        {order.timeline.map((entry) => (
                                            <li key={entry.id} className="px-4 py-3.5">
                                                <div className="flex gap-3">
                                                    <div className="mt-0.5 flex h-8 w-8 shrink-0 items-center justify-center rounded-full bg-neutral-100 text-neutral-500">
                                                        <Clock3 className="h-3.5 w-3.5" aria-hidden="true" />
                                                    </div>
                                                    <div className="min-w-0 flex-1">
                                                        <div className="flex flex-wrap items-center gap-x-1.5 gap-y-1 text-sm">
                                                            <span className="font-semibold text-neutral-950">
                                                                {entry.type_label}
                                                            </span>
                                                            <span className="text-neutral-300" aria-hidden="true">·</span>
                                                            {entry.from ? (
                                                                <>
                                                                    <span className="text-neutral-500">{entry.from.label}</span>
                                                                    <span className="text-neutral-400" aria-hidden="true">→</span>
                                                                    <span className="font-semibold text-neutral-800">{entry.to.label}</span>
                                                                </>
                                                            ) : (
                                                                <span className="font-semibold text-neutral-800">{entry.to.label}</span>
                                                            )}
                                                        </div>
                                                        <time
                                                            dateTime={entry.changed_at}
                                                            className="mt-1 block text-[11px] font-medium text-neutral-400"
                                                        >
                                                            {formatPlacedAt(entry.changed_at)}
                                                        </time>
                                                        {entry.note && (
                                                            <p className="mt-1.5 whitespace-pre-wrap break-words text-xs leading-5 text-neutral-500">
                                                                {entry.note}
                                                            </p>
                                                        )}
                                                    </div>
                                                </div>
                                            </li>
                                        ))}
                                    </ol>
                                </div>
                            )}
                        </div>
                    </div>
                </section>

                <section
                    aria-labelledby="customer-order-cancellation-heading"
                    className="overflow-hidden rounded-2xl border border-neutral-200 bg-white shadow-sm"
                >
                    <div className="border-b border-neutral-100 px-5 py-4 sm:px-6">
                        <div className="flex items-start gap-3">
                            <div
                                className={`flex h-10 w-10 shrink-0 items-center justify-center rounded-xl ${
                                    order.cancellation.allowed
                                        ? 'bg-amber-50 text-amber-700'
                                        : 'bg-neutral-100 text-neutral-500'
                                }`}
                            >
                                <Ban className="h-5 w-5" aria-hidden="true" />
                            </div>

                            <div className="min-w-0 flex-1">
                                <div className="flex flex-wrap items-center gap-2">
                                    <h2
                                        id="customer-order-cancellation-heading"
                                        className="font-bold text-neutral-950"
                                    >
                                        Order cancellation
                                    </h2>

                                    <span
                                        className={`inline-flex rounded-full border px-2.5 py-0.5 text-xs font-semibold ${
                                            order.cancellation.allowed
                                                ? 'border-amber-200 bg-amber-50 text-amber-700'
                                                : 'border-neutral-200 bg-neutral-100 text-neutral-600'
                                        }`}
                                    >
                                        {order.cancellation.allowed ? 'Available' : 'Unavailable'}
                                    </span>
                                </div>

                                <p className="mt-1 text-sm leading-6 text-neutral-500">
                                    {order.cancellation.allowed
                                        ? 'You may cancel this order before processing begins.'
                                        : 'This order has progressed beyond the direct cancellation window.'}
                                </p>
                            </div>
                        </div>
                    </div>

                    <div className="p-5 sm:p-6">
                        {order.cancellation.allowed ? (
                            !cancelOpen ? (
                                <div className="flex flex-col gap-5 lg:flex-row lg:items-center lg:justify-between">
                                    <div className="max-w-2xl">
                                        <p className="font-semibold text-neutral-950">
                                            Need to cancel this order?
                                        </p>

                                        <p className="mt-1 text-sm leading-6 text-neutral-600">
                                            Tell us why you need to cancel. Eligible inventory will
                                            be restored automatically.
                                        </p>
                                    </div>

                                    <button
                                        type="button"
                                        onClick={() => {
                                            cancellationForm.clearErrors()
                                            setCancelOpen(true)
                                        }}
                                        className="inline-flex min-h-11 shrink-0 items-center justify-center gap-2 rounded-xl border border-red-200 bg-white px-4 py-2.5 text-sm font-semibold text-red-700 transition hover:border-red-300 hover:bg-red-50 focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-red-500 focus-visible:ring-offset-2"
                                    >
                                        <Ban className="h-4 w-4" aria-hidden="true" />
                                        Cancel this order
                                    </button>
                                </div>
                            ) : (
                                <div className="overflow-hidden rounded-2xl border border-red-200 bg-red-50/40">
                                    <div className="flex items-start gap-3 border-b border-red-100 p-4 sm:p-5">
                                        <div className="flex h-10 w-10 shrink-0 items-center justify-center rounded-xl bg-red-100 text-red-700">
                                            <AlertTriangle className="h-5 w-5" aria-hidden="true" />
                                        </div>

                                        <div className="min-w-0 flex-1">
                                            <h3 className="font-bold text-red-950">
                                                Confirm cancellation
                                            </h3>

                                            <p className="mt-1 text-sm leading-6 text-red-700">
                                                Once cancelled, this order cannot return to normal
                                                processing.
                                            </p>
                                        </div>

                                        <button
                                            type="button"
                                            disabled={cancellationForm.processing}
                                            aria-label="Close cancellation form"
                                            onClick={() => {
                                                cancellationForm.clearErrors()
                                                setCancelOpen(false)
                                            }}
                                            className="rounded-lg p-2 text-red-500 transition hover:bg-red-100 hover:text-red-700 disabled:opacity-50"
                                        >
                                            <X className="h-4 w-4" aria-hidden="true" />
                                        </button>
                                    </div>

                                    <div className="p-4 sm:p-5">
                                        {cancellationErrors.cancellation && (
                                            <div
                                                role="alert"
                                                className="mb-4 flex items-start gap-3 rounded-xl border border-red-200 bg-white p-4"
                                            >
                                                <AlertTriangle
                                                    className="mt-0.5 h-4 w-4 shrink-0 text-red-600"
                                                    aria-hidden="true"
                                                />

                                                <p className="text-sm font-medium leading-6 text-red-700">
                                                    {cancellationErrors.cancellation}
                                                </p>
                                            </div>
                                        )}

                                        <label
                                            htmlFor="customer-cancellation-reason"
                                            className="text-sm font-semibold text-neutral-900"
                                        >
                                            Why are you cancelling?
                                        </label>

                                        <p className="mt-1 text-xs leading-5 text-neutral-500">
                                            Your reason will be saved with the order history.
                                        </p>

                                        <textarea
                                            id="customer-cancellation-reason"
                                            rows={4}
                                            maxLength={1000}
                                            required
                                            value={cancellationForm.data.reason}
                                            aria-invalid={
                                                cancellationErrors.reason ? true : undefined
                                            }
                                            aria-describedby={
                                                cancellationErrors.reason
                                                    ? 'customer-cancellation-reason-error'
                                                    : undefined
                                            }
                                            onChange={(event) => {
                                                cancellationForm.setData(
                                                    'reason',
                                                    event.target.value,
                                                )

                                                if (
                                                    cancellationErrors.reason ||
                                                    cancellationErrors.cancellation
                                                ) {
                                                    cancellationForm.clearErrors()
                                                }
                                            }}
                                            placeholder="For example: ordered by mistake, delivery no longer needed..."
                                            className="mt-3 block w-full resize-y rounded-xl border border-neutral-300 bg-white px-4 py-3 text-sm leading-6 text-neutral-950 outline-none transition placeholder:text-neutral-400 focus:border-red-400 focus:ring-4 focus:ring-red-100"
                                        />

                                        <div className="mt-2 flex items-start justify-between gap-4">
                                            <div>
                                                {cancellationErrors.reason && (
                                                    <p
                                                        id="customer-cancellation-reason-error"
                                                        className="text-sm font-medium text-red-600"
                                                    >
                                                        {cancellationErrors.reason}
                                                    </p>
                                                )}
                                            </div>

                                            <span className="shrink-0 text-xs text-neutral-400">
                                                {cancellationForm.data.reason.length}
                                                /1000
                                            </span>
                                        </div>

                                        <div className="mt-5 flex flex-col-reverse gap-3 sm:flex-row sm:justify-end">
                                            <button
                                                type="button"
                                                disabled={cancellationForm.processing}
                                                onClick={() => {
                                                    cancellationForm.clearErrors()
                                                    setCancelOpen(false)
                                                }}
                                                className="inline-flex min-h-11 items-center justify-center rounded-xl border border-neutral-300 bg-white px-4 py-2.5 text-sm font-semibold text-neutral-700 transition hover:bg-neutral-50 disabled:opacity-50"
                                            >
                                                Keep my order
                                            </button>

                                            <button
                                                type="button"
                                                disabled={
                                                    cancellationForm.processing ||
                                                    cancellationForm.data.reason.trim().length < 3
                                                }
                                                onClick={submitCancellation}
                                                className="inline-flex min-h-11 items-center justify-center gap-2 rounded-xl bg-red-600 px-5 py-2.5 text-sm font-semibold text-white transition hover:bg-red-700 focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-red-600 focus-visible:ring-offset-2 disabled:cursor-not-allowed disabled:opacity-50"
                                            >
                                                {cancellationForm.processing ? (
                                                    <>
                                                        <LoaderCircle
                                                            className="h-4 w-4 animate-spin"
                                                            aria-hidden="true"
                                                        />
                                                        Cancelling…
                                                    </>
                                                ) : (
                                                    <>
                                                        <Ban
                                                            className="h-4 w-4"
                                                            aria-hidden="true"
                                                        />
                                                        Confirm cancellation
                                                    </>
                                                )}
                                            </button>
                                        </div>
                                    </div>
                                </div>
                            )
                        ) : (
                            <div className="flex items-start gap-3 rounded-xl border border-neutral-200 bg-neutral-50 p-4">
                                <Package
                                    className="mt-0.5 h-5 w-5 shrink-0 text-neutral-500"
                                    aria-hidden="true"
                                />

                                <div>
                                    <p className="font-semibold text-neutral-900">
                                        Direct cancellation is unavailable
                                    </p>

                                    <p className="mt-1 text-sm leading-6 text-red-500">
                                        Once an order has been confirmed or processing has started,
                                        it cannot be cancelled directly from your account. If you
                                        want to cancel this order, please contact our support team.
                                    </p>
                                </div>
                            </div>
                        )}
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

                <section
                    aria-labelledby="order-documents-heading"
                    className="overflow-hidden rounded-2xl border border-neutral-200 bg-white shadow-sm"
                >
                    <div className="border-b border-neutral-100 px-5 py-4 sm:px-6">
                        <div className="flex items-center gap-3">
                            <div className="flex h-10 w-10 items-center justify-center rounded-xl bg-neutral-100 text-neutral-700">
                                <FileText className="h-5 w-5" aria-hidden="true" />
                            </div>

                            <div>
                                <h2
                                    id="order-documents-heading"
                                    className="font-bold text-neutral-950"
                                >
                                    Order documents
                                </h2>

                                <p className="mt-0.5 text-sm text-neutral-500">
                                    Documents generated for this order.
                                </p>
                            </div>
                        </div>
                    </div>

                    <div className="p-5 sm:p-6">
                        {order.invoice ? (
                            <Link
                                href={order.invoice.url}
                                className="group flex min-h-14 items-center justify-between gap-4 rounded-xl border border-neutral-200 bg-neutral-50 px-4 py-3 transition hover:border-neutral-300 hover:bg-white focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-neutral-950 focus-visible:ring-offset-2"
                            >
                                <div className="flex min-w-0 items-center gap-3">
                                    <div className="flex h-11 w-11 shrink-0 items-center justify-center rounded-xl bg-white text-neutral-700 shadow-sm ring-1 ring-neutral-200">
                                        <ReceiptText className="h-5 w-5" aria-hidden="true" />
                                    </div>

                                    <div className="min-w-0">
                                        <p className="font-semibold text-neutral-950">Invoice</p>

                                        <p className="mt-0.5 truncate text-xs text-neutral-500">
                                            {order.invoice.number}
                                        </p>
                                    </div>
                                </div>

                                <span className="shrink-0 text-sm font-semibold text-neutral-700 transition group-hover:text-neutral-950">
                                    View
                                </span>
                            </Link>
                        ) : (
                            <div className="flex items-start gap-3 rounded-xl border border-dashed border-neutral-300 bg-neutral-50 px-4 py-4">
                                <div className="flex h-11 w-11 shrink-0 items-center justify-center rounded-xl bg-white text-neutral-400 shadow-sm ring-1 ring-neutral-200">
                                    <ReceiptText className="h-5 w-5" aria-hidden="true" />
                                </div>

                                <div>
                                    <p className="font-semibold text-neutral-950">
                                        Invoice not available yet
                                    </p>

                                    <p className="mt-1 text-sm leading-6 text-neutral-500">
                                        Your invoice will be generated automatically when order
                                        processing begins.
                                    </p>
                                </div>
                            </div>
                        )}

                        <p className="mt-3 text-xs leading-5 text-neutral-400">
                            Delivery slips, notes, and other order documents can be added here
                            later.
                        </p>
                    </div>
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

function OrderProgress({ order }: { order: CustomerOrderDetails }) {
    const cancelled = order.status.value === 'cancelled'

    const steps = [
        { key: 'placed', label: 'Placed', complete: true },
        {
            key: 'confirmed',
            label: 'Confirmed',
            complete: ['confirmed', 'processing', 'completed'].includes(order.status.value),
        },
        {
            key: 'processing',
            label: 'Processing',
            complete: order.status.value === 'processing' || order.status.value === 'completed',
        },
        {
            key: 'shipped',
            label: 'Shipped',
            complete: ['shipped', 'delivered', 'returned'].includes(
                order.fulfilment_status.value,
            ),
        },
        {
            key: 'delivered',
            label: 'Delivered',
            complete:
                order.fulfilment_status.value === 'delivered' ||
                order.status.value === 'completed',
        },
    ]

    return (
        <div>
            <div className="mb-3 flex items-center justify-between gap-3">
                <div>
                    <p className="text-sm font-bold text-neutral-950">Delivery progress</p>
                    <p className="mt-0.5 text-xs text-neutral-500">
                        {cancelled
                            ? 'This order was cancelled before completion.'
                            : `Currently ${order.fulfilment_status.label.toLowerCase()}.`}
                    </p>
                </div>

                {cancelled && (
                    <span className="inline-flex rounded-full border border-red-200 bg-red-50 px-2.5 py-1 text-xs font-semibold text-red-700">
                        Cancelled
                    </span>
                )}
            </div>

            <div className="overflow-x-auto pb-1">
                <ol className="flex min-w-[620px] items-start">
                    {steps.map((step, index) => {
                        const complete = !cancelled && step.complete

                        return (
                            <li
                                key={step.key}
                                className="relative flex min-w-0 flex-1 flex-col items-center text-center"
                            >
                                {index > 0 && (
                                    <div
                                        className={[
                                            'absolute right-1/2 top-4 h-0.5 w-full',
                                            complete ? 'bg-emerald-500' : 'bg-neutral-200',
                                        ].join(' ')}
                                        aria-hidden="true"
                                    />
                                )}

                                <div
                                    className={[
                                        'relative z-10 flex h-8 w-8 items-center justify-center rounded-full border-2',
                                        complete
                                            ? 'border-emerald-500 bg-emerald-500 text-white'
                                            : 'border-neutral-300 bg-white text-neutral-400',
                                    ].join(' ')}
                                >
                                    {complete ? (
                                        <CheckCircle2 className="h-4 w-4" aria-hidden="true" />
                                    ) : (
                                        <span className="h-2 w-2 rounded-full bg-current" />
                                    )}
                                </div>

                                <span
                                    className={[
                                        'mt-2 text-xs font-semibold',
                                        complete ? 'text-emerald-700' : 'text-neutral-500',
                                    ].join(' ')}
                                >
                                    {step.label}
                                </span>
                            </li>
                        )
                    })}
                </ol>
            </div>
        </div>
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
