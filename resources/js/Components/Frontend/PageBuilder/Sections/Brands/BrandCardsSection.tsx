import { ArrowRight } from 'lucide-react'
import { useId } from 'react'

import type { StorefrontSectionProps } from '@/Components/Frontend/PageBuilder/types'

import { readBrandSectionConfig, readBrandSectionData, safeOptionalHref } from './brand-storefront'

interface Props {
    section: StorefrontSectionProps['section']
}

export default function BrandCardsSection({ section }: Props) {
    const headingId = useId()

    const config = readBrandSectionConfig(section.config)

    const brands = readBrandSectionData(section.data)

    if (brands.length === 0) {
        return null
    }

    const lightTheme = config.text_theme === 'light'

    const viewAllHref = safeOptionalHref(config.view_all_url)

    const showViewAll = config.view_all_label !== null && viewAllHref !== null

    const centered = config.alignment === 'center'

    return (
        <section aria-labelledby={headingId} className="overflow-x-clip py-6 sm:py-8">
            <div className="mx-auto max-w-7xl px-4 sm:px-6 lg:px-8">
                <div
                    className={[
                        'rounded-3xl border p-5 shadow-sm',
                        'sm:p-10 lg:p-14',

                        lightTheme ? 'border-white/10' : 'border-slate-200/80',
                    ].join(' ')}
                    style={{
                        backgroundColor: config.background_color,
                    }}
                >
                    <header className="mb-10 flex min-w-0 flex-col gap-4 sm:flex-row sm:items-end sm:justify-between">
                        <div
                            className={[
                                'min-w-0',

                                centered ? 'text-center sm:text-left' : 'text-left',
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
                                    'break-words text-2xl font-extrabold tracking-tight',
                                    '[overflow-wrap:anywhere] sm:text-3xl',

                                    config.eyebrow ? 'mt-1' : '',

                                    lightTheme ? 'text-white' : 'text-slate-900',
                                ].join(' ')}
                            >
                                {config.heading}
                            </h2>

                            {config.description && (
                                <p
                                    className={[
                                        'mt-3 max-w-2xl break-words text-sm leading-6',
                                        '[overflow-wrap:anywhere]',

                                        lightTheme ? 'text-white/80' : 'text-slate-600',
                                    ].join(' ')}
                                >
                                    {config.description}
                                </p>
                            )}
                        </div>

                        {showViewAll && (
                            <a
                                href={viewAllHref}
                                className={[
                                    'inline-flex min-h-11 max-w-full items-center gap-1.5',
                                    'break-words text-left text-sm font-bold',
                                    '[overflow-wrap:anywhere]',
                                    'transition',
                                    'focus-visible:outline-none focus-visible:ring-2',
                                    'focus-visible:ring-offset-2',
                                    'motion-reduce:transition-none',

                                    centered ? 'self-center sm:self-auto' : 'self-start',

                                    lightTheme
                                        ? 'text-white hover:text-white/85 focus-visible:ring-white'
                                        : 'text-indigo-600 hover:text-indigo-700 focus-visible:ring-indigo-600',
                                ].join(' ')}
                            >
                                <span className="min-w-0">{config.view_all_label}</span>

                                <ArrowRight aria-hidden="true" className="h-4 w-4 shrink-0" />
                            </a>
                        )}
                    </header>

                    <div
                        role="list"
                        aria-label="Brands"
                        className={['grid gap-6', gridClass(config.columns)].join(' ')}
                    >
                        {brands.map((brand) => (
                            <article
                                key={brand.id}
                                role="listitem"
                                className={[
                                    'group flex min-w-0 flex-col rounded-2xl border p-6',
                                    'transition duration-200',
                                    'hover:-translate-y-0.5 hover:shadow-md',
                                    'motion-reduce:transform-none motion-reduce:transition-none',

                                    lightTheme
                                        ? 'border-white/10 bg-white/10 hover:border-white/30'
                                        : 'border-slate-200/80 bg-slate-50 hover:border-indigo-300',
                                ].join(' ')}
                            >
                                <BrandIdentity
                                    name={brand.name}
                                    logoUrl={brand.logo_url}
                                    lightTheme={lightTheme}
                                />

                                {config.show_description && brand.description && (
                                    <p
                                        className={[
                                            'mt-4 break-words text-xs leading-relaxed',
                                            '[overflow-wrap:anywhere]',

                                            lightTheme ? 'text-white/80' : 'text-slate-600',
                                        ].join(' ')}
                                    >
                                        {brand.description}
                                    </p>
                                )}

                                <div
                                    className={[
                                        'mt-auto flex min-w-0 flex-wrap items-center justify-between gap-3',
                                        'border-t pt-4 text-xs',

                                        config.show_description && brand.description
                                            ? 'mt-4'
                                            : 'mt-6',

                                        lightTheme
                                            ? 'border-white/15 text-white/80'
                                            : 'border-slate-200/60 text-slate-600',
                                    ].join(' ')}
                                >
                                    {config.show_product_count ? (
                                        <span className="break-words [overflow-wrap:anywhere]">
                                            {brand.product_count}{' '}
                                            {brand.product_count === 1 ? 'Product' : 'Products'}
                                        </span>
                                    ) : (
                                        <span aria-hidden="true" />
                                    )}

                                    {brand.slug !== '' && (
                                        <a
                                            href={brandProductsUrl(brand.slug)}
                                            className={[
                                                'inline-flex min-h-10 items-center gap-1 font-bold',
                                                'transition-transform',
                                                'group-hover:translate-x-1',
                                                'focus-visible:outline-none focus-visible:ring-2',
                                                'focus-visible:ring-offset-2',
                                                'motion-reduce:transform-none motion-reduce:transition-none',

                                                lightTheme
                                                    ? 'text-white focus-visible:ring-white'
                                                    : 'text-indigo-600 focus-visible:ring-indigo-600',
                                            ].join(' ')}
                                            aria-label={`Explore products from ${brand.name}`}
                                        >
                                            Explore
                                            <ArrowRight
                                                aria-hidden="true"
                                                className="h-4 w-4 shrink-0"
                                            />
                                        </a>
                                    )}
                                </div>
                            </article>
                        ))}
                    </div>
                </div>
            </div>
        </section>
    )
}

