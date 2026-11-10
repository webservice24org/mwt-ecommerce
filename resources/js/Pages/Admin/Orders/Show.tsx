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
import OrderPaymentPanel from '@/Components/Admin/Orders/OrderPaymentPanel'

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

interface ShippingMethodSummary {
    id: number
    name: string
    code: string
}

interface ShippingAllocationItem {
    order_item_id: number
    product_name: string
    sku: string | null
    variant_description: string | null
    ordered_quantity: number
    active_allocated_quantity: number
    remaining_quantity: number
}

interface ShipmentItemSummary {
    id: number
    order_item_id: number
    quantity: number
    product_name: string | null
    sku: string | null
    variant_description: string | null
}

type ProviderSubmissionTrigger = 'automatic' | 'manual'

interface ProviderSubmissionDetails {
    status: StatusValue
    trigger: ProviderSubmissionTrigger | null
    last_trigger: ProviderSubmissionTrigger | null
    external_reference: string | null
    attempt_count: number
    last_attempted_at: string | null
    submitted_at: string | null
    last_error: string | null
    tracking_sync_status: StatusValue
    tracking_sync_attempt_count: number
    provider_status: string | null
    provider_mapped_shipment_status: StatusValue | null
    reconciliation_status: StatusValue
    reconciliation_attempt_count: number
    last_tracking_sync_attempt_at: string | null
    last_tracking_synced_at: string | null
    last_tracking_sync_error: string | null
    last_reconciliation_attempt_at: string | null
    last_reconciled_at: string | null
    last_reconciliation_error: string | null
}

type ProviderReviewResolution = 'confirm_existing' | 'allow_retry'

interface ShipmentProviderAccountDetails {
    name: string
    auto_submit: boolean
    operational: boolean
}

interface ShipmentProviderDetails {
    code: string | null
    registered: boolean
    external: boolean
    account: ShipmentProviderAccountDetails | null
    submission: ProviderSubmissionDetails | null
}

interface ShipmentActions {
    can_start_processing: boolean
    can_update_tracking: boolean
    can_submit_provider: boolean
    can_sync_provider: boolean
    can_resolve_provider_review: boolean
    can_ship: boolean
    can_deliver: boolean
    can_cancel: boolean
    can_return: boolean
}

interface AdminShipment {
    id: number
    status: StatusValue
    shipping_method: ShippingMethodSummary | null
    carrier: string | null
    service: string | null
    tracking_number: string | null
    tracking_url: string | null
    shipped_at: string | null
    delivered_at: string | null
    returned_at: string | null
    provider: ShipmentProviderDetails
    items: ShipmentItemSummary[]
    actions: ShipmentActions
}

interface AdminShippingDetails {
    selected_method: {
        id: number | null
        code: string | null
        name: string | null
    }
    method_options: ShippingMethodSummary[]
    provider_options:
        ShippingProviderOption[]

    default_provider_code:
        string
    create_allowed: boolean
    summary: {
        shipment_count: number
        total_ordered_quantity: number
        total_active_allocated_quantity: number
        total_remaining_quantity: number
    }
    items: ShippingAllocationItem[]
    shipments: AdminShipment[]
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

        due_at: string | null

        paid_at: string | null

        actions: {
            can_mark_due: boolean
            can_mark_paid: boolean

            due_url: string
            paid_url: string
        }
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
    shipping_selection: ShippingSelectionSnapshot

    shipping: AdminShippingDetails

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

interface ShippingSelectionSnapshot {
    method_id: number | null
    method_code: string | null
    method_name: string | null

    rate_id: number | null
    rate_code: string | null
    rate_name: string | null

    delivery_total: number
}

interface ShippingProviderOption {
    code: string
    label: string

    external: boolean
    selectable: boolean
    connected: boolean
    is_default: boolean

    account_name:
        | string
        | null
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

    const visibleOrderActions =
        order.shipping.shipments.length === 0
            ? order.available_actions
            : order.available_actions.filter(
                  (action) => action.key !== 'ship' && action.key !== 'deliver',
              )

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

                <OrderPaymentPanel
                    currencyCode={
                        order.currency_code
                    }
                    total={
                        order.grand_total
                    }
                    payment={
                        order.payment
                    }
                />

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

                        <CreateShipmentPanel
                            key={`${order.id}:${order.shipping.selected_method.id ?? 'none'}:${order.shipping.summary.total_remaining_quantity}`}
                            orderId={order.id}
                            shipping={order.shipping}
                        />

                        <ShipmentWorkspace
                            orderId={order.id}
                            shipments={order.shipping.shipments}
                        />

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

                        <section className="overflow-hidden rounded-xl border border-slate-200 bg-white shadow-sm">
                            <div className="border-b border-slate-200 px-5 py-4 sm:px-6">
                                <div className="flex items-center gap-3">
                                    <div className="flex h-10 w-10 items-center justify-center rounded-lg bg-slate-100">
                                        <Truck
                                            className="h-5 w-5 text-slate-600"
                                            aria-hidden="true"
                                        />
                                    </div>

                                    <div>
                                        <h2 className="font-semibold text-slate-900">
                                            Checkout shipping
                                        </h2>

                                        <p className="mt-1 text-sm text-slate-500">
                                            Shipping selection captured when
                                            the order was placed.
                                        </p>
                                    </div>
                                </div>
                            </div>

                            <div className="grid gap-5 p-5 sm:grid-cols-3 sm:p-6">
                                <div>
                                    <p className="text-xs font-semibold uppercase tracking-wider text-slate-400">
                                        Shipping method
                                    </p>

                                    <p className="mt-1 font-semibold text-slate-900">
                                        {order.shipping_selection.method_name ??
                                            'Not recorded'}
                                    </p>

                                    {order.shipping_selection.method_code && (
                                        <p className="mt-1 text-xs text-slate-500">
                                            {
                                                order.shipping_selection
                                                    .method_code
                                            }
                                        </p>
                                    )}
                                </div>

                                <div>
                                    <p className="text-xs font-semibold uppercase tracking-wider text-slate-400">
                                        Shipping rate
                                    </p>

                                    <p className="mt-1 font-semibold text-slate-900">
                                        {order.shipping_selection.rate_name ??
                                            'Not recorded'}
                                    </p>

                                    {order.shipping_selection.rate_code && (
                                        <p className="mt-1 text-xs text-slate-500">
                                            {
                                                order.shipping_selection
                                                    .rate_code
                                            }
                                        </p>
                                    )}
                                </div>

                                <div>
                                    <p className="text-xs font-semibold uppercase tracking-wider text-slate-400">
                                        Delivery charge
                                    </p>

                                    <p className="mt-1 text-lg font-bold text-slate-950">
                                        {order.shipping_selection
                                            .delivery_total === 0
                                            ? 'Free'
                                            : formatMoney(
                                                order.shipping_selection
                                                    .delivery_total,
                                                order.currency_code,
                                            )}
                                    </p>
                                </div>
                            </div>

