import AdminLayout from '@/Layouts/Admin/AdminLayout'
import { formatMoney } from '@/lib/money'
import { Head, Link, useForm } from '@inertiajs/react'
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
    CheckCircle2,
    Clock3,
    FileText,
    History,
    LoaderCircle,
    Send,
    AlertTriangle,
    Ban,
    X,
} from 'lucide-react'
import { useState, type ReactNode } from 'react'

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

interface TimelineStatus {
    value: string
    label: string
}

interface TimelineEntry {
    id: number
    type: 'order' | 'payment' | 'fulfilment'
    type_label: string
    from: TimelineStatus | null
    to: TimelineStatus
    note: string | null
    changed_at: string
}

interface AvailableAction {
    key: string
    label: string
    description: string
    url: string
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
    timeline: TimelineEntry[]
    available_actions: AvailableAction[]
    cancellation: {
        allowed: boolean
        url: string
    }

    invoice: {
        number: string
        url: string
    } | null
}

interface CancellationFormErrors {
    reason?: string
    cancellation?: string
}

interface Props {
    order: AdminOrderDetails
}

export default function Show({ order }: Props) {
    const { data, setData, post, processing, errors, clearErrors } = useForm({
        note: '',
    })

    const processAction = (action: AvailableAction) => {
        clearErrors()

        post(action.url, {
            preserveScroll: true,
            onSuccess: () => {
                setData('note', '')
            },
        })
    }

    const cancellationForm = useForm({
        reason: '',
    })

    const cancellationErrors = cancellationForm.errors as CancellationFormErrors

    const [cancelOpen, setCancelOpen] = useState(false)

    const submitCancellation = () => {
        cancellationForm.post(order.cancellation.url, {
            preserveScroll: true,

            onSuccess: () => {
                cancellationForm.reset()
                setCancelOpen(false)
            },
        })
    }
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

                        <section
                            aria-labelledby="order-processing-heading"
                            className="overflow-hidden rounded-xl border border-slate-200 bg-white shadow-sm"
                        >
                            <div className="border-b border-slate-200 px-5 py-4 sm:px-6">
                                <div className="flex items-start gap-3">
                                    <div className="flex h-10 w-10 shrink-0 items-center justify-center rounded-lg bg-slate-100 text-slate-700">
                                        <CheckCircle2 className="h-5 w-5" aria-hidden="true" />
                                    </div>

                                    <div>
                                        <h2
                                            id="order-processing-heading"
                                            className="font-semibold text-slate-900"
                                        >
                                            Order processing
                                        </h2>

                                        <p className="mt-1 text-sm text-slate-500">
                                            Only valid next-state actions are available.
                                        </p>
                                    </div>
                                </div>
                            </div>

                            <div className="p-5 sm:p-6">
                                {order.available_actions.length === 0 ? (
                                    <div className="rounded-xl border border-slate-200 bg-slate-50 p-4">
                                        <div className="flex items-start gap-3">
                                            <CheckCircle2
                                                className="mt-0.5 h-5 w-5 shrink-0 text-emerald-600"
                                                aria-hidden="true"
                                            />

                                            <div>
                                                <p className="font-semibold text-slate-900">
                                                    No processing action available
                                                </p>

                                                <p className="mt-1 text-sm leading-6 text-slate-500">
                                                    This order has no valid next processing
                                                    transition from its current state.
                                                </p>
                                            </div>
                                        </div>
                                    </div>
                                ) : (
                                    <div className="space-y-5">
                                        {order.available_actions.map((action) => (
                                            <div
                                                key={action.key}
                                                className="rounded-xl border border-slate-200 bg-slate-50 p-4 sm:p-5"
                                            >
                                                <div className="flex flex-col gap-4 lg:flex-row lg:items-end lg:justify-between">
                                                    <div className="min-w-0">
                                                        <p className="font-semibold text-slate-900">
                                                            {action.label}
                                                        </p>

                                                        <p className="mt-1 max-w-2xl text-sm leading-6 text-slate-500">
                                                            {action.description}
                                                        </p>
                                                    </div>

                                                    <button
                                                        type="button"
                                                        disabled={processing}
                                                        onClick={() => processAction(action)}
                                                        className="inline-flex min-h-11 shrink-0 items-center justify-center gap-2 rounded-lg bg-slate-950 px-4 py-2.5 text-sm font-semibold text-white transition hover:bg-slate-800 focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-slate-950 focus-visible:ring-offset-2 disabled:cursor-not-allowed disabled:opacity-50"
                                                    >
                                                        {processing ? (
                                                            <LoaderCircle
                                                                className="h-4 w-4 animate-spin"
                                                                aria-hidden="true"
                                                            />
                                                        ) : (
                                                            <Send
                                                                className="h-4 w-4"
                                                                aria-hidden="true"
                                                            />
                                                        )}

                                                        {action.label}
                                                    </button>
                                                </div>
                                            </div>
                                        ))}

                                        <div>
                                            <label
                                                htmlFor="order-transition-note"
                                                className="text-sm font-semibold text-slate-800"
                                            >
                                                Internal processing note
                                            </label>

                                            <textarea
                                                id="order-transition-note"
                                                value={data.note}
                                                onChange={(event) =>
                                                    setData('note', event.target.value)
                                                }
                                                rows={3}
                                                maxLength={1000}
                                                placeholder="Optional note about this status change..."
                                                className="mt-2 block w-full rounded-lg border border-slate-300 bg-white px-3 py-2 text-sm text-slate-900 shadow-sm outline-none transition placeholder:text-slate-400 focus:border-slate-500 focus:ring-2 focus:ring-slate-200"
                                            />

                                            {errors.note && (
                                                <p className="mt-2 text-sm font-medium text-red-600">
                                                    {errors.note}
                                                </p>
                                            )}
                                        </div>
                                    </div>
                                )}
                            </div>
                        </section>

                        <section
                            aria-labelledby="order-cancellation-heading"
                            className="overflow-hidden rounded-2xl border border-slate-200 bg-white shadow-sm"
                        >
                            <div className="flex flex-col gap-4 border-b border-slate-200 bg-gradient-to-r from-slate-50 via-white to-red-50/60 px-5 py-5 sm:flex-row sm:items-start sm:justify-between sm:px-6">
                                <div className="flex min-w-0 items-start gap-3">
                                    <div className="flex h-11 w-11 shrink-0 items-center justify-center rounded-xl border border-red-100 bg-red-50 text-red-600 shadow-sm">
                                        <Ban className="h-5 w-5" aria-hidden="true" />
                                    </div>

                                    <div className="min-w-0">
                                        <div className="flex flex-wrap items-center gap-2">
                                            <h2
                                                id="order-cancellation-heading"
                                                className="font-semibold text-slate-950"
                                            >
                                                Order cancellation
                                            </h2>

                                            <span
                                                className={[
                                                    'inline-flex rounded-full border px-2.5 py-1 text-xs font-semibold',
                                                    order.cancellation.allowed
                                                        ? 'border-amber-200 bg-amber-50 text-amber-700'
                                                        : 'border-slate-200 bg-slate-100 text-slate-600',
                                                ].join(' ')}
                                            >
                                                {order.cancellation.allowed
                                                    ? 'Available'
                                                    : 'Locked'}
                                            </span>
                                        </div>

                                        <p className="mt-1 max-w-2xl text-sm leading-6 text-slate-500">
                                            Cancel only when the order should not continue. Eligible
                                            inventory is restored transactionally and the reason is
                                            recorded in status history.
                                        </p>
                                    </div>
                                </div>

                                {order.cancellation.allowed && !cancelOpen && (
                                    <button
                                        type="button"
                                        onClick={() => {
                                            cancellationForm.clearErrors()
                                            setCancelOpen(true)
                                        }}
                                        className="inline-flex min-h-11 shrink-0 items-center justify-center gap-2 rounded-xl border border-red-200 bg-white px-4 py-2.5 text-sm font-semibold text-red-700 shadow-sm transition hover:border-red-300 hover:bg-red-50 focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-red-500 focus-visible:ring-offset-2"
                                    >
                                        <Ban className="h-4 w-4" aria-hidden="true" />
                                        Cancel order
                                    </button>
                                )}
                            </div>

                            <div className="p-5 sm:p-6">
                                {order.cancellation.allowed ? (
                                    cancelOpen ? (
                                        <div className="overflow-hidden rounded-2xl border border-red-200 bg-red-50/40">
                                            <div className="flex items-start gap-3 border-b border-red-100 bg-red-50 px-4 py-4 sm:px-5">
                                                <div className="flex h-9 w-9 shrink-0 items-center justify-center rounded-lg bg-white text-red-600 shadow-sm ring-1 ring-red-100">
                                                    <AlertTriangle
                                                        className="h-4 w-4"
                                                        aria-hidden="true"
                                                    />
                                                </div>

                                                <div className="min-w-0 flex-1">
                                                    <p className="font-semibold text-red-950">
                                                        Confirm cancellation
                                                    </p>

                                                    <p className="mt-1 text-sm leading-6 text-red-700">
                                                        This ends the normal processing path. Enter
                                                        a clear reason so the cancellation remains
                                                        auditable.
                                                    </p>
                                                </div>

                                                <button
                                                    type="button"
                                                    aria-label="Close cancellation form"
                                                    disabled={cancellationForm.processing}
                                                    onClick={() => {
                                                        cancellationForm.clearErrors()
                                                        setCancelOpen(false)
                                                    }}
                                                    className="rounded-lg p-2 text-red-500 transition hover:bg-white hover:text-red-700 focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-red-500 disabled:cursor-not-allowed disabled:opacity-50"
                                                >
                                                    <X className="h-4 w-4" aria-hidden="true" />
                                                </button>
                                            </div>

                                            <div className="space-y-4 p-4 sm:p-5">
                                                {cancellationErrors.cancellation && (
                                                    <div
                                                        role="alert"
                                                        className="flex items-start gap-3 rounded-xl border border-red-200 bg-white px-4 py-3 text-sm text-red-700 shadow-sm"
                                                    >
                                                        <AlertTriangle
                                                            className="mt-0.5 h-4 w-4 shrink-0"
                                                            aria-hidden="true"
                                                        />
                                                        <p className="leading-6">
                                                            {cancellationErrors.cancellation}
                                                        </p>
                                                    </div>
                                                )}

                                                <div>
                                                    <div className="flex items-end justify-between gap-3">
                                                        <label
                                                            htmlFor="admin-cancellation-reason"
                                                            className="text-sm font-semibold text-slate-800"
                                                        >
                                                            Cancellation reason
                                                            <span
                                                                className="ml-1 text-red-600"
                                                                aria-hidden="true"
                                                            >
                                                                *
                                                            </span>
                                                        </label>

                                                        <span className="text-xs tabular-nums text-slate-400">
                                                            {cancellationForm.data.reason.length}
                                                            /1000
                                                        </span>
                                                    </div>

                                                    <textarea
                                                        id="admin-cancellation-reason"
                                                        rows={4}
                                                        required
                                                        maxLength={1000}
                                                        aria-invalid={
                                                            cancellationErrors.reason
                                                                ? true
                                                                : undefined
                                                        }
                                                        aria-describedby={
                                                            cancellationErrors.reason
                                                                ? 'admin-cancellation-reason-error'
                                                                : 'admin-cancellation-reason-help'
                                                        }
                                                        value={cancellationForm.data.reason}
                                                        onChange={(event) =>
                                                            cancellationForm.setData(
                                                                'reason',
                                                                event.target.value,
                                                            )
                                                        }
                                                        placeholder="Example: Duplicate order confirmed with the customer."
                                                        className={[
                                                            'mt-2 block min-h-28 w-full resize-y rounded-xl border bg-white px-3.5 py-3 text-sm leading-6 text-slate-900 shadow-sm outline-none transition placeholder:text-slate-400',
                                                            cancellationErrors.reason
                                                                ? 'border-red-300 focus:border-red-400 focus:ring-4 focus:ring-red-100'
                                                                : 'border-slate-300 focus:border-slate-400 focus:ring-4 focus:ring-slate-100',
                                                        ].join(' ')}
                                                    />

                                                    {cancellationErrors.reason ? (
                                                        <p
                                                            id="admin-cancellation-reason-error"
                                                            role="alert"
                                                            className="mt-2 text-sm font-medium text-red-600"
                                                        >
                                                            {cancellationErrors.reason}
                                                        </p>
                                                    ) : (
                                                        <p
                                                            id="admin-cancellation-reason-help"
                                                            className="mt-2 text-xs leading-5 text-slate-500"
                                                        >
                                                            Required. Minimum 3 characters. This
                                                            note will appear in the order status
                                                            history.
                                                        </p>
                                                    )}
                                                </div>

                                                <div className="flex flex-col-reverse gap-3 border-t border-red-100 pt-4 sm:flex-row sm:items-center sm:justify-end">
                                                    <button
                                                        type="button"
                                                        disabled={cancellationForm.processing}
                                                        onClick={() => {
                                                            cancellationForm.clearErrors()
                                                            setCancelOpen(false)
                                                        }}
                                                        className="inline-flex min-h-11 items-center justify-center rounded-xl border border-slate-300 bg-white px-4 py-2.5 text-sm font-semibold text-slate-700 shadow-sm transition hover:bg-slate-50 focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-slate-500 focus-visible:ring-offset-2 disabled:cursor-not-allowed disabled:opacity-50"
                                                    >
                                                        Keep order
                                                    </button>

                                                    <button
                                                        type="button"
                                                        disabled={
                                                            cancellationForm.processing ||
                                                            cancellationForm.data.reason.trim()
                                                                .length < 3
                                                        }
                                                        onClick={submitCancellation}
                                                        className="inline-flex min-h-11 items-center justify-center gap-2 rounded-xl bg-red-600 px-4 py-2.5 text-sm font-semibold text-white shadow-sm transition hover:bg-red-700 focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-red-600 focus-visible:ring-offset-2 disabled:cursor-not-allowed disabled:opacity-50"
                                                    >
                                                        {cancellationForm.processing ? (
                                                            <LoaderCircle
                                                                className="h-4 w-4 animate-spin"
                                                                aria-hidden="true"
                                                            />
                                                        ) : (
                                                            <Ban
                                                                className="h-4 w-4"
                                                                aria-hidden="true"
                                                            />
                                                        )}

                                                        {cancellationForm.processing
                                                            ? 'Cancelling…'
                                                            : 'Confirm cancellation'}
                                                    </button>
                                                </div>
                                            </div>
                                        </div>
                                    ) : (
                                        <div className="grid gap-3 sm:grid-cols-3">
                                            <CancellationInfo
                                                title="Reason required"
                                                description="Every admin cancellation is recorded with an audit reason."
                                            />

                                            <CancellationInfo
                                                title="Inventory safe"
                                                description="Only stock known to have been decremented is restored."
                                            />

                                            <CancellationInfo
                                                title="State protected"
                                                description="The server re-checks whether cancellation is still allowed."
                                            />
                                        </div>
                                    )
                                ) : (
                                    <div className="flex items-start gap-3 rounded-xl border border-slate-200 bg-slate-50 px-4 py-4">
                                        <div className="flex h-9 w-9 shrink-0 items-center justify-center rounded-lg bg-white text-slate-500 shadow-sm">
                                            <Ban className="h-4 w-4" aria-hidden="true" />
                                        </div>

                                        <div>
                                            <p className="font-semibold text-slate-900">
                                                Cancellation unavailable
                                            </p>

                                            <p className="mt-1 text-sm leading-6 text-slate-500">
                                                This order has already moved beyond the cancellable
                                                state. No cancellation request is exposed for its
                                                current status.
                                            </p>
                                        </div>
                                    </div>
                                )}
                            </div>
                        </section>

                        <section
                            aria-labelledby="order-history-heading"
                            className="overflow-hidden rounded-xl border border-slate-200 bg-white shadow-sm"
                        >
                            <div className="border-b border-slate-200 px-4 py-3">
                                <div className="flex items-center gap-3">
                                    <div className="flex h-9 w-9 shrink-0 items-center justify-center rounded-lg bg-slate-100 text-slate-700">
                                        <History className="h-4 w-4" aria-hidden="true" />
                                    </div>

                                    <div className="min-w-0">
                                        <h2
                                            id="order-history-heading"
                                            className="font-semibold text-slate-900"
                                        >
                                            Status history
                                        </h2>

                                        <p className="mt-0.5 text-xs leading-5 text-slate-500">
                                            Order, payment, and fulfilment timeline.
                                        </p>
                                    </div>
                                </div>
                            </div>

                            {order.timeline.length === 0 ? (
                                <div className="px-4 py-6 text-sm text-slate-500">
                                    No status history is available for this order.
                                </div>
                            ) : (
                                <div className="max-h-80 overflow-y-auto">
                                    <ol className="divide-y divide-slate-100">
                                        {order.timeline.map((entry) => (
                                            <li key={entry.id} className="px-4 py-3">
                                                <div className="flex items-start gap-2.5">
                                                    <div className="mt-0.5 flex h-7 w-7 shrink-0 items-center justify-center rounded-full bg-slate-100 text-slate-500">
                                                        <Clock3
                                                            className="h-3.5 w-3.5"
                                                            aria-hidden="true"
                                                        />
                                                    </div>

                                                    <div className="min-w-0 flex-1">
                                                        <div className="flex min-w-0 flex-wrap items-center gap-x-1.5 gap-y-1 text-sm">
                                                            <span className="font-semibold text-slate-900">
                                                                {entry.type_label}
                                                            </span>

                                                            <span
                                                                className="text-slate-300"
                                                                aria-hidden="true"
                                                            >
                                                                ·
                                                            </span>

                                                            {entry.from ? (
                                                                <>
                                                                    <span className="truncate text-slate-500">
                                                                        {entry.from.label}
                                                                    </span>

                                                                    <span
                                                                        className="shrink-0 text-slate-400"
                                                                        aria-hidden="true"
                                                                    >
                                                                        →
                                                                    </span>

                                                                    <span className="font-semibold text-slate-800">
                                                                        {entry.to.label}
                                                                    </span>
                                                                </>
                                                            ) : (
                                                                <span className="font-semibold text-slate-800">
                                                                    {entry.to.label}
                                                                </span>
                                                            )}
                                                        </div>

                                                        <time
                                                            dateTime={entry.changed_at}
                                                            className="mt-1 block text-[11px] font-medium text-slate-400"
                                                        >
                                                            {formatDate(entry.changed_at)}
                                                        </time>

                                                        {entry.note && (
                                                            <p className="mt-1.5 line-clamp-2 whitespace-pre-wrap break-words text-xs leading-5 text-slate-500">
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
                        </section>

                        <section
                            aria-labelledby="order-documents-heading"
                            className="overflow-hidden rounded-xl border border-slate-200 bg-white shadow-sm"
                        >
                            <div className="border-b border-slate-200 px-5 py-4">
                                <div className="flex items-center gap-3">
                                    <FileText
                                        className="h-5 w-5 text-slate-500"
                                        aria-hidden="true"
                                    />

                                    <div>
                                        <h2
                                            id="order-documents-heading"
                                            className="font-semibold text-slate-900"
                                        >
                                            Order documents
                                        </h2>

                                        <p className="mt-1 text-xs leading-5 text-slate-500">
                                            Generated documents for this order.
                                        </p>
                                    </div>
                                </div>
                            </div>

                            <div className="p-5">
                                {order.invoice ? (
                                    <Link
                                        href={order.invoice.url}
                                        className="group flex min-h-12 items-center justify-between gap-4 rounded-xl border border-slate-200 bg-slate-50 px-4 py-3 transition hover:border-slate-300 hover:bg-white focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-slate-950 focus-visible:ring-offset-2"
                                    >
                                        <div className="flex min-w-0 items-center gap-3">
                                            <div className="flex h-10 w-10 shrink-0 items-center justify-center rounded-lg bg-white text-slate-700 shadow-sm ring-1 ring-slate-200">
                                                <ReceiptText
                                                    className="h-5 w-5"
                                                    aria-hidden="true"
                                                />
                                            </div>

                                            <div className="min-w-0">
                                                <p className="font-semibold text-slate-900">
                                                    Invoice
                                                </p>

                                                <p className="mt-0.5 truncate text-xs text-slate-500">
                                                    {order.invoice.number}
                                                </p>
                                            </div>
                                        </div>

                                        <span className="shrink-0 text-sm font-semibold text-slate-700 transition group-hover:text-slate-950">
                                            View
                                        </span>
                                    </Link>
                                ) : (
                                    <div className="flex items-start gap-3 rounded-xl border border-dashed border-slate-300 bg-slate-50 px-4 py-4">
                                        <div className="flex h-10 w-10 shrink-0 items-center justify-center rounded-lg bg-white text-slate-400 shadow-sm ring-1 ring-slate-200">
                                            <ReceiptText className="h-5 w-5" aria-hidden="true" />
                                        </div>

                                        <div>
                                            <p className="font-semibold text-slate-900">
                                                Invoice not available yet
                                            </p>

                                            <p className="mt-1 text-xs leading-5 text-slate-500">
                                                The invoice is generated automatically when order
                                                processing begins.
                                            </p>
                                        </div>
                                    </div>
                                )}

                                <p className="mt-3 text-xs leading-5 text-slate-400">
                                    Delivery slips, notes, and other order documents can be added
                                    here later.
                                </p>
                            </div>
                        </section>
                    </div>
                </div>
            </div>
        </AdminLayout>
    )
}

function CancellationInfo({ title, description }: { title: string; description: string }) {
    return (
        <div className="rounded-xl border border-slate-200 bg-slate-50 p-4">
            <p className="text-sm font-semibold text-slate-900">{title}</p>
            <p className="mt-1 text-xs leading-5 text-slate-500">{description}</p>
        </div>
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
