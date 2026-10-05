import { Mail, MessageCircle, Phone } from 'lucide-react'
import { useId } from 'react'

import {
    buildEmailHref,
    buildPhoneHref,
    buildWhatsAppHref,
    type CallToActionConfig,
} from './call-to-action-config'
import CallToActionBackground from './CallToActionBackground'

interface Props {
    config: CallToActionConfig
}

export default function HighImpact({ config }: Props) {
    const headingId = useId()

    const whatsappHref = buildWhatsAppHref(config.whatsapp_number, config.whatsapp_message)

    const emailHref = buildEmailHref(config.email)

    const phoneHref = buildPhoneHref(config.phone_number)

    const lightTheme = config.text_theme === 'light'

    const headingClass = lightTheme ? 'text-white' : 'text-neutral-950'

    const descriptionClass = lightTheme ? 'text-neutral-200' : 'text-neutral-700'

    const eyebrowClass = lightTheme
        ? 'border-white/15 bg-white/10 text-indigo-200'
        : 'border-neutral-900/10 bg-white/60 text-indigo-700'

    const secondaryButtonClass = lightTheme
        ? 'border-white/15 bg-white/10 text-white hover:bg-white/15 focus-visible:ring-white'
        : 'border-neutral-900/10 bg-white text-neutral-950 hover:bg-neutral-100 focus-visible:ring-neutral-900'

    const phoneTextClass = lightTheme ? 'text-neutral-300' : 'text-neutral-600'

    const phoneLinkClass = lightTheme
        ? 'text-indigo-200 hover:text-white focus-visible:ring-white'
        : 'text-indigo-700 hover:text-indigo-900 focus-visible:ring-indigo-700'

    const hasPrimaryActions = whatsappHref !== '' || emailHref !== ''

    return (
        <section aria-labelledby={headingId} className="min-w-0 max-w-full py-5 sm:py-7 lg:py-8">
            <div
                className="relative isolate max-w-full overflow-hidden rounded-2xl shadow-xl sm:rounded-3xl"
                style={{
                    backgroundColor: config.background_color,
                }}
            >
                <CallToActionBackground config={config} />

                <div
                    aria-hidden="true"
                    className="pointer-events-none absolute -top-24 -left-24 -z-10 h-72 w-72 rounded-full bg-indigo-500/20 blur-3xl"
                />

                <div
                    aria-hidden="true"
                    className="pointer-events-none absolute -right-24 -bottom-24 -z-10 h-72 w-72 rounded-full bg-fuchsia-500/15 blur-3xl"
                />

                <div className="relative z-10 mx-auto flex min-h-80 max-w-4xl min-w-0 flex-col items-center justify-center px-5 py-14 text-center sm:min-h-96 sm:px-8 sm:py-16 lg:px-12 lg:py-20">
                    {config.eyebrow && (
                        <span
                            className={[
                                'inline-flex max-w-full items-center rounded-full border px-3 py-1.5',
                                'break-words text-xs font-semibold tracking-wider uppercase',
                                '[overflow-wrap:anywhere]',
                                eyebrowClass,
                            ].join(' ')}
                        >
                            {config.eyebrow}
                        </span>
                    )}

                    <h2
                        id={headingId}
                        className={[
                            'max-w-4xl break-words text-3xl font-bold tracking-tight',
                            'leading-tight [overflow-wrap:anywhere]',
                            'sm:text-4xl lg:text-5xl',
                            config.eyebrow ? 'mt-5' : '',
                            headingClass,
                        ].join(' ')}
                    >
                        {config.heading}
                    </h2>

                    {config.description && (
                        <p
                            className={[
                                'mt-4 max-w-2xl whitespace-pre-line break-words',
                                'text-base leading-relaxed [overflow-wrap:anywhere]',
                                'sm:text-lg',
                                descriptionClass,
                            ].join(' ')}
                        >
                            {config.description}
                        </p>
                    )}

                    {hasPrimaryActions && (
                        <div className="mt-7 flex w-full max-w-full flex-col items-stretch justify-center gap-3 sm:w-auto sm:flex-row sm:items-center">
                            {whatsappHref && config.whatsapp_number && (
                                <a
                                    href={whatsappHref}
                                    target="_blank"
                                    rel="noopener noreferrer"
                                    aria-label={`${config.whatsapp_label ?? 'WhatsApp'}: ${config.whatsapp_number}. Opens in a new tab.`}
                                    className={[
                                        'inline-flex min-h-12 w-full max-w-full items-center justify-center gap-2',
                                        'rounded-xl bg-emerald-600 px-6 py-3 text-center',
                                        'text-sm font-semibold text-white shadow-lg',
                                        'transition hover:bg-emerald-500',
                                        'focus-visible:outline-none focus-visible:ring-2',
                                        'focus-visible:ring-emerald-400 focus-visible:ring-offset-2',
                                        'focus-visible:ring-offset-transparent',
                                        'active:scale-[0.98]',
                                        'motion-reduce:transform-none motion-reduce:transition-none',
                                        'sm:w-auto sm:px-8',
                                    ].join(' ')}
                                >
                                    <MessageCircle
                                        aria-hidden="true"
                                        className="h-5 w-5 shrink-0"
                                    />

                                    <span className="break-words [overflow-wrap:anywhere]">
                                        {config.whatsapp_label ?? 'WhatsApp'}
                                    </span>
                                </a>
                            )}

                            {emailHref && config.email && (
                                <a
                                    href={emailHref}
                                    aria-label={`${config.email_label ?? 'Email Us'}: ${config.email}`}
                                    className={[
                                        'inline-flex min-h-12 w-full max-w-full items-center justify-center gap-2',
                                        'rounded-xl border px-6 py-3 text-center text-sm font-semibold',
                                        'transition focus-visible:outline-none focus-visible:ring-2',
                                        'focus-visible:ring-offset-2 focus-visible:ring-offset-transparent',
                                        'active:scale-[0.98]',
                                        'motion-reduce:transform-none motion-reduce:transition-none',
                                        'sm:w-auto sm:px-8',
                                        secondaryButtonClass,
                                    ].join(' ')}
                                >
                                    <Mail aria-hidden="true" className="h-5 w-5 shrink-0" />

                                    <span className="break-words [overflow-wrap:anywhere]">
                                        {config.email_label ?? 'Email Us'}
                                    </span>
                                </a>
                            )}
                        </div>
                    )}

                    {phoneHref && config.phone_number && (
                        <div
                            className={[
                                'mt-5 flex max-w-full flex-wrap items-center justify-center gap-1.5',
                                'text-sm',
                                phoneTextClass,
                            ].join(' ')}
                        >
                            <Phone aria-hidden="true" className="h-4 w-4 shrink-0" />

                            {config.phone_label && <span>{config.phone_label}:</span>}

                            <a
                                href={phoneHref}
                                aria-label={`${config.phone_label ?? 'Call Us'}: ${config.phone_number}`}
                                className={[
                                    'inline-flex min-h-12 max-w-full items-center',
                                    'break-words rounded-sm font-semibold underline underline-offset-4',
                                    '[overflow-wrap:anywhere]',
                                    'transition focus-visible:outline-none focus-visible:ring-2',
                                    'focus-visible:ring-offset-2 focus-visible:ring-offset-transparent',
                                    'motion-reduce:transition-none',
                                    phoneLinkClass,
                                ].join(' ')}
                            >
                                {config.phone_number}
                            </a>
                        </div>
                    )}
                </div>
            </div>
        </section>
    )
}
