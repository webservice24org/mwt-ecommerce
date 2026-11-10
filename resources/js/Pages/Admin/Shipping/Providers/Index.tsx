import ShippingAdminNav from '@/Components/Admin/Shipping/ShippingAdminNav'
import AdminLayout from '@/Layouts/Admin/AdminLayout'
import type {
    ShippingProviderSummary,
} from '@/types/shipping-provider'
import type { PageProps } from '@/types'
import {
    Head,
    Link,
    router,
    usePage,
} from '@inertiajs/react'
import {
    CheckCircle2,
    CircleOff,
    KeyRound,
    Settings2,
    ShieldCheck,
    Truck,
} from 'lucide-react'

interface Props {
    providers:
        ShippingProviderSummary[]
}

type CourierProviderPageProps = PageProps<{
    flash?: {
        success?: string | null
        error?: string | null
    }
}>

export default function Index({
    providers,
}: Props) {
    const { flash } =
        usePage<CourierProviderPageProps>()
            .props

    const disconnect = (
        provider:
            ShippingProviderSummary,
    ) => {
        if (
            !provider
                .disconnect_url
        ) {
            return
        }

        if (
            !window.confirm(
                `Disconnect ${provider.name}? Stored courier credentials will be permanently removed.`,
            )
        ) {
            return
        }

        router.delete(
            provider
                .disconnect_url,
            {
                preserveScroll:
                    true,
            },
        )
    }

    const testConnection = (
        provider:
            ShippingProviderSummary,
    ) => {
        if (
            !provider.test_url
        ) {
            return
        }

        router.post(
            provider.test_url,
            {},
            {
                preserveScroll:
                    true,
            },
        )
    }

    return (
        <>
            <Head title="Courier Providers" />

            <AdminLayout
                title="Courier Providers"
                description="Connect and manage the courier accounts used for physical order fulfilment."
            >
                <div className="space-y-6">
                    <ShippingAdminNav />

                    {flash?.success && (
                        <div
                            role="status"
                            aria-live="polite"
                            className="rounded-xl border border-emerald-200 bg-emerald-50 px-4 py-3 text-sm font-medium text-emerald-800"
                        >
                            {flash.success}
                        </div>
                    )}

                    {flash?.error && (
                        <div
                            role="alert"
                            className="rounded-xl border border-red-200 bg-red-50 px-4 py-3 text-sm font-medium text-red-800"
                        >
                            {flash.error}
                        </div>
                    )}

                    <div className="rounded-xl border border-blue-200 bg-blue-50 p-4">
                        <p className="text-sm font-semibold text-blue-900">
                            Courier providers are separate from checkout delivery pricing
                        </p>

                        <p className="mt-1 text-sm leading-6 text-blue-800">
                            Shipping Zones and Rates control what the customer pays.
                            Courier Providers control which company physically delivers
                            the parcel. Connected accounts are managed independently from
                            the checkout delivery price.
                        </p>
                    </div>

                    <div className="grid gap-5 xl:grid-cols-3">
                        {providers.map(
                            (
                                provider,
                            ) => (
                                <ProviderCard
                                    key={
                                        provider.code
                                    }
                                    provider={
                                        provider
                                    }
                                    onDisconnect={() =>
                                        disconnect(
                                            provider,
                                        )
                                    }
                                    onTestConnection={() =>
                                        testConnection(
                                            provider,
                                        )
                                    }
                                />
                            ),
                        )}
                    </div>

                    <div className="rounded-xl border border-neutral-200 bg-white p-5 shadow-sm">
                        <div className="flex gap-3">
                            <ShieldCheck
                                className="mt-0.5 h-5 w-5 shrink-0 text-neutral-500"
                                aria-hidden="true"
                            />

                            <div>
                                <h2 className="font-semibold text-neutral-950">
                                    Credential security
                                </h2>

                                <p className="mt-1 text-sm leading-6 text-neutral-600">
                                    Courier secrets are encrypted in the database and
                                    are never returned to the browser after saving.
                                    Editing a provider requires entering only credentials
                                    that need to be replaced.
                                </p>
                            </div>
                        </div>
                    </div>
                </div>
            </AdminLayout>
        </>
    )
}

