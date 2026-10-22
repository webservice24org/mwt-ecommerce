import AdminLayout from '@/Layouts/Admin/AdminLayout'
import type { MailSettingEditorData } from '@/types/mail-setting'
import { Head, useForm } from '@inertiajs/react'
import { CheckCircle2, KeyRound, Mail, Save, Server, ShieldCheck } from 'lucide-react'
import type { FormEvent, ReactNode } from 'react'

interface Props {
    setting: MailSettingEditorData
    update_url: string
    test_url: string
}

interface MailSettingForm {
    enabled: boolean
    mailer: string
    host: string
    port: string
    username: string
    password: string
    encryption: string
    from_address: string
    from_name: string
    admin_order_notification_email: string
}

interface TestMailForm {
    email: string
}

interface TestMailFormErrors {
    email?: string
    mail?: string
}

export default function Edit({ setting, update_url, test_url }: Props) {
    const form = useForm<MailSettingForm>({
        enabled: setting.enabled,

        mailer: setting.mailer,

        host: setting.host ?? '',

        port: setting.port?.toString() ?? '',

        username: setting.username ?? '',

        /*
         * Intentionally always blank.
         *
         * The backend never sends the
         * configured SMTP password back.
         */
        password: '',

        encryption: setting.encryption ?? '',

        from_address: setting.from_address ?? '',

        from_name: setting.from_name ?? '',

        admin_order_notification_email: setting.admin_order_notification_email ?? '',
    })

    const submit = (event: FormEvent<HTMLFormElement>) => {
        event.preventDefault()

        form.post(update_url, {
            preserveScroll: true,

            onSuccess: () => {
                /*
                 * Never leave the replacement
                 * credential in React memory
                 * after a successful save.
                 */
                form.setData('password', '')
            },
        })
    }

    return (
        <AdminLayout
            title="Mail Settings"
            description="Configure SMTP delivery and order-notification email settings."
        >
            <Head title="Mail Settings" />

            <form onSubmit={submit} className="mx-auto max-w-5xl space-y-6">
                {form.recentlySuccessful && (
                    <div
                        role="status"
                        className="flex items-start gap-3 rounded-xl border border-emerald-200 bg-emerald-50 px-4 py-3 text-sm text-emerald-800"
                    >
                        <CheckCircle2 className="mt-0.5 h-5 w-5 shrink-0" aria-hidden="true" />

                        <div>
                            <p className="font-semibold">Mail settings saved</p>

                            <p className="mt-0.5 text-emerald-700">
                                Your mail configuration has been updated securely.
                            </p>
                        </div>
                    </div>
                )}

                <section className="overflow-hidden rounded-2xl border border-neutral-200 bg-white shadow-sm">
                    <SectionHeader
                        icon={<Mail className="h-5 w-5" aria-hidden="true" />}
                        title="Mail delivery"
                        description="Enable or disable application mail delivery."
                    />

                    <div className="p-5 sm:p-6">
                        <label className="flex cursor-pointer items-start gap-3 rounded-xl border border-neutral-200 bg-neutral-50 p-4">
                            <input
                                type="checkbox"
                                checked={form.data.enabled}
                                onChange={(event) => form.setData('enabled', event.target.checked)}
                                className="mt-1 h-4 w-4 rounded border-neutral-300 text-neutral-950 focus:ring-neutral-950"
                            />

                            <span>
                                <span className="block font-semibold text-neutral-950">
                                    Enable application mail
                                </span>

                                <span className="mt-1 block text-sm leading-6 text-neutral-500">
                                    When disabled, database SMTP settings will not be used for order
                                    emails.
                                </span>
                            </span>
                        </label>

                        <ErrorText message={form.errors.enabled} />
                    </div>
                </section>

                <section className="overflow-hidden rounded-2xl border border-neutral-200 bg-white shadow-sm">
                    <SectionHeader
                        icon={<Server className="h-5 w-5" aria-hidden="true" />}
                        title="SMTP connection"
                        description="Configure the server used to deliver application email."
                    />

                    <div className="grid gap-5 p-5 sm:grid-cols-2 sm:p-6">
                        <Field label="Mailer" error={form.errors.mailer}>
                            <select
                                value={form.data.mailer}
                                onChange={(event) => form.setData('mailer', event.target.value)}
                                className={inputClass}
                            >
                                <option value="smtp">SMTP</option>
                            </select>
                        </Field>

                        <Field label="SMTP host" error={form.errors.host}>
                            <input
                                type="text"
                                value={form.data.host}
                                onChange={(event) => form.setData('host', event.target.value)}
                                placeholder="sandbox.smtp.mailtrap.io"
                                className={inputClass}
                            />
                        </Field>

                        <Field label="Port" error={form.errors.port}>
                            <input
                                type="number"
                                min={1}
                                max={65535}
                                value={form.data.port}
                                onChange={(event) => form.setData('port', event.target.value)}
                                placeholder="587"
                                className={inputClass}
                            />
                        </Field>

                        <Field label="Encryption" error={form.errors.encryption}>
                            <select
                                value={form.data.encryption}
                                onChange={(event) => form.setData('encryption', event.target.value)}
                                className={inputClass}
                            >
                                <option value="">None</option>

                                <option value="tls">TLS</option>

                                <option value="ssl">SSL</option>
                            </select>
                        </Field>

                        <Field label="Username" error={form.errors.username}>
                            <input
                                type="text"
                                autoComplete="username"
                                value={form.data.username}
                                onChange={(event) => form.setData('username', event.target.value)}
                                className={inputClass}
                            />
                        </Field>

                        <Field label="SMTP password" error={form.errors.password}>
                            <div>
                                <input
                                    type="password"
                                    autoComplete="new-password"
                                    value={form.data.password}
                                    onChange={(event) =>
                                        form.setData('password', event.target.value)
                                    }
                                    placeholder={
                                        setting.has_password
                                            ? 'Leave blank to keep current password'
                                            : 'Enter SMTP password'
                                    }
                                    className={inputClass}
                                />

                                <div className="mt-2 flex items-start gap-2 text-xs text-neutral-500">
                                    <KeyRound
                                        className="mt-0.5 h-3.5 w-3.5 shrink-0"
                                        aria-hidden="true"
                                    />

                                    <span>
                                        {setting.has_password
                                            ? 'A password is already configured. Enter a new one only to replace it.'
                                            : 'No SMTP password is currently configured.'}
                                    </span>
                                </div>
                            </div>
                        </Field>
                    </div>
                </section>

                <section className="overflow-hidden rounded-2xl border border-neutral-200 bg-white shadow-sm">
                    <SectionHeader
                        icon={<ShieldCheck className="h-5 w-5" aria-hidden="true" />}
                        title="Sender identity"
                        description="These details appear on outgoing application emails."
                    />

                    <div className="grid gap-5 p-5 sm:grid-cols-2 sm:p-6">
                        <Field label="From address" error={form.errors.from_address}>
                            <input
                                type="email"
                                value={form.data.from_address}
                                onChange={(event) =>
                                    form.setData('from_address', event.target.value)
                                }
                                placeholder="orders@example.com"
                                className={inputClass}
                            />
                        </Field>

                        <Field label="From name" error={form.errors.from_name}>
                            <input
                                type="text"
                                value={form.data.from_name}
                                onChange={(event) => form.setData('from_name', event.target.value)}
                                placeholder="MWT Ecommerce"
                                className={inputClass}
                            />
                        </Field>

                        <div className="sm:col-span-2">
                            <Field
                                label="Admin order-notification email"
                                error={form.errors.admin_order_notification_email}
                            >
                                <input
                                    type="email"
                                    value={form.data.admin_order_notification_email}
                                    onChange={(event) =>
                                        form.setData(
                                            'admin_order_notification_email',
                                            event.target.value,
                                        )
                                    }
                                    placeholder="orders@example.com"
                                    className={inputClass}
                                />
                            </Field>

                            <p className="mt-2 text-xs leading-5 text-neutral-500">
                                New-order notifications will be sent to this address once order
                                email notifications are enabled.
                            </p>
                        </div>
                    </div>
                </section>

                <div className="sticky bottom-4 z-10 rounded-2xl border border-neutral-200 bg-white/95 p-4 shadow-xl backdrop-blur">
                    <div className="flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">
                        <div>
                            <p className="text-sm font-semibold text-neutral-950">
                                Secure mail configuration
                            </p>

                            <p className="mt-0.5 text-xs text-neutral-500">
                                Stored SMTP passwords are encrypted and are never returned to this
                                page.
                            </p>
                        </div>

                        <button
                            type="submit"
                            disabled={form.processing}
                            className="inline-flex min-h-11 items-center justify-center gap-2 rounded-xl bg-neutral-950 px-5 py-2.5 text-sm font-semibold text-white transition hover:bg-neutral-800 focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-neutral-950 focus-visible:ring-offset-2 disabled:cursor-not-allowed disabled:opacity-50"
                        >
                            <Save className="h-4 w-4" aria-hidden="true" />

                            {form.processing ? 'Saving…' : 'Save mail settings'}
                        </button>
                    </div>
                </div>
            </form>

            <div className="mx-auto mt-6 max-w-5xl">
                <TestMailCard
                    testUrl={test_url}
                    defaultEmail={
                        setting.admin_order_notification_email ?? setting.from_address ?? ''
                    }
                />
            </div>
        </AdminLayout>
    )
}

