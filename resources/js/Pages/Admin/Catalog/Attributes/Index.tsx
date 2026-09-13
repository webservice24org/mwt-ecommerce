import ConfirmDialog from '@/Components/Admin/ConfirmDialog'
import AdminLayout from '@/Layouts/Admin/AdminLayout'
import type { AttributeListItem } from '@/types/catalog'
import { Link, router } from '@inertiajs/react'
import { Edit3, Plus, Search, SlidersHorizontal, Trash2 } from 'lucide-react'
import { useState } from 'react'

type PaginationLink = {
    url: string | null
    label: string
    active: boolean
}

type PaginatedAttributes = {
    data: AttributeListItem[]
    current_page: number
    last_page: number
    per_page: number
    total: number
    from: number | null
    to: number | null
    links: PaginationLink[]
}

type Filters = {
    search: string
    status: string
}

type Props = {
    attributes: PaginatedAttributes
    filters: Filters
}

export default function Index({ attributes, filters }: Props) {
    const [search, setSearch] = useState(filters.search ?? '')

    const [status, setStatus] = useState(filters.status ?? '')

    const [attributeToDelete, setAttributeToDelete] = useState<AttributeListItem | null>(null)

    const applyFilters = () => {
        router.get(
            route('admin.attributes.index'),
            {
                search: search.trim() || undefined,

                status: status || undefined,
            },
            {
                preserveState: true,
                replace: true,
            },
        )
    }

    const resetFilters = () => {
        setSearch('')
        setStatus('')

        router.get(
            route('admin.attributes.index'),
            {},
            {
                preserveState: true,
                replace: true,
            },
        )
    }

    const destroyAttribute = () => {
        if (!attributeToDelete) {
            return
        }

        router.delete(route('admin.attributes.destroy', attributeToDelete.id), {
            preserveScroll: true,

            onSuccess: () => {
                setAttributeToDelete(null)
            },
        })
    }

    return (
        <AdminLayout
            title="Attributes"
            description="Manage product attributes and their values."
            actions={
                <Link
                    href={route('admin.attributes.create')}
                    className="inline-flex items-center gap-2 rounded-lg bg-neutral-900 px-4 py-2.5 text-sm font-medium text-white transition hover:bg-neutral-800"
                >
                    <Plus className="h-4 w-4" />
                    Add Attribute
                </Link>
            }
        >
            <div className="space-y-6">
                <section className="rounded-xl border border-neutral-200 bg-white p-4 shadow-sm">
                    <div className="grid gap-4 lg:grid-cols-[minmax(0,1fr)_220px_auto]">
                        <div>
                            <label
                                htmlFor="search"
                                className="mb-1.5 block text-sm font-medium text-neutral-700"
                            >
                                Search
                            </label>

                            <div className="relative">
                                <Search className="absolute left-3 top-1/2 h-4 w-4 -translate-y-1/2 text-neutral-400" />

                                <input
                                    id="search"
                                    type="search"
                                    value={search}
                                    onChange={(event) => setSearch(event.target.value)}
                                    onKeyDown={(event) => {
                                        if (event.key === 'Enter') {
                                            applyFilters()
                                        }
                                    }}
                                    placeholder="Search by name or slug..."
                                    className="w-full rounded-lg border border-neutral-300 bg-white py-2.5 pl-9 pr-3 text-sm text-neutral-900 outline-none transition focus:border-neutral-500 focus:ring-2 focus:ring-neutral-200"
                                />
                            </div>
                        </div>

                        <div>
                            <label
                                htmlFor="status"
                                className="mb-1.5 block text-sm font-medium text-neutral-700"
                            >
                                Status
                            </label>

                            <select
                                id="status"
                                value={status}
                                onChange={(event) => setStatus(event.target.value)}
                                className="w-full rounded-lg border border-neutral-300 bg-white px-3 py-2.5 text-sm text-neutral-900 outline-none transition focus:border-neutral-500 focus:ring-2 focus:ring-neutral-200"
                            >
                                <option value="">All statuses</option>

                                <option value="active">Active</option>

                                <option value="inactive">Inactive</option>
                            </select>
                        </div>

                        <div className="flex items-end gap-2">
                            <button
                                type="button"
                                onClick={applyFilters}
                                className="inline-flex h-[42px] items-center gap-2 rounded-lg bg-neutral-900 px-4 text-sm font-medium text-white transition hover:bg-neutral-800"
                            >
                                <SlidersHorizontal className="h-4 w-4" />
                                Filter
                            </button>

                            <button
                                type="button"
                                onClick={resetFilters}
                                className="h-[42px] rounded-lg border border-neutral-300 bg-white px-4 text-sm font-medium text-neutral-700 transition hover:bg-neutral-50"
                            >
                                Reset
                            </button>
                        </div>
                    </div>
                </section>

                <section className="overflow-hidden rounded-xl border border-neutral-200 bg-white shadow-sm">
                    <div className="border-b border-neutral-200 px-5 py-4">
                        <div className="flex flex-wrap items-center justify-between gap-3">
                            <div>
                                <h2 className="font-semibold text-neutral-900">Attribute List</h2>

                                <p className="mt-1 text-sm text-neutral-500">
                                    {attributes.total} attribute
                                    {attributes.total === 1 ? '' : 's'} found
                                </p>
                            </div>

                            {attributes.from !== null && attributes.to !== null && (
                                <p className="text-sm text-neutral-500">
                                    Showing {attributes.from} to {attributes.to} of{' '}
                                    {attributes.total}
                                </p>
                            )}
                        </div>
                    </div>

                    {attributes.data.length === 0 ? (
                        <div className="px-6 py-16 text-center">
                            <h3 className="text-base font-semibold text-neutral-900">
                                No attributes found
                            </h3>

                            <p className="mt-2 text-sm text-neutral-500">
                                Create your first attribute or change the current filters.
                            </p>

                            <Link
                                href={route('admin.attributes.create')}
                                className="mt-5 inline-flex items-center gap-2 rounded-lg bg-neutral-900 px-4 py-2.5 text-sm font-medium text-white"
                            >
                                <Plus className="h-4 w-4" />
                                Add Attribute
                            </Link>
                        </div>
                    ) : (
                        <>
                            <div className="overflow-x-auto">
                                <table className="min-w-full divide-y divide-neutral-200">
                                    <thead className="bg-neutral-50">
                                        <tr>
                                            <th className="px-5 py-3 text-left text-xs font-semibold uppercase tracking-wide text-neutral-500">
                                                Attribute
                                            </th>

                                            <th className="px-5 py-3 text-left text-xs font-semibold uppercase tracking-wide text-neutral-500">
                                                Slug
                                            </th>

                                            <th className="px-5 py-3 text-center text-xs font-semibold uppercase tracking-wide text-neutral-500">
                                                Values
                                            </th>

                                            <th className="px-5 py-3 text-center text-xs font-semibold uppercase tracking-wide text-neutral-500">
                                                Position
                                            </th>

                                            <th className="px-5 py-3 text-center text-xs font-semibold uppercase tracking-wide text-neutral-500">
                                                Status
                                            </th>

                                            <th className="px-5 py-3 text-right text-xs font-semibold uppercase tracking-wide text-neutral-500">
                                                Actions
                                            </th>
                                        </tr>
                                    </thead>

                                    <tbody className="divide-y divide-neutral-100 bg-white">
                                        {attributes.data.map((attribute) => (
                                            <tr
                                                key={attribute.id}
                                                className="transition hover:bg-neutral-50"
                                            >
                                                <td className="whitespace-nowrap px-5 py-4">
                                                    <div className="font-medium text-neutral-900">
                                                        {attribute.name}
                                                    </div>

                                                    <div className="mt-0.5 text-xs text-neutral-400">
                                                        ID: {attribute.id}
                                                    </div>
                                                </td>

                                                <td className="whitespace-nowrap px-5 py-4 text-sm text-neutral-600">
                                                    {attribute.slug}
                                                </td>

                                                <td className="whitespace-nowrap px-5 py-4 text-center">
                                                    <span className="inline-flex min-w-8 items-center justify-center rounded-full bg-neutral-100 px-2.5 py-1 text-xs font-semibold text-neutral-700">
                                                        {attribute.values_count}
                                                    </span>
                                                </td>

                                                <td className="whitespace-nowrap px-5 py-4 text-center text-sm text-neutral-600">
                                                    {attribute.position}
                                                </td>

                                                <td className="whitespace-nowrap px-5 py-4 text-center">
                                                    {attribute.is_active ? (
                                                        <span className="inline-flex rounded-full bg-green-50 px-2.5 py-1 text-xs font-medium text-green-700 ring-1 ring-inset ring-green-600/20">
                                                            Active
                                                        </span>
                                                    ) : (
                                                        <span className="inline-flex rounded-full bg-neutral-100 px-2.5 py-1 text-xs font-medium text-neutral-600 ring-1 ring-inset ring-neutral-500/10">
                                                            Inactive
                                                        </span>
                                                    )}
                                                </td>

                                                <td className="whitespace-nowrap px-5 py-4">
                                                    <div className="flex items-center justify-end gap-2">
                                                        <Link
                                                            href={route(
                                                                'admin.attributes.edit',
                                                                attribute.id,
                                                            )}
                                                            className="inline-flex items-center gap-1.5 rounded-lg border border-neutral-300 bg-white px-3 py-1.5 text-xs font-medium text-neutral-700 transition hover:bg-neutral-50"
                                                        >
                                                            <Edit3 className="h-3.5 w-3.5" />
                                                            Edit
                                                        </Link>

                                                        <button
                                                            type="button"
                                                            onClick={() =>
                                                                setAttributeToDelete(attribute)
                                                            }
                                                            className="inline-flex items-center gap-1.5 rounded-lg border border-red-200 bg-white px-3 py-1.5 text-xs font-medium text-red-600 transition hover:bg-red-50"
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

                            {attributes.links.length > 3 && (
                                <div className="flex flex-wrap items-center justify-between gap-4 border-t border-neutral-200 px-5 py-4">
                                    <p className="text-sm text-neutral-500">
                                        Page {attributes.current_page} of {attributes.last_page}
                                    </p>

                                    <div className="flex flex-wrap gap-1">
                                        {attributes.links.map((link, index) =>
                                            link.url ? (
                                                <Link
                                                    key={index}
                                                    href={link.url}
                                                    preserveScroll
                                                    className={`rounded-md border px-3 py-1.5 text-sm transition ${
                                                        link.active
                                                            ? 'border-neutral-900 bg-neutral-900 text-white'
                                                            : 'border-neutral-300 bg-white text-neutral-700 hover:bg-neutral-50'
                                                    }`}
                                                    dangerouslySetInnerHTML={{
                                                        __html: link.label,
                                                    }}
                                                />
                                            ) : (
                                                <span
                                                    key={index}
                                                    className="cursor-not-allowed rounded-md border border-neutral-200 bg-neutral-50 px-3 py-1.5 text-sm text-neutral-400"
                                                    dangerouslySetInnerHTML={{
                                                        __html: link.label,
                                                    }}
                                                />
                                            ),
                                        )}
                                    </div>
                                </div>
                            )}
                        </>
                    )}
                </section>
            </div>

            <ConfirmDialog
                open={attributeToDelete !== null}
                title="Delete Attribute"
                description={
                    attributeToDelete
                        ? `Are you sure you want to delete "${attributeToDelete.name}"? Its attribute values will also be deleted.`
                        : ''
                }
                confirmLabel="Delete Attribute"
                cancelLabel="Cancel"
                onCancel={() => setAttributeToDelete(null)}
                onConfirm={destroyAttribute}
            />
        </AdminLayout>
    )
}
