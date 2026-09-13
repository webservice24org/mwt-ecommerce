import ConfirmDialog from '@/Components/Admin/ConfirmDialog'
import type { ProductVariant, VariantAttributeOption } from '@/types/catalog'
import { router, useForm } from '@inertiajs/react'
import { CheckCircle2, Edit3, PackagePlus, Trash2, X } from 'lucide-react'
import { useMemo, useState } from 'react'
import type { FormEvent, ReactNode } from 'react'

type Props = {
    productId: number
    variants: ProductVariant[]
    attributes: VariantAttributeOption[]
}

type VariantFormData = {
    sku: string
    name: string
    price: string
    compare_at_price: string
    cost_price: string
    barcode: string
    position: number
    is_active: boolean
    is_default: boolean
    weight: string
    attribute_value_ids: number[]
}

const emptyForm: VariantFormData = {
    sku: '',
    name: '',
    price: '',
    compare_at_price: '',
    cost_price: '',
    barcode: '',
    position: 0,
    is_active: true,
    is_default: false,
    weight: '',
    attribute_value_ids: [],
}

function minorToMajor(value: number | null): string {
    if (value === null) {
        return ''
    }

    return (value / 100).toFixed(2)
}

function majorToMinor(value: string): number | null {
    const normalized = value.trim()

    if (normalized === '') {
        return null
    }

    const number = Number(normalized)

    if (!Number.isFinite(number)) {
        return null
    }

    return Math.round(number * 100)
}

