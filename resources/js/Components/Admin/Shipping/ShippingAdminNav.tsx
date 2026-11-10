import {
    Link,
    usePage,
} from '@inertiajs/react'
import {
    BadgeDollarSign,
    MapPinned,
    Truck,
} from 'lucide-react'

export default function ShippingAdminNav() {
    const { url } =
        usePage()

    const zonesActive =
        url.startsWith(
            '/admin/shipping/zones',
        )

    const ratesActive =
        url.startsWith(
            '/admin/shipping/rates',
        )

    const providersActive =
        url.startsWith(
            '/admin/shipping/providers',
        )

    return (
        <div className="rounded-xl border border-neutral-200 bg-white p-2 shadow-sm">
            <div className="grid gap-2 sm:grid-cols-3">
                <Link
                    href={route(
                        'admin.shipping.zones.index',
                    )}
                    className={[
                        'flex items-center gap-3 rounded-lg px-4 py-3 transition',

                        zonesActive
                            ? 'bg-neutral-950 text-white'
                            : 'text-neutral-700 hover:bg-neutral-100 hover:text-neutral-950',
                    ].join(' ')}
                >
                    <MapPinned
                        className="h-5 w-5 shrink-0"
                        strokeWidth={
                            1.9
                        }
                        aria-hidden="true"
                    />

                    <div className="min-w-0">
                        <div className="text-sm font-semibold">
                            Shipping Zones
                        </div>

                        <div
                            className={[
                                'mt-0.5 text-xs',

                                zonesActive
                                    ? 'text-neutral-300'
                                    : 'text-neutral-500',
                            ].join(
                                ' ',
                            )}
                        >
                            Customer delivery areas
                        </div>
                    </div>
                </Link>

                <Link
                    href={route(
                        'admin.shipping.rates.index',
                    )}
                    className={[
                        'flex items-center gap-3 rounded-lg px-4 py-3 transition',

                        ratesActive
                            ? 'bg-neutral-950 text-white'
                            : 'text-neutral-700 hover:bg-neutral-100 hover:text-neutral-950',
                    ].join(' ')}
                >
                    <BadgeDollarSign
                        className="h-5 w-5 shrink-0"
                        strokeWidth={
                            1.9
                        }
                        aria-hidden="true"
                    />

                    <div className="min-w-0">
                        <div className="text-sm font-semibold">
                            Shipping Rates
                        </div>

                        <div
                            className={[
                                'mt-0.5 text-xs',

                                ratesActive
                                    ? 'text-neutral-300'
                                    : 'text-neutral-500',
                            ].join(
                                ' ',
                            )}
                        >
                            Checkout delivery pricing
                        </div>
                    </div>
                </Link>

                <Link
                    href={route(
                        'admin.shipping.providers.index',
                    )}
                    className={[
                        'flex items-center gap-3 rounded-lg px-4 py-3 transition',

                        providersActive
                            ? 'bg-neutral-950 text-white'
                            : 'text-neutral-700 hover:bg-neutral-100 hover:text-neutral-950',
                    ].join(' ')}
                >
                    <Truck
                        className="h-5 w-5 shrink-0"
                        strokeWidth={
                            1.9
                        }
                        aria-hidden="true"
                    />

                    <div className="min-w-0">
                        <div className="text-sm font-semibold">
                            Courier Providers
                        </div>

                        <div
                            className={[
                                'mt-0.5 text-xs',

                                providersActive
                                    ? 'text-neutral-300'
                                    : 'text-neutral-500',
                            ].join(
                                ' ',
                            )}
                        >
                            Pathao, Steadfast & manual
                        </div>
                    </div>
                </Link>
            </div>
        </div>
    )
}