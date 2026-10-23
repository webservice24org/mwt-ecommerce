import { Head, Link, useForm } from '@inertiajs/react'
import { ArrowLeft, Mail, ShieldAlert, UserRound } from 'lucide-react'
import type { FormEvent } from 'react'

import AdminLayout from '@/Layouts/Admin/AdminLayout'

type Customer = {
    id: number
    name: string
    email: string
    status: string
    status_label: string
    verified: boolean
}

type CustomerForm = {
    name: string
    email: string
}

type Props = {
    customer: Customer
}

export default function Edit({ customer }: Props) {
    const { data, setData, put, processing, errors } = useForm<CustomerForm>({
        name: customer.name,
        email: customer.email,
    })

    const normalizedEmail = data.email.trim().toLowerCase()

    const emailWillChange = normalizedEmail !== customer.email.toLowerCase()

    const submit = (event: FormEvent<HTMLFormElement>) => {
        event.preventDefault()

        put(route('admin.customers.update', customer.id), {
            preserveScroll: true,
        })
    }

    return (
        <AdminLayout
            title="Edit customer"
            description="Update approved customer profile fields."
            actions={
                <Link
                    href={route('admin.customers.show', customer.id)}
                    className="inline-flex min-h-10 items-center gap-2 rounded-lg border border-neutral-300 px-4 text-sm font-medium text-neutral-700 transition hover:bg-neutral-50 dark:border-neutral-700 dark:text-neutral-200 dark:hover:bg-neutral-900"
                >
                    <ArrowLeft className="h-4 w-4" aria-hidden="true" />
                    Customer
                </Link>
            }
        >
            <Head title={`Edit Customer - ${customer.name}`} />

            <div className="mx-auto max-w-3xl space-y-6">
                <section className="rounded-xl border border-neutral-200 bg-white p-5 shadow-sm dark:border-neutral-800 dark:bg-neutral-950">
                    <div className="flex items-start gap-3">
                        <span className="flex h-10 w-10 shrink-0 items-center justify-center rounded-xl bg-neutral-100 text-neutral-600 dark:bg-neutral-900 dark:text-neutral-300">
                            <UserRound className="h-5 w-5" aria-hidden="true" />
                        </span>

                        <div>
                            <h2 className="font-semibold text-neutral-900 dark:text-white">
                                Profile information
                            </h2>

                            <p className="mt-1 text-sm leading-6 text-neutral-500">
                                Only the customer&apos;s display name and email address can be
                                changed here.
                            </p>
                        </div>
                    </div>

                    <div className="mt-5 grid gap-3 rounded-xl border border-neutral-200 bg-neutral-50 p-4 sm:grid-cols-2 dark:border-neutral-800 dark:bg-neutral-900/50">
                        <div>
                            <p className="text-xs font-medium uppercase tracking-wide text-neutral-500">
                                Account status
                            </p>

                            <p className="mt-1 text-sm font-semibold text-neutral-800 dark:text-neutral-200">
                                {customer.status_label}
                            </p>
                        </div>

                        <div>
                            <p className="text-xs font-medium uppercase tracking-wide text-neutral-500">
                                Email verification
                            </p>

                            <p className="mt-1 text-sm font-semibold text-neutral-800 dark:text-neutral-200">
                                {customer.verified ? 'Verified' : 'Unverified'}
                            </p>
                        </div>
                    </div>

                    <form onSubmit={submit} className="mt-6 space-y-5">
                        <div>
                            <label
                                htmlFor="name"
                                className="mb-2 block text-sm font-semibold text-neutral-700 dark:text-neutral-200"
                            >
                                Full name
                            </label>

                            <div className="relative">
                                <UserRound
                                    className="pointer-events-none absolute left-3.5 top-1/2 h-4 w-4 -translate-y-1/2 text-neutral-400"
                                    aria-hidden="true"
                                />

                                <input
                                    id="name"
                                    name="name"
                                    type="text"
                                    value={data.name}
                                    onChange={(event) => setData('name', event.target.value)}
                                    autoComplete="name"
                                    autoFocus
                                    required
                                    maxLength={120}
                                    aria-invalid={errors.name ? true : undefined}
                                    aria-describedby={errors.name ? 'name-error' : undefined}
                                    className={`${inputClass(Boolean(errors.name))} pl-10`}
                                />
                            </div>

                            {errors.name && (
                                <p
                                    id="name-error"
                                    role="alert"
                                    className="mt-2 text-xs font-medium text-red-600 dark:text-red-300"
                                >
                                    {errors.name}
                                </p>
                            )}
                        </div>

                        <div>
                            <label
                                htmlFor="email"
                                className="mb-2 block text-sm font-semibold text-neutral-700 dark:text-neutral-200"
                            >
                                Email address
                            </label>

                            <div className="relative">
                                <Mail
                                    className="pointer-events-none absolute left-3.5 top-1/2 h-4 w-4 -translate-y-1/2 text-neutral-400"
                                    aria-hidden="true"
                                />

                                <input
                                    id="email"
                                    name="email"
                                    type="email"
                                    value={data.email}
                                    onChange={(event) => setData('email', event.target.value)}
                                    autoComplete="email"
                                    inputMode="email"
                                    required
                                    maxLength={254}
                                    autoCapitalize="none"
                                    spellCheck={false}
                                    aria-invalid={errors.email ? true : undefined}
                                    aria-describedby={
                                        errors.email
                                            ? 'email-error'
                                            : emailWillChange
                                              ? 'email-change-warning'
                                              : undefined
                                    }
                                    className={`${inputClass(Boolean(errors.email))} pl-10`}
                                />
                            </div>

                            {errors.email && (
                                <p
                                    id="email-error"
                                    role="alert"
                                    className="mt-2 text-xs font-medium text-red-600 dark:text-red-300"
                                >
                                    {errors.email}
                                </p>
                            )}
                        </div>

                        {emailWillChange && (
                            <div
                                id="email-change-warning"
                                className="flex gap-3 rounded-xl border border-amber-200 bg-amber-50 p-4 text-amber-800 dark:border-amber-500/20 dark:bg-amber-500/10 dark:text-amber-200"
                            >
                                <ShieldAlert
                                    className="mt-0.5 h-5 w-5 shrink-0"
                                    aria-hidden="true"
                                />

                                <div>
                                    <p className="text-sm font-semibold">
                                        Email verification will be reset
                                    </p>

                                    <p className="mt-1 text-xs leading-5">
                                        The new email address must be verified again. The previous
                                        email&apos;s verified state will not transfer to the new
                                        address.
                                    </p>
                                </div>
                            </div>
                        )}

                        <div className="flex flex-col-reverse gap-3 border-t border-neutral-200 pt-5 sm:flex-row sm:justify-end dark:border-neutral-800">
                            <Link
                                href={route('admin.customers.show', customer.id)}
                                className="inline-flex min-h-11 items-center justify-center rounded-lg border border-neutral-300 px-4 text-sm font-semibold text-neutral-700 transition hover:bg-neutral-50 dark:border-neutral-700 dark:text-neutral-200 dark:hover:bg-neutral-900"
                            >
                                Cancel
                            </Link>

                            <button
                                type="submit"
                                disabled={processing}
                                className="inline-flex min-h-11 items-center justify-center rounded-lg bg-neutral-900 px-5 text-sm font-semibold text-white transition hover:bg-neutral-700 disabled:cursor-not-allowed disabled:opacity-60 dark:bg-neutral-100 dark:text-neutral-900 dark:hover:bg-neutral-300"
                            >
                                {processing ? 'Saving...' : 'Save changes'}
                            </button>
                        </div>
                    </form>
                </section>

                <section className="rounded-xl border border-neutral-200 bg-neutral-50 p-4 dark:border-neutral-800 dark:bg-neutral-900/40">
                    <p className="text-sm font-semibold text-neutral-800 dark:text-neutral-200">
                        Security controls are managed separately
                    </p>

                    <p className="mt-1 text-xs leading-5 text-neutral-500">
                        Passwords cannot be viewed or changed from this profile form. Account
                        block/unblock controls will be handled through dedicated protected actions.
                    </p>
                </section>
            </div>
        </AdminLayout>
    )
}

function inputClass(invalid: boolean): string {
    return [
        'min-h-11 w-full rounded-lg border bg-white px-3 text-sm text-neutral-900 outline-none transition focus:ring-2 dark:bg-neutral-950 dark:text-neutral-100',
        invalid
            ? 'border-red-400 focus:border-red-500 focus:ring-red-100 dark:border-red-500/60 dark:focus:ring-red-500/10'
            : 'border-neutral-300 focus:border-neutral-500 focus:ring-neutral-200 dark:border-neutral-700 dark:focus:border-neutral-500 dark:focus:ring-neutral-800',
    ].join(' ')
}
