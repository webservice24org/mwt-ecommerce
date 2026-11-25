import AdminLayout from '@/Layouts/Admin/AdminLayout'
import {
    Head,
    useForm,
} from '@inertiajs/react'
import type {
    FormEvent,
    ReactNode,
} from 'react'
import {
    useState,
} from 'react'

type Option = {
    value: string
    label: string
}

type Policy = {
    shipping_markup_enabled: boolean
    shipping_country_codes: string[]
    return_policy_enabled: boolean
    return_applicable_country_codes: string[]
    return_policy_category: string | null
    return_days: number | null
    return_policy_url: string | null
    return_country_code: string | null
    return_method: string | null
    return_fees: string | null
    return_shipping_fee_amount: number | null
    refund_type: string | null
    version: number
}

type Props = {
    policy: Policy
    structuredDataPreview:
        | Record<string, unknown>
        | null
    shippingOmissionReasons: string[]
    history: Array<{
        id: number
        version: number
        after_policy: Record<string, unknown>
        changed_by_admin_id: number | null
        created_at: string
    }>
    options: {
        return_categories: Option[]
        return_methods: Option[]
        return_fees: Option[]
        refund_types: Option[]
    }
}

export default function Policies({
    policy,
    structuredDataPreview,
    shippingOmissionReasons,
    history,
    options,
}: Props) {
    const [
        shippingCountries,
        setShippingCountries,
    ] = useState(
        policy.shipping_country_codes.join(
            ', ',
        ),
    )

    const [
        returnCountries,
        setReturnCountries,
    ] = useState(
        policy.return_applicable_country_codes.join(
            ', ',
        ),
    )

    const form =
        useForm({
            shipping_markup_enabled:
                policy.shipping_markup_enabled,

            shipping_country_codes:
                policy.shipping_country_codes,

            return_policy_enabled:
                policy.return_policy_enabled,

            return_applicable_country_codes:
                policy.return_applicable_country_codes,

            return_policy_category:
                policy.return_policy_category
                ?? '',

            return_days:
                policy.return_days,

            return_policy_url:
                policy.return_policy_url
                ?? '',

            return_country_code:
                policy.return_country_code
                ?? '',

            return_method:
                policy.return_method
                ?? '',

            return_fees:
                policy.return_fees
                ?? '',

            return_shipping_fee_amount:
                policy.return_shipping_fee_amount,

            refund_type:
                policy.refund_type
                ?? '',

            version:
                policy.version,
        })

    const submit = (
        event: FormEvent<HTMLFormElement>,
    ) => {
        event.preventDefault()

        form.transform(
            (
                data,
            ) => ({
                ...data,

                shipping_country_codes:
                    countryCodes(
                        shippingCountries,
                    ),

                return_applicable_country_codes:
                    countryCodes(
                        returnCountries,
                    ),
            }),
        )

        form.put(
            route(
                'admin.seo.merchant.policies.update',
            ),
            {
                preserveScroll:
                    true,
            },
        )
    }

    return (
        <AdminLayout title="">
            <Head title="Merchant shipping & return policy" />

            <form
                onSubmit={submit}
                className="space-y-6"
            >
                <div className="flex flex-col gap-4 lg:flex-row lg:items-start lg:justify-between">
                    <div>
                        <h1 className="text-2xl font-bold tracking-tight text-neutral-950 dark:text-neutral-100">
                            Merchant shipping & return policy
                        </h1>

                        <p className="mt-2 max-w-3xl text-sm leading-6 text-neutral-500 dark:text-neutral-400">
                            Publish only merchant policy facts that the storefront can represent accurately. Checkout shipping remains authoritative.
                        </p>
                    </div>

                    <button
                        type="submit"
                        disabled={
                            form.processing
                        }
                        className="inline-flex h-10 items-center justify-center rounded-lg bg-neutral-950 px-4 text-sm font-semibold text-white disabled:opacity-50 dark:bg-white dark:text-neutral-950"
                    >
                        {form.processing
                            ? 'Saving...'
                            : 'Save policy'}
                    </button>
                </div>

                {form.errors.version && (
                    <div className="rounded-xl border border-red-200 bg-red-50 p-4 text-sm text-red-700 dark:border-red-900/60 dark:bg-red-950/20 dark:text-red-300">
                        {form.errors.version}
                    </div>
                )}

                <div className="grid gap-6 xl:grid-cols-[minmax(0,1fr)_420px]">
                    <main className="space-y-6">
                        <section className="rounded-xl border border-neutral-200 bg-white p-5 shadow-sm dark:border-neutral-800 dark:bg-neutral-950">
                            <label className="flex items-start gap-3">
                                <input
                                    type="checkbox"
                                    checked={
                                        form.data
                                            .shipping_markup_enabled
                                    }
                                    onChange={(
                                        event,
                                    ) =>
                                        form.setData(
                                            'shipping_markup_enabled',
                                            event.target.checked,
                                        )
                                    }
                                    className="mt-1 h-4 w-4 rounded"
                                />

                                <span>
                                    <span className="block font-semibold text-neutral-950 dark:text-neutral-100">
                                        Publish merchant shipping policy markup
                                    </span>

                                    <span className="mt-1 block text-sm leading-6 text-neutral-500 dark:text-neutral-400">
                                        Shipping rates still come from the existing checkout zones/rates. Unsupported city-level or ambiguous rules cause shipping markup to be omitted rather than broadened.
                                    </span>
                                </span>
                            </label>

                            <div className="mt-5">
                                <label className="text-sm font-semibold text-neutral-800 dark:text-neutral-200">
                                    Catch-all shipping country codes
                                </label>

                                <input
                                    value={
                                        shippingCountries
                                    }
                                    onChange={(
                                        event,
                                    ) =>
                                        setShippingCountries(
                                            event.target.value,
                                        )
                                    }
                                    placeholder="BD"
                                    className="mt-2 block w-full rounded-lg border border-neutral-300 bg-white px-3 py-2.5 text-sm dark:border-neutral-700 dark:bg-neutral-950"
                                />

                                <p className="mt-2 text-xs leading-5 text-neutral-500">
                                    Comma-separated ISO 3166-1 alpha-2 codes. Used only to safely scope catch-all zones; never implies worldwide shipping.
                                </p>
                            </div>

                            {shippingOmissionReasons.length >
                                0 && (
                                <div className="mt-5 rounded-lg border border-amber-200 bg-amber-50 p-4 dark:border-amber-900/60 dark:bg-amber-950/20">
                                    <p className="text-sm font-semibold text-amber-900 dark:text-amber-100">
                                        Shipping markup currently omitted
                                    </p>

                                    <ul className="mt-2 list-disc space-y-1 pl-5 text-sm text-amber-800 dark:text-amber-300">
                                        {shippingOmissionReasons.map(
                                            (
                                                reason,
                                            ) => (
                                                <li
                                                    key={
                                                        reason
                                                    }
                                                >
                                                    {
                                                        reason
                                                    }
                                                </li>
                                            ),
                                        )}
                                    </ul>
                                </div>
                            )}
                        </section>

                        <section className="rounded-xl border border-neutral-200 bg-white p-5 shadow-sm dark:border-neutral-800 dark:bg-neutral-950">
                            <label className="flex items-start gap-3">
                                <input
                                    type="checkbox"
                                    checked={
                                        form.data
                                            .return_policy_enabled
                                    }
                                    onChange={(
                                        event,
                                    ) =>
                                        form.setData(
                                            'return_policy_enabled',
                                            event.target.checked,
                                        )
                                    }
                                    className="mt-1 h-4 w-4 rounded"
                                />

                                <span>
                                    <span className="block font-semibold text-neutral-950 dark:text-neutral-100">
                                        Publish merchant return policy markup
                                    </span>

                                    <span className="mt-1 block text-sm leading-6 text-neutral-500 dark:text-neutral-400">
                                        Enter the actual customer policy. No return window, fee, method, refund type, or country is inferred.
                                    </span>
                                </span>
                            </label>

                            <div className="mt-5 grid gap-4 md:grid-cols-2">
                                <Field
                                    label="Applicable countries"
                                    error={
                                        form.errors
                                            .return_applicable_country_codes
                                    }
                                >
                                    <input
                                        value={
                                            returnCountries
                                        }
                                        onChange={(
                                            event,
                                        ) =>
                                            setReturnCountries(
                                                event.target.value,
                                            )
                                        }
                                        placeholder="BD"
                                        className={inputClass}
                                    />
                                </Field>

                                <Field
                                    label="Return destination country"
                                    error={
                                        form.errors
                                            .return_country_code
                                    }
                                >
                                    <input
                                        value={
                                            form.data
                                                .return_country_code
                                        }
                                        onChange={(
                                            event,
                                        ) =>
                                            form.setData(
                                                'return_country_code',
                                                event.target.value.toUpperCase(),
                                            )
                                        }
                                        maxLength={
                                            2
                                        }
                                        placeholder="BD"
                                        className={inputClass}
                                    />
                                </Field>

                                <Field
                                    label="Return policy category"
                                    error={
                                        form.errors
                                            .return_policy_category
                                    }
                                >
                                    <select
                                        value={
                                            form.data
                                                .return_policy_category
                                        }
                                        onChange={(
                                            event,
                                        ) =>
                                            form.setData(
                                                'return_policy_category',
                                                event.target.value,
                                            )
                                        }
                                        className={inputClass}
                                    >
                                        <option value="">
                                            Select
                                        </option>

                                        {options.return_categories.map(
                                            (
                                                option,
                                            ) => (
                                                <option
                                                    key={
                                                        option.value
                                                    }
                                                    value={
                                                        option.value
                                                    }
                                                >
                                                    {
                                                        option.label
                                                    }
                                                </option>
                                            ),
                                        )}
                                    </select>
                                </Field>

                                <Field
                                    label="Return days"
                                    error={
                                        form.errors
                                            .return_days
                                    }
                                >
                                    <input
                                        type="number"
                                        min={
                                            1
                                        }
                                        value={
                                            form.data
                                                .return_days
                                            ?? ''
                                        }
                                        onChange={(
                                            event,
                                        ) =>
                                            form.setData(
                                                'return_days',
                                                event.target.value
                                                    ? Number(
                                                          event.target.value,
                                                      )
                                                    : null,
                                            )
                                        }
                                        disabled={
                                            form.data
                                                .return_policy_category !==
                                            'finite'
                                        }
                                        className={inputClass}
                                    />
                                </Field>

                                <Field
                                    label="Return method"
                                    error={
                                        form.errors
                                            .return_method
                                    }
                                >
                                    <select
                                        value={
                                            form.data
                                                .return_method
                                        }
                                        onChange={(
                                            event,
                                        ) =>
                                            form.setData(
                                                'return_method',
                                                event.target.value,
                                            )
                                        }
                                        className={inputClass}
                                    >
                                        <option value="">
                                            Not specified
                                        </option>

                                        {options.return_methods.map(
                                            (
                                                option,
                                            ) => (
                                                <option
                                                    key={
                                                        option.value
                                                    }
                                                    value={
                                                        option.value
                                                    }
                                                >
                                                    {
                                                        option.label
                                                    }
                                                </option>
                                            ),
                                        )}
                                    </select>
                                </Field>

                                <Field
                                    label="Return fees"
                                    error={
                                        form.errors
                                            .return_fees
                                    }
                                >
                                    <select
                                        value={
                                            form.data
                                                .return_fees
                                        }
                                        onChange={(
                                            event,
                                        ) =>
                                            form.setData(
                                                'return_fees',
                                                event.target.value,
                                            )
                                        }
                                        className={inputClass}
                                    >
                                        <option value="">
                                            Not specified
                                        </option>

                                        {options.return_fees.map(
                                            (
                                                option,
                                            ) => (
                                                <option
                                                    key={
                                                        option.value
                                                    }
                                                    value={
                                                        option.value
                                                    }
                                                >
                                                    {
                                                        option.label
                                                    }
                                                </option>
                                            ),
                                        )}
                                    </select>
                                </Field>

                                <Field
                                    label="Return shipping fee (BDT)"
                                    error={
                                        form.errors
                                            .return_shipping_fee_amount
                                    }
                                >
                                    <input
                                        type="number"
                                        min={
                                            0.01
                                        }
                                        step={
                                            0.01
                                        }
                                        value={
                                            minorToMajor(
                                                form.data
                                                    .return_shipping_fee_amount,
                                            )
                                        }
                                        onChange={(
                                            event,
                                        ) =>
                                            form.setData(
                                                'return_shipping_fee_amount',
                                                majorToMinor(
                                                    event.target.value,
                                                ),
                                            )
                                        }
                                        disabled={
                                            form.data
                                                .return_fees !==
                                            'shipping_fee'
                                        }
                                        className={inputClass}
                                    />
                                </Field>

                                <Field
                                    label="Refund type"
                                    error={
                                        form.errors
                                            .refund_type
                                    }
                                >
                                    <select
                                        value={
                                            form.data
                                                .refund_type
                                        }
                                        onChange={(
                                            event,
                                        ) =>
                                            form.setData(
                                                'refund_type',
                                                event.target.value,
                                            )
                                        }
                                        className={inputClass}
                                    >
                                        <option value="">
                                            Not specified
                                        </option>

                                        {options.refund_types.map(
                                            (
                                                option,
                                            ) => (
                                                <option
                                                    key={
                                                        option.value
                                                    }
                                                    value={
                                                        option.value
                                                    }
                                                >
                                                    {
                                                        option.label
                                                    }
                                                </option>
                                            ),
                                        )}
                                    </select>
                                </Field>
                            </div>

                            <Field
                                label="Customer-facing return policy URL"
                                error={
                                    form.errors
                                        .return_policy_url
                                }
                            >
                                <input
                                    type="url"
                                    value={
                                        form.data
                                            .return_policy_url
                                    }
                                    onChange={(
                                        event,
                                    ) =>
                                        form.setData(
                                            'return_policy_url',
                                            event.target.value,
                                        )
                                    }
                                    placeholder="https://example.com/pages/returns"
                                    className={inputClass}
                                />
                            </Field>
                        </section>
                    </main>

                    <aside className="space-y-6 xl:sticky xl:top-6 xl:self-start">
                        <section className="rounded-xl border border-neutral-200 bg-white p-5 shadow-sm dark:border-neutral-800 dark:bg-neutral-950">
                            <h2 className="font-semibold text-neutral-950 dark:text-neutral-100">
                                JSON-LD preview
                            </h2>

                            <pre className="mt-4 max-h-[500px] overflow-auto rounded-lg bg-neutral-950 p-4 text-xs leading-5 text-neutral-100">
                                {structuredDataPreview
                                    ? JSON.stringify(
                                          structuredDataPreview,
                                          null,
                                          2,
                                      )
                                    : 'No merchant policy structured data is currently publishable.'}
                            </pre>
                        </section>

                        <section className="rounded-xl border border-neutral-200 bg-white p-5 shadow-sm dark:border-neutral-800 dark:bg-neutral-950">
                            <h2 className="font-semibold text-neutral-950 dark:text-neutral-100">
                                History
                            </h2>

                            <p className="mt-1 text-xs text-neutral-500">
                                Current version: {
                                    policy.version
                                }
                            </p>

                            <div className="mt-4 space-y-3">
                                {history.length ===
                                0 ? (
                                    <p className="text-sm text-neutral-500">
                                        No policy changes yet.
                                    </p>
                                ) : (
                                    history.map(
                                        (
                                            event,
                                        ) => (
                                            <div
                                                key={
                                                    event.id
                                                }
                                                className="rounded-lg border border-neutral-200 p-3 text-xs dark:border-neutral-800"
                                            >
                                                Version {
                                                    event.version
                                                } · {
                                                    new Date(
                                                        event.created_at,
                                                    ).toLocaleString()
                                                }
                                            </div>
                                        ),
                                    )
                                )}
                            </div>
                        </section>
                    </aside>
                </div>
            </form>
        </AdminLayout>
    )
}

const inputClass =
    'mt-2 block w-full rounded-lg border border-neutral-300 bg-white px-3 py-2.5 text-sm dark:border-neutral-700 dark:bg-neutral-950'

function Field({
    label,
    error,
    children,
}: {
    label: string
    error?: string
    children: ReactNode
}) {
    return (
        <label className="block">
            <span className="text-sm font-semibold text-neutral-800 dark:text-neutral-200">
                {label}
            </span>

            {children}

            {error && (
                <span className="mt-1 block text-xs text-red-600">
                    {error}
                </span>
            )}
        </label>
    )
}

function countryCodes(
    value: string,
): string[] {
    return Array.from(
        new Set(
            value
                .split(',')
                .map(
                    (
                        item,
                    ) =>
                        item
                            .trim()
                            .toUpperCase(),
                )
                .filter(
                    (
                        item,
                    ) =>
                        /^[A-Z]{2}$/.test(
                            item,
                        ),
                ),
        ),
    )
}


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
        !Number.isFinite(
            parsed,
        )
        || parsed < 0
    ) {
        return null
    }

    return Math.round(
        parsed * 100,
    )
}
