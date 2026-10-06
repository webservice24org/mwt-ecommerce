import { useId } from 'react'

import type { StorefrontSectionProps } from '@/Components/Frontend/PageBuilder/types'

import { readBrandSectionConfig, readBrandSectionData } from './brand-storefront'

interface Props {
    section: StorefrontSectionProps['section']
}

export default function BrandLogoMarqueeSection({ section }: Props) {
    const headingId = useId()

    const config = readBrandSectionConfig(section.config)

    const brands = readBrandSectionData(section.data)

    if (brands.length === 0) {
        return null
    }

    const lightTheme = config.text_theme === 'light'

    return (
        <section aria-labelledby={headingId} className="overflow-x-clip py-6 sm:py-8">
            <div className="mx-auto max-w-7xl px-4 sm:px-6 lg:px-8">
                <div
                    className={[
                        'relative overflow-hidden rounded-3xl border px-4 py-10 shadow-sm',
                        'sm:px-6 sm:py-12',

                        lightTheme ? 'border-white/10' : 'border-slate-200/80',
                    ].join(' ')}
                    style={{
                        backgroundColor: config.background_color,
                    }}
                >
                    <header
                        className={[
                            'mb-8 space-y-1',

                            config.alignment === 'center' ? 'text-center' : 'text-left',
                        ].join(' ')}
                    >
                        {config.eyebrow && (
                            <p
                                className={[
                                    'break-words text-xs font-bold uppercase tracking-[0.18em]',
                                    '[overflow-wrap:anywhere]',

                                    lightTheme ? 'text-white/80' : 'text-indigo-600',
                                ].join(' ')}
                            >
                                {config.eyebrow}
                            </p>
                        )}

                        <h2
                            id={headingId}
                            className={[
                                'break-words text-xl font-bold',
                                '[overflow-wrap:anywhere] sm:text-2xl',

                                lightTheme ? 'text-white' : 'text-slate-900',
                            ].join(' ')}
                        >
                            {config.heading}
                        </h2>

                        {config.description && (
                            <p
                                className={[
                                    'break-words pt-2 text-sm leading-6',
                                    '[overflow-wrap:anywhere]',

                                    config.alignment === 'center'
                                        ? 'mx-auto max-w-2xl'
                                        : 'max-w-2xl',

                                    lightTheme ? 'text-white/80' : 'text-slate-600',
                                ].join(' ')}
                            >
                                {config.description}
                            </p>
                        )}
                    </header>

                    <div
                        className={[
                            'brand-logo-marquee relative w-full overflow-hidden',

                            config.pause_on_hover ? 'brand-logo-marquee-pause' : '',
                        ].join(' ')}
                    >
                        <div
                            aria-hidden="true"
                            className="pointer-events-none absolute inset-y-0 left-0 z-10 w-12 sm:w-28"
                            style={{
                                background: `linear-gradient(to right, ${config.background_color}, transparent)`,
                            }}
                        />

                        <div
                            aria-hidden="true"
                            className="pointer-events-none absolute inset-y-0 right-0 z-10 w-12 sm:w-28"
                            style={{
                                background: `linear-gradient(to left, ${config.background_color}, transparent)`,
                            }}
                        />

                        <div
                            className="brand-logo-marquee-track"
                            style={{
                                animationDuration: `${config.marquee_duration}s`,
                            }}
                        >
                            <BrandSet
                                brands={brands}
                                showName={config.show_name}
                                lightTheme={lightTheme}
                            />

                            <BrandSet
                                brands={brands}
                                showName={config.show_name}
                                lightTheme={lightTheme}
                                duplicate
                            />
                        </div>
                    </div>
                </div>
            </div>
        </section>
    )
}

interface BrandSetProps {
    brands: ReturnType<typeof readBrandSectionData>

    showName: boolean
    lightTheme: boolean
    duplicate?: boolean
}

function BrandSet({ brands, showName, lightTheme, duplicate = false }: BrandSetProps) {
    return (
        <div
            role={duplicate ? undefined : 'list'}
            aria-label={duplicate ? undefined : 'Featured brands'}
            aria-hidden={duplicate ? true : undefined}
            className={[
                'brand-logo-marquee-set',

                duplicate ? 'brand-logo-marquee-duplicate' : '',
            ].join(' ')}
        >
            {brands.map((brand) => (
                <BrandLogo
                    key={`${duplicate ? 'duplicate' : 'primary'}-${brand.id}`}
                    name={brand.name}
                    logoUrl={brand.logo_url}
                    showName={showName}
                    lightTheme={lightTheme}
                    duplicate={duplicate}
                />
            ))}
        </div>
    )
}

interface BrandLogoProps {
    name: string
    logoUrl: string | null
    showName: boolean
    lightTheme: boolean
    duplicate: boolean
}

function BrandLogo({ name, logoUrl, showName, lightTheme, duplicate }: BrandLogoProps) {
    return (
        <div
            role={duplicate ? undefined : 'listitem'}
            className={[
                'flex min-w-[8rem] shrink-0 flex-col items-center justify-center gap-2',
                'text-center sm:min-w-[10rem]',
            ].join(' ')}
        >
            {logoUrl ? (
                <img
                    src={logoUrl}
                    alt={duplicate || showName ? '' : name}
                    loading="lazy"
                    decoding="async"
                    draggable={false}
                    className={[
                        'max-h-12 w-auto max-w-[8rem] object-contain',
                        'sm:max-h-14 sm:max-w-[10rem]',
                        'opacity-75 grayscale',
                        'transition duration-300',
                        'hover:opacity-100 hover:grayscale-0',
                        'motion-reduce:transition-none',
                    ].join(' ')}
                />
            ) : (
                <span
                    className={[
                        'max-w-[8rem] break-words text-xl font-black tracking-tight',
                        '[overflow-wrap:anywhere]',
                        'sm:max-w-[10rem] sm:text-2xl',

                        lightTheme ? 'text-white' : 'text-slate-800',
                    ].join(' ')}
                >
                    {name}
                </span>
            )}

            {showName && logoUrl && (
                <span
                    className={[
                        'max-w-[8rem] break-words text-xs font-semibold',
                        '[overflow-wrap:anywhere]',
                        'sm:max-w-[10rem]',

                        lightTheme ? 'text-white/85' : 'text-slate-700',
                    ].join(' ')}
                >
                    {name}
                </span>
            )}
        </div>
    )
}