                            <div className="border-t border-slate-100 bg-slate-50 px-5 py-3 text-xs leading-5 text-slate-500 sm:px-6">
                                Historical snapshot — editing or deleting the
                                current shipping rate does not change this order.
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
                                {visibleOrderActions.length === 0 ? (
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
                                        {visibleOrderActions.map((action) => (
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

type ShipmentLifecycleAction = 'start-processing' | 'ship' | 'deliver' | 'return'

function ShipmentWorkspace({
    orderId,
    shipments,
}: {
    orderId: number
    shipments: AdminShipment[]
}) {
    return (
        <section
            aria-labelledby="physical-shipments-heading"
            className="overflow-hidden rounded-xl border border-slate-200 bg-white shadow-sm"
        >
            <div className="border-b border-slate-200 px-5 py-4 sm:px-6">
                <div className="flex items-start gap-3">
                    <div className="flex h-10 w-10 shrink-0 items-center justify-center rounded-lg bg-slate-100 text-slate-700">
                        <Package className="h-5 w-5" aria-hidden="true" />
                    </div>

                    <div className="min-w-0">
                        <h2
                            id="physical-shipments-heading"
                            className="font-semibold text-slate-900"
                        >
                            Physical shipments
                        </h2>

                        <p className="mt-1 text-sm leading-6 text-slate-500">
                            Track each parcel independently. Aggregate order fulfilment advances only
                            when all required quantities reach the matching physical state.
                        </p>
                    </div>
                </div>
            </div>

            {shipments.length === 0 ? (
                <div className="px-5 py-8 sm:px-6">
                    <div className="rounded-xl border border-dashed border-slate-300 bg-slate-50 px-4 py-6 text-center">
                        <Package
                            className="mx-auto h-7 w-7 text-slate-400"
                            aria-hidden="true"
                        />

                        <p className="mt-3 font-semibold text-slate-900">
                            No physical shipments yet
                        </p>

                        <p className="mx-auto mt-1 max-w-lg text-sm leading-6 text-slate-500">
                            Use the shipment allocation panel above to create the first parcel.
                        </p>
                    </div>
                </div>
            ) : (
                <div className="space-y-4 p-5 sm:p-6">
                    {shipments.map((shipment) => (
                        <ShipmentCard
                            key={`${shipment.id}:${shipment.status.value}:${shipment.tracking_number ?? ''}:${shipment.provider.submission?.status.value ?? 'none'}:${shipment.provider.submission?.tracking_sync_status.value ?? 'none'}`}
                            orderId={orderId}
                            shipment={shipment}
                        />
                    ))}
                </div>
            )}
        </section>
    )
}

function ShipmentCard({
    orderId,
    shipment,
}: {
    orderId: number
    shipment: AdminShipment
}) {
    const [trackingOpen, setTrackingOpen] = useState(false)
    const [cancelOpen, setCancelOpen] = useState(false)

    const transitionForm = useForm({
        note: '',
    })

    const trackingForm = useForm({
        carrier: shipment.carrier ?? '',
        service: shipment.service ?? '',
        tracking_number: shipment.tracking_number ?? '',
        tracking_url: shipment.tracking_url ?? '',
    })

    const cancelForm = useForm({
        reason: '',
    })

    const providerForm = useForm({})

    const providerReviewForm = useForm({
        resolution: 'confirm_existing' as ProviderReviewResolution,
        external_reference: shipment.provider.submission?.external_reference ?? '',
        note: '',
    })

    const transitionErrors = transitionForm.errors as Record<string, string>
    const trackingErrors = trackingForm.errors as Record<string, string>
    const cancelErrors = cancelForm.errors as Record<string, string>
    const providerErrors = providerForm.errors as Record<string, string>
    const providerReviewErrors = providerReviewForm.errors as Record<string, string>

    const submitToProvider = () => {
        providerForm.clearErrors()

        providerForm.post(
            route('admin.orders.shipments.provider.submit', [orderId, shipment.id]),
            {
                preserveScroll: true,
            },
        )
    }

    const syncProvider = () => {
        providerForm.clearErrors()

        providerForm.post(
            route('admin.orders.shipments.provider.sync', [orderId, shipment.id]),
            {
                preserveScroll: true,
            },
        )
    }

    const resolveProviderReview = () => {
        providerReviewForm.clearErrors()

        providerReviewForm.post(
            route('admin.orders.shipments.provider.review-resolution', [
                orderId,
                shipment.id,
            ]),
            {
                preserveScroll: true,
                onSuccess: () => {
                    providerReviewForm.reset('note')
                },
            },
        )
    }

    const performLifecycleAction = (action: ShipmentLifecycleAction) => {
        transitionForm.clearErrors()

        transitionForm.post(
            route(`admin.orders.shipments.${action}`, [orderId, shipment.id]),
            {
                preserveScroll: true,
                onSuccess: () => {
                    transitionForm.reset()
                },
            },
        )
    }

    const updateTracking = () => {
        trackingForm.clearErrors()

        trackingForm.patch(
            route('admin.orders.shipments.tracking.update', [orderId, shipment.id]),
            {
                preserveScroll: true,
                onSuccess: () => {
                    setTrackingOpen(false)
                },
            },
        )
    }

    const cancelShipment = () => {
        if (cancelForm.data.reason.trim().length < 3) {
            return
        }

        cancelForm.clearErrors()

        cancelForm.post(
            route('admin.orders.shipments.cancel', [orderId, shipment.id]),
            {
                preserveScroll: true,
                onSuccess: () => {
                    cancelForm.reset()
                    setCancelOpen(false)
                },
            },
        )
    }

    const hasLifecycleAction =
        shipment.actions.can_start_processing ||
        shipment.actions.can_ship ||
        shipment.actions.can_deliver ||
        shipment.actions.can_return

    const hasTrackingDetails =
        shipment.carrier !== null ||
        shipment.service !== null ||
        shipment.tracking_number !== null ||
        shipment.tracking_url !== null

    return (
        <article className="overflow-hidden rounded-2xl border border-slate-200 bg-slate-50/50">
            <div className="flex flex-col gap-4 border-b border-slate-200 bg-white px-4 py-4 sm:flex-row sm:items-start sm:justify-between sm:px-5">
                <div className="min-w-0">
                    <div className="flex flex-wrap items-center gap-2">
                        <h3 className="font-semibold text-slate-950">
                            Shipment #{shipment.id}
                        </h3>

                        <ShipmentStatusBadge
                            value={shipment.status.value}
                            label={shipment.status.label}
                        />
                    </div>

                    <p className="mt-1 text-sm text-slate-500">
                        {shipment.shipping_method
                            ? `${shipment.shipping_method.name} · ${shipment.shipping_method.code}`
                            : 'Shipping method unavailable'}
                    </p>
                </div>

                <p className="shrink-0 text-xs font-medium tabular-nums text-slate-500">
                    {shipment.items.reduce((total, item) => total + item.quantity, 0)}{' '}
                    {shipment.items.reduce((total, item) => total + item.quantity, 0) === 1
                        ? 'unit'
                        : 'units'}
                </p>
            </div>

            <div className="space-y-5 p-4 sm:p-5">
                <div>
                    <p className="text-xs font-semibold uppercase tracking-wide text-slate-500">
                        Shipment items
                    </p>

                    {shipment.items.length === 0 ? (
                        <p className="mt-2 text-sm text-slate-500">
                            No shipment items are attached.
                        </p>
                    ) : (
                        <div className="mt-3 divide-y divide-slate-200 overflow-hidden rounded-xl border border-slate-200 bg-white">
                            {shipment.items.map((item) => (
                                <div
                                    key={item.id}
                                    className="flex flex-col gap-2 px-4 py-3 sm:flex-row sm:items-start sm:justify-between"
                                >
                                    <div className="min-w-0">
                                        <p className="text-sm font-semibold text-slate-900">
                                            {item.product_name ?? `Order item #${item.order_item_id}`}
                                        </p>

                                        {item.variant_description && (
                                            <p className="mt-0.5 text-xs text-slate-500">
                                                {item.variant_description}
                                            </p>
                                        )}

                                        {item.sku && (
                                            <p className="mt-0.5 text-xs text-slate-400">
                                                SKU: {item.sku}
                                            </p>
                                        )}
                                    </div>

                                    <span className="shrink-0 rounded-full border border-slate-200 bg-slate-50 px-2.5 py-1 text-xs font-semibold tabular-nums text-slate-700">
                                        Qty {item.quantity}
                                    </span>
                                </div>
                            ))}
                        </div>
                    )}
                </div>

                <div className="grid gap-3 sm:grid-cols-3">
                    <ShipmentTimestamp
                        label="Shipped"
                        value={shipment.shipped_at}
                    />

                    <ShipmentTimestamp
                        label="Delivered"
                        value={shipment.delivered_at}
                    />

                    <ShipmentTimestamp
                        label="Returned"
                        value={shipment.returned_at}
                    />
                </div>

                <div className="rounded-xl border border-slate-200 bg-white">
                    <div className="flex flex-col gap-3 border-b border-slate-200 px-4 py-3 sm:flex-row sm:items-center sm:justify-between">
                        <div className="min-w-0">
                            <div className="flex flex-wrap items-center gap-2">
                                <p className="font-semibold text-slate-900">
                                    Courier provider
                                </p>

                                <ProviderModeBadge
                                    external={shipment.provider.external}
                                    registered={shipment.provider.registered}
                                />

                                {shipment.provider.account && (
                                    <span
                                        className={[
                                            'inline-flex items-center rounded-full px-2.5 py-1 text-xs font-semibold',
                                            shipment.provider.account.auto_submit
                                                ? 'bg-indigo-50 text-indigo-700'
                                                : 'bg-slate-100 text-slate-600',
                                        ].join(' ')}
                                    >
                                        {shipment.provider.account.auto_submit
                                            ? 'Auto-submit enabled'
                                            : 'Manual submit'}
                                    </span>
                                )}
                            </div>

                            <p className="mt-0.5 break-words text-xs leading-5 text-slate-500">
                                {shipment.provider.code
                                    ? `Courier: ${shipment.provider.code}${
                                          shipment.provider.account
                                              ? ` · Account: ${shipment.provider.account.name}`
                                              : ''
                                      }`
                                    : 'No courier provider is attached to this legacy shipment.'}
                            </p>
                        </div>

                        <div className="flex flex-wrap gap-2">
                            {shipment.actions.can_submit_provider && (
                                <button
                                    type="button"
                                    disabled={providerForm.processing}
                                    onClick={submitToProvider}
                                    className="inline-flex min-h-10 items-center justify-center gap-2 rounded-lg bg-slate-950 px-3.5 py-2 text-sm font-semibold text-white transition hover:bg-slate-800 disabled:cursor-not-allowed disabled:opacity-50"
                                >
                                    {providerForm.processing && (
                                        <LoaderCircle
                                            className="h-4 w-4 animate-spin"
                                            aria-hidden="true"
                                        />
                                    )}

                                    {shipment.provider.submission?.status.value === 'failed'
                                        ? 'Retry submission'
                                        : 'Submit to provider'}
                                </button>
                            )}

                            {shipment.actions.can_sync_provider && (
                                <button
                                    type="button"
                                    disabled={providerForm.processing}
                                    onClick={syncProvider}
                                    className="inline-flex min-h-10 items-center justify-center gap-2 rounded-lg border border-slate-300 bg-white px-3.5 py-2 text-sm font-semibold text-slate-700 transition hover:bg-slate-50 disabled:cursor-not-allowed disabled:opacity-50"
                                >
                                    {providerForm.processing && (
                                        <LoaderCircle
                                            className="h-4 w-4 animate-spin"
                                            aria-hidden="true"
                                        />
                                    )}

                                    Sync provider
                                </button>
                            )}
                        </div>
                    </div>

                    <div className="space-y-4 p-4">
                        {providerErrors.provider && (
                            <div
                                role="alert"
                                className="rounded-xl border border-red-200 bg-red-50 px-4 py-3 text-sm text-red-700"
                            >
                                {providerErrors.provider}
                            </div>
                        )}

                        {providerErrors.shipment_status && (
                            <div
                                role="alert"
                                className="rounded-xl border border-red-200 bg-red-50 px-4 py-3 text-sm text-red-700"
                            >
                                {providerErrors.shipment_status}
                            </div>
                        )}

                        {providerErrors.shipping_method_id && (
                            <div
                                role="alert"
                                className="rounded-xl border border-red-200 bg-red-50 px-4 py-3 text-sm text-red-700"
                            >
                                {providerErrors.shipping_method_id}
                            </div>
                        )}

                        {shipment.actions.can_resolve_provider_review &&
                            shipment.provider.submission?.status.value ===
                                'review_required' && (
                                <div className="rounded-xl border border-amber-300 bg-amber-50 p-4">
                                    <div className="flex items-start gap-3">
                                        <AlertTriangle
                                            className="mt-0.5 h-5 w-5 shrink-0 text-amber-700"
                                            aria-hidden="true"
                                        />

                                        <div className="min-w-0 flex-1">
                                            <p className="font-semibold text-amber-950">
                                                Manual provider review required
                                            </p>

                                            <p className="mt-1 text-sm leading-6 text-amber-800">
                                                The create request may have reached the courier even
                                                though our application did not receive a conclusive
                                                response. Check the provider panel before choosing a
                                                resolution. Never retry blindly.
                                            </p>
                                        </div>
                                    </div>

                                    <div className="mt-4 space-y-4">
                                        <div>
                                            <label
                                                htmlFor={`shipment-${shipment.id}-provider-review-resolution`}
                                                className="text-sm font-semibold text-amber-950"
                                            >
                                                Review decision
                                            </label>

                                            <select
                                                id={`shipment-${shipment.id}-provider-review-resolution`}
                                                value={providerReviewForm.data.resolution}
                                                disabled={providerReviewForm.processing}
                                                onChange={(event) =>
                                                    providerReviewForm.setData(
                                                        'resolution',
                                                        event.target
                                                            .value as ProviderReviewResolution,
                                                    )
                                                }
                                                className="mt-2 block w-full rounded-xl border border-amber-300 bg-white px-3.5 py-2.5 text-sm text-slate-900 shadow-sm outline-none transition focus:border-amber-400 focus:ring-4 focus:ring-amber-100 disabled:opacity-60"
                                            >
                                                <option value="confirm_existing">
                                                    I found the shipment in the provider panel
                                                </option>

                                                <option value="allow_retry">
                                                    I confirmed no provider shipment exists — allow retry
                                                </option>
                                            </select>
                                        </div>

                                        {providerReviewForm.data.resolution ===
                                            'confirm_existing' && (
                                            <div>
                                                <label
                                                    htmlFor={`shipment-${shipment.id}-provider-external-reference`}
                                                    className="text-sm font-semibold text-amber-950"
                                                >
                                                    Provider reference
                                                </label>

                                                <input
                                                    id={`shipment-${shipment.id}-provider-external-reference`}
                                                    type="text"
                                                    maxLength={190}
                                                    disabled={providerReviewForm.processing}
                                                    value={
                                                        providerReviewForm.data.external_reference
                                                    }
                                                    onChange={(event) =>
                                                        providerReviewForm.setData(
                                                            'external_reference',
                                                            event.target.value,
                                                        )
                                                    }
                                                    placeholder="Consignment / external shipment reference"
                                                    className="mt-2 block w-full rounded-xl border border-amber-300 bg-white px-3.5 py-2.5 text-sm text-slate-900 shadow-sm outline-none transition placeholder:text-slate-400 focus:border-amber-400 focus:ring-4 focus:ring-amber-100 disabled:opacity-60"
                                                />

                                                {providerReviewErrors.external_reference && (
                                                    <p className="mt-2 text-sm font-medium text-red-700">
                                                        {
                                                            providerReviewErrors.external_reference
                                                        }
                                                    </p>
                                                )}
                                            </div>
                                        )}

                                        <div>
                                            <label
                                                htmlFor={`shipment-${shipment.id}-provider-review-note`}
                                                className="text-sm font-semibold text-amber-950"
                                            >
                                                Review note
                                            </label>

                                            <textarea
                                                id={`shipment-${shipment.id}-provider-review-note`}
                                                rows={3}
                                                maxLength={500}
                                                disabled={providerReviewForm.processing}
                                                value={providerReviewForm.data.note}
                                                onChange={(event) =>
                                                    providerReviewForm.setData(
                                                        'note',
                                                        event.target.value,
                                                    )
                                                }
                                                placeholder="Record what you verified in the provider panel..."
                                                className="mt-2 block w-full resize-y rounded-xl border border-amber-300 bg-white px-3.5 py-3 text-sm leading-6 text-slate-900 shadow-sm outline-none transition placeholder:text-slate-400 focus:border-amber-400 focus:ring-4 focus:ring-amber-100 disabled:opacity-60"
                                            />

                                            {providerReviewErrors.note && (
                                                <p className="mt-2 text-sm font-medium text-red-700">
                                                    {providerReviewErrors.note}
                                                </p>
                                            )}
                                        </div>

                                        {providerReviewErrors.provider && (
                                            <div
                                                role="alert"
                                                className="rounded-lg border border-red-200 bg-red-50 px-3 py-2 text-sm text-red-700"
                                            >
                                                {providerReviewErrors.provider}
                                            </div>
                                        )}

                                        <button
                                            type="button"
                                            disabled={
                                                providerReviewForm.processing ||
                                                providerReviewForm.data.note.trim().length < 3 ||
                                                (providerReviewForm.data.resolution ===
                                                    'confirm_existing' &&
                                                    providerReviewForm.data.external_reference.trim()
                                                        .length === 0)
                                            }
                                            onClick={resolveProviderReview}
                                            className="inline-flex min-h-10 items-center justify-center gap-2 rounded-lg bg-amber-800 px-3.5 py-2 text-sm font-semibold text-white transition hover:bg-amber-900 disabled:cursor-not-allowed disabled:opacity-50"
                                        >
                                            {providerReviewForm.processing && (
                                                <LoaderCircle
                                                    className="h-4 w-4 animate-spin"
                                                    aria-hidden="true"
                                                />
                                            )}

                                            Apply reviewed resolution
                                        </button>
                                    </div>
                                </div>
                            )}

                        {!shipment.provider.external ? (
                            <p className="text-sm leading-6 text-slate-500">
                                This shipment uses manual fulfilment. External submission and remote
                                tracking synchronization are not required.
                            </p>
                        ) : !shipment.provider.registered ? (
                            <div className="rounded-xl border border-amber-200 bg-amber-50 px-4 py-3 text-sm leading-6 text-amber-800">
                                The configured provider driver is not registered in the application.
                                Provider controls are disabled.
                            </div>
                        ) : shipment.provider.submission === null ? (
                            <div className="rounded-xl border border-slate-200 bg-slate-50 px-4 py-3 text-sm leading-6 text-slate-600">
                                {shipment.provider.account?.auto_submit
                                    ? 'Auto-submit is currently enabled for this courier account, but this shipment does not have a provider submission yet.'
                                    : 'This shipment has not yet been submitted to the external provider. Use Submit to provider when you are ready.'}
                            </div>
                        ) : (
                            <>
                                <div className="grid gap-3 sm:grid-cols-2 lg:grid-cols-3 xl:grid-cols-5">
                                    <ProviderState
                                        label="Submission"
                                        value={shipment.provider.submission.status.label}
                                    />

                                    <ProviderState
                                        label="Started by"
                                        value={
                                            shipment.provider.submission.trigger === 'automatic'
                                                ? 'Automatic'
                                                : shipment.provider.submission.trigger === 'manual'
                                                  ? 'Manual'
                                                  : 'Legacy / unknown'
                                        }
                                    />

                                    <ProviderState
                                        label="Tracking sync"
                                        value={shipment.provider.submission.tracking_sync_status.label}
                                    />

                                    <ProviderState
                                        label="Reconciliation"
                                        value={shipment.provider.submission.reconciliation_status.label}
                                    />

                                    <ProviderState
                                        label="Provider status"
                                        value={shipment.provider.submission.provider_status ?? '—'}
                                    />
                                </div>

                                <dl className="grid gap-4 rounded-xl border border-slate-200 bg-slate-50 p-4 sm:grid-cols-2">
                                    <ProviderDetail
                                        label="External reference"
                                        value={
                                            shipment.provider.submission.external_reference ??
                                            'Not assigned'
                                        }
                                    />

                                    <ProviderDetail
                                        label="Mapped shipment state"
                                        value={
                                            shipment.provider.submission
                                                .provider_mapped_shipment_status?.label ?? '—'
                                        }
                                    />

                                    <ProviderDetail
                                        label="Submission attempts"
                                        value={String(
                                            shipment.provider.submission.attempt_count,
                                        )}
                                    />

                                    <ProviderDetail
                                        label="Last submission trigger"
                                        value={
                                            shipment.provider.submission.last_trigger === 'automatic'
                                                ? 'Automatic'
                                                : shipment.provider.submission.last_trigger ===
                                                    'manual'
                                                  ? 'Manual'
                                                  : 'Legacy / unknown'
                                        }
                                    />

                                    <ProviderDetail
                                        label="Tracking sync attempts"
                                        value={String(
                                            shipment.provider.submission
                                                .tracking_sync_attempt_count,
                                        )}
                                    />

                                    <ProviderDetail
                                        label="Submitted"
                                        value={formatOptionalDate(
                                            shipment.provider.submission.submitted_at,
                                        )}
                                    />

                                    <ProviderDetail
                                        label="Last tracking sync"
                                        value={formatOptionalDate(
                                            shipment.provider.submission
                                                .last_tracking_synced_at,
                                        )}
                                    />

                                    <ProviderDetail
                                        label="Last reconciled"
                                        value={formatOptionalDate(
                                            shipment.provider.submission.last_reconciled_at,
                                        )}
                                    />

                                    <ProviderDetail
                                        label="Reconciliation attempts"
                                        value={String(
                                            shipment.provider.submission
                                                .reconciliation_attempt_count,
                                        )}
                                    />
                                </dl>

                                {(shipment.provider.submission.last_error ||
                                    shipment.provider.submission
                                        .last_tracking_sync_error ||
                                    shipment.provider.submission
                                        .last_reconciliation_error) && (
                                    <div className="space-y-2">
                                        {shipment.provider.submission.last_error && (
                                            <ProviderIssue
                                                label="Submission"
                                                message={
                                                    shipment.provider.submission.last_error
                                                }
                                            />
                                        )}

                                        {shipment.provider.submission
                                            .last_tracking_sync_error && (
                                            <ProviderIssue
                                                label="Tracking sync"
                                                message={
                                                    shipment.provider.submission
                                                        .last_tracking_sync_error
                                                }
                                            />
                                        )}

                                        {shipment.provider.submission
                                            .last_reconciliation_error && (
                                            <ProviderIssue
                                                label="Reconciliation"
                                                message={
                                                    shipment.provider.submission
                                                        .last_reconciliation_error
                                                }
                                            />
                                        )}
                                    </div>
                                )}

                                {['queued', 'syncing'].includes(
                                    shipment.provider.submission.tracking_sync_status.value,
                                ) && (
                                    <div className="flex items-center gap-2 rounded-xl border border-blue-200 bg-blue-50 px-4 py-3 text-sm text-blue-700">
                                        <LoaderCircle
                                            className="h-4 w-4 animate-spin"
                                            aria-hidden="true"
                                        />
                                        Provider tracking synchronization is{' '}
                                        {shipment.provider.submission.tracking_sync_status.value ===
                                        'queued'
                                            ? 'queued.'
                                            : 'running.'}
                                    </div>
                                )}
                            </>
                        )}
                    </div>
                </div>

                <div className="rounded-xl border border-slate-200 bg-white">
                    <div className="flex flex-col gap-3 border-b border-slate-200 px-4 py-3 sm:flex-row sm:items-center sm:justify-between">
                        <div>
                            <p className="font-semibold text-slate-900">
                                Tracking
                            </p>

                            <p className="mt-0.5 text-xs leading-5 text-slate-500">
                                {shipment.provider.external
                                    ? 'Tracking for this parcel is provider-managed. Use provider sync instead of manual tracking edits.'
                                    : 'Carrier is required before a manual parcel can be marked shipped. Tracking number and URL are optional.'}
                            </p>

                            {!shipment.provider.external &&
                                shipment.status.value === 'processing' &&
                                !shipment.carrier && (
                                    <p className="mt-2 text-xs font-semibold text-amber-700">
                                        Add a carrier in tracking details before marking this shipment
                                        as shipped.
                                    </p>
                                )}
                        </div>

                        {shipment.actions.can_update_tracking && !trackingOpen && (
                            <button
                                type="button"
                                onClick={() => {
                                    trackingForm.clearErrors()
                                    setTrackingOpen(true)
                                }}
                                className="inline-flex min-h-10 items-center justify-center rounded-lg border border-slate-300 bg-white px-3 py-2 text-sm font-semibold text-slate-700 transition hover:bg-slate-50 focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-slate-500 focus-visible:ring-offset-2"
                            >
                                Edit tracking
                            </button>
                        )}
                    </div>

                    {trackingOpen ? (
                        <div className="space-y-4 p-4">
                            {trackingErrors.tracking && (
                                <div
                                    role="alert"
                                    className="rounded-xl border border-red-200 bg-red-50 px-4 py-3 text-sm text-red-700"
                                >
                                    {trackingErrors.tracking}
                                </div>
                            )}

                            <div className="grid gap-4 sm:grid-cols-2">
                                <ShipmentTextField
                                    id={`shipment-${shipment.id}-carrier`}
                                    label="Carrier"
                                    value={trackingForm.data.carrier}
                                    error={trackingErrors.carrier}
                                    maxLength={120}
                                    placeholder="Example: Pathao"
                                    onChange={(value) =>
                                        trackingForm.setData('carrier', value)
                                    }
                                />

                                <ShipmentTextField
                                    id={`shipment-${shipment.id}-service`}
                                    label="Service"
                                    value={trackingForm.data.service}
                                    error={trackingErrors.service}
                                    maxLength={120}
                                    placeholder="Example: Express"
                                    onChange={(value) =>
                                        trackingForm.setData('service', value)
                                    }
                                />

                                <ShipmentTextField
                                    id={`shipment-${shipment.id}-tracking-number`}
                                    label="Tracking number"
                                    value={trackingForm.data.tracking_number}
                                    error={trackingErrors.tracking_number}
                                    maxLength={190}
                                    placeholder="Example: TRK-10001"
                                    onChange={(value) =>
                                        trackingForm.setData('tracking_number', value)
                                    }
                                />

                                <ShipmentTextField
                                    id={`shipment-${shipment.id}-tracking-url`}
                                    label="Tracking URL"
                                    value={trackingForm.data.tracking_url}
                                    error={trackingErrors.tracking_url}
                                    maxLength={2048}
                                    placeholder="https://..."
                                    onChange={(value) =>
                                        trackingForm.setData('tracking_url', value)
                                    }
                                />
                            </div>

                            <div className="flex flex-col-reverse gap-3 sm:flex-row sm:justify-end">
                                <button
                                    type="button"
                                    disabled={trackingForm.processing}
                                    onClick={() => {
                                        trackingForm.clearErrors()
                                        trackingForm.setData({
                                            carrier: shipment.carrier ?? '',
                                            service: shipment.service ?? '',
                                            tracking_number: shipment.tracking_number ?? '',
                                            tracking_url: shipment.tracking_url ?? '',
                                        })
                                        setTrackingOpen(false)
                                    }}
                                    className="inline-flex min-h-10 items-center justify-center rounded-lg border border-slate-300 bg-white px-3 py-2 text-sm font-semibold text-slate-700 transition hover:bg-slate-50 disabled:cursor-not-allowed disabled:opacity-50"
                                >
                                    Cancel
                                </button>

                                <button
                                    type="button"
                                    disabled={trackingForm.processing}
                                    onClick={updateTracking}
                                    className="inline-flex min-h-10 items-center justify-center gap-2 rounded-lg bg-slate-950 px-4 py-2 text-sm font-semibold text-white transition hover:bg-slate-800 disabled:cursor-not-allowed disabled:opacity-50"
                                >
                                    {trackingForm.processing && (
                                        <LoaderCircle
                                            className="h-4 w-4 animate-spin"
                                            aria-hidden="true"
                                        />
                                    )}

                                    Save tracking
                                </button>
                            </div>
                        </div>
                    ) : (
                        <div className="p-4">
                            {hasTrackingDetails ? (
                                <dl className="grid gap-4 sm:grid-cols-2">
                                    <TrackingValue
                                        label="Carrier"
                                        value={shipment.carrier}
                                    />

                                    <TrackingValue
                                        label="Service"
                                        value={shipment.service}
                                    />

                                    <TrackingValue
                                        label="Tracking number"
                                        value={shipment.tracking_number}
                                    />

                                    <div>
                                        <dt className="text-xs font-semibold uppercase tracking-wide text-slate-500">
                                            Tracking link
                                        </dt>

                                        <dd className="mt-1 text-sm">
                                            {shipment.tracking_url ? (
                                                <a
                                                    href={shipment.tracking_url}
                                                    target="_blank"
                                                    rel="noopener noreferrer"
                                                    className="break-all font-semibold text-slate-900 underline decoration-slate-300 underline-offset-4 hover:decoration-slate-700"
                                                >
                                                    Open tracking ↗
                                                </a>
                                            ) : (
                                                <span className="text-slate-400">—</span>
                                            )}
                                        </dd>
                                    </div>
                                </dl>
                            ) : (
                                <p className="text-sm text-slate-500">
                                    No tracking details have been recorded.
                                </p>
                            )}
                        </div>
                    )}
                </div>

                {(hasLifecycleAction || shipment.actions.can_cancel) && (
                    <div className="rounded-xl border border-slate-200 bg-white">
                        <div className="border-b border-slate-200 px-4 py-3">
                            <p className="font-semibold text-slate-900">
                                Shipment lifecycle
                            </p>

                            <p className="mt-0.5 text-xs leading-5 text-slate-500">
                                Only transitions currently allowed by the server are shown.
                            </p>
                        </div>

                        <div className="space-y-4 p-4">
                            {transitionErrors.shipment_status && (
                                <div
                                    role="alert"
                                    className="rounded-xl border border-red-200 bg-red-50 px-4 py-3 text-sm text-red-700"
                                >
                                    {transitionErrors.shipment_status}
                                </div>
                            )}

                            {transitionErrors.status && (
                                <div
                                    role="alert"
                                    className="rounded-xl border border-red-200 bg-red-50 px-4 py-3 text-sm text-red-700"
                                >
                                    {transitionErrors.status}
                                </div>
                            )}

                            {transitionErrors.fulfilment_status && (
                                <div
                                    role="alert"
                                    className="rounded-xl border border-red-200 bg-red-50 px-4 py-3 text-sm text-red-700"
                                >
                                    {transitionErrors.fulfilment_status}
                                </div>
                            )}

                            {hasLifecycleAction && (
                                <div>
                                    <label
                                        htmlFor={`shipment-${shipment.id}-transition-note`}
                                        className="text-sm font-semibold text-slate-800"
                                    >
                                        Internal transition note
                                    </label>

                                    <textarea
                                        id={`shipment-${shipment.id}-transition-note`}
                                        rows={2}
                                        maxLength={1000}
                                        value={transitionForm.data.note}
                                        onChange={(event) =>
                                            transitionForm.setData('note', event.target.value)
                                        }
                                        placeholder="Optional note for this shipment transition..."
                                        className="mt-2 block w-full resize-y rounded-xl border border-slate-300 bg-white px-3.5 py-3 text-sm leading-6 text-slate-900 shadow-sm outline-none transition placeholder:text-slate-400 focus:border-slate-400 focus:ring-4 focus:ring-slate-100"
                                    />

                                    {transitionErrors.note && (
                                        <p className="mt-2 text-sm font-medium text-red-600">
                                            {transitionErrors.note}
                                        </p>
                                    )}
                                </div>
                            )}

                            <div className="flex flex-wrap gap-2">
                                {shipment.actions.can_start_processing && (
                                    <ShipmentActionButton
                                        label="Start processing"
                                        disabled={transitionForm.processing}
                                        onClick={() =>
                                            performLifecycleAction('start-processing')
                                        }
                                    />
                                )}

                                {shipment.actions.can_ship && (
                                    <ShipmentActionButton
                                        label="Mark shipped"
                                        disabled={transitionForm.processing}
                                        onClick={() => performLifecycleAction('ship')}
                                    />
                                )}

                                {shipment.actions.can_deliver && (
                                    <ShipmentActionButton
                                        label="Mark delivered"
                                        disabled={transitionForm.processing}
                                        onClick={() => performLifecycleAction('deliver')}
                                    />
                                )}

                                {shipment.actions.can_return && (
                                    <ShipmentActionButton
                                        label="Mark returned"
                                        disabled={transitionForm.processing}
                                        onClick={() => performLifecycleAction('return')}
                                        secondary
                                    />
                                )}

                                {shipment.actions.can_cancel && !cancelOpen && (
                                    <button
                                        type="button"
                                        disabled={transitionForm.processing}
                                        onClick={() => {
                                            cancelForm.clearErrors()
                                            setCancelOpen(true)
                                        }}
                                        className="inline-flex min-h-10 items-center justify-center gap-2 rounded-lg border border-red-200 bg-white px-3.5 py-2 text-sm font-semibold text-red-700 transition hover:bg-red-50 disabled:cursor-not-allowed disabled:opacity-50"
                                    >
                                        <Ban
                                            className="h-4 w-4"
                                            aria-hidden="true"
                                        />
                                        Cancel shipment
                                    </button>
                                )}
                            </div>

                            {transitionForm.processing && (
                                <div className="flex items-center gap-2 text-sm text-slate-500">
                                    <LoaderCircle
                                        className="h-4 w-4 animate-spin"
                                        aria-hidden="true"
                                    />
                                    Updating shipment…
                                </div>
                            )}

                            {cancelOpen && shipment.actions.can_cancel && (
                                <div className="rounded-xl border border-red-200 bg-red-50/50 p-4">
                                    <div className="flex items-start justify-between gap-3">
                                        <div>
                                            <p className="font-semibold text-red-950">
                                                Cancel shipment
                                            </p>

                                            <p className="mt-1 text-sm leading-6 text-red-700">
                                                Cancelling releases this shipment allocation. It
                                                does not cancel the order or restore product stock.
                                            </p>
                                        </div>

                                        <button
                                            type="button"
                                            aria-label="Close shipment cancellation"
                                            disabled={cancelForm.processing}
                                            onClick={() => {
                                                cancelForm.clearErrors()
                                                setCancelOpen(false)
                                            }}
                                            className="rounded-lg p-2 text-red-500 transition hover:bg-white hover:text-red-700 disabled:opacity-50"
                                        >
                                            <X className="h-4 w-4" aria-hidden="true" />
                                        </button>
                                    </div>

                                    <label
                                        htmlFor={`shipment-${shipment.id}-cancel-reason`}
                                        className="mt-4 block text-sm font-semibold text-slate-800"
                                    >
                                        Cancellation reason
                                    </label>

                                    <textarea
                                        id={`shipment-${shipment.id}-cancel-reason`}
                                        rows={3}
                                        maxLength={1000}
                                        required
                                        value={cancelForm.data.reason}
                                        onChange={(event) =>
                                            cancelForm.setData('reason', event.target.value)
                                        }
                                        placeholder="Why is this physical shipment being cancelled?"
                                        className={[
                                            'mt-2 block w-full resize-y rounded-xl border bg-white px-3.5 py-3 text-sm leading-6 text-slate-900 shadow-sm outline-none transition placeholder:text-slate-400',
                                            cancelErrors.reason
                                                ? 'border-red-300 focus:border-red-400 focus:ring-4 focus:ring-red-100'
                                                : 'border-slate-300 focus:border-slate-400 focus:ring-4 focus:ring-slate-100',
                                        ].join(' ')}
                                    />

                                    {cancelErrors.reason && (
                                        <p className="mt-2 text-sm font-medium text-red-600">
                                            {cancelErrors.reason}
                                        </p>
                                    )}

                                    {cancelErrors.shipment_status && (
                                        <p className="mt-2 text-sm font-medium text-red-600">
                                            {cancelErrors.shipment_status}
                                        </p>
                                    )}

                                    <div className="mt-4 flex flex-col-reverse gap-2 sm:flex-row sm:justify-end">
                                        <button
                                            type="button"
                                            disabled={cancelForm.processing}
                                            onClick={() => {
                                                cancelForm.clearErrors()
                                                setCancelOpen(false)
                                            }}
                                            className="inline-flex min-h-10 items-center justify-center rounded-lg border border-slate-300 bg-white px-3.5 py-2 text-sm font-semibold text-slate-700 transition hover:bg-slate-50 disabled:opacity-50"
                                        >
                                            Keep shipment
                                        </button>

                                        <button
                                            type="button"
                                            disabled={
                                                cancelForm.processing ||
                                                cancelForm.data.reason.trim().length < 3
                                            }
                                            onClick={cancelShipment}
                                            className="inline-flex min-h-10 items-center justify-center gap-2 rounded-lg bg-red-600 px-3.5 py-2 text-sm font-semibold text-white transition hover:bg-red-700 disabled:cursor-not-allowed disabled:opacity-50"
                                        >
                                            {cancelForm.processing && (
                                                <LoaderCircle
                                                    className="h-4 w-4 animate-spin"
                                                    aria-hidden="true"
                                                />
                                            )}

                                            Confirm cancellation
                                        </button>
                                    </div>
                                </div>
                            )}
                        </div>
                    </div>
                )}
            </div>
        </article>
    )
}

function ShipmentActionButton({
    label,
    disabled,
    onClick,
    secondary = false,
}: {
    label: string
    disabled: boolean
    onClick: () => void
    secondary?: boolean
}) {
    return (
        <button
            type="button"
            disabled={disabled}
            onClick={onClick}
            className={[
                'inline-flex min-h-10 items-center justify-center gap-2 rounded-lg px-3.5 py-2 text-sm font-semibold transition disabled:cursor-not-allowed disabled:opacity-50',
                secondary
                    ? 'border border-slate-300 bg-white text-slate-700 hover:bg-slate-50'
                    : 'bg-slate-950 text-white hover:bg-slate-800',
            ].join(' ')}
        >
            {disabled ? (
                <LoaderCircle
                    className="h-4 w-4 animate-spin"
                    aria-hidden="true"
                />
            ) : (
                <Send className="h-4 w-4" aria-hidden="true" />
            )}

            {label}
        </button>
    )
}

function ProviderModeBadge({
    external,
    registered,
}: {
    external: boolean
    registered: boolean
}) {
    if (!external) {
        return (
            <span className="inline-flex rounded-full border border-slate-200 bg-slate-50 px-2.5 py-1 text-xs font-semibold text-slate-600">
                Manual
            </span>
        )
    }

    return (
        <span
            className={[
                'inline-flex rounded-full border px-2.5 py-1 text-xs font-semibold',
                registered
                    ? 'border-blue-200 bg-blue-50 text-blue-700'
                    : 'border-amber-200 bg-amber-50 text-amber-700',
            ].join(' ')}
        >
            {registered ? 'External' : 'Unregistered'}
        </span>
    )
}

function ProviderState({
    label,
    value,
}: {
    label: string
    value: string
}) {
    return (
        <div className="rounded-xl border border-slate-200 bg-white px-3 py-3">
            <p className="text-[11px] font-semibold uppercase tracking-wide text-slate-500">
                {label}
            </p>

            <p className="mt-1 break-words text-sm font-semibold text-slate-800">
                {value}
            </p>
        </div>
    )
}

function ProviderDetail({
    label,
    value,
}: {
    label: string
    value: string
}) {
    return (
        <div>
            <dt className="text-xs font-semibold uppercase tracking-wide text-slate-500">
                {label}
            </dt>

            <dd className="mt-1 break-words text-sm font-medium text-slate-800">
                {value}
            </dd>
        </div>
    )
}

function ProviderIssue({
    label,
    message,
}: {
    label: string
    message: string
}) {
    return (
        <div className="rounded-xl border border-amber-200 bg-amber-50 px-4 py-3">
            <p className="text-xs font-semibold uppercase tracking-wide text-amber-700">
                {label}
            </p>

            <p className="mt-1 text-sm leading-6 text-amber-800">
                {message}
            </p>
        </div>
    )
}

function formatOptionalDate(value: string | null): string {
    return value ? formatDate(value) : '—'
}

function ShipmentStatusBadge({
    label,
    value,
}: {
    label: string
    value: string
}) {
    const className = (() => {
        switch (value) {
            case 'delivered':
                return 'border-emerald-200 bg-emerald-50 text-emerald-700'

            case 'shipped':
                return 'border-blue-200 bg-blue-50 text-blue-700'

            case 'processing':
                return 'border-sky-200 bg-sky-50 text-sky-700'

            case 'returned':
                return 'border-violet-200 bg-violet-50 text-violet-700'

            case 'cancelled':
                return 'border-red-200 bg-red-50 text-red-700'

            case 'pending':
            default:
                return 'border-amber-200 bg-amber-50 text-amber-700'
        }
    })()

    return (
        <span
            className={`inline-flex rounded-full border px-2.5 py-1 text-xs font-semibold ${className}`}
        >
            {label}
        </span>
    )
}

function ShipmentTimestamp({
    label,
    value,
}: {
    label: string
    value: string | null
}) {
    return (
        <div className="rounded-xl border border-slate-200 bg-white px-3 py-3">
            <p className="text-[11px] font-semibold uppercase tracking-wide text-slate-500">
                {label}
            </p>

            <p className="mt-1 text-sm font-semibold text-slate-800">
                {value ? formatDate(value) : '—'}
            </p>
        </div>
    )
}

function TrackingValue({
    label,
    value,
}: {
    label: string
    value: string | null
}) {
    return (
        <div>
            <dt className="text-xs font-semibold uppercase tracking-wide text-slate-500">
                {label}
            </dt>

            <dd className="mt-1 break-words text-sm font-medium text-slate-800">
                {value ?? '—'}
            </dd>
        </div>
    )
}

function ShipmentTextField({
    id,
    label,
    value,
    error,
    maxLength,
    placeholder,
    onChange,
}: {
    id: string
    label: string
    value: string
    error?: string
    maxLength: number
    placeholder: string
    onChange: (value: string) => void
}) {
    return (
        <div>
            <label
                htmlFor={id}
                className="text-sm font-semibold text-slate-800"
            >
                {label}
            </label>

            <input
                id={id}
                type="text"
                maxLength={maxLength}
                value={value}
                onChange={(event) => onChange(event.target.value)}
                placeholder={placeholder}
                className={[
                    'mt-2 block min-h-11 w-full rounded-xl border bg-white px-3.5 py-2.5 text-sm text-slate-900 shadow-sm outline-none transition placeholder:text-slate-400',
                    error
                        ? 'border-red-300 focus:border-red-400 focus:ring-4 focus:ring-red-100'
                        : 'border-slate-300 focus:border-slate-400 focus:ring-4 focus:ring-slate-100',
                ].join(' ')}
            />

            {error && (
                <p className="mt-2 text-sm font-medium text-red-600">
                    {error}
                </p>
            )}
        </div>
    )
}

function CreateShipmentPanel({
    orderId,
    shipping,
}: {
    orderId: number
    shipping: AdminShippingDetails
}) {
    const defaultMethodId =
        shipping.selected_method.id?.toString() ??
        shipping.method_options[0]?.id.toString() ??
        ''

    const form = useForm({
        shipping_method_id:
            defaultMethodId,

        provider_code:
            shipping
                .default_provider_code,

        carrier: '',
        service: '',

        items:
            shipping.items.map(
                (item) => ({
                    order_item_id:
                        item.order_item_id,

                    quantity: 0,
                }),
            ),
    })

    const manualProvider =
        form.data.provider_code ===
        'manual'

    const formErrors = form.errors as Record<string, string>

    const selectedQuantity = form.data.items.reduce(
        (total, item) => total + item.quantity,
        0,
    )

    const allocationErrors = Array.from(
        new Set(
            Object.entries(formErrors)
                .filter(([key]) => key === 'items' || key.startsWith('items.'))
                .map(([, message]) => message),
        ),
    )

    const setAllocationQuantity = (orderItemId: number, rawQuantity: number) => {
        const allocationItem = shipping.items.find(
            (item) => item.order_item_id === orderItemId,
        )

        if (!allocationItem) {
            return
        }

        const quantity = Number.isFinite(rawQuantity)
            ? Math.min(
                  Math.max(Math.trunc(rawQuantity), 0),
                  allocationItem.remaining_quantity,
              )
            : 0

        form.setData(
            'items',
            form.data.items.map((item) =>
                item.order_item_id === orderItemId
                    ? {
                          ...item,
                          quantity,
                      }
                    : item,
            ),
        )
    }

    const submit = () => {
        if (
            !shipping.create_allowed ||
            form.processing ||
            selectedQuantity <= 0 ||
            form.data.shipping_method_id === '' ||
            form.data.provider_code === ''
        ) {
            return
        }

        form.clearErrors()

        form.transform((data) => ({
            ...data,
            items: data.items.filter((item) => item.quantity > 0),
        }))

        form.post(route('admin.orders.shipments.store', orderId), {
            preserveScroll: true,
            onSuccess: () => {
                form.reset()
            },
        })
    }

    const unavailableMessage =
        shipping.summary.total_remaining_quantity === 0
            ? 'Every ordered quantity is already allocated to an active shipment.'
            : shipping.method_options.length === 0
              ? 'No active shipping method is available for this order.'
              : 'Shipment creation is unavailable from the current order or fulfilment state.'

    return (
        <section
            aria-labelledby="shipment-allocation-heading"
            className="overflow-hidden rounded-xl border border-slate-200 bg-white shadow-sm"
        >
            <div className="border-b border-slate-200 px-5 py-4 sm:px-6">
                <div className="flex items-start gap-3">
                    <div className="flex h-10 w-10 shrink-0 items-center justify-center rounded-lg bg-slate-100 text-slate-700">
                        <Truck className="h-5 w-5" aria-hidden="true" />
                    </div>

                    <div className="min-w-0">
                        <h2
                            id="shipment-allocation-heading"
                            className="font-semibold text-slate-900"
                        >
                            Shipment allocation
                        </h2>

                        <p className="mt-1 text-sm leading-6 text-slate-500">
                            Allocate only the quantities that should belong to this physical shipment.
                            Remaining quantities stay available for later partial shipments.
                        </p>
                    </div>
                </div>
            </div>

            <div className="space-y-6 p-5 sm:p-6">
                <div className="grid grid-cols-2 gap-3 sm:grid-cols-4">
                    <ShipmentStat
                        label="Ordered"
                        value={shipping.summary.total_ordered_quantity}
                    />

                    <ShipmentStat
                        label="Allocated"
                        value={shipping.summary.total_active_allocated_quantity}
                    />

                    <ShipmentStat
                        label="Remaining"
                        value={shipping.summary.total_remaining_quantity}
                    />

                    <ShipmentStat
                        label="Shipments"
                        value={shipping.summary.shipment_count}
                    />
                </div>

                {!shipping.create_allowed ? (
                    <div className="rounded-xl border border-slate-200 bg-slate-50 px-4 py-4">
                        <div className="flex items-start gap-3">
                            <CheckCircle2
                                className="mt-0.5 h-5 w-5 shrink-0 text-slate-500"
                                aria-hidden="true"
                            />

                            <div>
                                <p className="font-semibold text-slate-900">
                                    New shipment unavailable
                                </p>

                                <p className="mt-1 text-sm leading-6 text-slate-500">
                                    {unavailableMessage}
                                </p>
                            </div>
                        </div>
                    </div>
                ) : (
                    <div className="space-y-6">
                        <div className="grid gap-4 md:grid-cols-2">
                            <div className="md:col-span-2">
                                <label
                                    htmlFor="shipment-shipping-method"
                                    className="text-sm font-semibold text-slate-800"
                                >
                                    Checkout delivery service
                                </label>

                                {shipping.selected_method.id !== null ? (
                                    <div className="mt-2 rounded-xl border border-slate-200 bg-slate-50 px-4 py-3">
                                        <p className="font-semibold text-slate-900">
                                            {shipping.selected_method.name ??
                                                shipping.method_options[0]?.name ??
                                                'Selected shipping method'}
                                        </p>

                                        {(shipping.selected_method.code ??
                                            shipping.method_options[0]?.code) && (
                                            <p className="mt-1 text-xs font-medium uppercase tracking-wide text-slate-500">
                                                {shipping.selected_method.code ??
                                                    shipping.method_options[0]?.code}
                                            </p>
                                        )}

                                        <p className="mt-2 text-xs leading-5 text-slate-500">
                                            This delivery service and its price were selected at checkout.
                                            The physical courier is selected separately below.
                                        </p>
                                    </div>
                                ) : (
                                    <select
                                        id="shipment-shipping-method"
                                        value={form.data.shipping_method_id}
                                        onChange={(event) =>
                                            form.setData(
                                                'shipping_method_id',
                                                event.target.value,
                                            )
                                        }
                                        className={[
                                            'mt-2 block min-h-11 w-full rounded-xl border bg-white px-3.5 py-2.5 text-sm text-slate-900 shadow-sm outline-none transition',
                                            formErrors.shipping_method_id
                                                ? 'border-red-300 focus:border-red-400 focus:ring-4 focus:ring-red-100'
                                                : 'border-slate-300 focus:border-slate-400 focus:ring-4 focus:ring-slate-100',
                                        ].join(' ')}
                                    >
                                        <option value="">Select checkout delivery service</option>

                                        {shipping.method_options.map((method) => (
                                            <option key={method.id} value={method.id}>
                                                {method.name} ({method.code})
                                            </option>
                                        ))}
                                    </select>
                                )}

                                {formErrors.shipping_method_id && (
                                    <p className="mt-2 text-sm font-medium text-red-600">
                                        {formErrors.shipping_method_id}
                                    </p>
                                )}
                            </div>

                            <div className="md:col-span-2">
                                <label
                                    htmlFor="shipment-provider"
                                    className="text-sm font-semibold text-slate-800"
                                >
                                    Courier provider
                                </label>

                                <select
                                    id="shipment-provider"
                                    value={form.data.provider_code}
                                    onChange={(event) => {
                                        const providerCode = event.target.value

                                        form.setData('provider_code', providerCode)

                                        if (providerCode !== 'manual') {
                                            form.setData('carrier', '')
                                            form.setData('service', '')
                                        }
                                    }}
                                    className={[
                                        'mt-2 block min-h-11 w-full rounded-xl border bg-white px-3.5 py-2.5 text-sm text-slate-900 shadow-sm outline-none transition',
                                        formErrors.provider_code
                                            ? 'border-red-300 focus:border-red-400 focus:ring-4 focus:ring-red-100'
                                            : 'border-slate-300 focus:border-slate-400 focus:ring-4 focus:ring-slate-100',
                                    ].join(' ')}
                                >
                                    {shipping.provider_options.map((provider) => (
                                        <option
                                            key={provider.code}
                                            value={provider.code}
                                            disabled={!provider.selectable}
                                        >
                                            {provider.label}
                                            {provider.account_name
                                                ? ` — ${provider.account_name}`
                                                : ''}
                                            {!provider.selectable
                                                ? ' — Not connected'
                                                : provider.is_default
                                                  ? ' — Default'
                                                  : ''}
                                        </option>
                                    ))}
                                </select>

                                <p className="mt-2 text-xs leading-5 text-slate-500">
                                    This controls the physical courier for this parcel. It does not
                                    change the delivery charge paid by the customer.
                                </p>

                                {formErrors.provider_code && (
                                    <p className="mt-2 text-sm font-medium text-red-600">
                                        {formErrors.provider_code}
                                    </p>
                                )}
                            </div>

                            {!manualProvider && (
                                <div className="md:col-span-2 rounded-xl border border-blue-200 bg-blue-50 p-4">
                                    <p className="text-sm font-semibold text-blue-900">
                                        Tracking will be managed by the connected courier
                                    </p>

                                    <p className="mt-1 text-sm leading-6 text-blue-800">
                                        Carrier, service and tracking data will be populated from
                                        the courier integration after submission.
                                    </p>
                                </div>
                            )}

                            {manualProvider && (
                                <>
                                    <div>
                                        <label
                                            htmlFor="shipment-carrier"
                                    className="text-sm font-semibold text-slate-800"
                                >
                                    Carrier
                                </label>

                                <input
                                    id="shipment-carrier"
                                    type="text"
                                    maxLength={120}
                                    value={form.data.carrier}
                                    onChange={(event) =>
                                        form.setData('carrier', event.target.value)
                                    }
                                    placeholder="Example: Pathao"
                                    className={[
                                        'mt-2 block min-h-11 w-full rounded-xl border bg-white px-3.5 py-2.5 text-sm text-slate-900 shadow-sm outline-none transition placeholder:text-slate-400',
                                        formErrors.carrier
                                            ? 'border-red-300 focus:border-red-400 focus:ring-4 focus:ring-red-100'
                                            : 'border-slate-300 focus:border-slate-400 focus:ring-4 focus:ring-slate-100',
                                    ].join(' ')}
                                />

                                {formErrors.carrier && (
                                    <p className="mt-2 text-sm font-medium text-red-600">
                                        {formErrors.carrier}
                                    </p>
                                )}
                            </div>

                            <div>
                                <label
                                    htmlFor="shipment-service"
                                    className="text-sm font-semibold text-slate-800"
                                >
                                    Service
                                </label>

                                <input
                                    id="shipment-service"
                                    type="text"
                                    maxLength={120}
                                    value={form.data.service}
                                    onChange={(event) =>
                                        form.setData('service', event.target.value)
                                    }
                                    placeholder="Example: Express"
                                    className={[
                                        'mt-2 block min-h-11 w-full rounded-xl border bg-white px-3.5 py-2.5 text-sm text-slate-900 shadow-sm outline-none transition placeholder:text-slate-400',
                                        formErrors.service
                                            ? 'border-red-300 focus:border-red-400 focus:ring-4 focus:ring-red-100'
                                            : 'border-slate-300 focus:border-slate-400 focus:ring-4 focus:ring-slate-100',
                                    ].join(' ')}
                                />

                                {formErrors.service && (
                                    <p className="mt-2 text-sm font-medium text-red-600">
                                        {formErrors.service}
                                    </p>
                                )}
                            </div>
                                </>
                            )}
                        </div>

                        <div>
                            <div className="flex flex-col gap-1 sm:flex-row sm:items-end sm:justify-between">
                                <div>
                                    <h3 className="text-sm font-semibold text-slate-900">
                                        Allocate order quantities
                                    </h3>

                                    <p className="mt-1 text-xs leading-5 text-slate-500">
                                        Enter a quantity only for lines included in this shipment.
                                    </p>
                                </div>

                                <p className="text-xs font-semibold tabular-nums text-slate-500">
                                    Selected: {selectedQuantity}
                                </p>
                            </div>

                            {allocationErrors.length > 0 && (
                                <div
                                    role="alert"
                                    className="mt-4 rounded-xl border border-red-200 bg-red-50 px-4 py-3"
                                >
                                    <p className="text-sm font-semibold text-red-800">
                                        Shipment allocation could not be saved.
                                    </p>

                                    <ul className="mt-2 space-y-1 text-sm text-red-700">
                                        {allocationErrors.map((message) => (
                                            <li key={message}>{message}</li>
                                        ))}
                                    </ul>
                                </div>
                            )}

                            <div className="mt-4 space-y-3">
                                {shipping.items.map((item) => {
                                    const formItem = form.data.items.find(
                                        (candidate) =>
                                            candidate.order_item_id === item.order_item_id,
                                    )

                                    const quantity = formItem?.quantity ?? 0
                                    const fullyAllocated = item.remaining_quantity === 0

                                    return (
                                        <div
                                            key={item.order_item_id}
                                            className={[
                                                'rounded-xl border p-4',
                                                fullyAllocated
                                                    ? 'border-slate-200 bg-slate-50'
                                                    : 'border-slate-200 bg-white',
                                            ].join(' ')}
                                        >
                                            <div className="flex flex-col gap-4 sm:flex-row sm:items-start sm:justify-between">
                                                <div className="min-w-0 flex-1">
                                                    <p className="font-semibold text-slate-900">
                                                        {item.product_name}
                                                    </p>

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

                                                    <div className="mt-3 flex flex-wrap gap-2">
                                                        <QuantityPill
                                                            label="Ordered"
                                                            value={item.ordered_quantity}
                                                        />

                                                        <QuantityPill
                                                            label="Allocated"
                                                            value={
                                                                item.active_allocated_quantity
                                                            }
                                                        />

                                                        <QuantityPill
                                                            label="Remaining"
                                                            value={item.remaining_quantity}
                                                            strong
                                                        />
                                                    </div>
                                                </div>

                                                <div className="w-full sm:w-32">
                                                    <label
                                                        htmlFor={`shipment-quantity-${item.order_item_id}`}
                                                        className="text-xs font-semibold uppercase tracking-wide text-slate-500"
                                                    >
                                                        Ship now
                                                    </label>

                                                    <input
                                                        id={`shipment-quantity-${item.order_item_id}`}
                                                        type="number"
                                                        inputMode="numeric"
                                                        min={0}
                                                        max={item.remaining_quantity}
                                                        step={1}
                                                        disabled={fullyAllocated}
                                                        value={quantity === 0 ? '' : quantity}
                                                        onChange={(event) =>
                                                            setAllocationQuantity(
                                                                item.order_item_id,
                                                                Number(event.target.value),
                                                            )
                                                        }
                                                        placeholder="0"
                                                        className="mt-2 block min-h-11 w-full rounded-xl border border-slate-300 bg-white px-3 py-2 text-right text-sm font-semibold tabular-nums text-slate-900 shadow-sm outline-none transition placeholder:text-slate-400 focus:border-slate-400 focus:ring-4 focus:ring-slate-100 disabled:cursor-not-allowed disabled:bg-slate-100 disabled:text-slate-400"
                                                    />

                                                    <p className="mt-1 text-right text-[11px] text-slate-400">
                                                        Max {item.remaining_quantity}
                                                    </p>
                                                </div>
                                            </div>
                                        </div>
                                    )
                                })}
                            </div>
                        </div>

                        <div className="flex flex-col gap-3 border-t border-slate-200 pt-5 sm:flex-row sm:items-center sm:justify-between">
                            <p className="text-xs leading-5 text-slate-500">
                                Creating a shipment allocates quantity only. It does not mark the
                                shipment or order as shipped.
                            </p>

                            <button
                                type="button"
                                disabled={
                                    form.processing ||
                                    selectedQuantity <= 0 ||
                                    form.data.shipping_method_id === '' ||
                                    form.data.provider_code === ''
                                }
                                onClick={submit}
                                className="inline-flex min-h-11 shrink-0 items-center justify-center gap-2 rounded-xl bg-slate-950 px-5 py-2.5 text-sm font-semibold text-white shadow-sm transition hover:bg-slate-800 focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-slate-950 focus-visible:ring-offset-2 disabled:cursor-not-allowed disabled:opacity-50"
                            >
                                {form.processing ? (
                                    <LoaderCircle
                                        className="h-4 w-4 animate-spin"
                                        aria-hidden="true"
                                    />
                                ) : (
                                    <Package className="h-4 w-4" aria-hidden="true" />
                                )}

                                {form.processing ? 'Creating…' : 'Create shipment'}
                            </button>
                        </div>
                    </div>
                )}
            </div>
        </section>
    )
}

function ShipmentStat({ label, value }: { label: string; value: number }) {
    return (
        <div className="rounded-xl border border-slate-200 bg-slate-50 px-3 py-3">
            <p className="text-[11px] font-semibold uppercase tracking-wide text-slate-500">
                {label}
            </p>

            <p className="mt-1 text-lg font-bold tabular-nums text-slate-950">{value}</p>
        </div>
    )
}

function QuantityPill({
    label,
    value,
    strong = false,
}: {
    label: string
    value: number
    strong?: boolean
}) {
    return (
        <span
            className={[
                'inline-flex items-center gap-1 rounded-full border px-2.5 py-1 text-xs',
                strong
                    ? 'border-slate-300 bg-slate-100 font-semibold text-slate-800'
                    : 'border-slate-200 bg-white text-slate-600',
            ].join(' ')}
        >
            <span>{label}</span>
            <span className="font-semibold tabular-nums">{value}</span>
        </span>
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

            case 'due':
                return 'border-orange-200 bg-orange-50 text-orange-700'

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
