import { Head, Link, useForm } from '@inertiajs/react'
import {
    ArrowLeft,
    BadgeCheck,
    Ban,
    CalendarDays,
    ChevronRight,
    CircleOff,
    Clock3,
    History,
    Mail,
    MapPin,
    Package,
    ReceiptText,
    ShieldCheck,
    Trash2,
    UserRound,
} from 'lucide-react'
import type { FormEvent } from 'react'

import AdminLayout from '@/Layouts/Admin/AdminLayout'
import { formatMoney } from '@/lib/money'

type StatusValue = {
    value: string
    label: string
}

type Customer = {
    id: number
    name: string
    email: string
    status: string
    status_label: string
    verified: boolean
    email_verified_at: string | null
    orders_count: number
    addresses_count: number
    created_at: string | null
    anonymized: boolean
    anonymized_at: string | null
}

type Address = {
    id: number
    label: string | null
    recipient_name: string
    phone: string
    address_line_1: string
    address_line_2: string | null
    city: string
    state_or_region: string | null
    postal_code: string | null
    country_code: string
    is_default: boolean
}

type Order = {
    id: number
    order_number: string
    status: StatusValue
    payment_status: StatusValue
    fulfilment_status: StatusValue
    currency_code: string
    grand_total: number
    placed_at: string | null
}

type CustomerStatusHistory = {
    id: number
    from_status: string
    from_status_label: string
    to_status: string
    to_status_label: string
    reason: string | null
    actor_name: string | null
    changed_at: string | null
}

type PaginationLink = {
    url: string | null
    label: string
    active: boolean
}

type PaginatedOrders = {
    data: Order[]
    links: PaginationLink[]
    current_page: number
    last_page: number
    from: number | null
    to: number | null
    total: number
}

type Props = {
    customer: Customer
    addresses: Address[]
    orders: PaginatedOrders
    statusHistory: CustomerStatusHistory[]
}

