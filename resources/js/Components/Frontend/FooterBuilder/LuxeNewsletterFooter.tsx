import {
    Award,
    BadgeCheck,
    CreditCard,
    Headphones,
    LockKeyhole,
    Package,
    RefreshCcw,
    ShieldCheck,
    Truck,
} from 'lucide-react'
import { useForm } from '@inertiajs/react'
import { useRef } from 'react'
import type { FormEvent } from 'react'

import SharedBottomFooter from '@/Components/FooterBuilder/SharedBottomFooter'
import {
    readFooterBrand,
    readFooterCopyright,
    readFooterDeveloper,
    readFooterLinkGroups,
    readFooterNewsletter,
    readFooterPaymentMethods,
    readFooterSocialLinks,
    readFooterValueProps,
} from '@/types/footer-builder'
import type { FooterConfig, FooterValuePropIcon } from '@/types/footer-builder'

interface Props {
    config: FooterConfig
}

export default function LuxeNewsletterFooter({ config }: Props) {
    const brand = readFooterBrand(config)

    const copyright = readFooterCopyright(config)

    const developer = readFooterDeveloper(config)

    const linkGroups = readFooterLinkGroups(config)

    const socialLinks = readFooterSocialLinks(config)

    const valueProps = readFooterValueProps(config)

    const newsletter = readFooterNewsletter(config)

    const paymentMethods = readFooterPaymentMethods(config)

    const brandName = brand.name.trim() !== '' ? brand.name : 'Your Store'

    return (
        <footer
            aria-label="Store footer"
            className="w-full min-w-0 max-w-full overflow-x-clip bg-neutral-900 text-neutral-100"
        >
            {valueProps.length > 0 && (
                <section
                    aria-label="Store benefits"
                    className="w-full min-w-0 max-w-full border-b border-neutral-800 bg-neutral-950/40"
                >
                    <ul className="mx-auto grid w-full min-w-0 max-w-7xl grid-cols-1 gap-6 px-4 py-7 sm:grid-cols-2 sm:px-6 lg:grid-cols-4 lg:px-8">
                        {valueProps.map((item, index) => (
                            <li
                                key={index}
                                className="flex min-w-0 max-w-full items-start gap-3"
                            >
                                <div className="flex h-10 w-10 shrink-0 items-center justify-center rounded-xl border border-neutral-800 bg-neutral-900 text-indigo-400">
                                    <ValuePropIcon icon={item.icon} />
                                </div>

                                <div className="min-w-0 flex-1">
                                    <p className="max-w-full [overflow-wrap:anywhere] text-sm font-semibold text-white">
                                        {item.title}
                                    </p>

                                    <p className="mt-1 max-w-full [overflow-wrap:anywhere] text-xs leading-5 text-neutral-500">
                                        {item.description}
                                    </p>
                                </div>
                            </li>
                        ))}
                    </ul>
                </section>
            )}

            <div className="w-full min-w-0 max-w-full bg-neutral-900">
                <div className="mx-auto w-full min-w-0 max-w-7xl px-4 py-10 sm:px-6 sm:py-12 lg:px-8">
                    <div className="grid min-w-0 grid-cols-1 gap-10 lg:grid-cols-12 lg:gap-12">
                        <div className="min-w-0 max-w-full lg:col-span-5">
                            <h2 className="max-w-full [overflow-wrap:anywhere] text-2xl font-black tracking-tight text-white">
                                {brandName}
                            </h2>

                            {brand.description !== '' && (
                                <p className="mt-3 max-w-xl [overflow-wrap:anywhere] text-sm leading-6 text-neutral-400">
                                    {brand.description}
                                </p>
                            )}

                            {newsletter.enabled && (
                                <div className="mt-7 min-w-0 max-w-full">
                                    <NewsletterForm
                                        description={newsletter.description}
                                        placeholder={newsletter.placeholder}
                                        buttonLabel={newsletter.button_label}
                                    />
                                </div>
                            )}

                            {paymentMethods.length > 0 && (
                                <section
                                    className="mt-6 min-w-0 max-w-full"
                                    aria-labelledby="footer-payment-methods-heading"
                                >
                                    <h3
                                        id="footer-payment-methods-heading"
                                        className="max-w-full [overflow-wrap:anywhere] text-[10px] font-bold uppercase tracking-[0.14em] text-neutral-500"
                                    >
                                        Payment methods
                                    </h3>

                                    <ul className="mt-3 flex min-w-0 max-w-full flex-wrap gap-2">
                                        {paymentMethods.map((paymentMethod, index) => (
                                            <li
                                                key={`${paymentMethod}-${index}`}
                                                className="max-w-full [overflow-wrap:anywhere] rounded-md border border-neutral-700 bg-neutral-950 px-2.5 py-1.5 font-mono text-[10px] font-bold text-neutral-400"
                                            >
                                                {paymentMethod}
                                            </li>
                                        ))}
                                    </ul>
                                </section>
                            )}
                        </div>

                        <div className="min-w-0 max-w-full lg:col-span-7">
                            {linkGroups.length > 0 ? (
                                <div className="grid min-w-0 grid-cols-1 gap-x-8 gap-y-8 sm:grid-cols-2 lg:grid-cols-3">
                                    {linkGroups.map((group, groupIndex) => (
                                        <nav
                                            key={groupIndex}
                                            aria-label={
                                                group.heading || `Footer links ${groupIndex + 1}`
                                            }
                                            className="min-w-0 max-w-full"
                                        >
                                            <h2 className="max-w-full [overflow-wrap:anywhere] text-xs font-bold uppercase tracking-[0.12em] text-neutral-200">
                                                {group.heading || 'Links'}
                                            </h2>

                                            <ul className="mt-4 min-w-0 max-w-full space-y-2.5">
                                                {group.links.map((link, linkIndex) => (
                                                    <li
                                                        key={linkIndex}
                                                        className="min-w-0 max-w-full"
                                                    >
                                                        <a
                                                            href={link.url}
                                                            className="inline max-w-full [overflow-wrap:anywhere] text-xs leading-5 text-neutral-400 transition-colors hover:text-white focus-visible:rounded-sm focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-indigo-400 focus-visible:ring-offset-2 focus-visible:ring-offset-neutral-900"
                                                        >
                                                            {link.label}
                                                        </a>
                                                    </li>
                                                ))}
                                            </ul>
                                        </nav>
                                    ))}
                                </div>
                            ) : null}
                        </div>
                    </div>
                </div>
            </div>

            <SharedBottomFooter
                template="luxe_newsletter"
                copyright={copyright}
                socialLinks={socialLinks}
                developer={developer}
                brandName={brandName}
                mode="storefront"
                contentClassName="mx-auto w-full max-w-7xl"
            />
        </footer>
    )
}

