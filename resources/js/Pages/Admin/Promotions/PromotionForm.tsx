import { Link, useForm } from '@inertiajs/react'
import type { FormEvent } from 'react'

type DiscountTypeOption = {
    value: string
    label: string
}

export type PromotionFormValues = {
    name: string
    slug: string
    description: string
    discount_type: string
    discount_value: string
    maximum_discount: string
    minimum_subtotal: string
    starts_at: string
    ends_at: string
    is_active: boolean
    is_stackable: boolean
}

type Props = {
    initialValues: PromotionFormValues
    discountTypes: DiscountTypeOption[]
    submitLabel: string
    onSubmit: (
        data: ReturnType<typeof useForm<PromotionFormValues>>,
    ) => void
}

export default function PromotionForm({
    initialValues,
    discountTypes,
    submitLabel,
    onSubmit,
}: Props) {
    const form = useForm<PromotionFormValues>(initialValues)

    const submit = (event: FormEvent) => {
        event.preventDefault()

        onSubmit(form)
    }

    const isPercentage =
        form.data.discount_type === 'percentage'

    return (
        <form
            onSubmit={submit}
            className="space-y-6"
        >
            <div className="grid gap-6 rounded-xl border bg-white p-6 lg:grid-cols-2">
                <Field
                    label="Promotion name"
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
                        placeholder="Black Friday"
                    />
                </Field>

                <Field
                    label="Slug"
                    error={form.errors.slug}
                    hint="Leave blank to generate from the name."
                >
                    <input
                        value={form.data.slug}
                        onChange={(event) =>
                            form.setData(
                                'slug',
                                event.target.value,
                            )
                        }
                        className="w-full rounded-lg border px-3 py-2"
                        placeholder="black-friday"
                    />
                </Field>

                <Field
                    label="Discount type"
                    error={form.errors.discount_type}
                >
                    <select
                        value={form.data.discount_type}
                        onChange={(event) =>
                            form.setData(
                                'discount_type',
                                event.target.value,
                            )
                        }
                        className="w-full rounded-lg border px-3 py-2"
                    >
                        {discountTypes.map((type) => (
                            <option
                                key={type.value}
                                value={type.value}
                            >
                                {type.label}
                            </option>
                        ))}
                    </select>
                </Field>

                <Field
                    label={
                        isPercentage
                            ? 'Discount percentage'
                            : 'Discount amount'
                    }
                    error={form.errors.discount_value}
                    hint={
                        isPercentage
                            ? 'Stored in basis points. Example: 2000 = 20%.'
                            : 'Enter the fixed discount amount.'
                    }
                >
                    <input
                        type="number"
                        min="0"
                        max={
                            isPercentage
                                ? '10000'
                                : undefined
                        }
                        value={form.data.discount_value}
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
                    error={form.errors.maximum_discount}
                    hint="Optional cap, mainly useful for percentage discounts."
                >
                    <input
                        type="number"
                        min="0"
                        value={form.data.maximum_discount}
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
                    error={form.errors.minimum_subtotal}
                >
                    <input
                        type="number"
                        min="0"
                        value={form.data.minimum_subtotal}
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
                            value={form.data.description}
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
                    description="Allow this promotion to be used."
                    checked={form.data.is_active}
                    onChange={(checked) =>
                        form.setData(
                            'is_active',
                            checked,
                        )
                    }
                />

                <Toggle
                    label="Stackable"
                    description="Allow stacking with other eligible discounts."
                    checked={form.data.is_stackable}
                    onChange={(checked) =>
                        form.setData(
                            'is_stackable',
                            checked,
                        )
                    }
                />
            </div>

            <div className="flex flex-wrap justify-end gap-3">
                <Link
                    href={route('admin.promotions.index')}
                    className="rounded-lg border px-4 py-2 text-sm font-medium"
                >
                    Cancel
                </Link>

                <button
                    type="submit"
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
            <span className="text-sm font-semibold text-slate-800">
                {label}
            </span>

            {children}

            {hint && (
                <p className="text-xs text-slate-500">
                    {hint}
                </p>
            )}

            {error && (
                <p className="text-sm text-red-600">
                    {error}
                </p>
            )}
        </label>
    )
}

function Toggle({
    label,
    description,
    checked,
    onChange,
}: {
    label: string
    description: string
    checked: boolean
    onChange: (checked: boolean) => void
}) {
    return (
        <label className="flex items-start gap-3 rounded-lg border p-4">
            <input
                type="checkbox"
                checked={checked}
                onChange={(event) =>
                    onChange(event.target.checked)
                }
                className="mt-1"
            />

            <span>
                <span className="block text-sm font-semibold">
                    {label}
                </span>

                <span className="text-xs text-slate-500">
                    {description}
                </span>
            </span>
        </label>
    )
}