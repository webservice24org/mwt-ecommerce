import ConfirmDialog from '@/Components/Admin/ConfirmDialog'
import AdminLayout from '@/Layouts/Admin/AdminLayout'
import type { Category, Paginated } from '@/types/catalog'
import { Link, router } from '@inertiajs/react'
import { FormEvent, useState } from 'react'

type Props = {
    categories: Paginated<Category>

    filters: {
        search: string
        status: string
    }
}

export default function Index({ categories, filters }: Props) {
    const [search, setSearch] = useState(filters.search ?? '')

    const [status, setStatus] = useState(filters.status ?? '')

    const [categoryToDelete, setCategoryToDelete] = useState<Category | null>(null)

    const [deleting, setDeleting] = useState(false)

    const submitFilters = (event: FormEvent<HTMLFormElement>) => {
        event.preventDefault()

        router.get(
            route('admin.categories.index'),
            {
                search: search || undefined,
                status: status || undefined,
            },
            {
                preserveState: true,
                preserveScroll: true,
                replace: true,
            },
        )
    }

    const clearFilters = () => {
        setSearch('')
        setStatus('')

        router.get(
            route('admin.categories.index'),
            {},
            {
                preserveState: true,
                replace: true,
            },
        )
    }

    const deleteCategory = () => {
        if (!categoryToDelete) {
            return
        }

        setDeleting(true)

        router.delete(route('admin.categories.destroy', categoryToDelete.id), {
            preserveScroll: true,

            onSuccess: () => {
                setCategoryToDelete(null)
            },

            onFinish: () => {
                setDeleting(false)
            },
        })
    }

    return (
        <AdminLayout
            title="Categories"
            description="Manage your product catalog categories and hierarchy."
            actions={
                <Link
                    href={route('admin.categories.create')}
                    className="rounded-md bg-neutral-900 px-4 py-2 text-sm font-medium text-white hover:bg-neutral-800"
                >
                    Add Category
                </Link>
            }
        >
            <div className="space-y-5">
                <form
                    onSubmit={submitFilters}
                    className="grid gap-3 rounded-xl border bg-white p-4 shadow-sm sm:grid-cols-[1fr_180px_auto_auto]"
                >
                    <input
                        value={search}
                        onChange={(event) => setSearch(event.target.value)}
                        placeholder="Search name or slug..."
                        className="min-w-0 rounded-md border border-neutral-300 px-3 py-2 text-sm"
                    />

                    <select
                        value={status}
                        onChange={(event) => setStatus(event.target.value)}
                        className="rounded-md border border-neutral-300 px-3 py-2 text-sm"
                    >
                        <option value="">All statuses</option>

                        <option value="active">Active</option>

                        <option value="inactive">Inactive</option>
                    </select>

                    <button
                        type="submit"
                        className="rounded-md bg-neutral-900 px-4 py-2 text-sm font-medium text-white"
                    >
                        Filter
                    </button>

                    <button
                        type="button"
                        onClick={clearFilters}
                        className="rounded-md border px-4 py-2 text-sm font-medium"
                    >
                        Clear
                    </button>
                </form>

                <div className="overflow-hidden rounded-xl border bg-white shadow-sm">
                    <div className="overflow-x-auto">
                        <table className="min-w-full divide-y divide-neutral-200">
                            <thead className="bg-neutral-50">
                                <tr>
                                    <th className="px-4 py-3 text-left text-xs font-semibold uppercase text-neutral-600">
                                        Category
                                    </th>

                                    <th className="px-4 py-3 text-left text-xs font-semibold uppercase text-neutral-600">
                                        Parent
                                    </th>

                                    <th className="px-4 py-3 text-left text-xs font-semibold uppercase text-neutral-600">
                                        Position
                                    </th>

                                    <th className="px-4 py-3 text-left text-xs font-semibold uppercase text-neutral-600">
                                        Status
                                    </th>

                                    <th className="px-4 py-3 text-right text-xs font-semibold uppercase text-neutral-600">
                                        Actions
                                    </th>
                                </tr>
                            </thead>

                            <tbody className="divide-y divide-neutral-100">
                                {categories.data.map((category) => (
                                    <tr key={category.id}>
                                        <td className="px-4 py-4">
                                            <div className="font-medium text-neutral-900">
                                                {category.name}
                                            </div>

                                            <div className="mt-1 text-xs text-neutral-500">
                                                {category.slug}
                                            </div>
                                        </td>

                                        <td className="px-4 py-4 text-sm text-neutral-600">
                                            {category.parent?.name ?? '—'}
                                        </td>

                                        <td className="px-4 py-4 text-sm text-neutral-600">
                                            {category.position}
                                        </td>

                                        <td className="px-4 py-4">
                                            <span
                                                className={`inline-flex rounded-full px-2.5 py-1 text-xs font-medium ${
                                                    category.is_active
                                                        ? 'bg-green-100 text-green-700'
                                                        : 'bg-neutral-100 text-neutral-600'
                                                }`}
                                            >
                                                {category.is_active ? 'Active' : 'Inactive'}
                                            </span>
                                        </td>

                                        <td className="px-4 py-4">
                                            <div className="flex justify-end gap-2">
                                                <Link
                                                    href={route(
                                                        'admin.categories.edit',
                                                        category.id,
                                                    )}
                                                    className="rounded-md border px-3 py-1.5 text-sm font-medium"
                                                >
                                                    Edit
                                                </Link>

                                                <button
                                                    type="button"
                                                    onClick={() => setCategoryToDelete(category)}
                                                    className="rounded-md border border-red-200 px-3 py-1.5 text-sm font-medium text-red-600 hover:bg-red-50"
                                                >
                                                    Delete
                                                </button>
                                            </div>
                                        </td>
                                    </tr>
                                ))}

                                {categories.data.length === 0 && (
                                    <tr>
                                        <td
                                            colSpan={5}
                                            className="px-4 py-12 text-center text-sm text-neutral-500"
                                        >
                                            No categories found.
                                        </td>
                                    </tr>
                                )}
                            </tbody>
                        </table>
                    </div>
                </div>

                {categories.last_page > 1 && (
                    <div className="flex flex-wrap gap-2">
                        {categories.links.map((link, index) => (
                            <Link
                                key={`${link.label}-${index}`}
                                href={link.url ?? '#'}
                                preserveScroll
                                className={`rounded-md border px-3 py-2 text-sm ${
                                    link.active
                                        ? 'bg-neutral-900 text-white'
                                        : 'bg-white text-neutral-700'
                                } ${link.url === null ? 'pointer-events-none opacity-40' : ''}`}
                                dangerouslySetInnerHTML={{
                                    __html: link.label,
                                }}
                            />
                        ))}
                    </div>
                )}
            </div>

            <ConfirmDialog
                open={categoryToDelete !== null}
                title="Delete category?"
                description={
                    categoryToDelete
                        ? `Delete "${categoryToDelete.name}"? Child categories will become top-level categories.`
                        : ''
                }
                confirmLabel="Delete Category"
                cancelLabel="Cancel"
                processing={deleting}
                onConfirm={deleteCategory}
                onCancel={() => setCategoryToDelete(null)}
            />
        </AdminLayout>
    )
}