export default function Show({ customer, addresses, orders, statusHistory }: Props) {
    const statusForm = useForm<{
        reason: string
    }>({
        reason: '',
    })

    const removalForm = useForm<{
        reason: string
    }>({
        reason: '',
    })

    const statusErrors = statusForm.errors as Record<string, string | undefined>
    const removalErrors = removalForm.errors as Record<string, string | undefined>

    const isActive = customer.status === 'active'
    const isBlocked = customer.status === 'suspended'

    const submitStatusChange = (event: FormEvent<HTMLFormElement>) => {
        event.preventDefault()

        if (customer.anonymized) {
            return
        }

        const routeName = isActive ? 'admin.customers.block' : 'admin.customers.unblock'

        statusForm.patch(route(routeName, customer.id), {
            preserveScroll: true,
            onSuccess: () => {
                statusForm.reset()
            },
        })
    }

    const submitRemoval = (event: FormEvent<HTMLFormElement>) => {
        event.preventDefault()

        if (customer.anonymized) {
            return
        }

        const message =
            customer.orders_count > 0
                ? 'This account has transactional history and will be anonymized. Continue?'
                : 'The server will permanently delete this account only when no history exists; otherwise it will anonymize it. Continue?'

        if (!window.confirm(message)) {
            return
        }

        removalForm.delete(route('admin.customers.destroy', customer.id), {
            preserveScroll: true,
        })
    }

    return (
        <AdminLayout
            title="Customer details"
            description="Identity, account access, addresses, orders, and administrative history."
            actions={
                <div className="flex flex-wrap items-center gap-2">
                    {!customer.anonymized && (
                        <Link
                            href={route('admin.customers.edit', customer.id)}
                            className="inline-flex min-h-10 items-center justify-center rounded-xl bg-neutral-900 px-4 text-sm font-semibold text-white shadow-sm transition hover:bg-neutral-700 dark:bg-neutral-100 dark:text-neutral-900 dark:hover:bg-neutral-300"
                        >
                            Edit customer
                        </Link>
                    )}

                    <Link
                        href={route('admin.customers.index')}
                        className="inline-flex min-h-10 items-center gap-2 rounded-xl border border-neutral-300 bg-white px-4 text-sm font-semibold text-neutral-700 shadow-sm transition hover:bg-neutral-50 dark:border-neutral-700 dark:bg-neutral-950 dark:text-neutral-200 dark:hover:bg-neutral-900"
                    >
                        <ArrowLeft className="h-4 w-4" aria-hidden="true" />
                        Customers
                    </Link>
                </div>
            }
        >
            <Head title={`Customer - ${customer.name}`} />

            <div className="space-y-6">
                <CustomerHero customer={customer} />

                {customer.anonymized && (
                    <section className="overflow-hidden rounded-2xl border border-amber-200 bg-amber-50 shadow-sm dark:border-amber-500/20 dark:bg-amber-500/10">
                        <div className="flex items-start gap-4 p-5 sm:p-6">
                            <span className="flex h-11 w-11 shrink-0 items-center justify-center rounded-xl bg-amber-100 text-amber-700 dark:bg-amber-500/15 dark:text-amber-300">
                                <CircleOff className="h-5 w-5" aria-hidden="true" />
                            </span>

                            <div>
                                <h2 className="font-semibold text-amber-950 dark:text-amber-100">
                                    This account has been anonymized
                                </h2>

                                <p className="mt-1 max-w-3xl text-sm leading-6 text-amber-900/75 dark:text-amber-100/70">
                                    Personal account credentials and live address data have been
                                    removed. The account cannot be edited, blocked, unblocked, or
                                    reactivated. Historical orders, invoices, and audit records
                                    remain available.
                                </p>

                                {customer.anonymized_at && (
                                    <div className="mt-3 inline-flex items-center gap-2 rounded-lg bg-white/70 px-3 py-1.5 text-xs font-semibold text-amber-800 dark:bg-black/10 dark:text-amber-200">
                                        <Clock3 className="h-3.5 w-3.5" aria-hidden="true" />
                                        Anonymized {formatDateTime(customer.anonymized_at)}
                                    </div>
                                )}
                            </div>
                        </div>
                    </section>
                )}

                <section className="grid gap-4 sm:grid-cols-2 xl:grid-cols-4">
                    <SummaryCard
                        icon={UserRound}
                        label="Account status"
                        value={customer.status_label}
                        helper={
                            customer.anonymized ? 'Identity removed' : `Customer #${customer.id}`
                        }
                    />

                    <SummaryCard
                        icon={BadgeCheck}
                        label="Email verification"
                        value={customer.verified ? 'Verified' : 'Unverified'}
                        helper={
                            customer.verified
                                ? formatDateTime(customer.email_verified_at)
                                : 'Verification pending'
                        }
                    />

                    <SummaryCard
                        icon={Package}
                        label="Orders"
                        value={customer.orders_count}
                        helper="Historical orders"
                    />

                    <SummaryCard
                        icon={MapPin}
                        label="Saved addresses"
                        value={customer.addresses_count}
                        helper="Account address book"
                    />
                </section>

                <div className="grid gap-6 xl:grid-cols-[minmax(0,0.85fr)_minmax(0,1.35fr)]">
                    <div className="space-y-6">
                        <section className="overflow-hidden rounded-2xl border border-neutral-200 bg-white shadow-sm dark:border-neutral-800 dark:bg-neutral-950">
                            <SectionHeader
                                icon={UserRound}
                                title="Customer profile"
                                description="Current account identity and registration information."
                            />

                            <dl className="divide-y divide-neutral-100 px-5 pb-2 dark:divide-neutral-800 sm:px-6">
                                <DetailRow label="Customer ID" value={`#${customer.id}`} />
                                <DetailRow label="Name" value={customer.name} />
                                <DetailRow label="Email" value={customer.email} />
                                <DetailRow label="Status" value={customer.status_label} />
                                <DetailRow
                                    label="Email verified"
                                    value={
                                        customer.verified
                                            ? formatDateTime(customer.email_verified_at)
                                            : 'Not verified'
                                    }
                                />
                                <DetailRow
                                    label="Registered"
                                    value={formatDateTime(customer.created_at)}
                                />

                                {customer.anonymized && (
                                    <DetailRow
                                        label="Anonymized"
                                        value={formatDateTime(customer.anonymized_at)}
                                    />
                                )}
                            </dl>
                        </section>

                        {!customer.anonymized && (
                            <section className="overflow-hidden rounded-2xl border border-neutral-200 bg-white shadow-sm dark:border-neutral-800 dark:bg-neutral-950">
                                <SectionHeader
                                    icon={isActive ? Ban : ShieldCheck}
                                    title="Account access"
                                    description={
                                        isActive
                                            ? 'Suspend customer access without deleting their account or history.'
                                            : 'Restore access to a previously blocked customer account.'
                                    }
                                    tone={isActive ? 'danger' : 'success'}
                                />

                                {(isActive || isBlocked) && (
                                    <form
                                        onSubmit={submitStatusChange}
                                        className="space-y-4 px-5 pb-5 sm:px-6 sm:pb-6"
                                    >
                                        <div>
                                            <label
                                                htmlFor="status-reason"
                                                className="mb-2 block text-sm font-semibold text-neutral-700 dark:text-neutral-200"
                                            >
                                                {isActive ? 'Reason for blocking' : 'Unblock note'}
                                            </label>

                                            <textarea
                                                id="status-reason"
                                                value={statusForm.data.reason}
                                                onChange={(event) =>
                                                    statusForm.setData('reason', event.target.value)
                                                }
                                                required={isActive}
                                                minLength={isActive ? 3 : undefined}
                                                maxLength={500}
                                                rows={4}
                                                placeholder={
                                                    isActive
                                                        ? 'Explain why this customer is being blocked...'
                                                        : 'Optional note explaining why access is being restored...'
                                                }
                                                className={textareaClass}
                                            />

                                            {statusErrors.reason && (
                                                <FormError message={statusErrors.reason} />
                                            )}

                                            {statusErrors.status && (
                                                <FormError message={statusErrors.status} />
                                            )}
                                        </div>

                                        <div className="flex flex-wrap items-center justify-between gap-3 rounded-xl bg-neutral-50 p-3 dark:bg-neutral-900/60">
                                            <p className="max-w-md text-xs leading-5 text-neutral-500">
                                                {isActive
                                                    ? 'Blocking takes effect immediately for protected customer areas and checkout.'
                                                    : 'Unblocking restores normal access. Orders and audit history are never changed.'}
                                            </p>

                                            <button
                                                type="submit"
                                                disabled={
                                                    statusForm.processing ||
                                                    (isActive &&
                                                        statusForm.data.reason.trim().length < 3)
                                                }
                                                className={[
                                                    'inline-flex min-h-10 items-center justify-center gap-2 rounded-lg px-4 text-sm font-semibold text-white shadow-sm transition disabled:cursor-not-allowed disabled:opacity-60',
                                                    isActive
                                                        ? 'bg-red-600 hover:bg-red-700'
                                                        : 'bg-emerald-600 hover:bg-emerald-700',
                                                ].join(' ')}
                                            >
                                                {isActive ? (
                                                    <Ban className="h-4 w-4" aria-hidden="true" />
                                                ) : (
                                                    <ShieldCheck
                                                        className="h-4 w-4"
                                                        aria-hidden="true"
                                                    />
                                                )}

                                                {statusForm.processing
                                                    ? 'Saving...'
                                                    : isActive
                                                      ? 'Block customer'
                                                      : 'Unblock customer'}
                                            </button>
                                        </div>
                                    </form>
                                )}
                            </section>
                        )}

                        <section className="overflow-hidden rounded-2xl border border-neutral-200 bg-white shadow-sm dark:border-neutral-800 dark:bg-neutral-950">
                            <SectionHeader
                                icon={MapPin}
                                title="Saved addresses"
                                description="Addresses currently stored on this customer account."
                            />

                            {addresses.length === 0 ? (
                                <EmptyState
                                    icon={MapPin}
                                    title="No saved addresses"
                                    description="This customer has not saved an address yet."
                                />
                            ) : (
                                <div className="space-y-3 px-5 pb-5 sm:px-6 sm:pb-6">
                                    {addresses.map((address) => (
                                        <AddressCard key={address.id} address={address} />
                                    ))}
                                </div>
                            )}
                        </section>

                        <section className="overflow-hidden rounded-2xl border border-red-200 bg-white shadow-sm dark:border-red-500/20 dark:bg-neutral-950">
                            <div className="border-b border-red-100 bg-red-50/70 px-5 py-4 sm:px-6 dark:border-red-500/10 dark:bg-red-500/5">
                                <div className="flex items-start gap-3">
                                    <span className="flex h-10 w-10 shrink-0 items-center justify-center rounded-xl bg-red-100 text-red-700 dark:bg-red-500/10 dark:text-red-300">
                                        <Trash2 className="h-5 w-5" aria-hidden="true" />
                                    </span>

                                    <div>
                                        <h2 className="font-semibold text-neutral-950 dark:text-white">
                                            Danger zone
                                        </h2>
                                        <p className="mt-1 text-sm leading-6 text-neutral-500">
                                            Remove live customer identity while preserving required
                                            historical records.
                                        </p>
                                    </div>
                                </div>
                            </div>

                            {customer.anonymized ? (
                                <div className="px-5 py-5 sm:px-6">
                                    <div className="rounded-xl border border-neutral-200 bg-neutral-50 p-4 dark:border-neutral-800 dark:bg-neutral-900/50">
                                        <div className="flex items-start gap-3">
                                            <CircleOff
                                                className="mt-0.5 h-5 w-5 shrink-0 text-neutral-500"
                                                aria-hidden="true"
                                            />
                                            <div>
                                                <p className="text-sm font-semibold text-neutral-800 dark:text-neutral-200">
                                                    Account already anonymized
                                                </p>
                                                <p className="mt-1 text-xs leading-5 text-neutral-500">
                                                    This operation cannot be repeated and the
                                                    account cannot be reactivated.
                                                </p>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            ) : (
                                <form
                                    onSubmit={submitRemoval}
                                    className="space-y-4 px-5 py-5 sm:px-6"
                                >
                                    <div className="rounded-xl border border-red-100 bg-red-50/60 p-4 text-sm leading-6 text-red-800 dark:border-red-500/10 dark:bg-red-500/5 dark:text-red-200">
                                        {customer.orders_count > 0
                                            ? 'This customer has transactional history. The account will be anonymized instead of deleted; historical orders and invoices will remain unchanged.'
                                            : 'The server will check transactional and audit history. A clean account can be deleted; otherwise it will be anonymized automatically.'}
                                    </div>

                                    <div>
                                        <label
                                            htmlFor="removal-reason"
                                            className="mb-2 block text-sm font-semibold text-neutral-700 dark:text-neutral-200"
                                        >
                                            Removal reason
                                        </label>

                                        <textarea
                                            id="removal-reason"
                                            value={removalForm.data.reason}
                                            onChange={(event) =>
                                                removalForm.setData('reason', event.target.value)
                                            }
                                            required
                                            minLength={3}
                                            maxLength={500}
                                            rows={4}
                                            placeholder="Explain why this customer account is being removed..."
                                            className={`${textareaClass} focus:border-red-500 focus:ring-red-100 dark:focus:border-red-500 dark:focus:ring-red-500/10`}
                                        />

                                        {removalErrors.reason && (
                                            <FormError message={removalErrors.reason} />
                                        )}

                                        {removalErrors.customer && (
                                            <FormError message={removalErrors.customer} />
                                        )}
                                    </div>

                                    <div className="flex justify-end">
                                        <button
                                            type="submit"
                                            disabled={
                                                removalForm.processing ||
                                                removalForm.data.reason.trim().length < 3
                                            }
                                            className="inline-flex min-h-10 items-center justify-center gap-2 rounded-lg bg-red-600 px-4 text-sm font-semibold text-white shadow-sm transition hover:bg-red-700 disabled:cursor-not-allowed disabled:opacity-60"
                                        >
                                            <Trash2 className="h-4 w-4" aria-hidden="true" />
                                            {removalForm.processing
                                                ? 'Processing...'
                                                : customer.orders_count > 0
                                                  ? 'Anonymize customer'
                                                  : 'Remove customer'}
                                        </button>
                                    </div>
                                </form>
                            )}
                        </section>
                    </div>

                    <div className="min-w-0 space-y-6">
                        <section className="overflow-hidden rounded-2xl border border-neutral-200 bg-white shadow-sm dark:border-neutral-800 dark:bg-neutral-950">
                            <SectionHeader
                                icon={ReceiptText}
                                title="Order history"
                                description="Most recent customer orders first."
                                badge={`${orders.total} total`}
                            />

                            {orders.data.length === 0 ? (
                                <EmptyState
                                    icon={Package}
                                    title="No orders yet"
                                    description="This customer has not placed an order."
                                />
                            ) : (
                                <>
                                    <div className="overflow-x-auto">
                                        <table className="min-w-[820px] w-full divide-y divide-neutral-200 dark:divide-neutral-800">
                                            <thead className="bg-neutral-50/80 dark:bg-neutral-900/60">
                                                <tr>
                                                    <Heading>Order</Heading>
                                                    <Heading>Status</Heading>
                                                    <Heading>Payment</Heading>
                                                    <Heading>Fulfilment</Heading>
                                                    <Heading>Total</Heading>
                                                    <Heading>Date</Heading>
                                                </tr>
                                            </thead>

                                            <tbody className="divide-y divide-neutral-200 dark:divide-neutral-800">
                                                {orders.data.map((order) => (
                                                    <tr
                                                        key={order.id}
                                                        className="group transition hover:bg-neutral-50/80 dark:hover:bg-neutral-900/40"
                                                    >
                                                        <td className="whitespace-nowrap px-5 py-4">
                                                            <Link
                                                                href={route(
                                                                    'admin.orders.show',
                                                                    order.id,
                                                                )}
                                                                className="inline-flex items-center gap-1.5 text-sm font-semibold text-neutral-950 transition hover:text-neutral-600 dark:text-white dark:hover:text-neutral-300"
                                                            >
                                                                {order.order_number}
                                                                <ChevronRight
                                                                    className="h-3.5 w-3.5 opacity-0 transition group-hover:opacity-100"
                                                                    aria-hidden="true"
                                                                />
                                                            </Link>
                                                        </td>

                                                        <td className="px-4 py-4">
                                                            <StatusBadge
                                                                value={order.status.value}
                                                                label={order.status.label}
                                                            />
                                                        </td>

                                                        <td className="px-4 py-4">
                                                            <StatusBadge
                                                                value={order.payment_status.value}
                                                                label={order.payment_status.label}
                                                            />
                                                        </td>

                                                        <td className="px-4 py-4">
                                                            <StatusBadge
                                                                value={
                                                                    order.fulfilment_status.value
                                                                }
                                                                label={
                                                                    order.fulfilment_status.label
                                                                }
                                                            />
                                                        </td>

                                                        <td className="whitespace-nowrap px-4 py-4 text-sm font-bold text-neutral-800 dark:text-neutral-200">
                                                            {formatMoney(
                                                                order.grand_total,
                                                                order.currency_code,
                                                            )}
                                                        </td>

                                                        <td className="whitespace-nowrap px-4 py-4 text-sm text-neutral-500">
                                                            {formatDate(order.placed_at)}
                                                        </td>
                                                    </tr>
                                                ))}
                                            </tbody>
                                        </table>
                                    </div>

                                    {orders.last_page > 1 && <Pagination orders={orders} />}
                                </>
                            )}
                        </section>

                        <section className="overflow-hidden rounded-2xl border border-neutral-200 bg-white shadow-sm dark:border-neutral-800 dark:bg-neutral-950">
                            <SectionHeader
                                icon={History}
                                title="Account status history"
                                description="Administrative block and unblock audit trail."
                                badge={`${statusHistory.length} shown`}
                            />

                            {statusHistory.length === 0 ? (
                                <EmptyState
                                    icon={History}
                                    title="No status changes"
                                    description="No administrative block or unblock activity has been recorded."
                                />
                            ) : (
                                <div className="divide-y divide-neutral-200 dark:divide-neutral-800">
                                    {statusHistory.map((history) => (
                                        <div key={history.id} className="px-5 py-4 sm:px-6">
                                            <div className="flex gap-3">
                                                <div className="relative flex w-5 shrink-0 justify-center">
                                                    <span className="mt-2 h-2.5 w-2.5 rounded-full bg-neutral-900 ring-4 ring-neutral-100 dark:bg-neutral-100 dark:ring-neutral-900" />
                                                </div>

                                                <div className="min-w-0 flex-1">
                                                    <div className="flex flex-wrap items-center gap-2">
                                                        <StatusBadge
                                                            value={history.from_status}
                                                            label={history.from_status_label}
                                                        />
                                                        <ChevronRight
                                                            className="h-4 w-4 text-neutral-400"
                                                            aria-hidden="true"
                                                        />
                                                        <StatusBadge
                                                            value={history.to_status}
                                                            label={history.to_status_label}
                                                        />
                                                    </div>

                                                    {history.reason && (
                                                        <p className="mt-3 rounded-lg bg-neutral-50 px-3 py-2 text-sm leading-6 text-neutral-600 dark:bg-neutral-900/60 dark:text-neutral-400">
                                                            {history.reason}
                                                        </p>
                                                    )}

                                                    <div className="mt-3 flex flex-wrap items-center gap-x-4 gap-y-2 text-xs text-neutral-500">
                                                        <span className="inline-flex items-center gap-1.5">
                                                            <UserRound
                                                                className="h-3.5 w-3.5"
                                                                aria-hidden="true"
                                                            />
                                                            {history.actor_name ?? 'System'}
                                                        </span>

                                                        <span className="inline-flex items-center gap-1.5">
                                                            <CalendarDays
                                                                className="h-3.5 w-3.5"
                                                                aria-hidden="true"
                                                            />
                                                            {formatDateTime(history.changed_at)}
                                                        </span>
                                                    </div>
                                                </div>
                                            </div>
                                        </div>
                                    ))}
                                </div>
                            )}
                        </section>
                    </div>
                </div>
            </div>
        </AdminLayout>
    )
}

