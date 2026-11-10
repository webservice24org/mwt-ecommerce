import {
    Link,
    usePage,
} from '@inertiajs/react'
import {
    BadgeDollarSign,
    ChevronDown,
    MapPinned,
    Truck,
} from 'lucide-react'
import { useState } from 'react'

type Props = {
    onClose?: () => void
}

export default function ShippingSidebarLinks({
    onClose,
}: Props) {
    const { url } = usePage()

    const zonesActive =
        url.startsWith(
            '/admin/shipping/zones',
        )

    const ratesActive =
        url.startsWith(
            '/admin/shipping/rates',
        )

    const shippingActive =
        url.startsWith(
            '/admin/shipping',
        )

    const [
        shippingOpen,
        setShippingOpen,
    ] = useState(
        shippingActive,
    )

    /*
     * Keep Shipping expanded automatically
     * whenever the current page belongs to
     * the shipping section.
     */
    const showShipping =
        shippingActive ||
        shippingOpen

    const providersActive =
    url.startsWith(
        '/admin/shipping/providers',
    )

    return (
        <div className="space-y-1">
            {/* Shipping dropdown */}
            <button
                type="button"
                aria-expanded={
                    showShipping
                }
                aria-controls="admin-shipping-navigation"
                onClick={() =>
                    setShippingOpen(
                        (
                            current,
                        ) =>
                            !current,
                    )
                }
                className={[
                    'flex w-full items-center gap-3 rounded-lg px-3 py-2 text-sm font-medium transition-all',

                    shippingActive
                        ? 'bg-neutral-100 text-neutral-950'
                        : 'text-neutral-600 hover:bg-neutral-100 hover:text-neutral-950',
                ].join(' ')}
            >
                <Truck
                    className={[
                        'h-4 w-4 shrink-0 transition',

                        shippingActive
                            ? 'text-neutral-900'
                            : 'text-neutral-400',
                    ].join(' ')}
                    strokeWidth={
                        1.9
                    }
                    aria-hidden="true"
                />

                <span className="min-w-0 flex-1 text-left">
                    Shipping
                </span>

                <ChevronDown
                    className={[
                        'h-4 w-4 shrink-0 text-neutral-400 transition-transform duration-200',

                        showShipping
                            ? 'rotate-180'
                            : '',
                    ].join(' ')}
                    aria-hidden="true"
                />
            </button>

            {/* Shipping children */}
            <div
                id="admin-shipping-navigation"
                className={[
                    'grid transition-all duration-200',

                    showShipping
                        ? 'grid-rows-[1fr]'
                        : 'grid-rows-[0fr]',
                ].join(' ')}
            >
                <div className="overflow-hidden">
                    <div className="relative ml-5 mt-1 space-y-1 border-l border-neutral-200 pl-3">
                        {/* Shipping Zones */}
                        <Link
                            href={route(
                                'admin.shipping.zones.index',
                            )}
                            onClick={
                                onClose
                            }
                            className={[
                                'group relative flex items-center gap-3 rounded-lg px-3 py-2 text-sm font-medium transition-all duration-150',

                                zonesActive
                                    ? 'bg-neutral-900 text-white shadow-sm'
                                    : 'text-neutral-600 hover:bg-neutral-100 hover:text-neutral-950',
                            ].join(
                                ' ',
                            )}
                        >
                            <MapPinned
                                className={[
                                    'h-4 w-4 shrink-0 transition',

                                    zonesActive
                                        ? 'text-white'
                                        : 'text-neutral-400 group-hover:text-neutral-700',
                                ].join(
                                    ' ',
                                )}
                                strokeWidth={
                                    1.9
                                }
                                aria-hidden="true"
                            />

                            <span className="min-w-0 flex-1 truncate">
                                Shipping
                                Zones
                            </span>

                            {zonesActive && (
                                <span className="absolute right-2 h-1.5 w-1.5 rounded-full bg-white" />
                            )}
                        </Link>

                        {/* Shipping Rates */}
                        <Link
                            href={route(
                                'admin.shipping.rates.index',
                            )}
                            onClick={
                                onClose
                            }
                            className={[
                                'group relative flex items-center gap-3 rounded-lg px-3 py-2 text-sm font-medium transition-all duration-150',

                                ratesActive
                                    ? 'bg-neutral-900 text-white shadow-sm'
                                    : 'text-neutral-600 hover:bg-neutral-100 hover:text-neutral-950',
                            ].join(
                                ' ',
                            )}
                        >
                            <BadgeDollarSign
                                className={[
                                    'h-4 w-4 shrink-0 transition',

                                    ratesActive
                                        ? 'text-white'
                                        : 'text-neutral-400 group-hover:text-neutral-700',
                                ].join(
                                    ' ',
                                )}
                                strokeWidth={
                                    1.9
                                }
                                aria-hidden="true"
                            />

                            <span className="min-w-0 flex-1 truncate">
                                Shipping
                                Rates
                            </span>

                            {ratesActive && (
                                <span className="absolute right-2 h-1.5 w-1.5 rounded-full bg-white" />
                            )}
                        </Link>

                        <Link
                            href={route(
                                'admin.shipping.providers.index',
                            )}
                            onClick={
                                onClose
                            }
                            className={[
                                'group relative flex items-center gap-3 rounded-lg px-3 py-2 text-sm font-medium transition-all duration-150',

                                providersActive
                                    ? 'bg-neutral-900 text-white shadow-sm'
                                    : 'text-neutral-600 hover:bg-neutral-100 hover:text-neutral-950',
                            ].join(' ')}
                        >
                            <Truck
                                className={[
                                    'h-4 w-4 shrink-0 transition',

                                    providersActive
                                        ? 'text-white'
                                        : 'text-neutral-400 group-hover:text-neutral-700',
                                ].join(' ')}
                                strokeWidth={
                                    1.9
                                }
                                aria-hidden="true"
                            />

                            <span className="min-w-0 flex-1 truncate">
                                Courier Providers
                            </span>

                            {providersActive && (
                                <span className="absolute right-2 h-1.5 w-1.5 rounded-full bg-white" />
                            )}
                        </Link>
                    </div>
                </div>
            </div>
        </div>
    )
}