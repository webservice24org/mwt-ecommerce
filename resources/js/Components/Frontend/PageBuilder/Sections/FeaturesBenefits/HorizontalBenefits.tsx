import { ArrowRight, CheckCircle2 } from 'lucide-react'
import { useId } from 'react'

import FeatureBenefitIcon from '@/PageBuilder/feature-benefit-icons'

import type { FeatureBenefitItem, FeaturesBenefitsConfig } from './features-benefits-config'

interface Props {
    config: FeaturesBenefitsConfig
}

const numberToneClasses = [
    'bg-indigo-600',
    'bg-purple-600',
    'bg-emerald-600',
    'bg-blue-600',
    'bg-rose-600',
    'bg-amber-600',
] as const

export default function HorizontalBenefits({ config }: Props) {
    const headingId = useId()

    const lightTheme = config.text_theme === 'light'

    const centered = config.alignment === 'center'
    const descriptionId = useId()

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
                        'rounded-3xl border p-6 shadow-sm',
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
                                    'text-3xl font-extrabold tracking-tight sm:text-4xl lg:text-[2.65rem]',
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
                                    'mt-4 text-base leading-7 sm:text-lg',
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
                        className="mt-10 min-w-0 space-y-4 lg:mt-12"
                    >
                        {config.items.map((item, index) => (
                            <BenefitRow
                                key={`${item.title}-${index}`}
                                item={item}
                                index={index}
                                lightTheme={lightTheme}
                            />
                        ))}
                    </div>
                </div>
            </div>
        </section>
    )
}

interface BenefitRowProps {
    item: FeatureBenefitItem
    index: number
    lightTheme: boolean
}

function BenefitRow({ item, index, lightTheme }: BenefitRowProps) {
    const href = item.link_url

    const hasLink = item.link_label !== null && href !== null

    const tone = numberToneClasses[index % numberToneClasses.length]

    return (
        <article
            role="listitem"
            className={[
                'group flex min-w-0 flex-col gap-5 rounded-2xl border p-5',
                'transition duration-300',
                'md:flex-row md:items-center md:justify-between',
                'sm:p-6',
                'motion-reduce:transition-none',
                lightTheme
                    ? 'border-white/10 bg-white/[0.06] hover:border-indigo-300/40 hover:bg-white/[0.09]'
                    : 'border-slate-200/80 bg-slate-50 hover:border-indigo-300 hover:bg-white hover:shadow-lg hover:shadow-slate-200/50',
            ].join(' ')}
        >
            <div className="flex min-w-0 items-start gap-4 sm:gap-5">
                <div
                    aria-hidden="true"
                    className={[
                        'flex h-12 w-12 shrink-0 items-center justify-center rounded-xl text-white shadow-sm',
                        tone,
                    ].join(' ')}
                >
                    {item.icon ? (
                        <FeatureBenefitIcon
                            name={item.icon}
                            className="h-6 w-6"
                            strokeWidth={1.9}
                        />
                    ) : (
                        <span className="text-sm font-extrabold">
                            {String(index + 1).padStart(2, '0')}
                        </span>
                    )}
                </div>

                <div className="min-w-0">
                    <h3
                        className={[
                            'break-words text-lg font-bold',
                            lightTheme ? 'text-white' : 'text-slate-900',
                        ].join(' ')}
                    >
                        {item.title}
                    </h3>

                    {item.description && (
                        <p
                            className={[
                                'mt-1.5 text-sm leading-6',
                                lightTheme ? 'text-slate-300' : 'text-slate-600',
                            ].join(' ')}
                        >
                            {item.description}
                        </p>
                    )}
                </div>
            </div>

            {hasLink && (
                <a
                    href={href}
                    className={[
                        'inline-flex min-h-10 shrink-0 items-center justify-center gap-2',
                        'self-start rounded-xl px-4 py-2 text-sm font-semibold',
                        'transition focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-offset-2',
                        'md:self-center',
                        lightTheme
                            ? 'bg-white/10 text-white hover:bg-white/15 focus-visible:ring-white'
                            : 'bg-emerald-50 text-emerald-700 hover:bg-emerald-100 focus-visible:ring-emerald-600',
                    ].join(' ')}
                >
                    <CheckCircle2 aria-hidden="true" className="h-4 w-4" />

                    <span>{item.link_label}</span>

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
