import { Head, Link, router } from '@inertiajs/react'
import {
    BadgeCheck,
    ChevronRight,
    CircleOff,
    RotateCcw,
    Search,
    ShoppingBag,
    SlidersHorizontal,
    UserRoundCheck,
    UsersRound,
} from 'lucide-react'
import { useState } from 'react'
import type { FormEvent } from 'react'

import AdminLayout from '@/Layouts/Admin/AdminLayout'

type Customer = {
    id: number
    name: string
    email: string
    status: string
    status_label: string
    verified: boolean
    email_verified_at: string | null
    orders_count: number
    created_at: string | null
    anonymized: boolean
    anonymized_at: string | null
}

type PaginationLink = {
    url: string | null
    label: string
    active: boolean
}

type PaginatedCustomers = {
    data: Customer[]
    links: PaginationLink[]
    current_page: number
    last_page: number
    from: number | null
    to: number | null
    total: number
}

type Filters = {
    search?: string
    status?: string
    verified?: string
}

type StatusOption = {
    value: string
    label: string
}

type Props = {
    customers: PaginatedCustomers
    filters: Filters
    options: {
        statuses: StatusOption[]
    }
}

export default function Index({ customers, filters, options }: Props) {
    const [search, setSearch] = useState(filters.search ?? '')
    const [status, setStatus] = useState(filters.status ?? '')
    const [verified, setVerified] = useState(filters.verified ?? '')

    const activeOnPage = customers.data.filter(
        (customer) => customer.status === 'active' && !customer.anonymized,
    ).length

    const verifiedOnPage = customers.data.filter(
        (customer) => customer.verified && !customer.anonymized,
    ).length

    const withOrdersOnPage = customers.data.filter((customer) => customer.orders_count > 0).length

    const hasFilters = Boolean(search || status || verified)

    const submitFilters = (event: FormEvent<HTMLFormElement>) => {
        event.preventDefault()

        router.get(
            route('admin.customers.index'),
            {
                search: search || undefined,
                status: status || undefined,
                verified: verified || undefined,
            },
            {
                preserveState: true,
                replace: true,
            },
        )
    }

    const resetFilters = () => {
        setSearch('')
        setStatus('')
        setVerified('')

        router.get(
            route('admin.customers.index'),
            {},
            {
                preserveState: true,
                replace: true,
            },
        )
    }

    return (
        <AdminLayout
            title="Customers"
            description="Review customer accounts, verification state, order activity, and account access."
        >
            <Head title="Customers" />

            <div className="space-y-6">
                <section className="overflow-hidden rounded-2xl border border-neutral-200 bg-white shadow-sm dark:border-neutral-800 dark:bg-neutral-950">
                    <div className="relative overflow-hidden border-b border-neutral-200 px-5 py-6 sm:px-6 dark:border-neutral-800">
                        <div className="pointer-events-none absolute inset-y-0 right-0 w-64 bg-gradient-to-l from-neutral-100/80 to-transparent dark:from-neutral-900/60" />

                        <div className="relative flex flex-col gap-4 lg:flex-row lg:items-center lg:justify-between">
                            <div className="flex items-start gap-4">
                                <div className="flex h-12 w-12 shrink-0 items-center justify-center rounded-2xl bg-neutral-900 text-white shadow-sm dark:bg-neutral-100 dark:text-neutral-950">
                                    <UsersRound className="h-6 w-6" aria-hidden="true" />
                                </div>

                                <div>
                                    <p className="text-xs font-semibold uppercase tracking-[0.18em] text-neutral-500">
                                        Customer directory
                                    </p>

                                    <h2 className="mt-1 text-xl font-bold tracking-tight text-neutral-950 dark:text-white">
                                        Storefront accounts
                                    </h2>

                                    <p className="mt-1 max-w-2xl text-sm leading-6 text-neutral-500">
                                        Search customer identities, inspect order activity, and
                                        manage access without exposing authentication secrets.
                                    </p>
                                </div>
                            </div>

                            <div className="inline-flex w-fit items-center gap-2 rounded-full border border-neutral-200 bg-white/80 px-3 py-1.5 text-xs font-semibold text-neutral-600 shadow-sm backdrop-blur dark:border-neutral-800 dark:bg-neutral-950/80 dark:text-neutral-300">
                                <span className="h-2 w-2 rounded-full bg-emerald-500" />
                                {customers.total.toLocaleString()} registered
                            </div>
                        </div>
                    </div>

                    <div className="grid gap-px bg-neutral-200 sm:grid-cols-2 xl:grid-cols-4 dark:bg-neutral-800">
                        <MetricCard
                            icon={UsersRound}
                            label="Total customers"
                            value={customers.total}
                            helper="Across all pages"
                        />

                        <MetricCard
                            icon={UserRoundCheck}
                            label="Active here"
                            value={activeOnPage}
                            helper="Current page"
                        />

                        <MetricCard
                            icon={BadgeCheck}
                            label="Verified here"
                            value={verifiedOnPage}
                            helper="Current page"
                        />

                        <MetricCard
                            icon={ShoppingBag}
                            label="With orders"
                            value={withOrdersOnPage}
                            helper={`Page ${customers.current_page} of ${customers.last_page}`}
                        />
                    </div>
                </section>

                <section className="rounded-2xl border border-neutral-200 bg-white p-4 shadow-sm sm:p-5 dark:border-neutral-800 dark:bg-neutral-950">
                    <div className="mb-4 flex flex-wrap items-center justify-between gap-3">
                        <div className="flex items-center gap-2">
                            <SlidersHorizontal
                                className="h-4 w-4 text-neutral-500"
                                aria-hidden="true"
                            />
                            <h2 className="text-sm font-semibold text-neutral-900 dark:text-white">
                                Filters
                            </h2>

                            {hasFilters && (
                                <span className="rounded-full bg-neutral-100 px-2 py-0.5 text-[11px] font-semibold text-neutral-600 dark:bg-neutral-900 dark:text-neutral-300">
                                    Active
                                </span>
                            )}
                        </div>

                        {hasFilters && (
                            <button
                                type="button"
                                onClick={resetFilters}
                                className="inline-flex min-h-9 items-center gap-2 rounded-lg px-3 text-xs font-semibold text-neutral-500 transition hover:bg-neutral-100 hover:text-neutral-900 dark:hover:bg-neutral-900 dark:hover:text-white"
                            >
                                <RotateCcw className="h-3.5 w-3.5" aria-hidden="true" />
                                Clear filters
                            </button>
                        )}
                    </div>

                    <form
                        onSubmit={submitFilters}
                        className="grid gap-3 sm:grid-cols-2 xl:grid-cols-[minmax(260px,1.6fr)_1fr_1fr_auto]"
                    >
                        <div className="relative sm:col-span-2 xl:col-span-1">
                            <Search
                                className="pointer-events-none absolute left-3.5 top-1/2 h-4 w-4 -translate-y-1/2 text-neutral-400"
                                aria-hidden="true"
                            />

                            <input
                                type="search"
                                value={search}
                                onChange={(event) => setSearch(event.target.value)}
                                placeholder="Search by name or email..."
                                className={`${inputClass} pl-10`}
                            />
                        </div>

                        <select
                            value={status}
                            onChange={(event) => setStatus(event.target.value)}
                            className={inputClass}
                        >
                            <option value="">All statuses</option>

                            {options.statuses.map((option) => (
                                <option key={option.value} value={option.value}>
                                    {option.label}
                                </option>
                            ))}
                        </select>

                        <select
                            value={verified}
                            onChange={(event) => setVerified(event.target.value)}
                            className={inputClass}
                        >
                            <option value="">All verification</option>
                            <option value="1">Verified</option>
                            <option value="0">Unverified</option>
                        </select>

                        <button
                            type="submit"
                            className="inline-flex min-h-11 items-center justify-center gap-2 rounded-xl bg-neutral-900 px-5 text-sm font-semibold text-white shadow-sm transition hover:bg-neutral-700 dark:bg-neutral-100 dark:text-neutral-900 dark:hover:bg-neutral-300"
                        >
                            <Search className="h-4 w-4" aria-hidden="true" />
                            Apply
                        </button>
                    </form>
                </section>

                <section className="overflow-hidden rounded-2xl border border-neutral-200 bg-white shadow-sm dark:border-neutral-800 dark:bg-neutral-950">
                    <div className="flex flex-wrap items-center justify-between gap-3 border-b border-neutral-200 px-5 py-4 dark:border-neutral-800">
                        <div>
                            <h2 className="font-semibold text-neutral-900 dark:text-white">
                                Customer list
                            </h2>
                            <p className="mt-1 text-xs text-neutral-500">
                                Showing {customers.from ?? 0}–{customers.to ?? 0} of{' '}
                                {customers.total}
                            </p>
                        </div>

                        <span className="rounded-full bg-neutral-100 px-3 py-1 text-xs font-semibold text-neutral-600 dark:bg-neutral-900 dark:text-neutral-300">
                            Page {customers.current_page} / {customers.last_page}
                        </span>
                    </div>

                    {customers.data.length === 0 ? (
                        <div className="flex min-h-80 flex-col items-center justify-center px-6 py-12 text-center">
                            <div className="flex h-14 w-14 items-center justify-center rounded-2xl bg-neutral-100 text-neutral-500 dark:bg-neutral-900 dark:text-neutral-400">
                                <UsersRound className="h-6 w-6" aria-hidden="true" />
                            </div>

                            <h3 className="mt-4 text-base font-semibold text-neutral-900 dark:text-white">
                                No customers found
                            </h3>

                            <p className="mt-1 max-w-sm text-sm leading-6 text-neutral-500">
                                No customer matches the current search and filter combination.
                            </p>

                            {hasFilters && (
                                <button
                                    type="button"
                                    onClick={resetFilters}
                                    className="mt-4 inline-flex min-h-10 items-center gap-2 rounded-lg border border-neutral-300 px-4 text-sm font-semibold text-neutral-700 transition hover:bg-neutral-50 dark:border-neutral-700 dark:text-neutral-200 dark:hover:bg-neutral-900"
                                >
                                    <RotateCcw className="h-4 w-4" aria-hidden="true" />
                                    Reset filters
                                </button>
                            )}
                        </div>
                    ) : (
                        <div className="overflow-x-auto">
                            <table className="min-w-[900px] w-full divide-y divide-neutral-200 dark:divide-neutral-800">
                                <thead className="bg-neutral-50/90 dark:bg-neutral-900/60">
                                    <tr>
                                        <TableHeading>Customer</TableHeading>
                                        <TableHeading>Status</TableHeading>
                                        <TableHeading>Verification</TableHeading>
                                        <TableHeading>Orders</TableHeading>
                                        <TableHeading>Registered</TableHeading>
                                        <TableHeading align="right">Actions</TableHeading>
                                    </tr>
                                </thead>

                                <tbody className="divide-y divide-neutral-200 dark:divide-neutral-800">
                                    {customers.data.map((customer) => (
                                        <tr
                                            key={customer.id}
                                            className="group transition hover:bg-neutral-50/80 dark:hover:bg-neutral-900/40"
                                        >
                                            <td className="px-5 py-4">
                                                <div className="flex min-w-0 items-center gap-3">
                                                    <CustomerAvatar
                                                        name={customer.name}
                                                        anonymized={customer.anonymized}
                                                    />

                                                    <div className="min-w-0">
                                                        <div className="flex flex-wrap items-center gap-2">
                                                            <p className="truncate text-sm font-semibold text-neutral-950 dark:text-white">
                                                                {customer.name}
                                                            </p>

                                                            {customer.anonymized && (
                                                                <span className="rounded-full bg-neutral-200 px-2 py-0.5 text-[10px] font-bold uppercase tracking-wide text-neutral-600 dark:bg-neutral-800 dark:text-neutral-300">
                                                                    Anonymized
                                                                </span>
                                                            )}
                                                        </div>

                                                        <p className="mt-1 truncate text-xs text-neutral-500">
                                                            {customer.email}
                                                        </p>

                                                        <p className="mt-1 text-[11px] font-medium text-neutral-400">
                                                            Customer #{customer.id}
                                                        </p>
                                                    </div>
                                                </div>
                                            </td>

                                            <td className="px-4 py-4">
                                                <CustomerStatusBadge customer={customer} />
                                            </td>

                                            <td className="px-4 py-4">
                                                <VerificationBadge
                                                    verified={customer.verified}
                                                    anonymized={customer.anonymized}
                                                />
                                            </td>

                                            <td className="px-4 py-4">
                                                <div className="inline-flex min-w-10 items-center justify-center rounded-lg bg-neutral-100 px-2.5 py-1.5 text-sm font-bold text-neutral-700 dark:bg-neutral-900 dark:text-neutral-200">
                                                    {customer.orders_count}
                                                </div>
                                            </td>

                                            <td className="whitespace-nowrap px-4 py-4 text-sm text-neutral-500">
                                                {formatDate(customer.created_at)}
                                            </td>

                                            <td className="px-5 py-4 text-right">
                                                <Link
                                                    href={route(
                                                        'admin.customers.show',
                                                        customer.id,
                                                    )}
                                                    className="inline-flex min-h-9 items-center gap-1.5 rounded-lg border border-neutral-200 bg-white px-3 text-xs font-semibold text-neutral-700 shadow-sm transition group-hover:border-neutral-300 hover:bg-neutral-50 dark:border-neutral-800 dark:bg-neutral-950 dark:text-neutral-200 dark:group-hover:border-neutral-700 dark:hover:bg-neutral-900"
                                                >
                                                    View details
                                                    <ChevronRight
                                                        className="h-3.5 w-3.5"
                                                        aria-hidden="true"
                                                    />
                                                </Link>
                                            </td>
                                        </tr>
                                    ))}
                                </tbody>
                            </table>
                        </div>
                    )}

                    {customers.last_page > 1 && <Pagination customers={customers} />}
                </section>
            </div>
        </AdminLayout>
    )
}

