import { Head, Link, useForm } from '@inertiajs/react'
import {
    Check,
    Eye,
    EyeOff,
    LockKeyhole,
    Mail,
    ShieldCheck,
    X,
} from 'lucide-react'
import {
    type FormEvent,
    type ReactNode,
    useMemo,
    useState,
} from 'react'

interface ResetPasswordForm {
    token: string
    email: string
    password: string
    password_confirmation: string
}

interface PasswordRequirement {
    label: string
    met: boolean
}

export default function ResetPassword({
    token,
    email,
}: {
    token: string
    email: string
}) {
    const [showPassword, setShowPassword] = useState(false)

    const [
        showPasswordConfirmation,
        setShowPasswordConfirmation,
    ] = useState(false)

    const { data, setData, post, processing, errors, reset } =
        useForm<ResetPasswordForm>({
            token,
            email,
            password: '',
            password_confirmation: '',
        })

    const passwordRequirements =
        useMemo<PasswordRequirement[]>(
            () => [
                {
                    label: 'At least 10 characters',
                    met: data.password.length >= 10,
                },
                {
                    label: 'An uppercase letter',
                    met: /[A-Z]/.test(data.password),
                },
                {
                    label: 'A lowercase letter',
                    met: /[a-z]/.test(data.password),
                },
                {
                    label: 'A number',
                    met: /\d/.test(data.password),
                },
                {
                    label: 'A symbol',
                    met: /[^A-Za-z0-9]/.test(
                        data.password,
                    ),
                },
            ],
            [data.password],
        )

    const passwordConfirmationStarted =
        data.password_confirmation.length > 0

    const passwordsMatch =
        passwordConfirmationStarted &&
        data.password === data.password_confirmation

    const submit = (event: FormEvent<HTMLFormElement>) => {
        event.preventDefault()

        post(route('password.store'), {
            onFinish: () =>
                reset(
                    'password',
                    'password_confirmation',
                ),
        })
    }

    return (
        <>
            <Head title="Reset password" />

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
                                Choose a new secure password.
                            </h1>

                            <p className="mt-4 max-w-sm text-sm leading-7 text-neutral-400">
                                Create a strong new password for your customer
                                account and keep your orders, invoices, and
                                account information protected.
                            </p>
                        </div>

                        <div className="rounded-2xl border border-indigo-500/20 bg-indigo-500/10 p-5">
                            <p className="text-sm font-semibold text-indigo-100">
                                Strong password protection
                            </p>

                            <p className="mt-2 text-xs leading-6 text-indigo-200/70">
                                Use a password that you do not reuse on other
                                websites or services.
                            </p>
                        </div>
                    </aside>

                    <section
                        aria-labelledby="reset-password-heading"
                        className="min-w-0 p-6 sm:p-8 lg:p-10"
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
                                    id="reset-password-heading"
                                    className="mt-2 text-3xl font-black tracking-tight text-white"
                                >
                                    Reset password
                                </h2>

                                <p className="mt-3 text-sm leading-6 text-neutral-400">
                                    Enter a new password for your customer
                                    account.
                                </p>
                            </div>

                            <form
                                onSubmit={submit}
                                className="mt-8 space-y-5"
                            >
                                <ResetField
                                    id="email"
                                    label="Email address"
                                    error={errors.email}
                                >
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
                                </ResetField>

                                <ResetField
                                    id="password"
                                    label="New password"
                                    error={errors.password}
                                >
                                    <div className="relative">
                                        <LockKeyhole
                                            className="pointer-events-none absolute left-3.5 top-1/2 h-4 w-4 -translate-y-1/2 text-neutral-500"
                                            aria-hidden="true"
                                        />

                                        <input
                                            id="password"
                                            name="password"
                                            type={
                                                showPassword
                                                    ? 'text'
                                                    : 'password'
                                            }
                                            value={data.password}
                                            onChange={(event) =>
                                                setData(
                                                    'password',
                                                    event.target.value,
                                                )
                                            }
                                            autoComplete="new-password"
                                            autoFocus
                                            required
                                            minLength={10}
                                            maxLength={255}
                                            aria-invalid={
                                                errors.password
                                                    ? true
                                                    : undefined
                                            }
                                            aria-describedby={[
                                                'password-requirements',
                                                errors.password
                                                    ? 'password-error'
                                                    : '',
                                            ]
                                                .filter(Boolean)
                                                .join(' ')}
                                            className={`${inputClass(
                                                Boolean(
                                                    errors.password,
                                                ),
                                                true,
                                            )} pr-12`}
                                            placeholder="Create a strong new password"
                                        />

                                        <PasswordVisibilityButton
                                            visible={showPassword}
                                            onToggle={() =>
                                                setShowPassword(
                                                    (current) =>
                                                        !current,
                                                )
                                            }
                                            label="new password"
                                        />
                                    </div>

                                    <div
                                        id="password-requirements"
                                        className="mt-3 rounded-2xl border border-neutral-800 bg-neutral-950/70 p-4"
                                    >
                                        <p className="text-xs font-semibold text-neutral-300">
                                            Your new password must include:
                                        </p>

                                        <ul className="mt-3 grid gap-2 sm:grid-cols-2">
                                            {passwordRequirements.map(
                                                (requirement) => (
                                                    <li
                                                        key={
                                                            requirement.label
                                                        }
                                                        className={[
                                                            'flex min-w-0 items-center gap-2 text-xs',
                                                            requirement.met
                                                                ? 'text-emerald-300'
                                                                : 'text-neutral-500',
                                                        ].join(
                                                            ' ',
                                                        )}
                                                    >
                                                        <span
                                                            className={[
                                                                'flex h-4 w-4 shrink-0 items-center justify-center rounded-full border',
                                                                requirement.met
                                                                    ? 'border-emerald-500/40 bg-emerald-500/10'
                                                                    : 'border-neutral-700',
                                                            ].join(
                                                                ' ',
                                                            )}
                                                            aria-hidden="true"
                                                        >
                                                            {requirement.met ? (
                                                                <Check className="h-2.5 w-2.5" />
                                                            ) : (
                                                                <X className="h-2.5 w-2.5" />
                                                            )}
                                                        </span>

                                                        <span>
                                                            {
                                                                requirement.label
                                                            }
                                                        </span>

                                                        <span className="sr-only">
                                                            {requirement.met
                                                                ? 'Requirement met'
                                                                : 'Requirement not met'}
                                                        </span>
                                                    </li>
                                                ),
                                            )}
                                        </ul>
                                    </div>
                                </ResetField>

                                <ResetField
                                    id="password_confirmation"
                                    label="Confirm new password"
                                    error={
                                        errors.password_confirmation
                                    }
                                >
                                    <div className="relative">
                                        <LockKeyhole
                                            className="pointer-events-none absolute left-3.5 top-1/2 h-4 w-4 -translate-y-1/2 text-neutral-500"
                                            aria-hidden="true"
                                        />

                                        <input
                                            id="password_confirmation"
                                            name="password_confirmation"
                                            type={
                                                showPasswordConfirmation
                                                    ? 'text'
                                                    : 'password'
                                            }
                                            value={
                                                data.password_confirmation
                                            }
                                            onChange={(event) =>
                                                setData(
                                                    'password_confirmation',
                                                    event.target.value,
                                                )
                                            }
                                            autoComplete="new-password"
                                            required
                                            maxLength={255}
                                            aria-invalid={
                                                errors.password_confirmation
                                                    ? true
                                                    : undefined
                                            }
                                            aria-describedby={[
                                                passwordConfirmationStarted
                                                    ? 'password-confirmation-status'
                                                    : '',
                                                errors.password_confirmation
                                                    ? 'password_confirmation-error'
                                                    : '',
                                            ]
                                                .filter(Boolean)
                                                .join(' ') ||
                                                undefined}
                                            className={`${inputClass(
                                                Boolean(
                                                    errors.password_confirmation,
                                                ),
                                                true,
                                            )} pr-12`}
                                            placeholder="Repeat your new password"
                                        />

                                        <PasswordVisibilityButton
                                            visible={
                                                showPasswordConfirmation
                                            }
                                            onToggle={() =>
                                                setShowPasswordConfirmation(
                                                    (
                                                        current,
                                                    ) =>
                                                        !current,
                                                )
                                            }
                                            label="password confirmation"
                                        />
                                    </div>

                                    {passwordConfirmationStarted && (
                                        <p
                                            id="password-confirmation-status"
                                            role="status"
                                            aria-live="polite"
                                            className={[
                                                'mt-2 text-xs font-medium',
                                                passwordsMatch
                                                    ? 'text-emerald-300'
                                                    : 'text-amber-300',
                                            ].join(' ')}
                                        >
                                            {passwordsMatch
                                                ? 'Passwords match.'
                                                : 'Passwords do not match yet.'}
                                        </p>
                                    )}
                                </ResetField>

                                <button
                                    type="submit"
                                    disabled={processing}
                                    className="inline-flex min-h-12 w-full items-center justify-center rounded-xl bg-indigo-600 px-4 text-sm font-bold text-white transition hover:bg-indigo-500 disabled:cursor-not-allowed disabled:opacity-60 focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-indigo-400"
                                >
                                    {processing
                                        ? 'Resetting password...'
                                        : 'Reset password'}
                                </button>

                                <p className="text-center text-sm text-neutral-500">
                                    Remembered your password?{' '}
                                    <Link
                                        href={route('login')}
                                        className="font-semibold text-indigo-400 transition hover:text-indigo-300 focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-indigo-500"
                                    >
                                        Sign in
                                    </Link>
                                </p>
                            </form>
                        </div>
                    </section>
                </div>
            </main>
        </>
    )
}

