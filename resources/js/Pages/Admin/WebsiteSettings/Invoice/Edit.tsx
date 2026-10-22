import { Head, useForm } from '@inertiajs/react'
import { CheckCircle2, ImageIcon, Palette, ReceiptText, Save, Trash2, Upload } from 'lucide-react'
import type { FormEvent } from 'react'

import AdminLayout from '@/Layouts/Admin/AdminLayout'

interface InvoiceSettingData {
    company_name: string
    company_tagline: string
    logo_url: string | null
    header_start_color: string
    header_end_color: string
    footer_color: string
    support_email: string | null
    footer_credit: string
}

interface InvoiceSettingForm {
    company_name: string
    company_tagline: string
    logo: File | null
    remove_logo: boolean
    header_start_color: string
    header_end_color: string
    footer_color: string
    support_email: string
    footer_credit: string
}

interface Props {
    setting: InvoiceSettingData
    update_url: string
}

export default function Edit({ setting, update_url }: Props) {
    const form = useForm<InvoiceSettingForm>({
        company_name: setting.company_name,

        company_tagline: setting.company_tagline,

        logo: null,

        remove_logo: false,

        header_start_color: setting.header_start_color,

        header_end_color: setting.header_end_color,

        footer_color: setting.footer_color,

        support_email: setting.support_email ?? '',

        footer_credit: setting.footer_credit,
    })

    const submit = (event: FormEvent<HTMLFormElement>) => {
        event.preventDefault()

        form.post(update_url, {
            forceFormData: true,
            preserveScroll: true,

            onSuccess: () => {
                form.setData('logo', null)

                form.setData('remove_logo', false)
            },
        })
    }

    const removeExistingLogo = () => {
        form.setData('logo', null)

        form.setData('remove_logo', true)
    }

    const restoreExistingLogo = () => {
        form.setData('remove_logo', false)
    }

    const previewLogoUrl =
        form.data.logo !== null
            ? URL.createObjectURL(form.data.logo)
            : !form.data.remove_logo
              ? setting.logo_url
              : null

    return (
        <>
            <Head title="Invoice Settings" />

            <AdminLayout
                title="Invoice Settings"
                description="Configure the company identity, logo, colors, support contact, and footer credit used by customer and admin invoices."
            >
                <form onSubmit={submit} className="space-y-6" encType="multipart/form-data">
                    {form.recentlySuccessful && (
                        <div
                            role="status"
                            aria-live="polite"
                            className="flex items-start gap-3 rounded-xl border border-emerald-200 bg-emerald-50 p-4 text-sm text-emerald-800"
                        >
                            <CheckCircle2 className="mt-0.5 h-5 w-5 shrink-0" aria-hidden="true" />

                            <div>
                                <p className="font-semibold">Invoice settings saved</p>

                                <p className="mt-1 text-emerald-700">
                                    Customer and admin invoice views will use the updated branding.
                                </p>
                            </div>
                        </div>
                    )}

                    <section className="overflow-hidden rounded-2xl border border-neutral-200 bg-white shadow-sm">
                        <SectionHeader
                            icon={ReceiptText}
                            title="Company identity"
                            description="These details appear in the branded invoice header."
                        />

                        <div className="grid gap-6 p-5 sm:p-6 lg:grid-cols-2">
                            <Field>
                                <Label htmlFor="company_name" required>
                                    Company name
                                </Label>

                                <input
                                    id="company_name"
                                    type="text"
                                    maxLength={255}
                                    value={form.data.company_name}
                                    onChange={(event) =>
                                        form.setData('company_name', event.target.value)
                                    }
                                    aria-invalid={form.errors.company_name ? true : undefined}
                                    className={inputClass(Boolean(form.errors.company_name))}
                                />

                                <ErrorText message={form.errors.company_name} />
                            </Field>

                            <Field>
                                <Label htmlFor="company_tagline" required>
                                    Company tagline
                                </Label>

                                <input
                                    id="company_tagline"
                                    type="text"
                                    maxLength={255}
                                    value={form.data.company_tagline}
                                    onChange={(event) =>
                                        form.setData('company_tagline', event.target.value)
                                    }
                                    aria-invalid={form.errors.company_tagline ? true : undefined}
                                    className={inputClass(Boolean(form.errors.company_tagline))}
                                />

                                <ErrorText message={form.errors.company_tagline} />
                            </Field>

                            <Field>
                                <Label htmlFor="support_email">Support email</Label>

                                <input
                                    id="support_email"
                                    type="email"
                                    inputMode="email"
                                    autoComplete="email"
                                    maxLength={254}
                                    value={form.data.support_email}
                                    onChange={(event) =>
                                        form.setData('support_email', event.target.value)
                                    }
                                    placeholder="support@example.com"
                                    aria-invalid={form.errors.support_email ? true : undefined}
                                    className={inputClass(Boolean(form.errors.support_email))}
                                />

                                <p className="text-xs leading-5 text-neutral-500">
                                    If supplied, this address appears in the invoice footer.
                                </p>

                                <ErrorText message={form.errors.support_email} />
                            </Field>

                            <Field>
                                <Label htmlFor="footer_credit" required>
                                    Footer credit line
                                </Label>

                                <input
                                    id="footer_credit"
                                    type="text"
                                    maxLength={500}
                                    value={form.data.footer_credit}
                                    onChange={(event) =>
                                        form.setData('footer_credit', event.target.value)
                                    }
                                    placeholder="Thank you for your business!"
                                    aria-invalid={form.errors.footer_credit ? true : undefined}
                                    className={inputClass(Boolean(form.errors.footer_credit))}
                                />

                                <div className="flex justify-between gap-4 text-xs text-neutral-400">
                                    <span>Displayed in the invoice footer.</span>

                                    <span className="shrink-0">
                                        {form.data.footer_credit.length}
                                        /500
                                    </span>
                                </div>

                                <ErrorText message={form.errors.footer_credit} />
                            </Field>
                        </div>
                    </section>

                    <section className="overflow-hidden rounded-2xl border border-neutral-200 bg-white shadow-sm">
                        <SectionHeader
                            icon={ImageIcon}
                            title="Company logo"
                            description="Upload the company logo displayed in the invoice header."
                        />

                        <div className="grid gap-6 p-5 sm:p-6 lg:grid-cols-[220px_1fr]">
                            <div>
                                <p className="mb-2 text-sm font-semibold text-neutral-900">
                                    Preview
                                </p>

                                <div
                                    className="flex aspect-[4/2] w-full items-center justify-center overflow-hidden rounded-2xl border border-dashed border-neutral-300 bg-neutral-50 p-5"
                                    style={{
                                        backgroundImage: `linear-gradient(135deg, ${form.data.header_start_color}, ${form.data.header_end_color})`,
                                    }}
                                >
                                    {previewLogoUrl ? (
                                        <div className="flex h-full w-full items-center justify-center rounded-xl bg-white/95 p-4 shadow-sm">
                                            <img
                                                src={previewLogoUrl}
                                                alt="Invoice company logo preview"
                                                className="max-h-full max-w-full object-contain"
                                            />
                                        </div>
                                    ) : (
                                        <div className="flex h-20 w-20 items-center justify-center rounded-2xl bg-white text-xl font-black text-neutral-900 shadow-md">
                                            {companyInitials(form.data.company_name)}
                                        </div>
                                    )}
                                </div>
                            </div>

                            <div className="space-y-4">
                                <Field>
                                    <Label htmlFor="logo">Upload logo</Label>

                                    <label
                                        htmlFor="logo"
                                        className="flex min-h-28 cursor-pointer flex-col items-center justify-center rounded-xl border border-dashed border-neutral-300 bg-neutral-50 px-5 py-6 text-center transition hover:border-neutral-400 hover:bg-neutral-100"
                                    >
                                        <Upload
                                            className="h-6 w-6 text-neutral-500"
                                            aria-hidden="true"
                                        />

                                        <span className="mt-2 text-sm font-semibold text-neutral-800">
                                            Choose an invoice logo
                                        </span>

                                        <span className="mt-1 text-xs text-neutral-500">
                                            PNG, JPG, JPEG or WEBP. Maximum 2 MB.
                                        </span>
                                    </label>

                                    <input
                                        id="logo"
                                        type="file"
                                        accept="image/png,image/jpeg,image/webp"
                                        className="sr-only"
                                        onChange={(event) => {
                                            const file = event.target.files?.[0] ?? null

                                            form.setData('logo', file)

                                            if (file !== null) {
                                                form.setData('remove_logo', false)
                                            }
                                        }}
                                    />

                                    {form.data.logo && (
                                        <div className="rounded-lg border border-neutral-200 bg-white p-3 text-sm">
                                            <p className="font-medium text-neutral-900">
                                                {form.data.logo.name}
                                            </p>

                                            <p className="mt-1 text-xs text-neutral-500">
                                                {formatFileSize(form.data.logo.size)}
                                            </p>
                                        </div>
                                    )}

                                    <ErrorText message={form.errors.logo} />
                                </Field>

                                {setting.logo_url && form.data.logo === null && (
                                    <div className="flex flex-wrap gap-3">
                                        {!form.data.remove_logo ? (
                                            <button
                                                type="button"
                                                onClick={removeExistingLogo}
                                                className="inline-flex min-h-10 items-center gap-2 rounded-lg border border-red-200 bg-white px-3 text-sm font-semibold text-red-700 transition hover:bg-red-50"
                                            >
                                                <Trash2 className="h-4 w-4" aria-hidden="true" />
                                                Remove current logo
                                            </button>
                                        ) : (
                                            <button
                                                type="button"
                                                onClick={restoreExistingLogo}
                                                className="inline-flex min-h-10 items-center rounded-lg border border-neutral-300 bg-white px-3 text-sm font-semibold text-neutral-700 transition hover:bg-neutral-50"
                                            >
                                                Keep existing logo
                                            </button>
                                        )}
                                    </div>
                                )}

                                {form.data.remove_logo && (
                                    <p className="text-sm font-medium text-amber-700">
                                        The current logo will be removed when you save.
                                    </p>
                                )}
                            </div>
                        </div>
                    </section>

                    <section className="overflow-hidden rounded-2xl border border-neutral-200 bg-white shadow-sm">
                        <SectionHeader
                            icon={Palette}
                            title="Invoice colors"
                            description="Choose the colors used by the invoice header, total row, accent, and footer."
                        />

                        <div className="grid gap-6 p-5 sm:grid-cols-2 sm:p-6 xl:grid-cols-3">
                            <ColorField
                                id="header_start_color"
                                label="Header start"
                                value={form.data.header_start_color}
                                error={form.errors.header_start_color}
                                onChange={(value) => form.setData('header_start_color', value)}
                            />

                            <ColorField
                                id="header_end_color"
                                label="Header end / accent"
                                value={form.data.header_end_color}
                                error={form.errors.header_end_color}
                                onChange={(value) => form.setData('header_end_color', value)}
                            />

                            <ColorField
                                id="footer_color"
                                label="Footer"
                                value={form.data.footer_color}
                                error={form.errors.footer_color}
                                onChange={(value) => form.setData('footer_color', value)}
                            />
                        </div>

                        <div className="border-t border-neutral-200 bg-neutral-50 p-5 sm:p-6">
                            <p className="mb-3 text-xs font-bold uppercase tracking-[0.16em] text-neutral-500">
                                Live branding preview
                            </p>

                            <div className="overflow-hidden rounded-2xl border border-neutral-200 bg-white shadow-sm">
                                <div
                                    className="p-6 text-white"
                                    style={{
                                        backgroundImage: `linear-gradient(to right, ${form.data.header_start_color}, ${form.data.header_end_color})`,
                                    }}
                                >
                                    <div className="flex flex-wrap items-center justify-between gap-5">
                                        <div className="flex min-w-0 items-center gap-3">
                                            <div className="flex h-12 w-12 shrink-0 items-center justify-center overflow-hidden rounded-xl bg-white p-2 font-black text-neutral-950 shadow-sm">
                                                {previewLogoUrl ? (
                                                    <img
                                                        src={previewLogoUrl}
                                                        alt=""
                                                        className="max-h-full max-w-full object-contain"
                                                    />
                                                ) : (
                                                    companyInitials(form.data.company_name)
                                                )}
                                            </div>

                                            <div className="min-w-0">
                                                <p className="truncate text-lg font-bold">
                                                    {form.data.company_name || 'Company Name'}
                                                </p>

                                                <p className="mt-0.5 truncate text-[11px] font-semibold uppercase tracking-[0.16em] text-white/75">
                                                    {form.data.company_tagline || 'Company Tagline'}
                                                </p>
                                            </div>
                                        </div>

                                        <div className="text-right">
                                            <p className="text-xs font-semibold uppercase tracking-wider text-white/75">
                                                Official Invoice
                                            </p>

                                            <p className="mt-1 text-xl font-black">#INV-PREVIEW</p>
                                        </div>
                                    </div>
                                </div>

                                <div className="p-6">
                                    <div className="ml-auto max-w-xs overflow-hidden rounded-xl border border-neutral-200">
                                        <div className="flex items-center justify-between bg-neutral-50 px-4 py-3 text-sm">
                                            <span className="text-neutral-500">Subtotal</span>

                                            <span className="font-semibold text-neutral-900">
                                                BDT 1,000.00
                                            </span>
                                        </div>

                                        <div
                                            className="flex items-center justify-between px-4 py-3 font-bold text-white"
                                            style={{
                                                backgroundColor: form.data.header_end_color,
                                            }}
                                        >
                                            <span>Total</span>

                                            <span>BDT 1,000.00</span>
                                        </div>
                                    </div>
                                </div>

                                <div
                                    className="border-t-4 p-5 text-white"
                                    style={{
                                        backgroundColor: form.data.footer_color,

                                        borderTopColor: form.data.header_end_color,
                                    }}
                                >
                                    <p className="text-sm font-semibold">
                                        {form.data.footer_credit || 'Thank you for your business!'}
                                    </p>

                                    {form.data.support_email && (
                                        <p className="mt-1 text-xs text-white/70">
                                            Questions? Contact {form.data.support_email}
                                        </p>
                                    )}
                                </div>
                            </div>
                        </div>
                    </section>

                    <div className="sticky bottom-4 z-10 flex flex-col gap-3 rounded-2xl border border-neutral-200 bg-white/95 p-4 shadow-xl backdrop-blur sm:flex-row sm:items-center sm:justify-between">
                        <div>
                            <p className="text-sm font-semibold text-neutral-900">
                                Invoice branding
                            </p>

                            <p className="mt-0.5 text-xs text-neutral-500">
                                Changes apply to customer, admin, and printable invoice
                                presentation.
                            </p>
                        </div>

                        <button
                            type="submit"
                            disabled={form.processing}
                            className="inline-flex min-h-11 items-center justify-center gap-2 rounded-xl bg-neutral-950 px-5 py-2.5 text-sm font-semibold text-white transition hover:bg-neutral-800 focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-neutral-950 focus-visible:ring-offset-2 disabled:cursor-not-allowed disabled:opacity-50"
                        >
                            <Save className="h-4 w-4" aria-hidden="true" />

                            {form.processing ? 'Saving…' : 'Save Invoice Settings'}
                        </button>
                    </div>
                </form>
            </AdminLayout>
        </>
    )
}

