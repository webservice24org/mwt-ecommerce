import ConfirmDialog from '@/Components/Admin/ConfirmDialog'
import AdminLayout from '@/Layouts/Admin/AdminLayout'
import type { BrandListItem } from '@/types/catalog'
import { Link, router, useForm } from '@inertiajs/react'
import { FormEvent, useState } from 'react'

type PaginationLink = {
    url: string | null
    label: string
    active: boolean
}

type BrandsPagination = {
    data: BrandListItem[]
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
}

type Props = {
    brands: BrandsPagination
    filters: Filters
}

export default function Index({ brands, filters }: Props) {
    const [deleteBrand, setDeleteBrand] = useState<BrandListItem | null>(null)

    const filterForm = useForm({
        search: filters.search ?? '',
        status: filters.status ?? '',
    })

    const submitFilters = (event: FormEvent<HTMLFormElement>) => {
        event.preventDefault()

        router.get(
            route('admin.brands.index'),
            {
                search: filterForm.data.search || undefined,
                status: filterForm.data.status || undefined,
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
        })

        router.get(
            route('admin.brands.index'),
            {},
            {
                preserveState: true,
                preserveScroll: true,
                replace: true,
            },
        )
    }

    const destroyBrand = () => {
        if (!deleteBrand) {
            return
        }

        router.delete(route('admin.brands.destroy', deleteBrand.id), {
            preserveScroll: true,

            onSuccess: () => {
                setDeleteBrand(null)
            },
        })
    }

    return (
        <AdminLayout
            title="Brands"
            description="Manage product brands used throughout the catalog."
            actions={
                <Link
                    href={route('admin.brands.create')}
                    className="inline-flex items-center rounded-lg bg-neutral-900 px-4 py-2.5 text-sm font-medium text-white transition hover:bg-neutral-800"
                >
                    Add Brand
                </Link>
            }
        >
            <div className="space-y-6">
                <section className="rounded-xl border border-neutral-200 bg-white p-5 shadow-sm">
                    <form
                        onSubmit={submitFilters}
                        className="grid gap-4 md:grid-cols-[minmax(0,1fr)_220px_auto]"
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
                                placeholder="Search by brand name or slug..."
                                className="mt-1 block w-full rounded-lg border border-neutral-300 bg-white px-3 py-2.5 text-sm shadow-sm outline-none transition focus:border-neutral-500 focus:ring-1 focus:ring-neutral-500"
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
                                className="mt-1 block w-full rounded-lg border border-neutral-300 bg-white px-3 py-2.5 text-sm shadow-sm outline-none transition focus:border-neutral-500 focus:ring-1 focus:ring-neutral-500"
                            >
                                <option value="">All statuses</option>

                                <option value="active">Active</option>

                                <option value="inactive">Inactive</option>
                            </select>
                        </div>

                        <div className="flex items-end gap-2">
                            <button
                                type="submit"
                                className="rounded-lg bg-neutral-900 px-4 py-2.5 text-sm font-medium text-white transition hover:bg-neutral-800"
                            >
                                Filter
                            </button>

                            <button
                                type="button"
                                onClick={resetFilters}
                                className="rounded-lg border border-neutral-300 bg-white px-4 py-2.5 text-sm font-medium text-neutral-700 transition hover:bg-neutral-50"
                            >
                                Reset
                            </button>
                        </div>
                    </form>
                </section>

                <section className="overflow-hidden rounded-xl border border-neutral-200 bg-white shadow-sm">
                    <div className="flex items-center justify-between border-b border-neutral-200 px-5 py-4">
                        <div>
                            <h2 className="font-semibold text-neutral-900">Brand List</h2>

                            <p className="mt-1 text-xs text-neutral-500">
                                {brands.total} {brands.total === 1 ? 'brand' : 'brands'} found.
                            </p>
                        </div>
                    </div>

                    {brands.data.length === 0 ? (
                        <div className="px-5 py-16 text-center">
                            <div className="text-sm font-medium text-neutral-900">
                                No brands found
                            </div>

                            <p className="mt-1 text-sm text-neutral-500">
                                Try changing your filters or create your first brand.
                            </p>

                            <Link
                                href={route('admin.brands.create')}
                                className="mt-5 inline-flex rounded-lg bg-neutral-900 px-4 py-2.5 text-sm font-medium text-white hover:bg-neutral-800"
                            >
                                Add Brand
                            </Link>
                        </div>
                    ) : (
                        <>
                            <div className="overflow-x-auto">
                                <table className="min-w-full divide-y divide-neutral-200">
                                    <thead className="bg-neutral-50">
                                        <tr>
                                            <th className="px-5 py-3 text-left text-xs font-semibold uppercase tracking-wide text-neutral-500">
                                                Brand
                                            </th>

                                            <th className="px-5 py-3 text-left text-xs font-semibold uppercase tracking-wide text-neutral-500">
                                                Slug
                                            </th>

                                            <th className="px-5 py-3 text-left text-xs font-semibold uppercase tracking-wide text-neutral-500">
                                                Position
                                            </th>

                                            <th className="px-5 py-3 text-left text-xs font-semibold uppercase tracking-wide text-neutral-500">
                                                Status
                                            </th>

                                            <th className="px-5 py-3 text-right text-xs font-semibold uppercase tracking-wide text-neutral-500">
                                                Actions
                                            </th>
                                        </tr>
                                    </thead>

                                    <tbody className="divide-y divide-neutral-200 bg-white">
                                        {brands.data.map((brand) => (
                                            <tr
                                                key={brand.id}
                                                className="transition hover:bg-neutral-50"
                                            >
                                                <td className="whitespace-nowrap px-5 py-4">
                                                    <div className="flex items-center gap-3">
                                                        <div className="flex h-11 w-11 shrink-0 items-center justify-center overflow-hidden rounded-lg border border-neutral-200 bg-neutral-50">
                                                            {brand.logo_url ? (
                                                                <img
                                                                    src={brand.logo_url}
                                                                    alt={brand.name}
                                                                    className="h-full w-full object-contain p-1"
                                                                />
                                                            ) : (
                                                                <span className="text-xs font-semibold text-neutral-400">
                                                                    {brand.name[0]}
                                                                </span>
                                                            )}
                                                        </div>

                                                        <div className="min-w-0">
                                                            <div className="truncate text-sm font-medium text-neutral-900">
                                                                {brand.name}
                                                            </div>

                                                            <div className="mt-0.5 text-xs text-neutral-400">
                                                                ID: {brand.id}
                                                            </div>
                                                        </div>
                                                    </div>
                                                </td>

                                                <td className="px-5 py-4 text-sm text-neutral-600">
                                                    <span className="rounded bg-neutral-100 px-2 py-1 font-mono text-xs">
                                                        {brand.slug}
                                                    </span>
                                                </td>

                                                <td className="px-5 py-4 text-sm text-neutral-600">
                                                    {brand.position}
                                                </td>

                                                <td className="px-5 py-4">
                                                    {brand.is_active ? (
                                                        <span className="inline-flex rounded-full bg-green-50 px-2.5 py-1 text-xs font-medium text-green-700 ring-1 ring-inset ring-green-200">
                                                            Active
                                                        </span>
                                                    ) : (
                                                        <span className="inline-flex rounded-full bg-neutral-100 px-2.5 py-1 text-xs font-medium text-neutral-600 ring-1 ring-inset ring-neutral-200">
                                                            Inactive
                                                        </span>
                                                    )}
                                                </td>

                                                <td className="whitespace-nowrap px-5 py-4 text-right">
                                                    <div className="flex justify-end gap-2">
                                                        <Link
                                                            href={route(
                                                                'admin.brands.edit',
                                                                brand.id,
                                                            )}
                                                            className="rounded-md border border-neutral-300 bg-white px-3 py-1.5 text-xs font-medium text-neutral-700 transition hover:bg-neutral-50"
                                                        >
                                                            Edit
                                                        </Link>

                                                        <button
                                                            type="button"
                                                            onClick={() => setDeleteBrand(brand)}
                                                            className="rounded-md border border-red-200 bg-white px-3 py-1.5 text-xs font-medium text-red-600 transition hover:bg-red-50"
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
                                    Showing {brands.from ?? 0}–{brands.to ?? 0} of {brands.total}
                                </p>

                                {brands.last_page > 1 && (
                                    <div className="flex flex-wrap items-center gap-1">
                                        {brands.links.map((link, index) => {
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
                                                        'rounded-md border px-3 py-1.5 text-sm transition',
                                                        link.active
                                                            ? 'border-neutral-900 bg-neutral-900 text-white'
                                                            : 'border-neutral-300 bg-white text-neutral-700 hover:bg-neutral-50',
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
                open={deleteBrand !== null}
                title="Delete Brand"
                description={
                    deleteBrand
                        ? `Are you sure you want to delete "${deleteBrand.name}"? Its stored logo will also be deleted.`
                        : ''
                }
                confirmLabel="Delete Brand"
                cancelLabel="Cancel"
                onCancel={() => setDeleteBrand(null)}
                onConfirm={destroyBrand}
            />
        </AdminLayout>
    )
}
