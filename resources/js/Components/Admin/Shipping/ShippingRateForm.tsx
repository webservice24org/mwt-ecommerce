import type { InertiaFormProps } from '@inertiajs/react'
import {
    Check,
    Globe2,
    MapPinned,
    Truck,
} from 'lucide-react'
import {
    useState,
    type FormEvent,
} from 'react'

import type {
    ShippingRateFormData,
    ShippingRateFormOptions,
} from '@/types/shipping'

type CoverageMode =
    | 'global'
    | 'zones'

type Props = {
    form: InertiaFormProps<ShippingRateFormData>
    options: ShippingRateFormOptions
    submitLabel: string

    preferZonesOnCreate?: boolean

    onSubmit: (
        event: FormEvent<HTMLFormElement>,
    ) => void
}

/*
 * Store money in minor units.
 *
 * Example:
 *
 * Admin enters: 80
 * Stored value: 8000
 * formatMoney(8000): BDT 80
 */
function minorToMajor(
    value: number | null,
): string {
    if (value === null) {
        return ''
    }

    return String(
        value / 100,
    )
}

function majorToMinor(
    value: string,
): number | null {
    const trimmed =
        value.trim()

    if (trimmed === '') {
        return null
    }

    const parsed =
        Number(trimmed)

    if (
        !Number.isFinite(parsed) ||
        parsed < 0
    ) {
        return null
    }

    return Math.round(
        parsed * 100,
    )
}

