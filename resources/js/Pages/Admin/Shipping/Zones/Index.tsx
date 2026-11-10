import { Link, router, usePage } from '@inertiajs/react'
import {
    Globe2,
    MapPinned,
    Pencil,
    Plus,
    Trash2,
} from 'lucide-react'
import { useState } from 'react'
import type { ReactNode } from 'react'
import type { PageProps } from '@/types'

import ConfirmDialog from '@/Components/Admin/ConfirmDialog'
import AdminLayout from '@/Layouts/Admin/AdminLayout'
import ShippingAdminNav from '@/Components/Admin/Shipping/ShippingAdminNav'
import type { ShippingZoneIndexItem } from '@/types/shipping'

type Props = {
    zones: ShippingZoneIndexItem[]
}

type ShippingZonesPageProps = PageProps<{
    errors?: Record<string, string>
}>

export default function Index({
    zones,
}: Props) {
    const { errors = {} } =
    usePage<ShippingZonesPageProps>().props

    const [
        deleteTarget,
        setDeleteTarget,
    ] =
        useState<ShippingZoneIndexItem | null>(
            null,
        )

    const [deleting, setDeleting] =
        useState(false)

    const destroyZone = () => {
        if (
            deleteTarget === null ||
            deleting
        ) {
            return
        }

        setDeleting(true)

        router.delete(
            route(
                'admin.shipping.zones.destroy',
                deleteTarget.id,
            ),
            {
                preserveScroll: true,

                onSuccess: () => {
                    setDeleteTarget(null)
                },

                onFinish: () => {
                    setDeleting(false)
                },
            },
        )
    }

    return (
        <AdminLayout
            title="Shipping Zones"
            description="Manage the delivery areas customers can select during checkout."
            actions={
                <Link
                    href={route(
                        'admin.shipping.zones.create',
                    )}
                    className="inline-flex items-center gap-2 rounded-lg bg-neutral-900 px-4 py-2.5 text-sm font-medium text-white transition hover:bg-neutral-800"
                >
                    <Plus
                        className="h-4 w-4"
                        aria-hidden="true"
                    />

                    Add Shipping Zone
                </Link>
            }
        >
            <div className="space-y-6">
                <ShippingAdminNav />
                <div className="rounded-xl border border-blue-200 bg-blue-50 p-4">
                    <p className="text-sm font-semibold text-blue-900">
                        Manual shipping-zone checkout is active
                    </p>

                    <p className="mt-1 text-sm leading-6 text-blue-800">
                        Customers currently select their delivery
                        zone manually during checkout. Matching
                        rules are optional and reserved for future
                        automatic address and courier-area
                        resolution.
                    </p>
                </div>
                {errors.zone && (
                    <div className="rounded-xl border border-red-200 bg-red-50 p-4 text-sm text-red-700">
                        {errors.zone}
                    </div>
                )}

                <div className="grid gap-4 sm:grid-cols-3">
                    <SummaryCard
                        label="Total zones"
                        value={zones.length}
                    />

                    <SummaryCard
                        label="Active zones"
                        value={
                            zones.filter(
                                (zone) =>
                                    zone.is_active,
                            ).length
                        }
                    />

                    <SummaryCard
                        label="Catch-all zones"
                        value={
                            zones.filter(
                                (zone) =>
                                    zone.is_catch_all,
                            ).length
                        }
                    />
                </div>

                <section className="overflow-hidden rounded-xl border border-neutral-200 bg-white shadow-sm">
                    <div className="border-b border-neutral-200 px-5 py-4 sm:px-6">
                        <div className="flex items-start gap-3">
                            <div className="flex h-10 w-10 shrink-0 items-center justify-center rounded-lg bg-neutral-100 text-neutral-700">
                                <MapPinned
                                    className="h-5 w-5"
                                    aria-hidden="true"
                                />
                            </div>

                            <div>
                                <h2 className="font-semibold text-neutral-900">
                                    Delivery zones
                                </h2>

                                <p className="mt-1 text-sm text-neutral-500">
                                    Lower priority numbers are
                                    displayed first in the
                                    storefront shipping-zone
                                    selector.
                                </p>
                            </div>
                        </div>
                    </div>

                    {zones.length === 0 ? (
                        <div className="px-6 py-14 text-center">
                            <MapPinned
                                className="mx-auto h-10 w-10 text-neutral-300"
                                aria-hidden="true"
                            />

                            <h3 className="mt-4 font-semibold text-neutral-900">
                                No shipping zones
                            </h3>

                            <p className="mx-auto mt-2 max-w-md text-sm leading-6 text-neutral-500">
                                Create a zone such
                                as Dhaka,
                                Rangamati, or a
                                nationwide fallback
                                area.
                            </p>

                            <Link
                                href={route(
                                    'admin.shipping.zones.create',
                                )}
                                className="mt-5 inline-flex items-center gap-2 rounded-lg bg-neutral-900 px-4 py-2 text-sm font-medium text-white"
                            >
                                <Plus className="h-4 w-4" />

                                Create first zone
                            </Link>
                        </div>
                    ) : (
                        <div className="overflow-x-auto">
                            <table className="min-w-full divide-y divide-neutral-200">
                                <thead className="bg-neutral-50">
                                    <tr>
                                        <TableHeading>
                                            Zone
                                        </TableHeading>

                                        <TableHeading>
                                             Auto rules
                                        </TableHeading>

                                        <TableHeading>
                                            Rates
                                        </TableHeading>

                                        <TableHeading>
                                            Priority
                                        </TableHeading>

                                        <TableHeading>
                                            Status
                                        </TableHeading>

                                        <TableHeading align="right">
                                            Actions
                                        </TableHeading>
                                    </tr>
                                </thead>

                                <tbody className="divide-y divide-neutral-200 bg-white">
                                    {zones.map(
                                        (zone) => (
                                            <tr
                                                key={
                                                    zone.id
                                                }
                                                className="hover:bg-neutral-50/70"
                                            >
                                                <td className="whitespace-nowrap px-5 py-4 sm:px-6">
                                                    <div className="flex items-center gap-3">
                                                        <div className="flex h-9 w-9 shrink-0 items-center justify-center rounded-lg bg-neutral-100">
                                                            {zone.is_catch_all ? (
                                                                <Globe2
                                                                    className="h-4 w-4 text-neutral-600"
                                                                    aria-hidden="true"
                                                                />
                                                            ) : (
                                                                <MapPinned
                                                                    className="h-4 w-4 text-neutral-600"
                                                                    aria-hidden="true"
                                                                />
                                                            )}
                                                        </div>

                                                        <div>
                                                            <p className="font-medium text-neutral-900">
                                                                {
                                                                    zone.name
                                                                }
                                                            </p>

                                                            <p className="mt-0.5 text-xs text-neutral-500">
                                                                {
                                                                    zone.code
                                                                }
                                                            </p>
                                                        </div>
                                                    </div>
                                                </td>

                                                <td className="whitespace-nowrap px-5 py-4 text-sm text-neutral-600 sm:px-6">
                                                    {zone.is_catch_all ? (
                                                        <span className="inline-flex rounded-full bg-amber-100 px-2.5 py-1 text-xs font-medium text-amber-800">
                                                            Catch-all
                                                        </span>
                                                    ) : (
                                                        <div>
                                                            <p className="text-sm text-neutral-700">
                                                                {zone.rules_count}{' '}
                                                                {zone.rules_count === 1
                                                                    ? 'rule'
                                                                    : 'rules'}
                                                            </p>

                                                            <p className="mt-0.5 text-xs text-neutral-400">
                                                                Optional
                                                            </p>
                                                        </div>
                                                    )}
                                                </td>

                                                <td className="whitespace-nowrap px-5 py-4 text-sm text-neutral-600 sm:px-6">
                                                    {
                                                        zone.rates_count
                                                    }
                                                </td>

                                                <td className="whitespace-nowrap px-5 py-4 text-sm font-medium text-neutral-700 sm:px-6">
                                                    {
                                                        zone.priority
                                                    }
                                                </td>

                                                <td className="whitespace-nowrap px-5 py-4 sm:px-6">
                                                    <StatusBadge
                                                        active={
                                                            zone.is_active
                                                        }
                                                    />
                                                </td>

                                                <td className="whitespace-nowrap px-5 py-4 text-right sm:px-6">
                                                    <div className="flex justify-end gap-2">
                                                        <Link
                                                            href={route(
                                                                'admin.shipping.zones.edit',
                                                                zone.id,
                                                            )}
                                                            className="inline-flex h-9 w-9 items-center justify-center rounded-lg border border-neutral-300 text-neutral-600 transition hover:bg-neutral-50 hover:text-neutral-950"
                                                            aria-label={`Edit ${zone.name}`}
                                                        >
                                                            <Pencil
                                                                className="h-4 w-4"
                                                                aria-hidden="true"
                                                            />
                                                        </Link>

                                                        <button
                                                            type="button"
                                                            disabled={
                                                                zone.rates_count >
                                                                0
                                                            }
                                                            onClick={() =>
                                                                setDeleteTarget(
                                                                    zone,
                                                                )
                                                            }
                                                            title={
                                                                zone.rates_count >
                                                                0
                                                                    ? 'Remove this zone from all shipping rates before deleting it.'
                                                                    : `Delete ${zone.name}`
                                                            }
                                                            className="inline-flex h-9 w-9 items-center justify-center rounded-lg border border-red-200 text-red-600 transition hover:bg-red-50 disabled:cursor-not-allowed disabled:border-neutral-200 disabled:text-neutral-300 disabled:hover:bg-white"
                                                            aria-label={`Delete ${zone.name}`}
                                                        >
                                                            <Trash2
                                                                className="h-4 w-4"
                                                                aria-hidden="true"
                                                            />
                                                        </button>
                                                    </div>
                                                </td>
                                            </tr>
                                        ),
                                    )}
                                </tbody>
                            </table>
                        </div>
                    )}
                </section>
            </div>

            <ConfirmDialog
                open={deleteTarget !== null}
                title="Delete shipping zone?"
                description={
                    deleteTarget
                        ? `Delete "${deleteTarget.name}"? Its address matching rules will also be removed.`
                        : ''
                }
                confirmLabel="Delete Zone"
                processing={deleting}
                onConfirm={destroyZone}
                onCancel={() => {
                    if (!deleting) {
                        setDeleteTarget(
                            null,
                        )
                    }
                }}
            />
        </AdminLayout>
    )
}

function SummaryCard({
    label,
    value,
}: {
    label: string
    value: number
}) {
    return (
        <div className="rounded-xl border border-neutral-200 bg-white p-5 shadow-sm">
            <p className="text-sm text-neutral-500">
                {label}
            </p>

            <p className="mt-2 text-2xl font-bold text-neutral-950">
                {value}
            </p>
        </div>
    )
}

function TableHeading({
    children,
    align = 'left',
}: {
    children: ReactNode
    align?: 'left' | 'right'
}) {
    return (
        <th
            scope="col"
            className={[
                'px-5 py-3 text-xs font-semibold uppercase tracking-wide text-neutral-500 sm:px-6',
                align === 'right'
                    ? 'text-right'
                    : 'text-left',
            ].join(' ')}
        >
            {children}
        </th>
    )
}

function StatusBadge({
    active,
}: {
    active: boolean
}) {
    return (
        <span
            className={[
                'inline-flex rounded-full px-2.5 py-1 text-xs font-medium',
                active
                    ? 'bg-green-100 text-green-700'
                    : 'bg-neutral-100 text-neutral-600',
            ].join(' ')}
        >
            {active ? 'Active' : 'Inactive'}
        </span>
    )
}