import { Link, router } from '@inertiajs/react'
import { useState } from 'react'
import type { FormEvent, ReactNode } from 'react'

import ConfirmDialog from '@/Components/Admin/ConfirmDialog'
import AdminLayout from '@/Layouts/Admin/AdminLayout'
import type {
    PageAbilities,
    PageFilters,
    PageFormOptions,
    PaginatedPages,
    PageStatus,
    PageType,
} from '@/types/page-builder'

interface Props {
    pages: PaginatedPages
    filters: PageFilters
    options: PageFormOptions
    abilities: PageAbilities
}

interface DeleteTarget {
    id: number
    title: string
}

export default function Index({ pages, filters, options, abilities }: Props) {
    const [search, setSearch] = useState(filters.search ?? '')

    const [status, setStatus] = useState<PageStatus | ''>(filters.status ?? '')

    const [type, setType] = useState<PageType | ''>(filters.type ?? '')

    const [deleteTarget, setDeleteTarget] = useState<DeleteTarget | null>(null)

    const [deleting, setDeleting] = useState(false)

    const submitFilters = (event: FormEvent<HTMLFormElement>) => {
        event.preventDefault()

        router.get(
            route('admin.pages.index'),
            {
                search: search || undefined,
                status: status || undefined,
                type: type || undefined,
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
        setType('')

        router.get(
            route('admin.pages.index'),
            {},
            {
                preserveState: true,
                replace: true,
            },
        )
    }

    const destroyPage = () => {
        if (!deleteTarget || deleting) {
            return
        }

        setDeleting(true)

        router.delete(route('admin.pages.destroy', deleteTarget.id), {
            preserveScroll: true,

            onSuccess: () => {
                setDeleteTarget(null)
            },

            onFinish: () => {
                setDeleting(false)
            },
        })
    }

    const closeDeleteDialog = () => {
        if (deleting) {
            return
        }

        setDeleteTarget(null)
    }

    return (
        <AdminLayout
            title="Pages"
            description="Manage storefront pages and their Page Builder content."
            actions={
                abilities.create ? (
                    <Link
                        href={route('admin.pages.create')}
                        className="inline-flex items-center rounded-lg bg-neutral-900 px-4 py-2 text-sm font-medium text-white transition hover:bg-neutral-700 dark:bg-neutral-100 dark:text-neutral-900 dark:hover:bg-neutral-300"
                    >
                        Create Page
                    </Link>
                ) : null
            }
        >
            <form
                onSubmit={submitFilters}
                className="mb-6 grid gap-3 rounded-xl border border-neutral-200 bg-white p-4 shadow-sm sm:grid-cols-2 lg:grid-cols-5 dark:border-neutral-800 dark:bg-neutral-950"
            >
                <input
                    type="search"
                    value={search}
                    onChange={(event) => setSearch(event.target.value)}
                    placeholder="Search pages..."
                    className={`${inputClass} lg:col-span-2`}
                />

                <select
                    value={status}
                    onChange={(event) => setStatus(event.target.value as PageStatus | '')}
                    className={inputClass}
                >
                    <option value="">All statuses</option>

                    {options.statuses.map((option) => (
                        <option key={option.value} value={option.value}>
                            {option.label}
                        </option>
                    ))}
                </select>

                <select
                    value={type}
                    onChange={(event) => setType(event.target.value as PageType | '')}
                    className={inputClass}
                >
                    <option value="">All types</option>

                    {options.types.map((option) => (
                        <option key={option.value} value={option.value}>
                            {option.label}
                        </option>
                    ))}
                </select>

                <div className="flex gap-2">
                    <button
                        type="submit"
                        className="flex-1 rounded-lg bg-neutral-900 px-3 py-2 text-sm font-medium text-white transition hover:bg-neutral-700 dark:bg-neutral-100 dark:text-neutral-900 dark:hover:bg-neutral-300"
                    >
                        Filter
                    </button>

                    <button
                        type="button"
                        onClick={resetFilters}
                        className="rounded-lg border border-neutral-300 px-3 py-2 text-sm text-neutral-700 transition hover:bg-neutral-50 dark:border-neutral-700 dark:text-neutral-200 dark:hover:bg-neutral-900"
                    >
                        Reset
                    </button>
                </div>
            </form>

            <div className="overflow-hidden rounded-xl border border-neutral-200 bg-white shadow-sm dark:border-neutral-800 dark:bg-neutral-950">
                <div className="overflow-x-auto">
                    <table className="min-w-full divide-y divide-neutral-200 dark:divide-neutral-800">
                        <thead className="bg-neutral-50 dark:bg-neutral-900">
                            <tr>
                                <TableHead>Page</TableHead>

                                <TableHead>Type</TableHead>

                                <TableHead>Status</TableHead>

                                <TableHead>Sections</TableHead>

                                <TableHead>Published</TableHead>

                                <TableHead align="right">Actions</TableHead>
                            </tr>
                        </thead>

                        <tbody className="divide-y divide-neutral-200 dark:divide-neutral-800">
                            {pages.data.map((page) => (
                                <tr
                                    key={page.id}
                                    className="transition hover:bg-neutral-50 dark:hover:bg-neutral-900/50"
                                >
                                    <td className="px-4 py-4">
                                        <div className="font-medium text-neutral-900 dark:text-neutral-100">
                                            {page.title}
                                        </div>

                                        <div className="mt-1 text-xs text-neutral-500">
                                            /{page.slug}
                                        </div>
                                    </td>

                                    <td className="px-4 py-4 text-sm text-neutral-600 dark:text-neutral-300">
                                        {page.type === 'home' ? 'Homepage' : 'Standard'}
                                    </td>

                                    <td className="px-4 py-4">
                                        <StatusBadge status={page.status} />
                                    </td>

                                    <td className="px-4 py-4 text-sm text-neutral-600 dark:text-neutral-300">
                                        {page.sections_count}
                                    </td>

                                    <td className="px-4 py-4 text-sm text-neutral-600 dark:text-neutral-300">
                                        {formatDate(page.published_at)}
                                    </td>

                                    <td className="px-4 py-4">
                                        <div className="flex justify-end gap-2">
                                            <Link
                                                href={route('admin.pages.edit', page.id)}
                                                className="rounded-md border border-neutral-300 px-3 py-1.5 text-sm text-neutral-700 transition hover:bg-neutral-50 dark:border-neutral-700 dark:text-neutral-200 dark:hover:bg-neutral-900"
                                            >
                                                Edit
                                            </Link>

                                            {abilities.delete && (
                                                <button
                                                    type="button"
                                                    onClick={() =>
                                                        setDeleteTarget({
                                                            id: page.id,
                                                            title: page.title,
                                                        })
                                                    }
                                                    className="rounded-md border border-red-300 px-3 py-1.5 text-sm text-red-600 transition hover:bg-red-50 dark:border-red-900 dark:hover:bg-red-950/30"
                                                >
                                                    Delete
                                                </button>
                                            )}
                                        </div>
                                    </td>
                                </tr>
                            ))}

                            {pages.data.length === 0 && (
                                <tr>
                                    <td
                                        colSpan={6}
                                        className="px-4 py-12 text-center text-sm text-neutral-500"
                                    >
                                        No pages found.
                                    </td>
                                </tr>
                            )}
                        </tbody>
                    </table>
                </div>

                <Pagination pages={pages} />
            </div>

            <ConfirmDialog
                open={deleteTarget !== null}
                title="Delete page?"
                description={
                    deleteTarget
                        ? `Are you sure you want to delete "${deleteTarget.title}"? All sections belonging to this page will also be permanently deleted.`
                        : ''
                }
                confirmLabel="Delete Page"
                processing={deleting}
                onConfirm={destroyPage}
                onCancel={closeDeleteDialog}
            />
        </AdminLayout>
    )
}

function StatusBadge({ status }: { status: PageStatus }) {
    return (
        <span className="inline-flex rounded-full border border-neutral-200 px-2.5 py-1 text-xs font-medium capitalize text-neutral-700 dark:border-neutral-700 dark:text-neutral-200">
            {status}
        </span>
    )
}

function TableHead({
    children,
    align = 'left',
}: {
    children: ReactNode
    align?: 'left' | 'right'
}) {
    const alignment = align === 'right' ? 'text-right' : 'text-left'

    return (
        <th
            className={`px-4 py-3 ${alignment} text-xs font-semibold uppercase tracking-wide text-neutral-500`}
        >
            {children}
        </th>
    )
}

function Pagination({ pages }: { pages: PaginatedPages }) {
    if (pages.last_page <= 1) {
        return null
    }

    return (
        <div className="flex flex-col gap-3 border-t border-neutral-200 px-4 py-4 sm:flex-row sm:items-center sm:justify-between dark:border-neutral-800">
            <p className="text-sm text-neutral-500">
                Showing {pages.from ?? 0}–{pages.to ?? 0} of {pages.total}
            </p>

            <div className="flex flex-wrap gap-1">
                {pages.links.map((link, index) =>
                    link.url ? (
                        <Link
                            key={index}
                            href={link.url}
                            preserveScroll
                            className={`rounded-md border px-3 py-1.5 text-sm ${
                                link.active
                                    ? 'border-neutral-900 bg-neutral-900 text-white dark:border-neutral-100 dark:bg-neutral-100 dark:text-neutral-900'
                                    : 'border-neutral-300 text-neutral-700 dark:border-neutral-700 dark:text-neutral-200'
                            }`}
                            dangerouslySetInnerHTML={{
                                __html: link.label,
                            }}
                        />
                    ) : (
                        <span
                            key={index}
                            className="cursor-not-allowed rounded-md border border-neutral-200 px-3 py-1.5 text-sm text-neutral-400 dark:border-neutral-800"
                            dangerouslySetInnerHTML={{
                                __html: link.label,
                            }}
                        />
                    ),
                )}
            </div>
        </div>
    )
}

function formatDate(value: string | null): string {
    if (!value) {
        return '—'
    }

    const date = new Date(value)

    if (Number.isNaN(date.getTime())) {
        return '—'
    }

    return new Intl.DateTimeFormat(undefined, {
        year: 'numeric',
        month: 'short',
        day: 'numeric',
    }).format(date)
}

const inputClass =
    'w-full rounded-lg border border-neutral-300 bg-white px-3 py-2 text-sm text-neutral-900 shadow-sm outline-none focus:border-neutral-500 focus:ring-2 focus:ring-neutral-200 dark:border-neutral-700 dark:bg-neutral-900 dark:text-neutral-100 dark:focus:ring-neutral-800'
