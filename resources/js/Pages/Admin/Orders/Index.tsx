import ConfirmDialog from '@/Components/Admin/ConfirmDialog'
import { notify } from '@/lib/toast'
import AdminLayout from '@/Layouts/Admin/AdminLayout'
import { formatMoney } from '@/lib/money'
import { Head, Link, router } from '@inertiajs/react'
import { useState } from 'react'
import { ChevronLeft, ChevronRight, Eye, PackageSearch, RotateCcw, Trash2 } from 'lucide-react'

interface StatusValue {
    value: string
    label: string
}

interface CustomerSummary {
    name: string | null
    email: string | null
}

interface AdminOrderListItem {
    id: number
    order_number: string
    customer: CustomerSummary
    status: StatusValue
    payment_method: StatusValue
    payment_status: StatusValue
    fulfilment_status: StatusValue
    currency_code: string
    grand_total: number
    placed_at: string | null
    can_trash: boolean
    can_purge: boolean
}

interface PaginationLink {
    url: string | null
    label: string
    active: boolean
}

interface OrdersPaginator {
    current_page: number
    data: AdminOrderListItem[]
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

interface Props {
    orders: OrdersPaginator
    tabs: { value: string; label: string; count: number }[]
    tab: string
    can_manage_trash: boolean
}

type OrderAction = { order: AdminOrderListItem; type: 'trash' | 'purge' | 'restore' }

export default function Index({ orders, tabs, tab, can_manage_trash }: Props) {
    const [action, setAction] = useState<OrderAction | null>(null)
    const [processing, setProcessing] = useState(false)
    const inTrash = tab === 'trash'

    function submitAction() {
        if (!action || processing) return
        setProcessing(true)
        const options = {
            preserveScroll: true,
            onSuccess: () => setAction(null),
            onError: (errors: Record<string, string>) => { notify.error(errors.order_trash || 'The order action could not be completed.') },
            onFinish: () => setProcessing(false),
        }
        if (action.type === 'restore') {
            router.post(route('admin.orders.restore', action.order.id), {}, options)
        } else {
            router.delete(route(action.type === 'purge' ? 'admin.orders.purge' : 'admin.orders.destroy', action.order.id), options)
        }
    }

    return (
        <AdminLayout title="Orders" description="Manage Orders.">
            <Head title="Orders" />

            <div className="mx-auto max-w-7xl space-y-6">
                <div className="overflow-hidden rounded-xl border border-slate-200 bg-white shadow-sm">
                    <div className="border-b border-slate-200 px-5 py-4">
                        <div className="flex flex-col gap-2 sm:flex-row sm:items-center sm:justify-between">
                            <div>
                                <h2 className="font-semibold text-slate-900">Customer orders</h2>

                                <p className="mt-1 text-sm text-slate-500">
                                    {orders.total} {orders.total === 1 ? 'order' : 'orders'}
                                </p>
                            </div>
                        </div>
                        <nav aria-label="Order status" className="mt-4 flex flex-wrap gap-2">
                            {tabs.map((item) => (
                                <Link
                                    key={item.value}
                                    href={route('admin.orders.index', { tab: item.value })}
                                    aria-current={tab === item.value ? 'page' : undefined}
                                    className={`inline-flex items-center gap-2 rounded-lg border px-3 py-2 text-sm font-medium transition ${tab === item.value ? 'border-slate-900 bg-slate-900 text-white' : 'border-slate-200 text-slate-600 hover:bg-slate-50'}`}
                                >
                                    {item.value === 'trash' && <Trash2 className="h-4 w-4" aria-hidden="true" />}
                                    {item.label}
                                    <span className={`rounded-full px-2 py-0.5 text-xs tabular-nums ${tab === item.value ? 'bg-white/20' : 'bg-slate-100 text-slate-600'}`}>
                                        {item.count}
                                    </span>
                                </Link>
                            ))}
                        </nav>
                        {inTrash && (
                            <p className="mt-3 rounded-lg bg-amber-50 p-3 text-sm text-amber-900">
                                Restore orders to undo deletion. Permanent deletion is restricted to cancelled, unpaid, unfulfilled orders with no protected records. Archived orders remain in customer history and reports until permanently deleted.
                            </p>
                        )}
                    </div>

                    {orders.data.length === 0 ? (
                        <div className="flex flex-col items-center justify-center px-6 py-16 text-center">
                            <div className="flex h-12 w-12 items-center justify-center rounded-full bg-slate-100 text-slate-500">
                                <PackageSearch className="h-6 w-6" aria-hidden="true" />
                            </div>

                            <h3 className="mt-4 font-semibold text-slate-900">{inTrash ? 'Trash is empty' : 'No orders in this view'}</h3>

                            <p className="mt-1 max-w-md text-sm text-slate-500">
                                {inTrash ? 'Orders moved to Trash will appear here.' : 'There are no orders matching this status.'}
                            </p>
                        </div>
                    ) : (
                        <>
                            <div className="overflow-x-auto">
                                <table className="min-w-full divide-y divide-slate-200 text-sm">
                                    <thead className="bg-slate-50">
                                        <tr className="text-left text-xs font-semibold uppercase tracking-wide text-slate-500">
                                            <th className="px-5 py-3">Order</th>

                                            <th className="px-5 py-3">Customer</th>

                                            <th className="px-5 py-3">Order status</th>

                                            <th className="px-5 py-3">Payment</th>

                                            <th className="px-5 py-3">Payment status</th>

                                            <th className="px-5 py-3">Fulfilment</th>

                                            <th className="px-5 py-3 text-right">Total</th>

                                            <th className="px-5 py-3">Placed</th>

                                            <th className="px-5 py-3 text-right">Action</th>
                                        </tr>
                                    </thead>

                                    <tbody className="divide-y divide-slate-100 bg-white">
                                        {orders.data.map((order) => (
                                            <tr
                                                key={order.id}
                                                className="align-top transition hover:bg-slate-50"
                                            >
                                                <td className="whitespace-nowrap px-5 py-4">
                                                    <Link
                                                        href={route('admin.orders.show', order.id)}
                                                        className="font-semibold text-slate-900 hover:underline"
                                                    >
                                                        {order.order_number}
                                                    </Link>
                                                </td>

                                                <td className="px-5 py-4">
                                                    <div className="min-w-40">
                                                        <p className="font-medium text-slate-900">
                                                            {order.customer.name ??
                                                                'Unknown customer'}
                                                        </p>

                                                        {order.customer.email && (
                                                            <p className="mt-1 text-xs text-slate-500">
                                                                {order.customer.email}
                                                            </p>
                                                        )}
                                                    </div>
                                                </td>

                                                <td className="whitespace-nowrap px-5 py-4">
                                                    <StatusBadge
                                                        value={order.status.value}
                                                        label={order.status.label}
                                                        type="order"
                                                    />
                                                </td>

                                                <td className="whitespace-nowrap px-5 py-4">
                                                    <span className="font-medium text-slate-800">
                                                        {order.payment_method.label}
                                                    </span>
                                                </td>

                                                <td className="whitespace-nowrap px-5 py-4">
                                                    <StatusBadge
                                                        value={order.payment_status.value}
                                                        label={order.payment_status.label}
                                                        type="payment"
                                                    />
                                                </td>

                                                <td className="whitespace-nowrap px-5 py-4">
                                                    <StatusBadge
                                                        value={order.fulfilment_status.value}
                                                        label={order.fulfilment_status.label}
                                                        type="fulfilment"
                                                    />
                                                </td>

                                                <td className="whitespace-nowrap px-5 py-4 text-right font-semibold text-slate-900">
                                                    {formatMoney(
                                                        order.grand_total,
                                                        order.currency_code,
                                                    )}
                                                </td>

                                                <td className="whitespace-nowrap px-5 py-4 text-slate-600">
                                                    {formatDate(order.placed_at)}
                                                </td>

                                                <td className="whitespace-nowrap px-5 py-4 text-right">
                                                    <div className="flex flex-wrap justify-end gap-2">
                                                    <Link
                                                        href={route('admin.orders.show', order.id)}
                                                        className="inline-flex h-9 items-center justify-center gap-2 rounded-lg border border-slate-200 bg-white px-3 font-medium text-slate-700 transition hover:bg-slate-50"
                                                    >
                                                        <Eye
                                                            className="h-4 w-4"
                                                            aria-hidden="true"
                                                        />
                                                        View
                                                    </Link>
                                                    {can_manage_trash && (inTrash ? (
                                                        <>
                                                            <button type="button" disabled={processing} onClick={() => setAction({ order, type: 'restore' })} className="inline-flex h-9 items-center gap-1 rounded-lg border border-emerald-200 px-3 font-medium text-emerald-700 hover:bg-emerald-50 disabled:opacity-50">
                                                                <RotateCcw className="h-4 w-4" aria-hidden="true" /> Restore / Undo
                                                            </button>
                                                            <button type="button" disabled={processing || !order.can_purge} title={order.can_purge ? 'Permanently delete this order' : 'Protected records or order state prevent permanent deletion'} onClick={() => setAction({ order, type: 'purge' })} className="inline-flex h-9 items-center gap-1 rounded-lg border border-red-200 px-3 font-medium text-red-700 hover:bg-red-50 disabled:cursor-not-allowed disabled:opacity-50">
                                                                <Trash2 className="h-4 w-4" aria-hidden="true" /> Delete permanently
                                                            </button>
                                                        </>
                                                    ) : (
                                                        <button type="button" disabled={processing || !order.can_trash} title={order.can_trash ? 'Move to Trash' : 'Cancel or complete this order before moving it to Trash'} onClick={() => setAction({ order, type: 'trash' })} className="inline-flex h-9 items-center gap-1 rounded-lg border border-red-200 px-3 font-medium text-red-700 hover:bg-red-50 disabled:cursor-not-allowed disabled:opacity-50">
                                                            <Trash2 className="h-4 w-4" aria-hidden="true" /> Delete
                                                        </button>
                                                    ))}
                                                    </div>
                                                </td>
                                            </tr>
                                        ))}
                                    </tbody>
                                </table>
                            </div>

                            <Pagination orders={orders} />
                        </>
                    )}
                </div>
            </div>
            <ConfirmDialog
                open={action !== null}
                title={action?.type === 'purge' ? 'Permanently delete order?' : action?.type === 'restore' ? 'Restore order?' : 'Move order to Trash?'}
                description={action ? action.type === 'purge'
                    ? `${action.order.order_number} and its eligible order details will be permanently removed. This cannot be undone.`
                    : action.type === 'restore'
                        ? `${action.order.order_number} will return to the normal orders list.`
                        : `${action.order.order_number} will be moved to Trash. You can restore it later. Inventory and payment states will not change.` : ''}
                confirmLabel={action?.type === 'purge' ? 'Delete permanently' : action?.type === 'restore' ? 'Restore' : 'Move to Trash'}
                processing={processing}
                onConfirm={submitAction}
                onCancel={() => { if (!processing) setAction(null) }}
            />
        </AdminLayout>
    )
}

function Pagination({ orders }: { orders: OrdersPaginator }) {
    if (orders.last_page <= 1) {
        return null
    }

    return (
        <div className="flex flex-col gap-3 border-t border-slate-200 px-5 py-4 sm:flex-row sm:items-center sm:justify-between">
            <p className="text-sm text-slate-500">
                Showing {orders.from ?? 0}–{orders.to ?? 0} of {orders.total}
            </p>

            <div className="flex items-center gap-2">
                {orders.prev_page_url ? (
                    <Link
                        href={orders.prev_page_url}
                        preserveScroll
                        className="inline-flex h-9 items-center gap-1 rounded-lg border border-slate-200 px-3 text-sm font-medium text-slate-700 hover:bg-slate-50"
                    >
                        <ChevronLeft className="h-4 w-4" />
                        Previous
                    </Link>
                ) : (
                    <span className="inline-flex h-9 cursor-not-allowed items-center gap-1 rounded-lg border border-slate-100 px-3 text-sm font-medium text-slate-300">
                        <ChevronLeft className="h-4 w-4" />
                        Previous
                    </span>
                )}

                {orders.next_page_url ? (
                    <Link
                        href={orders.next_page_url}
                        preserveScroll
                        className="inline-flex h-9 items-center gap-1 rounded-lg border border-slate-200 px-3 text-sm font-medium text-slate-700 hover:bg-slate-50"
                    >
                        Next
                        <ChevronRight className="h-4 w-4" />
                    </Link>
                ) : (
                    <span className="inline-flex h-9 cursor-not-allowed items-center gap-1 rounded-lg border border-slate-100 px-3 text-sm font-medium text-slate-300">
                        Next
                        <ChevronRight className="h-4 w-4" />
                    </span>
                )}
            </div>
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
