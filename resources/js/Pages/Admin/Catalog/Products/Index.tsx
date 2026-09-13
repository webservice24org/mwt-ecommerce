import ConfirmDialog from '@/Components/Admin/ConfirmDialog'
import AdminLayout from '@/Layouts/Admin/AdminLayout'
import type { ProductListItem, ProductStatusOption } from '@/types/catalog'
import { Link, router, useForm } from '@inertiajs/react'
import type { FormEvent } from 'react'
import { useState } from 'react'

type PaginationLink = {
    url: string | null
    label: string
    active: boolean
}

type ProductPagination = {
    data: ProductListItem[]
    links: PaginationLink[]
    current_page: number
    last_page: number
    from: number | null
    to: number | null
    total: number
}

type Filters = {
    search: string
    status: string
    brand_id: number | null
    featured: string
}

type Props = {
    products: ProductPagination
    filters: Filters
    statuses: ProductStatusOption[]
}

function statusClasses(status: ProductListItem['status']): string {
    switch (status) {
        case 'published':
            return 'bg-green-50 text-green-700 ring-green-200'

        case 'archived':
            return 'bg-neutral-100 text-neutral-600 ring-neutral-200'

        default:
            return 'bg-amber-50 text-amber-700 ring-amber-200'
    }
}

function statusLabel(status: ProductListItem['status']): string {
    switch (status) {
        case 'published':
            return 'Published'

        case 'archived':
            return 'Archived'

        default:
            return 'Draft'
    }
}

