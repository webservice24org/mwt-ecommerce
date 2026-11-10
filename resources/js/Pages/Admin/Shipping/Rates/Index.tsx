import {
    Link,
    router,
} from '@inertiajs/react'
import {
    Globe2,
    MapPinned,
    Pencil,
    Plus,
    Trash2,
    Truck,
} from 'lucide-react'
import {
    useState,
    type ReactNode,
} from 'react'

import ConfirmDialog from '@/Components/Admin/ConfirmDialog'
import AdminLayout from '@/Layouts/Admin/AdminLayout'
import ShippingAdminNav from '@/Components/Admin/Shipping/ShippingAdminNav'
import { formatMoney } from '@/lib/money'
import type {
    ShippingRateIndexItem,
} from '@/types/shipping'

type Props = {
    rates: ShippingRateIndexItem[]
}

export default function Index({
    rates,
}: Props) {
    const [
        deleteTarget,
        setDeleteTarget,
    ] =
        useState<ShippingRateIndexItem | null>(
            null,
        )

    const [
        deleting,
        setDeleting,
    ] =
        useState(false)

    const destroyRate = () => {
        if (
            deleteTarget === null ||
            deleting
        ) {
            return
        }

        setDeleting(true)

        router.delete(
            route(
                'admin.shipping.rates.destroy',
                deleteTarget.id,
            ),
            {
                preserveScroll: true,

                onSuccess: () => {
                    setDeleteTarget(
                        null,
                    )
                },

                onFinish: () => {
                    setDeleting(
                        false,
                    )
                },
            },
        )
    }

    return (
        <AdminLayout
            title="Shipping Rates"
            description="Manage delivery costs, zone coverage, thresholds and checkout availability."
            actions={
                <Link
                    href={route(
                        'admin.shipping.rates.create',
                    )}
                    className="inline-flex items-center gap-2 rounded-lg bg-neutral-900 px-4 py-2.5 text-sm font-medium text-white transition hover:bg-neutral-800"
                >
                    <Plus className="h-4 w-4" />

                    Add Shipping Rate
                </Link>
            }
        >
            <div className="space-y-6">
                <ShippingAdminNav />
                <div className="grid gap-4 sm:grid-cols-3">
                    <SummaryCard
                        label="Total rates"
                        value={
                            rates.length
                        }
                    />

                    <SummaryCard
                        label="Active rates"
                        value={
                            rates.filter(
                                (rate) =>
                                    rate.is_active,
                            ).length
                        }
                    />

                    <SummaryCard
                        label="Global rates"
                        value={
                            rates.filter(
                                (rate) =>
                                    rate.is_global,
                            ).length
                        }
                    />
                </div>

                <section className="overflow-hidden rounded-xl border border-neutral-200 bg-white shadow-sm">
                    <div className="border-b border-neutral-200 px-5 py-4 sm:px-6">
                        <div className="flex items-start gap-3">
                            <div className="flex h-10 w-10 shrink-0 items-center justify-center rounded-lg bg-neutral-100 text-neutral-700">
                                <Truck className="h-5 w-5" />
                            </div>

                            <div>
                                <h2 className="font-semibold text-neutral-900">
                                    Delivery
                                    pricing
                                </h2>

                                <p className="mt-1 text-sm text-neutral-500">
                                    Rates are
                                    evaluated for
                                    the selected
                                    delivery
                                    address and
                                    cart subtotal.
                                </p>
                            </div>
                        </div>
                    </div>

                    {rates.length ===
                    0 ? (
                        <div className="px-6 py-14 text-center">
                            <Truck className="mx-auto h-10 w-10 text-neutral-300" />

                            <h3 className="mt-4 font-semibold text-neutral-900">
                                No shipping
                                rates
                            </h3>

                            <p className="mx-auto mt-2 max-w-md text-sm leading-6 text-neutral-500">
                                Create a delivery rate and attach it to
                                one or more shipping zones. Zone-specific
                                rates are shown in the storefront checkout.
                            </p>

                            <Link
                                href={route(
                                    'admin.shipping.rates.create',
                                )}
                                className="mt-5 inline-flex items-center gap-2 rounded-lg bg-neutral-900 px-4 py-2 text-sm font-medium text-white"
                            >
                                <Plus className="h-4 w-4" />

                                Create first
                                rate
                            </Link>

                            <Link
                                href={route(
                                    'admin.shipping.zones.index',
                                )}
                                className="mt-3 inline-flex text-sm font-semibold text-neutral-600 hover:text-neutral-950"
                            >
                                Manage shipping zones
                            </Link>
                        </div>
                    ) : (
                        <div className="overflow-x-auto">
                            <table className="min-w-full divide-y divide-neutral-200">
                                <thead className="bg-neutral-50">
                                    <tr>
                                        <TableHeading>
                                            Rate
                                        </TableHeading>

                                        <TableHeading>
                                            Method
                                        </TableHeading>

                                        <TableHeading>
                                            Coverage
                                        </TableHeading>

                                        <TableHeading>
                                            Cost
                                        </TableHeading>

                                        <TableHeading>
                                            Conditions
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
                                    {rates.map(
                                        (
                                            rate,
                                        ) => (
                                            <tr
                                                key={
                                                    rate.id
                                                }
                                                className="hover:bg-neutral-50/70"
                                            >
                                                <td className="whitespace-nowrap px-5 py-4 sm:px-6">
                                                    <p className="font-medium text-neutral-900">
                                                        {
                                                            rate.name
                                                        }
                                                    </p>

                                                    <p className="mt-0.5 text-xs text-neutral-500">
                                                        {
                                                            rate.code
                                                        }
                                                    </p>
                                                </td>

                                                <td className="whitespace-nowrap px-5 py-4 text-sm sm:px-6">
                                                    {rate.shipping_method ? (
                                                        <div>
                                                            <p className="font-medium text-neutral-800">
                                                                {
                                                                    rate
                                                                        .shipping_method
                                                                        .name
                                                                }
                                                            </p>

                                                            {!rate
                                                                .shipping_method
                                                                .is_active && (
                                                                <p className="mt-0.5 text-xs font-medium text-red-600">
                                                                    Method
                                                                    inactive
                                                                </p>
                                                            )}
                                                        </div>
                                                    ) : (
                                                        <span className="text-red-600">
                                                            Missing
                                                        </span>
                                                    )}
                                                </td>

                                                <td className="px-5 py-4 text-sm sm:px-6">
                                                    {rate.is_global ? (
                                                        <span className="inline-flex items-center gap-1.5 rounded-full bg-blue-100 px-2.5 py-1 text-xs font-medium text-blue-800">
                                                            <Globe2 className="h-3.5 w-3.5" />

                                                            Global
                                                        </span>
                                                    ) : (
                                                        <div className="min-w-[180px]">
                                                            <div className="flex items-center gap-1.5 text-xs font-medium text-neutral-700">
                                                                <MapPinned className="h-3.5 w-3.5" />

                                                                {rate
                                                                    .zones
                                                                    .length}{' '}
                                                                {rate
                                                                    .zones
                                                                    .length ===
                                                                1
                                                                    ? 'zone'
                                                                    : 'zones'}
                                                            </div>

                                                            <p className="mt-1 max-w-[240px] text-xs leading-5 text-neutral-500">
                                                                {rate.zones
                                                                    .map(
                                                                        (
                                                                            zone,
                                                                        ) =>
                                                                            zone.name,
                                                                    )
                                                                    .join(
                                                                        ', ',
                                                                    )}
                                                            </p>
                                                        </div>
                                                    )}
                                                </td>

                                                <td className="whitespace-nowrap px-5 py-4 sm:px-6">
                                                    <p className="font-semibold text-neutral-900">
                                                        {rate.amount ===
                                                        0
                                                            ? 'Free'
                                                            : formatMoney(
                                                                  rate.amount,
                                                              )}
                                                    </p>

                                                    {rate.free_shipping_threshold !==
                                                        null && (
                                                        <p className="mt-1 text-xs text-green-700">
                                                            Free
                                                            from{' '}
                                                            {formatMoney(
                                                                rate.free_shipping_threshold,
                                                            )}
                                                        </p>
                                                    )}
                                                </td>

                                                <td className="px-5 py-4 text-xs leading-5 text-neutral-500 sm:px-6">
                                                    {rate.min_order_amount ===
                                                        null &&
                                                    rate.max_order_amount ===
                                                        null ? (
                                                        <span>
                                                            Any
                                                            subtotal
                                                        </span>
                                                    ) : (
                                                        <>
                                                            {rate.min_order_amount !==
                                                                null && (
                                                                <div>
                                                                    Min:{' '}
                                                                    {formatMoney(
                                                                        rate.min_order_amount,
                                                                    )}
                                                                </div>
                                                            )}

                                                            {rate.max_order_amount !==
                                                                null && (
                                                                <div>
                                                                    Max:{' '}
                                                                    {formatMoney(
                                                                        rate.max_order_amount,
                                                                    )}
                                                                </div>
                                                            )}
                                                        </>
                                                    )}
                                                </td>

                                                <td className="whitespace-nowrap px-5 py-4 text-sm font-medium text-neutral-700 sm:px-6">
                                                    {
                                                        rate.priority
                                                    }
                                                </td>

                                                <td className="whitespace-nowrap px-5 py-4 sm:px-6">
                                                    <StatusBadge
                                                        active={
                                                            rate.is_active
                                                        }
                                                    />
                                                </td>

                                                <td className="whitespace-nowrap px-5 py-4 text-right sm:px-6">
                                                    <div className="flex justify-end gap-2">
                                                        <Link
                                                            href={route(
                                                                'admin.shipping.rates.edit',
                                                                rate.id,
                                                            )}
                                                            aria-label={`Edit ${rate.name}`}
                                                            className="inline-flex h-9 w-9 items-center justify-center rounded-lg border border-neutral-300 text-neutral-600 transition hover:bg-neutral-50 hover:text-neutral-950"
                                                        >
                                                            <Pencil className="h-4 w-4" />
                                                        </Link>

                                                        <button
                                                            type="button"
                                                            onClick={() =>
                                                                setDeleteTarget(
                                                                    rate,
                                                                )
                                                            }
                                                            aria-label={`Delete ${rate.name}`}
                                                            className="inline-flex h-9 w-9 items-center justify-center rounded-lg border border-red-200 text-red-600 transition hover:bg-red-50"
                                                        >
                                                            <Trash2 className="h-4 w-4" />
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
                open={
                    deleteTarget !==
                    null
                }
                title="Delete shipping rate?"
                description={
                    deleteTarget
                        ? `Delete "${deleteTarget.name}"? It will no longer be available for new checkouts.`
                        : ''
                }
                confirmLabel="Delete Rate"
                processing={
                    deleting
                }
                onConfirm={
                    destroyRate
                }
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
            {active
                ? 'Active'
                : 'Inactive'}
        </span>
    )
}