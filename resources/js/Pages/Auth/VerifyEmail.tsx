import { Head, Link, useForm } from '@inertiajs/react'
import { CheckCircle2, MailCheck, RefreshCw } from 'lucide-react'

interface Props {
    email: string
    status?: string | null
}

export default function VerifyEmail({ email, status = null }: Props) {
    const resendForm = useForm({})

    const resend = () => {
        resendForm.post(route('verification.send'), {
            preserveScroll: true,
        })
    }

    const linkSent = status === 'verification-link-sent'

    return (
        <>
            <Head title="Verify email" />

            <main className="flex min-h-screen items-center justify-center bg-neutral-950 px-4 py-10 text-white sm:px-6">
                <section
                    aria-labelledby="verify-email-heading"
                    className="w-full max-w-lg overflow-hidden rounded-3xl border border-neutral-800 bg-neutral-900 shadow-2xl"
                >
                    <div className="border-b border-neutral-800 p-6 sm:p-8">
                        <div
                            className="flex h-12 w-12 items-center justify-center rounded-2xl bg-indigo-500/10 text-indigo-300"
                            aria-hidden="true"
                        >
                            <MailCheck className="h-6 w-6" />
                        </div>

                        <h1
                            id="verify-email-heading"
                            className="mt-5 text-2xl font-bold tracking-tight text-white"
                        >
                            Verify your email
                        </h1>

                        <p className="mt-2 text-sm leading-6 text-neutral-400">
                            We sent a verification link to:
                        </p>

                        <p className="mt-1 break-all text-sm font-semibold text-neutral-100">
                            {email}
                        </p>
                    </div>

                    <div className="space-y-5 p-6 sm:p-8">
                        <p className="text-sm leading-6 text-neutral-400">
                            Open the email and follow the verification link to unlock your customer
                            dashboard, orders, addresses, and other protected account features.
                        </p>

                        {linkSent && (
                            <div
                                role="status"
                                aria-live="polite"
                                aria-atomic="true"
                                className="flex items-start gap-3 rounded-2xl border border-emerald-500/20 bg-emerald-500/10 p-4"
                            >
                                <CheckCircle2
                                    className="mt-0.5 h-5 w-5 shrink-0 text-emerald-300"
                                    aria-hidden="true"
                                />

                                <p className="text-sm leading-6 text-emerald-200">
                                    A new verification link has been sent to your email address.
                                </p>
                            </div>
                        )}

                        <div className="rounded-2xl border border-neutral-800 bg-neutral-950/70 p-4">
                            <p className="text-xs font-semibold text-neutral-200">
                                Didn&apos;t receive the email?
                            </p>

                            <p className="mt-1 text-xs leading-5 text-neutral-500">
                                Check your spam folder first, then request another link. Resend
                                attempts are rate-limited for account security.
                            </p>

                            <button
                                type="button"
                                disabled={resendForm.processing}
                                onClick={resend}
                                className="mt-4 inline-flex min-h-11 w-full items-center justify-center gap-2 rounded-xl bg-indigo-600 px-4 text-sm font-semibold text-white transition hover:bg-indigo-500 disabled:cursor-not-allowed disabled:opacity-60 focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-indigo-400"
                            >
                                <RefreshCw
                                    className={[
                                        'h-4 w-4',
                                        resendForm.processing ? 'animate-spin' : '',
                                    ].join(' ')}
                                    aria-hidden="true"
                                />

                                {resendForm.processing
                                    ? 'Sending verification email...'
                                    : 'Resend verification email'}
                            </button>
                        </div>

                        <div className="flex flex-col gap-2 border-t border-neutral-800 pt-5 sm:flex-row sm:items-center sm:justify-between">
                            <Link
                                href={route('home')}
                                className="inline-flex min-h-11 items-center justify-center rounded-xl border border-neutral-700 px-4 text-sm font-semibold text-neutral-300 transition hover:bg-neutral-800 hover:text-white focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-indigo-500"
                            >
                                Continue shopping
                            </Link>

                            <Link
                                href={route('logout')}
                                method="post"
                                as="button"
                                className="inline-flex min-h-11 items-center justify-center rounded-xl px-4 text-sm font-semibold text-neutral-400 transition hover:bg-neutral-800 hover:text-white focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-indigo-500"
                            >
                                Sign out
                            </Link>
                        </div>
                    </div>
                </section>
            </main>
        </>
    )
}