function MetricCard({
    icon: Icon,
    label,
    value,
    helper,
}: {
    icon: typeof UsersRound
    label: string
    value: number | string
    helper: string
}) {
    return (
        <div className="bg-white p-5 dark:bg-neutral-950">
            <div className="flex items-start justify-between gap-4">
                <div>
                    <p className="text-xs font-semibold uppercase tracking-wide text-neutral-500">
                        {label}
                    </p>
                    <p className="mt-2 text-2xl font-bold tracking-tight text-neutral-950 dark:text-white">
                        {value}
                    </p>
                    <p className="mt-1 text-xs text-neutral-400">{helper}</p>
                </div>

                <span className="flex h-10 w-10 shrink-0 items-center justify-center rounded-xl bg-neutral-100 text-neutral-600 dark:bg-neutral-900 dark:text-neutral-300">
                    <Icon className="h-5 w-5" aria-hidden="true" />
                </span>
            </div>
        </div>
    )
}

function CustomerAvatar({ name, anonymized }: { name: string; anonymized: boolean }) {
    return (
        <div
            className={[
                'flex h-11 w-11 shrink-0 items-center justify-center rounded-xl text-sm font-bold shadow-sm',
                anonymized
                    ? 'bg-neutral-200 text-neutral-500 dark:bg-neutral-800 dark:text-neutral-400'
                    : 'bg-neutral-900 text-white dark:bg-neutral-100 dark:text-neutral-900',
            ].join(' ')}
            aria-hidden="true"
        >
            {anonymized ? <CircleOff className="h-5 w-5" /> : initials(name)}
        </div>
    )
}

