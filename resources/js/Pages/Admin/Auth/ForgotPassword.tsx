import { Head, useForm } from '@inertiajs/react'
import type { FormEvent } from 'react'

type Props = {
    status?: string
}

export default function ForgotPassword({ status }: Props) {
    const { data, setData, post, processing, errors } = useForm({
        email: '',
    })

    const submit = (event: FormEvent<HTMLFormElement>) => {
        event.preventDefault()

        post(route('admin.password.email'))
    }

    return (
        <>
            <Head title="Admin Password Reset" />

            <main className="flex min-h-screen items-center justify-center bg-neutral-100 px-4">
                <div className="w-full max-w-md rounded-2xl bg-white p-8 shadow-sm">
                    <h1 className="text-2xl font-semibold text-neutral-900">
                        Reset admin password
                    </h1>

                    <p className="mt-2 text-sm text-neutral-500">
                        Enter your administrator email address.
                    </p>

                    {status && (
                        <div className="mt-4 rounded-lg bg-green-50 p-3 text-sm text-green-700">
                            {status}
                        </div>
                    )}

                    <form onSubmit={submit} className="mt-6 space-y-5">
                        <div>
                            <label htmlFor="email" className="mb-2 block text-sm font-medium">
                                Email
                            </label>

                            <input
                                id="email"
                                type="email"
                                autoComplete="email"
                                value={data.email}
                                onChange={(event) => setData('email', event.target.value)}
                                className="w-full rounded-lg border border-neutral-300 px-3 py-2"
                            />

                            {errors.email && (
                                <p className="mt-2 text-sm text-red-600">{errors.email}</p>
                            )}
                        </div>

                        <button
                            type="submit"
                            disabled={processing}
                            className="w-full rounded-lg bg-neutral-900 px-4 py-2.5 font-medium text-white disabled:opacity-60"
                        >
                            Send reset link
                        </button>
                    </form>
                </div>
            </main>
        </>
    )
}
