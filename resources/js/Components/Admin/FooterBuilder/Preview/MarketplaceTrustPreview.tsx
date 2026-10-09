import { BadgeCheck } from 'lucide-react'

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
import type { FooterConfig, FooterPreviewDevice } from '@/types/footer-builder'
import SharedBottomFooter from '@/Components/FooterBuilder/SharedBottomFooter'

interface Props {
    config: FooterConfig
    device: FooterPreviewDevice
}

export default function MarketplaceTrustPreview({ config, device }: Props) {
    const brand = readFooterBrand(config)

    const copyright = readFooterCopyright(config)

    const developer = readFooterDeveloper(config)

    const linkGroups = readFooterLinkGroups(config)

    const socialLinks = readFooterSocialLinks(config)

    const promotion = readFooterPromotion(config)

    const popularLinks = readFooterPopularLinks(config)

    const certifications = readFooterCertifications(config)

    const tablet = device === 'tablet'

    const mobile = device === 'mobile'

    const brandName = brand.name.trim() !== '' ? brand.name : 'Your Store'

    const mainPadding = mobile ? 'px-4 py-8' : tablet ? 'px-6 py-10' : 'px-10 py-12'

    return (
        <footer
            aria-label="Marketplace Trust footer preview"
            className="overflow-hidden bg-neutral-900 text-neutral-100"
        >
            {promotion.enabled && (
                <div className="bg-gradient-to-r from-amber-600 via-orange-600 to-amber-700 px-4 py-4 text-white sm:px-6">
                    <div
                        className={[
                            'flex min-w-0 gap-3',
                            mobile
                                ? 'flex-col items-center text-center'
                                : 'items-center justify-between text-left',
                        ].join(' ')}
                    >
                        <div
                            className={[
                                'flex min-w-0 gap-3',
                                mobile ? 'flex-col items-center' : 'items-center',
                            ].join(' ')}
                        >
                            {promotion.badge !== '' && (
                                <span className="shrink-0 rounded-full bg-white/20 px-2.5 py-1 font-mono text-[10px] font-extrabold uppercase tracking-[0.12em]">
                                    {promotion.badge}
                                </span>
                            )}

                            <p className="min-w-0 break-words text-xs font-semibold leading-5 sm:text-sm">
                                {promotion.message || 'Promotion message'}

                                {promotion.code !== '' && (
                                    <>
                                        {' '}
                                        <span className="font-mono font-bold underline underline-offset-2">
                                            {promotion.code}
                                        </span>
                                    </>
                                )}
                            </p>
                        </div>

                        <div
                            className={[
                                'shrink-0 rounded-xl bg-neutral-950 px-4 py-2 text-xs font-bold text-white shadow-md',
                                mobile ? 'w-full' : '',
                            ].join(' ')}
                        >
                            {promotion.button_label || 'Shop Now'}

                            <span aria-hidden="true" className="ml-1">
                                →
                            </span>
                        </div>
                    </div>
                </div>
            )}

            <div
                className={[
                    'space-y-9 border-b border-neutral-800 bg-neutral-900',
                    mainPadding,
                ].join(' ')}
            >
                {popularLinks.length > 0 && (
                    <div className="border-b border-neutral-800 pb-7">
                        <p className="text-[10px] font-bold uppercase tracking-[0.14em] text-neutral-400">
                            Popular searches &amp; categories
                        </p>

                        <div className="mt-3 flex flex-wrap gap-2">
                            {popularLinks.map((link, index) => (
                                <span
                                    key={index}
                                    className="rounded-lg border border-neutral-700 bg-neutral-800/70 px-3 py-1.5 text-[11px] font-medium text-neutral-300"
                                >
                                    {link.label || 'Popular link'}
                                </span>
                            ))}
                        </div>
                    </div>
                )}

                <div
                    className="grid min-w-0 gap-x-8 gap-y-8"
                    style={{
                        gridTemplateColumns: marketplaceGrid(
                            device,
                            linkGroups.length + (certifications.length > 0 ? 1 : 0),
                        ),
                    }}
                >
                    {linkGroups.map((group, groupIndex) => (
                        <div key={groupIndex} className="min-w-0">
                            <p className="break-words text-[11px] font-bold uppercase tracking-[0.1em] text-amber-400">
                                {group.heading || `Group ${groupIndex + 1}`}
                            </p>

                            {group.links.length > 0 ? (
                                <div className="mt-4 space-y-2.5">
                                    {group.links.map((link, linkIndex) => (
                                        <span
                                            key={linkIndex}
                                            className="block break-words text-xs leading-5 text-neutral-400"
                                        >
                                            {link.label || 'Link'}
                                        </span>
                                    ))}
                                </div>
                            ) : (
                                <p className="mt-3 text-[11px] italic text-neutral-600">No links</p>
                            )}
                        </div>
                    ))}

                    {certifications.length > 0 && (
                        <div className="min-w-0">
                            <p className="text-[11px] font-bold uppercase tracking-[0.1em] text-amber-400">
                                Certifications
                            </p>

                            <div className="mt-4 space-y-3">
                                {certifications.map((certification, index) => (
                                    <div
                                        key={index}
                                        className="flex min-w-0 items-start gap-2 text-xs leading-5 text-neutral-300"
                                    >
                                        <span className="mt-1.5 h-2 w-2 shrink-0 rounded-full bg-emerald-400" />

                                        <span className="min-w-0 break-words">{certification}</span>
                                    </div>
                                ))}
                            </div>
                        </div>
                    )}
                </div>

                {linkGroups.length === 0 && certifications.length === 0 && (
                    <div className="rounded-xl border border-dashed border-neutral-700 bg-neutral-950/40 p-5 text-xs leading-5 text-neutral-500">
                        No marketplace link groups or certifications configured.
                    </div>
                )}

                {brand.description !== '' && (
                    <div className="flex items-start gap-3 rounded-xl border border-neutral-800 bg-neutral-950/30 p-4">
                        <BadgeCheck
                            aria-hidden="true"
                            className="mt-0.5 h-4 w-4 shrink-0 text-amber-400"
                            strokeWidth={1.8}
                        />

                        <p className="max-w-3xl break-words text-[11px] leading-5 text-neutral-500">
                            {brand.description}
                        </p>
                    </div>
                )}
            </div>

            <SharedBottomFooter
                template="marketplace_trust"
                copyright={copyright}
                socialLinks={socialLinks}
                developer={developer}
                brandName={brandName}
                device={device}
                mode="preview"
            />
        </footer>
    )
}

function marketplaceGrid(device: FooterPreviewDevice, count: number): string {
    if (count <= 1) {
        return 'minmax(0, 1fr)'
    }

    const columns =
        device === 'desktop' ? Math.min(count, 4) : device === 'tablet' ? Math.min(count, 2) : 1

    return `repeat(${columns}, minmax(0, 1fr))`
}