export default function ShippingRateForm({
    form,
    options,
    submitLabel,
    preferZonesOnCreate = false,
    onSubmit,
}: Props) {
    const [
        coverageMode,
        setCoverageMode,
    ] = useState<CoverageMode>(
        form.data.zone_ids.length > 0
            ? 'zones'
            : preferZonesOnCreate &&
                options.zones.length > 0
            ? 'zones'
            : 'global',
    )

    const [
        coverageError,
        setCoverageError,
    ] =
        useState<string | null>(
            null,
        )

    const setCoverage = (
        mode: CoverageMode,
    ) => {
        setCoverageMode(mode)

        setCoverageError(null)

        if (mode === 'global') {
            form.setData(
                'zone_ids',
                [],
            )
        }
    }

    const toggleZone = (
        zoneId: number,
    ) => {
        setCoverageError(null)

        if (
            form.data.zone_ids.includes(
                zoneId,
            )
        ) {
            form.setData(
                'zone_ids',
                form.data.zone_ids.filter(
                    (id) =>
                        id !==
                        zoneId,
                ),
            )

            return
        }

        form.setData(
            'zone_ids',
            [
                ...form.data
                    .zone_ids,
                zoneId,
            ],
        )
    }

    const submitForm = (
        event: FormEvent<HTMLFormElement>,
    ) => {
        if (
            coverageMode ===
                'zones' &&
            form.data.zone_ids
                .length === 0
        ) {
            event.preventDefault()

            setCoverageError(
                'Select at least one shipping zone or choose Global coverage.',
            )

            return
        }

        setCoverageError(null)

        onSubmit(event)
    }

    return (
        <form
            onSubmit={submitForm}
            className="grid gap-6 xl:grid-cols-[minmax(0,1fr)_360px]"
        >
            <div className="space-y-6">
                {/* Main information */}
                <section className="rounded-xl border border-neutral-200 bg-white p-6 shadow-sm">
                    <div className="mb-6">
                        <h2 className="text-base font-semibold text-neutral-900">
                            Shipping rate
                            information
                        </h2>

                        <p className="mt-1 text-sm leading-6 text-neutral-500">
                            Configure the
                            delivery method,
                            customer-facing
                            rate name and
                            delivery cost.
                        </p>
                    </div>

                    <div className="space-y-5">
                        <div>
                            <label
                                htmlFor="shipping_method_id"
                                className="text-sm font-medium text-neutral-900"
                            >
                                Shipping method
                            </label>

                            <select
                                id="shipping_method_id"
                                value={
                                    form.data
                                        .shipping_method_id ??
                                    ''
                                }
                                onChange={(
                                    event,
                                ) =>
                                    form.setData(
                                        'shipping_method_id',
                                        event
                                            .target
                                            .value ===
                                            ''
                                            ? null
                                            : Number(
                                                  event
                                                      .target
                                                      .value,
                                              ),
                                    )
                                }
                                required
                                className="mt-1 block w-full rounded-lg border border-neutral-300 bg-white px-3 py-2.5 text-sm outline-none focus:border-neutral-500 focus:ring-1 focus:ring-neutral-500"
                            >
                                <option value="">
                                    Select
                                    shipping
                                    method
                                </option>

                                {options.shippingMethods.map(
                                    (
                                        method,
                                    ) => (
                                        <option
                                            key={
                                                method.id
                                            }
                                            value={
                                                method.id
                                            }
                                        >
                                            {
                                                method.name
                                            }

                                            {!method.is_active
                                                ? ' — inactive'
                                                : ''}
                                        </option>
                                    ),
                                )}
                            </select>

                            {form.errors
                                .shipping_method_id && (
                                <p className="mt-1 text-sm text-red-600">
                                    {
                                        form
                                            .errors
                                            .shipping_method_id
                                    }
                                </p>
                            )}
                        </div>

                        <div className="grid gap-5 sm:grid-cols-2">
                            <div>
                                <label
                                    htmlFor="name"
                                    className="text-sm font-medium text-neutral-900"
                                >
                                    Rate name
                                </label>

                                <input
                                    id="name"
                                    type="text"
                                    value={
                                        form.data
                                            .name
                                    }
                                    onChange={(
                                        event,
                                    ) =>
                                        form.setData(
                                            'name',
                                            event
                                                .target
                                                .value,
                                        )
                                    }
                                    placeholder="Dhaka Delivery"
                                    maxLength={
                                        120
                                    }
                                    required
                                    className="mt-1 block w-full rounded-lg border border-neutral-300 px-3 py-2.5 text-sm outline-none focus:border-neutral-500 focus:ring-1 focus:ring-neutral-500"
                                />

                                {form.errors
                                    .name && (
                                    <p className="mt-1 text-sm text-red-600">
                                        {
                                            form
                                                .errors
                                                .name
                                        }
                                    </p>
                                )}
                            </div>

                            <div>
                                <label
                                    htmlFor="code"
                                    className="text-sm font-medium text-neutral-900"
                                >
                                    Code
                                </label>

                                <input
                                    id="code"
                                    type="text"
                                    value={
                                        form.data
                                            .code
                                    }
                                    onChange={(
                                        event,
                                    ) =>
                                        form.setData(
                                            'code',
                                            event
                                                .target
                                                .value,
                                        )
                                    }
                                    placeholder="dhaka-delivery"
                                    maxLength={
                                        80
                                    }
                                    className="mt-1 block w-full rounded-lg border border-neutral-300 px-3 py-2.5 text-sm outline-none focus:border-neutral-500 focus:ring-1 focus:ring-neutral-500"
                                />

                                <p className="mt-1 text-xs text-neutral-500">
                                    Leave blank to
                                    generate it from
                                    the rate name.
                                </p>

                                {form.errors
                                    .code && (
                                    <p className="mt-1 text-sm text-red-600">
                                        {
                                            form
                                                .errors
                                                .code
                                        }
                                    </p>
                                )}
                            </div>
                        </div>

                        <div>
                            <label
                                htmlFor="amount"
                                className="text-sm font-medium text-neutral-900"
                            >
                                Delivery cost
                                (BDT)
                            </label>

                            <div className="relative mt-1">
                                <span className="pointer-events-none absolute inset-y-0 left-3 flex items-center text-sm font-semibold text-neutral-500">
                                    ৳
                                </span>

                                <input
                                    id="amount"
                                    type="number"
                                    min={0}
                                    step="0.01"
                                    value={
                                        minorToMajor(
                                            form.data
                                                .amount,
                                        )
                                    }
                                    onChange={(
                                        event,
                                    ) =>
                                        form.setData(
                                            'amount',
                                            majorToMinor(
                                                event
                                                    .target
                                                    .value,
                                            ) ??
                                                0,
                                        )
                                    }
                                    required
                                    className="block w-full rounded-lg border border-neutral-300 py-2.5 pl-8 pr-3 text-sm outline-none focus:border-neutral-500 focus:ring-1 focus:ring-neutral-500"
                                />
                            </div>

                            <p className="mt-1 text-xs leading-5 text-neutral-500">
                                Enter the amount
                                in taka. For
                                example, enter 80
                                for BDT 80.
                            </p>

                            {form.errors
                                .amount && (
                                <p className="mt-1 text-sm text-red-600">
                                    {
                                        form
                                            .errors
                                            .amount
                                    }
                                </p>
                            )}
                        </div>
                    </div>
                </section>

                {/* Coverage */}
                <section className="rounded-xl border border-neutral-200 bg-white p-6 shadow-sm">
                    <div>
                        <h2 className="text-base font-semibold text-neutral-900">
                            Delivery coverage
                        </h2>

                        <p className="mt-1 text-sm leading-6 text-neutral-500">
                            Make this rate
                            global or attach it
                            to one or more
                            shipping zones.
                        </p>
                    </div>

                    <div className="mt-5 grid gap-3 sm:grid-cols-2">
                        <button
                            type="button"
                            onClick={() =>
                                setCoverage(
                                    'global',
                                )
                            }
                            className={[
                                'rounded-xl border p-4 text-left transition',
                                coverageMode ===
                                'global'
                                    ? 'border-neutral-950 bg-neutral-50 ring-1 ring-neutral-950'
                                    : 'border-neutral-200 hover:border-neutral-400',
                            ].join(' ')}
                        >
                            <div className="flex items-start gap-3">
                                <div className="flex h-9 w-9 shrink-0 items-center justify-center rounded-lg bg-neutral-100">
                                    <Globe2 className="h-4 w-4 text-neutral-700" />
                                </div>

                                <div className="min-w-0">
                                    <p className="font-semibold text-neutral-900">
                                        Global /Special
                                    </p>

                                    <p className="mt-1 text-xs leading-5 text-neutral-500">
                                        Unzoned rate for special or legacy use.
                                    </p>
                                </div>

                                {coverageMode === 'global' && (
                                    <div className="mt-5 rounded-xl border border-amber-200 bg-amber-50 p-4">
                                        <p className="text-sm font-semibold text-amber-900">
                                            Global rates are not used by the current
                                            manual shipping-zone checkout.
                                        </p>

                                        <p className="mt-1 text-sm leading-6 text-amber-800">
                                            For normal storefront delivery pricing,
                                            choose Selected zones and attach this rate
                                            to at least one active shipping zone.
                                        </p>
                                    </div>
                                )}
                            </div>
                        </button>

                        <button
                            type="button"
                            onClick={() =>
                                setCoverage(
                                    'zones',
                                )
                            }
                            className={[
                                'rounded-xl border p-4 text-left transition',
                                coverageMode ===
                                'zones'
                                    ? 'border-neutral-950 bg-neutral-50 ring-1 ring-neutral-950'
                                    : 'border-neutral-200 hover:border-neutral-400',
                            ].join(' ')}
                        >
                            <div className="flex items-start gap-3">
                                <div className="flex h-9 w-9 shrink-0 items-center justify-center rounded-lg bg-neutral-100">
                                    <MapPinned className="h-4 w-4 text-neutral-700" />
                                </div>

                                <div className="min-w-0">
                                    <p className="font-semibold text-neutral-900">
                                        Selected
                                        zones
                                    </p>

                                    <p className="mt-1 text-xs leading-5 text-neutral-500">
                                        Recommended for storefront
                                        checkout.
                                    </p>
                                </div>

                                {coverageMode ===
                                    'zones' && (
                                    <Check className="ml-auto h-5 w-5 shrink-0 text-neutral-950" />
                                )}
                            </div>
                        </button>
                    </div>

                    {coverageMode ===
                        'global' && (
                        <div className="mt-5 rounded-xl border border-blue-200 bg-blue-50 p-4 text-sm leading-6 text-blue-800">
                            This rate has no
                            shipping-zone
                            restriction.
                        </div>
                    )}

                    {coverageMode ===
                        'zones' && (
                        <div className="mt-5">
                            {options.zones
                                .length ===
                            0 ? (
                                <div className="rounded-xl border border-amber-200 bg-amber-50 p-4">
                                    <p className="text-sm font-medium text-amber-900">
                                        No shipping
                                        zones exist
                                        yet.
                                    </p>

                                    <p className="mt-1 text-sm leading-6 text-amber-800">
                                        Create a
                                        shipping zone
                                        before
                                        creating a
                                        zone-specific
                                        rate.
                                    </p>
                                </div>
                            ) : (
                                <div className="grid gap-3 sm:grid-cols-2">
                                    {options.zones.map(
                                        (
                                            zone,
                                        ) => {
                                            const selected =
                                                form.data.zone_ids.includes(
                                                    zone.id,
                                                )

                                            return (
                                                <label
                                                    key={
                                                        zone.id
                                                    }
                                                    className={[
                                                        'flex cursor-pointer items-start gap-3 rounded-xl border p-4 transition',
                                                        selected
                                                            ? 'border-neutral-950 bg-neutral-50'
                                                            : 'border-neutral-200 bg-white hover:border-neutral-400',
                                                        !zone.is_active
                                                            ? 'opacity-60'
                                                            : '',
                                                    ].join(
                                                        ' ',
                                                    )}
                                                >
                                                    <input
                                                        type="checkbox"
                                                        checked={
                                                            selected
                                                        }
                                                        onChange={() =>
                                                            toggleZone(
                                                                zone.id,
                                                            )
                                                        }
                                                        className="mt-1 h-4 w-4 rounded border-neutral-300"
                                                    />

                                                    <span className="min-w-0 flex-1">
                                                        <span className="flex flex-wrap items-center gap-2">
                                                            <span className="font-medium text-neutral-900">
                                                                {
                                                                    zone.name
                                                                }
                                                            </span>

                                                            {zone.is_catch_all && (
                                                                <span className="rounded-full bg-amber-100 px-2 py-0.5 text-[11px] font-medium text-amber-800">
                                                                    Catch-all
                                                                </span>
                                                            )}

                                                            {!zone.is_active && (
                                                                <span className="rounded-full bg-neutral-100 px-2 py-0.5 text-[11px] font-medium text-neutral-600">
                                                                    Inactive
                                                                </span>
                                                            )}
                                                        </span>

                                                        <span className="mt-1 block text-xs text-neutral-500">
                                                            {
                                                                zone.code
                                                            }{' '}
                                                            · priority{' '}
                                                            {
                                                                zone.priority
                                                            }
                                                        </span>
                                                    </span>
                                                </label>
                                            )
                                        },
                                    )}
                                </div>
                            )}

                            {coverageError && (
                                <p className="mt-3 text-sm text-red-600">
                                    {
                                        coverageError
                                    }
                                </p>
                            )}

                            {form.errors
                                .zone_ids && (
                                <p className="mt-3 text-sm text-red-600">
                                    {
                                        form
                                            .errors
                                            .zone_ids
                                    }
                                </p>
                            )}
                        </div>
                    )}
                </section>

                {/* Conditions */}
                <section className="rounded-xl border border-neutral-200 bg-white p-6 shadow-sm">
                    <div>
                        <h2 className="text-base font-semibold text-neutral-900">
                            Order conditions
                        </h2>

                        <p className="mt-1 text-sm leading-6 text-neutral-500">
                            These conditions are
                            optional. Leave them
                            blank for an
                            unrestricted rate.
                        </p>
                    </div>

                    <div className="mt-5 grid gap-5 md:grid-cols-3">
                        <div>
                            <label
                                htmlFor="min_order_amount"
                                className="text-sm font-medium text-neutral-900"
                            >
                                Minimum order
                                (BDT)
                            </label>

                            <input
                                id="min_order_amount"
                                type="number"
                                min={0}
                                step="0.01"
                                value={
                                    minorToMajor(
                                        form.data
                                            .min_order_amount,
                                    )
                                }
                                onChange={(
                                    event,
                                ) =>
                                    form.setData(
                                        'min_order_amount',
                                        majorToMinor(
                                            event
                                                .target
                                                .value,
                                        ),
                                    )
                                }
                                placeholder="No minimum"
                                className="mt-1 block w-full rounded-lg border border-neutral-300 px-3 py-2.5 text-sm outline-none focus:border-neutral-500 focus:ring-1 focus:ring-neutral-500"
                            />

                            {form.errors
                                .min_order_amount && (
                                <p className="mt-1 text-sm text-red-600">
                                    {
                                        form
                                            .errors
                                            .min_order_amount
                                    }
                                </p>
                            )}
                        </div>

                        <div>
                            <label
                                htmlFor="max_order_amount"
                                className="text-sm font-medium text-neutral-900"
                            >
                                Maximum order
                                (BDT)
                            </label>

                            <input
                                id="max_order_amount"
                                type="number"
                                min={0}
                                step="0.01"
                                value={
                                    minorToMajor(
                                        form.data
                                            .max_order_amount,
                                    )
                                }
                                onChange={(
                                    event,
                                ) =>
                                    form.setData(
                                        'max_order_amount',
                                        majorToMinor(
                                            event
                                                .target
                                                .value,
                                        ),
                                    )
                                }
                                placeholder="No maximum"
                                className="mt-1 block w-full rounded-lg border border-neutral-300 px-3 py-2.5 text-sm outline-none focus:border-neutral-500 focus:ring-1 focus:ring-neutral-500"
                            />

                            {form.errors
                                .max_order_amount && (
                                <p className="mt-1 text-sm text-red-600">
                                    {
                                        form
                                            .errors
                                            .max_order_amount
                                    }
                                </p>
                            )}
                        </div>

                        <div>
                            <label
                                htmlFor="free_shipping_threshold"
                                className="text-sm font-medium text-neutral-900"
                            >
                                Free over
                                (BDT)
                            </label>

                            <input
                                id="free_shipping_threshold"
                                type="number"
                                min={0}
                                step="0.01"
                                value={
                                    minorToMajor(
                                        form.data
                                            .free_shipping_threshold,
                                    )
                                }
                                onChange={(
                                    event,
                                ) =>
                                    form.setData(
                                        'free_shipping_threshold',
                                        majorToMinor(
                                            event
                                                .target
                                                .value,
                                        ),
                                    )
                                }
                                placeholder="Never free"
                                className="mt-1 block w-full rounded-lg border border-neutral-300 px-3 py-2.5 text-sm outline-none focus:border-neutral-500 focus:ring-1 focus:ring-neutral-500"
                            />

                            {form.errors
                                .free_shipping_threshold && (
                                <p className="mt-1 text-sm text-red-600">
                                    {
                                        form
                                            .errors
                                            .free_shipping_threshold
                                    }
                                </p>
                            )}
                        </div>
                    </div>

                    <div className="mt-4 rounded-xl bg-neutral-50 p-4 text-xs leading-5 text-neutral-600">
                        Example: delivery
                        cost ৳150 with
                        <strong>
                            {' '}
                            Free over ৳2,000
                        </strong>{' '}
                        charges ৳150 below
                        ৳2,000 and ৳0 from
                        ৳2,000 upward.
                    </div>
                </section>
            </div>

            {/* Right column */}
            <div className="space-y-6">
                <section className="rounded-xl border border-neutral-200 bg-white p-6 shadow-sm">
                    <div className="flex items-center gap-3">
                        <div className="flex h-10 w-10 items-center justify-center rounded-lg bg-neutral-100">
                            <Truck className="h-5 w-5 text-neutral-700" />
                        </div>

                        <div>
                            <h2 className="font-semibold text-neutral-900">
                                Availability
                            </h2>

                            <p className="text-xs text-neutral-500">
                                Checkout
                                visibility
                            </p>
                        </div>
                    </div>

                    <div className="mt-5 space-y-5">
                        <label className="flex cursor-pointer items-start gap-3">
                            <input
                                type="checkbox"
                                checked={
                                    form.data
                                        .is_active
                                }
                                onChange={(
                                    event,
                                ) =>
                                    form.setData(
                                        'is_active',
                                        event
                                            .target
                                            .checked,
                                    )
                                }
                                className="mt-1 h-4 w-4 rounded border-neutral-300"
                            />

                            <span>
                                <span className="block text-sm font-medium text-neutral-900">
                                    Active rate
                                </span>

                                <span className="mt-0.5 block text-xs leading-5 text-neutral-500">
                                    Only active
                                    rates belonging
                                    to active
                                    shipping
                                    methods can be
                                    offered at
                                    checkout.
                                </span>
                            </span>
                        </label>

                        <div className="border-t border-neutral-200 pt-5">
                            <label
                                htmlFor="priority"
                                className="text-sm font-medium text-neutral-900"
                            >
                                Priority
                            </label>

                            <input
                                id="priority"
                                type="number"
                                min={0}
                                max={
                                    1000000
                                }
                                step={1}
                                value={
                                    form.data
                                        .priority
                                }
                                onChange={(
                                    event,
                                ) =>
                                    form.setData(
                                        'priority',
                                        Number(
                                            event
                                                .target
                                                .value,
                                        ) ||
                                            0,
                                    )
                                }
                                className="mt-1 block w-full rounded-lg border border-neutral-300 px-3 py-2.5 text-sm outline-none focus:border-neutral-500 focus:ring-1 focus:ring-neutral-500"
                            />

                            <p className="mt-1 text-xs leading-5 text-neutral-500">
                                Lower values are
                                displayed first
                                when several rates
                                are available.
                            </p>

                            {form.errors
                                .priority && (
                                <p className="mt-1 text-sm text-red-600">
                                    {
                                        form
                                            .errors
                                            .priority
                                    }
                                </p>
                            )}
                        </div>
                    </div>
                </section>

                <section className="rounded-xl border border-neutral-200 bg-white p-6 shadow-sm">
                    <button
                        type="submit"
                        disabled={
                            form.processing
                        }
                        className="inline-flex w-full items-center justify-center rounded-lg bg-neutral-900 px-4 py-2.5 text-sm font-semibold text-white transition hover:bg-neutral-800 disabled:cursor-not-allowed disabled:opacity-50"
                    >
                        {form.processing
                            ? 'Saving...'
                            : submitLabel}
                    </button>
                </section>
            </div>
        </form>
    )
}