import { ChevronDown, Coins, Languages } from 'lucide-react'

import {
    readFooterBrand,
    readFooterCopyright,
    readFooterDeveloper,
    readFooterLinkGroups,
    readFooterLocalization,
    readFooterSocialLinks,
} from '@/types/footer-builder'
import type { FooterConfig, FooterPreviewDevice } from '@/types/footer-builder'

import SharedBottomFooter from '@/Components/FooterBuilder/SharedBottomFooter'

interface Props {
    config: FooterConfig
    device: FooterPreviewDevice
}

export default function MinimalLocalizationPreview({ config, device }: Props) {
    const brand = readFooterBrand(config)

    const copyright = readFooterCopyright(config)

    const developer = readFooterDeveloper(config)

    const linkGroups = readFooterLinkGroups(config)

    const socialLinks = readFooterSocialLinks(config)

    const localization = readFooterLocalization(config)

    const desktop = device === 'desktop'

    const tablet = device === 'tablet'

    const mobile = device === 'mobile'

    const brandName = brand.name.trim() !== '' ? brand.name : 'Your Store'

    const language = localization.languages[0] ?? null

    const currency = localization.currencies[0] ?? null

    const mainPadding = mobile ? 'px-4 py-8' : tablet ? 'px-6 py-10' : 'px-10 py-12'

    return (
        <footer
            aria-label="Minimal Localization footer preview"
            className="overflow-hidden bg-black text-neutral-100"
        >
            <div className={['border-b border-neutral-900 bg-black', mainPadding].join(' ')}>
                <div
                    className={[
                        'grid min-w-0 gap-10',
                        desktop ? 'grid-cols-[minmax(0,4fr)_minmax(0,8fr)]' : 'grid-cols-1',
                    ].join(' ')}
                >
                    <div className="min-w-0">
                        <div>
                            <h3 className="break-words text-2xl font-black uppercase tracking-[0.12em] text-white">
                                {brandName}
                            </h3>

                            {brand.description !== '' && (
                                <p className="mt-3 max-w-md break-words text-xs leading-6 text-neutral-500">
                                    {brand.description}
                                </p>
                            )}
                        </div>

                        {localization.enabled && (
                            <div className="mt-7">
                                <p className="text-[10px] font-bold uppercase tracking-[0.14em] text-neutral-500">
                                    Regional settings
                                </p>

                                <div
                                    className={[
                                        'mt-3 flex gap-2',
                                        mobile ? 'flex-col' : 'flex-row flex-wrap',
                                    ].join(' ')}
                                >
                                    <LocalizationPreviewControl
                                        icon={Languages}
                                        label={language?.label ?? 'Language'}
                                        code={language?.code ?? ''}
                                        mobile={mobile}
                                    />

                                    <LocalizationPreviewControl
                                        icon={Coins}
                                        label={currency?.label ?? 'Currency'}
                                        code={currency?.code ?? ''}
                                        mobile={mobile}
                                    />
                                </div>

                                {(localization.languages.length > 1 ||
                                    localization.currencies.length > 1) && (
                                    <div className="mt-3 flex flex-wrap gap-x-4 gap-y-1 text-[10px] text-neutral-600">
                                        <span>
                                            {localization.languages.length} language options
                                        </span>

                                        <span>
                                            {localization.currencies.length} currency options
                                        </span>
                                    </div>
                                )}
                            </div>
                        )}

                        {!localization.enabled && (
                            <div className="mt-7 rounded-xl border border-neutral-900 bg-neutral-950 px-4 py-3">
                                <p className="text-[11px] leading-5 text-neutral-600">
                                    Localization controls are disabled.
                                </p>
                            </div>
                        )}
                    </div>

                    <div className="min-w-0">
                        {linkGroups.length > 0 ? (
                            <div
                                className="grid min-w-0 gap-x-8 gap-y-8"
                                style={{
                                    gridTemplateColumns: linkGroupGrid(device, linkGroups.length),
                                }}
                            >
                                {linkGroups.map((group, groupIndex) => (
                                    <div key={groupIndex} className="min-w-0">
                                        <p className="break-words text-[11px] font-bold uppercase tracking-[0.12em] text-neutral-200">
                                            {group.heading || `Group ${groupIndex + 1}`}
                                        </p>

                                        {group.links.length > 0 ? (
                                            <div className="mt-4 space-y-2.5">
                                                {group.links.map((link, linkIndex) => (
                                                    <span
                                                        key={linkIndex}
                                                        className="block break-words text-xs leading-5 text-neutral-500"
                                                    >
                                                        {link.label || 'Link'}
                                                    </span>
                                                ))}
                                            </div>
                                        ) : (
                                            <p className="mt-3 text-[11px] italic text-neutral-700">
                                                No links
                                            </p>
                                        )}
                                    </div>
                                ))}
                            </div>
                        ) : (
                            <div className="rounded-xl border border-dashed border-neutral-800 bg-neutral-950/50 p-5 text-xs leading-5 text-neutral-600">
                                No footer link groups configured.
                            </div>
                        )}
                    </div>
                </div>
            </div>

            <SharedBottomFooter
                template="minimal_localized"
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

function LocalizationPreviewControl({
    icon: Icon,
    label,
    code,
    mobile,
}: {
    icon: typeof Languages
    label: string
    code: string
    mobile: boolean
}) {
    return (
        <div
            aria-label={`${label} localization preview`}
            className={[
                'flex h-10 min-w-0 items-center gap-2 rounded-xl border border-neutral-800 bg-neutral-900 px-3 text-xs text-neutral-300',
                mobile ? 'w-full' : 'max-w-[220px]',
            ].join(' ')}
        >
            <Icon
                aria-hidden="true"
                className="h-4 w-4 shrink-0 text-emerald-400"
                strokeWidth={1.8}
            />

            <span className="min-w-0 flex-1 truncate">{label}</span>

            {code !== '' && (
                <span className="shrink-0 font-mono text-[9px] uppercase text-neutral-600">
                    {code}
                </span>
            )}

            <ChevronDown aria-hidden="true" className="h-3.5 w-3.5 shrink-0 text-neutral-600" />
        </div>
    )
}

function linkGroupGrid(device: FooterPreviewDevice, count: number): string {
    if (count <= 1) {
        return 'minmax(0, 1fr)'
    }

    const columns =
        device === 'desktop' ? Math.min(count, 3) : device === 'tablet' ? Math.min(count, 2) : 1

    return `repeat(${columns}, minmax(0, 1fr))`
}
