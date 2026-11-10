import { Link, useForm } from '@inertiajs/react'
import type { FormEvent } from 'react'

type PromotionOption = {
    id: number
    name: string
}

type DiscountTypeOption = {
    value: string
    label: string
}

export type PromoCodeFormValues = {
    promotion_id: string
    code: string
    name: string
    description: string
    discount_type: string
    discount_value: string
    maximum_discount: string
    minimum_subtotal: string
    usage_limit: string
    usage_limit_per_customer: string
    starts_at: string
    ends_at: string
    is_active: boolean
    is_stackable: boolean
}

type Props = {
    initialValues: PromoCodeFormValues
    promotions: PromotionOption[]
    discountTypes: DiscountTypeOption[]
    submitLabel: string
    redeemedCount?: number
    onSubmit: (
        form: ReturnType<
            typeof useForm<PromoCodeFormValues>
        >,
    ) => void
}

export default function PromoCodeForm({
    initialValues,
    promotions,
    discountTypes,
    submitLabel,
    redeemedCount,
    onSubmit,
}: Props) {
    const form =
        useForm<PromoCodeFormValues>(
            initialValues,
        )

    const inherits =
        form.data.promotion_id !== ''

    const submit = (
        event: FormEvent,
    ) => {
        event.preventDefault()
        onSubmit(form)
    }

    return (
        <form
            onSubmit={submit}
            className="space-y-6"
        >
            <div className="grid gap-6 rounded-xl border bg-white p-6 lg:grid-cols-2">
                <Field
                    label="Promo code"
                    error={form.errors.code}
                >
                    <input
                        value={form.data.code}
                        onChange={(event) =>
                            form.setData(
                                'code',
                                event.target.value.toUpperCase(),
                            )
                        }
                        className="w-full rounded-lg border px-3 py-2 font-mono uppercase"
                        placeholder="SAVE20"
                    />
                </Field>

                <Field
                    label="Name"
                    error={form.errors.name}
                >
                    <input
                        value={form.data.name}
                        onChange={(event) =>
                            form.setData(
                                'name',
                                event.target.value,
                            )
                        }
                        className="w-full rounded-lg border px-3 py-2"
                    />
                </Field>

                <Field
                    label="Parent promotion"
                    error={form.errors.promotion_id}
                    hint="Optional. Leave blank for a standalone promo code."
                >
                    <select
                        value={
                            form.data.promotion_id
                        }
                        onChange={(event) =>
                            form.setData(
                                'promotion_id',
                                event.target.value,
                            )
                        }
                        className="w-full rounded-lg border px-3 py-2"
                    >
                        <option value="">
                            Standalone promo code
                        </option>

                        {promotions.map(
                            (promotion) => (
                                <option
                                    key={
                                        promotion.id
                                    }
                                    value={
                                        promotion.id
                                    }
                                >
                                    {
                                        promotion.name
                                    }
                                </option>
                            ),
                        )}
                    </select>
                </Field>

                {redeemedCount !== undefined && (
                    <Field label="Redeemed">
                        <input
                            disabled
                            value={redeemedCount}
                            className="w-full rounded-lg border bg-slate-50 px-3 py-2"
                        />
                    </Field>
                )}

                <Field
                    label="Discount type"
                    error={form.errors.discount_type}
                    hint={
                        inherits
                            ? 'Optional. Leave blank to inherit from the parent promotion.'
                            : 'Required for standalone promo codes.'
                    }
                >
                    <select
                        value={
                            form.data.discount_type
                        }
                        onChange={(event) =>
                            form.setData(
                                'discount_type',
                                event.target.value,
                            )
                        }
                        className="w-full rounded-lg border px-3 py-2"
                    >
                        {inherits && (
                            <option value="">
                                Inherit
                            </option>
                        )}

                        {discountTypes.map(
                            (type) => (
                                <option
                                    key={
                                        type.value
                                    }
                                    value={
                                        type.value
                                    }
                                >
                                    {
                                        type.label
                                    }
                                </option>
                            ),
                        )}
                    </select>
                </Field>

                <Field
                    label="Discount value"
                    error={form.errors.discount_value}
                    hint={
                        inherits
                            ? 'Leave blank with discount type to inherit.'
                            : form.data.discount_type ===
                                'percentage'
                              ? '2000 = 20%'
                              : undefined
                    }
                >
                    <input
                        type="number"
                        min="0"
                        value={
                            form.data.discount_value
                        }
                        onChange={(event) =>
                            form.setData(
                                'discount_value',
                                event.target.value,
                            )
                        }
                        className="w-full rounded-lg border px-3 py-2"
                    />
                </Field>

                <Field
                    label="Maximum discount"
                    error={
                        form.errors.maximum_discount
                    }
                >
                    <input
                        type="number"
                        min="0"
                        value={
                            form.data.maximum_discount
                        }
                        onChange={(event) =>
                            form.setData(
                                'maximum_discount',
                                event.target.value,
                            )
                        }
                        className="w-full rounded-lg border px-3 py-2"
                    />
                </Field>

                <Field
                    label="Minimum subtotal"
                    error={
                        form.errors.minimum_subtotal
                    }
                >
                    <input
                        type="number"
                        min="0"
                        value={
                            form.data.minimum_subtotal
                        }
                        onChange={(event) =>
                            form.setData(
                                'minimum_subtotal',
                                event.target.value,
                            )
                        }
                        className="w-full rounded-lg border px-3 py-2"
                    />
                </Field>

                <Field
                    label="Global usage limit"
                    error={form.errors.usage_limit}
                >
                    <input
                        type="number"
                        min="1"
                        value={
                            form.data.usage_limit
                        }
                        onChange={(event) =>
                            form.setData(
                                'usage_limit',
                                event.target.value,
                            )
                        }
                        className="w-full rounded-lg border px-3 py-2"
                    />
                </Field>

                <Field
                    label="Per-customer usage limit"
                    error={
                        form.errors
                            .usage_limit_per_customer
                    }
                >
                    <input
                        type="number"
                        min="1"
                        value={
                            form.data
                                .usage_limit_per_customer
                        }
                        onChange={(event) =>
                            form.setData(
                                'usage_limit_per_customer',
                                event.target.value,
                            )
                        }
                        className="w-full rounded-lg border px-3 py-2"
                    />
                </Field>

                <Field
                    label="Starts at"
                    error={form.errors.starts_at}
                >
                    <input
                        type="datetime-local"
                        value={form.data.starts_at}
                        onChange={(event) =>
                            form.setData(
                                'starts_at',
                                event.target.value,
                            )
                        }
                        className="w-full rounded-lg border px-3 py-2"
                    />
                </Field>

                <Field
                    label="Ends at"
                    error={form.errors.ends_at}
                >
                    <input
                        type="datetime-local"
                        value={form.data.ends_at}
                        onChange={(event) =>
                            form.setData(
                                'ends_at',
                                event.target.value,
                            )
                        }
                        className="w-full rounded-lg border px-3 py-2"
                    />
                </Field>

                <div className="lg:col-span-2">
                    <Field
                        label="Description"
                        error={form.errors.description}
                    >
                        <textarea
                            rows={5}
                            value={
                                form.data.description
                            }
                            onChange={(event) =>
                                form.setData(
                                    'description',
                                    event.target.value,
                                )
                            }
                            className="w-full rounded-lg border px-3 py-2"
                        />
                    </Field>
                </div>

                <Toggle
                    label="Active"
                    checked={
                        form.data.is_active
                    }
                    onChange={(checked) =>
                        form.setData(
                            'is_active',
                            checked,
                        )
                    }
                />

                <Toggle
                    label="Stackable"
                    checked={
                        form.data.is_stackable
                    }
                    onChange={(checked) =>
                        form.setData(
                            'is_stackable',
                            checked,
                        )
                    }
                />
            </div>

            <div className="flex justify-end gap-3">
                <Link
                    href={route(
                        'admin.promo-codes.index',
                    )}
                    className="rounded-lg border px-4 py-2 text-sm font-medium"
                >
                    Cancel
                </Link>

                <button
                    disabled={form.processing}
                    className="rounded-lg bg-slate-900 px-4 py-2 text-sm font-semibold text-white disabled:opacity-50"
                >
                    {form.processing
                        ? 'Saving...'
                        : submitLabel}
                </button>
            </div>
        </form>
    )
}

function Field({
    label,
    error,
    hint,
    children,
}: {
    label: string
    error?: string
    hint?: string
    children: React.ReactNode
}) {
    return (
        <label className="block space-y-2">
            <span className="text-sm font-semibold">
                {label}
            </span>

            {children}

            {hint && (
                <div className="text-xs text-slate-500">
                    {hint}
                </div>
            )}

            {error && (
                <div className="text-sm text-red-600">
                    {error}
                </div>
            )}
        </label>
    )
}

function Toggle({
    label,
    checked,
    onChange,
}: {
    label: string
    checked: boolean
    onChange: (checked: boolean) => void
}) {
    return (
        <label className="flex items-center gap-3 rounded-lg border p-4">
            <input
                type="checkbox"
                checked={checked}
                onChange={(event) =>
                    onChange(
                        event.target.checked,
                    )
                }
            />

            <span className="text-sm font-semibold">
                {label}
            </span>
        </label>
    )
}