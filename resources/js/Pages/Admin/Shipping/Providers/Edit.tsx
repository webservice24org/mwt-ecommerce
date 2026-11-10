import ShippingAdminNav from '@/Components/Admin/Shipping/ShippingAdminNav'
import AdminLayout from '@/Layouts/Admin/AdminLayout'
import type {
    ShippingProviderEditData,
} from '@/types/shipping-provider'
import {
    Head,
    Link,
    useForm,
} from '@inertiajs/react'
import {
    KeyRound,
    LockKeyhole,
    Settings2,
} from 'lucide-react'
import type {
    FormEvent,
} from 'react'

interface Props {
    provider:
        ShippingProviderEditData
}

interface FormData {
    name: string

    credentials: {
        client_id: string
        client_secret: string
        access_token: string
        username: string
        password: string
        api_key: string
        secret_key: string
    }

    settings: {
        store_id: number | null
        delivery_type: number
        item_type: number
        default_item_weight_kg: number
    }
}

interface OperationalFormData {
    is_active: boolean
    is_default: boolean
    auto_submit: boolean
}

type OperationalAccount = NonNullable<
    ShippingProviderEditData['account']
> & {
    is_active: boolean
    is_default: boolean
    auto_submit: boolean
}

export default function Edit({
    provider,
}: Props) {
    const account =
        provider.account

    const operationalAccount =
        account as
            | OperationalAccount
            | null

    const operationalForm =
        useForm<OperationalFormData>({
            is_active:
                operationalAccount
                    ?.is_active ??
                false,

            is_default:
                operationalAccount
                    ?.is_default ??
                false,

            auto_submit:
                operationalAccount
                    ?.auto_submit ??
                false,
        })

    const form =
        useForm<FormData>({
            name:
                account?.name ??
                provider.name,

            credentials: {
                client_id: '',
                client_secret: '',
                access_token: '',
                username: '',
                password: '',
                api_key: '',
                secret_key: '',
            },

            settings: {
                store_id:
                    account
                        ?.settings
                        .store_id ??
                    null,

                delivery_type:
                    account
                        ?.settings
                        .delivery_type ??
                    provider
                        .defaults
                        .delivery_type,

                item_type:
                    account
                        ?.settings
                        .item_type ??
                    provider
                        .defaults
                        .item_type,

                default_item_weight_kg:
                    account
                        ?.settings
                        .default_item_weight_kg ??
                    provider
                        .defaults
                        .default_item_weight_kg,
            },
        })

    const submit = (
        event:
            FormEvent<HTMLFormElement>,
    ) => {
        event.preventDefault()

        form.put(
            route(
                'admin.shipping.providers.update',
                provider.code,
            ),
        )
    }

    const submitOperational = (
        event:
            FormEvent<HTMLFormElement>,
    ) => {
        event.preventDefault()

        if (!operationalAccount) {
            return
        }

        operationalForm.put(
            route(
                'admin.shipping.providers.operational-settings',
                provider.code,
            ),
            {
                preserveScroll: true,
            },
        )
    }

    const connectionIsVerified =
        operationalAccount
            ?.connection_status
            .value ===
        'connected'

    return (
        <>
            <Head
                title={`Configure ${provider.name}`}
            />

            <AdminLayout
                title={`Configure ${provider.name}`}
                description="Store merchant courier credentials securely for later provider connection and automated fulfilment."
            >
                <div className="space-y-6">
                    <ShippingAdminNav />

                    <div className="flex">
                        <Link
                            href={route(
                                'admin.shipping.providers.index',
                            )}
                            className="text-sm font-semibold text-neutral-600 hover:text-neutral-950"
                        >
                            ← Back to Courier Providers
                        </Link>
                    </div>

                    <form
                        onSubmit={
                            submit
                        }
                        className="space-y-6"
                    >
                        <section className="rounded-xl border border-neutral-200 bg-white p-5 shadow-sm sm:p-6">
                            <div className="flex gap-3">
                                <KeyRound
                                    className="mt-0.5 h-5 w-5 shrink-0 text-neutral-500"
                                    aria-hidden="true"
                                />

                                <div>
                                    <h2 className="font-semibold text-neutral-950">
                                        Merchant account
                                    </h2>

                                    <p className="mt-1 text-sm leading-6 text-neutral-500">
                                        {provider.code === 'pathao'
                                            ? 'Pathao merchant API credentials are stored encrypted and existing secrets are never displayed again after saving.'
                                            : 'Steadfast merchant API credentials are stored encrypted and existing secrets are never displayed again after saving.'}
                                    </p>
                                </div>
                            </div>

                            <div className="mt-6">
                                <label
                                    htmlFor="provider-name"
                                    className="text-sm font-semibold text-neutral-800"
                                >
                                    Account name
                                </label>

                                <input
                                    id="provider-name"
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
                                    className="mt-2 block w-full rounded-lg border border-neutral-300 px-3 py-2.5"
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
                        </section>

                        <section className="rounded-xl border border-neutral-200 bg-white p-5 shadow-sm sm:p-6">
                            <div className="flex gap-3">
                                <LockKeyhole
                                    className="mt-0.5 h-5 w-5 shrink-0 text-neutral-500"
                                    aria-hidden="true"
                                />

                                <div>
                                    <h2 className="font-semibold text-neutral-950">
                                        API credentials
                                    </h2>

                                    <p className="mt-1 text-sm leading-6 text-neutral-500">
                                        Leave an existing credential field blank to
                                        keep its currently encrypted value.
                                    </p>
                                </div>
                            </div>

                            {provider.code === 'pathao' ? (
                                <>
                                    <div className="mt-6 grid gap-5 md:grid-cols-2">
                                        <SecretField
                                            id="client-id"
                                            label="Client ID"
                                            configured={
                                                account
                                                    ?.credential_state
                                                    .client_id ??
                                                false
                                            }
                                            value={
                                                form.data
                                                    .credentials
                                                    .client_id
                                            }
                                            onChange={(
                                                value,
                                            ) =>
                                                form.setData(
                                                    'credentials',
                                                    {
                                                        ...form
                                                            .data
                                                            .credentials,

                                                        client_id:
                                                            value,
                                                    },
                                                )
                                            }
                                            error={
                                                form.errors[
                                                    'credentials.client_id'
                                                ]
                                            }
                                        />

                                        <SecretField
                                            id="client-secret"
                                            label="Client Secret"
                                            configured={
                                                account
                                                    ?.credential_state
                                                    .client_secret ??
                                                false
                                            }
                                            value={
                                                form.data
                                                    .credentials
                                                    .client_secret
                                            }
                                            onChange={(
                                                value,
                                            ) =>
                                                form.setData(
                                                    'credentials',
                                                    {
                                                        ...form
                                                            .data
                                                            .credentials,

                                                        client_secret:
                                                            value,
                                                    },
                                                )
                                            }
                                            error={
                                                form.errors[
                                                    'credentials.client_secret'
                                                ]
                                            }
                                        />

                                        <div className="md:col-span-2">
                                            <SecretField
                                                id="access-token"
                                                label="Access Token (optional)"
                                                configured={
                                                    account
                                                        ?.credential_state
                                                        .access_token ??
                                                    false
                                                }
                                                value={
                                                    form.data
                                                        .credentials
                                                        .access_token
                                                }
                                                onChange={(
                                                    value,
                                                ) =>
                                                    form.setData(
                                                        'credentials',
                                                        {
                                                            ...form
                                                                .data
                                                                .credentials,

                                                            access_token:
                                                                value,
                                                        },
                                                    )
                                                }
                                                helper="Leave this blank to let the system issue and cache an access token automatically using Client ID, Client Secret, Username and Password."
                                                error={
                                                    form.errors[
                                                        'credentials.access_token'
                                                    ]
                                                }
                                            />
                                        </div>
                                    </div>

                                    <details
                                        open
                                        className="mt-6 rounded-xl border border-neutral-200 bg-neutral-50 p-4"
                                    >
                                        <summary className="cursor-pointer text-sm font-semibold text-neutral-800">
                                            Automatic token generation
                                        </summary>

                                        <p className="mt-2 text-sm leading-6 text-neutral-500">
                                            Pathao can issue the access token automatically using
                                            Client ID, Client Secret, Username and Password. If you
                                            leave Access Token blank, keep these credentials
                                            configured.
                                        </p>

                                        <div className="mt-4 grid gap-5 md:grid-cols-2">
                                            <SecretField
                                                id="username"
                                                label="Username"
                                                configured={
                                                    account
                                                        ?.credential_state
                                                        .username ??
                                                    false
                                                }
                                                value={
                                                    form.data
                                                        .credentials
                                                        .username
                                                }
                                                onChange={(
                                                    value,
                                                ) =>
                                                    form.setData(
                                                        'credentials',
                                                        {
                                                            ...form
                                                                .data
                                                                .credentials,

                                                            username:
                                                                value,
                                                        },
                                                    )
                                                }
                                                error={
                                                    form.errors[
                                                        'credentials.username'
                                                    ]
                                                }
                                            />

                                            <SecretField
                                                id="password"
                                                label="Password"
                                                configured={
                                                    account
                                                        ?.credential_state
                                                        .password ??
                                                    false
                                                }
                                                value={
                                                    form.data
                                                        .credentials
                                                        .password
                                                }
                                                onChange={(
                                                    value,
                                                ) =>
                                                    form.setData(
                                                        'credentials',
                                                        {
                                                            ...form
                                                                .data
                                                                .credentials,

                                                            password:
                                                                value,
                                                        },
                                                    )
                                                }
                                                error={
                                                    form.errors[
                                                        'credentials.password'
                                                    ]
                                                }
                                            />
                                        </div>
                                    </details>
                                </>
                            ) : (
                                <div className="mt-6 grid gap-5 md:grid-cols-2">
                                    <SecretField
                                        id="api-key"
                                        label="API Key"
                                        configured={
                                            (
                                                account
                                                    ?.credential_state as
                                                    | Record<
                                                          string,
                                                          boolean
                                                      >
                                                    | undefined
                                            )?.api_key ??
                                            false
                                        }
                                        value={
                                            form.data
                                                .credentials
                                                .api_key
                                        }
                                        onChange={(
                                            value,
                                        ) =>
                                            form.setData(
                                                'credentials',
                                                {
                                                    ...form
                                                        .data
                                                        .credentials,

                                                    api_key:
                                                        value,
                                                },
                                            )
                                        }
                                        error={
                                            form.errors[
                                                'credentials.api_key'
                                            ]
                                        }
                                    />

                                    <SecretField
                                        id="secret-key"
                                        label="Secret Key"
                                        configured={
                                            (
                                                account
                                                    ?.credential_state as
                                                    | Record<
                                                          string,
                                                          boolean
                                                      >
                                                    | undefined
                                            )?.secret_key ??
                                            false
                                        }
                                        value={
                                            form.data
                                                .credentials
                                                .secret_key
                                        }
                                        onChange={(
                                            value,
                                        ) =>
                                            form.setData(
                                                'credentials',
                                                {
                                                    ...form
                                                        .data
                                                        .credentials,

                                                    secret_key:
                                                        value,
                                                },
                                            )
                                        }
                                        helper="Generated from your Steadfast merchant API credentials."
                                        error={
                                            form.errors[
                                                'credentials.secret_key'
                                            ]
                                        }
                                    />
                                </div>
                            )}
                        </section>

                        {provider.code === 'pathao' && (
                            <section className="rounded-xl border border-neutral-200 bg-white p-5 shadow-sm sm:p-6">
                                <h2 className="font-semibold text-neutral-950">
                                    Pathao fulfilment defaults
                                </h2>

                                <p className="mt-1 text-sm leading-6 text-neutral-500">
                                    These settings are not secrets and are used when a
                                    shipment is submitted to Pathao.
                                </p>

                                <div className="mt-6 grid gap-5 sm:grid-cols-2">
                                    <NumberField
                                        id="store-id"
                                        label="Pathao Store ID"
                                        value={
                                            form.data
                                                .settings
                                                .store_id
                                        }
                                        onChange={(
                                            value,
                                        ) =>
                                            form.setData(
                                                'settings',
                                                {
                                                    ...form
                                                        .data
                                                        .settings,

                                                    store_id:
                                                        value,
                                                },
                                            )
                                        }
                                        error={
                                            form.errors[
                                                'settings.store_id'
                                            ]
                                        }
                                    />

                                    <div>
                                        <label
                                            htmlFor="delivery-type"
                                            className="text-sm font-semibold text-neutral-800"
                                        >
                                            Delivery type
                                        </label>

                                        <select
                                            id="delivery-type"
                                            value={
                                                form.data
                                                    .settings
                                                    .delivery_type
                                            }
                                            onChange={(event) =>
                                                form.setData(
                                                    'settings',
                                                    {
                                                        ...form.data
                                                            .settings,

                                                        delivery_type:
                                                            Number(
                                                                event
                                                                    .target
                                                                    .value,
                                                            ),
                                                    },
                                                )
                                            }
                                            className="mt-2 block w-full rounded-lg border border-neutral-300 px-3 py-2.5"
                                        >
                                            <option value={48}>
                                                Normal Delivery
                                            </option>

                                            <option value={12}>
                                                On Demand Delivery
                                            </option>
                                        </select>

                                        {form.errors[
                                            'settings.delivery_type'
                                        ] && (
                                            <p className="mt-1 text-sm text-red-600">
                                                {
                                                    form.errors[
                                                        'settings.delivery_type'
                                                    ]
                                                }
                                            </p>
                                        )}
                                    </div>

                                    <div>
                                        <label
                                            htmlFor="item-type"
                                            className="text-sm font-semibold text-neutral-800"
                                        >
                                            Product type
                                        </label>

                                        <select
                                            id="item-type"
                                            value={
                                                form.data
                                                    .settings
                                                    .item_type
                                            }
                                            onChange={(event) =>
                                                form.setData(
                                                    'settings',
                                                    {
                                                        ...form.data
                                                            .settings,

                                                        item_type:
                                                            Number(
                                                                event
                                                                    .target
                                                                    .value,
                                                            ),
                                                    },
                                                )
                                            }
                                            className="mt-2 block w-full rounded-lg border border-neutral-300 px-3 py-2.5"
                                        >
                                            <option value={2}>
                                                Parcel
                                            </option>

                                            <option value={1}>
                                                Document
                                            </option>
                                        </select>

                                        <p className="mt-1 text-xs leading-5 text-neutral-500">
                                            Use Parcel for normal ecommerce products.
                                        </p>

                                        {form.errors[
                                            'settings.item_type'
                                        ] && (
                                            <p className="mt-1 text-sm text-red-600">
                                                {
                                                    form.errors[
                                                        'settings.item_type'
                                                    ]
                                                }
                                            </p>
                                        )}
                                    </div>

                                    <div>
                                        <label
                                            htmlFor="default-weight"
                                            className="text-sm font-semibold text-neutral-800"
                                        >
                                            Default item weight (kg)
                                        </label>

                                        <input
                                            id="default-weight"
                                            type="number"
                                            min="0.5"
                                            max="10"
                                            step="0.01"
                                            value={
                                                form.data
                                                    .settings
                                                    .default_item_weight_kg
                                            }
                                            onChange={(
                                                event,
                                            ) =>
                                                form.setData(
                                                    'settings',
                                                    {
                                                        ...form
                                                            .data
                                                            .settings,

                                                        default_item_weight_kg:
                                                            Number(
                                                                event
                                                                    .target
                                                                    .value,
                                                            ),
                                                    },
                                                )
                                            }
                                            className="mt-2 block w-full rounded-lg border border-neutral-300 px-3 py-2.5"
                                        />

                                        {form.errors[
                                            'settings.default_item_weight_kg'
                                        ] && (
                                            <p className="mt-1 text-sm text-red-600">
                                                {
                                                    form.errors[
                                                        'settings.default_item_weight_kg'
                                                    ]
                                                }
                                            </p>
                                        )}
                                    </div>
                                </div>
                            </section>
                        )}

                        <div className="rounded-xl border border-amber-200 bg-amber-50 p-4">
                            <p className="text-sm font-semibold text-amber-900">
                                Re-test the connection after saving
                            </p>

                            <p className="mt-1 text-sm leading-6 text-amber-800">
                                Saving credentials or fulfilment settings resets the
                                verification state to Configured. Return to Courier
                                Providers and click Test connection. A successful test
                                marks {provider.name} Connected. Operational settings
                                are managed separately below and do not reset verification.
                            </p>
                        </div>

                        <div className="flex justify-end gap-3">
                            <Link
                                href={route(
                                    'admin.shipping.providers.index',
                                )}
                                className="inline-flex min-h-11 items-center rounded-lg border border-neutral-300 px-5 text-sm font-semibold text-neutral-700 hover:bg-neutral-50"
                            >
                                Cancel
                            </Link>

                            <button
                                type="submit"
                                disabled={
                                    form.processing
                                }
                                className="inline-flex min-h-11 items-center rounded-lg bg-neutral-950 px-5 text-sm font-semibold text-white hover:bg-neutral-800 disabled:opacity-50"
                            >
                                {form.processing
                                    ? 'Saving...'
                                    : 'Save configuration'}
                            </button>
                        </div>
                    </form>

                    <form
                        onSubmit={
                            submitOperational
                        }
                        className="rounded-xl border border-neutral-200 bg-white p-5 shadow-sm sm:p-6"
                    >
                        <div className="flex gap-3">
                            <Settings2
                                className="mt-0.5 h-5 w-5 shrink-0 text-neutral-500"
                                aria-hidden="true"
                            />

                            <div>
                                <h2 className="font-semibold text-neutral-950">
                                    Operational settings
                                </h2>

                                <p className="mt-1 text-sm leading-6 text-neutral-500">
                                    Control whether this connected courier is available, selected by default, and submitted automatically.
                                </p>
                            </div>
                        </div>

                        {!operationalAccount ? (
                            <div className="mt-6 rounded-lg border border-amber-200 bg-amber-50 p-4">
                                <p className="text-sm font-semibold text-amber-900">
                                    Save the merchant account first
                                </p>

                                <p className="mt-1 text-sm leading-6 text-amber-800">
                                    Configure and save this courier account, then run Test connection before operational controls become available.
                                </p>
                            </div>
                        ) : (
                            <>
                                <div className="mt-6 flex flex-wrap items-center gap-3">
                                    <span
                                        className={[
                                            'inline-flex rounded-full px-2.5 py-1 text-xs font-semibold',
                                            connectionIsVerified
                                                ? 'bg-emerald-100 text-emerald-800'
                                                : 'bg-amber-100 text-amber-800',
                                        ].join(' ')}
                                    >
                                        {operationalAccount.connection_status.label}
                                    </span>

                                    {!connectionIsVerified && (
                                        <span className="text-xs font-medium text-amber-700">
                                            Test connection successfully before enabling the provider.
                                        </span>
                                    )}
                                </div>

                                <div className="mt-6 grid gap-4 lg:grid-cols-3">
                                    <OperationalToggle
                                        id="provider-active"
                                        label="Active"
                                        description="Allow this courier to be selected and used for fulfilment."
                                        checked={
                                            operationalForm.data.is_active
                                        }
                                        onChange={(checked) => {
                                            operationalForm.setData(
                                                'is_active',
                                                checked,
                                            )

                                            if (!checked) {
                                                operationalForm.setData(
                                                    'is_default',
                                                    false,
                                                )

                                                operationalForm.setData(
                                                    'auto_submit',
                                                    false,
                                                )
                                            }
                                        }}
                                        disabled={
                                            !connectionIsVerified &&
                                            !operationalForm.data.is_active
                                        }
                                    />

                                    <OperationalToggle
                                        id="provider-default"
                                        label="Default provider"
                                        description="Preselect this courier when creating a new shipment."
                                        checked={
                                            operationalForm.data.is_default
                                        }
                                        onChange={(checked) =>
                                            operationalForm.setData(
                                                'is_default',
                                                checked,
                                            )
                                        }
                                        disabled={
                                            !operationalForm.data.is_active
                                        }
                                    />

                                    <OperationalToggle
                                        id="provider-auto-submit"
                                        label="Auto submit"
                                        description="Submit new shipments to this courier automatically after creation."
                                        checked={
                                            operationalForm.data.auto_submit
                                        }
                                        onChange={(checked) =>
                                            operationalForm.setData(
                                                'auto_submit',
                                                checked,
                                            )
                                        }
                                        disabled={
                                            !operationalForm.data.is_active
                                        }
                                    />
                                </div>

                                {(operationalForm.errors.is_active ||
                                    operationalForm.errors.is_default ||
                                    operationalForm.errors.auto_submit) && (
                                    <div className="mt-4 rounded-lg border border-red-200 bg-red-50 p-3 text-sm text-red-700">
                                        {operationalForm.errors.is_active && (
                                            <p>{operationalForm.errors.is_active}</p>
                                        )}

                                        {operationalForm.errors.is_default && (
                                            <p>{operationalForm.errors.is_default}</p>
                                        )}

                                        {operationalForm.errors.auto_submit && (
                                            <p>{operationalForm.errors.auto_submit}</p>
                                        )}
                                    </div>
                                )}

                                <div className="mt-6 flex justify-end">
                                    <button
                                        type="submit"
                                        disabled={
                                            operationalForm.processing
                                        }
                                        className="inline-flex min-h-11 items-center rounded-lg bg-neutral-950 px-5 text-sm font-semibold text-white hover:bg-neutral-800 disabled:opacity-50"
                                    >
                                        {operationalForm.processing
                                            ? 'Saving...'
                                            : 'Save operational settings'}
                                    </button>
                                </div>
                            </>
                        )}
                    </form>
                </div>
            </AdminLayout>
        </>
    )
}

