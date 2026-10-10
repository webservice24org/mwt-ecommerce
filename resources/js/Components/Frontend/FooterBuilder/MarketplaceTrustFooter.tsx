import { BadgeCheck } from 'lucide-react'

import SharedBottomFooter from '@/Components/FooterBuilder/SharedBottomFooter'
import {
    readFooterBrand,
    readFooterCertifications,
    readFooterCopyright,
    readFooterDeveloper,
    readFooterLinkGroups,
    readFooterPopularLinks,
    readFooterPromotion,
    readFooterSocialLinks,
} from '@/types/footer-builder'
import type { FooterConfig } from '@/types/footer-builder'

interface Props {
    config: FooterConfig
}

export default function MarketplaceTrustFooter({ config }: Props) {
    const brand = readFooterBrand(config)

    const copyright = readFooterCopyright(config)

    const developer = readFooterDeveloper(config)

    const linkGroups = readFooterLinkGroups(config)

    const socialLinks = readFooterSocialLinks(config)

    const promotion = readFooterPromotion(config)

    const popularLinks = readFooterPopularLinks(config)

    const certifications = readFooterCertifications(config)

    const brandName = brand.name.trim() !== '' ? brand.name : 'Your Store'

    return (
        <footer
            aria-label="Store footer"
            className="w-full min-w-0 max-w-full overflow-x-clip bg-neutral-900 text-neutral-100"
        >
            {promotion.enabled && (
                <section
                    aria-label="Current promotion"
                    className="w-full min-w-0 max-w-full bg-gradient-to-r from-amber-600 via-orange-600 to-amber-700 text-white"
                >
                    <div className="mx-auto flex w-full min-w-0 max-w-7xl flex-col items-stretch justify-between gap-3 px-4 py-4 text-center sm:px-6 md:flex-row md:items-center md:text-left lg:px-8">
                        <div className="flex min-w-0 max-w-full flex-col items-center gap-3 sm:flex-row sm:flex-wrap md:flex-1 md:items-center">
                            {promotion.badge !== '' && (
                                <span className="max-w-full [overflow-wrap:anywhere] rounded-full bg-white/20 px-2.5 py-1 text-center font-mono text-[10px] font-extrabold uppercase tracking-[0.12em] sm:shrink-0">
                                    {promotion.badge}
                                </span>
                            )}

                            <p className="min-w-0 max-w-full flex-1 [overflow-wrap:anywhere] text-xs font-semibold leading-5 sm:text-sm">
                                {promotion.message}

                                {promotion.code !== '' && (
                                    <>
                                        {' '}
                                        <code className="max-w-full [overflow-wrap:anywhere] font-mono font-bold underline underline-offset-2">
                                            {promotion.code}
                                        </code>
                                    </>
                                )}
                            </p>
                        </div>

                        <a
                            href={promotion.button_url}
                            className="inline-flex min-h-10 w-full min-w-0 max-w-full items-center justify-center rounded-xl bg-neutral-950 px-4 py-2 text-xs font-bold text-white shadow-md transition-colors hover:bg-neutral-900 focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-white focus-visible:ring-offset-2 focus-visible:ring-offset-orange-600 md:w-auto md:shrink-0"
                        >
                            <span className="min-w-0 max-w-full [overflow-wrap:anywhere]">
                                {promotion.button_label}
                            </span>

                            <span aria-hidden="true" className="ml-1 shrink-0">
                                →
                            </span>
                        </a>
                    </div>
                </section>
            )}

            <div className="w-full min-w-0 max-w-full bg-neutral-900">
                <div className="mx-auto w-full min-w-0 max-w-7xl space-y-9 px-4 py-10 sm:px-6 sm:py-12 lg:px-8">
                    {popularLinks.length > 0 && (
                        <nav
                            aria-label="Popular searches and categories"
                            className="min-w-0 max-w-full border-b border-neutral-800 pb-7"
                        >
                            <h2 className="max-w-full [overflow-wrap:anywhere] text-[10px] font-bold uppercase tracking-[0.14em] text-neutral-400">
                                Popular searches &amp; categories
                            </h2>

                            <ul className="mt-3 flex min-w-0 max-w-full flex-wrap gap-2">
                                {popularLinks.map((link, index) => (
                                    <li key={index} className="min-w-0 max-w-full">
                                        <a
                                            href={link.url}
                                            className="inline-flex min-w-0 max-w-full rounded-lg border border-neutral-700 bg-neutral-800/70 px-3 py-1.5 text-[11px] font-medium text-neutral-300 transition-colors hover:border-amber-500/40 hover:text-amber-300 focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-amber-400 focus-visible:ring-offset-2 focus-visible:ring-offset-neutral-900"
                                        >
                                            <span className="min-w-0 max-w-full [overflow-wrap:anywhere]">
                                                {link.label}
                                            </span>
                                        </a>
                                    </li>
                                ))}
                            </ul>
                        </nav>
                    )}

                    {(linkGroups.length > 0 || certifications.length > 0) && (
                        <div className="grid min-w-0 grid-cols-1 gap-x-8 gap-y-8 sm:grid-cols-2 lg:grid-cols-4">
                            {linkGroups.map((group, groupIndex) => (
                                <nav
                                    key={groupIndex}
                                    aria-label={group.heading || `Footer links ${groupIndex + 1}`}
                                    className="min-w-0 max-w-full"
                                >
                                    <h2 className="max-w-full [overflow-wrap:anywhere] text-[11px] font-bold uppercase tracking-[0.1em] text-amber-400">
                                        {group.heading || 'Links'}
                                    </h2>

                                    <ul className="mt-4 min-w-0 max-w-full space-y-2.5">
                                        {group.links.map((link, linkIndex) => (
                                            <li key={linkIndex} className="min-w-0 max-w-full">
                                                <a
                                                    href={link.url}
                                                    className="inline max-w-full [overflow-wrap:anywhere] text-xs leading-5 text-neutral-400 transition-colors hover:text-white focus-visible:rounded-sm focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-amber-400 focus-visible:ring-offset-2 focus-visible:ring-offset-neutral-900"
                                                >
                                                    {link.label}
                                                </a>
                                            </li>
                                        ))}
                                    </ul>
                                </nav>
                            ))}

                            {certifications.length > 0 && (
                                <section
                                    aria-labelledby="footer-certifications-heading"
                                    className="min-w-0 max-w-full"
                                >
                                    <h2
                                        id="footer-certifications-heading"
                                        className="max-w-full [overflow-wrap:anywhere] text-[11px] font-bold uppercase tracking-[0.1em] text-amber-400"
                                    >
                                        Certifications
                                    </h2>

                                    <ul className="mt-4 min-w-0 max-w-full space-y-3">
                                        {certifications.map((certification, index) => (
                                            <li
                                                key={index}
                                                className="flex min-w-0 max-w-full items-start gap-2 text-xs leading-5 text-neutral-300"
                                            >
                                                <span
                                                    aria-hidden="true"
                                                    className="mt-1.5 h-2 w-2 shrink-0 rounded-full bg-emerald-400"
                                                />

                                                <span className="min-w-0 max-w-full flex-1 [overflow-wrap:anywhere]">
                                                    {certification}
                                                </span>
                                            </li>
                                        ))}
                                    </ul>
                                </section>
                            )}
                        </div>
                    )}

                    {brand.description !== '' && (
                        <div className="flex min-w-0 max-w-full items-start gap-3 rounded-xl border border-neutral-800 bg-neutral-950/30 p-4">
                            <BadgeCheck
                                aria-hidden="true"
                                className="mt-0.5 h-4 w-4 shrink-0 text-amber-400"
                                strokeWidth={1.8}
                            />

                            <p className="min-w-0 max-w-3xl flex-1 [overflow-wrap:anywhere] text-[11px] leading-5 text-neutral-500">
                                {brand.description}
                            </p>
                        </div>
                    )}
                </div>
            </div>

            <SharedBottomFooter
                template="marketplace_trust"
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
