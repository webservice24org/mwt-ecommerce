import { ArrowRight, Mail, MessageCircle, Phone } from 'lucide-react'
import { useId } from 'react'

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

export default function ContactGrid({ config }: Props) {
    const headingId = useId()

    const phoneHref = buildPhoneHref(config.phone_number)

    const emailHref = buildEmailHref(config.email)

    const whatsappHref = buildWhatsAppHref(config.whatsapp_number, config.whatsapp_message)

    const contactCount =
        Number(Boolean(phoneHref)) + Number(Boolean(whatsappHref)) + Number(Boolean(emailHref))

    const contactGridClass =
        contactCount <= 1
            ? 'sm:grid-cols-1'
            : contactCount === 2
              ? 'sm:grid-cols-2'
              : 'sm:grid-cols-3'

    const isLight = config.text_theme === 'light'

    const headingClass = isLight ? 'text-white' : 'text-neutral-950'

    const descriptionClass = isLight ? 'text-neutral-200' : 'text-neutral-600'

    const eyebrowClass = isLight ? 'text-indigo-200' : 'text-indigo-700'

    const cardClass = isLight
        ? 'border-white/15 bg-white/10 text-white hover:bg-white/15'
        : 'border-neutral-200 bg-neutral-50 text-neutral-800 hover:bg-white hover:shadow-sm'

    const labelClass = isLight ? 'text-neutral-300' : 'text-neutral-500'

    const phoneHoverClass = isLight ? 'group-hover:text-white' : 'group-hover:text-indigo-600'

    const emailHoverClass = isLight ? 'group-hover:text-white' : 'group-hover:text-indigo-600'

    const whatsappHoverClass = isLight
        ? 'group-hover:text-emerald-300'
        : 'group-hover:text-emerald-600'

    return (
        <section aria-labelledby={headingId} className="min-w-0 max-w-full py-5 sm:py-7 lg:py-8">
            <div
                className="relative isolate max-w-full overflow-hidden rounded-2xl border border-neutral-200 shadow-sm sm:rounded-3xl"
                style={{
                    backgroundColor: config.background_color,
                }}
            >
                <CallToActionBackground config={config} />

                <div className="mx-auto min-w-0 max-w-5xl px-5 py-10 text-center sm:px-8 sm:py-12 lg:px-10 lg:py-16">
                    <div className="mx-auto min-w-0 max-w-2xl">
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
                                'break-words text-3xl font-bold tracking-tight',
                                '[overflow-wrap:anywhere]',
                                'sm:text-4xl',
                                headingClass,
                            ].join(' ')}
                        >
                            {config.heading}
                        </h2>

                        {config.description && (
                            <p
                                className={[
                                    'mx-auto mt-4 max-w-xl whitespace-pre-line break-words',
                                    'text-sm leading-relaxed [overflow-wrap:anywhere]',
                                    'sm:text-base',
                                    descriptionClass,
                                ].join(' ')}
                            >
                                {config.description}
                            </p>
                        )}

                        {contactCount > 0 && (
                            <div
                                className={[
                                    'mt-7 grid min-w-0 max-w-full grid-cols-1 gap-3',
                                    contactGridClass,
                                ].join(' ')}
                            >
                                {phoneHref && config.phone_number && (
                                    <a
                                        href={phoneHref}
                                        aria-label={`${config.phone_label ?? 'Call Us'}: ${config.phone_number}`}
                                        className={[
                                            'group min-h-12 min-w-0 max-w-full rounded-xl border p-4 text-center',
                                            'transition duration-200',
                                            'focus-visible:outline-none focus-visible:ring-2',
                                            'focus-visible:ring-indigo-500 focus-visible:ring-offset-2',
                                            'motion-reduce:transition-none',
                                            cardClass,
                                        ].join(' ')}
                                    >
                                        <span className="mb-3 flex justify-center">
                                            <span className="inline-flex h-10 w-10 items-center justify-center rounded-full bg-indigo-500/10">
                                                <Phone
                                                    aria-hidden="true"
                                                    className="h-5 w-5 text-indigo-600"
                                                />
                                            </span>
                                        </span>

                                        <span
                                            className={[
                                                'block text-xs font-semibold tracking-wider uppercase',
                                                labelClass,
                                            ].join(' ')}
                                        >
                                            {config.phone_label ?? 'Call Us'}
                                        </span>

                                        <span
                                            className={[
                                                'mt-1 block break-words text-sm font-bold transition',
                                                '[overflow-wrap:anywhere]',
                                                phoneHoverClass,
                                            ].join(' ')}
                                        >
                                            {config.phone_number}
                                        </span>
                                    </a>
                                )}

                                {whatsappHref && config.whatsapp_number && (
                                    <a
                                        href={whatsappHref}
                                        target="_blank"
                                        rel="noopener noreferrer"
                                        aria-label={`${config.whatsapp_label ?? 'WhatsApp'}: ${config.whatsapp_number}. Opens in a new tab.`}
                                        className={[
                                            'group min-h-12 min-w-0 max-w-full rounded-xl border p-4 text-center',
                                            'transition duration-200',
                                            'focus-visible:outline-none focus-visible:ring-2',
                                            'focus-visible:ring-emerald-500 focus-visible:ring-offset-2',
                                            'motion-reduce:transition-none',
                                            cardClass,
                                        ].join(' ')}
                                    >
                                        <span className="mb-3 flex justify-center">
                                            <span className="inline-flex h-10 w-10 items-center justify-center rounded-full bg-emerald-500/10">
                                                <MessageCircle
                                                    aria-hidden="true"
                                                    className="h-5 w-5 text-emerald-600"
                                                />
                                            </span>
                                        </span>

                                        <span
                                            className={[
                                                'block text-xs font-semibold tracking-wider uppercase',
                                                labelClass,
                                            ].join(' ')}
                                        >
                                            {config.whatsapp_label ?? 'WhatsApp'}
                                        </span>

                                        <span
                                            className={[
                                                'mt-1 block break-words text-sm font-bold transition',
                                                '[overflow-wrap:anywhere]',
                                                whatsappHoverClass,
                                            ].join(' ')}
                                        >
                                            {config.whatsapp_number}
                                        </span>
                                    </a>
                                )}

                                {emailHref && config.email && (
                                    <a
                                        href={emailHref}
                                        aria-label={`${config.email_label ?? 'Email Us'}: ${config.email}`}
                                        className={[
                                            'group min-h-12 min-w-0 max-w-full rounded-xl border p-4 text-center',
                                            'transition duration-200',
                                            'focus-visible:outline-none focus-visible:ring-2',
                                            'focus-visible:ring-indigo-500 focus-visible:ring-offset-2',
                                            'motion-reduce:transition-none',
                                            cardClass,
                                        ].join(' ')}
                                    >
                                        <span className="mb-3 flex justify-center">
                                            <span className="inline-flex h-10 w-10 items-center justify-center rounded-full bg-indigo-500/10">
                                                <Mail
                                                    aria-hidden="true"
                                                    className="h-5 w-5 text-indigo-600"
                                                />
                                            </span>
                                        </span>

                                        <span
                                            className={[
                                                'block text-xs font-semibold tracking-wider uppercase',
                                                labelClass,
                                            ].join(' ')}
                                        >
                                            {config.email_label ?? 'Email Us'}
                                        </span>

                                        <span
                                            className={[
                                                'mt-1 block break-all text-sm font-bold transition',
                                                emailHoverClass,
                                            ].join(' ')}
                                        >
                                            {config.email}
                                        </span>
                                    </a>
                                )}
                            </div>
                        )}

                        {whatsappHref && (
                            <div className="mt-7 min-w-0 max-w-full">
                                <a
                                    href={whatsappHref}
                                    target="_blank"
                                    rel="noopener noreferrer"
                                    aria-label="Start WhatsApp conversation. Opens in a new tab."
                                    className={[
                                        'group inline-flex min-h-12 w-full max-w-full items-center justify-center',
                                        'gap-2 rounded-xl px-6 py-3 text-sm font-semibold',
                                        'shadow-sm transition sm:w-auto',
                                        'focus-visible:outline-none focus-visible:ring-2',
                                        'focus-visible:ring-emerald-500 focus-visible:ring-offset-2',
                                        'motion-reduce:transition-none',
                                        isLight
                                            ? 'bg-white text-neutral-950 hover:bg-neutral-100'
                                            : 'bg-neutral-950 text-white hover:bg-neutral-800',
                                    ].join(' ')}
                                >
                                    <MessageCircle
                                        aria-hidden="true"
                                        className="h-5 w-5 shrink-0"
                                    />

                                    <span className="min-w-0 break-words [overflow-wrap:anywhere]">
                                        Start WhatsApp Conversation
                                    </span>

                                    <ArrowRight
                                        aria-hidden="true"
                                        className="h-4 w-4 shrink-0 transition-transform group-hover:translate-x-1 motion-reduce:transform-none motion-reduce:transition-none"
                                    />
                                </a>
                            </div>
                        )}
                    </div>
                </div>
            </div>
        </section>
    )
}