function OperationalToggle({
    id,
    label,
    description,
    checked,
    onChange,
    disabled = false,
}: {
    id: string
    label: string
    description: string
    checked: boolean
    onChange: (checked: boolean) => void
    disabled?: boolean
}) {
    return (
        <label
            htmlFor={id}
            className={[
                'flex cursor-pointer items-start gap-3 rounded-xl border p-4 transition',
                checked
                    ? 'border-neutral-400 bg-neutral-50'
                    : 'border-neutral-200 bg-white',
                disabled
                    ? 'cursor-not-allowed opacity-60'
                    : 'hover:border-neutral-300',
            ].join(' ')}
        >
            <input
                id={id}
                type="checkbox"
                checked={checked}
                disabled={disabled}
                onChange={(event) =>
                    onChange(
                        event.target.checked,
                    )
                }
                className="mt-0.5 h-4 w-4 rounded border-neutral-300 text-neutral-950 focus:ring-neutral-500"
            />

            <span>
                <span className="block text-sm font-semibold text-neutral-900">
                    {label}
                </span>

                <span className="mt-1 block text-xs leading-5 text-neutral-500">
                    {description}
                </span>
            </span>
        </label>
    )
}

function SecretField({
    id,
    label,
    configured,
    value,
    onChange,
    helper,
    error,
}: {
    id: string
    label: string
    configured: boolean
    value: string
    onChange:
        (value: string) => void
    helper?: string
    error?:
        string
}) {
    return (
        <div>
            <div className="flex items-center justify-between gap-3">
                <label
                    htmlFor={id}
                    className="text-sm font-semibold text-neutral-800"
                >
                    {label}
                </label>

                {configured && (
                    <span className="text-xs font-semibold text-emerald-700">
                        Configured
                    </span>
                )}
            </div>

            <input
                id={id}
                type="password"
                autoComplete="new-password"
                value={value}
                onChange={(
                    event,
                ) =>
                    onChange(
                        event
                            .target
                            .value,
                    )
                }
                placeholder={
                    configured
                        ? 'Leave blank to keep current value'
                        : `Enter ${label}`
                }
                className="mt-2 block w-full rounded-lg border border-neutral-300 px-3 py-2.5"
            />

            {helper && (
                <p className="mt-1 text-xs leading-5 text-neutral-500">
                    {helper}
                </p>
            )}

            {error && (
                <p className="mt-1 text-sm text-red-600">
                    {error}
                </p>
            )}
        </div>
    )
}

function NumberField({
    id,
    label,
    value,
    onChange,
    error,
}: {
    id: string
    label: string

    value:
        | number
        | null

    onChange:
        (
            value:
                | number
                | null,
        ) => void

    error?:
        string
}) {
    return (
        <div>
            <label
                htmlFor={id}
                className="text-sm font-semibold text-neutral-800"
            >
                {label}
            </label>

            <input
                id={id}
                type="number"
                min="1"
                value={
                    value ??
                    ''
                }
                onChange={(
                    event,
                ) =>
                    onChange(
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
                className="mt-2 block w-full rounded-lg border border-neutral-300 px-3 py-2.5"
            />

            {error && (
                <p className="mt-1 text-sm text-red-600">
                    {error}
                </p>
            )}
        </div>
    )
}