function SectionHeader({
    icon: Icon,
    title,
    description,
}: {
    icon: typeof ReceiptText
    title: string
    description: string
}) {
    return (
        <div className="flex items-start gap-3 border-b border-neutral-200 bg-neutral-50/70 px-5 py-4 sm:px-6">
            <div className="flex h-10 w-10 shrink-0 items-center justify-center rounded-xl border border-neutral-200 bg-white text-neutral-700 shadow-sm">
                <Icon className="h-5 w-5" aria-hidden="true" />
            </div>

            <div>
                <h2 className="font-bold text-neutral-950">{title}</h2>

                <p className="mt-1 text-sm leading-6 text-neutral-500">{description}</p>
            </div>
        </div>
    )
}

function Field({ children }: { children: React.ReactNode }) {
    return <div className="space-y-2">{children}</div>
}

function Label({
    htmlFor,
    required = false,
    children,
}: {
    htmlFor: string
    required?: boolean
    children: React.ReactNode
}) {
    return (
        <label htmlFor={htmlFor} className="block text-sm font-semibold text-neutral-900">
            {children}

            {required && (
                <span className="ml-1 text-red-600" aria-hidden="true">
                    *
                </span>
            )}
        </label>
    )
}

function ErrorText({ message }: { message?: string }) {
    if (!message) {
        return null
    }

    return (
        <p role="alert" className="text-sm font-medium text-red-600">
            {message}
        </p>
    )
}

