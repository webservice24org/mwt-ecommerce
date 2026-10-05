import { useForm } from '@inertiajs/react'
import { CheckCircle2, Mail, MessageCircle, Phone } from 'lucide-react'
import { type FormEvent, useId, useRef, useState } from 'react'

import CallToActionBackground from './CallToActionBackground'
import {
    buildEmailHref,
    buildPhoneHref,
    buildWhatsAppHref,
    type CallToActionConfig,
} from './call-to-action-config'

interface Props {
    config: CallToActionConfig
}

export default function SplitLeadCapture({ config }: Props) {
    const headingId = useId()
    const emailInputId = useId()

    const emailInputRef = useRef<HTMLInputElement>(null)

    const [subscribed, setSubscribed] = useState(false)

    const { data, setData, post, processing, errors, reset, clearErrors } = useForm({
        email: '',
    })

    const emailErrorId = `${emailInputId}-error`

    const emailNoteId = `${emailInputId}-note`

    const emailDescribedBy =
        [errors.email ? emailErrorId : '', config.newsletter_note ? emailNoteId : '']
            .filter(Boolean)
            .join(' ') || undefined

    const phoneHref = buildPhoneHref(config.phone_number)

    const emailHref = buildEmailHref(config.email)

    const whatsappHref = buildWhatsAppHref(config.whatsapp_number, config.whatsapp_message)

    const isLight = config.text_theme === 'light'

    const headingClass = isLight ? 'text-white' : 'text-neutral-950'

    const descriptionClass = isLight ? 'text-neutral-200' : 'text-neutral-600'

    const eyebrowClass = isLight ? 'text-indigo-200' : 'text-indigo-700'

    const contactCardClass = isLight
        ? 'border-white/15 bg-white/10 text-white hover:bg-white/15'
        : 'border-neutral-200 bg-white/80 text-neutral-800 hover:border-indigo-200 hover:bg-indigo-50'

    const contactLabelClass = isLight ? 'text-neutral-300' : 'text-neutral-500'

    const submit = (event: FormEvent<HTMLFormElement>) => {
        event.preventDefault()

        setSubscribed(false)
        clearErrors()

        post(route('newsletter.subscribe'), {
            preserveScroll: true,

            onSuccess: () => {
                reset('email')
                clearErrors()
                setSubscribed(true)
            },

            onError: () => {
                setSubscribed(false)

                emailInputRef.current?.focus()
            },
        })
    }

    return (
        <section aria-labelledby={headingId} className="min-w-0 max-w-full py-5 sm:py-7 lg:py-8">
            <div
                className="relative isolate max-w-full overflow-hidden rounded-xl border border-neutral-200 shadow-sm sm:rounded-2xl"
                style={{
                    backgroundColor: config.background_color,
                }}
            >
                <CallToActionBackground config={config} />

                <div className="grid min-w-0 max-w-full gap-8 px-5 py-8 sm:px-8 sm:py-10 lg:grid-cols-12 lg:items-center lg:gap-10 lg:px-10 lg:py-12">
                    {/* Left content */}
                    <div className="min-w-0 max-w-full lg:col-span-7">
                        {config.eyebrow && (
                            <p
                                className={[
                                    'mb-3 break-words text-xs font-semibold tracking-wider uppercase',
                                    '[overflow-wrap:anywhere]',
                                    eyebrowClass,
                                ].join(' ')}
                            >
                                {config.eyebrow}
                            </p>
                        )}

                        <h2
                            id={headingId}
                            className={[
                                'max-w-3xl break-words text-2xl font-bold tracking-tight',
                                '[overflow-wrap:anywhere]',
                                'sm:text-3xl lg:text-4xl',
                                headingClass,
                            ].join(' ')}
                        >
                            {config.heading}
                        </h2>

                        {config.description && (
                            <p
                                className={[
                                    'mt-3 max-w-2xl whitespace-pre-line break-words',
                                    'text-sm leading-relaxed [overflow-wrap:anywhere]',
                                    'sm:mt-4 sm:text-base',
                                    descriptionClass,
                                ].join(' ')}
                            >
                                {config.description}
                            </p>
                        )}

                        {(phoneHref || emailHref || whatsappHref) && (
                            <div className="mt-6 grid min-w-0 max-w-full gap-3 sm:grid-cols-2 xl:grid-cols-3">
                                {phoneHref && config.phone_number && (
                                    <a
                                        href={phoneHref}
                                        aria-label={`${config.phone_label ?? 'Call Us'}: ${config.phone_number}`}
                                        className={[
                                            'group min-h-12 min-w-0 max-w-full rounded-xl border p-4',
                                            'transition focus-visible:outline-none focus-visible:ring-2',
                                            'focus-visible:ring-indigo-500 focus-visible:ring-offset-2',
                                            'motion-reduce:transition-none',
                                            contactCardClass,
                                        ].join(' ')}
                                    >
                                        <div className="flex min-w-0 items-start gap-3">
                                            <Phone
                                                aria-hidden="true"
                                                className="mt-0.5 h-5 w-5 shrink-0"
                                            />

                                            <div className="min-w-0">
                                                <span
                                                    className={[
                                                        'block text-xs font-semibold tracking-wider uppercase',
                                                        contactLabelClass,
                                                    ].join(' ')}
                                                >
                                                    {config.phone_label ?? 'Call Us'}
                                                </span>

                                                <span className="mt-1 block break-words text-sm font-semibold [overflow-wrap:anywhere]">
                                                    {config.phone_number}
                                                </span>
                                            </div>
                                        </div>
                                    </a>
                                )}

                                {emailHref && config.email && (
                                    <a
                                        href={emailHref}
                                        aria-label={`${config.email_label ?? 'Email Us'}: ${config.email}`}
                                        className={[
                                            'group min-h-12 min-w-0 max-w-full rounded-xl border p-4',
                                            'transition focus-visible:outline-none focus-visible:ring-2',
                                            'focus-visible:ring-indigo-500 focus-visible:ring-offset-2',
                                            'motion-reduce:transition-none',
                                            contactCardClass,
                                        ].join(' ')}
                                    >
                                        <div className="flex min-w-0 items-start gap-3">
                                            <Mail
                                                aria-hidden="true"
                                                className="mt-0.5 h-5 w-5 shrink-0"
                                            />

                                            <div className="min-w-0">
                                                <span
                                                    className={[
                                                        'block text-xs font-semibold tracking-wider uppercase',
                                                        contactLabelClass,
                                                    ].join(' ')}
                                                >
                                                    {config.email_label ?? 'Email Us'}
                                                </span>

                                                <span className="mt-1 block break-all text-sm font-semibold">
                                                    {config.email}
                                                </span>
                                            </div>
                                        </div>
                                    </a>
                                )}

                                {whatsappHref && config.whatsapp_number && (
                                    <a
                                        href={whatsappHref}
                                        target="_blank"
                                        rel="noopener noreferrer"
                                        aria-label={`${config.whatsapp_label ?? 'WhatsApp'}: ${config.whatsapp_number}. Opens in a new tab.`}
                                        className={[
                                            'group min-h-12 min-w-0 max-w-full rounded-xl border p-4',
                                            'transition focus-visible:outline-none focus-visible:ring-2',
                                            'focus-visible:ring-emerald-500 focus-visible:ring-offset-2',
                                            'motion-reduce:transition-none',
                                            contactCardClass,
                                        ].join(' ')}
                                    >
                                        <div className="flex min-w-0 items-start gap-3">
                                            <MessageCircle
                                                aria-hidden="true"
                                                className="mt-0.5 h-5 w-5 shrink-0"
                                            />

                                            <div className="min-w-0">
                                                <span
                                                    className={[
                                                        'block text-xs font-semibold tracking-wider uppercase',
                                                        contactLabelClass,
                                                    ].join(' ')}
                                                >
                                                    {config.whatsapp_label ?? 'WhatsApp'}
                                                </span>

                                                <span className="mt-1 block break-words text-sm font-semibold [overflow-wrap:anywhere]">
                                                    {config.whatsapp_number}
                                                </span>
                                            </div>
                                        </div>
                                    </a>
                                )}
                            </div>
                        )}
                    </div>

                    {/* Right newsletter form */}
                    <div className="min-w-0 max-w-full lg:col-span-5">
                        <div className="max-w-full rounded-2xl border border-neutral-200 bg-white/95 p-5 shadow-lg backdrop-blur-sm sm:p-6">
                            <form onSubmit={submit} noValidate aria-busy={processing}>
                                <p
                                    role="status"
                                    aria-live="polite"
                                    aria-atomic="true"
                                    className="sr-only"
                                >
                                    {processing ? 'Submitting newsletter subscription.' : ''}
                                </p>

                                <label
                                    htmlFor={emailInputId}
                                    className="mb-2 block text-sm font-semibold text-neutral-800"
                                >
                                    Email address
                                </label>

                                <div className="flex min-w-0 max-w-full flex-col gap-3 sm:flex-row">
                                    <input
                                        ref={emailInputRef}
                                        id={emailInputId}
                                        type="email"
                                        name="email"
                                        inputMode="email"
                                        autoComplete="email"
                                        autoCapitalize="none"
                                        spellCheck={false}
                                        required
                                        value={data.email}
                                        placeholder={
                                            config.newsletter_placeholder ??
                                            'Enter your email address'
                                        }
                                        aria-invalid={errors.email ? true : undefined}
                                        aria-describedby={emailDescribedBy}
                                        onChange={(event) => {
                                            setData('email', event.target.value)

                                            if (errors.email) {
                                                clearErrors('email')
                                            }

                                            if (subscribed) {
                                                setSubscribed(false)
                                            }
                                        }}
                                        className={[
                                            'min-h-12 w-full min-w-0 max-w-full rounded-xl border',
                                            'bg-white px-4 py-3 text-sm text-neutral-950 shadow-sm outline-none',
                                            'placeholder:text-neutral-400',
                                            'transition focus:ring-2 motion-reduce:transition-none',
                                            errors.email
                                                ? 'border-red-400 focus:border-red-500 focus:ring-red-100'
                                                : 'border-neutral-300 focus:border-indigo-500 focus:ring-indigo-100',
                                        ].join(' ')}
                                    />

                                    <button
                                        type="submit"
                                        disabled={processing}
                                        aria-disabled={processing}
                                        className={[
                                            'inline-flex min-h-12 w-full shrink-0 items-center justify-center',
                                            'rounded-xl bg-indigo-600 px-6 py-3 text-sm font-semibold',
                                            'whitespace-nowrap text-white shadow-sm',
                                            'transition hover:bg-indigo-700',
                                            'focus-visible:outline-none focus-visible:ring-2',
                                            'focus-visible:ring-indigo-500 focus-visible:ring-offset-2',
                                            'disabled:cursor-not-allowed disabled:opacity-60',
                                            'motion-reduce:transition-none sm:w-auto',
                                        ].join(' ')}
                                    >
                                        {processing
                                            ? 'Subscribing...'
                                            : (config.newsletter_button_label ?? 'Subscribe')}
                                    </button>
                                </div>

                                {errors.email && (
                                    <p
                                        id={emailErrorId}
                                        role="alert"
                                        className="mt-2 text-sm text-red-600"
                                    >
                                        {errors.email}
                                    </p>
                                )}

                                {subscribed && (
                                    <div
                                        role="status"
                                        aria-live="polite"
                                        aria-atomic="true"
                                        className="mt-3 flex items-start gap-2 text-sm font-medium text-emerald-700"
                                    >
                                        <CheckCircle2
                                            aria-hidden="true"
                                            className="mt-0.5 h-4 w-4 shrink-0"
                                        />

                                        <span>Thanks! You&apos;re subscribed.</span>
                                    </div>
                                )}

                                {config.newsletter_note && (
                                    <p
                                        id={emailNoteId}
                                        className="mt-3 flex items-start gap-2 text-xs leading-5 text-neutral-500"
                                    >
                                        <CheckCircle2
                                            aria-hidden="true"
                                            className="mt-0.5 h-4 w-4 shrink-0 text-emerald-600"
                                        />

                                        <span className="min-w-0 break-words [overflow-wrap:anywhere]">
                                            {config.newsletter_note}
                                        </span>
                                    </p>
                                )}
                            </form>
                        </div>
                    </div>
                </div>
            </div>
        </section>
    )
}