function ProviderCard({
    provider,
    onDisconnect,
    onTestConnection,
}: {
    provider:
        ShippingProviderSummary

    onDisconnect:
        () => void

    onTestConnection:
        () => void
}) {
    const account =
        provider.account

    return (
        <section className="overflow-hidden rounded-xl border border-neutral-200 bg-white shadow-sm">
            <div className="border-b border-neutral-200 p-5">
                <div className="flex items-start justify-between gap-4">
                    <div className="flex min-w-0 gap-3">
                        <div className="flex h-11 w-11 shrink-0 items-center justify-center rounded-xl bg-neutral-100">
                            <Truck
                                className="h-5 w-5 text-neutral-600"
                                aria-hidden="true"
                            />
                        </div>

                        <div className="min-w-0">
                            <h2 className="font-semibold text-neutral-950">
                                {provider.name}
                            </h2>

                            <p className="mt-1 text-xs font-medium uppercase tracking-wide text-neutral-400">
                                {provider.code}
                            </p>
                        </div>
                    </div>

                    <ProviderBadge
                        provider={
                            provider
                        }
                    />
                </div>
            </div>

            <div className="space-y-5 p-5">
                {!provider.external ? (
                    <div className="rounded-lg bg-emerald-50 p-4">
                        <div className="flex gap-3">
                            <CheckCircle2
                                className="mt-0.5 h-5 w-5 shrink-0 text-emerald-700"
                                aria-hidden="true"
                            />

                            <div>
                                <p className="text-sm font-semibold text-emerald-900">
                                    Always available
                                </p>

                                <p className="mt-1 text-sm leading-6 text-emerald-700">
                                    No API account is required. Carrier, service and
                                    tracking details can be entered manually.
                                </p>
                            </div>
                        </div>
                    </div>
                ) : !provider.implemented ? (
                    <div className="rounded-lg bg-neutral-50 p-4">
                        <p className="text-sm font-semibold text-neutral-800">
                            Integration reserved
                        </p>

                        <p className="mt-1 text-sm leading-6 text-neutral-500">
                            The provider is registered in the new courier-account
                            architecture, but credential setup will be enabled only
                            after its merchant API contract is verified.
                        </p>
                    </div>
                ) : account ? (
                    <>
                        <dl className="grid gap-4 sm:grid-cols-2 xl:grid-cols-1 2xl:grid-cols-2">
                            <Detail
                                label="Account"
                                value={
                                    account.name
                                }
                            />

                            <Detail
                                label="Credentials"
                                value={
                                    account
                                        .credentials_configured
                                        ? 'Configured'
                                        : 'Missing'
                                }
                            />

                            <Detail
                                label="Active"
                                value={
                                    account
                                        .is_active
                                        ? 'Yes'
                                        : 'No'
                                }
                            />

                            <Detail
                                label="Auto submit"
                                value={
                                    account
                                        .auto_submit
                                        ? 'Yes'
                                        : 'No'
                                }
                            />
                        </dl>

                        {account.last_error && (
                            <div className="rounded-lg border border-red-200 bg-red-50 p-3 text-sm leading-6 text-red-700">
                                {account.last_error}
                            </div>
                        )}
                    </>
                ) : (
                    <div className="rounded-lg bg-amber-50 p-4">
                        <div className="flex gap-3">
                            <KeyRound
                                className="mt-0.5 h-5 w-5 shrink-0 text-amber-700"
                                aria-hidden="true"
                            />

                            <div>
                                <p className="text-sm font-semibold text-amber-900">
                                    Not configured
                                </p>

                                <p className="mt-1 text-sm leading-6 text-amber-700">
                                    Add your merchant API credentials to prepare this
                                    courier account for connection testing.
                                </p>
                            </div>
                        </div>
                    </div>
                )}

                <div className="flex flex-wrap gap-2 border-t border-neutral-100 pt-4">
                    {provider.edit_url && (
                        <Link
                            href={
                                provider.edit_url
                            }
                            className="inline-flex min-h-10 items-center gap-2 rounded-lg bg-neutral-950 px-4 text-sm font-semibold text-white hover:bg-neutral-800"
                        >
                            <Settings2
                                className="h-4 w-4"
                                aria-hidden="true"
                            />

                            {account
                                ? 'Edit configuration'
                                : 'Configure'}
                        </Link>
                    )}

                    {provider.disconnect_url && (
                        <button
                            type="button"
                            onClick={
                                onDisconnect
                            }
                            className="inline-flex min-h-10 items-center gap-2 rounded-lg border border-red-200 px-4 text-sm font-semibold text-red-700 hover:bg-red-50"
                        >
                            <CircleOff
                                className="h-4 w-4"
                                aria-hidden="true"
                            />

                            Disconnect
                        </button>
                    )}

                    {provider.test_url && (
                        <button
                            type="button"
                            onClick={
                                onTestConnection
                            }
                            className="inline-flex min-h-10 items-center rounded-lg border border-blue-200 px-4 text-sm font-semibold text-blue-700 transition hover:bg-blue-50"
                        >
                            Test connection
                        </button>
                    )}
                </div>
            </div>
        </section>
    )
}

function ProviderBadge({
    provider,
}: {
    provider:
        ShippingProviderSummary
}) {
    if (
        !provider.external
    ) {
        return (
            <span className="rounded-full bg-emerald-100 px-2.5 py-1 text-xs font-semibold text-emerald-700">
                Manual
            </span>
        )
    }

    if (
        !provider.implemented
    ) {
        return (
            <span className="rounded-full bg-neutral-100 px-2.5 py-1 text-xs font-semibold text-neutral-600">
                Planned
            </span>
        )
    }

    if (!provider.account) {
        return (
            <span className="rounded-full bg-neutral-100 px-2.5 py-1 text-xs font-semibold text-neutral-600">
                Not configured
            </span>
        )
    }

    const status =
        provider.account
            .connection_status

    const className =
        status.value ===
        'connected'
            ? 'bg-emerald-100 text-emerald-700'
            : status.value ===
                'error'
              ? 'bg-red-100 text-red-700'
              : status.value ===
                  'configured'
                ? 'bg-amber-100 text-amber-700'
                : 'bg-neutral-100 text-neutral-600'

    return (
        <span
            className={[
                'rounded-full px-2.5 py-1 text-xs font-semibold',
                className,
            ].join(' ')}
        >
            {status.label}
        </span>
    )
}

function Detail({
    label,
    value,
}: {
    label: string
    value: string
}) {
    return (
        <div>
            <dt className="text-xs font-semibold uppercase tracking-wider text-neutral-400">
                {label}
            </dt>

            <dd className="mt-1 text-sm font-semibold text-neutral-900">
                {value}
            </dd>
        </div>
    )
}