function TestMailCard({ testUrl, defaultEmail }: { testUrl: string; defaultEmail: string }) {
    const form = useForm<TestMailForm>({
        email: defaultEmail,
    })

    const errors = form.errors as TestMailFormErrors

    const submit = (event: FormEvent<HTMLFormElement>) => {
        event.preventDefault()

        form.post(testUrl, {
            preserveScroll: true,
        })
    }

    return (
        <section className="overflow-hidden rounded-2xl border border-neutral-200 bg-white shadow-sm">
            <SectionHeader
                icon={<Mail className="h-5 w-5" aria-hidden="true" />}
                title="Send test email"
                description="Verify the currently saved SMTP configuration before enabling order notifications."
            />

            <form onSubmit={submit} className="p-5 sm:p-6">
                {errors.mail && (
                    <div
                        role="alert"
                        className="mb-4 rounded-xl border border-red-200 bg-red-50 px-4 py-3 text-sm font-medium text-red-700"
                    >
                        {errors.mail}
                    </div>
                )}

                <div className="flex flex-col gap-4 sm:flex-row sm:items-end">
                    <div className="min-w-0 flex-1">
                        <Field label="Test recipient" error={errors.email}>
                            <input
                                type="email"
                                value={form.data.email}
                                onChange={(event) => form.setData('email', event.target.value)}
                                placeholder="you@example.com"
                                className={inputClass}
                            />
                        </Field>
                    </div>

                    <button
                        type="submit"
                        disabled={form.processing || form.data.email.trim() === ''}
                        className="inline-flex min-h-11 shrink-0 items-center justify-center gap-2 rounded-xl border border-neutral-300 bg-white px-5 py-2.5 text-sm font-semibold text-neutral-800 shadow-sm transition hover:bg-neutral-50 focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-neutral-950 focus-visible:ring-offset-2 disabled:cursor-not-allowed disabled:opacity-50"
                    >
                        <Mail className="h-4 w-4" aria-hidden="true" />

                        {form.processing ? 'Sending…' : 'Send test email'}
                    </button>
                </div>

                <p className="mt-3 text-xs leading-5 text-neutral-500">
                    This uses the configuration already saved in the database. Unsaved changes above
                    are not used until you save them.
                </p>
            </form>
        </section>
    )
}

