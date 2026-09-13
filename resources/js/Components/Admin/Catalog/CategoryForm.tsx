import SeoFields from '@/Components/Admin/SeoFields'
import type { CategoryParentOption } from '@/types/catalog'
import type { InertiaFormProps } from '@inertiajs/react'
import { useEffect, useState } from 'react'

export type CategoryFormData = {
    parent_id: string
    name: string
    slug: string
    description: string
    image: File | null
    remove_image: boolean
    position: number
    is_active: boolean
    meta_title: string
    meta_description: string
}

type Props = {
    form: InertiaFormProps<CategoryFormData>
    parentCategories: CategoryParentOption[]
    submitLabel: string
    onSubmit: () => void
    currentImageUrl?: string | null
}

export default function CategoryForm({
    form,
    parentCategories,
    submitLabel,
    onSubmit,
    currentImageUrl = null,
}: Props) {
    const inputClass =
        'mt-1 block w-full rounded-md border border-neutral-300 bg-white px-3 py-2 text-sm shadow-sm focus:border-neutral-500 focus:outline-none focus:ring-1 focus:ring-neutral-500'

    const errorClass = 'mt-1 text-sm text-red-600'

    const [previewUrl, setPreviewUrl] = useState<string | null>(null)

    useEffect(() => {
        return () => {
            if (previewUrl) {
                URL.revokeObjectURL(previewUrl)
            }
        }
    }, [previewUrl])

    const handleImageChange = (event: React.ChangeEvent<HTMLInputElement>) => {
        const file = event.target.files?.[0] ?? null

        form.setData('image', file)

        if (!file) {
            setPreviewUrl((current) => {
                if (current) {
                    URL.revokeObjectURL(current)
                }

                return null
            })

            return
        }

        setPreviewUrl((current) => {
            if (current) {
                URL.revokeObjectURL(current)
            }

            return URL.createObjectURL(file)
        })

        form.setData('remove_image', false)
    }

    return (
        <form
            onSubmit={(event) => {
                event.preventDefault()
                onSubmit()
            }}
            className="space-y-6"
        >
            <div className="grid gap-6 lg:grid-cols-3">
                <div className="space-y-6 lg:col-span-2">
                    <section className="rounded-xl border border-neutral-200 bg-white p-5 shadow-sm">
                        <h2 className="font-semibold text-neutral-900">Category Information</h2>

                        <div className="mt-5 space-y-5">
                            <div>
                                <label
                                    htmlFor="name"
                                    className="text-sm font-medium text-neutral-900"
                                >
                                    Name
                                </label>

                                <input
                                    id="name"
                                    type="text"
                                    value={form.data.name}
                                    onChange={(event) => form.setData('name', event.target.value)}
                                    className={inputClass}
                                    placeholder="Enter category name"
                                />

                                {form.errors.name && (
                                    <p className={errorClass}>{form.errors.name}</p>
                                )}
                            </div>

                            <div>
                                <label
                                    htmlFor="slug"
                                    className="text-sm font-medium text-neutral-900"
                                >
                                    Slug
                                </label>

                                <input
                                    id="slug"
                                    type="text"
                                    value={form.data.slug}
                                    onChange={(event) => form.setData('slug', event.target.value)}
                                    placeholder="Leave blank to generate automatically"
                                    className={inputClass}
                                />

                                <p className="mt-1 text-xs text-neutral-500">
                                    Leave blank and Laravel will generate a unique slug
                                    automatically.
                                </p>

                                {form.errors.slug && (
                                    <p className={errorClass}>{form.errors.slug}</p>
                                )}
                            </div>

                            <div>
                                <label
                                    htmlFor="parent_id"
                                    className="text-sm font-medium text-neutral-900"
                                >
                                    Parent Category
                                </label>

                                <select
                                    id="parent_id"
                                    value={form.data.parent_id}
                                    onChange={(event) =>
                                        form.setData('parent_id', event.target.value)
                                    }
                                    className={inputClass}
                                >
                                    <option value="">No parent</option>

                                    {parentCategories.map((category) => (
                                        <option key={category.id} value={category.id}>
                                            {category.name}
                                        </option>
                                    ))}
                                </select>

                                {form.errors.parent_id && (
                                    <p className={errorClass}>{form.errors.parent_id}</p>
                                )}
                            </div>

                            <div>
                                <label
                                    htmlFor="description"
                                    className="text-sm font-medium text-neutral-900"
                                >
                                    Description
                                </label>

                                <textarea
                                    id="description"
                                    rows={6}
                                    value={form.data.description}
                                    onChange={(event) =>
                                        form.setData('description', event.target.value)
                                    }
                                    className={inputClass}
                                    placeholder="Write a short category description..."
                                />

                                {form.errors.description && (
                                    <p className={errorClass}>{form.errors.description}</p>
                                )}
                            </div>

                            <div>
                                <label
                                    htmlFor="category-image"
                                    className="text-sm font-medium text-neutral-900"
                                >
                                    Category Image
                                </label>

                                <p className="mt-1 text-xs text-neutral-500">
                                    JPG, JPEG, PNG or WebP. Maximum 5 MB.
                                </p>

                                {currentImageUrl && !form.data.remove_image && !form.data.image && (
                                    <div className="mt-4">
                                        <div className="overflow-hidden rounded-xl border border-neutral-200 bg-neutral-50">
                                            <img
                                                src={currentImageUrl}
                                                alt="Current category"
                                                className="h-48 w-full object-cover"
                                            />
                                        </div>

                                        <label className="mt-3 flex cursor-pointer items-center gap-2 text-sm text-red-600">
                                            <input
                                                type="checkbox"
                                                checked={form.data.remove_image}
                                                onChange={(event) =>
                                                    form.setData(
                                                        'remove_image',
                                                        event.target.checked,
                                                    )
                                                }
                                                className="h-4 w-4 rounded border-neutral-300"
                                            />
                                            Remove current image
                                        </label>
                                    </div>
                                )}

                                {currentImageUrl && form.data.remove_image && !form.data.image && (
                                    <div className="mt-4 rounded-lg border border-red-200 bg-red-50 px-4 py-3 text-sm text-red-700">
                                        The current image will be removed when you save this
                                        category.
                                    </div>
                                )}

                                {previewUrl && (
                                    <div className="mt-4">
                                        <div className="overflow-hidden rounded-xl border border-neutral-200 bg-neutral-50">
                                            <img
                                                src={previewUrl}
                                                alt="New category preview"
                                                className="h-48 w-full object-cover"
                                            />
                                        </div>

                                        {form.data.image && (
                                            <div className="mt-2 flex items-center justify-between gap-3">
                                                <p className="truncate text-xs text-neutral-500">
                                                    New image selected: {form.data.image.name}
                                                </p>

                                                <button
                                                    type="button"
                                                    onClick={() => {
                                                        form.setData('image', null)

                                                        setPreviewUrl((current) => {
                                                            if (current) {
                                                                URL.revokeObjectURL(current)
                                                            }

                                                            return null
                                                        })
                                                    }}
                                                    className="shrink-0 text-xs font-medium text-red-600 hover:text-red-700"
                                                >
                                                    Remove selection
                                                </button>
                                            </div>
                                        )}
                                    </div>
                                )}

                                <input
                                    id="category-image"
                                    type="file"
                                    accept="image/jpeg,image/png,image/webp"
                                    onChange={handleImageChange}
                                    className="mt-4 block w-full rounded-md border border-neutral-300 bg-white px-3 py-2 text-sm text-neutral-700 file:mr-4 file:rounded-md file:border-0 file:bg-neutral-100 file:px-4 file:py-2 file:text-sm file:font-medium file:text-neutral-700 hover:file:bg-neutral-200"
                                />

                                {form.errors.image && (
                                    <p className={errorClass}>{form.errors.image}</p>
                                )}
                            </div>
                        </div>
                    </section>

                    <SeoFields
                        metaTitle={form.data.meta_title}
                        metaDescription={form.data.meta_description}
                        titleError={form.errors.meta_title}
                        descriptionError={form.errors.meta_description}
                        onTitleChange={(value) => form.setData('meta_title', value)}
                        onDescriptionChange={(value) => form.setData('meta_description', value)}
                    />
                </div>

                <div className="space-y-6">
                    <section className="rounded-xl border border-neutral-200 bg-white p-5 shadow-sm">
                        <h2 className="font-semibold text-neutral-900">Publishing</h2>

                        <div className="mt-5 space-y-5">
                            <div>
                                <label
                                    htmlFor="position"
                                    className="text-sm font-medium text-neutral-900"
                                >
                                    Position
                                </label>

                                <input
                                    id="position"
                                    type="number"
                                    min={0}
                                    value={form.data.position}
                                    onChange={(event) =>
                                        form.setData('position', Number(event.target.value))
                                    }
                                    className={inputClass}
                                />

                                <p className="mt-1 text-xs text-neutral-500">
                                    Lower numbers appear first.
                                </p>

                                {form.errors.position && (
                                    <p className={errorClass}>{form.errors.position}</p>
                                )}
                            </div>

                            <div>
                                <label className="flex cursor-pointer items-start gap-3 rounded-lg border border-neutral-200 p-3">
                                    <input
                                        type="checkbox"
                                        checked={form.data.is_active}
                                        onChange={(event) =>
                                            form.setData('is_active', event.target.checked)
                                        }
                                        className="mt-0.5 h-4 w-4 rounded border-neutral-300"
                                    />

                                    <span>
                                        <span className="block text-sm font-medium text-neutral-900">
                                            Active
                                        </span>

                                        <span className="mt-0.5 block text-xs text-neutral-500">
                                            Allow this category to be displayed on the storefront.
                                        </span>
                                    </span>
                                </label>

                                {form.errors.is_active && (
                                    <p className={errorClass}>{form.errors.is_active}</p>
                                )}
                            </div>
                        </div>
                    </section>

                    <button
                        type="submit"
                        disabled={form.processing}
                        className="w-full rounded-lg bg-neutral-900 px-4 py-2.5 text-sm font-medium text-white transition hover:bg-neutral-800 disabled:cursor-not-allowed disabled:opacity-50"
                    >
                        {form.processing ? 'Saving...' : submitLabel}
                    </button>
                </div>
            </div>
        </form>
    )
}
