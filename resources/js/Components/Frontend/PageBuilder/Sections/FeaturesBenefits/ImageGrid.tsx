import { ArrowRight, ImageIcon } from 'lucide-react'
import { useId } from 'react'

import type { FeatureBenefitItem, FeaturesBenefitsConfig } from './features-benefits-config'

interface Props {
    config: FeaturesBenefitsConfig
}

export default function ImageGrid({ config }: Props) {
    const headingId = useId()
    const descriptionId = useId()

    const lightTheme = config.text_theme === 'light'

    const centered = config.alignment === 'center'

    return (
        <section
            aria-labelledby={config.heading ? headingId : undefined}
            aria-describedby={config.description ? descriptionId : undefined}
            className="overflow-x-clip py-8 sm:py-12 lg:py-16"
            style={{
                backgroundColor: config.background_color,
            }}
        >
            <div className="mx-auto max-w-7xl px-4 sm:px-6 lg:px-8">
                <div
                    className={[
                        'rounded-3xl border p-6 shadow-sm sm:p-9 lg:p-12',
                        lightTheme
                            ? 'border-white/10 bg-white/[0.06]'
                            : 'border-slate-200/80 bg-white',
                    ].join(' ')}
                >
                    <header
                        className={['max-w-3xl', centered ? 'mx-auto text-center' : ''].join(' ')}
                    >
                        {config.eyebrow && (
                            <span
                                className={[
                                    'inline-flex rounded-full px-3.5 py-1.5',
                                    'text-xs font-bold uppercase tracking-[0.18em]',
                                    lightTheme
                                        ? 'bg-white/10 text-emerald-200'
                                        : 'bg-emerald-50 text-emerald-700',
                                ].join(' ')}
                            >
                                {config.eyebrow}
                            </span>
                        )}

                        {config.heading && (
                            <h2
                                id={headingId}
                                className={[
                                    config.eyebrow ? 'mt-4' : '',
                                    'break-words text-3xl font-extrabold tracking-tight',
                                    '[overflow-wrap:anywhere]',
                                    'sm:text-4xl lg:text-[2.65rem]',
                                    lightTheme ? 'text-white' : 'text-slate-950',
                                ].join(' ')}
                            >
                                {config.heading}
                            </h2>
                        )}

                        {config.description && (
                            <p
                                id={descriptionId}
                                className={[
                                    'mt-4 break-words text-base leading-7',
                                    '[overflow-wrap:anywhere] sm:text-lg',
                                    lightTheme ? 'text-slate-300' : 'text-slate-600',
                                ].join(' ')}
                            >
                                {config.description}
                            </p>
                        )}
                    </header>

                    <div
                        role="list"
                        aria-label="Benefits"
                        className={[
                            'mt-10 grid min-w-0 gap-6 lg:mt-12',
                            getGridClass(config.columns),
                        ].join(' ')}
                    >
                        {config.items.map((item, index) => (
                            <ImageCard
                                key={`${item.title}-${index}`}
                                item={item}
                                centered={centered}
                                lightTheme={lightTheme}
                            />
                        ))}
                    </div>
                </div>
            </div>
        </section>
    )
}

interface ImageCardProps {
    item: FeatureBenefitItem
    centered: boolean
    lightTheme: boolean
}

function ImageCard({ item, centered, lightTheme }: ImageCardProps) {
    const imageUrl = item.image

    const href = item.link_url

    const hasLink = item.link_label !== null && href !== null

    return (
        <article
            role="listitem"
            className={[
                'group min-w-0 overflow-hidden rounded-2xl border',
                'transition duration-300 motion-reduce:transition-none',
                lightTheme
                    ? 'border-white/10 bg-white/[0.06] hover:border-white/20'
                    : 'border-slate-200/80 bg-slate-50 hover:-translate-y-1 hover:border-indigo-200 hover:bg-white hover:shadow-xl hover:shadow-slate-200/60 motion-reduce:hover:translate-y-0',
            ].join(' ')}
        >
            <div
                className={[
                    'relative overflow-hidden',
                    configImageHeight(),
                    lightTheme ? 'bg-white/5' : 'bg-slate-200',
                ].join(' ')}
            >
                {imageUrl ? (
                    <img
                        src={imageUrl}
                        alt={item.image_alt ?? ''}
                        loading="lazy"
                        decoding="async"
                        className={[
                            'h-full w-full object-cover',
                            'transition-transform duration-500',
                            'group-hover:scale-105',
                            'motion-reduce:transition-none motion-reduce:group-hover:scale-100',
                        ].join(' ')}
                    />
                ) : (
                    <div className="flex h-full min-h-48 items-center justify-center">
                        <ImageIcon
                            aria-hidden="true"

                            className={[
                                'h-10 w-10',
                                lightTheme ? 'text-white/30' : 'text-slate-400',
                            ].join(' ')}
                        />
                    </div>
                )}

                <div
                    aria-hidden="true"
                    className="pointer-events-none absolute inset-x-0 bottom-0 h-20 bg-gradient-to-t from-black/15 to-transparent"
                />
            </div>

            <div className={['p-6', centered ? 'text-center' : ''].join(' ')}>
                <h3
                    className={[
                        'text-xl font-bold tracking-tight',
                        lightTheme ? 'text-white' : 'text-slate-900',
                    ].join(' ')}
                >
                    {item.title}
                </h3>

                {item.description && (
                    <p
                        className={[
                            'mt-2 text-sm leading-6',
                            lightTheme ? 'text-slate-300' : 'text-slate-600',
                        ].join(' ')}
                    >
                        {item.description}
                    </p>
                )}

                {hasLink && (
                    <a
                        href={href}
                        className={[
                            'mt-5 inline-flex min-h-10 items-center gap-1.5 rounded-lg',
                            'text-sm font-semibold transition',
                            'focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-offset-2',
                            lightTheme
                                ? 'text-emerald-200 hover:text-white focus-visible:ring-white'
                                : 'text-indigo-600 hover:text-indigo-800 focus-visible:ring-indigo-600',
                        ].join(' ')}
                    >
                        {item.link_label}

                        <ArrowRight
                            aria-hidden="true"
                            className="h-4 w-4 transition-transform group-hover:translate-x-1 motion-reduce:transition-none"
                        />
                    </a>
                )}
            </div>
        </article>
    )
}

function configImageHeight(): string {
    return 'h-52 sm:h-56'
}

function getGridClass(columns: number): string {
    switch (columns) {
        case 2:
            return 'md:grid-cols-2'

        case 4:
            return 'sm:grid-cols-2 xl:grid-cols-4'

        default:
            return 'sm:grid-cols-2 lg:grid-cols-3'
    }
}
