import AdminLayout from '@/Layouts/Admin/AdminLayout'
import { Link, useForm, usePage } from '@inertiajs/react'
import { FormEvent } from 'react'

type AdminRole = 'super_admin' | 'admin' | 'manager' | 'editor'

type Admin = {
    id: number
    name: string
    email: string
    role: AdminRole
    is_active: boolean
}

type RoleOption = {
    label: string
    value: AdminRole
}

type Props = {
    admin: Admin
    roles: RoleOption[]
}

export default function Edit({ admin, roles }: Props) {
    const form = useForm({
        name: admin.name,
        email: admin.email,
        password: '',
        password_confirmation: '',
        role: admin.role,
        is_active: admin.is_active,
    })
    const { errors } = usePage().props

    const submit = (event: FormEvent) => {
        event.preventDefault()

        form.put(route('admin.admins.update', admin.id), {
            preserveScroll: true,
        })
    }

    return (
        <AdminLayout
            title="Edit Administrator"
            description="Update administrator details, access level, account status, or password."
            actions={
                <Link
                    href={route('admin.admins.index')}
                    className="inline-flex items-center justify-center rounded-md border border-neutral-300 bg-white px-4 py-2 text-sm font-medium text-neutral-700 transition hover:bg-neutral-50"
                >
                    Back to Administrators
                </Link>
            }
        >
            <div className="max-w-3xl">
                {errors.admin && (
                    <div className="mb-6 rounded-lg border border-red-200 bg-red-50 px-4 py-3 text-sm text-red-700">
                        {errors.admin}
                    </div>
                )}

                <form
                    onSubmit={submit}
                    className="space-y-6 rounded-xl border border-neutral-200 bg-white p-5 shadow-sm sm:p-6"
                >
                    <div>
                        <label
                            htmlFor="name"
                            className="mb-2 block text-sm font-medium text-neutral-700"
                        >
                            Name
                        </label>

                        <input
                            id="name"
                            type="text"
                            value={form.data.name}
                            onChange={(event) => form.setData('name', event.target.value)}
                            autoComplete="name"
                            className="w-full rounded-md border border-neutral-300 bg-white px-3 py-2 text-sm text-neutral-900 outline-none transition focus:border-neutral-500 focus:ring-2 focus:ring-neutral-200"
                        />

                        {form.errors.name && (
                            <p className="mt-2 text-sm text-red-600">{form.errors.name}</p>
                        )}
                    </div>

                    <div>
                        <label
                            htmlFor="email"
                            className="mb-2 block text-sm font-medium text-neutral-700"
                        >
                            Email
                        </label>

                        <input
                            id="email"
                            type="email"
                            value={form.data.email}
                            onChange={(event) => form.setData('email', event.target.value)}
                            autoComplete="email"
                            className="w-full rounded-md border border-neutral-300 bg-white px-3 py-2 text-sm text-neutral-900 outline-none transition focus:border-neutral-500 focus:ring-2 focus:ring-neutral-200"
                        />

                        {form.errors.email && (
                            <p className="mt-2 text-sm text-red-600">{form.errors.email}</p>
                        )}
                    </div>

                    <div>
                        <label
                            htmlFor="role"
                            className="mb-2 block text-sm font-medium text-neutral-700"
                        >
                            Role
                        </label>

                        <select
                            id="role"
                            value={form.data.role}
                            onChange={(event) =>
                                form.setData('role', event.target.value as AdminRole)
                            }
                            className="w-full rounded-md border border-neutral-300 bg-white px-3 py-2 text-sm text-neutral-900 outline-none transition focus:border-neutral-500 focus:ring-2 focus:ring-neutral-200"
                        >
                            {roles.map((role) => (
                                <option key={role.value} value={role.value}>
                                    {role.label}
                                </option>
                            ))}
                        </select>

                        {form.errors.role && (
                            <p className="mt-2 text-sm text-red-600">{form.errors.role}</p>
                        )}
                    </div>

                    <div className="grid gap-6 md:grid-cols-2">
                        <div>
                            <label
                                htmlFor="password"
                                className="mb-2 block text-sm font-medium text-neutral-700"
                            >
                                New Password
                            </label>

                            <input
                                id="password"
                                type="password"
                                value={form.data.password}
                                onChange={(event) => form.setData('password', event.target.value)}
                                autoComplete="new-password"
                                className="w-full rounded-md border border-neutral-300 bg-white px-3 py-2 text-sm text-neutral-900 outline-none transition focus:border-neutral-500 focus:ring-2 focus:ring-neutral-200"
                            />

                            <p className="mt-1 text-xs text-neutral-500">
                                Leave blank to keep the current password.
                            </p>

                            {form.errors.password && (
                                <p className="mt-2 text-sm text-red-600">{form.errors.password}</p>
                            )}
                        </div>

                        <div>
                            <label
                                htmlFor="password_confirmation"
                                className="mb-2 block text-sm font-medium text-neutral-700"
                            >
                                Confirm New Password
                            </label>

                            <input
                                id="password_confirmation"
                                type="password"
                                value={form.data.password_confirmation}
                                onChange={(event) =>
                                    form.setData('password_confirmation', event.target.value)
                                }
                                autoComplete="new-password"
                                className="w-full rounded-md border border-neutral-300 bg-white px-3 py-2 text-sm text-neutral-900 outline-none transition focus:border-neutral-500 focus:ring-2 focus:ring-neutral-200"
                            />
                        </div>
                    </div>

                    <div className="rounded-lg border border-neutral-200 bg-neutral-50 p-4">
                        <label className="flex cursor-pointer items-start gap-3">
                            <input
                                type="checkbox"
                                checked={form.data.is_active}
                                onChange={(event) =>
                                    form.setData('is_active', event.target.checked)
                                }
                                className="mt-0.5 h-4 w-4 rounded border-neutral-300"
                            />

                            <span>
                                <span className="block text-sm font-medium text-neutral-800">
                                    Active administrator
                                </span>

                                <span className="mt-1 block text-sm text-neutral-500">
                                    Inactive administrators cannot sign in to the admin panel.
                                </span>
                            </span>
                        </label>

                        {form.errors.is_active && (
                            <p className="mt-2 text-sm text-red-600">{form.errors.is_active}</p>
                        )}
                    </div>

                    {admin.role === 'super_admin' && (
                        <div className="rounded-lg border border-amber-200 bg-amber-50 p-4">
                            <p className="text-sm font-medium text-amber-800">
                                Super Admin Protection
                            </p>

                            <p className="mt-1 text-sm leading-6 text-amber-700">
                                If this is the final active Super Admin account, the system will
                                prevent demoting or deactivating it.
                            </p>
                        </div>
                    )}

                    <div className="rounded-lg border border-neutral-200 bg-neutral-50 p-4">
                        <p className="text-sm font-medium text-neutral-800">
                            Password requirements
                        </p>

                        <p className="mt-1 text-sm leading-6 text-neutral-500">
                            If changing the password, use at least 12 characters with uppercase and
                            lowercase letters, numbers, and symbols.
                        </p>
                    </div>

                    <div className="flex flex-col-reverse gap-3 border-t border-neutral-200 pt-6 sm:flex-row sm:justify-end">
                        <Link
                            href={route('admin.admins.index')}
                            className="inline-flex items-center justify-center rounded-md border border-neutral-300 bg-white px-4 py-2 text-sm font-medium text-neutral-700 transition hover:bg-neutral-50"
                        >
                            Cancel
                        </Link>

                        <button
                            type="submit"
                            disabled={form.processing}
                            className="inline-flex items-center justify-center rounded-md bg-neutral-900 px-4 py-2 text-sm font-medium text-white transition hover:bg-neutral-800 disabled:cursor-not-allowed disabled:opacity-50"
                        >
                            {form.processing ? 'Saving...' : 'Save Changes'}
                        </button>
                    </div>
                </form>
            </div>
        </AdminLayout>
    )
}
