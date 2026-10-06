import { ArrowRight } from 'lucide-react'
import { useId } from 'react'

import type { StorefrontSectionProps } from '@/Components/Frontend/PageBuilder/types'

import { readBrandSectionConfig, readBrandSectionData, safeOptionalHref } from './brand-storefront'

interface Props {
    section: StorefrontSectionProps['section']
}

export default function BrandSpotlightSection({ section }: Props) {
    const headingId = useId()

    const config = readBrandSectionConfig(section.config)

    const brands = readBrandSectionData(section.data)

    if (brands.length === 0) {
        return null
    }

    const primaryHref = safeOptionalHref(config.primary_button_url)

    const secondaryHref = safeOptionalHref(config.secondary_button_url)

    const showPrimary = config.primary_button_label !== null && primaryHref !== null

    const showSecondary = config.secondary_button_label !== null && secondaryHref !== null

    const centered = config.alignment === 'center'

    return (
        <section aria-labelledby={headingId} className="overflow-x-clip py-6 sm:py-8">
            <div className="mx-auto max-w-7xl px-4 sm:px-6 lg:px-8">
                <div
                    className={[
                        'relative overflow-hidden rounded-3xl',
                        'p-5 text-white shadow-xl',
                        'sm:p-10 lg:p-12',
                    ].join(' ')}
                    style={{
                        backgroundColor: config.background_color,

                        backgroundImage:
                            'linear-gradient(90deg, rgba(30, 27, 75, 0.82) 0%, rgba(49, 46, 129, 0.78) 48%, rgba(15, 23, 42, 0.96) 100%)',
                    }}
                >
                    <div
                        aria-hidden="true"
                        className="pointer-events-none absolute -right-24 -top-24 h-72 w-72 rounded-full bg-white/[0.05] blur-3xl"
                    />

                    <div
                        aria-hidden="true"
                        className="pointer-events-none absolute -bottom-28 left-1/3 h-72 w-72 rounded-full bg-indigo-400/[0.08] blur-3xl"
                    />

                    <div className="relative grid min-w-0 grid-cols-1 items-center gap-10 lg:grid-cols-12">
                        <div
                            className={[
                                'min-w-0 lg:col-span-7',

                                centered ? 'text-center' : 'text-left',
                            ].join(' ')}
                        >
                            {config.eyebrow && (
                                <p>
                                    <span
                                        className={[
                                            'inline-flex max-w-full rounded-full border',
                                            'border-indigo-400/30 bg-indigo-500/20',
                                            'break-words px-3 py-1 text-center',
                                            '[overflow-wrap:anywhere]',
                                            'text-xs font-bold uppercase tracking-[0.16em]',
                                            'text-indigo-100',
                                        ].join(' ')}
                                    >
                                        {config.eyebrow}
                                    </span>
                                </p>
                            )}

                            <h2
                                id={headingId}
                                className={[
                                    'break-words text-3xl font-extrabold tracking-tight',
                                    '[overflow-wrap:anywhere]',
                                    'sm:text-4xl',

                                    config.eyebrow ? 'mt-4' : '',
                                ].join(' ')}
                            >
                                {config.heading}
                            </h2>

                            {config.description && (
                                <p
                                    className={[
                                        'mt-4 max-w-xl break-words text-sm leading-7',
                                        '[overflow-wrap:anywhere]',
                                        'text-indigo-100/90',

                                        centered ? 'mx-auto' : '',
                                    ].join(' ')}
                                >
                                    {config.description}
                                </p>
                            )}

                            {(showPrimary || showSecondary) && (
                                <div
                                    className={[
                                        'mt-6 flex min-w-0 flex-wrap gap-3',

                                        centered ? 'justify-center' : 'justify-start',
                                    ].join(' ')}
                                >
                                    {showPrimary && (
                                        <a
                                            href={primaryHref}
                                            className={[
                                                'inline-flex min-h-11 max-w-full items-center justify-center gap-2',
                                                'break-words rounded-xl bg-white px-6 py-3 text-center',
                                                '[overflow-wrap:anywhere]',
                                                'text-xs font-bold text-slate-900',
                                                'transition hover:bg-slate-100',
                                                'focus-visible:outline-none',
                                                'focus-visible:ring-2',
                                                'focus-visible:ring-white',
                                                'focus-visible:ring-offset-2',
                                                'focus-visible:ring-offset-indigo-900',
                                                'motion-reduce:transition-none',
                                            ].join(' ')}
                                        >
                                            <span className="min-w-0">
                                                {config.primary_button_label}
                                            </span>

                                            <ArrowRight
                                                aria-hidden="true"
                                                className="h-4 w-4 shrink-0"
                                            />
                                        </a>
                                    )}

                                    {showSecondary && (
                                        <a
                                            href={secondaryHref}
                                            className={[
                                                'inline-flex min-h-11 max-w-full items-center justify-center gap-2',
                                                'break-words rounded-xl border border-indigo-300/40',
                                                '[overflow-wrap:anywhere]',
                                                'px-6 py-3 text-center',
                                                'text-xs font-bold text-white',
                                                'transition hover:bg-white/10',
                                                'focus-visible:outline-none',
                                                'focus-visible:ring-2',
                                                'focus-visible:ring-white',
                                                'focus-visible:ring-offset-2',
                                                'focus-visible:ring-offset-indigo-900',
                                                'motion-reduce:transition-none',
                                            ].join(' ')}
                                        >
                                            <span className="min-w-0">
                                                {config.secondary_button_label}
                                            </span>

                                            <ArrowRight
                                                aria-hidden="true"
                                                className="h-4 w-4 shrink-0"
                                            />
                                        </a>
                                    )}
                                </div>
                            )}
                        </div>

                        <div
                            role="list"
                            aria-label="Spotlight brands"
                            className={[
                                'grid min-w-0 gap-3',
                                'lg:col-span-5',

                                spotlightGridClass(config.columns),
                            ].join(' ')}
                        >
                            {brands.map((brand) => (
                                <BrandPill
                                    key={brand.id}
                                    name={brand.name}
                                    logoUrl={brand.logo_url}
                                    showName={config.show_name}
                                />
                            ))}
                        </div>
                    </div>
                </div>
            </div>
        </section>
    )
}

