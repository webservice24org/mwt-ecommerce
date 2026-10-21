import { Link, router } from '@inertiajs/react'
import { MapPin, Plus } from 'lucide-react'

import CustomerLayout from '@/Layouts/Customer/CustomerLayout'
import type { CustomerAddress } from '@/types/customer-address'

interface Props {
    addresses: CustomerAddress[]
}

export default function Index({ addresses }: Props) {
    const deleteAddress = (address: CustomerAddress) => {
        const confirmed = window.confirm(`Delete ${address.label ?? 'this delivery address'}?`)

        if (!confirmed) {
            return
        }

        router.delete(route('customer.addresses.destroy', address.id), {
            preserveScroll: true,
        })
    }

    const makeDefault = (address: CustomerAddress) => {
        if (address.is_default) {
            return
        }

        router.patch(
            route('customer.addresses.default', address.id),
            {},
            {
                preserveScroll: true,
            },
        )
    }

    return (
        <CustomerLayout
            title="Delivery Addresses"
            description="Manage the addresses used for order delivery."
            actions={
                <Link
                    href={route('customer.addresses.create')}
                    className="inline-flex min-h-11 items-center justify-center gap-2 rounded-xl bg-neutral-950 px-4 text-sm font-semibold text-white transition hover:bg-neutral-800"
                >
                    <Plus className="h-4 w-4" aria-hidden="true" />
                    Add address
                </Link>
            }
        >
            {addresses.length === 0 ? (
                <EmptyState />
            ) : (
                <div className="grid gap-4 md:grid-cols-2">
                    {addresses.map((address) => (
                        <article
                            key={address.id}
                            className="rounded-2xl border border-neutral-200 bg-white p-5 shadow-sm"
                        >
                            <div className="flex items-start justify-between gap-4">
                                <div className="flex min-w-0 items-start gap-3">
                                    <span className="flex h-10 w-10 shrink-0 items-center justify-center rounded-xl bg-neutral-100 text-neutral-700">
                                        <MapPin className="h-5 w-5" aria-hidden="true" />
                                    </span>

                                    <div className="min-w-0">
                                        <div className="flex flex-wrap items-center gap-2">
                                            <h3 className="font-bold text-neutral-950">
                                                {address.label || 'Delivery address'}
                                            </h3>

                                            {address.is_default && (
                                                <span className="rounded-full bg-neutral-950 px-2.5 py-1 text-xs font-semibold text-white">
                                                    Default
                                                </span>
                                            )}
                                        </div>

                                        <p className="mt-3 font-semibold text-neutral-800">
                                            {address.recipient_name}
                                        </p>

                                        <p className="mt-1 text-sm text-neutral-500">
                                            {address.phone}
                                        </p>

                                        <address className="mt-3 not-italic text-sm leading-6 text-neutral-600">
                                            <span className="block">{address.address_line_1}</span>

                                            {address.address_line_2 && (
                                                <span className="block">
                                                    {address.address_line_2}
                                                </span>
                                            )}

                                            <span className="block">
                                                {address.city}

                                                {address.state_or_region
                                                    ? `, ${address.state_or_region}`
                                                    : ''}
                                            </span>

                                            {address.postal_code && (
                                                <span className="block">{address.postal_code}</span>
                                            )}

                                            <span className="block uppercase">
                                                {address.country_code}
                                            </span>
                                        </address>
                                    </div>
                                </div>
                            </div>

                            <div className="mt-5 flex flex-wrap items-center justify-between gap-3 border-t border-neutral-100 pt-4">
                                <div>
                                    {!address.is_default && (
                                        <button
                                            type="button"
                                            onClick={() => makeDefault(address)}
                                            className="inline-flex min-h-10 items-center justify-center rounded-lg border border-neutral-300 bg-white px-4 text-sm font-semibold text-neutral-700 transition hover:bg-neutral-50"
                                        >
                                            Make default
                                        </button>
                                    )}

                                    {address.is_default && (
                                        <span className="text-sm font-medium text-neutral-500">
                                            Default delivery address
                                        </span>
                                    )}
                                </div>

                                <div className="flex flex-wrap items-center gap-2">
                                    <Link
                                        href={route('customer.addresses.edit', address.id)}
                                        className="inline-flex min-h-10 items-center justify-center rounded-lg border border-neutral-300 bg-white px-4 text-sm font-semibold text-neutral-700 transition hover:bg-neutral-50"
                                    >
                                        Edit
                                    </Link>

                                    <button
                                        type="button"
                                        onClick={() => deleteAddress(address)}
                                        className="inline-flex min-h-10 items-center justify-center rounded-lg border border-red-200 bg-white px-4 text-sm font-semibold text-red-600 transition hover:bg-red-50"
                                    >
                                        Delete
                                    </button>
                                </div>
                            </div>
                        </article>
                    ))}
                </div>
            )}
        </CustomerLayout>
    )
}

function EmptyState() {
    return (
        <section className="rounded-2xl border border-dashed border-neutral-300 bg-white px-6 py-12 text-center">
            <span className="mx-auto flex h-12 w-12 items-center justify-center rounded-2xl bg-neutral-100 text-neutral-600">
                <MapPin className="h-6 w-6" aria-hidden="true" />
            </span>

            <h3 className="mt-4 text-lg font-bold text-neutral-950">No delivery addresses</h3>

            <p className="mx-auto mt-2 max-w-md text-sm leading-6 text-neutral-500">
                Add your first delivery address so it can be used when you place an order.
            </p>

            <Link
                href={route('customer.addresses.create')}
                className="mt-5 inline-flex min-h-11 items-center justify-center gap-2 rounded-xl bg-neutral-950 px-4 text-sm font-semibold text-white transition hover:bg-neutral-800"
            >
                <Plus className="h-4 w-4" aria-hidden="true" />
                Add address
            </Link>
        </section>
    )
}
