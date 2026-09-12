import { Head, Link, useForm } from '@inertiajs/react'
import type { FormEvent } from 'react'

type LoginForm = {
    email: string
    password: string
    remember: boolean
}

export default function Login() {
    const { data, setData, post, processing, errors } = useForm<LoginForm>({
        email: '',
        password: '',
        remember: false,
    })

    const submit = (event: FormEvent<HTMLFormElement>) => {
        event.preventDefault()

        post(route('admin.login.store'), {
            preserveScroll: true,
            onFinish: () => {
                setData('password', '')
            },
        })
    }

    return (
        <>
            <Head title="Admin Login" />

            <main className="flex min-h-screen items-center justify-center bg-neutral-100 px-4">
                <div className="w-full max-w-md rounded-2xl bg-white p-8 shadow-sm">
                    <div className="mb-8">
                        <h1 className="text-2xl font-semibold text-neutral-900">Admin Login</h1>

                        <p className="mt-2 text-sm text-neutral-500">
                            Sign in to manage the ecommerce application.
                        </p>
                    </div>

                    <form onSubmit={submit} className="space-y-5">
                        <div>
                            <label htmlFor="email" className="mb-2 block text-sm font-medium">
                                Email
                            </label>

                            <input
                                id="email"
                                type="email"
                                autoComplete="username"
                                value={data.email}
                                onChange={(event) => setData('email', event.target.value)}
                                className="w-full rounded-lg border border-neutral-300 px-3 py-2 outline-none focus:border-neutral-900"
                            />

                            {errors.email && (
                                <p className="mt-2 text-sm text-red-600">{errors.email}</p>
                            )}
                        </div>

                        <div>
                            <label htmlFor="password" className="mb-2 block text-sm font-medium">
                                Password
                            </label>

                            <input
                                id="password"
                                type="password"
                                autoComplete="current-password"
                                value={data.password}
                                onChange={(event) => setData('password', event.target.value)}
                                className="w-full rounded-lg border border-neutral-300 px-3 py-2 outline-none focus:border-neutral-900"
                            />

                            {errors.password && (
                                <p className="mt-2 text-sm text-red-600">{errors.password}</p>
                            )}
                        </div>

                        <label className="flex items-center gap-2 text-sm">
                            <input
                                type="checkbox"
                                checked={data.remember}
                                onChange={(event) => setData('remember', event.target.checked)}
                            />
                            Remember me
                        </label>

                        <Link
                            href={route('admin.password.request')}
                            className="text-sm font-medium text-neutral-700 hover:text-neutral-950"
                        >
                            Forgot password?
                        </Link>

                        <button
                            type="submit"
                            disabled={processing}
                            className="w-full rounded-lg bg-neutral-900 px-4 py-2.5 font-medium text-white disabled:opacity-60"
                        >
                            {processing ? 'Signing in...' : 'Sign in'}
                        </button>
                    </form>
                </div>
            </main>
        </>
    )
}