function ResetField({
    id,
    label,
    error,
    children,
}: {
    id: string
    label: string
    error?: string
    children: ReactNode
}) {
    return (
        <div className="min-w-0">
            <label
                htmlFor={id}
                className="mb-2 block text-sm font-semibold text-neutral-200"
            >
                {label}
            </label>

            {children}

            {error && (
                <p
                    id={`${id}-error`}
                    role="alert"
                    className="mt-2 text-xs font-medium leading-5 text-red-300"
                >
                    {error}
                </p>
            )}
        </div>
    )
}

function PasswordVisibilityButton({
    visible,
    onToggle,
    label,
}: {
    visible: boolean
    onToggle: () => void
    label: string
}) {
    return (
        <button
            type="button"
            onClick={onToggle}
            aria-pressed={visible}
            aria-label={`${visible ? 'Hide' : 'Show'} ${label}`}
            className="absolute right-2 top-1/2 flex h-9 w-9 -translate-y-1/2 items-center justify-center rounded-lg text-neutral-400 transition hover:bg-neutral-800 hover:text-white focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-indigo-500"
        >
            {visible ? (
                <EyeOff
                    className="h-4 w-4"
                    aria-hidden="true"
                />
            ) : (
                <Eye
                    className="h-4 w-4"
                    aria-hidden="true"
                />
            )}
        </button>
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