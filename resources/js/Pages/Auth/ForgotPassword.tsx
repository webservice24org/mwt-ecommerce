import { Head, Link, useForm } from '@inertiajs/react'
import {
    ArrowLeft,
    Mail,
    Send,
    ShieldCheck,
} from 'lucide-react'
import { type FormEvent } from 'react'

interface ForgotPasswordForm {
    email: string
}

export default function ForgotPassword({
    status,
}: {
    status?: string
}) {
    const { data, setData, post, processing, errors } =
        useForm<ForgotPasswordForm>({
            email: '',
        })

    const submit = (event: FormEvent<HTMLFormElement>) => {
        event.preventDefault()

        post(route('password.email'))
    }

    return (
        <>
            <Head title="Forgot password" />

            <main className="min-h-screen bg-neutral-950 px-4 py-8 text-white sm:px-6 lg:px-8">
                <div className="mx-auto grid min-h-[calc(100vh-4rem)] w-full max-w-5xl overflow-hidden rounded-3xl border border-neutral-800 bg-neutral-900 shadow-2xl lg:grid-cols-[0.9fr_1.1fr]">
                    <aside className="hidden border-r border-neutral-800 bg-gradient-to-br from-indigo-950 via-neutral-950 to-neutral-950 p-10 lg:flex lg:flex-col lg:justify-between">
                        <div>
                            <Link
                                href={route('home')}
                                className="inline-flex items-center gap-3 rounded-xl focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-indigo-400"
                            >
                                <span
                                    className="flex h-10 w-10 items-center justify-center rounded-xl bg-indigo-600"
                                    aria-hidden="true"
                                >
                                    <ShieldCheck className="h-5 w-5" />
                                </span>

                                <span className="text-sm font-bold tracking-wide text-white">
                                    MWT Ecommerce
                                </span>
                            </Link>

                            <h1 className="mt-12 max-w-sm text-4xl font-black tracking-tight text-white">
                                Get back into your account.
                            </h1>

                            <p className="mt-4 max-w-sm text-sm leading-7 text-neutral-400">
                                Enter the email attached to your customer
                                account and we&apos;ll send you a secure link
                                to choose a new password.
                            </p>
                        </div>

                        <div className="rounded-2xl border border-indigo-500/20 bg-indigo-500/10 p-5">
                            <p className="text-sm font-semibold text-indigo-100">
                                Secure password recovery
                            </p>

                            <p className="mt-2 text-xs leading-6 text-indigo-200/70">
                                For account privacy, password recovery does not
                                need to reveal whether a particular email is
                                registered.
                            </p>
                        </div>
                    </aside>

                    <section
                        aria-labelledby="forgot-password-heading"
                        className="flex min-w-0 items-center p-6 sm:p-8 lg:p-10"
                    >
                        <div className="mx-auto w-full max-w-xl">
                            <div className="lg:hidden">
                                <Link
                                    href={route('home')}
                                    className="inline-flex items-center gap-2 rounded-xl text-sm font-bold text-white focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-indigo-400"
                                >
                                    <span
                                        className="flex h-9 w-9 items-center justify-center rounded-xl bg-indigo-600"
                                        aria-hidden="true"
                                    >
                                        <ShieldCheck className="h-4 w-4" />
                                    </span>

                                    MWT Ecommerce
                                </Link>
                            </div>

                            <div className="mt-8 lg:mt-0">
                                <p className="text-xs font-bold uppercase tracking-[0.14em] text-indigo-400">
                                    Password recovery
                                </p>

                                <h2
                                    id="forgot-password-heading"
                                    className="mt-2 text-3xl font-black tracking-tight text-white"
                                >
                                    Forgot your password?
                                </h2>

                                <p className="mt-3 max-w-lg text-sm leading-6 text-neutral-400">
                                    No problem. Enter your email address and
                                    we&apos;ll send password-reset instructions
                                    if the account can receive them.
                                </p>
                            </div>

                            {status && (
                                <div
                                    role="status"
                                    className="mt-6 rounded-2xl border border-emerald-500/20 bg-emerald-500/10 px-4 py-3 text-sm font-medium leading-6 text-emerald-300"
                                >
                                    {status}
                                </div>
                            )}

                            <form
                                onSubmit={submit}
                                className="mt-8 space-y-5"
                            >
                                <div className="min-w-0">
                                    <label
                                        htmlFor="email"
                                        className="mb-2 block text-sm font-semibold text-neutral-200"
                                    >
                                        Email address
                                    </label>

                                    <div className="relative">
                                        <Mail
                                            className="pointer-events-none absolute left-3.5 top-1/2 h-4 w-4 -translate-y-1/2 text-neutral-500"
                                            aria-hidden="true"
                                        />

                                        <input
                                            id="email"
                                            name="email"
                                            type="email"
                                            value={data.email}
                                            onChange={(event) =>
                                                setData(
                                                    'email',
                                                    event.target.value,
                                                )
                                            }
                                            autoComplete="email"
                                            inputMode="email"
                                            autoFocus
                                            required
                                            maxLength={254}
                                            autoCapitalize="none"
                                            spellCheck={false}
                                            aria-invalid={
                                                errors.email
                                                    ? true
                                                    : undefined
                                            }
                                            aria-describedby={
                                                errors.email
                                                    ? 'email-error'
                                                    : undefined
                                            }
                                            className={inputClass(
                                                Boolean(errors.email),
                                                true,
                                            )}
                                            placeholder="you@example.com"
                                        />
                                    </div>

                                    {errors.email && (
                                        <p
                                            id="email-error"
                                            role="alert"
                                            className="mt-2 text-xs font-medium leading-5 text-red-300"
                                        >
                                            {errors.email}
                                        </p>
                                    )}
                                </div>

                                <button
                                    type="submit"
                                    disabled={processing}
                                    className="inline-flex min-h-12 w-full items-center justify-center gap-2 rounded-xl bg-indigo-600 px-4 text-sm font-bold text-white transition hover:bg-indigo-500 disabled:cursor-not-allowed disabled:opacity-60 focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-indigo-400"
                                >
                                    <Send
                                        className="h-4 w-4"
                                        aria-hidden="true"
                                    />

                                    {processing
                                        ? 'Sending reset link...'
                                        : 'Email password reset link'}
                                </button>

                                <div className="border-t border-neutral-800 pt-5">
                                    <Link
                                        href={route('login')}
                                        className="inline-flex min-h-11 w-full items-center justify-center gap-2 rounded-xl border border-neutral-700 bg-neutral-950 px-4 text-sm font-bold text-neutral-200 transition hover:border-neutral-600 hover:bg-neutral-800 hover:text-white focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-indigo-500"
                                    >
                                        <ArrowLeft
                                            className="h-4 w-4"
                                            aria-hidden="true"
                                        />

                                        Back to sign in
                                    </Link>

                                    <p className="mt-4 text-center text-sm text-neutral-500">
                                        Don&apos;t have an account?{' '}
                                        <Link
                                            href={route('register')}
                                            className="font-semibold text-indigo-400 transition hover:text-indigo-300 focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-indigo-500"
                                        >
                                            Create account
                                        </Link>
                                    </p>
                                </div>
                            </form>
                        </div>
                    </section>
                </div>
            </main>
        </>
    )
}

function inputClass(
    invalid: boolean,
    withLeadingIcon = false,
): string {
    return [
        'min-h-12 w-full rounded-xl border bg-neutral-950 px-3 text-sm text-white outline-none transition placeholder:text-neutral-600 focus:ring-2 focus:ring-indigo-500',
        withLeadingIcon ? 'pl-10' : '',
        invalid
            ? 'border-red-500/60 focus:border-red-400'
            : 'border-neutral-700 hover:border-neutral-600 focus:border-indigo-500',
    ].join(' ')
}