export default function ProductVariantsPanel({ productId, variants, attributes }: Props) {
    const [editingVariant, setEditingVariant] = useState<ProductVariant | null>(null)

    const [variantToDelete, setVariantToDelete] = useState<ProductVariant | null>(null)

    const [formOpen, setFormOpen] = useState(false)

    const form = useForm<VariantFormData>({
        ...emptyForm,
    })

    const selectedValueSet = useMemo(
        () => new Set(form.data.attribute_value_ids),
        [form.data.attribute_value_ids],
    )

    const openCreate = () => {
        setEditingVariant(null)

        form.setData({
            ...emptyForm,
            position: variants.length,
            is_default: variants.length === 0,
        })

        form.clearErrors()
        setFormOpen(true)
    }

    const openEdit = (variant: ProductVariant) => {
        setEditingVariant(variant)

        form.setData({
            sku: variant.sku,
            name: variant.name ?? '',
            price: minorToMajor(variant.price),
            compare_at_price: minorToMajor(variant.compare_at_price),
            cost_price: minorToMajor(variant.cost_price),
            barcode: variant.barcode ?? '',
            position: variant.position,
            is_active: variant.is_active,
            is_default: variant.is_default,
            weight: variant.weight ?? '',
            attribute_value_ids: variant.attribute_values.map((value) => value.id),
        })

        form.clearErrors()
        setFormOpen(true)
    }

    const closeForm = () => {
        setFormOpen(false)
        setEditingVariant(null)
        form.clearErrors()
        form.reset()
    }

    const setAttributeValue = (attribute: VariantAttributeOption, rawValue: string) => {
        const valueId = rawValue === '' ? null : Number(rawValue)

        const idsForThisAttribute = new Set(attribute.values.map((value) => value.id))

        const withoutCurrent = form.data.attribute_value_ids.filter(
            (id) => !idsForThisAttribute.has(id),
        )

        form.setData(
            'attribute_value_ids',
            valueId === null ? withoutCurrent : [...withoutCurrent, valueId],
        )
    }

    const selectedValueForAttribute = (attribute: VariantAttributeOption): number | '' => {
        const selected = attribute.values.find((value) => selectedValueSet.has(value.id))

        return selected?.id ?? ''
    }

    const submit = (event: FormEvent) => {
        event.preventDefault()

        const price = majorToMinor(form.data.price)

        const compareAtPrice = majorToMinor(form.data.compare_at_price)

        const costPrice = majorToMinor(form.data.cost_price)

        form.transform((data) => ({
            ...data,

            price: price === null ? data.price : price,

            compare_at_price: data.compare_at_price.trim() === '' ? null : compareAtPrice,

            cost_price: data.cost_price.trim() === '' ? null : costPrice,

            name: data.name.trim() || null,

            barcode: data.barcode.trim() || null,

            weight: data.weight.trim() || null,
        }))

        if (editingVariant) {
            form.put(route('admin.products.variants.update', [productId, editingVariant.id]), {
                preserveScroll: true,
                onSuccess: closeForm,
            })

            return
        }

        form.post(route('admin.products.variants.store', productId), {
            preserveScroll: true,
            onSuccess: closeForm,
        })
    }

    const destroyVariant = () => {
        if (!variantToDelete) {
            return
        }

        router.delete(route('admin.products.variants.destroy', [productId, variantToDelete.id]), {
            preserveScroll: true,
            onSuccess: () => setVariantToDelete(null),
        })
    }

    return (
        <section className="rounded-xl border border-neutral-200 bg-white shadow-sm">
            <div className="flex flex-wrap items-center justify-between gap-3 border-b border-neutral-200 px-5 py-4">
                <div>
                    <h2 className="font-semibold text-neutral-900">Product Variants</h2>

                    <p className="mt-1 text-sm text-neutral-500">
                        Manage SKU, pricing and attribute combinations.
                    </p>
                </div>

                <button
                    type="button"
                    onClick={openCreate}
                    className="inline-flex items-center gap-2 rounded-lg bg-neutral-900 px-4 py-2.5 text-sm font-medium text-white transition hover:bg-neutral-800"
                >
                    <PackagePlus className="h-4 w-4" />
                    Add Variant
                </button>
            </div>

            {formOpen && (
                <form
                    onSubmit={submit}
                    className="border-b border-neutral-200 bg-neutral-50/60 p-5"
                >
                    <div className="mb-5 flex items-center justify-between gap-4">
                        <div>
                            <h3 className="font-semibold text-neutral-900">
                                {editingVariant ? `Edit ${editingVariant.sku}` : 'New Variant'}
                            </h3>

                            <p className="mt-1 text-sm text-neutral-500">
                                Prices are entered in normal currency units.
                            </p>
                        </div>

                        <button
                            type="button"
                            onClick={closeForm}
                            className="rounded-lg p-2 text-neutral-500 hover:bg-neutral-200"
                        >
                            <X className="h-4 w-4" />
                        </button>
                    </div>

                    <div className="grid gap-5 md:grid-cols-2 xl:grid-cols-3">
                        <Field label="SKU" required error={form.errors.sku}>
                            <input
                                value={form.data.sku}
                                onChange={(event) => form.setData('sku', event.target.value)}
                                className={inputClass}
                            />
                        </Field>

                        <Field label="Variant name" error={form.errors.name}>
                            <input
                                value={form.data.name}
                                onChange={(event) => form.setData('name', event.target.value)}
                                placeholder="Optional"
                                className={inputClass}
                            />
                        </Field>

                        <Field label="Barcode" error={form.errors.barcode}>
                            <input
                                value={form.data.barcode}
                                onChange={(event) => form.setData('barcode', event.target.value)}
                                className={inputClass}
                            />
                        </Field>

                        <Field label="Price" required error={form.errors.price}>
                            <input
                                type="number"
                                min="0"
                                step="0.01"
                                value={form.data.price}
                                onChange={(event) => form.setData('price', event.target.value)}
                                className={inputClass}
                            />
                        </Field>

                        <Field label="Compare-at price" error={form.errors.compare_at_price}>
                            <input
                                type="number"
                                min="0"
                                step="0.01"
                                value={form.data.compare_at_price}
                                onChange={(event) =>
                                    form.setData('compare_at_price', event.target.value)
                                }
                                className={inputClass}
                            />
                        </Field>

                        <Field label="Cost price" error={form.errors.cost_price}>
                            <input
                                type="number"
                                min="0"
                                step="0.01"
                                value={form.data.cost_price}
                                onChange={(event) => form.setData('cost_price', event.target.value)}
                                className={inputClass}
                            />
                        </Field>

                        <Field label="Weight" error={form.errors.weight}>
                            <input
                                type="number"
                                min="0"
                                step="0.001"
                                value={form.data.weight}
                                onChange={(event) => form.setData('weight', event.target.value)}
                                className={inputClass}
                            />
                        </Field>

                        <Field label="Position" error={form.errors.position}>
                            <input
                                type="number"
                                min="0"
                                value={form.data.position}
                                onChange={(event) =>
                                    form.setData('position', Number(event.target.value))
                                }
                                className={inputClass}
                            />
                        </Field>
                    </div>

                    {attributes.length > 0 && (
                        <div className="mt-6">
                            <h4 className="mb-3 text-sm font-semibold text-neutral-900">
                                Attributes
                            </h4>

                            <div className="grid gap-4 md:grid-cols-2 xl:grid-cols-3">
                                {attributes.map((attribute) => {
                                    const current = selectedValueForAttribute(attribute)

                                    return (
                                        <Field key={attribute.id} label={attribute.name}>
                                            <select
                                                value={current}
                                                onChange={(event) =>
                                                    setAttributeValue(attribute, event.target.value)
                                                }
                                                className={inputClass}
                                            >
                                                <option value="">No value</option>

                                                {attribute.values.map((value) => (
                                                    <option
                                                        key={value.id}
                                                        value={value.id}
                                                        disabled={
                                                            !value.is_active && current !== value.id
                                                        }
                                                    >
                                                        {value.name}
                                                        {!value.is_active ? ' (inactive)' : ''}
                                                    </option>
                                                ))}
                                            </select>
                                        </Field>
                                    )
                                })}
                            </div>

                            {form.errors.attribute_value_ids && (
                                <p className="mt-2 text-sm text-red-600">
                                    {form.errors.attribute_value_ids}
                                </p>
                            )}
                        </div>
                    )}

                    <div className="mt-6 flex flex-wrap items-center gap-6">
                        <label className="inline-flex items-center gap-2 text-sm font-medium text-neutral-700">
                            <input
                                type="checkbox"
                                checked={form.data.is_active}
                                onChange={(event) =>
                                    form.setData('is_active', event.target.checked)
                                }
                                className="h-4 w-4 rounded border-neutral-300"
                            />
                            Active
                        </label>

                        <label className="inline-flex items-center gap-2 text-sm font-medium text-neutral-700">
                            <input
                                type="checkbox"
                                checked={form.data.is_default}
                                onChange={(event) =>
                                    form.setData('is_default', event.target.checked)
                                }
                                className="h-4 w-4 rounded border-neutral-300"
                            />
                            Default variant
                        </label>
                    </div>

                    {form.errors.is_default && (
                        <p className="mt-2 text-sm text-red-600">{form.errors.is_default}</p>
                    )}

                    <div className="mt-6 flex gap-3">
                        <button
                            type="submit"
                            disabled={form.processing}
                            className="rounded-lg bg-neutral-900 px-4 py-2.5 text-sm font-medium text-white disabled:opacity-50"
                        >
                            {form.processing
                                ? 'Saving...'
                                : editingVariant
                                  ? 'Update Variant'
                                  : 'Create Variant'}
                        </button>

                        <button
                            type="button"
                            onClick={closeForm}
                            className="rounded-lg border border-neutral-300 bg-white px-4 py-2.5 text-sm font-medium text-neutral-700"
                        >
                            Cancel
                        </button>
                    </div>
                </form>
            )}

            {variants.length === 0 ? (
                <div className="px-6 py-14 text-center">
                    <PackagePlus className="mx-auto h-8 w-8 text-neutral-300" />

                    <h3 className="mt-3 font-semibold text-neutral-900">No variants yet</h3>

                    <p className="mt-1 text-sm text-neutral-500">
                        Add the first SKU and its attribute combination.
                    </p>
                </div>
            ) : (
                <div className="overflow-x-auto">
                    <table className="min-w-full divide-y divide-neutral-200">
                        <thead className="bg-neutral-50">
                            <tr>
                                <TableHead>Variant</TableHead>

                                <TableHead>Attributes</TableHead>

                                <TableHead>Price</TableHead>

                                <TableHead>Status</TableHead>

                                <TableHead align="right">Actions</TableHead>
                            </tr>
                        </thead>

                        <tbody className="divide-y divide-neutral-100">
                            {variants.map((variant) => (
                                <tr key={variant.id} className="hover:bg-neutral-50">
                                    <td className="px-5 py-4">
                                        <div className="flex items-center gap-2">
                                            <span className="font-medium text-neutral-900">
                                                {variant.sku}
                                            </span>

                                            {variant.is_default && (
                                                <span className="inline-flex items-center gap-1 rounded-full bg-blue-50 px-2 py-0.5 text-xs font-medium text-blue-700">
                                                    <CheckCircle2 className="h-3 w-3" />
                                                    Default
                                                </span>
                                            )}
                                        </div>

                                        {variant.name && (
                                            <p className="mt-1 text-xs text-neutral-500">
                                                {variant.name}
                                            </p>
                                        )}
                                    </td>

                                    <td className="px-5 py-4">
                                        <div className="flex flex-wrap gap-1.5">
                                            {variant.attribute_values.length === 0 ? (
                                                <span className="text-sm text-neutral-400">—</span>
                                            ) : (
                                                variant.attribute_values.map((value) => (
                                                    <span
                                                        key={value.id}
                                                        className="rounded-md bg-neutral-100 px-2 py-1 text-xs text-neutral-700"
                                                    >
                                                        {value.attribute_name}: {value.name}
                                                    </span>
                                                ))
                                            )}
                                        </div>
                                    </td>

                                    <td className="px-5 py-4 text-sm font-medium text-neutral-900">
                                        {minorToMajor(variant.price)}
                                    </td>

                                    <td className="px-5 py-4">
                                        {variant.is_active ? (
                                            <span className="rounded-full bg-green-50 px-2.5 py-1 text-xs font-medium text-green-700">
                                                Active
                                            </span>
                                        ) : (
                                            <span className="rounded-full bg-neutral-100 px-2.5 py-1 text-xs font-medium text-neutral-600">
                                                Inactive
                                            </span>
                                        )}
                                    </td>

                                    <td className="px-5 py-4">
                                        <div className="flex justify-end gap-2">
                                            <button
                                                type="button"
                                                onClick={() => openEdit(variant)}
                                                className="inline-flex items-center gap-1.5 rounded-lg border border-neutral-300 px-3 py-1.5 text-xs font-medium text-neutral-700"
                                            >
                                                <Edit3 className="h-3.5 w-3.5" />
                                                Edit
                                            </button>

                                            <button
                                                type="button"
                                                onClick={() => setVariantToDelete(variant)}
                                                className="inline-flex items-center gap-1.5 rounded-lg border border-red-200 px-3 py-1.5 text-xs font-medium text-red-600"
                                            >
                                                <Trash2 className="h-3.5 w-3.5" />
                                                Delete
                                            </button>
                                        </div>
                                    </td>
                                </tr>
                            ))}
                        </tbody>
                    </table>
                </div>
            )}

            <ConfirmDialog
                open={variantToDelete !== null}
                title="Delete Variant"
                description={
                    variantToDelete
                        ? `Delete variant "${variantToDelete.sku}"? If it is the default variant, another remaining variant will automatically become default.`
                        : ''
                }
                confirmLabel="Delete Variant"
                cancelLabel="Cancel"
                onCancel={() => setVariantToDelete(null)}
                onConfirm={destroyVariant}
            />
        </section>
    )
}

const inputClass =
    'w-full rounded-lg border border-neutral-300 bg-white px-3 py-2.5 text-sm text-neutral-900 outline-none transition focus:border-neutral-500 focus:ring-2 focus:ring-neutral-200'

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
            <span className="mb-1.5 block text-sm font-medium text-neutral-700">
                {label}

                {required && <span className="ml-1 text-red-500">*</span>}
            </span>

            {children}

            {error && <span className="mt-1 block text-sm text-red-600">{error}</span>}
        </label>
    )
}

function TableHead({
    children,
    align = 'left',
}: {
    children: ReactNode
    align?: 'left' | 'right'
}) {
    return (
        <th
            className={`px-5 py-3 text-xs font-semibold uppercase tracking-wide text-neutral-500 ${
                align === 'right' ? 'text-right' : 'text-left'
            }`}
        >
            {children}
        </th>
    )
}
