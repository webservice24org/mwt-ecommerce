import type { ReactNode } from 'react'

import type { CustomerAddressFormData } from '@/types/customer-address'

interface Props {
    data: CustomerAddressFormData
    errors: Partial<Record<keyof CustomerAddressFormData, string>>
    processing: boolean
    submitLabel: string
    showDefaultOption?: boolean
    onChange: <K extends keyof CustomerAddressFormData>(
        field: K,
        value: CustomerAddressFormData[K],
    ) => void
}

const inputClass =
    'mt-1 block w-full rounded-xl border border-neutral-300 bg-white px-3 py-2.5 text-sm text-neutral-900 shadow-sm outline-none transition placeholder:text-neutral-400 focus:border-neutral-900 focus:ring-1 focus:ring-neutral-900'

export default function AddressForm({
    data,
    errors,
    processing,
    submitLabel,
    showDefaultOption = true,
    onChange,
}: Props) {
    return (
        <div className="space-y-6">
            <section className="rounded-2xl border border-neutral-200 bg-white p-5 shadow-sm sm:p-6">
                <div className="mb-6">
                    <h3 className="text-lg font-bold text-neutral-950">Delivery details</h3>

                    <p className="mt-1 text-sm text-neutral-500">
                        Enter the address where your orders should be delivered.
                    </p>
                </div>

                <div className="grid gap-5 sm:grid-cols-2">
                    <Field label="Address label" error={errors.label}>
                        <input
                            type="text"
                            value={data.label}
                            onChange={(event) => onChange('label', event.target.value)}
                            className={inputClass}
                            placeholder="Home, Office..."
                            maxLength={50}
                        />
                    </Field>

                    <Field label="Recipient name" required error={errors.recipient_name}>
                        <input
                            type="text"
                            value={data.recipient_name}
                            onChange={(event) => onChange('recipient_name', event.target.value)}
                            className={inputClass}
                            autoComplete="name"
                            maxLength={150}
                        />
                    </Field>

                    <Field label="Phone" required error={errors.phone}>
                        <input
                            type="tel"
                            value={data.phone}
                            onChange={(event) => onChange('phone', event.target.value)}
                            className={inputClass}
                            autoComplete="tel"
                            maxLength={30}
                        />
                    </Field>

                    <Field label="Country code" required error={errors.country_code}>
                        <input
                            type="text"
                            value={data.country_code}
                            onChange={(event) =>
                                onChange(
                                    'country_code',
                                    event.target.value.toUpperCase().slice(0, 2),
                                )
                            }
                            className={inputClass}
                            autoComplete="country"
                            maxLength={2}
                            placeholder="BD"
                        />
                    </Field>

                    <div className="sm:col-span-2">
                        <Field label="Address line 1" required error={errors.address_line_1}>
                            <input
                                type="text"
                                value={data.address_line_1}
                                onChange={(event) => onChange('address_line_1', event.target.value)}
                                className={inputClass}
                                autoComplete="address-line1"
                                maxLength={255}
                            />
                        </Field>
                    </div>

                    <div className="sm:col-span-2">
                        <Field label="Address line 2" error={errors.address_line_2}>
                            <input
                                type="text"
                                value={data.address_line_2}
                                onChange={(event) => onChange('address_line_2', event.target.value)}
                                className={inputClass}
                                autoComplete="address-line2"
                                maxLength={255}
                                placeholder="Apartment, suite, floor..."
                            />
                        </Field>
                    </div>

                    <Field label="City" required error={errors.city}>
                        <input
                            type="text"
                            value={data.city}
                            onChange={(event) => onChange('city', event.target.value)}
                            className={inputClass}
                            autoComplete="address-level2"
                            maxLength={120}
                        />
                    </Field>

                    <Field label="State / Region" error={errors.state_or_region}>
                        <input
                            type="text"
                            value={data.state_or_region}
                            onChange={(event) => onChange('state_or_region', event.target.value)}
                            className={inputClass}
                            autoComplete="address-level1"
                            maxLength={120}
                        />
                    </Field>

                    <Field label="Postal code" error={errors.postal_code}>
                        <input
                            type="text"
                            value={data.postal_code}
                            onChange={(event) => onChange('postal_code', event.target.value)}
                            className={inputClass}
                            autoComplete="postal-code"
                            maxLength={30}
                        />
                    </Field>
                </div>
            </section>

            {showDefaultOption && (
                <section className="rounded-2xl border border-neutral-200 bg-white p-5 shadow-sm sm:p-6">
                    <label className="flex cursor-pointer items-start gap-3">
                        <input
                            type="checkbox"
                            checked={data.is_default}
                            onChange={(event) => onChange('is_default', event.target.checked)}
                            className="mt-1 h-4 w-4 rounded border-neutral-300 text-neutral-950 focus:ring-neutral-900"
                        />

                        <span>
                            <span className="block text-sm font-semibold text-neutral-900">
                                Use as default delivery address
                            </span>

                            <span className="mt-1 block text-sm leading-6 text-neutral-500">
                                This address will be selected automatically during checkout.
                            </span>
                        </span>
                    </label>

                    {errors.is_default && (
                        <p className="mt-2 text-sm text-red-600">{errors.is_default}</p>
                    )}
                </section>
            )}

            <div className="flex justify-end">
                <button
                    type="submit"
                    disabled={processing}
                    className="inline-flex min-h-11 items-center justify-center rounded-xl bg-neutral-950 px-5 text-sm font-semibold text-white transition hover:bg-neutral-800 disabled:cursor-not-allowed disabled:opacity-50"
                >
                    {processing ? 'Saving...' : submitLabel}
                </button>
            </div>
        </div>
    )
}

function Field({
    label,
    required = false,
    error,
    children,
}: {
    label: string
    required?: boolean
    error?: string
    children: ReactNode
}) {
    return (
        <label className="block">
            <span className="text-sm font-semibold text-neutral-700">
                {label}

                {required && (
                    <span className="ml-1 text-red-600" aria-hidden="true">
                        *
                    </span>
                )}
            </span>

            {children}

            {error && <span className="mt-1 block text-sm text-red-600">{error}</span>}
        </label>
    )
}