function ColorField({
    id,
    label,
    value,
    error,
    onChange,
}: {
    id: string
    label: string
    value: string
    error?: string
    onChange: (value: string) => void
}) {
    return (
        <Field>
            <Label htmlFor={id} required>
                {label}
            </Label>

            <div className="flex items-center gap-3">
                <input
                    id={id}
                    type="color"
                    value={value}
                    onChange={(event) => onChange(event.target.value)}
                    className="h-11 w-14 cursor-pointer rounded-lg border border-neutral-300 bg-white p-1"
                    aria-label={`${label} color picker`}
                />

                <input
                    type="text"
                    value={value}
                    maxLength={7}
                    onChange={(event) => onChange(event.target.value)}
                    aria-label={`${label} hexadecimal color`}
                    aria-invalid={error ? true : undefined}
                    className={inputClass(Boolean(error))}
                />
            </div>

            <ErrorText message={error} />
        </Field>
    )
}

function inputClass(invalid: boolean): string {
    return [
        'block min-h-11 w-full rounded-xl border bg-white px-3.5 py-2.5 text-sm text-neutral-950 outline-none transition',
        'placeholder:text-neutral-400',
        'focus:ring-4',
        invalid
            ? 'border-red-300 focus:border-red-400 focus:ring-red-100'
            : 'border-neutral-300 focus:border-neutral-500 focus:ring-neutral-100',
    ].join(' ')
}

function companyInitials(value: string): string {
    const initials = value
        .trim()
        .split(/\s+/)
        .filter(Boolean)
        .slice(0, 2)
        .map((part) => part[0]?.toUpperCase() ?? '')
        .join('')

    return initials || 'IN'
}

function formatFileSize(bytes: number): string {
    if (bytes < 1024) {
        return `${bytes} B`
    }

    if (bytes < 1024 * 1024) {
        return `${(bytes / 1024).toFixed(1)} KB`
    }

    return `${(bytes / (1024 * 1024)).toFixed(1)} MB`
}