export default function Index({ products, filters, statuses }: Props) {
    const [deleteProduct, setDeleteProduct] = useState<ProductListItem | null>(null)

    const filterForm = useForm({
        search: filters.search ?? '',
        status: filters.status ?? '',
        featured: filters.featured ?? '',
    })

    const submitFilters = (event: FormEvent<HTMLFormElement>) => {
        event.preventDefault()

        router.get(
            route('admin.products.index'),
            {
                search: filterForm.data.search || undefined,
                status: filterForm.data.status || undefined,
                featured: filterForm.data.featured || undefined,
            },
            {
                preserveState: true,
                preserveScroll: true,
                replace: true,
            },
        )
    }

    const resetFilters = () => {
        filterForm.setData({
            search: '',
            status: '',
            featured: '',
        })

        router.get(
            route('admin.products.index'),
            {},
            {
                preserveState: true,
                preserveScroll: true,
                replace: true,
            },
        )
    }

    const destroyProduct = () => {
        if (!deleteProduct) {
            return
        }

        router.delete(route('admin.products.destroy', deleteProduct.id), {
            preserveScroll: true,

            onSuccess: () => {
                setDeleteProduct(null)
            },
        })
    }

    return (
        <AdminLayout
            title="Products"
            description="Manage products in your catalog."
            actions={
                <Link
                    href={route('admin.products.create')}
                    className="inline-flex items-center rounded-lg bg-neutral-900 px-4 py-2.5 text-sm font-medium text-white transition hover:bg-neutral-800"
                >
                    Add Product
                </Link>
            }
        >
            <div className="space-y-6">
                <section className="rounded-xl border border-neutral-200 bg-white p-5 shadow-sm">
                    <form
                        onSubmit={submitFilters}
                        className="grid gap-4 lg:grid-cols-[minmax(0,1fr)_190px_190px_auto]"
                    >
                        <div>
                            <label
                                htmlFor="search"
                                className="text-sm font-medium text-neutral-900"
                            >
                                Search
                            </label>

                            <input
                                id="search"
                                type="search"
                                value={filterForm.data.search}
                                onChange={(event) =>
                                    filterForm.setData('search', event.target.value)
                                }
                                placeholder="Product name or slug..."
                                className="mt-1 block w-full rounded-lg border border-neutral-300 bg-white px-3 py-2.5 text-sm"
                            />
                        </div>

                        <div>
                            <label
                                htmlFor="status"
                                className="text-sm font-medium text-neutral-900"
                            >
                                Status
                            </label>

                            <select
                                id="status"
                                value={filterForm.data.status}
                                onChange={(event) =>
                                    filterForm.setData('status', event.target.value)
                                }
                                className="mt-1 block w-full rounded-lg border border-neutral-300 bg-white px-3 py-2.5 text-sm"
                            >
                                <option value="">All statuses</option>

                                {statuses.map((status) => (
                                    <option key={status.value} value={status.value}>
                                        {status.label}
                                    </option>
                                ))}
                            </select>
                        </div>

                        <div>
                            <label
                                htmlFor="featured"
                                className="text-sm font-medium text-neutral-900"
                            >
                                Featured
                            </label>

                            <select
                                id="featured"
                                value={filterForm.data.featured}
                                onChange={(event) =>
                                    filterForm.setData('featured', event.target.value)
                                }
                                className="mt-1 block w-full rounded-lg border border-neutral-300 bg-white px-3 py-2.5 text-sm"
                            >
                                <option value="">All products</option>

                                <option value="1">Featured</option>

                                <option value="0">Not featured</option>
                            </select>
                        </div>

                        <div className="flex items-end gap-2">
                            <button
                                type="submit"
                                className="rounded-lg bg-neutral-900 px-4 py-2.5 text-sm font-medium text-white"
                            >
                                Filter
                            </button>

                            <button
                                type="button"
                                onClick={resetFilters}
                                className="rounded-lg border border-neutral-300 bg-white px-4 py-2.5 text-sm font-medium text-neutral-700"
                            >
                                Reset
                            </button>
                        </div>
                    </form>
                </section>

                <section className="overflow-hidden rounded-xl border border-neutral-200 bg-white shadow-sm">
                    {products.data.length === 0 ? (
                        <div className="px-5 py-16 text-center">
                            <h2 className="text-sm font-medium text-neutral-900">
                                No products found
                            </h2>

                            <p className="mt-1 text-sm text-neutral-500">
                                Create your first product or adjust your filters.
                            </p>

                            <Link
                                href={route('admin.products.create')}
                                className="mt-5 inline-flex rounded-lg bg-neutral-900 px-4 py-2.5 text-sm font-medium text-white"
                            >
                                Add Product
                            </Link>
                        </div>
                    ) : (
                        <>
                            <div className="overflow-x-auto">
                                <table className="min-w-full divide-y divide-neutral-200">
                                    <thead className="bg-neutral-50">
                                        <tr>
                                            <th className="px-5 py-3 text-left text-xs font-semibold uppercase tracking-wide text-neutral-500">
                                                Product
                                            </th>

                                            <th className="px-5 py-3 text-left text-xs font-semibold uppercase tracking-wide text-neutral-500">
                                                Brand
                                            </th>

                                            <th className="px-5 py-3 text-left text-xs font-semibold uppercase tracking-wide text-neutral-500">
                                                Catalog
                                            </th>

                                            <th className="px-5 py-3 text-left text-xs font-semibold uppercase tracking-wide text-neutral-500">
                                                Status
                                            </th>

                                            <th className="px-5 py-3 text-right text-xs font-semibold uppercase tracking-wide text-neutral-500">
                                                Actions
                                            </th>
                                        </tr>
                                    </thead>

                                    <tbody className="divide-y divide-neutral-200">
                                        {products.data.map((product) => (
                                            <tr key={product.id} className="hover:bg-neutral-50">
                                                <td className="px-5 py-4">
                                                    <div className="max-w-sm">
                                                        <div className="font-medium text-neutral-900">
                                                            {product.name}
                                                        </div>

                                                        <div className="mt-1 font-mono text-xs text-neutral-500">
                                                            {product.slug}
                                                        </div>

                                                        {product.is_featured && (
                                                            <span className="mt-2 inline-flex rounded-full bg-blue-50 px-2 py-0.5 text-xs font-medium text-blue-700 ring-1 ring-inset ring-blue-200">
                                                                Featured
                                                            </span>
                                                        )}
                                                    </div>
                                                </td>

                                                <td className="px-5 py-4 text-sm text-neutral-600">
                                                    {product.brand?.name ?? '—'}
                                                </td>

                                                <td className="px-5 py-4">
                                                    <div className="text-sm text-neutral-600">
                                                        {product.categories_count} categories
                                                    </div>

                                                    <div className="mt-1 text-xs text-neutral-400">
                                                        {product.variants_count} variants
                                                    </div>
                                                </td>

                                                <td className="px-5 py-4">
                                                    <span
                                                        className={[
                                                            'inline-flex rounded-full px-2.5 py-1 text-xs font-medium ring-1 ring-inset',
                                                            statusClasses(product.status),
                                                        ].join(' ')}
                                                    >
                                                        {statusLabel(product.status)}
                                                    </span>
                                                </td>

                                                <td className="px-5 py-4 text-right">
                                                    <div className="flex justify-end gap-2">
                                                        <Link
                                                            href={route(
                                                                'admin.products.edit',
                                                                product.id,
                                                            )}
                                                            className="rounded-md border border-neutral-300 bg-white px-3 py-1.5 text-xs font-medium text-neutral-700 hover:bg-neutral-50"
                                                        >
                                                            Edit
                                                        </Link>

                                                        <button
                                                            type="button"
                                                            onClick={() =>
                                                                setDeleteProduct(product)
                                                            }
                                                            className="rounded-md border border-red-200 bg-white px-3 py-1.5 text-xs font-medium text-red-600 hover:bg-red-50"
                                                        >
                                                            Delete
                                                        </button>
                                                    </div>
                                                </td>
                                            </tr>
                                        ))}
                                    </tbody>
                                </table>
                            </div>

                            <div className="flex flex-col gap-4 border-t border-neutral-200 px-5 py-4 sm:flex-row sm:items-center sm:justify-between">
                                <p className="text-sm text-neutral-500">
                                    Showing {products.from ?? 0}–{products.to ?? 0} of{' '}
                                    {products.total}
                                </p>

                                {products.last_page > 1 && (
                                    <div className="flex flex-wrap gap-1">
                                        {products.links.map((link, index) => {
                                            if (!link.url) {
                                                return (
                                                    <span
                                                        key={index}
                                                        className="cursor-not-allowed rounded-md border border-neutral-200 px-3 py-1.5 text-sm text-neutral-300"
                                                        dangerouslySetInnerHTML={{
                                                            __html: link.label,
                                                        }}
                                                    />
                                                )
                                            }

                                            return (
                                                <Link
                                                    key={index}
                                                    href={link.url}
                                                    preserveScroll
                                                    preserveState
                                                    className={[
                                                        'rounded-md border px-3 py-1.5 text-sm',
                                                        link.active
                                                            ? 'border-neutral-900 bg-neutral-900 text-white'
                                                            : 'border-neutral-300 bg-white text-neutral-700',
                                                    ].join(' ')}
                                                    dangerouslySetInnerHTML={{
                                                        __html: link.label,
                                                    }}
                                                />
                                            )
                                        })}
                                    </div>
                                )}
                            </div>
                        </>
                    )}
                </section>
            </div>

            <ConfirmDialog
                open={deleteProduct !== null}
                title="Delete Product"
                description={
                    deleteProduct ? `Are you sure you want to delete "${deleteProduct.name}"?` : ''
                }
                confirmLabel="Delete Product"
                cancelLabel="Cancel"
                onCancel={() => setDeleteProduct(null)}
                onConfirm={destroyProduct}
            />
        </AdminLayout>
    )
}