function CustomerHero({ customer }: { customer: Customer }) {
    return (
        <section className="relative overflow-hidden rounded-2xl border border-neutral-200 bg-neutral-950 text-white shadow-sm dark:border-neutral-800">
            <div className="pointer-events-none absolute -right-16 -top-24 h-64 w-64 rounded-full bg-white/10 blur-3xl" />
            <div className="pointer-events-none absolute -bottom-24 left-1/3 h-48 w-48 rounded-full bg-white/5 blur-3xl" />

            <div className="relative flex flex-col gap-5 p-5 sm:p-6 lg:flex-row lg:items-center lg:justify-between">
                <div className="flex min-w-0 items-center gap-4">
                    <div className="flex h-16 w-16 shrink-0 items-center justify-center rounded-2xl border border-white/10 bg-white/10 text-xl font-black shadow-inner backdrop-blur">
                        {customer.anonymized ? (
                            <CircleOff className="h-7 w-7" aria-hidden="true" />
                        ) : (
                            initials(customer.name)
                        )}
                    </div>

                    <div className="min-w-0">
                        <div className="flex flex-wrap items-center gap-2">
                            <h1 className="truncate text-2xl font-bold tracking-tight">
                                {customer.name}
                            </h1>

                            <CustomerStateBadge customer={customer} />
                        </div>

                        <div className="mt-2 flex flex-wrap items-center gap-x-4 gap-y-2 text-sm text-white/65">
                            <span className="inline-flex items-center gap-1.5 break-all">
                                <Mail className="h-4 w-4" aria-hidden="true" />
                                {customer.email}
                            </span>

                            <span className="inline-flex items-center gap-1.5">
                                <UserRound className="h-4 w-4" aria-hidden="true" />
                                Customer #{customer.id}
                            </span>
                        </div>
                    </div>
                </div>

                <div className="flex flex-wrap gap-2">
                    <HeroPill
                        icon={BadgeCheck}
                        label={customer.verified ? 'Verified email' : 'Unverified email'}
                    />
                    <HeroPill icon={Package} label={`${customer.orders_count} orders`} />
                    <HeroPill icon={MapPin} label={`${customer.addresses_count} addresses`} />
                </div>
            </div>
        </section>
    )
}

