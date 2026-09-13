import AttributeForm, { type AttributeFormData } from '@/Components/Admin/Catalog/AttributeForm'
import ConfirmDialog from '@/Components/Admin/ConfirmDialog'
import AdminLayout from '@/Layouts/Admin/AdminLayout'
import type { AttributeValue, ProductAttribute } from '@/types/catalog'
import { Link, router, useForm } from '@inertiajs/react'
import type { FormEvent } from 'react'
import { useState } from 'react'

type Props = {
    attribute: ProductAttribute
}

type ValueFormData = {
    name: string
    slug: string
    position: number
    is_active: boolean
}

export default function Edit({ attribute }: Props) {
    const attributeForm = useForm<AttributeFormData>(`EditAttribute:${attribute.id}`, {
        name: attribute.name,
        slug: attribute.slug,
        position: attribute.position,
        is_active: attribute.is_active,
    })

    const valueForm = useForm<ValueFormData>({
        name: '',
        slug: '',
        position: 0,
        is_active: true,
    })

    const [editingValue, setEditingValue] = useState<AttributeValue | null>(null)

    const [deleteValue, setDeleteValue] = useState<AttributeValue | null>(null)

    const updateAttribute = (event: FormEvent<HTMLFormElement>) => {
        event.preventDefault()

        attributeForm.put(route('admin.attributes.update', attribute.id), {
            preserveScroll: true,
        })
    }

    const resetValueForm = () => {
        setEditingValue(null)

        valueForm.setData({
            name: '',
            slug: '',
            position: 0,
            is_active: true,
        })

        valueForm.clearErrors()
    }

    const startEditing = (value: AttributeValue) => {
        setEditingValue(value)

        valueForm.setData({
            name: value.name,
            slug: value.slug,
            position: value.position,
            is_active: value.is_active,
        })

        valueForm.clearErrors()
    }

    const submitValue = (event: FormEvent<HTMLFormElement>) => {
        event.preventDefault()

        if (editingValue) {
            valueForm.put(
                route('admin.attributes.values.update', [attribute.id, editingValue.id]),
                {
                    preserveScroll: true,
                    onSuccess: resetValueForm,
                },
            )

            return
        }

        valueForm.post(route('admin.attributes.values.store', attribute.id), {
            preserveScroll: true,
            onSuccess: resetValueForm,
        })
    }

    const destroyValue = () => {
        if (!deleteValue) {
            return
        }

        router.delete(route('admin.attributes.values.destroy', [attribute.id, deleteValue.id]), {
            preserveScroll: true,
            onSuccess: () => setDeleteValue(null),
        })
    }

    return (
        <AdminLayout
            title="Edit Attribute"
            description={attribute.name}
            actions={
                <Link
                    href={route('admin.attributes.index')}
                    className="rounded-lg border border-neutral-300 px-4 py-2 text-sm"
                >
                    Back to Attributes
                </Link>
            }
        >
            <div className="grid gap-6 xl:grid-cols-[minmax(0,1fr)_minmax(360px,480px)]">
                <AttributeForm
                    form={attributeForm}
                    submitLabel="Update Attribute"
                    onSubmit={updateAttribute}
                />

                <section className="rounded-xl border border-neutral-200 bg-white p-6 shadow-sm">
                    <div>
                        <h2 className="font-semibold text-neutral-900">Attribute Values</h2>

                        <p className="mt-1 text-sm text-neutral-500">
                            Add values such as Red, Blue or XL.
                        </p>
                    </div>

                    <form
                        onSubmit={submitValue}
                        className="mt-6 space-y-4 rounded-lg bg-neutral-50 p-4"
                    >
                        <div>
                            <label className="text-sm font-medium">Value name</label>

                            <input
                                value={valueForm.data.name}
                                onChange={(event) => valueForm.setData('name', event.target.value)}
                                required
                                className="mt-1 block w-full rounded-lg border border-neutral-300 bg-white px-3 py-2"
                            />

                            {valueForm.errors.name && (
                                <p className="mt-1 text-sm text-red-600">{valueForm.errors.name}</p>
                            )}
                        </div>

                        <div>
                            <label className="text-sm font-medium">Slug</label>

                            <input
                                value={valueForm.data.slug}
                                onChange={(event) => valueForm.setData('slug', event.target.value)}
                                placeholder="Auto-generated if blank"
                                className="mt-1 block w-full rounded-lg border border-neutral-300 bg-white px-3 py-2"
                            />
                        </div>

                        <div className="grid grid-cols-2 gap-4">
                            <div>
                                <label className="text-sm font-medium">Position</label>

                                <input
                                    type="number"
                                    min={0}
                                    value={valueForm.data.position}
                                    onChange={(event) =>
                                        valueForm.setData('position', Number(event.target.value))
                                    }
                                    className="mt-1 block w-full rounded-lg border border-neutral-300 bg-white px-3 py-2"
                                />
                            </div>

                            <label className="flex items-center gap-2 pt-7 text-sm font-medium">
                                <input
                                    type="checkbox"
                                    checked={valueForm.data.is_active}
                                    onChange={(event) =>
                                        valueForm.setData('is_active', event.target.checked)
                                    }
                                />
                                Active
                            </label>
                        </div>

                        <div className="flex gap-2">
                            <button
                                disabled={valueForm.processing}
                                className="rounded-lg bg-neutral-900 px-4 py-2 text-sm font-medium text-white"
                            >
                                {editingValue ? 'Update Value' : 'Add Value'}
                            </button>

                            {editingValue && (
                                <button
                                    type="button"
                                    onClick={resetValueForm}
                                    className="rounded-lg border border-neutral-300 px-4 py-2 text-sm"
                                >
                                    Cancel
                                </button>
                            )}
                        </div>
                    </form>

                    <div className="mt-6 divide-y divide-neutral-200 rounded-lg border border-neutral-200">
                        {attribute.values.length === 0 ? (
                            <p className="p-5 text-center text-sm text-neutral-500">
                                No values yet.
                            </p>
                        ) : (
                            attribute.values.map((value) => (
                                <div
                                    key={value.id}
                                    className="flex items-center justify-between gap-4 p-4"
                                >
                                    <div>
                                        <div className="font-medium text-neutral-900">
                                            {value.name}
                                        </div>

                                        <div className="mt-1 text-xs text-neutral-500">
                                            {value.slug}
                                            {' · '}
                                            Position {value.position}
                                        </div>
                                    </div>

                                    <div className="flex items-center gap-2">
                                        <span
                                            className={
                                                value.is_active
                                                    ? 'text-xs font-medium text-green-700'
                                                    : 'text-xs font-medium text-neutral-400'
                                            }
                                        >
                                            {value.is_active ? 'Active' : 'Inactive'}
                                        </span>

                                        <button
                                            type="button"
                                            onClick={() => startEditing(value)}
                                            className="rounded border border-neutral-300 px-2.5 py-1 text-xs"
                                        >
                                            Edit
                                        </button>

                                        <button
                                            type="button"
                                            onClick={() => setDeleteValue(value)}
                                            className="rounded border border-red-200 px-2.5 py-1 text-xs text-red-600"
                                        >
                                            Delete
                                        </button>
                                    </div>
                                </div>
                            ))
                        )}
                    </div>
                </section>
            </div>

            <ConfirmDialog
                open={deleteValue !== null}
                title="Delete Attribute Value"
                description={
                    deleteValue ? `Are you sure you want to delete "${deleteValue.name}"?` : ''
                }
                confirmLabel="Delete Value"
                cancelLabel="Cancel"
                onCancel={() => setDeleteValue(null)}
                onConfirm={destroyValue}
            />
        </AdminLayout>
    )
}
