import MoneyInput from '@/Components/Admin/Catalog/MoneyInput'
import type {
    ProductBrandOption,
    ProductCategoryOption,
    ProductStatus,
    ProductStatusOption,
    ProductType,
} from '@/types/catalog'
import { TiptapEditor } from '@microweb/laravel-tiptap-editor'
import '@microweb/laravel-tiptap-editor/editor.css'
import type { InertiaFormProps } from '@inertiajs/react'
import { useState, type FormEvent } from 'react'
import './product-editor.css'
import SeoFormSection from '@/Components/Admin/Seo/SeoFormSection'
import type { EntitySeoFormData } from '@/types/seo'

// Legacy plain text must be escaped before Tiptap interprets it as HTML.
function shortEditorValue(value: string): string {
    if (!value || /<(?:\/?[a-z][^>]*|!--[\s\S]*?)>/i.test(value)) {
        return value
    }

    const escaped = value.replace(/&/g, '&amp;').replace(/</g, '&lt;').replace(/>/g, '&gt;')
    return `<p>${escaped.replace(/\r\n|\r|\n/g, '<br>')}</p>`
}

const rejectShortMedia = async (_file: File): Promise<never> => {
    throw new Error('Please add media to the full product description.')
}

export type ProductFormData = {
    brand_id: number | null
    type: ProductType
    sku: string
    price: number | null
    compare_at_price: number | null
    cost_price: number | null
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
    seo: EntitySeoFormData
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
    const seoErrors = form.errors as Record<string, string | undefined>
    const [editorError, setEditorError] = useState('')

    const submit = (event: FormEvent<HTMLFormElement>) => {
        // The package commits HTML code edits when returning to Visual mode.
        const codeEditor = event.currentTarget.querySelector<HTMLElement>(
            '.mw-product-editor .mw-tiptap-editor[data-editor-mode="code"]',
        )

        if (codeEditor) {
            event.preventDefault()
            setEditorError('Switch both description editors back to Visual before saving to apply any code changes.')
            codeEditor.querySelector<HTMLButtonElement>('[aria-label="Visual editor"]')?.focus()
            return
        }

        setEditorError('')
        onSubmit(event)
    }

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
        <form onSubmit={submit} className="grid gap-6 xl:grid-cols-[minmax(0,1fr)_340px]">
            <div className="min-w-0 space-y-6">
                <section className="min-w-0 rounded-xl border border-neutral-200 bg-white p-6 shadow-sm">
                    

                    <div className="min-w-0 space-y-5">
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
                            <p id="short-description-label" className="text-sm font-medium text-neutral-900">
                                Short description
                            </p>
                            <div role="group" aria-labelledby="short-description-label" className="mw-product-editor mw-product-editor--short mt-1 min-w-0">
                                <TiptapEditor
                                    value={shortEditorValue(form.data.short_description)}
                                    onChange={(html) => form.setData('short_description', html)}
                                    minHeight={160}
                                    imageUploadUrl={route('admin.products.description-media.store')}
                                    onImageUpload={rejectShortMedia}
                                />
                            </div>
                            <p className="mt-2 text-xs text-neutral-500">
                                Format a concise summary with text, lists and links. Media, video and tables belong in the full description. Product cards use a plain-text excerpt.
                            </p>

                            {form.errors.short_description && (
                                <p className="mt-1 text-sm text-red-600">
                                    {form.errors.short_description}
                                </p>
                            )}
                        </div>

                        <div className="min-w-0">
                            <p className="text-sm font-medium text-neutral-900">Description</p>

                            <div className="mw-product-editor mt-1 min-w-0">
                                <TiptapEditor
                                    value={form.data.description}
                                    onChange={(html) => form.setData('description', html)}
                                    minHeight={420}
                                    imageUploadUrl={route('admin.products.description-media.store')}
                                />
                            </div>

                            <p className="mt-2 text-xs leading-5 text-neutral-500">
                                Add formatted product content including headings, lists, links,
                                images, videos and tables. Return to Visual mode before saving code changes.
                            </p>

                            {form.errors.description && (
                                <p className="mt-1 text-sm text-red-600">
                                    {form.errors.description}
                                </p>
                            )}
                        </div>
                        {editorError && (
                            <p role="alert" className="text-sm text-red-600">{editorError}</p>
                        )}
                    </div>
                </section>

                <section className="rounded-xl border border-neutral-200 bg-white p-6 shadow-sm">
                    <div className="mb-6">
                        <h2 className="text-base font-semibold text-neutral-900">
                            Product type & pricing
                        </h2>

                        <p className="mt-1 text-sm text-neutral-500">
                            Configure how this product is sold and its default pricing.
                        </p>
                    </div>

                    <div>
                        <span className="text-sm font-medium text-neutral-900">Product type</span>

                        <div className="mt-2 grid gap-3 sm:grid-cols-2">
                            <label
                                className={`cursor-pointer rounded-lg border p-4 transition ${
                                    form.data.type === 'simple'
                                        ? 'border-neutral-900 bg-neutral-50 ring-1 ring-neutral-900'
                                        : 'border-neutral-200 hover:border-neutral-300'
                                }`}
                            >
                                <div className="flex items-start gap-3">
                                    <input
                                        type="radio"
                                        name="type"
                                        value="simple"
                                        checked={form.data.type === 'simple'}
                                        onChange={() => form.setData('type', 'simple')}
                                        className="mt-1 h-4 w-4 border-neutral-300"
                                    />

                                    <span>
                                        <span className="block text-sm font-medium text-neutral-900">
                                            Simple product
                                        </span>

                                        <span className="mt-1 block text-xs leading-5 text-neutral-500">
                                            One product with its own SKU and price.
                                        </span>
                                    </span>
                                </div>
                            </label>

                            <label
                                className={`cursor-pointer rounded-lg border p-4 transition ${
                                    form.data.type === 'variable'
                                        ? 'border-neutral-900 bg-neutral-50 ring-1 ring-neutral-900'
                                        : 'border-neutral-200 hover:border-neutral-300'
                                }`}
                            >
                                <div className="flex items-start gap-3">
                                    <input
                                        type="radio"
                                        name="type"
                                        value="variable"
                                        checked={form.data.type === 'variable'}
                                        onChange={() => form.setData('type', 'variable')}
                                        className="mt-1 h-4 w-4 border-neutral-300"
                                    />

                                    <span>
                                        <span className="block text-sm font-medium text-neutral-900">
                                            Variable product
                                        </span>

                                        <span className="mt-1 block text-xs leading-5 text-neutral-500">
                                            A product with variants such as size, color or other
                                            options.
                                        </span>
                                    </span>
                                </div>
                            </label>
                        </div>

                        {form.errors.type && (
                            <p className="mt-2 text-sm text-red-600">{form.errors.type}</p>
                        )}
                    </div>

                    <div className="mt-6 grid gap-5 md:grid-cols-2">
                        <div>
                            <label htmlFor="sku" className="text-sm font-medium text-neutral-900">
                                {form.data.type === 'simple' ? 'SKU' : 'Product SKU'}
                            </label>

                            <input
                                id="sku"
                                type="text"
                                value={form.data.sku}
                                onChange={(event) => form.setData('sku', event.target.value)}
                                maxLength={100}
                                placeholder={
                                    form.data.type === 'simple'
                                        ? 'e.g. PRODUCT-001'
                                        : 'Optional product reference SKU'
                                }
                                className="mt-1 block w-full rounded-lg border border-neutral-300 px-3 py-2.5 text-sm outline-none focus:border-neutral-500 focus:ring-1 focus:ring-neutral-500"
                            />

                            <p className="mt-1 text-xs text-neutral-500">
                                {form.data.type === 'simple'
                                    ? 'Optional unique SKU for this product.'
                                    : 'Optional product-level reference SKU. Variants use their own SKUs.'}
                            </p>

                            {form.errors.sku && (
                                <p className="mt-1 text-sm text-red-600">{form.errors.sku}</p>
                            )}
                        </div>

                        <MoneyInput
                            id="price"
                            label={form.data.type === 'simple' ? 'Price' : 'Base price'}
                            value={form.data.price}
                            onChange={(value) => form.setData('price', value)}
                            error={form.errors.price}
                            required={form.data.type === 'simple'}
                            helpText={
                                form.data.type === 'simple'
                                    ? 'The selling price of this product.'
                                    : 'Default selling price. Variants can override this price.'
                            }
                        />

                        <MoneyInput
                            id="compare_at_price"
                            label="Compare-at price"
                            value={form.data.compare_at_price}
                            onChange={(value) => form.setData('compare_at_price', value)}
                            error={form.errors.compare_at_price}
                            helpText={
                                form.data.type === 'simple'
                                    ? 'Optional original price shown when the product is discounted.'
                                    : 'Default compare-at price inherited by variants without an override.'
                            }
                        />

                        <MoneyInput
                            id="cost_price"
                            label="Cost price"
                            value={form.data.cost_price}
                            onChange={(value) => form.setData('cost_price', value)}
                            error={form.errors.cost_price}
                            helpText={
                                form.data.type === 'simple'
                                    ? 'Optional internal product cost.'
                                    : 'Default internal cost inherited by variants without an override.'
                            }
                        />
                    </div>

                    {form.data.type === 'variable' && (
                        <div className="mt-6 rounded-lg border border-neutral-200 bg-neutral-50 p-4">
                            <p className="text-sm font-medium text-neutral-900">Variant pricing</p>

                            <p className="mt-1 text-sm leading-6 text-neutral-600">
                                These values are the product defaults. Individual variants can use
                                their own SKU and pricing. Variant price inheritance will be enabled
                                in the next pricing step.
                            </p>
                        </div>
                    )}
                </section>

                <SeoFormSection
                    metaTitle={form.data.meta_title}
                    metaDescription={form.data.meta_description}
                    seo={form.data.seo}
                    errors={{
                        meta_title: form.errors.meta_title,
                        meta_description: form.errors.meta_description,
                        canonical_url: seoErrors['seo.canonical_url'],
                        robots_index: seoErrors['seo.robots_index'],
                        robots_follow: seoErrors['seo.robots_follow'],
                        og_title: seoErrors['seo.og_title'],
                        og_description: seoErrors['seo.og_description'],
                        og_image: seoErrors['seo.og_image'],
                        twitter_title: seoErrors['seo.twitter_title'],
                        twitter_description: seoErrors['seo.twitter_description'],
                        twitter_image: seoErrors['seo.twitter_image'],
                    }}
                    onMetaTitleChange={(value) => form.setData('meta_title', value)}
                    onMetaDescriptionChange={(value) => form.setData('meta_description', value)}
                    onSeoChange={(key, value) =>
                        form.setData('seo', {
                            ...form.data.seo,
                            [key]: value,
                        })
                    }
                    titleMaxLength={180}
                    descriptionMaxLength={320}
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
