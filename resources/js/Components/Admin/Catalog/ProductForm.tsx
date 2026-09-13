import SeoFields from '@/Components/Admin/SeoFields'
import type {
    ProductBrandOption,
    ProductCategoryOption,
    ProductStatus,
    ProductStatusOption,
} from '@/types/catalog'
import type { InertiaFormProps } from '@inertiajs/react'
import type { FormEvent } from 'react'

export type ProductFormData = {
    brand_id: number | null
    name: string
    slug: string
    short_description: string
    description: string
    status: ProductStatus
    is_featured: boolean
    position: number
    published_at: string
    meta_title: string
    meta_description: string
    category_ids: number[]
}

type Props = {
    form: InertiaFormProps<ProductFormData>
    brands: ProductBrandOption[]
    categories: ProductCategoryOption[]
    statuses: ProductStatusOption[]
    submitLabel: string
    onSubmit: (event: FormEvent<HTMLFormElement>) => void
}

export default function ProductForm({
    form,
    brands,
    categories,
    statuses,
    submitLabel,
    onSubmit,
}: Props) {
    const toggleCategory = (categoryId: number) => {
        if (form.data.category_ids.includes(categoryId)) {
            form.setData(
                'category_ids',
                form.data.category_ids.filter((id) => id !== categoryId),
            )

            return
        }

        form.setData('category_ids', [...form.data.category_ids, categoryId])
    }

    return (
        <form onSubmit={onSubmit} className="grid gap-6 xl:grid-cols-[minmax(0,1fr)_340px]">
            <div className="space-y-6">
                <section className="rounded-xl border border-neutral-200 bg-white p-6 shadow-sm">
                    <div className="mb-6">
                        <h2 className="text-base font-semibold text-neutral-900">
                            Product information
                        </h2>

                        <p className="mt-1 text-sm text-neutral-500">
                            Add the main information used throughout the catalog.
                        </p>
                    </div>

                    <div className="space-y-5">
                        <div>
                            <label htmlFor="name" className="text-sm font-medium text-neutral-900">
                                Product name
                            </label>

                            <input
                                id="name"
                                type="text"
                                value={form.data.name}
                                onChange={(event) => form.setData('name', event.target.value)}
                                maxLength={180}
                                required
                                className="mt-1 block w-full rounded-lg border border-neutral-300 px-3 py-2.5 text-sm outline-none focus:border-neutral-500 focus:ring-1 focus:ring-neutral-500"
                            />

                            {form.errors.name && (
                                <p className="mt-1 text-sm text-red-600">{form.errors.name}</p>
                            )}
                        </div>

                        <div>
                            <label htmlFor="slug" className="text-sm font-medium text-neutral-900">
                                Slug
                            </label>

                            <input
                                id="slug"
                                type="text"
                                value={form.data.slug}
                                onChange={(event) => form.setData('slug', event.target.value)}
                                maxLength={200}
                                placeholder="Leave blank to generate from product name"
                                className="mt-1 block w-full rounded-lg border border-neutral-300 px-3 py-2.5 text-sm outline-none focus:border-neutral-500 focus:ring-1 focus:ring-neutral-500"
                            />

                            <p className="mt-1 text-xs text-neutral-500">
                                Leave blank and a unique slug will be generated.
                            </p>

                            {form.errors.slug && (
                                <p className="mt-1 text-sm text-red-600">{form.errors.slug}</p>
                            )}
                        </div>

                        <div>
                            <label
                                htmlFor="short_description"
                                className="text-sm font-medium text-neutral-900"
                            >
                                Short description
                            </label>

                            <textarea
                                id="short_description"
                                rows={3}
                                value={form.data.short_description}
                                onChange={(event) =>
                                    form.setData('short_description', event.target.value)
                                }
                                className="mt-1 block w-full rounded-lg border border-neutral-300 px-3 py-2.5 text-sm outline-none focus:border-neutral-500 focus:ring-1 focus:ring-neutral-500"
                            />

                            {form.errors.short_description && (
                                <p className="mt-1 text-sm text-red-600">
                                    {form.errors.short_description}
                                </p>
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
                                rows={10}
                                value={form.data.description}
                                onChange={(event) =>
                                    form.setData('description', event.target.value)
                                }
                                className="mt-1 block w-full rounded-lg border border-neutral-300 px-3 py-2.5 text-sm outline-none focus:border-neutral-500 focus:ring-1 focus:ring-neutral-500"
                            />

                            {form.errors.description && (
                                <p className="mt-1 text-sm text-red-600">
                                    {form.errors.description}
                                </p>
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
                    <h2 className="text-sm font-semibold text-neutral-900">Publishing</h2>

                    <div className="mt-5 space-y-5">
                        <div>
                            <label
                                htmlFor="status"
                                className="text-sm font-medium text-neutral-900"
                            >
                                Status
                            </label>

                            <select
                                id="status"
                                value={form.data.status}
                                onChange={(event) =>
                                    form.setData('status', event.target.value as ProductStatus)
                                }
                                className="mt-1 block w-full rounded-lg border border-neutral-300 bg-white px-3 py-2.5 text-sm"
                            >
                                {statuses.map((status) => (
                                    <option key={status.value} value={status.value}>
                                        {status.label}
                                    </option>
                                ))}
                            </select>

                            {form.errors.status && (
                                <p className="mt-1 text-sm text-red-600">{form.errors.status}</p>
                            )}
                        </div>

                        <div>
                            <label
                                htmlFor="published_at"
                                className="text-sm font-medium text-neutral-900"
                            >
                                Publish date
                            </label>

                            <input
                                id="published_at"
                                type="datetime-local"
                                value={form.data.published_at}
                                onChange={(event) =>
                                    form.setData('published_at', event.target.value)
                                }
                                className="mt-1 block w-full rounded-lg border border-neutral-300 px-3 py-2.5 text-sm"
                            />

                            <p className="mt-1 text-xs text-neutral-500">
                                A future date schedules the product for publication.
                            </p>

                            {form.errors.published_at && (
                                <p className="mt-1 text-sm text-red-600">
                                    {form.errors.published_at}
                                </p>
                            )}
                        </div>

                        <label className="flex items-start gap-3">
                            <input
                                type="checkbox"
                                checked={form.data.is_featured}
                                onChange={(event) =>
                                    form.setData('is_featured', event.target.checked)
                                }
                                className="mt-1 h-4 w-4 rounded border-neutral-300"
                            />

                            <span>
                                <span className="block text-sm font-medium text-neutral-900">
                                    Featured product
                                </span>

                                <span className="block text-xs text-neutral-500">
                                    Allow this product to be selected for featured storefront areas.
                                </span>
                            </span>
                        </label>

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
                                className="mt-1 block w-full rounded-lg border border-neutral-300 px-3 py-2.5 text-sm"
                            />

                            {form.errors.position && (
                                <p className="mt-1 text-sm text-red-600">{form.errors.position}</p>
                            )}
                        </div>
                    </div>
                </section>

                <section className="rounded-xl border border-neutral-200 bg-white p-5 shadow-sm">
                    <h2 className="text-sm font-semibold text-neutral-900">Brand</h2>

                    <div className="mt-4">
                        <select
                            value={form.data.brand_id ?? ''}
                            onChange={(event) =>
                                form.setData(
                                    'brand_id',
                                    event.target.value === '' ? null : Number(event.target.value),
                                )
                            }
                            className="block w-full rounded-lg border border-neutral-300 bg-white px-3 py-2.5 text-sm"
                        >
                            <option value="">No brand</option>

                            {brands.map((brand) => (
                                <option key={brand.id} value={brand.id}>
                                    {brand.name}
                                </option>
                            ))}
                        </select>

                        {form.errors.brand_id && (
                            <p className="mt-1 text-sm text-red-600">{form.errors.brand_id}</p>
                        )}
                    </div>
                </section>

                <section className="rounded-xl border border-neutral-200 bg-white p-5 shadow-sm">
                    <div>
                        <h2 className="text-sm font-semibold text-neutral-900">Categories</h2>

                        <p className="mt-1 text-xs text-neutral-500">
                            A product may belong to multiple categories.
                        </p>
                    </div>

                    <div className="mt-4 max-h-80 space-y-1 overflow-y-auto rounded-lg border border-neutral-200 p-2">
                        {categories.length === 0 ? (
                            <p className="p-3 text-sm text-neutral-500">
                                No active categories available.
                            </p>
                        ) : (
                            categories.map((category) => (
                                <label
                                    key={category.id}
                                    className="flex cursor-pointer items-center gap-3 rounded-md px-3 py-2 hover:bg-neutral-50"
                                >
                                    <input
                                        type="checkbox"
                                        checked={form.data.category_ids.includes(category.id)}
                                        onChange={() => toggleCategory(category.id)}
                                        className="h-4 w-4 rounded border-neutral-300"
                                    />

                                    <span className="text-sm text-neutral-700">
                                        {category.name}
                                    </span>
                                </label>
                            ))
                        )}
                    </div>

                    {form.errors.category_ids && (
                        <p className="mt-2 text-sm text-red-600">{form.errors.category_ids}</p>
                    )}
                </section>

                <button
                    type="submit"
                    disabled={form.processing}
                    className="inline-flex w-full items-center justify-center rounded-lg bg-neutral-900 px-4 py-2.5 text-sm font-medium text-white transition hover:bg-neutral-800 disabled:cursor-not-allowed disabled:opacity-50"
                >
                    {form.processing ? 'Saving...' : submitLabel}
                </button>
            </div>
        </form>
    )
}