function CustomerStatusBadge({ customer }: { customer: Customer }) {
    if (customer.anonymized) {
        return (
            <span className="inline-flex items-center gap-1.5 rounded-full bg-neutral-100 px-2.5 py-1 text-xs font-semibold text-neutral-600 dark:bg-neutral-800 dark:text-neutral-300">
                <CircleOff className="h-3 w-3" aria-hidden="true" />
                Anonymized
            </span>
        )
    }

    const active = customer.status === 'active'

    return (
        <span
            className={[
                'inline-flex items-center gap-1.5 rounded-full px-2.5 py-1 text-xs font-semibold',
                active
                    ? 'bg-emerald-100 text-emerald-700 dark:bg-emerald-500/10 dark:text-emerald-300'
                    : 'bg-amber-100 text-amber-700 dark:bg-amber-500/10 dark:text-amber-300',
            ].join(' ')}
        >
            <span
                className={[
                    'h-1.5 w-1.5 rounded-full',
                    active ? 'bg-emerald-500' : 'bg-amber-500',
                ].join(' ')}
            />
            {customer.status_label}
        </span>
    )
}

function VerificationBadge({ verified, anonymized }: { verified: boolean; anonymized: boolean }) {
    if (anonymized) {
        return (
            <span className="inline-flex rounded-full bg-neutral-100 px-2.5 py-1 text-xs font-semibold text-neutral-500 dark:bg-neutral-800 dark:text-neutral-400">
                Removed
            </span>
        )
    }

    return (
        <span
            className={[
                'inline-flex items-center gap-1.5 rounded-full px-2.5 py-1 text-xs font-semibold',
                verified
                    ? 'bg-sky-100 text-sky-700 dark:bg-sky-500/10 dark:text-sky-300'
                    : 'bg-neutral-100 text-neutral-600 dark:bg-neutral-800 dark:text-neutral-300',
            ].join(' ')}
        >
            {verified && <BadgeCheck className="h-3 w-3" aria-hidden="true" />}
            {verified ? 'Verified' : 'Unverified'}
        </span>
    )
}