function HeroPill({ icon: Icon, label }: { icon: typeof BadgeCheck; label: string }) {
    return (
        <span className="inline-flex items-center gap-2 rounded-xl border border-white/10 bg-white/10 px-3 py-2 text-xs font-semibold text-white/85 backdrop-blur">
            <Icon className="h-3.5 w-3.5" aria-hidden="true" />
            {label}
        </span>
    )
}

function CustomerStateBadge({ customer }: { customer: Customer }) {
    if (customer.anonymized) {
        return (
            <span className="rounded-full border border-white/10 bg-white/10 px-2.5 py-1 text-[11px] font-bold uppercase tracking-wide text-white/80">
                Anonymized
            </span>
        )
    }

    const active = customer.status === 'active'

    return (
        <span
            className={[
                'rounded-full px-2.5 py-1 text-[11px] font-bold uppercase tracking-wide',
                active ? 'bg-emerald-400/15 text-emerald-200' : 'bg-amber-400/15 text-amber-200',
            ].join(' ')}
        >
            {customer.status_label}
        </span>
    )
}

function SummaryCard({
    icon: Icon,
    label,
    value,
    helper,
}: {
    icon: typeof UserRound
    label: string
    value: string | number
    helper: string
}) {
    return (
        <div className="rounded-2xl border border-neutral-200 bg-white p-5 shadow-sm dark:border-neutral-800 dark:bg-neutral-950">
            <div className="flex items-start justify-between gap-4">
                <div className="min-w-0">
                    <p className="text-xs font-semibold uppercase tracking-wide text-neutral-500">
                        {label}
                    </p>
                    <p className="mt-2 truncate text-xl font-bold tracking-tight text-neutral-950 dark:text-white">
                        {value}
                    </p>
                    <p className="mt-1 truncate text-xs text-neutral-400">{helper}</p>
                </div>

                <span className="flex h-10 w-10 shrink-0 items-center justify-center rounded-xl bg-neutral-100 text-neutral-600 dark:bg-neutral-900 dark:text-neutral-300">
                    <Icon className="h-5 w-5" aria-hidden="true" />
                </span>
            </div>
        </div>
    )
}

