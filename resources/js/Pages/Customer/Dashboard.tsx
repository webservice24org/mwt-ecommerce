import { Link, usePage } from '@inertiajs/react'
import { ArrowRight, MapPin, Package, ShoppingBag, UserRound } from 'lucide-react'
import type { ReactNode } from 'react'

import CustomerLayout from '@/Layouts/Customer/CustomerLayout'
import type { PageProps } from '@/types'

interface CustomerDashboardCustomer {
    id: number
    name: string
    email: string
    email_verified_at: string | null
}

interface CustomerDashboardSummary {
    orders: number
    addresses: number
}

type DashboardPageProps = PageProps<
    Record<string, unknown> & {
        customer: CustomerDashboardCustomer
        summary: CustomerDashboardSummary
    }
>

export default function Dashboard() {
    const { customer, summary } = usePage<DashboardPageProps>().props

    return (
        <CustomerLayout
            title="My Account"
            description="Manage your orders, delivery addresses, and account details."
        >
            <div className="space-y-6">
                <section className="overflow-hidden rounded-2xl border border-neutral-200 bg-white shadow-sm">
                    <div className="flex flex-col gap-4 p-5 sm:flex-row sm:items-center sm:justify-between sm:p-6">
                        <div className="flex min-w-0 items-center gap-4">
                            <div className="flex h-12 w-12 shrink-0 items-center justify-center rounded-2xl bg-neutral-950 text-white">
                                <UserRound className="h-5 w-5" aria-hidden="true" />
                            </div>

                            <div className="min-w-0">
                                <p className="text-sm text-neutral-500">Welcome back</p>

                                <h3 className="truncate text-xl font-bold text-neutral-950">
                                    {customer.name}
                                </h3>

                                <p className="mt-1 truncate text-sm text-neutral-500">
                                    {customer.email}
                                </p>
                            </div>
                        </div>

                        <Link
                            href={route('profile.edit')}
                            className="inline-flex min-h-11 shrink-0 items-center justify-center rounded-xl border border-neutral-300 bg-white px-4 text-sm font-semibold text-neutral-700 transition hover:bg-neutral-50 focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-neutral-950"
                        >
                            Edit profile
                        </Link>
                    </div>
                </section>

                <section aria-labelledby="account-summary-heading">
                    <div className="mb-4">
                        <h3
                            id="account-summary-heading"
                            className="text-lg font-bold text-neutral-950"
                        >
                            Account overview
                        </h3>

                        <p className="mt-1 text-sm text-neutral-500">
                            Your latest account activity and saved information.
                        </p>
                    </div>

                    <div className="grid gap-4 sm:grid-cols-2">
                        <SummaryCard
                            icon={<ShoppingBag className="h-5 w-5" aria-hidden="true" />}
                            label="Orders"
                            value={summary.orders}
                            description="Orders placed from your account."
                            href={route('customer.orders.index')}
                        />

                        <SummaryCard
                            icon={<MapPin className="h-5 w-5" aria-hidden="true" />}
                            label="Delivery addresses"
                            value={summary.addresses}
                            description="Add and manage delivery addresses."
                            href={route('customer.addresses.index')}
                        />
                    </div>
                </section>

                <section aria-labelledby="quick-actions-heading">
                    <div className="mb-4">
                        <h3
                            id="quick-actions-heading"
                            className="text-lg font-bold text-neutral-950"
                        >
                            Quick actions
                        </h3>
                    </div>

                    <div className="grid gap-4 md:grid-cols-2">
                        <ComingSoonCard
                            icon={<Package className="h-5 w-5" aria-hidden="true" />}
                            title="My orders"
                            description="View ordered items, totals, payment status, and delivery details."
                        />

                        <ComingSoonCard
                            icon={<MapPin className="h-5 w-5" aria-hidden="true" />}
                            title="Delivery addresses"
                            description="Create, update, delete, and choose your default delivery address."
                        />
                    </div>
                </section>
            </div>
        </CustomerLayout>
    )
}

function SummaryCard({
    icon,
    label,
    value,
    description,
    href,
    disabled = false,
}: {
    icon: ReactNode
    label: string
    value: number
    description: string
    href: string
    disabled?: boolean
}) {
    const content = (
        <div className="flex min-h-40 flex-col justify-between rounded-2xl border border-neutral-200 bg-white p-5 shadow-sm transition">
            <div>
                <div className="flex items-start justify-between gap-4">
                    <span className="flex h-10 w-10 shrink-0 items-center justify-center rounded-xl bg-neutral-100 text-neutral-700">
                        {icon}
                    </span>

                    <span className="text-3xl font-black text-neutral-950">{value}</span>
                </div>

                <h4 className="mt-4 text-base font-bold text-neutral-950">{label}</h4>

                <p className="mt-1 text-sm leading-6 text-neutral-500">{description}</p>
            </div>

            <div className="mt-4 inline-flex items-center gap-2 text-sm font-semibold text-neutral-500">
                {disabled ? 'Coming next' : 'View'}

                <ArrowRight className="h-4 w-4" aria-hidden="true" />
            </div>
        </div>
    )

    if (disabled) {
        return (
            <div aria-disabled="true" className="cursor-not-allowed opacity-70">
                {content}
            </div>
        )
    }

    return (
        <Link
            href={href}
            className="rounded-2xl focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-neutral-950"
        >
            {content}
        </Link>
    )
}

function ComingSoonCard({
    icon,
    title,
    description,
}: {
    icon: ReactNode
    title: string
    description: string
}) {
    return (
        <article className="rounded-2xl border border-dashed border-neutral-300 bg-neutral-100/60 p-5">
            <div className="flex items-start gap-4">
                <span className="flex h-10 w-10 shrink-0 items-center justify-center rounded-xl bg-white text-neutral-600 shadow-sm">
                    {icon}
                </span>

                <div className="min-w-0">
                    <h4 className="font-bold text-neutral-900">{title}</h4>

                    <p className="mt-1 text-sm leading-6 text-neutral-500">{description}</p>
                </div>
            </div>
        </article>
    )
}