function NewsletterForm({
    description,
    placeholder,
    buttonLabel,
}: {
    description: string
    placeholder: string
    buttonLabel: string
}) {
    const inputRef = useRef<HTMLInputElement>(null)

    const { data, setData, post, processing, errors, reset, clearErrors, recentlySuccessful } =
        useForm({
            email: '',
        })

    const descriptionId = 'footer-newsletter-description'

    const errorId = 'footer-newsletter-email-error'

    const describedBy = [description !== '' ? descriptionId : null, errors.email ? errorId : null]
        .filter(Boolean)
        .join(' ')

    const submit = (event: FormEvent<HTMLFormElement>) => {
        event.preventDefault()

        if (processing) {
            return
        }

        clearErrors()

        post(route('newsletter.subscribe'), {
            preserveScroll: true,

            errorBag: 'newsletterSubscription',

            onSuccess: () => {
                reset('email')

                clearErrors()
            },

            onError: () => {
                inputRef.current?.focus()
            },
        })
    }

    return (
        <div className="w-full min-w-0 max-w-full">
            <h3 className="max-w-full [overflow-wrap:anywhere] text-xs font-bold uppercase tracking-[0.14em] text-neutral-200">
                Newsletter
            </h3>

            {description !== '' && (
                <p
                    id={descriptionId}
                    className="mt-3 max-w-lg [overflow-wrap:anywhere] text-xs leading-5 text-neutral-400"
                >
                    {description}
                </p>
            )}

            <form
                onSubmit={submit}
                className="mt-4 w-full min-w-0 max-w-full"
                noValidate
                aria-busy={processing}
            >
                <div className="flex w-full min-w-0 max-w-full flex-col gap-2 sm:flex-row">
                    <label htmlFor="footer-newsletter-email" className="sr-only">
                        Email address
                    </label>

                    <input
                        ref={inputRef}
                        id="footer-newsletter-email"
                        type="email"
                        name="email"
                        inputMode="email"
                        autoComplete="email"
                        required
                        maxLength={254}
                        value={data.email}
                        placeholder={placeholder || 'Enter your email address'}
                        aria-invalid={errors.email ? true : undefined}
                        aria-describedby={describedBy !== '' ? describedBy : undefined}
                        disabled={processing}
                        onChange={(event) => {
                            if (errors.email) {
                                clearErrors('email')
                            }

                            setData('email', event.target.value)
                        }}
                        className="h-11 w-full min-w-0 max-w-full flex-1 rounded-xl border border-neutral-700 bg-neutral-950 px-4 text-sm text-white outline-none placeholder:text-neutral-600 focus:border-indigo-400 focus:ring-2 focus:ring-indigo-400/30 disabled:cursor-not-allowed disabled:opacity-60"
                    />

                    <button
                        type="submit"
                        disabled={processing}
                        aria-disabled={processing}
                        className="inline-flex min-h-11 w-full min-w-0 max-w-full items-center justify-center rounded-xl bg-indigo-500 px-4 py-2 text-center text-sm font-bold text-white transition-colors hover:bg-indigo-400 focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-indigo-300 focus-visible:ring-offset-2 focus-visible:ring-offset-neutral-900 disabled:cursor-not-allowed disabled:opacity-60 sm:w-auto sm:shrink-0 sm:px-5"
                    >
                        <span className="max-w-full [overflow-wrap:anywhere]">
                            {processing ? 'Submitting…' : buttonLabel || 'Subscribe'}
                        </span>
                    </button>
                </div>

                <div className="min-h-5 min-w-0 max-w-full">
                    {errors.email && (
                        <p
                            id={errorId}
                            role="alert"
                            aria-atomic="true"
                            className="mt-2 max-w-full [overflow-wrap:anywhere] text-xs font-medium text-red-300"
                        >
                            {errors.email}
                        </p>
                    )}

                    {recentlySuccessful && !errors.email && (
                        <p
                            role="status"
                            aria-live="polite"
                            aria-atomic="true"
                            className="mt-2 max-w-full [overflow-wrap:anywhere] text-xs font-medium text-emerald-400"
                        >
                            Thanks! You are subscribed.
                        </p>
                    )}
                </div>
            </form>
        </div>
    )
}

function ValuePropIcon({ icon }: { icon: FooterValuePropIcon }) {
    const className = 'h-5 w-5'

    switch (icon) {
        case 'package':
            return <Package aria-hidden="true" className={className} />

        case 'truck':
            return <Truck aria-hidden="true" className={className} />

        case 'shield-check':
            return <ShieldCheck aria-hidden="true" className={className} />

        case 'refresh-ccw':
            return <RefreshCcw aria-hidden="true" className={className} />

        case 'headphones':
            return <Headphones aria-hidden="true" className={className} />

        case 'credit-card':
            return <CreditCard aria-hidden="true" className={className} />

        case 'lock':
            return <LockKeyhole aria-hidden="true" className={className} />

        case 'badge-check':
            return <BadgeCheck aria-hidden="true" className={className} />

        case 'award':
            return <Award aria-hidden="true" className={className} />
    }
}