function SectionHeader({
    icon: Icon,
    title,
    description,
    badge,
    tone = 'neutral',
}: {
    icon: typeof UserRound
    title: string
    description: string
    badge?: string
    tone?: 'neutral' | 'danger' | 'success'
}) {
    const iconClass =
        tone === 'danger'
            ? 'bg-red-50 text-red-600 dark:bg-red-500/10 dark:text-red-300'
            : tone === 'success'
              ? 'bg-emerald-50 text-emerald-600 dark:bg-emerald-500/10 dark:text-emerald-300'
              : 'bg-neutral-100 text-neutral-600 dark:bg-neutral-900 dark:text-neutral-300'

    return (
        <div className="flex flex-wrap items-start justify-between gap-3 px-5 py-5 sm:px-6">
            <div className="flex items-start gap-3">
                <span
                    className={`flex h-10 w-10 shrink-0 items-center justify-center rounded-xl ${iconClass}`}
                >
                    <Icon className="h-5 w-5" aria-hidden="true" />
                </span>

                <div>
                    <h2 className="font-semibold text-neutral-950 dark:text-white">{title}</h2>
                    <p className="mt-1 text-sm leading-6 text-neutral-500">{description}</p>
                </div>
            </div>

            {badge && (
                <span className="rounded-full bg-neutral-100 px-2.5 py-1 text-xs font-semibold text-neutral-500 dark:bg-neutral-900 dark:text-neutral-300">
                    {badge}
                </span>
            )}
        </div>
    )
}