interface BrandPillProps {
    name: string
    logoUrl: string | null
    showName: boolean
}

function BrandPill({ name, logoUrl, showName }: BrandPillProps) {
    return (
        <div
            role="listitem"
            className={[
                'flex min-h-20 min-w-0 items-center justify-center',
                'rounded-2xl border border-white/10',
                'bg-white/10 p-3 text-center sm:p-4',
                'backdrop-blur-md',
                'transition',
                'hover:border-white/20 hover:bg-white/[0.14]',
                'motion-reduce:transition-none',
            ].join(' ')}
        >
            {logoUrl ? (
                <div className="flex min-w-0 max-w-full flex-col items-center gap-2">
                    <img
                        src={logoUrl}
                        alt={showName ? '' : name}
                        loading="lazy"
                        decoding="async"
                        draggable={false}
                        className="max-h-10 max-w-full object-contain brightness-0 invert"
                    />

                    {showName && (
                        <span className="max-w-full break-words text-xs font-bold tracking-wide text-white [overflow-wrap:anywhere]">
                            {name}
                        </span>
                    )}
                </div>
            ) : (
                <span className="max-w-full break-words text-sm font-extrabold tracking-widest text-white [overflow-wrap:anywhere]">
                    {name}
                </span>
            )}
        </div>
    )
}

function spotlightGridClass(columns: number): string {
    switch (columns) {
        case 3:
            return 'grid-cols-2 sm:grid-cols-3 lg:grid-cols-2'

        case 4:
        case 5:
        case 6:
            return 'grid-cols-2'

        case 2:
        default:
            return 'grid-cols-2'
    }
}
