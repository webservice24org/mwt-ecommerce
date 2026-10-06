import { ArrowRight } from 'lucide-react'
import { useId } from 'react'

import FeatureBenefitIcon from '@/PageBuilder/feature-benefit-icons'

import type { FeatureBenefitItem, FeaturesBenefitsConfig } from './features-benefits-config'

interface Props {
    config: FeaturesBenefitsConfig
}

const iconToneClasses = [
    'bg-indigo-100 text-indigo-600 ring-indigo-200/70',
    'bg-purple-100 text-purple-600 ring-purple-200/70',
    'bg-emerald-100 text-emerald-600 ring-emerald-200/70',
    'bg-amber-100 text-amber-600 ring-amber-200/70',
    'bg-blue-100 text-blue-600 ring-blue-200/70',
    'bg-rose-100 text-rose-600 ring-rose-200/70',
] as const

export default function IconGrid({ config }: Props) {
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
            <div className="mx-auto w-full max-w-7xl px-4 sm:px-6 lg:px-8">
                <div
                    className={[
                        'overflow-hidden rounded-3xl border p-6 shadow-sm',
                        'sm:p-9 lg:p-12',
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
                                        ? 'bg-white/10 text-indigo-200'
                                        : 'bg-indigo-50 text-indigo-600',
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
                                    'sm:text-lg [overflow-wrap:anywhere]',
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
                            'mt-10 grid min-w-0 gap-5 lg:mt-12 lg:gap-6',
                            getGridClass(config.columns),
                        ].join(' ')}
                    >
                        {config.items.map((item, index) => (
                            <BenefitCard
                                key={`${item.title}-${index}`}
                                item={item}
                                index={index}
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

interface BenefitCardProps {
    item: FeatureBenefitItem
    index: number
    centered: boolean
    lightTheme: boolean
}

function BenefitCard({ item, index, centered, lightTheme }: BenefitCardProps) {
    const href = item.link_url

    const hasLink = item.link_label !== null && href !== null

    const tone = iconToneClasses[index % iconToneClasses.length]

    return (
        <article
            role="listitem"
            className={[
                'group min-w-0 rounded-2xl border p-6',
                'transition duration-300',
                'motion-reduce:transition-none',
                lightTheme
                    ? 'border-white/10 bg-white/[0.06] hover:border-indigo-300/40 hover:bg-white/[0.09]'
                    : 'border-slate-200/70 bg-slate-50 hover:-translate-y-1 motion-reduce:hover:translate-y-0 hover:border-indigo-300 hover:bg-white hover:shadow-xl hover:shadow-slate-200/50',
                centered ? 'text-center' : 'text-left',
            ].join(' ')}
        >
            <div className={centered ? 'flex justify-center' : ''}>
                <div
                    className={[
                        'flex h-12 w-12 items-center justify-center rounded-xl ring-1',
                        lightTheme ? 'bg-white/10 text-indigo-200 ring-white/10' : tone,
                    ].join(' ')}
                >
                    <FeatureBenefitIcon name={item.icon} className="h-6 w-6" strokeWidth={1.9} />
                </div>
            </div>

            <h3
                className={[
                    'mt-5 break-words text-lg font-bold',
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
                        'text-sm font-semibold outline-none transition',
                        'focus-visible:ring-2 focus-visible:ring-offset-2',
                        lightTheme
                            ? 'text-indigo-200 hover:text-white focus-visible:ring-white'
                            : 'text-indigo-600 hover:text-indigo-800 focus-visible:ring-indigo-600',
                    ].join(' ')}
                >
                    {item.link_label}

                    <ArrowRight
                        aria-hidden="true"
                        className={[
                            'h-4 w-4 shrink-0 transition-transform',
                            'group-hover:translate-x-1',
                            'motion-reduce:transition-none',
                            'motion-reduce:group-hover:translate-x-0',
                        ].join(' ')}
                    />
                </a>
            )}
        </article>
    )
}

function getGridClass(columns: number): string {
    switch (columns) {
        case 2:
            return 'sm:grid-cols-2'

        case 4:
            return 'sm:grid-cols-2 xl:grid-cols-4'

        default:
            return 'sm:grid-cols-2 lg:grid-cols-3'
    }
}