function DetailRow({ label, value }: { label: string; value: string }) {
    return (
        <div className="grid gap-1 py-3.5 sm:grid-cols-[150px_minmax(0,1fr)] sm:gap-4">
            <dt className="text-xs font-semibold uppercase tracking-wide text-neutral-500">
                {label}
            </dt>
            <dd className="break-words text-sm font-medium text-neutral-800 dark:text-neutral-200">
                {value}
            </dd>
        </div>
    )
}

function AddressCard({ address }: { address: Address }) {
    return (
        <article className="rounded-xl border border-neutral-200 bg-neutral-50/60 p-4 transition hover:border-neutral-300 dark:border-neutral-800 dark:bg-neutral-900/40 dark:hover:border-neutral-700">
            <div className="flex flex-wrap items-start justify-between gap-3">
                <div>
                    <div className="flex flex-wrap items-center gap-2">
                        <p className="text-sm font-semibold text-neutral-950 dark:text-white">
                            {address.label || 'Saved address'}
                        </p>

                        {address.is_default && (
                            <span className="inline-flex rounded-full bg-indigo-100 px-2 py-0.5 text-[10px] font-bold uppercase tracking-wide text-indigo-700 dark:bg-indigo-500/10 dark:text-indigo-300">
                                Default
                            </span>
                        )}
                    </div>

                    <p className="mt-2 text-sm font-semibold text-neutral-800 dark:text-neutral-200">
                        {address.recipient_name}
                    </p>
                </div>

                <MapPin className="h-4 w-4 text-neutral-400" aria-hidden="true" />
            </div>

            <div className="mt-3 text-sm leading-6 text-neutral-500">
                <p>{address.address_line_1}</p>
                {address.address_line_2 && <p>{address.address_line_2}</p>}
                <p>
                    {[address.city, address.state_or_region, address.postal_code]
                        .filter(Boolean)
                        .join(', ')}
                </p>
                <p>{address.country_code}</p>
            </div>

            <div className="mt-3 border-t border-neutral-200 pt-3 text-xs font-medium text-neutral-500 dark:border-neutral-800">
                {address.phone}
            </div>
        </article>
    )
}

