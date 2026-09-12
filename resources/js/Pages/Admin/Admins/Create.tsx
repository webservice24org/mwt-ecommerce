import AdminLayout from '@/Layouts/Admin/AdminLayout'
import { Link, useForm } from '@inertiajs/react'
import { FormEvent } from 'react'

type RoleOption = {
    label: string
    value: string
}

type Props = {
    roles: RoleOption[]
}

export default function Create({ roles }: Props) {
    const form = useForm({
        name: '',
        email: '',
        password: '',
        password_confirmation: '',
        role: 'admin',
        is_active: true,
    })

    const submit = (event: FormEvent) => {
        event.preventDefault()

        form.post(route('admin.admins.store'))
    }

    return (
        <AdminLayout
            title="Create Administrator"
            description="Create a new administrator account and assign its access level."
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
                            onChange={(event) => form.setData('role', event.target.value)}
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
                                Password
                            </label>

                            <input
                                id="password"
                                type="password"
                                value={form.data.password}
                                onChange={(event) => form.setData('password', event.target.value)}
                                autoComplete="new-password"
                                className="w-full rounded-md border border-neutral-300 bg-white px-3 py-2 text-sm text-neutral-900 outline-none transition focus:border-neutral-500 focus:ring-2 focus:ring-neutral-200"
                            />

                            {form.errors.password && (
                                <p className="mt-2 text-sm text-red-600">{form.errors.password}</p>
                            )}
                        </div>

                        <div>
                            <label
                                htmlFor="password_confirmation"
                                className="mb-2 block text-sm font-medium text-neutral-700"
                            >
                                Confirm Password
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

                    <div className="rounded-lg border border-amber-200 bg-amber-50 p-4">
                        <p className="text-sm font-medium text-amber-800">
                            Administrator password requirements
                        </p>

                        <p className="mt-1 text-sm leading-6 text-amber-700">
                            Use at least 12 characters with uppercase and lowercase letters,
                            numbers, and symbols.
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
                            {form.processing ? 'Creating...' : 'Create Administrator'}
                        </button>
                    </div>
                </form>
            </div>
        </AdminLayout>
    )
}