function SectionHeader({
    icon,
    title,
    description,
}: {
    icon: ReactNode
    title: string
    description: string
}) {
    return (
        <div className="border-b border-neutral-200 bg-neutral-50/70 px-5 py-4 sm:px-6">
            <div className="flex items-start gap-3">
                <div className="flex h-10 w-10 shrink-0 items-center justify-center rounded-xl bg-white text-neutral-700 shadow-sm ring-1 ring-neutral-200">
                    {icon}
                </div>

                <div>
                    <h2 className="font-bold text-neutral-950">{title}</h2>

                    <p className="mt-1 text-sm leading-6 text-neutral-500">{description}</p>
                </div>
            </div>
        </div>
    )
}

function Field({ label, error, children }: { label: string; error?: string; children: ReactNode }) {
    return (
        <div>
            <label className="block text-sm font-semibold text-neutral-800">{label}</label>

            <div className="mt-2">{children}</div>

            <ErrorText message={error} />
        </div>
    )
}

function ErrorText({ message }: { message?: string }) {
    if (!message) {
        return null
    }

    return <p className="mt-2 text-sm font-medium text-red-600">{message}</p>
}

const inputClass =
    'block min-h-11 w-full rounded-xl border border-neutral-300 bg-white px-3.5 py-2.5 text-sm text-neutral-950 shadow-sm outline-none transition placeholder:text-neutral-400 focus:border-neutral-500 focus:ring-4 focus:ring-neutral-100'