function EmptyState({
    icon: Icon,
    title,
    description,
}: {
    icon: typeof Package
    title: string
    description: string
}) {
    return (
        <div className="flex min-h-52 flex-col items-center justify-center px-6 pb-8 text-center">
            <span className="flex h-12 w-12 items-center justify-center rounded-2xl bg-neutral-100 text-neutral-400 dark:bg-neutral-900">
                <Icon className="h-5 w-5" aria-hidden="true" />
            </span>
            <p className="mt-3 text-sm font-semibold text-neutral-800 dark:text-neutral-200">
                {title}
            </p>
            <p className="mt-1 max-w-sm text-sm leading-6 text-neutral-500">{description}</p>
        </div>
    )
}

function FormError({ message }: { message: string }) {
    return (
        <p role="alert" className="mt-2 text-xs font-semibold text-red-600 dark:text-red-300">
            {message}
        </p>
    )
}

function Heading({ children }: { children: string }) {
    return (
        <th
            scope="col"
            className="px-4 py-3.5 text-left text-xs font-semibold uppercase tracking-[0.08em] text-neutral-500"
        >
            {children}
        </th>
    )
}

function StatusBadge({ value, label }: { value: string; label: string }) {
    const positive = ['active', 'paid', 'delivered', 'completed', 'confirmed'].includes(value)
    const negative = ['cancelled', 'failed', 'suspended', 'blocked'].includes(value)

    return (
        <span
            className={[
                'inline-flex whitespace-nowrap rounded-full px-2.5 py-1 text-xs font-semibold',
                positive
                    ? 'bg-emerald-100 text-emerald-700 dark:bg-emerald-500/10 dark:text-emerald-300'
                    : negative
                      ? 'bg-red-100 text-red-700 dark:bg-red-500/10 dark:text-red-300'
                      : 'bg-amber-100 text-amber-700 dark:bg-amber-500/10 dark:text-amber-300',
            ].join(' ')}
        >
            {label}
        </span>
    )
}

