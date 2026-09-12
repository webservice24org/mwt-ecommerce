import AdminLayout from '@/Layouts/Admin/AdminLayout'
import { Link, router, usePage } from '@inertiajs/react'
import ConfirmDialog from '@/Components/Admin/ConfirmDialog'
import { useState } from 'react'

type AdminRole = 'super_admin' | 'admin' | 'manager' | 'editor'

type Admin = {
    id: number
    name: string
    email: string
    role: AdminRole
    is_active: boolean
    created_at: string
}

type PaginationLink = {
    url: string | null
    label: string
    active: boolean
}

type PaginatedAdmins = {
    data: Admin[]
    links: PaginationLink[]
}

type Props = {
    admins: PaginatedAdmins
}

function getRoleLabel(role: AdminRole): string {
    switch (role) {
        case 'super_admin':
            return 'Super Admin'

        case 'admin':
            return 'Admin'

        case 'manager':
            return 'Manager'

        case 'editor':
            return 'Editor'
    }
}

export default function Index({ admins }: Props) {
    const [adminToDelete, setAdminToDelete] = useState<Admin | null>(null)

    const [deleting, setDeleting] = useState(false)

    const { errors } = usePage().props

    const confirmDelete = () => {
        if (!adminToDelete) {
            return
        }

        setDeleting(true)

        router.delete(route('admin.admins.destroy', adminToDelete.id), {
            preserveScroll: true,

            onSuccess: () => {
                setAdminToDelete(null)
            },

            onFinish: () => {
                setDeleting(false)
            },
        })
    }

    return (
        <AdminLayout
            title="Administrators"
            description="Manage administrator accounts, roles, and access."
            actions={
                <Link
                    href={route('admin.admins.create')}
                    className="inline-flex items-center justify-center rounded-md bg-neutral-900 px-4 py-2 text-sm font-medium text-white transition hover:bg-neutral-800"
                >
                    Create Administrator
                </Link>
            }
        >
            {errors.admin && (
                <div className="mb-6 rounded-lg border border-red-200 bg-red-50 px-4 py-3 text-sm text-red-700">
                    {errors.admin}
                </div>
            )}
            <div className="overflow-hidden rounded-xl border border-neutral-200 bg-white shadow-sm">
                <div className="overflow-x-auto">
                    <table className="min-w-full divide-y divide-neutral-200">
                        <thead className="bg-neutral-50">
                            <tr>
                                <th className="px-4 py-3 text-left text-xs font-semibold uppercase tracking-wide text-neutral-500">
                                    Name
                                </th>

                                <th className="px-4 py-3 text-left text-xs font-semibold uppercase tracking-wide text-neutral-500">
                                    Email
                                </th>

                                <th className="px-4 py-3 text-left text-xs font-semibold uppercase tracking-wide text-neutral-500">
                                    Role
                                </th>

                                <th className="px-4 py-3 text-left text-xs font-semibold uppercase tracking-wide text-neutral-500">
                                    Status
                                </th>

                                <th className="px-4 py-3 text-right text-xs font-semibold uppercase tracking-wide text-neutral-500">
                                    Actions
                                </th>
                            </tr>
                        </thead>

                        <tbody className="divide-y divide-neutral-200 bg-white">
                            {admins.data.length === 0 ? (
                                <tr>
                                    <td
                                        colSpan={5}
                                        className="px-4 py-10 text-center text-sm text-neutral-500"
                                    >
                                        No administrators found.
                                    </td>
                                </tr>
                            ) : (
                                admins.data.map((admin) => (
                                    <tr key={admin.id} className="transition hover:bg-neutral-50">
                                        <td className="whitespace-nowrap px-4 py-4 text-sm font-medium text-neutral-900">
                                            {admin.name}
                                        </td>

                                        <td className="whitespace-nowrap px-4 py-4 text-sm text-neutral-600">
                                            {admin.email}
                                        </td>

                                        <td className="whitespace-nowrap px-4 py-4 text-sm text-neutral-600">
                                            {getRoleLabel(admin.role)}
                                        </td>

                                        <td className="whitespace-nowrap px-4 py-4">
                                            <span
                                                className={[
                                                    'inline-flex rounded-full px-2.5 py-1 text-xs font-medium',
                                                    admin.is_active
                                                        ? 'bg-green-100 text-green-700'
                                                        : 'bg-red-100 text-red-700',
                                                ].join(' ')}
                                            >
                                                {admin.is_active ? 'Active' : 'Inactive'}
                                            </span>
                                        </td>

                                        <td className="whitespace-nowrap px-4 py-4 text-right">
                                            <div className="flex justify-end gap-2">
                                                <Link
                                                    href={route('admin.admins.edit', admin.id)}
                                                    className="rounded-md border border-neutral-300 bg-white px-3 py-1.5 text-sm font-medium text-neutral-700 transition hover:bg-neutral-50"
                                                >
                                                    Edit
                                                </Link>

                                                <button
                                                    type="button"
                                                    onClick={() => setAdminToDelete(admin)}
                                                    className="rounded-md border border-red-300 bg-white px-3 py-1.5 text-sm font-medium text-red-600 transition hover:bg-red-50"
                                                >
                                                    Delete
                                                </button>
                                            </div>
                                        </td>
                                    </tr>
                                ))
                            )}
                        </tbody>
                    </table>
                </div>
            </div>

            {admins.links.length > 3 && (
                <div className="mt-6 flex flex-wrap gap-2">
                    {admins.links.map((link, index) => {
                        if (!link.url) {
                            return (
                                <span
                                    key={`${link.label}-${index}`}
                                    className="rounded-md border border-neutral-200 bg-white px-3 py-2 text-sm text-neutral-400"
                                    dangerouslySetInnerHTML={{
                                        __html: link.label,
                                    }}
                                />
                            )
                        }

                        return (
                            <Link
                                key={`${link.label}-${index}`}
                                href={link.url}
                                preserveScroll
                                className={[
                                    'rounded-md border px-3 py-2 text-sm font-medium transition',
                                    link.active
                                        ? 'border-neutral-900 bg-neutral-900 text-white'
                                        : 'border-neutral-200 bg-white text-neutral-700 hover:bg-neutral-50',
                                ].join(' ')}
                                dangerouslySetInnerHTML={{
                                    __html: link.label,
                                }}
                            />
                        )
                    })}
                </div>
            )}

            <ConfirmDialog
                open={adminToDelete !== null}
                title="Delete Administrator"
                description={
                    adminToDelete
                        ? `Are you sure you want to delete "${adminToDelete.name}"? This action cannot be undone.`
                        : ''
                }
                confirmLabel="Delete Administrator"
                processing={deleting}
                onCancel={() => {
                    if (!deleting) {
                        setAdminToDelete(null)
                    }
                }}
                onConfirm={confirmDelete}
            />
        </AdminLayout>
    )
}
