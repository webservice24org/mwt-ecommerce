import { Head, useForm } from '@inertiajs/react'
import type { FormEvent } from 'react'

type Props = {
    token: string
    email: string
}

export default function ResetPassword({ token, email }: Props) {
    const { data, setData, post, processing, errors, reset } = useForm({
        token,
        email,
        password: '',
        password_confirmation: '',
    })

    const submit = (event: FormEvent<HTMLFormElement>) => {
        event.preventDefault()

        post(route('admin.password.store'), {
            onFinish: () => {
                reset('password', 'password_confirmation')
            },
        })
    }

    return (
        <>
            <Head title="Reset Admin Password" />

            <main className="flex min-h-screen items-center justify-center bg-neutral-100 px-4">
                <div className="w-full max-w-md rounded-2xl bg-white p-8 shadow-sm">
                    <h1 className="text-2xl font-semibold text-neutral-900">
                        Choose a new password
                    </h1>

                    <form onSubmit={submit} className="mt-6 space-y-5">
                        <div>
                            <label htmlFor="email" className="mb-2 block text-sm font-medium">
                                Email
                            </label>

                            <input
                                id="email"
                                type="email"
                                value={data.email}
                                readOnly
                                className="w-full rounded-lg border border-neutral-300 bg-neutral-50 px-3 py-2"
                            />

                            {errors.email && (
                                <p className="mt-2 text-sm text-red-600">{errors.email}</p>
                            )}
                        </div>

                        <div>
                            <label htmlFor="password" className="mb-2 block text-sm font-medium">
                                New password
                            </label>

                            <input
                                id="password"
                                type="password"
                                autoComplete="new-password"
                                value={data.password}
                                onChange={(event) => setData('password', event.target.value)}
                                className="w-full rounded-lg border border-neutral-300 px-3 py-2"
                            />

                            {errors.password && (
                                <p className="mt-2 text-sm text-red-600">{errors.password}</p>
                            )}
                        </div>

                        <div>
                            <label
                                htmlFor="password_confirmation"
                                className="mb-2 block text-sm font-medium"
                            >
                                Confirm password
                            </label>

                            <input
                                id="password_confirmation"
                                type="password"
                                autoComplete="new-password"
                                value={data.password_confirmation}
                                onChange={(event) =>
                                    setData('password_confirmation', event.target.value)
                                }
                                className="w-full rounded-lg border border-neutral-300 px-3 py-2"
                            />
                        </div>

                        <button
                            type="submit"
                            disabled={processing}
                            className="w-full rounded-lg bg-neutral-900 px-4 py-2.5 font-medium text-white disabled:opacity-60"
                        >
                            Reset password
                        </button>
                    </form>
                </div>
            </main>
        </>
    )
}