interface BrandIdentityProps {
    name: string
    logoUrl: string | null
    lightTheme: boolean
}

function BrandIdentity({ name, logoUrl, lightTheme }: BrandIdentityProps) {
    return (
        <div className="flex min-w-0 items-center gap-3">
            {logoUrl && (
                <div className="flex h-10 w-14 shrink-0 items-center justify-center overflow-hidden rounded-lg bg-white p-1.5">
                    <img
                        src={logoUrl}
                        alt=""
                        loading="lazy"
                        decoding="async"
                        draggable={false}
                        className="max-h-full max-w-full object-contain"
                    />
                </div>
            )}

            <h3
                className={[
                    'min-w-0 break-words text-xl font-extrabold tracking-tight',
                    '[overflow-wrap:anywhere]',

                    lightTheme ? 'text-white' : 'text-slate-900',
                ].join(' ')}
            >
                {name}
            </h3>
        </div>
    )
}

function gridClass(columns: number): string {
    switch (columns) {
        case 2:
            return 'grid-cols-1 sm:grid-cols-2'

        case 3:
            return 'grid-cols-1 sm:grid-cols-2 lg:grid-cols-3'

        case 5:
            return 'grid-cols-1 sm:grid-cols-2 lg:grid-cols-5'

        case 6:
            return 'grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 xl:grid-cols-6'

        case 4:
        default:
            return 'grid-cols-1 sm:grid-cols-2 lg:grid-cols-4'
    }
}

function brandProductsUrl(slug: string): string {
    return `/products?brand=${encodeURIComponent(slug)}`
}
