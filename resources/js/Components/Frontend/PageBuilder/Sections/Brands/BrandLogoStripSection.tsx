import { useId } from 'react'

import type { StorefrontSectionProps } from '@/Components/Frontend/PageBuilder/types'

import { readBrandSectionConfig, readBrandSectionData } from './brand-storefront'

interface Props {
    section: StorefrontSectionProps['section']
}

export default function BrandLogoStripSection({ section }: Props) {
    const headingId = useId()

    const config = readBrandSectionConfig(section.config)

    const brands = readBrandSectionData(section.data)

    if (brands.length === 0) {
        return null
    }

    const lightTheme = config.text_theme === 'light'

    const centered = config.alignment === 'center'

    return (
        <section aria-labelledby={headingId} className="overflow-x-clip py-6 sm:py-8">
            <div className="mx-auto max-w-7xl px-4 sm:px-6 lg:px-8">
                <div
                    className={[
                        'overflow-hidden rounded-3xl border p-5 shadow-sm',
                        'sm:p-8 lg:p-12',

                        lightTheme ? 'border-white/10' : 'border-slate-200/80',
                    ].join(' ')}
                    style={{
                        backgroundColor: config.background_color,
                    }}
                >
                    <header className={['mb-8', centered ? 'text-center' : 'text-left'].join(' ')}>
                        {config.eyebrow && (
                            <p
                                className={[
                                    'break-words text-xs font-bold uppercase tracking-[0.18em]',
                                    '[overflow-wrap:anywhere]',

                                    lightTheme ? 'text-white/75' : 'text-slate-500',
                                ].join(' ')}
                            >
                                {config.eyebrow}
                            </p>
                        )}

                        <h2
                            id={headingId}
                            className={[
                                config.eyebrow ? 'mt-2' : '',

                                'break-words text-xs font-bold uppercase tracking-[0.18em]',
                                '[overflow-wrap:anywhere]',

                                lightTheme ? 'text-white/85' : 'text-slate-600',
                            ].join(' ')}
                        >
                            {config.heading}
                        </h2>

                        {config.description && (
                            <p
                                className={[
                                    'mt-3 break-words text-sm leading-6',
                                    '[overflow-wrap:anywhere]',

                                    centered ? 'mx-auto max-w-2xl' : 'max-w-2xl',

                                    lightTheme ? 'text-white/80' : 'text-slate-600',
                                ].join(' ')}
                            >
                                {config.description}
                            </p>
                        )}
                    </header>

                    <div
                        role="list"
                        aria-label="Brands"
                        className={[
                            'grid items-center gap-x-6 gap-y-8',

                            getGridClass(config.columns),
                        ].join(' ')}
                    >
                        {brands.map((brand) => (
                            <BrandLogo
                                key={brand.id}
                                brand={brand}
                                showName={config.show_name}
                                lightTheme={lightTheme}
                            />
                        ))}
                    </div>
                </div>
            </div>
        </section>
    )
}

interface BrandLogoProps {
    brand: {
        id: number
        name: string
        logo_url: string | null
    }

    showName: boolean
    lightTheme: boolean
}

function BrandLogo({ brand, showName, lightTheme }: BrandLogoProps) {
    return (
        <div
            role="listitem"
            className="group flex min-w-0 flex-col items-center justify-center gap-2 px-2 py-1 text-center"
        >
            {brand.logo_url ? (
                <img
                    src={brand.logo_url}
                    alt={showName ? '' : brand.name}
                    loading="lazy"
                    decoding="async"
                    draggable={false}
                    className={[
                        'max-h-12 w-auto max-w-full object-contain sm:max-h-14',
                        'opacity-70 grayscale',
                        'transition duration-300',
                        'group-hover:opacity-100 group-hover:grayscale-0',
                        'motion-reduce:transition-none',
                    ].join(' ')}
                />
            ) : (
                <span
                    className={[
                        'max-w-full break-words text-xl font-black tracking-tight',
                        '[overflow-wrap:anywhere] sm:text-2xl',

                        lightTheme ? 'text-white' : 'text-slate-800',
                    ].join(' ')}
                >
                    {brand.name}
                </span>
            )}

            {showName && brand.logo_url && (
                <span
                    className={[
                        'max-w-full break-words text-xs font-semibold',
                        '[overflow-wrap:anywhere]',

                        lightTheme ? 'text-white/85' : 'text-slate-700',
                    ].join(' ')}
                >
                    {brand.name}
                </span>
            )}
        </div>
    )
}

function getGridClass(columns: number): string {
    switch (columns) {
        case 2:
            return 'grid-cols-2'

        case 3:
            return 'grid-cols-2 sm:grid-cols-3'

        case 4:
            return 'grid-cols-2 sm:grid-cols-4'

        case 5:
            return 'grid-cols-2 sm:grid-cols-3 lg:grid-cols-5'

        case 6:
        default:
            return 'grid-cols-2 sm:grid-cols-3 md:grid-cols-6'
    }
}