function Pagination({ orders }: { orders: PaginatedOrders }) {
    return (
        <div className="flex flex-col gap-3 border-t border-neutral-200 px-5 py-4 sm:flex-row sm:items-center sm:justify-between dark:border-neutral-800">
            <p className="text-xs text-neutral-500">
                Showing {orders.from ?? 0}–{orders.to ?? 0} of {orders.total} orders
            </p>

            <div className="flex flex-wrap gap-1">
                {orders.links.map((link, index) =>
                    link.url ? (
                        <Link
                            key={`${link.label}-${index}`}
                            href={link.url}
                            preserveScroll
                            preserveState
                            className={[
                                'inline-flex min-h-9 min-w-9 items-center justify-center rounded-lg border px-3 text-xs font-semibold transition',
                                link.active
                                    ? 'border-neutral-900 bg-neutral-900 text-white dark:border-neutral-100 dark:bg-neutral-100 dark:text-neutral-900'
                                    : 'border-neutral-200 bg-white text-neutral-600 hover:bg-neutral-50 dark:border-neutral-800 dark:bg-neutral-950 dark:text-neutral-300 dark:hover:bg-neutral-900',
                            ].join(' ')}
                        >
                            {paginationLabel(link.label)}
                        </Link>
                    ) : (
                        <span
                            key={`${link.label}-${index}`}
                            className="inline-flex min-h-9 min-w-9 cursor-not-allowed items-center justify-center rounded-lg border border-neutral-200 px-3 text-xs text-neutral-400 opacity-60 dark:border-neutral-800"
                        >
                            {paginationLabel(link.label)}
                        </span>
                    ),
                )}
            </div>
        </div>
    )
}

function initials(name: string): string {
    const parts = name.trim().split(/\s+/).filter(Boolean)

    if (parts.length === 0) {
        return '?'
    }

    return parts
        .slice(0, 2)
        .map((part) => part.charAt(0).toUpperCase())
        .join('')
}

function formatDate(value: string | null): string {
    if (!value) {
        return '—'
    }

    return new Intl.DateTimeFormat(undefined, {
        year: 'numeric',
        month: 'short',
        day: 'numeric',
    }).format(new Date(value))
}

function formatDateTime(value: string | null): string {
    if (!value) {
        return '—'
    }

    return new Intl.DateTimeFormat(undefined, {
        year: 'numeric',
        month: 'short',
        day: 'numeric',
        hour: 'numeric',
        minute: '2-digit',
    }).format(new Date(value))
}

function paginationLabel(label: string): string {
    return label.replace('&laquo; Previous', 'Previous').replace('Next &raquo;', 'Next')
}

const textareaClass =
    'w-full rounded-xl border border-neutral-300 bg-white px-3 py-2.5 text-sm text-neutral-900 outline-none transition placeholder:text-neutral-400 focus:border-neutral-500 focus:ring-2 focus:ring-neutral-200 dark:border-neutral-700 dark:bg-neutral-950 dark:text-neutral-100 dark:focus:border-neutral-500 dark:focus:ring-neutral-800'
