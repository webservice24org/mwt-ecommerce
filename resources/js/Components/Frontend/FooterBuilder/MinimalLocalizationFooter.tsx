import { Coins, Languages } from 'lucide-react'

import SharedBottomFooter from '@/Components/FooterBuilder/SharedBottomFooter'
import {
    readFooterBrand,
    readFooterCopyright,
    readFooterDeveloper,
    readFooterLinkGroups,
    readFooterLocalization,
    readFooterSocialLinks,
} from '@/types/footer-builder'
import type { FooterConfig } from '@/types/footer-builder'

interface Props {
    config: FooterConfig
}

export default function MinimalLocalizationFooter({ config }: Props) {
    const brand = readFooterBrand(config)

    const copyright = readFooterCopyright(config)

    const developer = readFooterDeveloper(config)

    const linkGroups = readFooterLinkGroups(config)

    const socialLinks = readFooterSocialLinks(config)

    const localization = readFooterLocalization(config)

    const brandName = brand.name.trim() !== '' ? brand.name : 'Your Store'

    return (
        <footer
            aria-label="Store footer"
            className="w-full min-w-0 max-w-full overflow-x-clip bg-black text-neutral-100"
        >
            <div className="w-full min-w-0 max-w-full bg-black">
                <div className="mx-auto w-full min-w-0 max-w-7xl px-4 py-10 sm:px-6 sm:py-12 lg:px-8">
                    <div className="grid min-w-0 grid-cols-1 gap-10 lg:grid-cols-12 lg:gap-12">
                        <div className="min-w-0 max-w-full lg:col-span-4">
                            <h2 className="max-w-full [overflow-wrap:anywhere] text-2xl font-black uppercase tracking-[0.12em] text-white">
                                {brandName}
                            </h2>

                            {brand.description !== '' && (
                                <p className="mt-4 max-w-md [overflow-wrap:anywhere] text-xs leading-6 text-neutral-500">
                                    {brand.description}
                                </p>
                            )}

                            {localization.enabled && (
                                <div className="mt-8 min-w-0 max-w-full">
                                    <h3 className="max-w-full [overflow-wrap:anywhere] text-[10px] font-bold uppercase tracking-[0.14em] text-neutral-500">
                                        Regional settings
                                    </h3>

                                    <p className="mt-2 max-w-sm [overflow-wrap:anywhere] text-[11px] leading-5 text-neutral-600">
                                        Available regional options are shown below. Changing the
                                        application language or currency is not enabled by the
                                        footer itself.
                                    </p>

                                    <div className="mt-4 min-w-0 max-w-full space-y-4">
                                        {localization.languages.length > 0 && (
                                            <RegionalOptions
                                                type="language"
                                                options={localization.languages}
                                            />
                                        )}

                                        {localization.currencies.length > 0 && (
                                            <RegionalOptions
                                                type="currency"
                                                options={localization.currencies}
                                            />
                                        )}
                                    </div>
                                </div>
                            )}
                        </div>

                        <div className="min-w-0 max-w-full lg:col-span-8">
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
                                            <h2 className="max-w-full [overflow-wrap:anywhere] text-[11px] font-bold uppercase tracking-[0.12em] text-neutral-200">
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
                                                            className="inline max-w-full [overflow-wrap:anywhere] text-xs leading-5 text-neutral-500 transition-colors hover:text-emerald-400 focus-visible:rounded-sm focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-emerald-400 focus-visible:ring-offset-2 focus-visible:ring-offset-black"
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
                template="minimal_localized"
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

function RegionalOptions({
    type,
    options,
}: {
    type: 'language' | 'currency'

    options: {
        code: string
        label: string
    }[]
}) {
    const Icon = type === 'language' ? Languages : Coins

    const title = type === 'language' ? 'Languages' : 'Currencies'

    return (
        <section className="min-w-0 max-w-full">
            <h4 className="flex min-w-0 max-w-full items-center gap-2 text-xs font-semibold text-neutral-300">
                <Icon
                    aria-hidden="true"
                    className="h-4 w-4 shrink-0 text-emerald-400"
                    strokeWidth={1.8}
                />

                <span className="min-w-0 max-w-full [overflow-wrap:anywhere]">{title}</span>
            </h4>

            <ul className="mt-2 flex min-w-0 max-w-full flex-wrap gap-2">
                {options.map((option, index) => (
                    <li
                        key={`${option.code}-${index}`}
                        className="inline-flex min-w-0 max-w-full flex-wrap items-center gap-x-2 gap-y-1 rounded-lg border border-neutral-800 bg-neutral-900 px-3 py-2 text-[11px] text-neutral-300"
                    >
                        <span className="min-w-0 max-w-full [overflow-wrap:anywhere]">
                            {option.label}
                        </span>

                        <span className="max-w-full [overflow-wrap:anywhere] font-mono text-[9px] uppercase text-neutral-600">
                            {option.code}
                        </span>
                    </li>
                ))}
            </ul>
        </section>
    )
}