function TableHeading({
    children,
    align = 'left',
}: {
    children: string
    align?: 'left' | 'right'
}) {
    return (
        <th
            scope="col"
            className={[
                'px-4 py-3.5 text-xs font-semibold uppercase tracking-[0.08em] text-neutral-500',
                align === 'right' ? 'text-right' : 'text-left',
            ].join(' ')}
        >
            {children}
        </th>
    )
}

function Pagination({ customers }: { customers: PaginatedCustomers }) {
    return (
        <div className="flex flex-col gap-3 border-t border-neutral-200 px-5 py-4 sm:flex-row sm:items-center sm:justify-between dark:border-neutral-800">
            <p className="text-xs text-neutral-500">
                Showing {customers.from ?? 0}–{customers.to ?? 0} of {customers.total} customers
            </p>

            <div className="flex flex-wrap gap-1">
                {customers.links.map((paginationLink, index) =>
                    paginationLink.url ? (
                        <Link
                            key={`${paginationLink.label}-${index}`}
                            href={paginationLink.url}
                            preserveScroll
                            preserveState
                            className={[
                                'inline-flex min-h-9 min-w-9 items-center justify-center rounded-lg border px-3 text-xs font-semibold transition',
                                paginationLink.active
                                    ? 'border-neutral-900 bg-neutral-900 text-white dark:border-neutral-100 dark:bg-neutral-100 dark:text-neutral-900'
                                    : 'border-neutral-200 bg-white text-neutral-600 hover:bg-neutral-50 dark:border-neutral-800 dark:bg-neutral-950 dark:text-neutral-300 dark:hover:bg-neutral-900',
                            ].join(' ')}
                        >
                            {paginationLabel(paginationLink.label)}
                        </Link>
                    ) : (
                        <span
                            key={`${paginationLink.label}-${index}`}
                            className="inline-flex min-h-9 min-w-9 cursor-not-allowed items-center justify-center rounded-lg border border-neutral-200 px-3 text-xs text-neutral-400 opacity-60 dark:border-neutral-800"
                        >
                            {paginationLabel(paginationLink.label)}
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

function paginationLabel(label: string): string {
    return label.replace('&laquo; Previous', 'Previous').replace('Next &raquo;', 'Next')
}

const inputClass =
    'min-h-11 w-full rounded-xl border border-neutral-300 bg-white px-3 text-sm text-neutral-900 outline-none transition placeholder:text-neutral-400 focus:border-neutral-500 focus:ring-2 focus:ring-neutral-200 dark:border-neutral-700 dark:bg-neutral-950 dark:text-neutral-100 dark:focus:border-neutral-500 dark:focus:ring-neutral-800'
